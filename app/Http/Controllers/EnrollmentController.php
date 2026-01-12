<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class EnrollmentController extends Controller
{
    public function index(): View
    {
        $enrollments = Enrollment::with(['curriculum.requiredChapters', 'plan'])
            ->where('user_id', Auth::id())
            ->orderBy('enrolled_at', 'desc')
            ->get();

        return view('enrollments.index', compact('enrollments'));
    }

    public function create(): View
    {
        return view('enrollments.create');
    }

    public function createPlan(Enrollment $enrollment): View
    {
        if ($enrollment->user_id !== Auth::id()) {
            abort(403);
        }

        if ($enrollment->plan) {
            return redirect()->route('plans.show', $enrollment->plan);
        }

        $enrollment->load('curriculum.requiredChapters');

        return view('enrollments.create-plan', compact('enrollment'));
    }

    public function destroy(Enrollment $enrollment): RedirectResponse
    {
        if ($enrollment->user_id !== Auth::id()) {
            abort(403);
        }

        if ($enrollment->plan) {
            $enrollment->plan->progress()->delete();
            $enrollment->plan->delete();
        }

        $enrollment->delete();

        return redirect()->route('enrollments.index')
            ->with('success', 'Successfully unenrolled from curriculum.');
    }
}
