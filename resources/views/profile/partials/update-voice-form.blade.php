<form method="POST" action="{{ route('profile.voice.update') }}" class="flex flex-col gap-4" data-loader data-voice-preferences
    data-current-voice="{{ old('tts_voice', $user->tts_voice ?: 'auto') }}"
    data-default-voice="{{ app(\App\Services\LocalSpeech::class)->defaultVoice() }}"
    data-local-voices="{{ json_encode($localVoices) }}" data-endpoint="{{ route('speech.store') }}">
    @csrf
    @method('patch')
    <p class="text-body-md text-on-surface-variant">Elige cómo quieres escuchar a Lumi. Prueba las voces y guarda la que te resulte más agradable.</p>
    <div class="flex flex-col gap-2">
        <label class="text-label-md font-label-md text-on-surface" for="tts-voice">Voz de Lumi</label>
        <select id="tts-voice" name="tts_voice" data-voice-select class="w-full rounded-xl border-outline-variant/40 bg-surface-container-lowest text-on-surface focus:border-primary focus:ring-primary">
            <option value="auto">Automática (recomendada)</option>
        </select>
        @error('tts_voice', 'updateVoice') <p class="text-label-sm text-error">{{ $message }}</p> @enderror
    </div>
    <div class="flex flex-wrap gap-2">
        <button type="button" data-voice-preview class="inline-flex items-center gap-2 rounded-full bg-primary/10 px-4 py-2 text-primary hover:bg-primary/20 disabled:opacity-50"><span class="material-symbols-outlined" aria-hidden="true">volume_up</span>Escuchar muestra</button>
        <button type="button" data-voice-preview-stop hidden class="rounded-full bg-surface-container px-4 py-2 text-on-surface">Detener muestra</button>
    </div>
    <p data-voice-preview-status aria-live="polite" class="text-label-md text-on-surface-variant">Las voces del navegador pueden variar entre dispositivos.</p>
    <x-submit-button class="sm:w-auto sm:self-start sm:px-6" icon="save">Guardar voz</x-submit-button>
</form>
