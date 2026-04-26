<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tournament;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\ImageService;

class TournamentController extends Controller
{
    /**
     * List all tournaments for the authenticated organizer.
     */
    public function index(Request $request): JsonResponse
    {
        $tournaments = Tournament::where('organizer_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($tournament) {
                return $this->formatTournament($tournament);
            });

        return response()->json([
            'tournaments' => $tournaments,
        ]);
    }

    /**
     * Create a new tournament.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'season' => 'required|string|max:50',
            'club_name' => 'required|string|max:255',
            'team_budget' => 'required|integer|min:0',
            'max_players_per_team' => 'required|integer|min:1',
            'player_base_price' => 'required|integer|min:0',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:10240',
        ]);

        $tournament = Tournament::create([
            'organizer_id' => $request->user()->id,
            'name' => $validated['name'],
            'season' => $validated['season'],
            'club_name' => $validated['club_name'],
            'team_budget' => $validated['team_budget'],
            'max_players_per_team' => $validated['max_players_per_team'],
            'player_base_price' => $validated['player_base_price'],
        ]);

        if ($request->hasFile('logo')) {
            $logoPath = ImageService::processAndStore($request->file('logo'), "tournaments/{$tournament->storage_key}/logo");
            $tournament->update(['logo' => $logoPath]);
        }

        return response()->json([
            'message' => 'Tournament created successfully.',
            'tournament' => $this->formatTournament($tournament),
        ], 201);
    }

    /**
     * Show a single tournament.
     */
    public function show(Request $request, Tournament $tournament): JsonResponse
    {
        // Ensure the tournament belongs to the authenticated organizer
        if ($tournament->organizer_id !== $request->user()->id) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        return response()->json([
            'tournament' => $this->formatTournament($tournament),
        ]);
    }

    /**
     * Get a single tournament publicly (No Auth required).
     */
    public function publicShow(Tournament $tournament): JsonResponse
    {
        $playerController = new \App\Http\Controllers\Api\PlayerController();
        
        // Use reflection or a direct query to format teams to avoid duplicating the private method. 
        // Actually, since formatTeam is private in TeamController, we'll format it manually or make it public.
        // Let's just format teams here.
        $teams = $tournament->teams()
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($team) {
                return [
                    'id' => $team->id,
                    'name' => $team->name,
                    'logo_url' => ImageService::mediaUrl($team->logo),
                    'created_at' => $team->created_at->toISOString(),
                ];
            });

        $players = $tournament->players()
            ->orderBy('sort_order', 'asc')
            ->get()
            ->map(function ($player) use ($playerController) {
                return $playerController->formatPlayer($player);
            });

        return response()->json([
            'tournament' => $this->formatTournament($tournament),
            'teams' => $teams,
            'players' => $players,
        ]);
    }

    /**
     * Update a tournament.
     */
    public function update(Request $request, Tournament $tournament): JsonResponse
    {
        if ($tournament->organizer_id !== $request->user()->id) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'season' => 'sometimes|required|string|max:50',
            'club_name' => 'sometimes|required|string|max:255',
            'team_budget' => 'sometimes|required|integer|min:0',
            'max_players_per_team' => 'sometimes|required|integer|min:1',
            'player_base_price' => 'sometimes|required|integer|min:0',
            'registration_closing_date' => 'nullable|date',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:10240',
        ]);

        if ($request->hasFile('logo')) {
            // Delete old logo if exists
            if ($tournament->logo) {
                ImageService::deleteMedia($tournament->logo);
            }
            $validated['logo'] = ImageService::processAndStore($request->file('logo'), "tournaments/{$tournament->storage_key}/logo");
        }

        $tournament->update($validated);

        return response()->json([
            'message' => 'Tournament updated successfully.',
            'tournament' => $this->formatTournament($tournament->fresh()),
        ]);
    }

    /**
     * Delete a tournament.
     */
    public function destroy(Request $request, Tournament $tournament): JsonResponse
    {
        if ($tournament->organizer_id !== $request->user()->id) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        // Delete logo file
        if ($tournament->logo) {
            ImageService::deleteMedia($tournament->logo);
        }

        $tournament->delete();

        return response()->json([
            'message' => 'Tournament deleted successfully.',
        ]);
    }

    /**
     * Format tournament for API response.
     */
    private function formatTournament(Tournament $tournament): array
    {
        return [
            'id' => $tournament->id,
            'name' => $tournament->name,
            'season' => $tournament->season,
            'club_name' => $tournament->club_name,
            'team_budget' => $tournament->team_budget,
            'max_players_per_team' => $tournament->max_players_per_team,
            'player_base_price' => $tournament->player_base_price,
            'registration_closing_date' => $tournament->registration_closing_date ? $tournament->registration_closing_date->format('Y-m-d H:i:s') : null,
            'is_registration_closed' => $tournament->registration_closing_date ? now()->greaterThan($tournament->registration_closing_date) : false,
            'logo_url' => ImageService::mediaUrl($tournament->logo),
            'created_at' => $tournament->created_at->toISOString(),
        ];
    }

    /**
     * Get the current public auction state (live player and history).
     */
    public function publicAuctionState(Tournament $tournament)
    {
        $history = \App\Models\AuctionHistory::with('player', 'team')
            ->where('tournament_id', $tournament->id)
            ->orderBy('created_at', 'asc')
            ->get();

        $livePlayerHistory = $history->where('action', 'Pending')->last();
        $livePlayer = $livePlayerHistory ? $livePlayerHistory->player : null;

        $playerController = new \App\Http\Controllers\Api\PlayerController();

        // Format history records with proper photo_url
        $formattedHistory = $history->whereIn('action', ['Sold', 'Unsold', 'Rejected'])->values()->map(function ($record) use ($playerController) {
            $data = $record->toArray();
            if ($record->player) {
                $data['player'] = $playerController->formatPlayer($record->player);
            }
            return $data;
        });

        return response()->json([
            'tournament' => $this->formatTournament($tournament),
            'live_player' => $livePlayer ? $playerController->formatPlayer($livePlayer) : null,
            'history' => $formattedHistory,
        ]);
    }

    /**
     * Get auction history for a tournament.
     */
    public function auctionHistory(Request $request, Tournament $tournament)
    {
        if ($tournament->organizer_id !== $request->user()->id) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        $history = \App\Models\AuctionHistory::with('player', 'team')
            ->where('tournament_id', $tournament->id)
            ->orderBy('id', 'asc')
            ->get()
            ->map(function ($item) {
                // Return in the format expected by frontend
                return [
                    'id' => $item->id,
                    'action' => $item->action,
                    'sold_price' => $item->sold_price,
                    'player' => app(PlayerController::class)->formatPlayer($item->player),
                ];
            });

        return response()->json(['history' => $history]);
    }
}
