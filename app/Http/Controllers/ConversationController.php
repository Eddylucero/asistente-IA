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
        return $this->screen(auth()->user()->latestConversation());
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('conversations.show', auth()->user()->conversations()->create());
    }

    public function show(Conversation $conversation): View
    {
        abort_unless($conversation->belongsToCurrentUser(), 404);

        return $this->screen($conversation);
    }

    public function history(): View
    {
        $conversations = auth()->user()->conversations()
            ->withCount('messages')
            ->latest('updated_at')
            ->get();

        return view('conversations.history', compact('conversations'));
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
        ]);

        try {
            $messages = DB::transaction(function () use ($request, $validated, $assistant) {
                $conversation = $this->resolve($validated['conversation_id'] ?? null);

                $question = $conversation->messages()->create([
                    'role' => 'user',
                    'content' => $validated['content'],
                ]);

                $conversation->rememberTitle($validated['content']);

                $answer = $conversation->messages()->create([
                    'role' => 'assistant',
                    'content' => $assistant->reply($conversation, $request->user(), $validated['content']),
                ]);

                return [$question, $answer];
            });
        } catch (ConnectionException|RequestException|RuntimeException $exception) {
            report($exception);

            return response()->json([
                'message' => 'No pudimos obtener una respuesta de la IA. Inténtalo de nuevo.',
            ], 502);
        }

        return response()->json([
            'messages' => collect($messages)->map(fn (Message $message) => $message->toPayload())->values(),
        ]);
    }

    private function screen(Conversation $conversation): View
    {
        return view('conversations.index', [
            'conversation' => $conversation,
            'messages' => $conversation->messages()->oldest()->get(),
        ]);
    }

    private function resolve(?int $id): Conversation
    {
        if ($id === null) {
            return auth()->user()->latestConversation();
        }

        return auth()->user()->conversations()->find($id)
            ?? throw new RuntimeException('La conversación solicitada no existe.');
    }
}
