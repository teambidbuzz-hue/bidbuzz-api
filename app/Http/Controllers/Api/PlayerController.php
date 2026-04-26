<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Player;
use App\Models\Tournament;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Services\ImageService;

class PlayerController extends Controller
{
    /**
     * Ensure the tournament belongs to the authenticated organizer.
     */
    private function authorizedTournament(Request $request, Tournament $tournament): bool
    {
        return $tournament->organizer_id === $request->user()->id;
    }

    /**
     * List all players for a tournament.
     */
    public function index(Request $request, Tournament $tournament): JsonResponse
    {
        if (!$this->authorizedTournament($request, $tournament)) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        $players = $tournament->players()
            ->orderBy('sort_order', 'asc')
            ->get()
            ->map(fn($player) => $this->formatPlayer($player));

        return response()->json(['players' => $players]);
    }

    /**
     * Add a new player to a tournament.
     */
    public function store(Request $request, Tournament $tournament): JsonResponse
    {
        if (!$this->authorizedTournament($request, $tournament)) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        $validated = $request->validate([
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:10240',
            'full_name' => 'required|string|max:255',
            'age' => 'nullable|integer|min:1|max:100',
            'phone_number' => 'nullable|string|max:20',
            'batting_hand' => ['required', Rule::in(['Right', 'Left'])],
            'player_role' => ['required', Rule::in(['Batsman', 'Bowler', 'Wicketkeeper', 'Batting All-rounder', 'Bowling All-rounder'])],
            'bowling_arm' => ['required', Rule::in(['Right-arm', 'Left-arm', 'N/A'])],
        ]);

        $sortOrder = ($tournament->players()->max('sort_order') ?? 0) + 1;

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = ImageService::processAndStore($request->file('photo'), "tournaments/{$tournament->storage_key}/players");
        }

        $player = $tournament->players()->create([
            'photo' => $photoPath,
            'full_name' => $validated['full_name'],
            'age' => $validated['age'] ?? null,
            'phone_number' => $validated['phone_number'] ?? null,
            'batting_hand' => $validated['batting_hand'],
            'player_role' => $validated['player_role'],
            'bowling_arm' => $validated['bowling_arm'],
            'sort_order' => $sortOrder,
            'status' => 'Pending',
        ]);

        return response()->json([
            'message' => 'Player added successfully.',
            'player' => $this->formatPlayer($player),
        ], 201);
    }

    /**
     * Public endpoint to register a new player to a tournament.
     */
    public function publicRegister(Request $request, Tournament $tournament): JsonResponse
    {
        if ($tournament->registration_closing_date && now()->isAfter($tournament->registration_closing_date)) {
            return response()->json(['message' => 'Registration is closed for this tournament.'], 422);
        }

        $validated = $request->validate([
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:10240',
            'full_name' => 'required|string|max:255',
            'age' => 'nullable|integer|min:1|max:100',
            'phone_number' => 'nullable|string|max:20',
            'batting_hand' => ['required', Rule::in(['Right', 'Left'])],
            'player_role' => ['required', Rule::in(['Batsman', 'Bowler', 'Wicketkeeper', 'Batting All-rounder', 'Bowling All-rounder'])],
            'bowling_arm' => ['required', Rule::in(['Right-arm', 'Left-arm', 'N/A'])],
        ]);

        $sortOrder = ($tournament->players()->max('sort_order') ?? 0) + 1;

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = ImageService::processAndStore($request->file('photo'), "tournaments/{$tournament->storage_key}/players");
        }

        $player = $tournament->players()->create([
            'photo' => $photoPath,
            'full_name' => $validated['full_name'],
            'age' => $validated['age'] ?? null,
            'phone_number' => $validated['phone_number'] ?? null,
            'batting_hand' => $validated['batting_hand'],
            'player_role' => $validated['player_role'],
            'bowling_arm' => $validated['bowling_arm'],
            'sort_order' => $sortOrder,
            'status' => 'Pending',
        ]);

        return response()->json([
            'message' => 'Player registered successfully.',
            'player' => $this->formatPlayer($player),
        ], 201);
    }

    /**
     * Update a player.
     */
    public function update(Request $request, Tournament $tournament, Player $player): JsonResponse
    {
        if (!$this->authorizedTournament($request, $tournament)) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        if ($player->tournament_id !== $tournament->id) {
            return response()->json(['message' => 'Player not found in this tournament.'], 404);
        }

        $validated = $request->validate([
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:10240',
            'full_name' => 'sometimes|required|string|max:255',
            'age' => 'sometimes|nullable|integer|min:1|max:100',
            'phone_number' => 'nullable|string|max:20',
            'batting_hand' => ['sometimes', 'required', Rule::in(['Right', 'Left'])],
            'player_role' => ['sometimes', 'required', Rule::in(['Batsman', 'Bowler', 'Wicketkeeper', 'Batting All-rounder', 'Bowling All-rounder'])],
            'bowling_arm' => ['sometimes', 'required', Rule::in(['Right-arm', 'Left-arm', 'N/A'])],
            'status' => ['sometimes', 'required', Rule::in(['Pending', 'Rejected', 'Sold', 'Unsold'])],
            'label' => 'sometimes|nullable|string|max:255',
        ]);

        if ($request->hasFile('photo')) {
            if ($player->photo) {
                ImageService::deleteMedia($player->photo);
            }
            $validated['photo'] = ImageService::processAndStore($request->file('photo'), "tournaments/{$tournament->storage_key}/players");
        }

        if (isset($validated['status'])) {
            if ($validated['status'] === 'Sold' && $player->status !== 'Sold') {
                $validated['sold_at'] = now();
            } elseif ($validated['status'] !== 'Sold') {
                $validated['sold_at'] = null;
            }
        }

        $player->update($validated);

        return response()->json([
            'message' => 'Player updated successfully.',
            'player' => $this->formatPlayer($player->fresh()),
        ]);
    }

    /**
     * Sell a player to a team.
     */
    public function sell(Request $request, Tournament $tournament, Player $player)
    {
        if (!$this->authorizedTournament($request, $tournament)) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        if ($player->tournament_id !== $tournament->id) {
            return response()->json(['message' => 'Player not found in this tournament.'], 404);
        }

        $validated = $request->validate([
            'team_id' => 'required|uuid|exists:teams,id',
            'sold_price' => 'required|integer|min:0',
        ]);

        $team = $tournament->teams()->findOrFail($validated['team_id']);

        if ($player->status === 'Rejected') {
            return response()->json(['message' => 'Rejected players cannot be sold.'], 422);
        }

        $teamPlayers = $team->players()->where('status', 'Sold')->get();
        $pointsSpent = $teamPlayers->sum('sold_price');
        $remainingPlayers = max(0, $tournament->max_players_per_team - $teamPlayers->count());
        $basePrice = $tournament->player_base_price;
        $teamBudget = $tournament->team_budget;

        if ($remainingPlayers <= 0) {
            return response()->json([
                'message' => 'This team has already reached the maximum number of players.'
            ], 422);
        }

        $maxBid = $remainingPlayers > 0
            ? ($teamBudget - $pointsSpent) - ($basePrice * $remainingPlayers) + $basePrice
            : 0;

        if ($validated['sold_price'] > $maxBid) {
            return response()->json([
                'message' => 'The sold points exceed the maximum possible bid for this team (' . max(0, $maxBid) . ' pts).'
            ], 422);
        }

        $player->update([
            'status' => 'Sold',
            'team_id' => $team->id,
            'sold_price' => $validated['sold_price'],
            'sold_at' => now(),
        ]);

        // Remove any Pending auction history for this player
        \App\Models\AuctionHistory::where('player_id', $player->id)
            ->where('action', 'Pending')
            ->delete();

        \App\Models\AuctionHistory::create([
            'tournament_id' => $tournament->id,
            'player_id' => $player->id,
            'action' => 'Sold',
            'team_id' => $team->id,
            'sold_price' => $validated['sold_price'],
        ]);

        $this->broadcastAuctionEvent($tournament->id, 'player.sold', [
            'player' => $this->formatPlayer($player->fresh()),
            'team_name' => $team->name,
            ...$this->auctionStatusPayload($tournament),
        ]);

        return response()->json([
            'message' => 'Player sold successfully.',
            'player' => $this->formatPlayer($player->fresh()),
        ]);
    }

    /**
     * Revert a sold player to Pending.
     */
    public function revert(Request $request, Tournament $tournament, Player $player)
    {
        if (!$this->authorizedTournament($request, $tournament)) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        if ($player->tournament_id !== $tournament->id) {
            return response()->json(['message' => 'Player not found in this tournament.'], 404);
        }

        $player->update([
            'status' => 'Pending',
            'team_id' => null,
            'sold_price' => null,
            'sold_at' => null,
        ]);

        \App\Models\AuctionHistory::where('player_id', $player->id)->delete();

        return response()->json([
            'message' => 'Player status reverted to pending.',
            'player' => $this->formatPlayer($player->fresh()),
        ]);
    }

    /**
     * Reject a player.
     */
    public function reject(Request $request, Tournament $tournament, Player $player)
    {
        if (!$this->authorizedTournament($request, $tournament)) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        if ($player->tournament_id !== $tournament->id) {
            return response()->json(['message' => 'Player not found in this tournament.'], 404);
        }



        $player->update([
            'status' => 'Rejected',
            'team_id' => null,
            'sold_price' => null,
            'sold_at' => null,
        ]);

        // Remove any Pending auction history and create Rejected history
        \App\Models\AuctionHistory::where('player_id', $player->id)
            ->where('action', 'Pending')
            ->delete();

        \App\Models\AuctionHistory::create([
            'tournament_id' => $tournament->id,
            'player_id' => $player->id,
            'action' => 'Rejected',
        ]);

        $this->broadcastAuctionEvent($tournament->id, 'player.rejected', [
            'player' => $this->formatPlayer($player->fresh()),
            ...$this->auctionStatusPayload($tournament),
        ]);

        return response()->json([
            'message' => 'Player rejected successfully.',
            'player' => $this->formatPlayer($player->fresh()),
        ]);
    }

    /**
     * Mark a player as Unsold (auction flow).
     */
    public function markUnsold(Request $request, Tournament $tournament, Player $player)
    {
        if (!$this->authorizedTournament($request, $tournament)) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        if ($player->tournament_id !== $tournament->id) {
            return response()->json(['message' => 'Player not found in this tournament.'], 404);
        }

        $player->update([
            'status' => 'Unsold',
            'team_id' => null,
            'sold_price' => null,
            'sold_at' => null,
        ]);

        // Remove any Pending auction history for this player
        \App\Models\AuctionHistory::where('player_id', $player->id)
            ->where('action', 'Pending')
            ->delete();

        \App\Models\AuctionHistory::create([
            'tournament_id' => $tournament->id,
            'player_id' => $player->id,
            'action' => 'Unsold',
        ]);

        $this->broadcastAuctionEvent($tournament->id, 'player.unsold', [
            'player' => $this->formatPlayer($player->fresh()),
            ...$this->auctionStatusPayload($tournament),
        ]);

        return response()->json([
            'message' => 'Player marked as unsold.',
            'player' => $this->formatPlayer($player->fresh()),
        ]);
    }

    /**
     * Reset an Unsold player back to Pending (for next auction round).
     */
    public function resetToPending(Request $request, Tournament $tournament, Player $player)
    {
        if (!$this->authorizedTournament($request, $tournament)) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        if ($player->tournament_id !== $tournament->id) {
            return response()->json(['message' => 'Player not found in this tournament.'], 404);
        }

        if ($player->status !== 'Unsold') {
            return response()->json(['message' => 'Only Unsold players can be reset to pending.'], 422);
        }

        $player->update([
            'status' => 'Pending',
            'team_id' => null,
            'sold_price' => null,
            'sold_at' => null,
        ]);

        return response()->json([
            'message' => 'Player reset to pending.',
            'player' => $this->formatPlayer($player->fresh()),
        ]);
    }

    /**
     * Pick a random pending player for auction.
     */
    public function pickRandom(Request $request, Tournament $tournament)
    {
        if (!$this->authorizedTournament($request, $tournament)) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        // Check if there's already a pending auction pick
        $existingPending = \App\Models\AuctionHistory::with('player')
            ->where('tournament_id', $tournament->id)
            ->where('action', 'Pending')
            ->first();

        if ($existingPending) {
            return response()->json([
                'message' => 'There is already a pending player in auction.',
                'history' => [
                    'id' => $existingPending->id,
                    'action' => $existingPending->action,
                    'sold_price' => $existingPending->sold_price,
                    'player' => $this->formatPlayer($existingPending->player),
                ],
            ], 422);
        }

        // Pick a random pending player
        $player = $tournament->players()
            ->where('status', 'Pending')
            ->inRandomOrder()
            ->first();

        if (!$player) {
            // Fallback to Unsold players
            $player = $tournament->players()
                ->where('status', 'Unsold')
                ->inRandomOrder()
                ->first();
        }

        if (!$player) {
            return response()->json(['message' => 'No pending or unsold players available.'], 422);
        }

        $history = \App\Models\AuctionHistory::create([
            'tournament_id' => $tournament->id,
            'player_id' => $player->id,
            'action' => 'Pending',
        ]);

        $history->load('player');

        $this->broadcastAuctionEvent($tournament->id, 'player.picked', [
            'player' => $this->formatPlayer($history->player),
            'is_unsold_round' => $request->boolean('is_unsold_round')
        ]);

        return response()->json([
            'message' => 'Player picked for auction.',
            'history' => [
                'id' => $history->id,
                'action' => $history->action,
                'sold_price' => $history->sold_price,
                'player' => $this->formatPlayer($history->player),
            ],
        ]);
    }

    /**
     * Format player for API response.
     */
    public function formatPlayer(Player $player): array
    {
        return [
            'id' => $player->id,
            'full_name' => $player->full_name,
            'age' => $player->age,
            'phone_number' => $player->phone_number,
            'batting_hand' => $player->batting_hand,
            'player_role' => $player->player_role,
            'bowling_arm' => $player->bowling_arm,
            'status' => $player->status,
            'team_id' => $player->team_id,
            'sold_price' => $player->sold_price,
            'sold_at' => $player->sold_at ? $player->sold_at->toISOString() : null,
            'label' => $player->label,
            'sort_order' => $player->sort_order,
            'photo_url' => ImageService::mediaUrl($player->photo),
            'created_at' => $player->created_at->toISOString(),
        ];
    }

    /**
     * Broadcast auction event via Ably.
     * 
     * This method is intentionally fire-and-forget. Any failure here
     * (missing key, network error, Ably outage) will only affect the
     * live spectator page — it will NEVER block or break auction operations.
     */
    private function broadcastAuctionEvent($tournamentId, $eventName, $data)
    {
        try {
            $ablyKey = config('services.ably.key');
            if (!$ablyKey) {
                \Log::warning('Ably broadcast skipped: ABLY_KEY is not configured.');
                return;
            }
            $ably = new \Ably\AblyRest($ablyKey);
            $channel = $ably->channel('public-auction-' . $tournamentId);
            $channel->publish($eventName, $data);
        } catch (\Throwable $e) {
            // Never let broadcast failures affect the auction flow
            \Log::error('Ably Broadcast Error: ' . $e->getMessage());
        }
    }

    /**
     * Get remaining pending and unsold counts for auction status.
     */
    private function auctionStatusPayload(Tournament $tournament): array
    {
        $pendingCount = Player::where('tournament_id', $tournament->id)
            ->where('status', 'Pending')
            ->count();

        $unsoldCount = Player::where('tournament_id', $tournament->id)
            ->where('status', 'Unsold')
            ->count();

        return [
            'pending_count' => $pendingCount,
            'unsold_count' => $unsoldCount,
        ];
    }
}
