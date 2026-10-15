<?php

namespace App\Services;

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
        $result = Process::timeout(config('speech.timeout'))
            ->input(json_encode(['text' => $text], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE))
            ->run([config('speech.python'), base_path('scripts/tts/synthesize.py'), $this->modelPath($voice['model'])]);

        if (! $result->successful() || ! str_starts_with($result->output(), 'RIFF') || substr($result->output(), 8, 4) !== 'WAVE') {
            throw new RuntimeException('No se pudo generar la voz local. '.$result->errorOutput());
        }

        return $result->output();
    }

    private function modelPath(string $model): string
    {
        return config('speech.models_path').DIRECTORY_SEPARATOR.$model.'.onnx';
    }
}
