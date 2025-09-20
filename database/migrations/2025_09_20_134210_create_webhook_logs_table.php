<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->string('direction'); // inbound, outbound
            $table->string('endpoint')->nullable(); // For outbound webhooks
            $table->string('event_type')->nullable(); // lead.created, deal.won, etc.
            $table->string('method')->default('POST');
            $table->string('headers_hash')->nullable(); // MD5 hash of headers for privacy
            $table->integer('response_status')->nullable();
            $table->text('response_body')->nullable(); // Truncated response
            $table->enum('status', ['pending', 'success', 'failed', 'retrying'])->default('pending');
            $table->integer('attempts')->default(1);
            $table->text('last_error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->json('metadata')->nullable(); // Additional context
            $table->timestamps();

            $table->index('direction');
            $table->index('event_type');
            $table->index('status');
            $table->index('sent_at');
            $table->index(['status', 'attempts']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_logs');
    }
};
