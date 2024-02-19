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

    /**
     * Update the order at each QCM step.
     */

    public function update(Request $request, $orderId)
    {
        $fieldsToUpdate = $request->input('fieldsToUpdate', []);

        $validationRules = [
            'name' => 'required|string|max:255',
            'project_type' => 'required|in:single,ep,album',
            'file_type' => 'required|in:stereo,stems,mixed',
            'support' => 'required|in:str,strcd',
        ];

        $fieldsToValidate = array_intersect_key($validationRules, array_flip($fieldsToUpdate));

        $validatedData = $request->validate($fieldsToValidate);

        $order = Order::where('order_id', $orderId)->firstOrFail();

        $order->update($validatedData);

        return response()->json(['message' => 'Order updated successfully', 'order' => $order]);
    }

    public function upload(Request $request, $orderId)
    {
        $order = Order::where('order_id', $orderId)->firstOrFail();

        $client = $request->user()->email;
        $project = $order->project_name;

        // return response()->json(['message' => $request->file('lefichier')]);

        $file = $request->file('lefichier');

        $fileName = 'musique-1';

        // return response()->json(['message' => $fileName]);
        $path = $file->storeAs('public/uploads', $fileName . '.' . $file->getClientOriginalExtension());

        // $order->file = $fileName;
        // $order->save();

        return response()->json(['message' => 'File uploaded successfully', 'path' => $path]);
    }

    /**
     * Complete the QCM and define 'status' and 'deadline'.
     */
    public function complete(Request $request, $orderId)
    {
        $order = Order::where('order_id', $orderId)->firstOrFail();

        $order->status = 2;
        $order->deadline = $request->deadline;
        $order->date = now(); // Set the 'date' when QCM is completed

        $order->save();

        return response()->json(['message' => 'Order completed successfully', 'order' => $order]);
    }
}
