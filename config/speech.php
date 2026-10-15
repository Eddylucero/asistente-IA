<?php

return [
    'python' => env('TTS_PYTHON', base_path(DIRECTORY_SEPARATOR === '\\' ? '.venv-tts/Scripts/python.exe' : '.venv-tts/bin/python')),
    'models_path' => storage_path('app/private/tts/models'),
    'timeout' => 60,
    'default_voice' => 'piper:es_AR-daniela-high',
    'voices' => [
        'piper:es_AR-daniela-high' => ['name' => 'Daniela — Español (Argentina)', 'model' => 'es_AR-daniela-high'],
        'piper:es_MX-claude-high' => ['name' => 'Claude — Español (México)', 'model' => 'es_MX-claude-high'],
        'piper:es_ES-davefx-medium' => ['name' => 'Dave — Español (España)', 'model' => 'es_ES-davefx-medium'],
    ],
];
