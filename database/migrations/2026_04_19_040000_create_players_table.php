<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('players', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tournament_id')->constrained('tournaments')->cascadeOnDelete();
            $table->string('photo')->nullable();
            $table->string('full_name');
            $table->integer('age');
            $table->string('phone_number')->nullable();
            $table->enum('batting_hand', ['Right', 'Left']);
            $table->enum('player_role', ['Batsman', 'Bowler', 'All-rounder', 'Wicketkeeper']);
            $table->enum('bowling_arm', ['Right-arm', 'Left-arm', 'N/A']);
            $table->enum('status', ['Pending', 'Rejected', 'Sold', 'Unsold'])->default('Pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('players');
    }
};
