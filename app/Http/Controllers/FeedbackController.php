<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FeedbackController extends Controller
{
    public function newVersion(Request $request, $orderId)
    {
        $order = Order::where('order_id', $orderId)->firstOrFail();

        $userEmail = $request->user()->email;
        $projectName = strtolower(preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $order->name)));

//        $feedbacksCount = $this->countFeedbacks($orderId);

        $feedbacks = new Feedback;
        $feedbacks->seller_message = $request->input('seller_message');
        $feedbacks->folder_path = $userEmail . '/' . $projectName . '/feedback-';
        $feedbacks->status = 2;
        $feedbacks->order_id = $order->order_id;
        $feedbacks->feedback_id = Str::uuid();
        
        $feedbacks->save();


        return response()->json(['message' => 'Feedback created successfully', 'feedback' => $feedbacks]);
    }

    private function countFeedbacks($orderId)
    {
        $feedbacks = Feedback::where('order_id', $orderId)->get();
//        dd($feedbacks??"");
        return count($feedbacks);
    }
}
