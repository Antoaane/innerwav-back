<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    /**
     * Display a listing of the orders for the authenticated user.
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function userInfos(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json($user);
    }

    public function ordersInfos(Request $request): JsonResponse
    {
        $user = $request->user();

        $orders = $user->orders;

        $feedbacks = Feedback::where('order_id', $orders->order_id)->get();

        $orders->feedbacks = $feedbacks;

        return response()->json($orders);
    }
}