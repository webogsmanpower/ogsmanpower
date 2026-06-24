<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * MessageController
 * 
 * Handles in-app messaging between Seekers and Employers.
 * Supports conversations, messages, read receipts, and file attachments.
 */
class MessageController extends Controller
{
    /**
     * Get all conversations for the authenticated user.
     */
    public function conversations(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = $request->input('per_page', 20);

        // Get conversations where user is a participant
        // Using whereRaw with JSON_CONTAINS for better compatibility
        $conversations = Conversation::query()
            ->where(function ($query) use ($user) {
                $query->whereRaw("JSON_CONTAINS(participants, ?)", [json_encode(['user_id' => $user->id])])
                      ->orWhereRaw("JSON_SEARCH(participants, 'one', ?) IS NOT NULL", [(string)$user->id]);
            })
            ->with(['latestMessage', 'jobApplication.job'])
            ->orderByDesc('last_message_at')
            ->paginate($perPage);

        // Transform conversations with additional data
        $conversations->getCollection()->transform(function ($conversation) use ($user) {
            return $this->transformConversation($conversation, $user);
        });

        return response()->json($conversations);
    }

    /**
     * Get a single conversation with messages.
     */
    public function show(Request $request, string $uuid): JsonResponse
    {
        $user = $request->user();

        $conversation = Conversation::where('uuid', $uuid)
            ->where(function ($query) use ($user) {
                $query->whereRaw("JSON_CONTAINS(participants, ?)", [json_encode(['user_id' => $user->id])])
                      ->orWhereRaw("JSON_SEARCH(participants, 'one', ?) IS NOT NULL", [(string)$user->id]);
            })
            ->with(['messages' => function ($query) {
                $query->orderBy('created_at', 'asc');
            }, 'messages.sender', 'messages.readReceipts', 'jobApplication.job'])
            ->firstOrFail();

        // Mark all unread messages as read
        $this->markConversationAsRead($conversation, $user);

        return response()->json([
            'conversation' => $this->transformConversation($conversation, $user),
            'messages' => $conversation->messages->map(function ($message) use ($user) {
                return $this->transformMessage($message, $user);
            }),
        ]);
    }

    /**
     * Get messages for a conversation (paginated).
     */
    public function messages(Request $request, string $uuid): JsonResponse
    {
        $user = $request->user();
        $perPage = $request->input('per_page', 50);

        $conversation = Conversation::where('uuid', $uuid)
            ->where(function ($query) use ($user) {
                $query->whereRaw("JSON_CONTAINS(participants, ?)", [json_encode(['user_id' => $user->id])])
                      ->orWhereRaw("JSON_SEARCH(participants, 'one', ?) IS NOT NULL", [(string)$user->id]);
            })
            ->firstOrFail();

        $messages = $conversation->messages()
            ->with(['sender', 'readReceipts', 'replyTo'])
            ->orderByDesc('created_at')
            ->paginate($perPage);

        $messages->getCollection()->transform(function ($message) use ($user) {
            return $this->transformMessage($message, $user);
        });

        return response()->json($messages);
    }

    /**
     * Start a new conversation or get existing one.
     */
    public function startConversation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'recipient_id' => 'required|exists:users,id',
            'job_application_id' => 'nullable|exists:job_applications,id',
            'subject' => 'nullable|string|max:255',
            'message' => 'required|string|max:5000',
        ]);

        $user = $request->user();
        $recipientId = $validated['recipient_id'];

        // Check if conversation already exists between these users
        $existingConversation = $this->findExistingConversation($user->id, $recipientId, $validated['job_application_id'] ?? null);

        if ($existingConversation) {
            // Add message to existing conversation
            $message = $this->createMessage($existingConversation, $user, $validated['message']);
            
            return response()->json([
                'conversation' => $this->transformConversation($existingConversation->fresh(['latestMessage']), $user),
                'message' => $this->transformMessage($message, $user),
            ]);
        }

        // Create new conversation
        $conversation = DB::transaction(function () use ($user, $recipientId, $validated) {
            $recipient = User::findOrFail($recipientId);
            
            $conversation = Conversation::create([
                'type' => 'direct',
                'job_application_id' => $validated['job_application_id'] ?? null,
                'subject' => $validated['subject'] ?? null,
                'participants' => [
                    ['user_id' => $user->id, 'role' => $this->getUserRole($user), 'joined_at' => now()->toISOString()],
                    ['user_id' => $recipientId, 'role' => $this->getUserRole($recipient), 'joined_at' => now()->toISOString()],
                ],
                'last_message_at' => now(),
            ]);

            // Create the first message
            $this->createMessage($conversation, $user, $validated['message']);

            return $conversation;
        });

        return response()->json([
            'conversation' => $this->transformConversation($conversation->fresh(['latestMessage', 'messages']), $user),
            'message' => $this->transformMessage($conversation->messages->first(), $user),
        ], 201);
    }

    /**
     * Send a message in a conversation.
     */
    public function sendMessage(Request $request, string $uuid): JsonResponse
    {
        $validated = $request->validate([
            'message' => 'required|string|max:5000',
            'reply_to_id' => 'nullable|exists:messages,id',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'file|max:10240', // 10MB max per file
        ]);

        $user = $request->user();

        $conversation = Conversation::where('uuid', $uuid)
            ->where(function ($query) use ($user) {
                $query->whereRaw("JSON_CONTAINS(participants, ?)", [json_encode(['user_id' => $user->id])])
                      ->orWhereRaw("JSON_SEARCH(participants, 'one', ?) IS NOT NULL", [(string)$user->id]);
            })
            ->firstOrFail();

        if ($conversation->is_closed) {
            return response()->json(['error' => 'This conversation is closed'], 403);
        }

        // Handle file attachments
        $attachments = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('messages/attachments', 'public');
                $attachments[] = [
                    'name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'url' => Storage::disk('public')->url($path),
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                ];
            }
        }

        $message = $this->createMessage(
            $conversation,
            $user,
            $validated['message'],
            $validated['reply_to_id'] ?? null,
            $attachments
        );

        return response()->json([
            'message' => $this->transformMessage($message, $user),
        ], 201);
    }

    /**
     * Mark messages as read.
     */
    public function markAsRead(Request $request, string $uuid): JsonResponse
    {
        $user = $request->user();

        $conversation = Conversation::where('uuid', $uuid)
            ->where(function ($query) use ($user) {
                $query->whereRaw("JSON_CONTAINS(participants, ?)", [json_encode(['user_id' => $user->id])])
                      ->orWhereRaw("JSON_SEARCH(participants, 'one', ?) IS NOT NULL", [(string)$user->id]);
            })
            ->firstOrFail();

        $this->markConversationAsRead($conversation, $user);

        return response()->json(['success' => true]);
    }

    /**
     * Get unread message count.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $user = $request->user();

        $count = Message::whereHas('conversation', function ($query) use ($user) {
            $query->whereRaw("JSON_CONTAINS(participants, ?)", [json_encode(['user_id' => $user->id])])
                  ->orWhereRaw("JSON_SEARCH(participants, 'one', ?) IS NOT NULL", [(string)$user->id]);
        })
        ->where('sender_id', '!=', $user->id)
        ->whereDoesntHave('readReceipts', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
        ->count();

        return response()->json(['unread_count' => $count]);
    }

    /**
     * Archive a conversation.
     */
    public function archive(Request $request, string $uuid): JsonResponse
    {
        $user = $request->user();

        $conversation = Conversation::where('uuid', $uuid)
            ->where(function ($query) use ($user) {
                $query->whereRaw("JSON_CONTAINS(participants, ?)", [json_encode(['user_id' => $user->id])])
                      ->orWhereRaw("JSON_SEARCH(participants, 'one', ?) IS NOT NULL", [(string)$user->id]);
            })
            ->firstOrFail();

        $conversation->archive();

        return response()->json(['success' => true]);
    }

    /**
     * Delete a message (soft delete).
     */
    public function deleteMessage(Request $request, int $messageId): JsonResponse
    {
        $user = $request->user();

        $message = Message::where('id', $messageId)
            ->where('sender_id', $user->id)
            ->firstOrFail();

        $message->softDeleteMessage();

        return response()->json(['success' => true]);
    }

    /**
     * Edit a message.
     */
    public function editMessage(Request $request, int $messageId): JsonResponse
    {
        $validated = $request->validate([
            'message' => 'required|string|max:5000',
        ]);

        $user = $request->user();

        $message = Message::where('id', $messageId)
            ->where('sender_id', $user->id)
            ->where('created_at', '>=', now()->subMinutes(15)) // Can only edit within 15 minutes
            ->firstOrFail();

        $message->edit($validated['message']);

        return response()->json([
            'message' => $this->transformMessage($message->fresh(), $user),
        ]);
    }

    // ==================== HELPER METHODS ====================

    /**
     * Find existing conversation between two users.
     */
    private function findExistingConversation(int $userId1, int $userId2, ?int $jobApplicationId = null): ?Conversation
    {
        return Conversation::query()
            ->where('type', 'direct')
            ->when($jobApplicationId, function ($query) use ($jobApplicationId) {
                $query->where('job_application_id', $jobApplicationId);
            })
            ->where(function ($query) use ($userId1) {
                $query->whereRaw("JSON_CONTAINS(participants, ?)", [json_encode(['user_id' => $userId1])])
                      ->orWhereRaw("JSON_SEARCH(participants, 'one', ?) IS NOT NULL", [(string)$userId1]);
            })
            ->where(function ($query) use ($userId2) {
                $query->whereRaw("JSON_CONTAINS(participants, ?)", [json_encode(['user_id' => $userId2])])
                      ->orWhereRaw("JSON_SEARCH(participants, 'one', ?) IS NOT NULL", [(string)$userId2]);
            })
            ->first();
    }

    /**
     * Create a new message.
     */
    private function createMessage(
        Conversation $conversation,
        User $user,
        string $content,
        ?int $replyToId = null,
        array $attachments = []
    ): Message {
        $messageType = !empty($attachments) ? 'file' : 'text';

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'sender_type' => $this->getUserRole($user),
            'message' => $content,
            'message_type' => $messageType,
            'reply_to_id' => $replyToId,
            'attachments' => !empty($attachments) ? $attachments : null,
        ]);

        $conversation->touchLastMessage();

        return $message->load('sender');
    }

    /**
     * Mark all unread messages in conversation as read.
     */
    private function markConversationAsRead(Conversation $conversation, User $user): void
    {
        $unreadMessages = $conversation->messages()
            ->where('sender_id', '!=', $user->id)
            ->whereDoesntHave('readReceipts', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->get();

        foreach ($unreadMessages as $message) {
            $message->markReadBy($user);
        }
    }

    /**
     * Get user role for messaging.
     */
    private function getUserRole(User $user): string
    {
        if ($user->employer) {
            return 'employer';
        }
        if ($user->seeker) {
            return 'seeker';
        }
        if ($user->is_admin) {
            return 'admin';
        }
        return 'user';
    }

    /**
     * Transform conversation for API response.
     */
    private function transformConversation(Conversation $conversation, User $currentUser): array
    {
        $participants = collect($conversation->participants);
        $otherParticipant = $participants->first(fn($p) => $p['user_id'] !== $currentUser->id);
        $otherUser = $otherParticipant ? User::with(['seeker', 'employer'])->find($otherParticipant['user_id']) : null;

        return [
            'id' => $conversation->uuid,
            'type' => $conversation->type,
            'subject' => $conversation->subject,
            'is_archived' => $conversation->is_archived,
            'is_closed' => $conversation->is_closed,
            'last_message_at' => $conversation->last_message_at?->toISOString(),
            'unread_count' => $conversation->getUnreadCountFor($currentUser->id),
            'last_message' => $conversation->latestMessage ? [
                'content' => $conversation->latestMessage->is_deleted 
                    ? '[Message deleted]' 
                    : $conversation->latestMessage->message,
                'sender_id' => $conversation->latestMessage->sender_id,
                'is_mine' => $conversation->latestMessage->sender_id === $currentUser->id,
                'created_at' => $conversation->latestMessage->created_at->toISOString(),
            ] : null,
            'contact' => $otherUser ? [
                'id' => $otherUser->id,
                'name' => $this->getContactName($otherUser),
                'title' => $this->getContactTitle($otherUser),
                'avatar' => $this->getContactAvatar($otherUser),
                'role' => $otherParticipant['role'] ?? 'user',
                'online' => $otherUser->last_login_at && $otherUser->last_login_at->diffInMinutes(now()) < 5,
            ] : null,
            'job' => $conversation->jobApplication?->job ? [
                'id' => $conversation->jobApplication->job->id,
                'title' => $conversation->jobApplication->job->title,
            ] : null,
            'created_at' => $conversation->created_at->toISOString(),
        ];
    }

    /**
     * Transform message for API response.
     */
    private function transformMessage(Message $message, User $currentUser): array
    {
        return [
            'id' => $message->id,
            'conversation_id' => $message->conversation_id,
            'sender' => [
                'id' => $message->sender_id,
                'name' => $message->sender ? $this->getContactName($message->sender) : 'Unknown',
                'avatar' => $message->sender ? $this->getContactAvatar($message->sender) : null,
            ],
            'is_mine' => $message->sender_id === $currentUser->id,
            'content' => $message->is_deleted ? '[Message deleted]' : $message->message,
            'message_type' => $message->message_type,
            'attachments' => $message->attachments ?? [],
            'reply_to' => $message->replyTo ? [
                'id' => $message->replyTo->id,
                'content' => $message->replyTo->is_deleted ? '[Message deleted]' : $message->replyTo->message,
                'sender_name' => $message->replyTo->sender ? $this->getContactName($message->replyTo->sender) : 'Unknown',
            ] : null,
            'is_edited' => $message->is_edited,
            'is_deleted' => $message->is_deleted,
            'is_read' => $message->sender_id === $currentUser->id 
                ? $message->readReceipts->isNotEmpty() 
                : $message->isReadBy($currentUser->id),
            'created_at' => $message->created_at->toISOString(),
            'edited_at' => $message->edited_at?->toISOString(),
        ];
    }

    /**
     * Get contact display name.
     */
    private function getContactName(User $user): string
    {
        if ($user->employer) {
            return $user->employer->company_name ?? $user->name;
        }
        if ($user->seeker) {
            return trim(($user->seeker->first_name ?? '') . ' ' . ($user->seeker->last_name ?? '')) ?: $user->name;
        }
        return $user->name;
    }

    /**
     * Get contact title/subtitle.
     */
    private function getContactTitle(User $user): string
    {
        if ($user->employer) {
            return $user->employer->industry ?? 'Employer';
        }
        if ($user->seeker) {
            return $user->seeker->job_title ?? $user->seeker->headline ?? 'Job Seeker';
        }
        return '';
    }

    /**
     * Get contact avatar URL.
     */
    private function getContactAvatar(User $user): ?string
    {
        if ($user->employer && $user->employer->logo_path) {
            return $user->employer->logo_path;
        }
        if ($user->seeker && $user->seeker->profile_image_path) {
            return $user->seeker->profile_image_path;
        }
        return null;
    }

    /**
     * Search users by name or email for starting new conversations.
     * Only returns seekers that employers can message.
     */
    public function searchUsers(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2|max:100'
        ]);

        $query = $request->input('q');
        $currentUser = $request->user();

        // Only allow employers to search for seekers
        if (!$currentUser->employer) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $users = User::where('role', 'seeker')
            ->where(function ($q) use ($query) {
                $q->where('email', 'LIKE', "%{$query}%")
                  ->orWhere('name', 'LIKE', "%{$query}%");
            })
            ->with('seeker')
            ->limit(10)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'first_name' => $user->seeker?->first_name,
                    'last_name' => $user->seeker?->last_name,
                    'headline' => $user->seeker?->headline,
                    'job_title' => $user->seeker?->job_title,
                ];
            });

        return response()->json($users);
    }
}
