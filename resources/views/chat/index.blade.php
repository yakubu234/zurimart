@extends('layouts.app')

@section('title', 'Chat')
@section('page_title', 'Team Chat')
@section('page_intro', 'Send quick internal messages to other active users in the system.')

@push('css')
    <style>
        .chat-shell {
            min-height: 68vh;
        }

        .chat-contact-list {
            max-height: 68vh;
            overflow-y: auto;
        }

        .chat-contact {
            color: inherit;
            border-left: 4px solid transparent;
        }

        .chat-contact.active {
            border-left-color: #ffc107;
            background: #fff8e1;
        }

        .chat-message-list {
            height: 54vh;
            min-height: 360px;
            overflow-y: auto;
            background: linear-gradient(180deg, #f8fafc 0%, #eef2f7 100%);
        }

        .chat-bubble {
            max-width: 75%;
            border-radius: 1rem;
            padding: 0.75rem 0.9rem;
            box-shadow: 0 0.15rem 0.45rem rgba(15, 23, 42, 0.08);
            overflow-wrap: anywhere;
        }

        .chat-bubble.mine {
            margin-left: auto;
            color: #fff;
            background: #111;
            border-bottom-right-radius: 0.25rem;
        }

        .chat-bubble.theirs {
            margin-right: auto;
            color: #111827;
            background: #fff;
            border-bottom-left-radius: 0.25rem;
        }

        .chat-empty-state {
            min-height: 360px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: #6c757d;
        }

        @media (max-width: 767.98px) {
            .chat-message-list {
                height: 48vh;
            }

            .chat-bubble {
                max-width: 90%;
            }
        }
    </style>
@endpush

@section('page')
    <div class="row chat-shell">
        <div class="col-lg-4 mb-3 mb-lg-0">
            <div class="card card-warning h-100">
                <div class="card-header">
                    <h3 class="card-title">Users</h3>
                </div>
                <div class="list-group list-group-flush chat-contact-list">
                    @forelse ($contacts as $contact)
                        @php
                            $contactUser = $contact['user'];
                            $isActive = $recipient && (int) $recipient->id === (int) $contactUser->id;
                            $latestMessage = $contact['latest_message'];
                        @endphp
                        <a href="{{ route('chat.index', ['user_id' => $contactUser->id]) }}" class="list-group-item list-group-item-action chat-contact {{ $isActive ? 'active' : '' }}">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <strong>{{ $contactUser->name }}</strong>
                                    <div class="small text-muted">{{ $contactUser->branch?->name ?: ucfirst(str_replace('_', ' ', $contactUser->roleKey() ?: 'User')) }}</div>
                                </div>
                                @if ($contact['unread_count'] > 0)
                                    <span class="badge badge-danger">{{ $contact['unread_count'] }}</span>
                                @endif
                            </div>
                            <div class="small text-muted mt-2 text-truncate">
                                {{ $latestMessage?->body ?: 'No messages yet' }}
                            </div>
                        </a>
                    @empty
                        <div class="p-4 text-muted">No other active users are available for chat yet.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card card-info h-100">
                <div class="card-header">
                    <h3 class="card-title">
                        @if ($recipient)
                            Chat with {{ $recipient->name }}
                        @else
                            Select a user
                        @endif
                    </h3>
                </div>

                @if ($recipient)
                    <div id="chat-messages" class="card-body chat-message-list" data-messages-url="{{ route('chat.messages.index', $recipient) }}">
                        @forelse ($messages as $message)
                            <div class="mb-3 chat-message" data-message-id="{{ $message->id }}">
                                <div class="chat-bubble {{ $message->sender_id === auth()->id() ? 'mine' : 'theirs' }}">
                                    <div>{{ $message->body }}</div>
                                    <div class="small mt-1 {{ $message->sender_id === auth()->id() ? 'text-white-50' : 'text-muted' }}">
                                        {{ $message->sender_id === auth()->id() ? 'You' : $message->sender?->name }} · {{ $message->created_at->format('d M Y, h:i A') }}
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div id="chat-empty" class="chat-empty-state">
                                <div>
                                    <i class="far fa-comments fa-2x mb-2"></i>
                                    <div>No messages yet. Start the conversation.</div>
                                </div>
                            </div>
                        @endforelse
                    </div>
                    <div class="card-footer">
                        <form id="chat-form" action="{{ route('chat.messages.store') }}" method="POST">
                            @csrf
                            <input type="hidden" name="recipient_id" value="{{ $recipient->id }}">
                            <div class="input-group">
                                <textarea id="chat-body" name="body" class="form-control" rows="2" maxlength="2000" placeholder="Type your message..." required></textarea>
                                <div class="input-group-append">
                                    <button type="submit" class="btn btn-warning">
                                        <i class="far fa-paper-plane mr-1"></i> Send
                                    </button>
                                </div>
                            </div>
                            <div id="chat-status" class="small text-muted mt-2">Messages refresh automatically.</div>
                        </form>
                    </div>
                @else
                    <div class="card-body chat-empty-state">
                        <div>
                            <i class="far fa-user fa-2x mb-2"></i>
                            <div>Add another active user before using chat.</div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@if ($recipient)
    @push('js')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const messagesBox = document.getElementById('chat-messages');
                const form = document.getElementById('chat-form');
                const bodyInput = document.getElementById('chat-body');
                const status = document.getElementById('chat-status');
                const token = document.querySelector('meta[name="csrf-token"]').content;

                let lastMessageId = Number(messagesBox.querySelector('.chat-message:last-of-type')?.dataset.messageId || 0);
                let isLoading = false;

                const escapeText = (value) => {
                    const div = document.createElement('div');
                    div.textContent = value ?? '';
                    return div.innerHTML;
                };

                const scrollToBottom = () => {
                    messagesBox.scrollTop = messagesBox.scrollHeight;
                };

                const appendMessage = (message) => {
                    document.getElementById('chat-empty')?.remove();

                    if (messagesBox.querySelector(`[data-message-id="${message.id}"]`)) {
                        return;
                    }

                    const wrapper = document.createElement('div');
                    wrapper.className = 'mb-3 chat-message';
                    wrapper.dataset.messageId = message.id;
                    wrapper.innerHTML = `
                        <div class="chat-bubble ${message.is_mine ? 'mine' : 'theirs'}">
                            <div>${escapeText(message.body)}</div>
                            <div class="small mt-1 ${message.is_mine ? 'text-white-50' : 'text-muted'}">
                                ${message.is_mine ? 'You' : escapeText(message.sender_name)} · ${escapeText(message.created_at)}
                            </div>
                        </div>
                    `;
                    messagesBox.appendChild(wrapper);
                    lastMessageId = Math.max(lastMessageId, Number(message.id));
                    scrollToBottom();
                };

                const loadMessages = async () => {
                    if (isLoading) {
                        return;
                    }

                    isLoading = true;

                    try {
                        const url = new URL(messagesBox.dataset.messagesUrl, window.location.origin);
                        url.searchParams.set('after_id', lastMessageId);

                        const response = await fetch(url, {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        });

                        if (! response.ok) {
                            throw new Error('Unable to refresh messages.');
                        }

                        const data = await response.json();
                        data.messages.forEach(appendMessage);
                        status.textContent = 'Messages refresh automatically.';
                    } catch (error) {
                        status.textContent = error.message || 'Unable to refresh messages.';
                    } finally {
                        isLoading = false;
                    }
                };

                form.addEventListener('submit', async (event) => {
                    event.preventDefault();

                    const body = bodyInput.value.trim();

                    if (! body) {
                        return;
                    }

                    status.textContent = 'Sending...';

                    const formData = new FormData(form);
                    formData.set('body', body);

                    try {
                        const response = await fetch(form.action, {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': token,
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        });

                        if (! response.ok) {
                            throw new Error('Message could not be sent.');
                        }

                        const data = await response.json();
                        appendMessage(data.message);
                        bodyInput.value = '';
                        status.textContent = 'Message sent.';
                    } catch (error) {
                        status.textContent = error.message || 'Message could not be sent.';
                    }
                });

                bodyInput.addEventListener('keydown', (event) => {
                    if (event.key === 'Enter' && ! event.shiftKey) {
                        event.preventDefault();
                        form.requestSubmit();
                    }
                });

                scrollToBottom();
                setInterval(loadMessages, 5000);
            });
        </script>
    @endpush
@endif
