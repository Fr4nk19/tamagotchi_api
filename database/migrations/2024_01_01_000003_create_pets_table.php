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
        Schema::create('pets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('stage')->default('egg');
            $table->integer('hunger')->default(50);
            $table->integer('happiness')->default(50);
            $table->integer('energy')->default(50);
            $table->integer('cleanliness')->default(50);
            $table->integer('health')->default(100);
            $table->integer('weight')->default(5);
            $table->integer('age_minutes')->default(0);
            $table->boolean('is_alive')->default(true);
            $table->boolean('is_sleeping')->default(false);
            $table->timestamp('born_at')->nullable();
            $table->timestamp('last_interaction_at')->nullable();
            $table->timestamp('last_calculated_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'is_alive']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pets');
    }
};
