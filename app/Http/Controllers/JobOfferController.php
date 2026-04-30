<?php

namespace App\Http\Controllers;

use App\Models\JobOffer;
use App\Models\UserMatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class JobOfferController extends Controller
{
    /**
     * Affiche le tableau de bord avec les meilleures correspondances.
     */
    public function dashboard()
    {
        $user = Auth::user();

        // Récupérer les matches de l'utilisateur, triés par score final
        $matches = UserMatch::where('user_id', $user->id)
            ->with('jobOffer.employer', 'jobOffer.metier')
            ->orderByDesc('final_score')
            ->orderByDesc('pre_score')
            ->paginate(10);

        return view('dashboard', compact('matches'));
    }

    /**
     * Affiche le détail d'une offre et son analyse de matching.
     */
    public function show(JobOffer $jobOffer)
    {
        $user = Auth::user();

        $match = UserMatch::where('user_id', $user->id)
            ->where('job_offer_id', $jobOffer->id)
            ->first();

        return view('job-offers.show', compact('jobOffer', 'match'));
    }
}
