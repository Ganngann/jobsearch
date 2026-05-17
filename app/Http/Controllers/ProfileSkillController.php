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
        
        // On récupère uniquement les soft skills déjà classées
        $skills = $user->skills()
            ->where('type', 'soft')
            ->withPivot('status')
            ->get()
            ->groupBy('pivot.status');

        return view('profile.skills', [
            'activeSkills' => $skills->get('active', collect()),
            'neutralSkills' => $skills->get('neutral', collect()),
            'refusedSkills' => $skills->get('refused', collect()),
        ]);
    }

    public function softSkills()
    {
        $user = Auth::user();

        $associatedSkillIds = $user->skills()->pluck('skills.id')->toArray();

        $suggestions = Skill::where('type', 'soft')
            ->whereNotIn('id', $associatedSkillIds)
            ->inRandomOrder()
            ->limit(10)
            ->get(['id', 'label']);

        return response()->json([
            'suggestions' => $suggestions
        ]);
    }

    public function suggest()
    {
        // Currently bypassing AI suggestion generation for this view
        return response()->json([
            'suggestions' => []
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

}
