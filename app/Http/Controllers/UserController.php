<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    public function listOrders(Request $request): JsonResponse
    {
        $user = $request->user();
        $orders = $user->orders;



        return response()->json(['orders' => $orders]);
    }
}
