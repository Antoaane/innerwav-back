<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    /**
     * Initialize a new order when the QCM starts.
     */
    public function start(Request $request)
    {
        $order = new Order;
        $order->name = 'Uncompleted order';
        $order->date = now();
        $order->project_type = 'undefined';
        $order->file_type = 'undefined';
        $order->support = 'undefined';
        $order->deadline = now();
        $order->order_id = Str::uuid();
        $order->user_id = $request->user()->user_id;
        $order->status = 0; // Default status when QCM starts
        $order->save();

        return response()->json(['order' => $order->order_id, 'user' => $order->user_id], 201);
    }
}
