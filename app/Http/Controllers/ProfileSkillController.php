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
        $status = $request->input('status'); // active, neutral, refused

        if (!in_array($status, ['active', 'neutral', 'refused'])) {
            return response()->json(['status' => 'error', 'message' => 'Statut invalide.'], 400);
        }

        // Si status neutral, on peut aussi l'enlever du profil si on veut être strict, 
        // mais ici "neutral" veut dire "Je peux m'adapter", donc on le garde avec ce tag.
        
        $user->skills()->syncWithoutDetaching([
            $skill->id => [
                'status' => $status,
                'level' => 'intermediate' // Défaut
            ]
        ]);

        return response()->json([
            'status' => 'success',
            'skill_id' => $skill->id,
            'new_status' => $status
        ]);
    }
}
