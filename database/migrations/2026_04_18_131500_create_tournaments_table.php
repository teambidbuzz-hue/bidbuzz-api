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
        Schema::create('tournaments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('organizer_id')->constrained('organizers')->cascadeOnDelete();
            $table->string('name');
            $table->string('season');
            $table->string('club_name');
            $table->integer('team_budget');
            $table->integer('max_players_per_team');
            $table->integer('player_base_price');
            $table->string('logo')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tournaments');
    }
};
