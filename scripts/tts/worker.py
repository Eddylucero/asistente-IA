"""Local Piper process: keep voice models in memory between requests."""
import io
import json
import os
import secrets
import sys
import threading
import time
import wave
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
MODELS = ROOT / "storage/app/private/tts/models"
RUNTIME = MODELS / ".runtime-cache"
INFO = RUNTIME / "worker.json"
VERSION = str(Path(__file__).stat().st_mtime_ns)
VOICES = {}
LOCK = threading.Lock()
LAST_USE = time.monotonic()
TOKEN = secrets.token_hex(32)

def load_voice(model):
    import onnxruntime
    from piper import PiperVoice
    from piper.config import PiperConfig
    key = str(model)
    if key in VOICES:
        return VOICES[key]
    with model.with_suffix(".onnx.json").open(encoding="utf-8") as file:
        config = PiperConfig.from_dict(json.load(file))
    options = onnxruntime.SessionOptions()
    options.intra_op_num_threads = 2
    options.inter_op_num_threads = 1
    fingerprint = f"{onnxruntime.__version__}-{model.stat().st_size}-{model.stat().st_mtime_ns}"
    cached = RUNTIME / f"{model.stem}-{fingerprint}.onnx"
    temporary = RUNTIME / f"{model.stem}-{os.getpid()}.tmp.onnx"
    if cached.is_file():
        options.graph_optimization_level = onnxruntime.GraphOptimizationLevel.ORT_DISABLE_ALL
        source = cached
    else:
        options.optimized_model_filepath = str(temporary)
        source = model
    session = onnxruntime.InferenceSession(str(source), sess_options=options, providers=["CPUExecutionProvider"])
    if temporary.is_file():
        os.replace(temporary, cached)
    voice = PiperVoice(config=config, session=session)
    VOICES[key] = voice
    return voice

class Handler(BaseHTTPRequestHandler):
    def log_message(self, *args):
        pass

    def respond(self, code, data, kind="application/json"):
        self.send_response(code)
        self.send_header("Content-Type", kind)
        self.send_header("Content-Length", str(len(data)))
        self.end_headers()
        try:
            self.wfile.write(data)
        except (BrokenPipeError, ConnectionResetError, ConnectionAbortedError):
            pass

    def authorized(self):
        if self.headers.get("X-Lumi-TTS-Token") != TOKEN:
            self.respond(403, b'{"error":"Forbidden"}')
            return False
        return True

    def do_GET(self):
        if not self.authorized():
            return
        self.respond(200, json.dumps({"version": VERSION, "models": len(VOICES)}).encode())

    def do_POST(self):
        global LAST_USE
        if not self.authorized():
            return
        LAST_USE = time.monotonic()
        if self.path == "/stop":
            self.respond(200, b'{}')
            threading.Thread(target=self.server.shutdown, daemon=True).start()
            return
        try:
            length = int(self.headers.get("Content-Length", "0"))
            if length <= 0 or length > 100000:
                raise ValueError("Invalid request")
            request = json.loads(self.rfile.read(length))
            with LOCK:
                if self.path == "/warm":
                    for model in MODELS.glob("*.onnx"):
                        load_voice(model.resolve())
                    result = json.dumps({"models": len(VOICES)}).encode()
                    kind = "application/json"
                else:
                    model = Path(request["model"]).resolve()
                    text = request["text"]
                    if model.parent != MODELS.resolve() or model.suffix != ".onnx" or not isinstance(text, str) or not 0 < len(text) <= 5000:
                        raise ValueError("Invalid synthesis")
                    voice = load_voice(model)
                    buffer = io.BytesIO()
                    with wave.open(buffer, "wb") as audio:
                        voice.synthesize_wav(text, audio)
                    result = buffer.getvalue()
                    kind = "audio/wav"
            LAST_USE = time.monotonic()
            self.respond(200, result, kind)
        except Exception:
            self.respond(503, b'{"error":"Unable to synthesize local audio"}')

def main():
    RUNTIME.mkdir(exist_ok=True)
    server = ThreadingHTTPServer(("127.0.0.1", 0), Handler)
    server.daemon_threads = True
    temporary = RUNTIME / f"worker-{os.getpid()}.json"
    temporary.write_text(json.dumps({"port": server.server_port, "token": TOKEN, "pid": os.getpid(), "version": VERSION}), encoding="utf-8")
    os.replace(temporary, INFO)
    def idle_exit():
        while True:
            time.sleep(30)
            if time.monotonic() - LAST_USE > 600:
                server.shutdown()
                return
    threading.Thread(target=idle_exit, daemon=True).start()
    try:
        server.serve_forever()
    finally:
        server.server_close()
        try:
            if json.loads(INFO.read_text())["pid"] == os.getpid():
                INFO.unlink(missing_ok=True)
        except (OSError, ValueError):
            pass

if __name__ == "__main__":
    main()
