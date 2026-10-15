<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use RuntimeException;

class LocalSpeech
{
    public function isAvailable(string $id): bool
    {
        $voice = config('speech.voices', [])[$id] ?? null;

        return $voice !== null && is_file(config('speech.python'))
            && is_file($this->modelPath($voice['model']))
            && is_file($this->modelPath($voice['model']).'.json');
    }

    public function voices(): array
    {
        $voices = [];
        foreach (config('speech.voices', []) as $id => $voice) {
            if ($this->isAvailable($id)) {
                $voices[] = ['id' => $id, 'name' => $voice['name']];
            }
        }

        return $voices;
    }

    public function defaultVoice(): string
    {
        $id = config('speech.default_voice');

        return $this->isAvailable($id) ? $id : 'auto';
    }

    public function synthesize(string $text, string $id): string
    {
        if (! $this->isAvailable($id)) {
            throw new RuntimeException('La voz seleccionada no está instalada.');
        }

        $voice = config('speech.voices')[$id];
        $response = $this->workerRequest('/synthesize', ['model' => $this->modelPath($voice['model']), 'text' => $text]);
        if ($response !== null) {
            if (! $response->successful() || ! $this->isWav($response->body())) {
                throw new RuntimeException('No se pudo generar la voz local.');
            }

            return $response->body();
        }

        $result = Process::timeout(config('speech.timeout'))
            ->input(json_encode(['text' => $text], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE))
            ->run([config('speech.python'), base_path('scripts/tts/synthesize.py'), $this->modelPath($voice['model'])]);

        if (! $result->successful() || ! $this->isWav($result->output())) {
            throw new RuntimeException('No se pudo generar la voz local. '.$result->errorOutput());
        }

        return $result->output();
    }

    public function prepare(string $id): bool
    {
        if (! $this->isAvailable($id)) {
            return false;
        }

        $model = $this->modelPath(config('speech.voices')[$id]['model']);
        $response = $this->workerRequest('/prepare', ['model' => $model], 2);
        if ($response !== null) {
            return $response->successful();
        }

        return Process::timeout(4)->run([
            config('speech.python'), base_path('scripts/tts/synthesize.py'), '--prepare', $model,
        ])->successful();
    }

    private function workerRequest(string $path, array $body, int $timeout = 55): ?Response
    {
        $file = config('speech.models_path').DIRECTORY_SEPARATOR.'.runtime-cache'.DIRECTORY_SEPARATOR.'worker.json';
        $info = is_file($file) ? json_decode(file_get_contents($file), true) : null;
        if (! is_array($info) || ! is_int($info['port'] ?? null) || $info['port'] < 1 || $info['port'] > 65535
            || ! is_string($info['token'] ?? null) || ! preg_match('/^[a-f0-9]{64}$/', $info['token'])
            || ($info['version'] ?? null) !== hash_file('sha256', base_path('scripts/tts/worker.py'))) {
            return null;
        }

        try {
            return Http::withHeaders(['X-Lumi-TTS-Token' => $info['token']])
                ->withOptions(['proxy' => '', 'allow_redirects' => false])->connectTimeout(1)->timeout($timeout)
                ->post('http://127.0.0.1:'.$info['port'].$path, $body);
        } catch (ConnectionException $exception) {
            if ($path === '/synthesize' && str_contains($exception->getMessage(), 'cURL error 28')) {
                throw new RuntimeException('La voz tardó demasiado en generarse.', previous: $exception);
            }

            return null;
        }
    }

    private function isWav(string $audio): bool
    {
        return str_starts_with($audio, 'RIFF') && substr($audio, 8, 4) === 'WAVE';
    }

    private function modelPath(string $model): string
    {
        return config('speech.models_path').DIRECTORY_SEPARATOR.$model.'.onnx';
    }
}
