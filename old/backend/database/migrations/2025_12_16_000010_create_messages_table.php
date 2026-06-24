<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create messages table
 * 
 * Purpose: In-app messaging system for communication between
 * Seekers, Employers, and (future) Agents/Agencies.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Conversations table - groups messages between participants
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')
                ->unique()
                ->comment('Public conversation identifier');
            
            // Conversation type
            $table->enum('type', ['direct', 'group', 'support'])
                ->default('direct')
                ->comment('Conversation type');
            
            // Context (optional link to job application)
            $table->foreignId('job_application_id')
                ->nullable()
                ->constrained('job_applications')
                ->nullOnDelete()
                ->comment('Related job application if applicable');
            
            // Metadata
            $table->string('subject')
                ->nullable()
                ->comment('Conversation subject/title');
            $table->json('participants')
                ->comment('Array of participant user_ids with roles');
            $table->timestamp('last_message_at')
                ->nullable()
                ->comment('Timestamp of last message');
            
            // Status
            $table->boolean('is_archived')
                ->default(false)
                ->comment('Archived status');
            $table->boolean('is_closed')
                ->default(false)
                ->comment('Closed/resolved status');
            
            // Timestamps
            $table->timestamps();
            
            // Indexes
            $table->index('uuid', 'idx_conversations_uuid');
            $table->index('job_application_id', 'idx_conversations_application');
            $table->index('last_message_at', 'idx_conversations_last_message');
        });

        // Messages table - individual messages
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            
            // Conversation relationship
            $table->foreignId('conversation_id')
                ->constrained('conversations')
                ->cascadeOnDelete()
                ->comment('Parent conversation');
            
            // Sender
            $table->foreignId('sender_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->comment('Message sender');
            $table->enum('sender_type', ['seeker', 'employer', 'agent', 'agency', 'system', 'admin'])
                ->comment('Sender role type');
            
            // Message Content
            $table->text('message')
                ->comment('Message content');
            $table->enum('message_type', ['text', 'file', 'image', 'system', 'notification'])
                ->default('text')
                ->comment('Type of message');
            
            // Attachments
            $table->json('attachments')
                ->nullable()
                ->comment('Array of file attachments with paths and metadata');
            
            // Reply to (for threaded conversations)
            $table->foreignId('reply_to_id')
                ->nullable()
                ->constrained('messages')
                ->nullOnDelete()
                ->comment('Parent message if reply');
            
            // Read Status (per-user tracking in separate table for scalability)
            $table->timestamp('read_at')
                ->nullable()
                ->comment('When message was read (for direct messages)');
            
            // Metadata
            $table->json('metadata')
                ->nullable()
                ->comment('Additional metadata (formatting, mentions, etc.)');
            
            // Moderation
            $table->boolean('is_edited')
                ->default(false)
                ->comment('Message was edited');
            $table->timestamp('edited_at')
                ->nullable()
                ->comment('When message was edited');
            $table->boolean('is_deleted')
                ->default(false)
                ->comment('Soft delete flag for messages');
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('conversation_id', 'idx_messages_conversation');
            $table->index('sender_id', 'idx_messages_sender');
            $table->index('created_at', 'idx_messages_created');
            $table->index(['conversation_id', 'created_at'], 'idx_messages_conv_created');
        });

        // Message read receipts - tracks who has read which messages
        Schema::create('message_read_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')
                ->constrained('messages')
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->timestamp('read_at');
            
            $table->unique(['message_id', 'user_id'], 'idx_read_receipts_unique');
            $table->index('user_id', 'idx_read_receipts_user');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('message_read_receipts');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversations');
    }
};
