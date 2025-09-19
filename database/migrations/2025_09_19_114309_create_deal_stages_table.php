<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('deal_stages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_winning_stage')->default(false);
            $table->boolean('is_losing_stage')->default(false);
            $table->timestamps();

            $table->index(['is_active', 'order']);
            $table->index('order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deal_stages');
    }
};
