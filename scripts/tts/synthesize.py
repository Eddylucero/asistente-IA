"""Send one JSON request to the local Piper worker and emit its WAV."""
import json
import os
import subprocess
import sys
import tempfile
import time
# Artisan's development server can omit SystemRoot from child environments.
# Winsock cannot initialize without it, even for localhost requests.
if os.name == "nt" and not os.environ.get("SystemRoot"):
    import ctypes
    directory = ctypes.create_unicode_buffer(32768)
    if not ctypes.windll.kernel32.GetWindowsDirectoryW(directory, len(directory)):
        raise OSError("Cannot locate the Windows system directory")
    os.environ["SystemRoot"] = directory.value

import urllib.error
import urllib.request
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
WORKER = Path(__file__).with_name("worker.py")
INFO = ROOT / "storage/app/private/tts/models/.runtime-cache/worker.json"
VERSION = str(WORKER.stat().st_mtime_ns)

def read_info():
    try:
        return json.loads(INFO.read_text(encoding="utf-8"))
    except (OSError, ValueError):
        return None

def request(info, path, body=None, timeout=55):
    data = json.dumps(body, ensure_ascii=False).encode("utf-8") if body is not None else None
    call = urllib.request.Request(
        f"http://127.0.0.1:{info['port']}{path}", data=data,
        headers={"X-Lumi-TTS-Token": info["token"], "Content-Type": "application/json"},
    )
    opener = urllib.request.build_opener(urllib.request.ProxyHandler({}))
    with opener.open(call, timeout=timeout) as response:
        return response.read()

def ensure_worker():
    info = read_info()
    if info:
        try:
            health = json.loads(request(info, "/health", timeout=1))
            if health["version"] == VERSION:
                return info
            request(info, "/stop", {}, timeout=1)
        except (OSError, ValueError, urllib.error.URLError):
            pass
    startup_errors = tempfile.TemporaryFile()
    options = {"stdin": subprocess.DEVNULL, "stdout": subprocess.DEVNULL, "stderr": startup_errors, "cwd": str(ROOT)}
    if os.name == "nt":
        options["creationflags"] = subprocess.CREATE_NO_WINDOW
    else:
        options["start_new_session"] = True
    process = subprocess.Popen([sys.executable, str(WORKER)], **options)
    deadline = time.monotonic() + 10
    while time.monotonic() < deadline:
        info = read_info()
        if info and info.get("version") == VERSION:
            try:
                request(info, "/health", timeout=1)
                startup_errors.close()
                return info
            except (OSError, urllib.error.URLError):
                pass
        if process.poll() is not None:
            break
        time.sleep(0.05)
    startup_errors.seek(0)
    details = startup_errors.read().decode("utf-8", errors="replace")
    startup_errors.close()
    raise RuntimeError("Local voice engine could not start. " + details)

def main():
    info = ensure_worker()
    if sys.argv[1] == "--warm":
        print(request(info, "/warm", {}, timeout=120).decode())
        return
    text = json.loads(sys.stdin.buffer.read().decode("utf-8"))["text"]
    result = request(info, "/synthesize", {"model": str(Path(sys.argv[1]).resolve()), "text": text})
    sys.stdout.buffer.write(result)

if __name__ == "__main__":
    main()
