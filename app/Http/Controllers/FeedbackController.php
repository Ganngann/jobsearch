<?php

namespace App\Http\Controllers;

use App\Models\UserFeedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FeedbackController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'message' => 'required|string|min:5',
            'type' => 'required|string|in:feedback,bug,idea',
            'page_url' => 'nullable|string|max:2048',
        ]);

        UserFeedback::create([
            'user_id' => Auth::id(),
            'message' => $request->message,
            'page_url' => $request->page_url,
            'type' => $request->type,
        ]);

        return response()->json(['success' => true, 'message' => 'Merci pour ton retour !']);
    }
}
