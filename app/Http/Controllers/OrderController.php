<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Track;
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

    public function update(Request $request, $orderId)
    {
        $fieldsToUpdate = $request->input('fieldsToUpdate', []);

        $validationRules = [
            'name' => 'required|string|max:255',
            'global_ref' => 'required|string',
            'project_type' => 'required|in:single,ep,album',
            'file_type' => 'required|in:stereo,stems,multi',
            'support' => 'required|in:str,strcd',
        ];

        $fieldsToValidate = array_intersect_key($validationRules, array_flip($fieldsToUpdate));
        $validatedData = $request->validate($fieldsToValidate);

        $order = Order::where('order_id', $orderId)->firstOrFail();

        if ($order->support == 'strcd' && $request->hasFile('project_cover')) {
            $request->input('project_cover')->storeAs($request->user()->email . '/' . $request->input('project_name'), 'cover.jpg');
        }

        $order->update($validatedData);
        return response()->json(['message' => 'Order updated successfully', 'order' => $order]);
    }

    public function upload(Request $request, $orderId)
    {
        $order = Order::where('order_id', $orderId)->firstOrFail();

        $track = new Track;
        $track->user_name = $request->user()->name;
        $track->artists = $request->artists??"";
        $track->name = $request->name??"";
        $track->spec_ref = $request->spec_ref??"";
        $track->order_id = $orderId;
        $track->user_id = $request->user()->user_id;
        $track->track_id = Str::uuid();
        $track->save();

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
        $projectName = strtolower(preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $order->name)));

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

            $this->calculBasePrice($order, $order->file_type);
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

            $this->calculBasePrice($order, $order->file_type);
        } else {
            $multi = $request->file('multi');
            $paths = [];

            if (count($multi) == 1) {
                $audio = $multi[0];

                $audioFileName = 'audio.' . $audio->getClientOriginalExtension();
                $audioPath = $audio->storeAs($userEmail . '/' . $projectName . '/track-' . $filesCount, $audioFileName, 'public');
                $paths['audio'] = $audioPath;

                $this->calculBasePrice($order, 'stereo');

            } elseif (count($multi) == 2) {
                $voice = $multi[0];
                $prod = $multi[1];

                $voiceFileName = 'voice.' . $voice->getClientOriginalExtension();
                $voicePath = $voice->storeAs($userEmail . '/' . $projectName . '/track-' . $filesCount, $voiceFileName, 'public');
                $paths['voice'] = $voicePath;

                $prodFileName = 'prod.' . $prod->getClientOriginalExtension();
                $prodPath = $prod->storeAs($userEmail . '/' . $projectName . '/track-' . $filesCount, $prodFileName, 'public');
                $paths['prod'] = $prodPath;

                $this->calculBasePrice($order, 'stems');

            } else {
                // Plus de deux fichiers, on renvoie une erreur
                return response()->json(['message' => 'You can only upload one or two files'], 400);

            }

            if ($order->support == 'strcd') {
                $metadataPath = $metadata->storeAs($userEmail . '/' . $projectName . '/track-' . $filesCount, $metadataFileName . '.' . $metadata->getClientOriginalExtension());
                $path['metadata'] = $metadataPath;
            }
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
        } elseif ($order->file_type == 'stereo' || $fileType == 'stereo') {
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
