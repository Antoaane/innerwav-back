<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Track;
use App\Models\Feedback;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class OrderController extends Controller
{
    /**
     * Initialize a new order when the QCM starts.
     */

    public function start(Request $request)
    {
        $order = new Order;
        $order->name = 'Uncompleted order';
        $order->global_ref = 'No global references';
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

    // public function update(Request $request, $orderId)
    // {
    //     $validationRules = $request->validate([
    //         'name' => 'required|string|max:255',
    //         'global_ref' => 'string',
    //         'project_type' => 'required|in:single,ep,album',
    //         'file_type' => 'required|in:lr,stems',
    //         'support' => 'required|in:str,strcd'
    //     ]);

    //     $order = Order::where('order_id', $orderId)->firstOrFail();

    //     if ($order->support == 'strcd') {
    //         $request->input('project_cover')->storeAs($request->user()->email . '/' . $request->input('project_name'), 'cover.jpg');
    //     }

    //     $order->update($validationRules);

    //     return response()->json(['message' => 'Order updated successfully', 'order' => $order]);
    // }

    public function uploadTrack(Request $request, $orderId)
    {
        $order = Order::where('order_id', $orderId)->firstOrFail();

        $track = new Track;
        $track->user_name = $request->user()->name;
        $track->spec_ref = $request->spec_ref??"";
        $track->file_type = $request->file_type;
        $track->artists = $request->artists??"";
        $track->name = $request->name??"";
        $track->order_id = $orderId;
        $track->user_id = $request->user()->user_id;
        $track->track_id = Str::uuid();
        $track->save();

        if ($track->file_type == 'lr') {
            $lr = $request->file('lr');
        } elseif ($track->file_type == 'stems') {
            $main = $request->file('main');
            $prod = $request->file('prod');
        }

        if ($order->support == 'strcd') {
            $metadata = $request->file('metadata');
        }

        $lrFileName = 'lr';
        $mainFileName = 'main';
        $prodFileName = 'prod';
        $metadataFileName = 'metadata';

        $userEmail = $request->user()->email;
        $projectName = strtolower(preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $order->name)));

        $filesCount = $this->countFolders($userEmail . '/' . $projectName) + 1;

        if ($request->file_type == 'lr') {

            if ($order->support == 'strcd') {
                $lrPath = $lr->storeAs($userEmail . '/' . $projectName . '/track-' . $filesCount, $lrFileName . '.' . $lr->getClientOriginalExtension());
                $metadataPath = $metadata->storeAs($userEmail . '/' . $projectName . '/track-' . $filesCount, $metadataFileName . '.' . $metadata->getClientOriginalExtension());
                $path = [
                    'lr' => $lrPath,
                    'metadata' => $metadataPath
                ];
            } else {
                $lrPath = $lr->storeAs($userEmail . '/' . $projectName, 'track-' . $lrFileName . '-' . $filesCount . '.' . $lr->getClientOriginalExtension());
                $path = [
                    'lr' => $lrPath
                ];
            }

            // $this->calculBasePrice($order, $track->file_type);

        } elseif ($request->file_type == 'stems') {

            $mainPath = $main->storeAs($userEmail . '/' . $projectName . '/track-' . $filesCount, $mainFileName . '.' . $main->getClientOriginalExtension());
            $prodPath = $prod->storeAs($userEmail . '/' . $projectName . '/track-' . $filesCount, $prodFileName . '.' . $prod->getClientOriginalExtension());
            if ($order->support == 'strcd') {
                $metadataPath = $metadata->storeAs($userEmail . '/' . $projectName . '/track-' . $filesCount, $metadataFileName . '.' . $metadata->getClientOriginalExtension());
                $path = [
                    'main' => $mainPath,
                    'prod' => $prodPath,
                    'metadata' => $metadataPath
                ];
            } else {
                $path = [
                    'main' => $mainPath,
                    'prod' => $prodPath
                ];
            }

            // $this->calculBasePrice($order, $track->file_type);

        }

        if ($request->input('isLast')) {
            $formatProjectName = strtolower(preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $order->name)));

            $zipFilePath = $this->zipDirectory($userEmail . '/' . $formatProjectName, $userEmail . '/' . $formatProjectName . '/ressources.zip');

            $dirs = Storage::allDirectories($userEmail . '/' . $formatProjectName);
            foreach ($dirs as $dir) {
                Storage::deleteDirectory($dir);
            }
        }

        return response()->json(['message' => 'File uploaded successfully', 'path' => $path, 'folders' => $filesCount, 'zip' => $zipFilePath??"no zip yet"], 201);
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


    public function orderInfos($orderId)
    {
        $order = Order::where('order_id', $orderId)->firstOrFail();

        $tracks = Track::where('order_id', $orderId)->get();

        $order->tracks = $tracks;

        $feedBacks = Feedback::where('order_id', $orderId)->get();

        $order->feedbacks = $feedBacks;

        return response()->json(['order' => $order]);
    }












    public function newOrder(Request $request) 
    {
        $order = new Order;

        $validationRules = $request->validate([
            'name' => 'required|string|max:255',
            'global_ref' => 'string',
            'project_type' => 'required|in:single,ep,album',
            'support' => 'required|in:str,strcd'
        ]);

        $order->name = $validationRules['name'];
        $order->global_ref = $validationRules['global_ref']??'No global references';
        $order->date = now();
        $order->project_type = $validationRules['project_type'];
        $order->support = $validationRules['support'];
        $order->order_id = Str::uuid();
        $order->user_id = $request->user()->user_id;
        $order->status = 0;
        $order->deadline = now();
        $order->save();

        return response()->json(['order' => $order, 'user' => $order->user_id], 201);
    }














    private function countFolders(string $directory): int
    {
        $directories = Storage::directories($directory);
        return count($directories);
    }

    private function zipDirectory(string $directory, string $zipFileName): string
    {
        $zip = new ZipArchive();
        $zipFileName = storage_path('app/' . $zipFileName);

        $zip->open($zipFileName, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $files = Storage::allFiles($directory);

        foreach ($files as $file) {
            $relativePathInZipFile = substr($file, strlen($directory) + 1);
            $fileContent = Storage::get($file);
            $zip->addFromString($relativePathInZipFile, $fileContent);
        }

        $zip->close();

        return $zipFileName;
    }

    private function calculBasePrice($order, string $fileType)
    {
        if ($order->project_type == 'single') {
            $amount = 40;
        } elseif ($order->project_type == 'ep') {
            $amount = 36;
        } elseif ($order->project_type == 'album') {
            $amount = 34;
        }

        if ($order->file_type == 'stems' || $fileType == 'stems') {
            if ($order->support == 'strcd') {
                $amount = $amount * 4;
            } else {
                $amount = $amount * 1.5;
            }
        } elseif ($order->file_type == 'lr' || $fileType == 'lr') {
            if ($order->support == 'strcd') {
                $amount = $amount * 3;
            } else {
                $amount = $amount;
            }
        }

        $order = Order::where('order_id', $order->order_id)->firstOrFail();
        $order->update(['price' => $order->price + $amount]);
    }
}
