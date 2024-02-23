<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Display a listing of the orders for the authenticated user.
     *
     * @return \Illuminate\Http\Response
    */

    public function userInfos(Request $request)
    {
        $user = $request->user();

        return response()->json($user);
    }

    public function ordersInfos(Request $request)
    {
        $user = $request->user();
        
        $orders = $user->orders;

        return response()->json($orders);
    }
}
