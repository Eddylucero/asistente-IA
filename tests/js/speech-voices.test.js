import test from 'node:test';
import assert from 'node:assert/strict';
import { browserVoiceId, chooseBrowserVoice, spanishVoices } from '../../resources/js/ui/speech-voices.js';

test('saved voice takes precedence over automatic voice selection', () => {
    const voices = [
        { name: 'English Natural', lang: 'en-US' },
        { name: 'México', lang: 'es-MX', voiceURI: 'mexico' },
        { name: 'España', lang: 'es-ES', voiceURI: 'españa' },
        { name: 'Spanish Natural', lang: 'es-ES' },
    ];
    const synth = { getVoices: () => voices };
    assert.equal(chooseBrowserVoice(synth, browserVoiceId(voices[2])), voices[2]);
    assert.equal(chooseBrowserVoice(synth), voices[3]);
    assert.equal(chooseBrowserVoice(synth, 'browser:missing:es-ES'), voices[3]);
    assert.equal(spanishVoices(synth).length, 3);
});

test('voice identity retains the URI and language and handles delayed voice availability', () => {
    assert.equal(browserVoiceId({ voiceURI: 'Voz: con espacios', lang: 'es-EC' }), 'browser:Voz%3A%20con%20espacios:es-EC');
    assert.equal(chooseBrowserVoice({ getVoices: () => [] }), null);
    assert.deepEqual(spanishVoices(undefined), []);
});
