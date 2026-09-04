<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Services\Assistant;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use RuntimeException;

class ConversationController extends Controller
{
    public function index(): View
    {
        return $this->screen(auth()->user()->latestConversationOrNull());
    }

    public function create(): View
    {
        return $this->screen(null);
    }

    public function show(Conversation $conversation): View
    {
        abort_unless($conversation->belongsToCurrentUser(), 404);

        return $this->screen($conversation);
    }

    public function history(): View
    {
        $conversations = auth()->user()->conversations()
            ->with('messages')
            ->withCount('messages')
            ->latest('updated_at')
            ->get();

        $weekStart = now()->startOfWeek();
        $weeklySessions = $conversations->filter(
            fn (Conversation $conversation) => $conversation->updated_at->gte($weekStart)
        )->count();

        $activity = collect(range(6, 0))->map(function (int $daysAgo) use ($conversations) {
            $date = now()->subDays($daysAgo);

            return [
                'label' => $date->isoFormat('dd'),
                'count' => $conversations->filter(
                    fn (Conversation $conversation) => $conversation->updated_at->isSameDay($date)
                )->count(),
            ];
        });

        return view('conversations.history', compact('conversations', 'weeklySessions', 'activity'));
    }

    public function destroy(Conversation $conversation): RedirectResponse
    {
        abort_unless($conversation->belongsToCurrentUser(), 404);

        $conversation->delete();

        return redirect()->route('conversations.history')->with('swal', [
            'icon' => 'success',
            'title' => 'Eliminado correctamente',
        ]);
    }

    public function store(Request $request, Assistant $assistant): JsonResponse
    {
        $validated = $request->validate([
            'content' => ['required', 'string', 'max:5000'],
            'conversation_id' => ['nullable', 'integer'],
            'new_conversation' => ['sometimes', 'boolean'],
        ]);

        try {
            [$conversation, $messages] = DB::transaction(function () use ($request, $validated, $assistant) {
                $conversation = $this->resolve(
                    $validated['conversation_id'] ?? null,
                    (bool) ($validated['new_conversation'] ?? false)
                );

                $question = $conversation->messages()->create([
                    'role' => 'user',
                    'content' => $validated['content'],
                ]);

                $conversation->rememberTitle($validated['content']);

                $answer = $conversation->messages()->create([
                    'role' => 'assistant',
                    'content' => $assistant->reply($conversation, $request->user(), $validated['content']),
                ]);

                return [$conversation, [$question, $answer]];
            });
        } catch (ConnectionException|RequestException|RuntimeException $exception) {
            report($exception);

            return response()->json([
                'message' => 'No pudimos obtener una respuesta de la IA. Inténtalo de nuevo.',
            ], 502);
        }

        return response()->json([
            'conversation' => [
                'id' => $conversation->id,
                'title' => $conversation->title,
            ],
            'messages' => collect($messages)->map(fn (Message $message) => $message->toPayload())->values(),
        ]);
    }

    private function screen(?Conversation $conversation): View
    {
        return view('conversations.index', [
            'conversation' => $conversation,
            'messages' => $conversation?->messages()->oldest()->get() ?? collect(),
            'suggestions' => $conversation?->suggestions() ?? [
                'Me siento cansado',
                'Estoy muy nervioso',
                'Necesito calma',
                'Quiero hablar de ansiedad',
            ],
        ]);
    }

    private function resolve(?int $id, bool $newConversation = false): Conversation
    {
        if ($newConversation) {
            return auth()->user()->conversations()->create();
        }

        if ($id === null || $id === 0) {
            return auth()->user()->latestConversationOrNull()
                ?? auth()->user()->conversations()->create();
        }

        return auth()->user()->conversations()->find($id)
            ?? throw new RuntimeException('La conversación solicitada no existe.');
    }
}
