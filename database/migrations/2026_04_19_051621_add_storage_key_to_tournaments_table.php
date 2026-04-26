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
        Schema::table('tournaments', function (Blueprint $table) {
            $table->unsignedBigInteger('storage_key')->nullable()->after('id');
        });

        // Backfill existing data
        $tournaments = \App\Models\Tournament::all();
        $key = 1000;
        foreach ($tournaments as $tournament) {
            // we use raw DB to avoid model events
            \Illuminate\Support\Facades\DB::table('tournaments')
                ->where('id', $tournament->id)
                ->update(['storage_key' => $key++]);
        }

        Schema::table('tournaments', function (Blueprint $table) {
            $table->unsignedBigInteger('storage_key')->unique()->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            $table->dropColumn('storage_key');
        });
    }
};
