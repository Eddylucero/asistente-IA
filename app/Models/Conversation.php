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
            Str::contains($text, ['mi ex', 'ex pareja', 'me dejo', 'me abandono', 'ruptura', 'terminamos', 'termino conmigo'])
                && Str::contains($text, ['superar', 'olvidar', 'soltar', 'seguir', 'continuar'])
                => 'Proceso para superar una ruptura y recuperar estabilidad emocional.',
            Str::contains($text, ['mi ex', 'ex pareja', 'me dejo', 'me abandono', 'ruptura', 'terminamos', 'termino conmigo'])
                => 'Impacto emocional de una ruptura y necesidad de reconstruir el bienestar personal.',
            Str::contains($text, ['no se que hacer con mi vida', 'que hacer con mi vida', 'sin rumbo', 'proposito', 'sentido de vida'])
                => 'Búsqueda de dirección personal y claridad sobre los próximos pasos.',
            Str::contains($text, ['no puedo continuar', 'no puedo seguir', 'seguir adelante', 'salir adelante', 'continuar con mi vida'])
                => 'Necesidad de recuperar fuerzas y encontrar un camino para seguir adelante.',
            Str::contains($text, ['ansiedad', 'ansioso', 'ansiosa', 'nervioso', 'nerviosa', 'preocup'])
                => 'Preocupaciones y sensaciones de ansiedad que afectan el bienestar emocional.',
            Str::contains($text, ['tarea', 'tareas', 'deberes', 'estudiar', 'estudio', 'examen'])
                => 'Dificultad para organizar o avanzar con tareas y responsabilidades.',
            Str::contains($text, ['pujili']) && Str::contains($text, ['universidad', 'utc'])
                => 'Consulta sobre la presencia universitaria de la UTC en Pujilí.',
            Str::contains($text, ['solo', 'sola', 'soledad', 'nadie me entiende', 'me siento aislado', 'me siento aislada'])
                => 'Sensación de soledad y necesidad de recuperar conexión y apoyo emocional.',
            Str::contains($text, ['autoestima', 'no valgo', 'no soy suficiente', 'inseguro', 'insegura', 'confianza en mi'])
                => 'Dudas sobre el propio valor y búsqueda de mayor confianza personal.',
            Str::contains($text, ['enojo', 'enojado', 'enojada', 'rabia', 'ira', 'frustrado', 'frustrada'])
                => 'Manejo de emociones intensas y búsqueda de respuestas más tranquilas.',
            Str::contains($text, ['estres', 'agotado', 'agotada', 'cansado', 'cansada'])
                => 'Estrés y cansancio relacionados con las exigencias del día a día.',
            Str::contains($text, ['dormir', 'sueño', 'descansar', 'descanso'])
                => 'Dificultades emocionales relacionadas con el descanso y el sueño.',
            Str::contains($text, ['triste', 'tristeza', 'desanimado', 'desanimada'])
                => 'Un momento de tristeza o desánimo que necesita acompañamiento emocional.',
            Str::contains($text, ['duelo', 'fallecio', 'muerte', 'perdi a', 'perdida de'])
                => 'Proceso de duelo y adaptación emocional ante una pérdida importante.',
            Str::contains($text, ['trabajo', 'oficina', 'jefe', 'reunion', 'presentacion'])
                => 'Presiones laborales y búsqueda de equilibrio para afrontar el día a día.',
            Str::contains($text, ['pareja', 'familia', 'amigo', 'amiga', 'relacion', 'discusion'])
                => 'Dificultades en una relación y búsqueda de formas más sanas de comunicarse.',
            default => 'Reflexión sobre una experiencia personal y búsqueda de claridad emocional.',
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
        if ($this->title && $this->title !== 'Acompañamiento emocional') {
            return;
        }

        $this->update(['title' => $this->thematicTitle($content)]);
    }

    public function thematicTitle(?string $content = null): string
    {
        $content ??= $this->relationLoaded('messages')
            ? $this->messages->firstWhere('role', 'user')?->content
            : $this->messages()->where('role', 'user')->oldest()->value('content');

        $text = Str::ascii(Str::lower($content ?? ''));
        $hasAnxiety = Str::contains($text, ['ansiedad', 'ansioso', 'ansiosa', 'nervioso', 'nerviosa', 'preocupado', 'preocupada']);
        $hasTasks = Str::contains($text, ['tarea', 'tareas', 'deberes', 'estudiar', 'estudio', 'examen']);
        $hasBreakup = Str::contains($text, ['mi ex', 'mi pareja me dejo', 'me dejo mi pareja', 'ruptura', 'terminamos', 'termino conmigo']);
        $hasLifePurpose = Str::contains($text, ['no se que hacer con mi vida', 'que hacer con mi vida', 'sin rumbo', 'perdido', 'perdida', 'proposito']);
        $hasNeedToMoveForward = Str::contains($text, ['no puedo continuar', 'no puedo seguir', 'seguir adelante', 'continuar con mi vida', 'salir adelante']);
        $hasPujili = Str::contains($text, ['pujili']);
        $hasUniversity = Str::contains($text, ['universidad', 'utc']);

        $title = match (true) {
            $hasPujili && $hasUniversity => 'Consulta sobre universidades en Pujilí',
            $hasUniversity => 'Información sobre la Universidad Técnica de Cotopaxi',
            $hasAnxiety && $hasTasks => 'Apoyo para la ansiedad con tareas',
            $hasBreakup && $hasNeedToMoveForward => 'Fuerza para seguir adelante tras una ruptura',
            $hasBreakup => 'Acompañamiento tras una ruptura',
            $hasLifePurpose => 'Orientación para encontrar un rumbo',
            $hasNeedToMoveForward => 'Apoyo para seguir adelante',
            $hasAnxiety => 'Calma para la ansiedad',
            $hasTasks => 'Apoyo para organizar tareas',
            Str::contains($text, ['estres', 'agotado', 'agotada', 'cansado', 'cansada']) => 'Apoyo para el estrés y el cansancio',
            Str::contains($text, ['dormir', 'sueño', 'descansar', 'descanso']) => 'Apoyo para mejorar el descanso',
            Str::contains($text, ['triste', 'tristeza', 'desanimado', 'desanimada']) => 'Acompañamiento para la tristeza',
            Str::contains($text, ['pareja', 'familia', 'amigo', 'amiga', 'relacion', 'discusion']) => 'Apoyo en relaciones personales',
            default => 'Un espacio para ordenar lo que sientes',
        };

        return $title;
    }

}
