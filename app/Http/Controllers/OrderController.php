<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use Illuminate\Support\Facades\Storage;
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

        if ($order->file_type == 'stereo') {
            $audio = $request->file('audio');
        } elseif ($order->file_type == 'stems') {
            $voice = $request->file('voice');
            $prod = $request->file('prod');
        } else {
            $multi = $request->file('multi');
        }

        if ($order->support == 'strcd') {
            $metadata = $request->file('metadata');
        }

        $audioFileName = 'track';
        $voiceFileName = 'voice';
        $prodFileName = 'prod';
        $metadataFileName = 'metadata';

        $userEmail = $request->user()->email;
        $projectName = $order->name;

        // count the number of direct child of the project user's directory
//        $filesCount = count(Storage::folders($userEmail . '/' . $projectName)) + 1;
        $filesCount = $this->countFolders($userEmail . '/' . $projectName) + 1;

        if ($order->file_type == 'stereo') {
            $audioPath = $audio->storeAs($userEmail . '/' . $projectName . '/track-' . $filesCount, $audioFileName . '.' . $audio->getClientOriginalExtension());
            if ($order->support == 'strcd') {
                $metadataPath = $metadata->storeAs($userEmail . '/' . $projectName . '/track-' . $filesCount, $metadataFileName . '.' . $metadata->getClientOriginalExtension());
                $path = [
                    'audio' => $audioPath,
                    'metadata' => $metadataPath
                ];
            } else {
                $path = [
                    'audio' => $audioPath
                ];
            }
        } elseif ($order->file_type == 'stems') {
            $voicePath = $voice->storeAs($userEmail . '/' . $projectName . '/track-' . $filesCount, $voiceFileName . '.' . $voice->getClientOriginalExtension());
            $prodPath = $prod->storeAs($userEmail . '/' . $projectName . '/track-' . $filesCount, $prodFileName . '.' . $prod->getClientOriginalExtension());
            if ($order->support == 'strcd') {
                $metadataPath = $metadata->storeAs($userEmail . '/' . $projectName . '/track-' . $filesCount, $metadataFileName . '.' . $metadata->getClientOriginalExtension());
                $path = [
                    'voice' => $voicePath,
                    'prod' => $prodPath,
                    'metadata' => $metadataPath
                ];
            } else {
                $path = [
                    'voice' => $voicePath,
                    'prod' => $prodPath
                ];
            }
        } else {
            if ($order->support == 'strcd') {
                $metadataPath = $metadata->storeAs($userEmail . '/' . $projectName . '/track-' . $filesCount, $metadataFileName . '.' . $metadata->getClientOriginalExtension());
                $path = [
                    'multi' => '',
                    'metadata' => $metadataPath
                ];
            } else {
                $path = [
                    'multi' => ''
                ];
            }
        }


         return response()->json(['message' => 'File uploaded successfully', 'path' => $path, 'folders' => $filesCount], 201);
    }

    /**
     * Complete the QCM and define 'status' and 'deadline'.
     */
    public function complete(Request $request, $orderId)
    {
        $order = Order::where('order_id', $orderId)->firstOrFail();

        $order->status = 2;
        $order->deadline = $request->deadline;
        $order->date = now();

        $order->save();

        return response()->json(['message' => 'Order completed successfully', 'order' => $order]);
    }

    private function countFolders(string $directory): int
    {
        $directories = Storage::directories($directory);
        return count($directories);
    }
}
