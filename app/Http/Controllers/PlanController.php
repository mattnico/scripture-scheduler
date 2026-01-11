<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PlanController extends Controller
{
    public function index(): View
    {
        $plans = Plan::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();

        return view('plans.index', compact('plans'));
    }

    public function create(): View
    {
        return view('plans.create');
    }

    public function show(Plan $plan): View
    {
        return view('plans.show', compact('plan'));
    }

    public function destroy(Plan $plan)
    {
        $plan->delete();

        return redirect()->route('plans.index')
            ->with('success', 'Plan deleted successfully.');
    }
}
