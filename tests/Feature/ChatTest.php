<?php

namespace Tests\Feature;

use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_users_can_open_chat_and_send_messages(): void
    {
        $sender = User::factory()->create(['name' => 'Sender User', 'status' => 'active']);
        $recipient = User::factory()->create(['name' => 'Recipient User', 'status' => 'active']);

        $this->actingAs($sender)
            ->get(route('chat.index', absolute: false))
            ->assertOk()
            ->assertSee('Team Chat')
            ->assertSee('Recipient User');

        $this->actingAs($sender)
            ->postJson(route('chat.messages.store', absolute: false), [
                'recipient_id' => $recipient->id,
                'body' => 'Hello from the bakery floor.',
            ])
            ->assertOk()
            ->assertJsonPath('message.body', 'Hello from the bakery floor.')
            ->assertJsonPath('message.is_mine', true);

        $this->assertDatabaseHas('chat_messages', [
            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'body' => 'Hello from the bakery floor.',
        ]);
    }

    public function test_chat_polling_returns_only_messages_between_the_two_users_and_marks_incoming_as_read(): void
    {
        $currentUser = User::factory()->create(['status' => 'active']);
        $otherUser = User::factory()->create(['status' => 'active']);
        $thirdUser = User::factory()->create(['status' => 'active']);

        $incoming = ChatMessage::query()->create([
            'sender_id' => $otherUser->id,
            'recipient_id' => $currentUser->id,
            'body' => 'Please check today production.',
        ]);

        ChatMessage::query()->create([
            'sender_id' => $thirdUser->id,
            'recipient_id' => $currentUser->id,
            'body' => 'This should not be in the selected conversation.',
        ]);

        $this->actingAs($currentUser)
            ->getJson(route('chat.messages.index', $otherUser, false))
            ->assertOk()
            ->assertJsonCount(1, 'messages')
            ->assertJsonPath('messages.0.body', 'Please check today production.')
            ->assertJsonPath('messages.0.is_mine', false);

        $this->assertNotNull($incoming->fresh()->read_at);
    }

    public function test_users_cannot_message_themselves_or_suspended_users(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $suspendedUser = User::factory()->create(['status' => 'suspended']);

        $this->actingAs($user)
            ->postJson(route('chat.messages.store', absolute: false), [
                'recipient_id' => $user->id,
                'body' => 'Talking to myself.',
            ])
            ->assertJsonValidationErrors('recipient_id');

        $this->actingAs($user)
            ->postJson(route('chat.messages.store', absolute: false), [
                'recipient_id' => $suspendedUser->id,
                'body' => 'This should not send.',
            ])
            ->assertJsonValidationErrors('recipient_id');
    }
}
