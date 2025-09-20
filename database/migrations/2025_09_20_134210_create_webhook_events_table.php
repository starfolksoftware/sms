<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('idempotency_key')->unique();
            $table->string('event_type'); // lead.created, deal.won, etc.
            $table->string('source'); // website_form, meta_ads, etc.
            $table->json('payload');
            $table->string('signature')->nullable(); // Provider signature
            $table->timestamp('received_at');
            $table->enum('status', ['pending', 'processing', 'processed', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->integer('attempts')->default(0);
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index('event_type');
            $table->index('source');
            $table->index('status');
            $table->index('received_at');
            $table->index(['status', 'attempts']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
    }
};
