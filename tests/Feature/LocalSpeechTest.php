<?php

namespace Tests\Feature;

use App\Services\LocalSpeech;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Process;
use RuntimeException;
use Tests\TestCase;

class LocalSpeechTest extends TestCase
{
    private string $models;

    protected function setUp(): void
    {
        parent::setUp();
        $this->models = storage_path('framework/testing/tts-'.uniqid());
        mkdir($this->models, 0777, true);
        file_put_contents($this->models.'/es_AR-daniela-high.onnx', 'model fixture');
        file_put_contents($this->models.'/es_AR-daniela-high.onnx.json', '{}');
        config(['speech.models_path' => $this->models, 'speech.python' => __FILE__]);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->models.'/*') as $file) {
            unlink($file);
        }
        rmdir($this->models);
        parent::tearDown();
    }

    public function test_available_voices_require_both_installed_model_files(): void
    {
        $speech = app(LocalSpeech::class);
        $this->assertCount(1, $speech->voices());
        $this->assertSame('piper:es_AR-daniela-high', $speech->defaultVoice());
        $this->assertFalse($speech->isAvailable('piper:../../secret'));
        unlink($this->models.'/es_AR-daniela-high.onnx.json');
        $this->assertSame([], $speech->voices());
        $this->assertSame('auto', $speech->defaultVoice());
    }

    public function test_text_is_passed_as_unicode_json_input_and_never_as_a_shell_argument(): void
    {
        $wav = 'RIFF0000WAVEaudio';
        $text = '¿Cómo estás? `command` $(command) "hola"';
        Process::fake(fn () => Process::result(output: $wav));
        $audio = app(LocalSpeech::class)->synthesize($text, 'piper:es_AR-daniela-high');
        $this->assertStringStartsWith($wav, $audio);
        Process::assertRan(function (PendingProcess $process) use ($text) {
            return $process->command === [__FILE__, base_path('scripts/tts/synthesize.py'), $this->models.DIRECTORY_SEPARATOR.'es_AR-daniela-high.onnx']
                && json_decode($process->input, true)['text'] === $text
                && $process->timeout === 60;
        });
    }

    public function test_invalid_engine_output_is_rejected(): void
    {
        Process::fake(fn () => Process::result(output: 'RIFF0000invalid'));
        $this->expectException(RuntimeException::class);
        app(LocalSpeech::class)->synthesize('Hola', 'piper:es_AR-daniela-high');
    }
}
