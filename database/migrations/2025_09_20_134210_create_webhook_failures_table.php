<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_failures', function (Blueprint $table) {
            $table->id();
            $table->string('direction'); // inbound, outbound
            $table->string('event_type')->nullable();
            $table->string('endpoint')->nullable(); // For outbound webhooks
            $table->json('payload'); // Full payload for debugging
            $table->json('headers')->nullable(); // Request headers
            $table->text('error_message');
            $table->text('stack_trace')->nullable();
            $table->integer('final_attempts');
            $table->timestamp('first_failed_at');
            $table->timestamp('final_failed_at');
            $table->enum('failure_reason', ['network', 'timeout', 'validation', 'processing', 'unknown'])->default('unknown');
            $table->timestamps();

            $table->index('direction');
            $table->index('event_type');
            $table->index('failure_reason');
            $table->index('final_failed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_failures');
    }
};
