<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class Assistant
{
    private const ENDPOINT = 'https://api.groq.com/openai/v1/chat/completions';

    public function __construct(private readonly QuickReply $quickReply) {}

    public function reply(Conversation $conversation, User $user, string $content): string
    {
        return $this->quickReply->for($content, $user->name)
            ?? $this->ask($conversation, $user);
    }

    private function ask(Conversation $conversation, User $user): string
    {
        $response = Http::withToken(config('services.groq.key'))
            ->acceptJson()
            ->timeout(config('psychology.timeout'))
            ->post(self::ENDPOINT, [
                'model' => config('services.groq.model'),
                'messages' => $this->history($conversation, $user),
                'temperature' => config('psychology.temperature'),
                'max_tokens' => config('psychology.max_tokens'),
            ])
            ->throw();

        $content = $response->json('choices.0.message.content');

        if (! is_string($content) || trim($content) === '') {
            throw new RuntimeException('Groq devolvió una respuesta vacía.');
        }

        return trim($content);
    }

    private function history(Conversation $conversation, User $user): array
    {
        $messages = $conversation->messages()
            ->oldest()
            ->get(['role', 'content'])
            ->map(fn (Message $message) => [
                'role' => $message->role,
                'content' => $message->content,
            ])
            ->all();

        array_unshift($messages, [
            'role' => 'system',
            'content' => str_replace(':name', $user->name, config('psychology.system_prompt')),
        ]);

        return $messages;
    }
}
