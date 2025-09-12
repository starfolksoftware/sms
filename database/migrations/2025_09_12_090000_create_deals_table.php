<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('deals', function (Blueprint $t) {
            $t->id();
            $t->string('title');

            $t->foreignId('contact_id')->constrained('contacts')->restrictOnDelete();
            $t->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $t->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();

            $t->decimal('amount', 12, 2)->nullable();
            $t->char('currency', 3)->default('USD');

            $t->string('stage')->default('new');
            $t->string('status')->default('open');
            $t->date('expected_close_date')->nullable();
            $t->unsignedTinyInteger('probability')->nullable();
            $t->text('lost_reason')->nullable();
            $t->decimal('won_amount', 12, 2)->nullable();
            $t->timestamp('closed_at')->nullable();

            $t->string('source')->default('manual');
            $t->json('source_meta')->nullable();
            $t->text('notes')->nullable();

            $t->timestamps();
            $t->softDeletes();

            $t->index('status');
            $t->index(['stage','status']);
            $t->index(['owner_id','status']);
            $t->index('contact_id');
            $t->index('expected_close_date');
            $t->index(['status','closed_at']);
            $t->index('source');
            $t->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deals');
    }
};
