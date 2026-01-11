<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
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
}
