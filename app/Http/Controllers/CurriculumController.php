<?php

namespace App\Http\Controllers;

use App\Models\Curriculum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CurriculumController extends Controller
{
    public function index(): View
    {
        $curricula = Curriculum::with('requiredChapters')
            ->where('teacher_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();

        return view('curricula.index', compact('curricula'));
    }

    public function create(): View
    {
        return view('curricula.create');
    }

    public function edit(Curriculum $curriculum): View
    {
        $this->authorize('update', $curriculum);
        
        return view('curricula.edit', compact('curriculum'));
    }

    public function destroy(Curriculum $curriculum)
    {
        $this->authorize('delete', $curriculum);
        
        $curriculum->delete();

        return redirect()->route('curricula.index')
            ->with('success', 'Curriculum deleted successfully.');
    }
}
