<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ChatController extends Controller
{
    public function index(Request $request): View
    {
        $currentUser = $request->user();
        $users = $this->chatUsers($currentUser->id);
        $recipient = $this->selectedRecipient($request, $users);

        $messages = collect();

        if ($recipient) {
            $this->markConversationRead($currentUser->id, $recipient->id);

            $messages = $this->conversationQuery($currentUser->id, $recipient->id)
                ->with(['sender', 'recipient'])
                ->orderBy('id')
                ->limit(200)
                ->get();
        }

        $contacts = $users->map(fn (User $user) => [
            'user' => $user,
            'latest_message' => $this->latestMessage($currentUser->id, $user->id),
            'unread_count' => $this->unreadCount($currentUser->id, $user->id),
        ]);

        return view('chat.index', compact('contacts', 'recipient', 'messages'));
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $currentUser = $request->user();
        $data = $request->validate([
            'recipient_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query
                    ->where('id', '!=', $currentUser->id)
                    ->where('status', 'active')),
            ],
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $message = ChatMessage::query()->create([
            'sender_id' => $currentUser->id,
            'recipient_id' => $data['recipient_id'],
            'body' => trim($data['body']),
        ]);

        $message->load(['sender', 'recipient']);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $this->messagePayload($message, $currentUser->id),
            ]);
        }

        return redirect()
            ->route('chat.index', ['user_id' => $data['recipient_id']])
            ->with('success', 'Message sent.');
    }

    public function messages(Request $request, User $user): JsonResponse
    {
        $currentUser = $request->user();
        abort_if((int) $user->id === (int) $currentUser->id, 404);

        $afterId = max(0, $request->integer('after_id'));

        $this->markConversationRead($currentUser->id, $user->id);

        $messages = $this->conversationQuery($currentUser->id, $user->id)
            ->with(['sender', 'recipient'])
            ->when($afterId > 0, fn ($query) => $query->where('id', '>', $afterId))
            ->orderBy('id')
            ->limit(100)
            ->get()
            ->map(fn (ChatMessage $message) => $this->messagePayload($message, $currentUser->id))
            ->values();

        return response()->json([
            'messages' => $messages,
        ]);
    }

    private function chatUsers(int $currentUserId)
    {
        return User::query()
            ->with('branch')
            ->whereKeyNot($currentUserId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
    }

    private function selectedRecipient(Request $request, $users): ?User
    {
        $requestedId = $request->integer('user_id');

        if ($requestedId) {
            return $users->firstWhere('id', $requestedId);
        }

        return $users->first();
    }

    private function conversationQuery(int $currentUserId, int $recipientId)
    {
        return ChatMessage::query()
            ->where(function ($conversation) use ($currentUserId, $recipientId) {
                $conversation
                    ->where(function ($query) use ($currentUserId, $recipientId) {
                        $query
                            ->where('sender_id', $currentUserId)
                            ->where('recipient_id', $recipientId);
                    })
                    ->orWhere(function ($query) use ($currentUserId, $recipientId) {
                        $query
                            ->where('sender_id', $recipientId)
                            ->where('recipient_id', $currentUserId);
                    });
            });
    }

    private function latestMessage(int $currentUserId, int $recipientId): ?ChatMessage
    {
        return $this->conversationQuery($currentUserId, $recipientId)
            ->latest('id')
            ->first();
    }

    private function unreadCount(int $currentUserId, int $senderId): int
    {
        return ChatMessage::query()
            ->where('sender_id', $senderId)
            ->where('recipient_id', $currentUserId)
            ->whereNull('read_at')
            ->count();
    }

    private function markConversationRead(int $currentUserId, int $senderId): void
    {
        ChatMessage::query()
            ->where('sender_id', $senderId)
            ->where('recipient_id', $currentUserId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    private function messagePayload(ChatMessage $message, int $currentUserId): array
    {
        return [
            'id' => $message->id,
            'body' => $message->body,
            'is_mine' => (int) $message->sender_id === $currentUserId,
            'sender_name' => $message->sender?->name ?? 'Unknown user',
            'created_at' => $message->created_at?->format('d M Y, h:i A'),
        ];
    }
}
