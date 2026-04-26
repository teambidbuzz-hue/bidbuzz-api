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
        Schema::create('auction_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('tournament_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('player_id')->constrained()->cascadeOnDelete();
            $table->string('action'); // 'Sold', 'Unsold'
            $table->foreignUuid('team_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('sold_price')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auction_histories');
    }
};
