<?php

namespace App\Http\Controllers;

use App\Models\Plan;

class PublicPlanController extends Controller
{
    public function show(string $token)
    {
        $plan = Plan::where('public_token', $token)->firstOrFail();
        
        return view('plans.public', [
            'plan' => $plan,
        ]);
    }
}
