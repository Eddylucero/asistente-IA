<?php

namespace App\Services;

use Illuminate\Support\Str;

class QuickReply
{
    public function for(string $content, string $name): ?string
    {
        $text = $this->normalize($content);

        return $this->utcReply($text)
            ?? $this->calendarReply($text, $name)
            ?? $this->configuredReply($text, $name);
    }

    public function isUtcQuery(string $content): bool
    {
        return $this->mentions($this->normalize($content), config('psychology.utc.phrases', []));
    }

    private function utcReply(string $text): ?string
    {
        $knowledge = config('psychology.utc', []);

        if (! $this->mentions($text, $knowledge['phrases'] ?? [])) {
            return null;
        }

        if ($this->mentions($text, ['rector', 'rectora', 'autoridad'])) {
            return $knowledge['rector_response'] ?? null;
        }

        if ($this->mentions($text, ['facultad', 'facultades', 'carrera', 'carreras'])) {
            return $knowledge['faculties_response'] ?? null;
        }

        if ($this->mentions($text, ['extension', 'extensiones', 'sedes', 'campus'])
            && ! $this->mentions($text, ['salache', 'pujili', 'la mana', 'salcedo', 'matriz'])) {
            return $knowledge['extensions_response'] ?? null;
        }

        $locations = $knowledge['locations'] ?? [];
        $maps = $knowledge['maps'] ?? [];

        if ($this->mentions($text, ['pujili', 'universidad en pujili', 'universidad hay en pujili'])) {
            return 'En Pujilí se encuentra la Extensión Pujilí de la Universidad Técnica de Cotopaxi (UTC). Está ubicada en '.$locations['pujili']."\n\n[[UTC_MAPA_PUJILI]]\n\n[Abrir Extensión Pujilí en Google Maps]({$maps['pujili']})";
        }

        if ($this->mentions($text, ['salache'])) {
            return 'El Campus Salache de la UTC está en '.$locations['salache']."\n\n[[UTC_MAPA_SALACHE]]\n\n[Abrir Campus Salache en Google Maps]({$maps['salache']})";
        }

        if ($this->mentions($text, ['la mana'])) {
            return 'La UTC tiene una extensión en La Maná, ubicada en '.$locations['la_mana']."\n\n[[UTC_MAPA_LA_MANA]]\n\n[Abrir Extensión La Maná en Google Maps]({$maps['la_mana']})";
        }

        if ($this->mentions($text, ['salcedo'])) {
            return 'La UTC tiene un campus en Salcedo, ubicado en '.$locations['salcedo']."\n\n[[UTC_MAPA_SALCEDO]]\n\n[Abrir Campus Salcedo en Google Maps]({$maps['salcedo']})";
        }

        foreach ($knowledge['cities'] ?? [] as $city => $response) {
            if ($this->mentions($text, [$city]) && $this->mentions($text, ['universidad', 'utc', 'hay', 'existe'])) {
                return $response;
            }
        }

        if ($this->mentions($text, ['universidad'])
            && $this->mentions($text, ['hay', 'existe', 'alguna', 'que universidad'])) {
            return 'Sí. La Universidad Técnica de Cotopaxi (UTC) es la universidad a la que corresponden el Campus La Matriz, el Campus Salache y sus extensiones.';
        }

        if ($this->mentions($text, ['universidad', 'hay', 'existe', 'sede'])
            && ! $this->mentions($text, ['latacunga', 'pujili', 'salache', 'la mana', 'salcedo', 'donde queda', 'ubicacion', 'ubicada', 'direccion', 'campus'])) {
            return 'No tengo registrada una sede de la UTC en la ciudad que mencionas. Las sedes locales disponibles son Latacunga, Pujilí, La Maná y Salcedo.';
        }

        if ($this->mentions($text, ['donde queda', 'ubicacion', 'ubicada', 'direccion', 'campus'])) {
            return 'El Campus La Matriz de la UTC está en Latacunga, en '.$locations['matriz']."\n\n[[UTC_MAPA_MATRIZ]]\n\n[Abrir Campus La Matriz en Google Maps]({$maps['matriz']})";
        }

        return null;
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
