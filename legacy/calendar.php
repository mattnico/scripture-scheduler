<?php
// ICS Calendar Feed Generator for Scripture Reading Plans
// This file generates iCalendar (.ics) format feeds that users can subscribe to in their calendar apps

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get plan ID from URL
$plan_id = '';
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $plan_id = $_GET['id'];
} else {
    $request_uri = $_SERVER['REQUEST_URI'];
    if (preg_match('#/calendar/([a-z0-9]+)#', $request_uri, $matches)) {
        $plan_id = $matches[1];
    }
}

if (empty($plan_id)) {
    http_response_code(400);
    die('Error: No plan ID specified');
}

// Load plan data
$plan_file = 'plans/' . $plan_id . '.json';
if (!file_exists($plan_file)) {
    http_response_code(404);
    die('Error: Plan not found');
}

$plan_data = json_decode(file_get_contents($plan_file), true);
if (!$plan_data) {
    http_response_code(500);
    die('Error: Invalid plan data');
}

// Helper function to format text for ICS
function ics_escape($text) {
    $text = str_replace('\\', '\\\\', $text);
    $text = str_replace(',', '\\,', $text);
    $text = str_replace(';', '\\;', $text);
    $text = str_replace("\n", '\\n', $text);
    $text = str_replace("\r", '', $text);
    return $text;
}

// Helper function to format date for ICS
function ics_date($date_string) {
    return date('Ymd', strtotime($date_string));
}

// Helper function to generate UID for events
function generate_uid($plan_id, $date) {
    return 'reading-' . $plan_id . '-' . $date . '@smallsimple.net';
}

// Helper function to wrap long lines in ICS format
function ics_wrap($string, $line_length = 75) {
    $lines = array();
    $current_line = '';
    
    $words = explode(' ', $string);
    foreach ($words as $word) {
        if (strlen($current_line . ' ' . $word) > $line_length && !empty($current_line)) {
            $lines[] = $current_line;
            $current_line = ' ' . $word; // Start continuation with space
        } else {
            $current_line .= (empty($current_line) ? '' : ' ') . $word;
        }
    }
    
    if (!empty($current_line)) {
        $lines[] = $current_line;
    }
    
    return implode("\r\n", $lines);
}

// Helper functions from plan.php for reading range formatting
function parseScriptureReference($reference) {
    if (preg_match('/^(.+?)\s+(\d+)(?::(\d+))?$/', $reference, $matches)) {
        return array(
            'book' => trim($matches[1]),
            'chapter' => (int)$matches[2],
            'verse' => isset($matches[3]) ? (int)$matches[3] : null
        );
    }
    return null;
}

function formatCalendarReadingRange($reading, $previous_reading, $scheduling_method, $is_first_reading = false) {
    if ($is_first_reading) {
        return 'Read through ' . $reading['reading'];
    }
    
    if (!$previous_reading) {
        return 'Read through ' . $reading['reading'];
    }
    
    $start_ref = $previous_reading['reading'];
    $end_ref = $reading['reading'];
    
    $start_parsed = parseScriptureReference($start_ref);
    $end_parsed = parseScriptureReference($end_ref);
    
    if (!$start_parsed || !$end_parsed) {
        return 'Continue from ' . $start_ref . ' to ' . $end_ref;
    }
    
    if ($start_parsed['book'] === $end_parsed['book'] && $start_parsed['chapter'] === $end_parsed['chapter']) {
        return 'Read through ' . $reading['reading'];
    }
    
    if ($start_parsed['book'] === $end_parsed['book']) {
        if ($scheduling_method === 'chapter') {
            $start_chapter = $start_parsed['chapter'] + 1;
            if ($start_chapter === $end_parsed['chapter']) {
                return $end_parsed['book'] . ' ' . $end_parsed['chapter'];
            } else {
                return $end_parsed['book'] . ' ' . $start_chapter . '-' . $end_parsed['chapter'];
            }
        } else {
            if ($start_parsed['chapter'] === $end_parsed['chapter']) {
                if (isset($start_parsed['verse']) && isset($end_parsed['verse'])) {
                    $start_verse = $start_parsed['verse'] + 1;
                    if ($start_verse === $end_parsed['verse']) {
                        return $end_parsed['book'] . ' ' . $end_parsed['chapter'] . ':' . $end_parsed['verse'];
                    } else {
                        return $end_parsed['book'] . ' ' . $end_parsed['chapter'] . ':' . $start_verse . '-' . $end_parsed['verse'];
                    }
                } else {
                    return $end_parsed['book'] . ' ' . $end_parsed['chapter'];
                }
            } else {
                $start_verse = isset($start_parsed['verse']) ? $start_parsed['verse'] + 1 : 1;
                $start_display = ($start_parsed['chapter'] + 1) . ':' . $start_verse;
                $end_display = $end_parsed['chapter'] . ':' . (isset($end_parsed['verse']) ? $end_parsed['verse'] : 'end');
                return $end_parsed['book'] . ' ' . $start_display . ' - ' . $end_display;
            }
        }
    } else {
        return $start_parsed['book'] . ' (continue) - ' . $end_parsed['book'] . ' ' . $end_parsed['chapter'];
    }
}

function formatReadingInfo($reading, $scheduling_method) {
    $word_info = number_format($reading['word_count']) . ' words';
    
    if ($scheduling_method === 'chapter') {
        if (isset($reading['chapter_count'])) {
            $chapter_count = $reading['chapter_count'];
            $verse_count = $reading['verse_count'];
            $chapter_info = $chapter_count . ($chapter_count == 1 ? ' chapter' : ' chapters');
            $verse_info = $verse_count . ($verse_count == 1 ? ' verse' : ' verses');
            return $chapter_info . ' • ' . $verse_info . ' • ' . $word_info;
        } else {
            $chapter_count = $reading['verse_count'];
            $chapter_info = $chapter_count . ($chapter_count == 1 ? ' chapter' : ' chapters');
            return $chapter_info . ' • ' . $word_info;
        }
    } else {
        $verse_count = $reading['verse_count'];
        $verse_info = $verse_count . ($verse_count == 1 ? ' verse' : ' verses');
        return $verse_info . ' • ' . $word_info;
    }
}

// Set proper headers for ICS file
header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: attachment; filename="scripture-reading-plan-' . $plan_id . '.ics"');

// Start building the ICS content
$ics_content = "BEGIN:VCALENDAR\r\n";
$ics_content .= "VERSION:2.0\r\n";
$ics_content .= "PRODID:-//Small Simple//Scripture Reading Plan//EN\r\n";
$ics_content .= "CALSCALE:GREGORIAN\r\n";
$ics_content .= "METHOD:PUBLISH\r\n";
$ics_content .= "X-WR-CALNAME:Scripture Reading Plan\r\n";
$ics_content .= "X-WR-CALDESC:Daily scripture reading schedule from " . 
                date('M j, Y', strtotime($plan_data['parameters']['start_date'])) . " to " . 
                date('M j, Y', strtotime($plan_data['parameters']['end_date'])) . "\r\n";
$ics_content .= "X-WR-TIMEZONE:America/Denver\r\n";

// Generate events for each reading
foreach ($plan_data['schedule'] as $index => $reading) {
    // Find previous reading for range formatting
    $previous_reading = null;
    if ($index > 0) {
        $previous_reading = $plan_data['schedule'][$index - 1];
    } elseif (isset($reading['virtual_previous_reading'])) {
        $previous_reading = array('reading' => $reading['virtual_previous_reading']);
    }
    
    $is_first_reading = ($index == 0 && !isset($reading['virtual_previous_reading']));
    
    // Format the reading assignment
    $reading_title = formatCalendarReadingRange($reading, $previous_reading, $plan_data['parameters']['scheduling_method'], $is_first_reading);
    $reading_details = formatReadingInfo($reading, $plan_data['parameters']['scheduling_method']);
    
    // Create event description
    $description = "Today's scripture reading assignment:\n\n";
    $description .= $reading_title . "\n\n";
    $description .= "Details: " . $reading_details . "\n\n";
    $description .= "Track your progress at: ";
    $description .= (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . '/schedule/plan/' . $plan_id;
    
    // Generate UID and timestamps
    $uid = generate_uid($plan_id, $reading['date']);
    $created_timestamp = date('Ymd\THis\Z');
    
    // Add event to ICS
    $ics_content .= "BEGIN:VEVENT\r\n";
    $ics_content .= "UID:" . $uid . "\r\n";
    $ics_content .= "DTSTAMP:" . $created_timestamp . "\r\n";
    $ics_content .= "DTSTART;VALUE=DATE:" . ics_date($reading['date']) . "\r\n";
    $ics_content .= "DTEND;VALUE=DATE:" . ics_date(date('Y-m-d', strtotime($reading['date'] . ' +1 day'))) . "\r\n";
    $ics_content .= ics_wrap("SUMMARY:" . ics_escape("Scripture Reading: " . $reading_title)) . "\r\n";
    $ics_content .= ics_wrap("DESCRIPTION:" . ics_escape($description)) . "\r\n";
    $ics_content .= "CATEGORIES:Scripture Study,Religious\r\n";
    $ics_content .= "STATUS:CONFIRMED\r\n";
    $ics_content .= "TRANSP:TRANSPARENT\r\n";
    
    // Add reminder (15 minutes before, which will be at 11:45 PM the night before for all-day events)
    $ics_content .= "BEGIN:VALARM\r\n";
    $ics_content .= "TRIGGER:-PT15M\r\n";
    $ics_content .= "ACTION:DISPLAY\r\n";
    $ics_content .= "DESCRIPTION:" . ics_escape("Scripture reading reminder: " . $reading_title) . "\r\n";
    $ics_content .= "END:VALARM\r\n";
    
    $ics_content .= "END:VEVENT\r\n";
}

$ics_content .= "END:VCALENDAR\r\n";

// Output the ICS content
echo $ics_content;
?> 