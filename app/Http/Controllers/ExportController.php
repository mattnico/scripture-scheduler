<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class ExportController extends Controller
{
    public function csv(Plan $plan): Response
    {
        $this->authorize($plan);
        
        $csv = $this->generateCsv($plan);
        
        return response($csv, 200)
            ->header('Content-Type', 'text/csv; charset=utf-8')
            ->header('Content-Disposition', 'attachment; filename="reading-plan-' . $plan->id . '.csv"');
    }

    public function table(Plan $plan)
    {
        $this->authorize($plan);
        
        return view('exports.table', [
            'plan' => $plan,
            'schedule' => $plan->schedule ?? [],
        ]);
    }

    public function calendar(Plan $plan)
    {
        $this->authorize($plan);
        
        $schedule = $plan->schedule ?? [];
        $calendarData = $this->organizeByMonth($schedule);
        
        return view('exports.calendar', [
            'plan' => $plan,
            'months' => $calendarData,
        ]);
    }

    public function pdfTable(Plan $plan)
    {
        $this->authorize($plan);
        
        $pdf = Pdf::loadView('exports.pdf-table', [
            'plan' => $plan,
            'schedule' => $plan->schedule ?? [],
        ]);
        
        $pdf->setPaper('letter', 'portrait');
        
        $filename = 'reading-plan-' . $plan->start_date->format('Y-m-d') . '.pdf';
        
        return $pdf->download($filename);
    }

    public function pdfCalendar(Plan $plan)
    {
        $this->authorize($plan);
        
        $schedule = $plan->schedule ?? [];
        $calendarData = $this->organizeByMonth($schedule);
        
        $pdf = Pdf::loadView('exports.pdf-calendar', [
            'plan' => $plan,
            'months' => $calendarData,
        ]);
        
        $pdf->setPaper('letter', 'landscape');
        
        $filename = 'reading-calendar-' . $plan->start_date->format('Y-m-d') . '.pdf';
        
        return $pdf->download($filename);
    }

    protected function authorize(Plan $plan): void
    {
        if ($plan->user_id !== Auth::id()) {
            abort(403);
        }
    }

    protected function generateCsv(Plan $plan): string
    {
        $schedule = $plan->schedule ?? [];
        $isChapterBased = $plan->scheduling_method === 'chapter';
        
        $lines = [];
        
        if ($isChapterBased) {
            $lines[] = 'date,ending_chapter,chapter_count,word_count';
        } else {
            $lines[] = 'date,ending_verse,verse_count,word_count';
        }
        
        foreach ($schedule as $entry) {
            $date = $entry['date'];
            $reading = '"' . str_replace('"', '""', $entry['reading']) . '"';
            $count = $entry['chapter_count'] ?? $entry['verse_count'] ?? 0;
            $wordCount = $entry['word_count'];
            
            $lines[] = "{$date},{$reading},{$count},{$wordCount}";
        }
        
        return implode("\n", $lines);
    }

    protected function organizeByMonth(array $schedule): array
    {
        $months = [];
        
        foreach ($schedule as $entry) {
            $date = \Carbon\Carbon::parse($entry['date']);
            $monthKey = $date->format('Y-m');
            
            if (!isset($months[$monthKey])) {
                $months[$monthKey] = [
                    'year' => $date->year,
                    'month' => $date->month,
                    'name' => $date->format('F Y'),
                    'days' => [],
                    'first_day_of_week' => $date->copy()->startOfMonth()->dayOfWeekIso,
                    'days_in_month' => $date->daysInMonth,
                ];
            }
            
            $months[$monthKey]['days'][$date->day] = [
                'reading' => $entry['reading'],
                'word_count' => $entry['word_count'],
            ];
        }
        
        return $months;
    }
}
