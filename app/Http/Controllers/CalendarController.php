<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Http\Response;

class CalendarController extends Controller
{
    public function show(Plan $plan): Response
    {
        $icsContent = $this->generateIcs($plan);

        return response($icsContent, 200)
            ->header('Content-Type', 'text/calendar; charset=utf-8')
            ->header('Content-Disposition', 'inline; filename="reading-plan-' . $plan->id . '.ics"')
            ->header('Cache-Control', 'no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', gmdate('D, d M Y H:i:s', time()) . ' GMT');
    }

    protected function generateIcs(Plan $plan): string
    {
        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Scripture Scheduler//Reading Plan//EN',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'X-WR-CALNAME:Scripture Reading Plan',
            'X-WR-CALDESC:Daily scripture reading schedule from ' . $plan->start_date->format('M j, Y') . ' to ' . $plan->end_date->format('M j, Y'),
        ];

        foreach ($plan->schedule as $index => $entry) {
            $lines = array_merge($lines, $this->generateEvent($plan, $entry, $index));
        }

        $lines[] = 'END:VCALENDAR';

        return implode("\r\n", $lines) . "\r\n";
    }

    protected function generateEvent(Plan $plan, array $entry, int $index): array
    {
        $date = $entry['date'];
        $reading = $entry['reading'];
        $wordCount = number_format($entry['word_count']);
        
        $uid = 'reading-' . $plan->id . '-' . $date . '@scripturescheduler';
        $dtstamp = gmdate('Ymd\THis\Z');
        $dtstart = str_replace('-', '', $date);
        $dtend = date('Ymd', strtotime($date . ' +1 day'));
        
        $summary = $this->escapeIcs('Scripture Reading: ' . $reading);
        $description = $this->escapeIcs(
            "Today's reading: " . $reading . "\\n\\n" .
            "Word count: " . $wordCount . "\\n\\n" .
            "Track progress at: " . route('plans.show', $plan)
        );

        return [
            'BEGIN:VEVENT',
            'UID:' . $uid,
            'DTSTAMP:' . $dtstamp,
            'DTSTART;VALUE=DATE:' . $dtstart,
            'DTEND;VALUE=DATE:' . $dtend,
            'SUMMARY:' . $summary,
            'DESCRIPTION:' . $description,
            'CATEGORIES:Scripture Study',
            'STATUS:CONFIRMED',
            'TRANSP:TRANSPARENT',
            'BEGIN:VALARM',
            'TRIGGER:-PT15M',
            'ACTION:DISPLAY',
            'DESCRIPTION:Scripture reading reminder',
            'END:VALARM',
            'END:VEVENT',
        ];
    }

    protected function escapeIcs(string $text): string
    {
        $text = str_replace('\\', '\\\\', $text);
        $text = str_replace(',', '\\,', $text);
        $text = str_replace(';', '\\;', $text);
        $text = str_replace("\n", '\\n', $text);
        return $text;
    }
}
