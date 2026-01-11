<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Debug: Check what we're receiving
// Uncomment these lines temporarily for debugging:
// echo "REQUEST_URI: " . $_SERVER['REQUEST_URI'] . "<br>";
// echo "GET params: " . print_r($_GET, true) . "<br>";
// echo "PATH_INFO: " . (isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : 'not set') . "<br>";


/*/////////////////////////////////////////////////////////////////////////////////////////////////
//
// COMPLETED FEATURES:
//   ✓ Added "Recalculate" button that appears when user is more than 2 days behind
//   ✓ Added helpful "Catch up tips" that show randomly when user is 1-2 days behind  
//   ✓ Fixed "Back to Plan" button in edit.php to use proper URL routing
//   ✓ Added calendar feed functionality - users can subscribe to their reading plan in calendar apps
//
// TO DO:
//   - Make it so marking a reading as complete/incomplete doesn't reload the page. It's stuffing
//     the user's history with a bunch of unuseful reloads.
//   - Fix the formatting of "Open on your phone" button to match the other buttons.
//
// Future enhancements could include:
//   - Email/SMS reminders for missed readings
//   - Progress streaks and achievements
//   - Sharing progress with friends/family
/////////////////////////////////////////////////////////////////////////////////////////////////*/


// Check if this is an edit request and redirect to edit.php
$request_uri = $_SERVER['REQUEST_URI'];
if (preg_match('#/plan/([a-z0-9]+)/edit/?#', $request_uri, $matches)) {
    $plan_id = $matches[1];
    header('Location: /schedule/edit.php?id=' . $plan_id);
    exit;
}

// Get plan ID from URL - try multiple methods
$plan_id = '';

// Method 1: Standard GET parameter (for old URLs and if rewrite works)
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $plan_id = $_GET['id'];
} 
// Method 2: Parse from REQUEST_URI if rewrite isn't working
else {
    $request_uri = $_SERVER['REQUEST_URI'];
    // Match pattern: /[directory]/plan/abc123 or /plan/abc123
    if (preg_match('#/plan/([a-z0-9]+)#', $request_uri, $matches)) {
        $plan_id = $matches[1];
    }
}

if (empty($plan_id)) {
    die('Error: No plan ID specified. URI: ' . $_SERVER['REQUEST_URI']);
}

// Load plan data
$plan_file = 'plans/' . $plan_id . '.json';
if (!file_exists($plan_file)) {
    die('Error: Plan not found');
}

$plan_data = json_decode(file_get_contents($plan_file), true);
if (!$plan_data) {
    die('Error: Invalid plan data');
}

// CSV loading functions needed for calculateNextStartingVerse
if (!function_exists('load_csv_data')) {
function load_csv_data() {
    static $csv_data = null;
    if ($csv_data === null) {
        $csv_data = array();
        if (($handle = fopen("data/lds-scriptures.csv", "r")) !== FALSE) {
            $header = fgetcsv($handle, 1000, ","); // Read header row
            while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $record = array_combine($header, $row);
                $csv_data[] = array(
                    'verse_title' => $record['verse_title'],
                    'word_count' => intval($record['word_count'])
                );
            }
            fclose($handle);
        }
    }
    return $csv_data;
}
}

if (!function_exists('load_chapter_data')) {
function load_chapter_data() {
    static $chapter_data = null;
    if ($chapter_data === null) {
        $chapter_data = array();
        if (($handle = fopen("data/lds-scriptures-chapters.csv", "r")) !== FALSE) {
            $header = fgetcsv($handle, 1000, ","); // Read header row
            while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $record = array_combine($header, $row);
                // Map volume titles to volume IDs for compatibility
                $volume_map = array(
                    'Old Testament' => 1,
                    'New Testament' => 2,
                    'Book of Mormon' => 3,
                    'Doctrine and Covenants' => 4,
                    'Pearl of Great Price' => 5
                );
                
                $volume_id = isset($record['volume_title']) && isset($volume_map[$record['volume_title']]) 
                    ? $volume_map[$record['volume_title']] : 1;
                
                $chapter_data[] = array(
                    'verse_id' => isset($record['verse_id']) ? intval($record['verse_id']) : 0,
                    'volume_title' => isset($record['volume_title']) ? $record['volume_title'] : '',
                    'verse_title' => $record['verse_title'],
                    'word_count' => intval($record['word_count']),
                    'verse_count' => isset($record['verse_count']) ? intval($record['verse_count']) : 0,
                    'volume_id' => $volume_id
                );
            }
            fclose($handle);
        }
    }
    return $chapter_data;
}
}

// Helper function to calculate the next starting verse
if (!function_exists('calculateNextStartingVerse')) {
function calculateNextStartingVerse($last_verse, $scheduling_method) {
    // Load the appropriate data source
    if ($scheduling_method === 'chapter') {
        $data_source = load_chapter_data();
    } else {
        $data_source = load_csv_data();
    }
    
    // Find the current verse/chapter in the data
    $current_index = -1;
    for ($i = 0; $i < count($data_source); $i++) {
        if ($data_source[$i]['verse_title'] === $last_verse) {
            $current_index = $i;
            break;
        }
    }
    
    if ($current_index >= 0 && $current_index + 1 < count($data_source)) {
        // Return the next item in sequence
        return $data_source[$current_index + 1]['verse_title'];
    }
    
    // Fallback: Try to match by parsing the reference
    if (preg_match('/^(.+?)\s+(\d+)(?::(\d+))?$/', $last_verse, $matches)) {
        $book_name = trim($matches[1]);
        $chapter_num = (int)$matches[2];
        $verse_num = isset($matches[3]) ? (int)$matches[3] : null;
        
        // Look for entries that match this parsed reference
        foreach ($data_source as $i => $row) {
            $row_title = $row['verse_title'];
            
            // Parse the row title to compare
            if (preg_match('/^(.+?)\s+(\d+)(?::(\d+))?$/', $row_title, $row_matches)) {
                $row_book = trim($row_matches[1]);
                $row_chapter = (int)$row_matches[2];
                $row_verse = isset($row_matches[3]) ? (int)$row_matches[3] : null;
                
                // Check if this matches our search
                $book_match = ($row_book === $book_name);
                $chapter_match = ($row_chapter === $chapter_num);
                $verse_match = ($verse_num === null && $row_verse === null) || ($verse_num === $row_verse);
                
                if ($book_match && $chapter_match && $verse_match) {
                    // Found a match, return the next entry if it exists
                    if ($i + 1 < count($data_source)) {
                        return $data_source[$i + 1]['verse_title'];
                    }
                    break;
                }
            }
        }
    }
    
    // Ultimate fallback
    return $last_verse;
}
}

// Handle progress updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_progress'])) {
    $date = $_POST['date'];
    if (isset($plan_data['progress'][$date])) {
        $plan_data['progress'][$date] = !$plan_data['progress'][$date];
        
        // Save updated progress
        file_put_contents($plan_file, json_encode($plan_data, JSON_PRETTY_PRINT));
        
        // Redirect to prevent re-submission
        header('Location: plan.php?id=' . $plan_id);
        exit;
    }
}

// Handle recalculate request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['recalculate_plan'])) {
    // Find the last completed reading to determine new starting verse
    $last_completed_reading = null;
    $last_completed_date = null;
    
    foreach ($plan_data['schedule'] as $reading) {
        if (isset($plan_data['progress'][$reading['date']]) && $plan_data['progress'][$reading['date']]) {
            if (!$last_completed_date || $reading['date'] > $last_completed_date) {
                $last_completed_reading = $reading;
                $last_completed_date = $reading['date'];
            }
        }
    }
    
    // Prepare parameters for recalculation - use existing plan settings
    $recalc_params = array(
        'start_date' => date('Y-m-d'), // Start from today
        'end_date' => $plan_data['parameters']['end_date'],
        'scheduling_method' => $plan_data['parameters']['scheduling_method'],
        'volumes' => $plan_data['parameters']['volumes']
    );
    
    // Determine new starting verse
    if ($last_completed_reading) {
        $next_verse = calculateNextStartingVerse($last_completed_reading['reading'], $plan_data['parameters']['scheduling_method']);
        if ($next_verse === $last_completed_reading['reading']) {
            // If calculateNextStartingVerse returned the same verse, it means it couldn't find the next one
            die('Error: Beginning verse reference not found in database. Last completed reading: ' . $last_completed_reading['reading'] . '. Please check that this reference exists in your selected volumes.');
        }
        $recalc_params['beginning_verse'] = $next_verse;
    } else {
        $recalc_params['beginning_verse'] = $plan_data['parameters']['beginning_verse'];
    }
    
    // Prepare form data for plan regeneration using calc.php format
    $calc_params = array(
        'start_date' => $recalc_params['start_date'],
        'end_date' => $recalc_params['end_date'],
        'format' => 'table',
        'scheduling_method' => $recalc_params['scheduling_method'],
        'beginning_verse' => $recalc_params['beginning_verse'],
        'show_word_count' => 'on'
    );
    
    // Add volume checkboxes in calc.php format
    // Convert from plan.json volume IDs to calc.php volume flags
    if (isset($recalc_params['volumes']) && is_array($recalc_params['volumes'])) {
        foreach ($recalc_params['volumes'] as $volume_id) {
            switch($volume_id) {
                case 1:
                    $calc_params['ot'] = 'on';
                    break;
                case 2:
                    $calc_params['nt'] = 'on';
                    break;
                case 3:
                    $calc_params['bom'] = 'on';
                    break;
                case 4:
                    $calc_params['dc'] = 'on';
                    break;
                case 5:
                    $calc_params['pgp'] = 'on';
                    break;
            }
        }
    }
    
    // Set up environment for calc.php
    $_REQUEST = $calc_params;
    $_POST = $calc_params;
    $_GET = array();
    
    // Include calc.php to generate the schedule data
    ob_start();
    include 'calc.php';
    $calc_output = ob_get_contents();
    ob_end_clean();
    
    // Check if schedule was generated successfully
    if (isset($schedule) && !empty($schedule)) {
        // SMART CONTINUATION: Preserve completed readings, generate new schedule for remaining content
        
        // Step 1: Collect all completed readings and preserve them exactly
        $preserved_readings = array();
        $preserved_progress = array();
        
        foreach ($plan_data['schedule'] as $reading) {
            if (isset($plan_data['progress'][$reading['date']]) && $plan_data['progress'][$reading['date']]) {
                // Keep completed readings with their original dates
                $preserved_readings[] = $reading;
                $preserved_progress[$reading['date']] = true;
            }
        }
        
        // Step 2: Convert new schedule to plan format (these will be future readings)
        $new_future_readings = array();
        foreach ($schedule as $day) {
            if (!empty($day['verse'])) {
                $date_key = $day['year'] . '-' . str_pad($day['month'], 2, '0', STR_PAD_LEFT) . '-' . str_pad($day['day_of_month'], 2, '0', STR_PAD_LEFT);
                $reading_data = array(
                    'date' => $date_key,
                    'reading' => $day['verse'],
                    'word_count' => intval($day['word_count']),
                    'verse_count' => intval($day['verse_count'])
                );
                
                if ($recalc_params['scheduling_method'] === 'chapter' && isset($day['chapter_count'])) {
                    $reading_data['chapter_count'] = intval($day['chapter_count']);
                }
                
                if (isset($day['virtual_previous_reading'])) {
                    $reading_data['virtual_previous_reading'] = $day['virtual_previous_reading'];
                }
                
                $new_future_readings[] = $reading_data;
                // Initialize future readings as not completed
                $preserved_progress[$date_key] = false;
            }
        }
        
        // Step 3: Combine preserved + new readings into full schedule
        $combined_schedule = array_merge($preserved_readings, $new_future_readings);
        
        // Sort by date to maintain chronological order
        usort($combined_schedule, function($a, $b) {
            return strtotime($a['date']) - strtotime($b['date']);
        });
        
        // Step 4: Create updated plan data structure
        $updated_plan_data = array(
            'parameters' => array(
                'start_date' => $plan_data['parameters']['start_date'], // Keep original start date
                'end_date' => $recalc_params['end_date'],
                'scheduling_method' => $recalc_params['scheduling_method'],
                'beginning_verse' => $plan_data['parameters']['beginning_verse'], // Keep original beginning verse
                'show_word_count' => 'on',
                'volumes' => $recalc_params['volumes']
            ),
            'schedule' => $combined_schedule,
            'progress' => $preserved_progress,
            'stats' => array(
                // Keep original plan statistics - this reflects the complete journey
                'total_words' => $plan_data['stats']['total_words'],
                'words_per_day' => $plan_data['stats']['words_per_day'],
                'days_to_read' => count($combined_schedule) // Update to reflect actual final schedule length
            )
        );
        
        // Step 5: Preserve plan metadata
        $updated_plan_data['id'] = $plan_id;
        $updated_plan_data['created'] = isset($plan_data['created']) ? $plan_data['created'] : date('Y-m-d H:i:s');
        $updated_plan_data['updated'] = date('Y-m-d H:i:s');
        
        // Save the updated plan
        if (file_put_contents($plan_file, json_encode($updated_plan_data, JSON_PRETTY_PRINT))) {
            // Redirect to prevent re-submission
            header('Location: /schedule/plan/' . $plan_id);
            exit;
        }
    }
}



// Get today's date
$today = date('Y-m-d');

// Find the next uncompleted reading (starting from today or earlier if behind)
$current_reading = null;
$current_index = -1;

// First, try to find the first uncompleted reading from today onwards
foreach ($plan_data['schedule'] as $index => $day) {
    if ($day['date'] >= $today && (!isset($plan_data['progress'][$day['date']]) || !$plan_data['progress'][$day['date']])) {
        $current_reading = $day;
        $current_index = $index;
        break;
    }
}

// If no uncompleted reading found from today onwards, check if we're behind
// (find the first uncompleted reading from the beginning)
if (!$current_reading) {
    foreach ($plan_data['schedule'] as $index => $day) {
        if (!isset($plan_data['progress'][$day['date']]) || !$plan_data['progress'][$day['date']]) {
            $current_reading = $day;
            $current_index = $index;
            break;
        }
    }
}

// Calculate progress statistics
$total_days = count($plan_data['schedule']);
$completed_days = array_sum($plan_data['progress']);
$progress_percentage = $total_days > 0 ? round(($completed_days / $total_days) * 100) : 0;

// Calculate days ahead/behind with the correct logic:
// - Behind: haven't done yesterday's reading
// - On Track: done yesterday's (gray check) or today's (green check)  
// - Ahead: done tomorrow's or beyond

$yesterday = date('Y-m-d', strtotime('-1 day'));
$expected_through_today = 0;
$expected_through_yesterday = 0;

foreach ($plan_data['schedule'] as $day) {
    if ($day['date'] <= $today) {
        $expected_through_today++;
    }
    if ($day['date'] <= $yesterday) {
        $expected_through_yesterday++;
    }
}

// Check if today's reading is completed (for on-track status color)
$today_completed = isset($plan_data['progress'][$today]) && $plan_data['progress'][$today];

// Calculate days difference
if ($completed_days < $expected_through_yesterday) {
    // Behind (haven't done yesterday's)
    $days_difference = $completed_days - $expected_through_yesterday;
} else if ($completed_days <= $expected_through_today) {
    // On track (done yesterday's, maybe today's)
    $days_difference = 0;
} else {
    // Ahead (done tomorrow's or beyond)
    $days_difference = $completed_days - $expected_through_today;
}

// Get recent readings (last 7 completed + any from last 7 calendar days)
$recent_readings = array();
$completed_readings = array();
$calendar_readings = array();

// First, get all completed readings
foreach ($plan_data['schedule'] as $day) {
    if (isset($plan_data['progress'][$day['date']]) && $plan_data['progress'][$day['date']]) {
        $completed_readings[] = $day;
    }
}

// Sort completed readings by date (most recent first)
usort($completed_readings, function($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
});

// Take the last 5 completed readings
$recent_completed = array_slice($completed_readings, 0, 5);

// Also get readings from the last 7 calendar days (to show today and upcoming)
$current_date = strtotime($today);
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days", $current_date));
    foreach ($plan_data['schedule'] as $day) {
        if ($day['date'] === $date) {
            $calendar_readings[] = $day;
            break;
        }
    }
}

// Combine and deduplicate
$all_readings = array_merge($recent_completed, $calendar_readings);
$seen_dates = array();
foreach ($all_readings as $reading) {
    if (!in_array($reading['date'], $seen_dates)) {
        $recent_readings[] = $reading;
        $seen_dates[] = $reading['date'];
    }
}

// Sort by date (most recent first)
usort($recent_readings, function($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
});

// Limit to 7 total readings
$recent_readings = array_slice($recent_readings, 0, 7);

// Helper function to format reading info based on scheduling method
function formatReadingInfo($reading, $scheduling_method) {
    $word_info = number_format($reading['word_count']) . ' words';
    
    if ($scheduling_method === 'chapter') {
        // For chapter-based: show chapters and verses first, then words
        if (isset($reading['chapter_count'])) {
            // New format: we have separate chapter_count and verse_count
            $chapter_count = $reading['chapter_count'];
            $verse_count = $reading['verse_count'];
            $chapter_info = $chapter_count . ($chapter_count == 1 ? ' chapter' : ' chapters');
            $verse_info = $verse_count . ($verse_count == 1 ? ' verse' : ' verses');
            return $chapter_info . ' &#8226; ' . $verse_info . ' &#8226; ' . $word_info;
        } else {
            // Legacy format: verse_count actually stores chapter count
            $chapter_count = $reading['verse_count'];
            $chapter_info = $chapter_count . ($chapter_count == 1 ? ' chapter' : ' chapters');
            return $chapter_info . ' &#8226; ' . $word_info;
        }
    } else {
        // For verse-based: show verses first, then words
        $verse_count = $reading['verse_count'];
        $verse_info = $verse_count . ($verse_count == 1 ? ' verse' : ' verses');
        return $verse_info . ' &#8226; ' . $word_info;
    }
}

// Helper function to format reading range display
function formatReadingRange($reading, $previous_reading, $scheduling_method, $is_first_reading = false) {
    if ($is_first_reading) {
        // For the first reading, show it as "Read through [end reference]"
        return 'Read through ' . htmlspecialchars($reading['reading']);
    }
    
    if (!$previous_reading) {
        // Fallback if no previous reading data
        return 'Read through ' . htmlspecialchars($reading['reading']);
    }
    
    $start_ref = $previous_reading['reading'];
    $end_ref = $reading['reading'];
    
    // Parse references more carefully
    $start_parsed = parseScriptureReference($start_ref);
    $end_parsed = parseScriptureReference($end_ref);
    
    if (!$start_parsed || !$end_parsed) {
        // Fallback if parsing failed
        return 'Continue from ' . htmlspecialchars($start_ref) . ' to ' . htmlspecialchars($end_ref);
    }
    
    // For the special case where we're dealing with the second reading after a custom starting verse,
    // we need to be more careful about the range calculation
    if ($start_parsed['book'] === $end_parsed['book'] && $start_parsed['chapter'] === $end_parsed['chapter']) {
        // If previous and current reading end in the same chapter, this might be the first reading
        // after a custom starting verse - just show the current reading
        return 'Read through ' . htmlspecialchars($reading['reading']);
    }
    
    // Determine the starting point for the current reading
    $start_point = calculateNextReference($start_parsed, $scheduling_method);
    
    // Safety check: if start_point is after end_parsed, something is wrong - fall back to simple display
    if ($start_parsed['book'] === $end_parsed['book'] && $start_point['chapter'] > $end_parsed['chapter']) {
        // This indicates a data issue - just show the end reference
        return 'Read through ' . htmlspecialchars($reading['reading']);
    }
    
    if ($start_parsed['book'] === $end_parsed['book']) {
        // Same book - show range within the book
        if ($scheduling_method === 'chapter') {
            if ($start_point['chapter'] === $end_parsed['chapter']) {
                // Single chapter
                return htmlspecialchars($end_parsed['book'] . ' ' . $end_parsed['chapter']);
            } else {
                // Multiple chapters - ensure proper order
                return htmlspecialchars($end_parsed['book'] . ' ' . $start_point['chapter'] . '-' . $end_parsed['chapter']);
            }
        } else {
            // Verse-based scheduling
            if ($start_point['chapter'] === $end_parsed['chapter']) {
                // Same chapter - show verse range
                if (isset($start_point['verse']) && isset($end_parsed['verse'])) {
                    if ($start_point['verse'] === $end_parsed['verse']) {
                        return htmlspecialchars($end_parsed['book'] . ' ' . $end_parsed['chapter'] . ':' . $end_parsed['verse']);
                    } else {
                        return htmlspecialchars($end_parsed['book'] . ' ' . $end_parsed['chapter'] . ':' . $start_point['verse'] . '-' . $end_parsed['verse']);
                    }
                } else {
                    return htmlspecialchars($end_parsed['book'] . ' ' . $end_parsed['chapter']);
                }
            } else {
                // Cross-chapter verse range
                $start_display = $start_point['chapter'] . ':' . (isset($start_point['verse']) && $start_point['verse'] ? $start_point['verse'] : '1');
                $end_display = $end_parsed['chapter'] . ':' . (isset($end_parsed['verse']) && $end_parsed['verse'] ? $end_parsed['verse'] : 'end');
                return htmlspecialchars($end_parsed['book'] . ' ' . $start_display . ' - ' . $end_display);
            }
        }
    } else {
        // Different books
        $start_display = formatReferenceDisplay($start_point);
        $end_display = formatReferenceDisplay($end_parsed);
        return htmlspecialchars($start_display . ' - ' . $end_display);
    }
}

// Helper function to get the starting reference for a reading (for URL generation)
function getReadingStartReference($reading, $previous_reading, $scheduling_method, $is_first_reading = false) {
    if ($is_first_reading) {
        // For the first reading, link to the end reference
        return $reading['reading'];
    }
    
    if (!$previous_reading) {
        // Fallback if no previous reading data
        return $reading['reading'];
    }
    
    $start_ref = $previous_reading['reading'];
    $end_ref = $reading['reading'];
    
    // Parse references more carefully
    $start_parsed = parseScriptureReference($start_ref);
    $end_parsed = parseScriptureReference($end_ref);
    
    if (!$start_parsed || !$end_parsed) {
        // Fallback if parsing failed
        return $end_ref;
    }
    
    // Determine the starting point for the current reading
    $start_point = calculateNextReference($start_parsed, $scheduling_method);
    
    if ($start_parsed['book'] === $end_parsed['book']) {
        // Same book - return starting reference
        if ($scheduling_method === 'chapter') {
            return $end_parsed['book'] . ' ' . $start_point['chapter'];
        } else {
            // Verse-based scheduling
            if ($start_point['chapter'] === $end_parsed['chapter']) {
                // Same chapter
                if (isset($start_point['verse']) && $start_point['verse']) {
                    return $end_parsed['book'] . ' ' . $end_parsed['chapter'] . ':' . $start_point['verse'];
                } else {
                    return $end_parsed['book'] . ' ' . $end_parsed['chapter'];
                }
            } else {
                // Cross-chapter verse range
                $verse = isset($start_point['verse']) && $start_point['verse'] ? $start_point['verse'] : 1;
                return $end_parsed['book'] . ' ' . $start_point['chapter'] . ':' . $verse;
            }
        }
    } else {
        // Different books - return starting reference
        return formatReferenceDisplay($start_point);
    }
}

// Helper function to parse scripture references
function parseScriptureReference($reference) {
    // Pattern to match: "Book Name Chapter:Verse" or "Book Name Chapter"
    // Handles: "1 Nephi 2:1", "Genesis 1", "Doctrine and Covenants 1:1"
    if (preg_match('/^(.+?)\s+(\d+)(?::(\d+))?$/', $reference, $matches)) {
        return array(
            'book' => trim($matches[1]),
            'chapter' => (int)$matches[2],
            'verse' => isset($matches[3]) ? (int)$matches[3] : null
        );
    }
    return null;
}

// Helper function to calculate the next reference point using CSV data
function calculateNextReference($parsed_ref, $scheduling_method) {
    // Reconstruct the reference string for lookup
    $reference_string = $parsed_ref['book'] . ' ' . $parsed_ref['chapter'];
    if (isset($parsed_ref['verse']) && $parsed_ref['verse']) {
        $reference_string .= ':' . $parsed_ref['verse'];
    }
    
    // Load the appropriate data source
    if ($scheduling_method === 'chapter') {
        $data_source = load_chapter_data();
    } else {
        $data_source = load_csv_data();
    }
    
    // Find the current verse/chapter in the data
    $current_index = -1;
    for ($i = 0; $i < count($data_source); $i++) {
        if ($data_source[$i]['verse_title'] === $reference_string) {
            $current_index = $i;
            break;
        }
    }
    
    if ($current_index >= 0 && $current_index + 1 < count($data_source)) {
        // Parse the next reference from the CSV data
        $next_reference = $data_source[$current_index + 1]['verse_title'];
        return parseScriptureReference($next_reference);
    }
    
    // Fallback to simple arithmetic if CSV lookup fails
    if ($scheduling_method === 'chapter') {
        return array(
            'book' => $parsed_ref['book'],
            'chapter' => $parsed_ref['chapter'] + 1,
            'verse' => null
        );
    } else {
        if ($parsed_ref['verse']) {
            return array(
                'book' => $parsed_ref['book'],
                'chapter' => $parsed_ref['chapter'],
                'verse' => $parsed_ref['verse'] + 1
            );
        } else {
            return array(
                'book' => $parsed_ref['book'],
                'chapter' => $parsed_ref['chapter'] + 1,
                'verse' => 1
            );
        }
    }
}



// Helper function to format a parsed reference for display
function formatReferenceDisplay($parsed_ref) {
    $display = $parsed_ref['book'] . ' ' . $parsed_ref['chapter'];
    if (isset($parsed_ref['verse']) && $parsed_ref['verse']) {
        $display .= ':' . $parsed_ref['verse'];
    }
    return $display;
}

// Helper function to map book names to Church website URL format
function getBookUrlInfo($bookName) {
    // Normalize book name for comparison
    $normalized = strtolower(str_replace(' ', '', $bookName));
    
    // Old Testament mapping
    $oldTestament = array(
        'genesis' => array('volume' => 'ot', 'book' => 'gen'),
        'exodus' => array('volume' => 'ot', 'book' => 'ex'),
        'leviticus' => array('volume' => 'ot', 'book' => 'lev'),
        'numbers' => array('volume' => 'ot', 'book' => 'num'),
        'deuteronomy' => array('volume' => 'ot', 'book' => 'deut'),
        'joshua' => array('volume' => 'ot', 'book' => 'josh'),
        'judges' => array('volume' => 'ot', 'book' => 'judg'),
        'ruth' => array('volume' => 'ot', 'book' => 'ruth'),
        '1samuel' => array('volume' => 'ot', 'book' => '1-sam'),
        '2samuel' => array('volume' => 'ot', 'book' => '2-sam'),
        '1kings' => array('volume' => 'ot', 'book' => '1-kgs'),
        '2kings' => array('volume' => 'ot', 'book' => '2-kgs'),
        '1chronicles' => array('volume' => 'ot', 'book' => '1-chr'),
        '2chronicles' => array('volume' => 'ot', 'book' => '2-chr'),
        'ezra' => array('volume' => 'ot', 'book' => 'ezra'),
        'nehemiah' => array('volume' => 'ot', 'book' => 'neh'),
        'esther' => array('volume' => 'ot', 'book' => 'esth'),
        'job' => array('volume' => 'ot', 'book' => 'job'),
        'psalms' => array('volume' => 'ot', 'book' => 'ps'),
        'proverbs' => array('volume' => 'ot', 'book' => 'prov'),
        'ecclesiastes' => array('volume' => 'ot', 'book' => 'eccl'),
        'songofsolomon' => array('volume' => 'ot', 'book' => 'song'),
        'isaiah' => array('volume' => 'ot', 'book' => 'isa'),
        'jeremiah' => array('volume' => 'ot', 'book' => 'jer'),
        'lamentations' => array('volume' => 'ot', 'book' => 'lam'),
        'ezekiel' => array('volume' => 'ot', 'book' => 'ezek'),
        'daniel' => array('volume' => 'ot', 'book' => 'dan'),
        'hosea' => array('volume' => 'ot', 'book' => 'hosea'),
        'joel' => array('volume' => 'ot', 'book' => 'joel'),
        'amos' => array('volume' => 'ot', 'book' => 'amos'),
        'obadiah' => array('volume' => 'ot', 'book' => 'obad'),
        'jonah' => array('volume' => 'ot', 'book' => 'jonah'),
        'micah' => array('volume' => 'ot', 'book' => 'micah'),
        'nahum' => array('volume' => 'ot', 'book' => 'nahum'),
        'habakkuk' => array('volume' => 'ot', 'book' => 'hab'),
        'zephaniah' => array('volume' => 'ot', 'book' => 'zeph'),
        'haggai' => array('volume' => 'ot', 'book' => 'hag'),
        'zechariah' => array('volume' => 'ot', 'book' => 'zech'),
        'malachi' => array('volume' => 'ot', 'book' => 'mal')
    );
    
    // New Testament mapping
    $newTestament = array(
        'matthew' => array('volume' => 'nt', 'book' => 'matt'),
        'mark' => array('volume' => 'nt', 'book' => 'mark'),
        'luke' => array('volume' => 'nt', 'book' => 'luke'),
        'john' => array('volume' => 'nt', 'book' => 'john'),
        'acts' => array('volume' => 'nt', 'book' => 'acts'),
        'romans' => array('volume' => 'nt', 'book' => 'rom'),
        '1corinthians' => array('volume' => 'nt', 'book' => '1-cor'),
        '2corinthians' => array('volume' => 'nt', 'book' => '2-cor'),
        'galatians' => array('volume' => 'nt', 'book' => 'gal'),
        'ephesians' => array('volume' => 'nt', 'book' => 'eph'),
        'philippians' => array('volume' => 'nt', 'book' => 'philip'),
        'colossians' => array('volume' => 'nt', 'book' => 'col'),
        '1thessalonians' => array('volume' => 'nt', 'book' => '1-thes'),
        '2thessalonians' => array('volume' => 'nt', 'book' => '2-thes'),
        '1timothy' => array('volume' => 'nt', 'book' => '1-tim'),
        '2timothy' => array('volume' => 'nt', 'book' => '2-tim'),
        'titus' => array('volume' => 'nt', 'book' => 'titus'),
        'philemon' => array('volume' => 'nt', 'book' => 'philem'),
        'hebrews' => array('volume' => 'nt', 'book' => 'heb'),
        'james' => array('volume' => 'nt', 'book' => 'james'),
        '1peter' => array('volume' => 'nt', 'book' => '1-pet'),
        '2peter' => array('volume' => 'nt', 'book' => '2-pet'),
        '1john' => array('volume' => 'nt', 'book' => '1-jn'),
        '2john' => array('volume' => 'nt', 'book' => '2-jn'),
        '3john' => array('volume' => 'nt', 'book' => '3-jn'),
        'jude' => array('volume' => 'nt', 'book' => 'jude'),
        'revelation' => array('volume' => 'nt', 'book' => 'rev')
    );
    
    // Book of Mormon mapping
    $bookOfMormon = array(
        '1nephi' => array('volume' => 'bofm', 'book' => '1-ne'),
        '2nephi' => array('volume' => 'bofm', 'book' => '2-ne'),
        'jacob' => array('volume' => 'bofm', 'book' => 'jacob'),
        'enos' => array('volume' => 'bofm', 'book' => 'enos'),
        'jarom' => array('volume' => 'bofm', 'book' => 'jarom'),
        'omni' => array('volume' => 'bofm', 'book' => 'omni'),
        'wordsofmormon' => array('volume' => 'bofm', 'book' => 'w-of-m'),
        'mosiah' => array('volume' => 'bofm', 'book' => 'mosiah'),
        'alma' => array('volume' => 'bofm', 'book' => 'alma'),
        'helaman' => array('volume' => 'bofm', 'book' => 'hel'),
        '3nephi' => array('volume' => 'bofm', 'book' => '3-ne'),
        '4nephi' => array('volume' => 'bofm', 'book' => '4-ne'),
        'mormon' => array('volume' => 'bofm', 'book' => 'morm'),
        'ether' => array('volume' => 'bofm', 'book' => 'ether'),
        'moroni' => array('volume' => 'bofm', 'book' => 'moro')
    );
    
    // Doctrine and Covenants mapping
    $doctrineAndCovenants = array(
        'doctrineandcovenants' => array('volume' => 'dc-testament', 'book' => 'dc')
    );
    
    // Pearl of Great Price mapping
    $pearlOfGreatPrice = array(
        'moses' => array('volume' => 'pgp', 'book' => 'moses'),
        'abraham' => array('volume' => 'pgp', 'book' => 'abr'),
        'josephsmith—matthew' => array('volume' => 'pgp', 'book' => 'js-m'),
        'josephsmith—history' => array('volume' => 'pgp', 'book' => 'js-h'),
        'articlesoffaith' => array('volume' => 'pgp', 'book' => 'a-of-f')
    );
    
    // Combine all mappings
    $allBooks = array_merge($oldTestament, $newTestament, $bookOfMormon, $doctrineAndCovenants, $pearlOfGreatPrice);
    
    // Try to find exact match first
    if (isset($allBooks[$normalized])) {
        return $allBooks[$normalized];
    }
    
    // Try some common alternate spellings/abbreviations
    $alternates = array(
        'dc' => 'doctrineandcovenants',
        'd&c' => 'doctrineandcovenants',
        'matt' => 'matthew',
        '1ne' => '1nephi',
        '2ne' => '2nephi',
        '3ne' => '3nephi',
        '4ne' => '4nephi',
        'morm' => 'mormon',
        'hel' => 'helaman',
        'moro' => 'moroni'
    );
    
    if (isset($alternates[$normalized]) && isset($allBooks[$alternates[$normalized]])) {
        return $allBooks[$alternates[$normalized]];
    }
    
    return null;
}

// Helper function to generate Church website URLs
function generateScriptureUrl($reference) {
    $parsed = parseScriptureReference($reference);
    if (!$parsed) {
        return null;
    }
    
    $bookInfo = getBookUrlInfo($parsed['book']);
    if (!$bookInfo) {
        return null;
    }
    
    $url = 'https://www.churchofjesuschrist.org/study/scriptures/' . 
           $bookInfo['volume'] . '/' . $bookInfo['book'] . '/' . $parsed['chapter'] . 
           '?lang=eng';
    
    // Add verse parameter if specified
    if (isset($parsed['verse']) && $parsed['verse']) {
        $url .= '&id=p' . $parsed['verse'] . '#p' . $parsed['verse'];
    }
    
    return $url;
}
?>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">
    
    <!-- Primary Meta Tags -->
    <title>Scripture Reading Plan</title>
    <meta name="title" content="Scripture Reading Plan">
    <meta name="description" content="Track your daily scripture reading progress">
    
    <!-- Mobile Web App Meta Tags -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Scripture Plan">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="application-name" content="Scripture Plan">
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link href="../theme.css?v=<?= time() ?>" rel="stylesheet">
    
    <style>
        /* Plan-specific progress circle styling with dynamic PHP values */
        .progress-circle {
            background: conic-gradient(var(--accent-primary) 0deg <?= $progress_percentage * 3.6 ?>deg, var(--border-color) <?= $progress_percentage * 3.6 ?>deg 360deg);
        }
        
        /* Custom progress circle sizes for the summary card */
        .progress-circle[style*="60px"] {
            width: 60px !important;
            height: 60px !important;
            font-size: 0.9rem !important;
        }
        
        .progress-circle[style*="60px"]::before {
            width: 46px !important;
            height: 46px !important;
        }
        
        /* Larger progress circle with thinner ring */
        .progress-circle[style*="120px"] {
            width: 120px !important;
            height: 120px !important;
            font-size: 1.2rem !important;
        }
        
        .progress-circle[style*="120px"]::before {
            width: 100px !important;
            height: 100px !important;
        }
    </style>
</head>
<body>
    <button class="theme-toggle" id="theme-toggle" title="Toggle theme">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="5"/>
            <path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/>
        </svg>
    </button>
    
    <div class="plan-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-12">
                    <h1 class="mb-2">My Reading Plan <span class="text-muted" style="font-size: 0.8rem; vertical-align: top; font-weight: 800;">BETA</span></h1>
                    <p class="mb-0">
                        <?= date('M j, Y', strtotime($plan_data['parameters']['start_date'])) ?> - 
                        <?= date('M j, Y', strtotime($plan_data['parameters']['end_date'])) ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="row">
            <div class="col-lg-8">
                <?php if ($current_reading): ?>
                    <?php
                    $is_current_today = $current_reading['date'] === $today;
                    $is_current_past = $current_reading['date'] < $today;
                    $is_current_future = $current_reading['date'] > $today;
                    ?>
                    <div class="card mb-4 <?= $is_current_today ? 'today' : ($is_current_past ? 'completed' : 'future') ?>">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="card-title mb-0">
                                    <?php if ($is_current_today): ?>
                                        Today's Reading
                                    <?php elseif ($is_current_past): ?>
                                        Catch Up Reading
                                    <?php else: ?>
                                        Next Reading
                                    <?php endif; ?>
                                </h5>
                                <?php if ($is_current_today): ?>
                                    <span class="badge bg-warning status-badge">Today</span>
                                <?php elseif ($is_current_past): ?>
                                    <span class="badge bg-danger status-badge">Behind</span>
                                <?php else: ?>
                                    <span class="badge bg-info status-badge">
                                        <?= date('M j', strtotime($current_reading['date'])) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <h3 class="text-primary mb-3">
                                <?php
                                // Find the previous reading to show the range
                                $previous_reading = null;
                                if ($current_index > 0) {
                                    $previous_reading = $plan_data['schedule'][$current_index - 1];
                                } elseif (isset($current_reading['virtual_previous_reading'])) {
                                    // For the first reading after a starting verse, use the virtual previous reading
                                    $previous_reading = array('reading' => $current_reading['virtual_previous_reading']);
                                }
                                $is_first_reading = ($current_index == 0 && !isset($current_reading['virtual_previous_reading']));
                                echo formatReadingRange($current_reading, $previous_reading, $plan_data['parameters']['scheduling_method'], $is_first_reading);
                                ?>
                            </h3>
                            <div class="row">
                                <div class="col-sm-6">
                                    <small class="text-muted">
                                        <?= formatReadingInfo($current_reading, $plan_data['parameters']['scheduling_method']) ?>
                                    </small>
                                </div>
                                <div class="col-sm-6 text-end">
                                    <?php
                                    // Get the scripture URL for the current reading
                                    $previous_reading_for_url = null;
                                    if ($current_index > 0) {
                                        $previous_reading_for_url = $plan_data['schedule'][$current_index - 1];
                                    } elseif (isset($current_reading['virtual_previous_reading'])) {
                                        $previous_reading_for_url = array('reading' => $current_reading['virtual_previous_reading']);
                                    }
                                    $is_first_reading_for_url = ($current_index == 0 && !isset($current_reading['virtual_previous_reading']));
                                    $scripture_ref = getReadingStartReference($current_reading, $previous_reading_for_url, $plan_data['parameters']['scheduling_method'], $is_first_reading_for_url);
                                    $scripture_url = generateScriptureUrl($scripture_ref);
                                    ?>
                                    <?php if ($scripture_url): ?>
                                        <a href="<?= $scripture_url ?>" target="_blank" class="btn btn-outline-secondary btn-sm me-2">
                                            &#128214; Read Online
                                        </a>
                                    <?php endif; ?>
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="date" value="<?= $current_reading['date'] ?>">
                                        <input type="hidden" name="toggle_progress" value="1">
                                        <?php if (isset($plan_data['progress'][$current_reading['date']]) && $plan_data['progress'][$current_reading['date']]): ?>
                                            <button type="submit" class="btn btn-success btn-sm">
                                                &#10003; Completed
                                            </button>
                                        <?php else: ?>
                                            <button type="submit" class="btn btn-outline-primary btn-sm">
                                                Mark as Read
                                            </button>
                                        <?php endif; ?>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-success">
                        <h5>&#127881; Congratulations!</h5>
                        <p class="mb-0">You have completed your entire reading plan! Well done!</p>
                    </div>
                <?php endif; ?>

                <div class="card mb-4">
                    <div class="card-header">
                    <h5 class="mb-0">Progress Summary</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center align-items-center">
                            <div class="col-6 col-md-3 mb-3 mb-md-0">
                                <div class="py-2 px-2 mb-0 d-flex flex-column align-items-center justify-content-center">
                                    <div class="progress-circle mb-2" style="width: 120px; height: 120px; font-size: 1.2rem; box-shadow: 0 4px 16px var(--shadow-color);">
                                        <span class="progress-text" style="font-size: 1.6rem;"><?= $progress_percentage ?>%</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3 mb-3 mb-md-0">
                                <div class="py-2 px-2 mb-0 d-flex flex-column align-items-center justify-content-center" style="min-height: 120px;">
                                    <div class="h2 text-success mb-1"><?= $completed_days ?></div>
                                    <small class="text-muted">Days Done</small>
                                </div>
                            </div>
                            <div class="col-6 col-md-3 mb-3 mb-md-0">
                                <div class="py-2 px-2 mb-0 d-flex flex-column align-items-center justify-content-center" style="min-height: 120px;">
                                    <div class="h2 text-primary mb-1"><?= $total_days - $completed_days ?></div>
                                    <small class="text-muted">Days Left</small>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <?php if ($days_difference == 0): ?>
                                    <?php if ($today_completed): ?>
                                        <div class="alert alert-success py-2 px-2 mb-0 text-center d-flex flex-column align-items-center justify-content-center" style="min-height: 120px;">
                                            <div class="h2 text-success mb-1">&#10003;</div>
                                            <small class="text-success">On Track</small>
                                        </div>
                                    <?php else: ?>
                                        <div class="py-2 px-2 mb-0 d-flex flex-column align-items-center justify-content-center" style="min-height: 120px;">
                                            <div class="h2 text-muted mb-1">&#10003;</div>
                                            <small class="text-muted">On Track</small>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="py-2 px-2 mb-0 d-flex flex-column align-items-center justify-content-center" style="min-height: 120px;">
                                        <div class="h2 <?= $days_difference > 0 ? 'text-success' : 'text-warning' ?> mb-1">
                                            <?= $days_difference > 0 ? '+' . $days_difference : $days_difference ?>
                                        </div>
                                        <small class="text-muted">Days<br><?= $days_difference > 0 ? 'Ahead' : 'Behind' ?></small>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if ($days_difference < 0): ?>
                            <?php if (abs($days_difference) > 2): ?>
                                <!-- Show warning message for more than 2 days behind -->
                                <div class="alert alert-warning mt-3 py-2">
                                    <small><strong>You're <?= abs($days_difference) ?> day(s) behind schedule.</strong></small>
                                    <form method="post" class="mt-2">
                                        <input type="hidden" name="recalculate_plan" value="1">
                                        <button type="submit" class="btn btn-warning btn-sm" onclick="return confirm('This will recalculate your reading plan from where you left off. Your current progress will be preserved, but future readings will be rescheduled. Continue?')">
                                            Recalculate Plan
                                        </button>
                                    </form>
                                    <small class="text-muted d-block mt-1">This will create a new schedule starting from where you left off.</small>
                                </div>
                            <?php elseif (abs($days_difference) >= 1): ?>
                                <!-- Show catch up tips for 1-2 days behind -->
                                <?php
                                $catchup_tips = array(
                                    "Try listening to scriptures during your commute or while exercising.",
                                    "Consider reading during lunch breaks or while waiting in line.",
                                    "Read one extra verse or chapter with your regular reading to catch up gradually.",
                                    "Use audio scriptures while doing household chores or walking.",
                                    "Set a daily reminder on your phone to help establish a consistent reading habit.",
                                    "Find a quiet spot and read for just 10 minutes - you'll be surprised how much you can cover.",
                                    "Try reading right after you wake up or just before bed to build consistency.",
                                    "Consider pairing scripture reading with your daily routines like brushing your teeth or getting dressed.",
                                    "Use the Church's <a href='https://www.churchofjesuschrist.org/learn/mobile-applications/gospel-library?lang=eng' target='_blank'>Gospel Library</a> app to read scriptures on your phone anywhere.",
                                    "Join a family member or friend for daily scripture reading for mutual encouragement."
                                );
                                $random_tip = $catchup_tips[array_rand($catchup_tips)];
                                ?>
                                <div class="alert alert-info mt-3 py-2">
                                    <small><strong>Catch up tip:</strong> <?= $random_tip ?></small>
                                </div>
                                <!-- Show gentle recalculate option for 1-2 days behind -->
                                <div class="alert alert-info mt-2 py-2">
                                    <small>If you want to spread out your catch-up reading, you can always recalculate your plan from where you are.</small>
                                    <form method="post" class="mt-2">
                                        <input type="hidden" name="recalculate_plan" value="1">
                                        <button type="submit" class="btn btn-info btn-sm" onclick="return confirm('This will recalculate your reading plan from where you left off. Your current progress will be preserved, but future readings will be rescheduled. Continue?')">
                                            Recalculate Plan
                                        </button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        <?php elseif ($days_difference > 0): ?>
                            <div class="alert alert-success mt-3 py-2">
                                <small><strong>Great job!</strong> You're <?= $days_difference ?> day(s) ahead of schedule!</small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Recent Readings (Last 7 Days)</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_readings)): ?>
                            <p class="text-muted">No readings found for the past week.</p>
                        <?php else: ?>
                            <?php foreach ($recent_readings as $reading): ?>
                                <?php
                                $is_completed = isset($plan_data['progress'][$reading['date']]) && $plan_data['progress'][$reading['date']];
                                $is_today = $reading['date'] === $today;
                                $is_past = $reading['date'] < $today;
                                
                                $card_class = 'reading-card mb-3';
                                if ($is_completed) $card_class .= ' completed';
                                elseif ($is_today) $card_class .= ' today';
                                elseif ($is_past) $card_class .= ' future';
                                ?>
                                <div class="card <?= $card_class ?>">
                                    <div class="card-body py-2">
                                        <div class="row align-items-center">
                                            <div class="col-md-2">
                                                <small class="text-muted">
                                                    <?= date('M j', strtotime($reading['date'])) ?>
                                                    <?php if ($is_today): ?>
                                                        <span class="badge bg-warning status-badge ms-1">Today</span>
                                                    <?php endif; ?>
                                                </small>
                                            </div>
                                            <div class="col-md-6">
                                                <?php
                                                // Find this reading's index in the schedule to get the previous reading
                                                $reading_index = -1;
                                                $previous_reading_for_this = null;
                                                
                                                foreach ($plan_data['schedule'] as $idx => $scheduled_reading) {
                                                    if ($scheduled_reading['date'] === $reading['date']) {
                                                        $reading_index = $idx;
                                                        if ($idx > 0) {
                                                            $previous_reading_for_this = $plan_data['schedule'][$idx - 1];
                                                        } elseif (isset($scheduled_reading['virtual_previous_reading'])) {
                                                            // For the first reading after a starting verse, use the virtual previous reading
                                                            $previous_reading_for_this = array('reading' => $scheduled_reading['virtual_previous_reading']);
                                                        }
                                                        break;
                                                    }
                                                }
                                                
                                                $is_first_reading = ($reading_index == 0 && !isset($reading['virtual_previous_reading']));
                                                ?>
                                                <strong><?= formatReadingRange($reading, $previous_reading_for_this, $plan_data['parameters']['scheduling_method'], $is_first_reading) ?></strong>
                                                <br>
                                                <small class="text-muted">
                                                    <?= formatReadingInfo($reading, $plan_data['parameters']['scheduling_method']) ?>
                                                </small>
                                            </div>
                                            <div class="col-md-4 text-end">
                                                <?php
                                                // Get the scripture URL for this reading
                                                $scripture_ref_recent = getReadingStartReference($reading, $previous_reading_for_this, $plan_data['parameters']['scheduling_method'], $is_first_reading);
                                                $scripture_url_recent = generateScriptureUrl($scripture_ref_recent);
                                                ?>
                                                <?php if ($scripture_url_recent): ?>
                                                    <a href="<?= $scripture_url_recent ?>" target="_blank" class="btn btn-outline-secondary btn-sm me-2">
                                                        &#128214;
                                                    </a>
                                                <?php endif; ?>
                                                <form method="post" style="display: inline;">
                                                    <input type="hidden" name="date" value="<?= $reading['date'] ?>">
                                                    <input type="hidden" name="toggle_progress" value="1">
                                                    <?php if ($is_completed): ?>
                                                        <button type="submit" class="btn btn-success btn-sm">
                                                            &#10003; Completed
                                                        </button>
                                                    <?php else: ?>
                                                        <button type="submit" class="btn btn-outline-secondary btn-sm">
                                                            Mark as Read
                                                        </button>
                                                    <?php endif; ?>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4 mt-4 mt-lg-0">
                <div class="bookmark-box mb-4">
                    <h6 class="text-primary">&#128218; Bookmark This Plan</h6>
                    <p class="small mb-2">Save this link to access your reading plan anytime:</p>
                    <div class="input-group">
                        <input type="text" class="form-control form-control-sm" 
                               value="<?= (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . '/p/' . $plan_id ?>" 
                               readonly onclick="this.select()">
                        <button class="btn btn-outline-primary btn-sm" type="button" 
                                onclick="navigator.clipboard.writeText(this.previousElementSibling.value); this.textContent='Copied!'; setTimeout(() => this.textContent='Copy', 1000)">
                            Copy
                        </button>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-2">
                        <button class="btn btn-outline-primary btn-sm w-100" type="button" 
                                onclick="toggleQRCode()" id="qr-toggle-btn">
                            Open on Your Phone
                        </button>
                    </div>
                    <div id="qr-code-container" class="text-center mt-3" style="display: none;">
                        <img id="qr-code-img" src="" alt="QR Code" class="img-fluid" style="max-width: 150px;">
                        <p class="small text-muted mt-2">Scan with your phone's camera</p>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">&#128197; Calendar Subscription</h6>
                    </div>
                    <div class="card-body">
                        <p class="small mb-3 text-primary">Add your reading plan to your calendar app and get daily reminders:</p>
                        
                        <!-- Calendar subscription options -->
                        <div class="d-grid gap-2">
                            <a href="/schedule/calendar.php?id=<?= $plan_id ?>" 
                               class="btn btn-outline-primary btn-sm" 
                               download="scripture-reading-plan-<?= $plan_id ?>.ics">
                                Download Calendar File (.ics)
                            </a>
                            
                            <div class="dropdown">
                                <button class="btn btn-outline-primary btn-sm dropdown-toggle w-100" type="button" 
                                        id="calendarDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    Subscribe in Calendar App
                                </button>
                                <ul class="dropdown-menu w-100" aria-labelledby="calendarDropdown">
                                    <li><a class="dropdown-item" href="#" onclick="subscribeToCalendar('google')">
                                        Google Calendar</a></li>
                                    <li><a class="dropdown-item" href="#" onclick="subscribeToCalendar('outlook')">
                                        Outlook.com</a></li>
                                    <li><a class="dropdown-item" href="#" onclick="subscribeToCalendar('apple')">
                                        Apple Calendar</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="#" onclick="showCalendarInstructions()">
                                        Manual Instructions</a></li>
                                </ul>
                            </div>
                        </div>
                        
                        <div id="calendar-instructions" class="alert alert-info mt-3" style="display: none;">
                            <small>
                                <strong>Manual Setup:</strong><br>
                                1. Copy this URL: <br>
                                <code class="small"><?= (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . '/schedule/calendar.php?id=' . $plan_id ?></code><br>
                                2. In your calendar app, choose "Add Calendar by URL" or "Subscribe to Calendar"<br>
                                3. Paste the URL and save
                            </small>
                        </div>
                        
                        <p class="small text-muted mt-3 mb-0">
                            &#128276; Each reading will appear as an all-day event with a reminder the night before.
                        </p>
                    </div>
                </div>



                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">&#128203; Plan Details</h6>
                        <a href="/schedule/plan/<?= $plan_id ?>/edit" class="btn btn-outline-primary btn-sm">
                            Edit Plan
                        </a>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0 small">
                            <li><strong>Duration:</strong> <?= $plan_data['stats']['days_to_read'] ?> days</li>
                            <li><strong>Average:</strong> <?= number_format($plan_data['stats']['words_per_day']) ?> words/day</li>
                            <li><strong>Total words:</strong> <?= number_format($plan_data['stats']['total_words']) ?></li>
                            <li><strong>Method:</strong> <?= ucfirst($plan_data['parameters']['scheduling_method']) ?>-level</li>
                            <?php if (!empty($plan_data['parameters']['beginning_verse'])): ?>
                                <li><strong>Starting at:</strong> <?= htmlspecialchars($plan_data['parameters']['beginning_verse']) ?></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Theme toggle functionality
        const themeToggle = document.getElementById('theme-toggle');
        const body = document.body;
        
        // Load saved theme or default to dark
        const savedTheme = localStorage.getItem('theme') || 'dark';
        if (savedTheme === 'light') {
            body.classList.add('light-mode');
        }
        
        // Toggle theme
        themeToggle.addEventListener('click', () => {
            body.classList.toggle('light-mode');
            const newTheme = body.classList.contains('light-mode') ? 'light' : 'dark';
            localStorage.setItem('theme', newTheme);
        });

        // QR Code toggle functionality
        function toggleQRCode() {
            const container = document.getElementById('qr-code-container');
            const img = document.getElementById('qr-code-img');
            const btn = document.getElementById('qr-toggle-btn');
            
            if (container.style.display === 'none') {
                // Show QR code
                const url = encodeURIComponent('<?= (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . '/p/' . $plan_id ?>');
                
                // Get theme colors
                const isLightMode = document.body.classList.contains('light-mode');
                const qrColor = '00d4aa'; // Bright green accent
                const bgColor = isLightMode ? 'ffffff' : '1e293b'; // Card background color
                
                const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${url}&color=${qrColor}&bgcolor=${bgColor}`;
                
                img.src = qrUrl;
                container.style.display = 'block';
                btn.innerHTML = '&#128241; Hide QR Code';
            } else {
                // Hide QR code
                container.style.display = 'none';
                btn.innerHTML = '&#128241; Open on your phone';
            }
        }

        // Calendar subscription functionality
        function subscribeToCalendar(provider) {
            const calendarUrl = '<?= (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . '/schedule/calendar.php?id=' . $plan_id ?>';
            const encodedUrl = encodeURIComponent(calendarUrl);
            
            let subscriptionUrl = '';
            
            switch(provider) {
                case 'google':
                    // Google Calendar subscription - use webcal protocol
                    subscriptionUrl = 'https://calendar.google.com/calendar/render?cid=' + encodedUrl;
                    break;
                case 'outlook':
                    // Outlook.com subscription
                    subscriptionUrl = 'https://outlook.live.com/calendar/0/addfromweb/?url=' + encodedUrl + '&name=Scripture%20Reading%20Plan';
                    break;
                case 'apple':
                    // Apple Calendar - use webcal protocol
                    subscriptionUrl = calendarUrl.replace('http://', 'webcal://').replace('https://', 'webcal://');
                    break;
            }
            
            if (subscriptionUrl) {
                window.open(subscriptionUrl, '_blank');
            }
        }

        function showCalendarInstructions() {
            const instructions = document.getElementById('calendar-instructions');
            if (instructions.style.display === 'none') {
                instructions.style.display = 'block';
            } else {
                instructions.style.display = 'none';
            }
        }
    </script>
</body>
</html> 