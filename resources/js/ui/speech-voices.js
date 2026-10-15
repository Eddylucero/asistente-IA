export function browserVoiceId(voice) {
    return `browser:${encodeURIComponent(voice.voiceURI || voice.name || voice.lang)}:${voice.lang}`;
}

export function spanishVoices(synth) {
    return (synth?.getVoices() || []).filter((voice) => /^es(?:[-_]|$)/i.test(voice.lang));
}

export function chooseBrowserVoice(synth, preference = 'auto') {
    const voices = spanishVoices(synth);
    return voices.find((voice) => browserVoiceId(voice) === preference)
        || voices.find((voice) => /natural|neural|online/i.test(voice.name || ''))
        || voices.find((voice) => /^es[-_](EC|MX|US)/i.test(voice.lang))
        || voices.find((voice) => voice.default) || voices[0] || null;
}
