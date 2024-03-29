<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Order;
use App\Models\Feedback;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FeedbackController extends Controller
{
    public function newVersion(Request $request, $orderId)
    {
        $order = Order::where('order_id', $orderId)->firstOrFail();
        $user = User::where('user_id', $order->user_id)->firstOrFail();

        $userEmail = $user->email;
        $projectName = strtolower(preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $order->name)));

        $feedbacksCount = $this->countFeedbacks($orderId);

        $feedbacks = new Feedback;
        $feedbacks->date = now();
        $feedbacks->seller_message = $request->input('seller_message');
        $feedbacks->folder_path = $userEmail . '/' . $projectName;
        $feedbacks->status = 2;
        $feedbacks->order_id = $order->order_id;
        $feedbacks->feedback_id = Str::uuid();
        
        $feedbacks->save();

        $master = $request->file('master');
       
        $masterPath = $master->storeAs($feedbacks->folder_path, '/feedback-' . $feedbacksCount . '.' . $master->getClientOriginalExtension());

        return response()->json(['message' => 'Feedback created and uploaded successfully', 'feedback' => $feedbacks, 'master' => $masterPath]);
    }

    public function newFeedback(Request $request, $orderId)
    {
        $feedback = Feedback::where('order_id', $orderId)->firstOrFail();
        
        $validatedData = $request->validate([
            'client_message' => 'required|string|max:255',
        ]);

        $feedback->client_message = $validatedData['client_message'];

        $feedback->save();

        return response()->json(['message' => 'Feedback updated successfully', 'feedback' => $feedback]);
    }

    private function countFeedbacks($orderId)
    {
        $feedbacks = Feedback::where('order_id', $orderId)->get();
        return count($feedbacks) + 1;
    }
}
