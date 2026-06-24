<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create contract_messages table
 * 
 * Purpose: Encrypted Q&A messaging for contract-specific discussions.
 * Messages are encrypted using Laravel's Crypt facade for security.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('contract_messages', function (Blueprint $table) {
            $table->id();
            
            // Relationships
            $table->foreignId('contract_id')
                ->constrained('contracts')
                ->cascadeOnDelete()
                ->comment('Related contract');
            $table->foreignId('sender_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->comment('Message sender');
            
            // Content (encrypted)
            $table->text('content')
                ->comment('Encrypted message content');
            
            // Attachments
            $table->json('attachments')
                ->nullable()
                ->comment('Encrypted attachment paths');
            
            // Read status
            $table->timestamp('read_at')
                ->nullable()
                ->comment('When recipient read the message');
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('contract_id', 'idx_contract_messages_contract');
            $table->index(['contract_id', 'created_at'], 'idx_contract_messages_timeline');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_messages');
    }
};
