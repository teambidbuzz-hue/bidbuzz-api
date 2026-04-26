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
        $tournaments = \DB::table('players')->distinct()->pluck('tournament_id');

        foreach ($tournaments as $tournamentId) {
            $players = \DB::table('players')
                ->where('tournament_id', $tournamentId)
                ->orderBy('created_at')
                ->get();

            foreach ($players as $index => $player) {
                \DB::table('players')
                    ->where('id', $player->id)
                    ->update(['sort_order' => $index + 1]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        \DB::table('players')->update(['sort_order' => null]);
    }
};
