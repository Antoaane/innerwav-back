<?php

namespace App\Http\Controllers;

use App\Models\Track;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Order;
use App\Models\Feedback;
use App\Models\User;

class AdminController extends Controller
{
    public function allOrders(): JsonResponse
    {
        $request = request();

        if (!$request->user()->hasRole('super admin')) {
            return response()->json(['message' => 'You are not authorized to access this resource'], 403);
        } else {
            $orders = Order::all();
        }

        foreach ($orders as $order) {
            $tracks = Track::where('order_id', $order->order_id)->get();

            $order->tracks = $tracks;

            $feedbacks = Feedback::where('order_id', $order->order_id)->get();

            $order->feedbacks = $feedbacks;
        }

        return response()->json($orders);
    }

    public function allUsers(): JsonResponse
    {
        $request = request();

        if (!$request->user()->hasRole('super admin')) {
            return response()->json(['message' => 'You are not authorized to access this resource'], 403);
        } else {
            $users = User::all();
        }

        return response()->json($users);
    }

    public function allData(): JsonResponse
    {
        $request = request();

        if (!$request->user()->hasRole('super admin')) {
            return response()->json(['message' => 'You are not authorized to access this resource'], 403);
        } else {
            $data = User::all();
        }

        foreach ($data as $user) {
            $orders = Order::where('user_id', $user->user_id)->get();

            foreach ($orders as $order) {
                $tracks = Track::where('order_id', $order->order_id)->get();

                $order->tracks = $tracks;

                $feedbacks = Feedback::where('order_id', $order->order_id)->get();

                $order->feedbacks = $feedbacks;
            }

            $user->orders = $orders;
        }

        return response()->json($data);
    }
}
