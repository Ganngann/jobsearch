<?php

namespace App\Http\Controllers;

use App\Models\Skill;
use App\Services\ProfileMappingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProfileSkillController extends Controller
{
    protected $mappingService;

    public function __construct(ProfileMappingService $mappingService)
    {
        $this->mappingService = $mappingService;
    }

    public function index()
    {
        $user = Auth::user();
        
        // On récupère les compétences déjà classées
        $skills = $user->skills()
            ->withPivot('status')
            ->get()
            ->groupBy('pivot.status');

        return view('profile.skills', [
            'activeSkills' => $skills->get('active', collect()),
            'neutralSkills' => $skills->get('neutral', collect()),
            'refusedSkills' => $skills->get('refused', collect()),
        ]);
    }

    public function suggest()
    {
        $user = Auth::user();
        $suggestions = $this->mappingService->suggestSkills($user, 20);

        return response()->json([
            'suggestions' => $suggestions
        ]);
    }

    public function updateStatus(Request $request, Skill $skill)
    {
        $user = Auth::user();
        $status = $request->input('status'); // active, neutral, refused, none

        if (!in_array($status, ['active', 'neutral', 'refused', 'none'])) {
            return response()->json(['status' => 'error', 'message' => 'Statut invalide.'], 400);
        }

        if ($status === 'none') {
            $user->skills()->detach($skill->id);
        } else {
            $user->skills()->syncWithoutDetaching([
                $skill->id => [
                    'status' => $status,
                    'level' => 'intermediate' // Défaut
                ]
            ]);
        }

        return response()->json([
            'status' => 'success',
            'skill_id' => $skill->id,
            'new_status' => $status
        ]);
    }

    public function softSkills()
    {
        $user = Auth::user();
        $currentSkillIds = $user->skills()->pluck('skills.id');

        $softSkills = Skill::where('type', 'soft')
            ->whereNotIn('id', $currentSkillIds)
            ->withCount(['jobOffers as popularity' => function($query) {
                $query->where('status', 'active');
            }])
            ->orderBy('popularity', 'desc')
            ->get()
            ->map(function($skill) {
                return [
                    'id' => $skill->id,
                    'label' => $skill->label,
                    'type' => 'soft',
                    'popularity' => $skill->popularity,
                    'reason' => 'Compétence comportementale couramment demandée.'
                ];
            });

        return response()->json([
            'suggestions' => $softSkills
        ]);
    }
}
