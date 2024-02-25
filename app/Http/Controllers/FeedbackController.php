<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    public function newFeedback(Request $request)
    {
        $validatedData = $request->validate([
            'user_id' => 'required|string',
            'order_id' => 'required|string',
            'feedback' => 'required|string',
        ]);

        $feedback = Feedback::create([
            'user_id' => $validatedData['user_id'],
            'order_id' => $validatedData['order_id'],
            'feedback' => $validatedData['feedback'],
        ]);

        return response()->json(['message' => 'Feedback created successfully', 'feedback' => $feedback]);
    }
}
