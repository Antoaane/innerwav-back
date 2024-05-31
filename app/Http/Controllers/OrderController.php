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
     * Create a new order.
     */
    public function newOrder(Request $request) 
    {
        $order = new Order;

        $validationRules = $request->validate([
            'project_name' => 'required|max:255',
            'global_ref' => 'string',
            'project_type' => 'required|in:single,ep,album',
            'support' => 'required|in:str,strcd'
        ]);

        $order->project_name = $validationRules['project_name'];
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

    /**
     * Upload a track for an order.
     */
    public function uploadTrack(Request $request, $orderId)
    {
        $order = Order::where('order_id', $orderId)->firstOrFail();

        $validationRules = $request->validate([
            'file_type' => 'required|in:lr,stems',
            'artists' => 'string|max:255',
            'track_name' => 'string|max:255',
            'spec_ref' => 'string'
        ]);

        $track = new Track;
        $track->user_name = $request->user()->name;

        $track->track_name = $validationRules['track_name']??"";
        $track->artists = $validationRules['artists']??"";
        $track->spec_ref = $validationRules['spec_ref']??'No specific references';
        $track->file_type = $validationRules['file_type'];
        $track->order_id = $orderId;
        $track->user_id = $request->user()->user_id;
        $track->track_id = Str::uuid();
        $track->save();

        $lrFileName = 'lr';
        $mainFileName = 'main';
        $prodFileName = 'prod';
        $metadataFileName = 'metadata';

        $userEmail = $request->user()->email;
        $projectName = strtolower(preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $order->project_name)));

        $filesCount = $this->countElementsInFolder($userEmail . '/' . $projectName) + 1;

        if ($request->file_type == 'lr') {

            if ($order->support == 'strcd') {

                $validationRules = $request->validate([
                    'lr' => 'required|file|mimes:mp3,wav|max:204800',
                    'metadata' => 'required|file|mimes:pdf,doc,docx,txt,rtf,odt,ods,xls,xlsx|max:5120'
                ]);

                $lrPath = $validationRules['lr']->storeAs($userEmail . '/' . $projectName . '/track-' . $filesCount, $lrFileName . '.' . $validationRules['lr']->getClientOriginalExtension());
                $metadataPath = $validationRules['metadata']->storeAs($userEmail . '/' . $projectName . '/track-' . $filesCount, $metadataFileName . '.' . $validationRules['metadata']->getClientOriginalExtension());
                
                $path = [
                    'lr' => $lrPath,
                    'metadata' => $metadataPath
                ];
            } else {

                $validationRules = $request->validate([
                    'lr' => 'required|file|mimes:mp3,wav|max:204800',
                    'artists' => 'required|string|max:255',
                    'track_name' => 'required|string|max:255'
                ]);

                $lrPath = $validationRules['lr']->storeAs($userEmail . '/' . $projectName, 'track-' . $lrFileName . '-' . $filesCount . '.' . $validationRules['lr']->getClientOriginalExtension());
                
                $path = [
                    'lr' => $lrPath
                ];
            }

            // $this->calculBasePrice($order, $track->file_type);

        } elseif ($request->file_type == 'stems') {

            if ($order->support == 'strcd') {

                $validationRules = $request->validate([
                    'main' => 'required|file|mimes:mp3,wav|max:204800',
                    'prod' => 'required|file|mimes:mp3,wav|max:204800',
                    'metadata' => 'required|file|mimes:pdf,doc,docx,txt,rtf,odt,ods,xls,xlsx|max:5120'
                ]);

                $mainPath = $validationRules['main']->storeAs($userEmail . '/' . $projectName . '/track-' . $filesCount, $mainFileName . '.' . $validationRules['main']->getClientOriginalExtension());
                $prodPath = $validationRules['prod']->storeAs($userEmail . '/' . $projectName . '/track-' . $filesCount, $prodFileName . '.' . $validationRules['prod']->getClientOriginalExtension());
                $metadataPath = $validationRules['metadata']->storeAs($userEmail . '/' . $projectName . '/track-' . $filesCount, $metadataFileName . '.' . $validationRules['metadata']->getClientOriginalExtension());
                
                $path = [
                    'main' => $mainPath,
                    'prod' => $prodPath,
                    'metadata' => $metadataPath
                ];
            } else {

                $validationRules = $request->validate([
                    'main' => 'required|file|mimes:mp3,wav|max:204800',
                    'prod' => 'required|file|mimes:mp3,wav|max:204800',
                    'artists' => 'required|string|max:255',
                    'track_name' => 'required|string|max:255'
                ]);

                $mainPath = $validationRules['main']->storeAs($userEmail . '/' . $projectName . '/track-' . $filesCount, $mainFileName . '.' . $validationRules['main']->getClientOriginalExtension());
                $prodPath = $validationRules['prod']->storeAs($userEmail . '/' . $projectName . '/track-' . $filesCount, $prodFileName . '.' . $validationRules['prod']->getClientOriginalExtension());
                
                $path = [
                    'main' => $mainPath,
                    'prod' => $prodPath
                ];
            }

            // $this->calculBasePrice($order, $track->file_type);

        }

        if ($request->input('is_last')) {
            $formatProjectName = strtolower(preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $order->project_name)));

            $zipFilePath = $this->zipDirectory($userEmail . '/' . $formatProjectName, $userEmail . '/' . $formatProjectName . '/ressources.zip');

            $dirs = Storage::allDirectories($userEmail . '/' . $formatProjectName);
            foreach ($dirs as $dir) {
                Storage::deleteDirectory($dir);
            }
        }

        return response()->json(['message' => 'File uploaded successfully', 'path' => $path, 'folders' => $filesCount, 'zip' => $zipFilePath??"no zip yet"], 201);
    }

    /**
     * Complete the order and define 'status' and 'deadline'.
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

    /**
     * Get the order informations.
     */
    public function orderInfos($orderId)
    {
        $order = Order::where('order_id', $orderId)->firstOrFail();

        $tracks = Track::where('order_id', $orderId)->get();

        $order->tracks = $tracks;

        $feedBacks = Feedback::where('order_id', $orderId)->get();

        $order->feedbacks = $feedBacks;

        return response()->json(['order' => $order]);
    }



    private function countElementsInFolder(string $directory): int
    {
        $directories = count(Storage::directories($directory));
        $files = count(Storage::files($directory));
        return $directories + $files;
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


    // NEED TO BE REWORKED
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
