<?php

namespace App\Services;

use Illuminate\Support\Str;

class QuickReply
{
    public function for(string $content, string $name): ?string
    {
        $text = $this->normalize($content);

        return $this->calendarReply($text, $name)
            ?? $this->configuredReply($text, $name);
    }

    private function normalize(string $content): string
    {
        return preg_replace('/[^a-z0-9\s]/', '', Str::ascii(Str::lower($content)));
    }

    private function calendarReply(string $text, string $name): ?string
    {
        $asksForDate = $this->mentions($text, ['que dia es hoy', 'fecha de hoy']);
        $asksForMonth = $this->mentions($text, ['que mes estamos', 'en que mes estamos']);
        $asksForYear = $this->mentions($text, ['que ano es', 'en que ano estamos']);
        $asksForName = $this->mentions($text, ['como me llamo', 'cual es mi nombre']);

        $date = match (true) {
            $asksForDate => now()->locale('es')->translatedFormat('l d \d\e F \d\e Y'),
            $asksForMonth => now()->locale('es')->translatedFormat('F \d\e Y'),
            $asksForYear => 'el año '.now()->format('Y'),
            default => null,
        };

        if ($date === null) {
            return null;
        }

        if ($asksForName) {
            return 'Hoy es '.$date.' y te llamas '.$name.'. ¿En qué más puedo ayudarte?';
        }

        return match (true) {
            $asksForDate => 'Hoy es '.$date.'.',
            $asksForMonth => 'Estamos en '.$date.'.',
            default => 'Estamos en '.$date.'.',
        };
    }

    private function configuredReply(string $text, string $name): ?string
    {
        foreach (config('psychology.local_responses', []) as $entry) {
            if ($this->mentions($text, $entry['phrases'])) {
                return str_replace(':name', $name, $entry['response']);
            }
        }

        return null;
    }

    private function mentions(string $text, array $phrases): bool
    {
        foreach ($phrases as $phrase) {
            if (str_contains($text, $phrase)) {
                return true;
            }
        }

        return false;
    }
}
