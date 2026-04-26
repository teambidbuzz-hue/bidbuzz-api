<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\ImageService;

class TeamController extends Controller
{
    /**
     * Ensure the tournament belongs to the authenticated organizer.
     */
    private function authorizedTournament(Request $request, Tournament $tournament): bool
    {
        return $tournament->organizer_id === $request->user()->id;
    }

    /**
     * List all teams for a tournament.
     */
    public function index(Request $request, Tournament $tournament): JsonResponse
    {
        if (!$this->authorizedTournament($request, $tournament)) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        $teams = $tournament->teams()
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn ($team) => $this->formatTeam($team));

        return response()->json(['teams' => $teams]);
    }

    /**
     * Create a new team.
     */
    public function store(Request $request, Tournament $tournament): JsonResponse
    {
        if (!$this->authorizedTournament($request, $tournament)) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:10240',
        ]);

        $logoPath = null;
        if ($request->hasFile('logo')) {
            $logoPath = ImageService::processAndStore($request->file('logo'), "tournaments/{$tournament->storage_key}/teams");
        }

        $team = $tournament->teams()->create([
            'name' => $validated['name'],
            'logo' => $logoPath,
        ]);

        return response()->json([
            'message' => 'Team created successfully.',
            'team' => $this->formatTeam($team),
        ], 201);
    }

    /**
     * Update a team.
     */
    public function update(Request $request, Tournament $tournament, Team $team): JsonResponse
    {
        if (!$this->authorizedTournament($request, $tournament)) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        if ($team->tournament_id !== $tournament->id) {
            return response()->json(['message' => 'Team not found in this tournament.'], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:10240',
        ]);

        if ($request->hasFile('logo')) {
            if ($team->logo) {
                ImageService::deleteMedia($team->logo);
            }
            $validated['logo'] = ImageService::processAndStore($request->file('logo'), "tournaments/{$tournament->storage_key}/teams");
        }

        $team->update($validated);

        return response()->json([
            'message' => 'Team updated successfully.',
            'team' => $this->formatTeam($team->fresh()),
        ]);
    }

    /**
     * Delete a team.
     */
    public function destroy(Request $request, Tournament $tournament, Team $team): JsonResponse
    {
        if (!$this->authorizedTournament($request, $tournament)) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        if ($team->tournament_id !== $tournament->id) {
            return response()->json(['message' => 'Team not found in this tournament.'], 404);
        }

        if ($team->logo) {
            ImageService::deleteMedia($team->logo);
        }

        $team->players()->update([
            'status' => 'Pending',
            'team_id' => null,
            'sold_price' => null,
        ]);

        $team->delete();

        return response()->json(['message' => 'Team deleted successfully.']);
    }

    /**
     * Format team for API response.
     */
    private function formatTeam(Team $team): array
    {
        return [
            'id' => $team->id,
            'name' => $team->name,
            'logo_url' => ImageService::mediaUrl($team->logo),
            'created_at' => $team->created_at->toISOString(),
        ];
    }
}
