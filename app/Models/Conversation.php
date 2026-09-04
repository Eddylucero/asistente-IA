<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Conversation extends Model
{
    protected $fillable = [
        'user_id',
        'title',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public function summary(): string
    {
        $messages = $this->relationLoaded('messages')
            ? $this->messages
            : $this->messages()->oldest()->get();
        $userMessages = $messages->where('role', 'user');

        if ($userMessages->isEmpty()) {
            return 'Esta conversación aún no tiene un tema definido.';
        }

        $text = Str::ascii(Str::lower($userMessages
            ->pluck('content')
            ->implode(' ')));

        return match (true) {
            Str::contains($text, ['ansiedad', 'ansioso', 'ansiosa', 'nervioso', 'nerviosa'])
                && Str::contains($text, ['tarea', 'tareas', 'deberes', 'estudiar', 'estudio', 'examen'])
                => 'Ansiedad relacionada con la dificultad para avanzar en las tareas.',
            Str::contains($text, ['ansiedad', 'ansioso', 'ansiosa', 'nervioso', 'nerviosa', 'preocup'])
                => 'Preocupaciones y sensaciones de ansiedad que afectan el bienestar emocional.',
            Str::contains($text, ['tarea', 'tareas', 'deberes', 'estudiar', 'estudio', 'examen'])
                => 'Dificultad para organizar o avanzar con tareas y responsabilidades.',
            Str::contains($text, ['estres', 'agotado', 'agotada', 'cansado', 'cansada'])
                => 'Estrés y cansancio relacionados con las exigencias del día a día.',
            Str::contains($text, ['dormir', 'sueño', 'descansar', 'descanso'])
                => 'Dificultades emocionales relacionadas con el descanso y el sueño.',
            Str::contains($text, ['triste', 'tristeza', 'desanimado', 'desanimada'])
                => 'Un momento de tristeza o desánimo que necesita acompañamiento emocional.',
            Str::contains($text, ['pareja', 'familia', 'amigo', 'amiga', 'relacion', 'discusion'])
                => 'Situación emocional relacionada con vínculos y relaciones personales.',
            default => 'Una situación personal que requiere comprensión y acompañamiento emocional.',
        };
    }

    public function suggestions(): array
    {
        $text = Str::ascii(Str::lower($this->messages->where('role', 'user')->pluck('content')->implode(' ')));

        return match (true) {
            Str::contains($text, ['ansiedad', 'nervioso', 'estres', 'preocup']) => [
                '¿Qué situación está activando más esa sensación?',
                '¿Quieres que hagamos un ejercicio breve de respiración?',
                '¿Qué necesitas resolver primero ahora mismo?',
            ],
            Str::contains($text, ['cansado', 'sueño', 'dormir', 'descans']) => [
                '¿Qué ha dificultado tu descanso últimamente?',
                '¿Cómo te sentiste al despertar hoy?',
                '¿Quieres preparar una rutina sencilla para esta noche?',
            ],
            Str::contains($text, ['trabajo', 'oficina', 'presentacion', 'estudio', 'examen']) => [
                '¿Qué parte de esa situación te preocupa más?',
                '¿Qué paso pequeño podrías dar hoy?',
                '¿Quieres ordenar tus ideas antes de actuar?',
            ],
            Str::contains($text, ['pareja', 'familia', 'amigo', 'relacion', 'discusion']) => [
                '¿Qué te gustaría poder expresar en esa conversación?',
                '¿Qué necesitas de la otra persona?',
                '¿Quieres practicar cómo decirlo con calma?',
            ],
            default => [
                '¿Qué fue lo más importante de lo que hablamos?',
                '¿Cómo te sientes después de compartirlo?',
                '¿Qué te gustaría explorar ahora?',
            ],
        };
    }

    public function belongsToCurrentUser(): bool
    {
        return $this->user_id === auth()->id();
    }

    public function rememberTitle(string $content): void
    {
        if ($this->title) {
            return;
        }

        $text = Str::ascii(Str::lower($content));
        $hasAnxiety = Str::contains($text, ['ansiedad', 'ansioso', 'ansiosa', 'nervioso', 'nerviosa', 'preocupado', 'preocupada']);
        $hasTasks = Str::contains($text, ['tarea', 'tareas', 'deberes', 'estudiar', 'estudio', 'examen']);

        $title = match (true) {
            $hasAnxiety && $hasTasks => 'Apoyo para la ansiedad con tareas',
            $hasAnxiety => 'Calma para la ansiedad',
            $hasTasks => 'Apoyo para organizar tareas',
            Str::contains($text, ['estres', 'agotado', 'agotada', 'cansado', 'cansada']) => 'Apoyo para el estrés y el cansancio',
            Str::contains($text, ['dormir', 'sueño', 'descansar', 'descanso']) => 'Apoyo para mejorar el descanso',
            Str::contains($text, ['triste', 'tristeza', 'desanimado', 'desanimada']) => 'Acompañamiento para la tristeza',
            Str::contains($text, ['pareja', 'familia', 'amigo', 'amiga', 'relacion', 'discusion']) => 'Apoyo en relaciones personales',
            default => 'Acompañamiento emocional',
        };

        $this->update(['title' => $title]);
    }

}
