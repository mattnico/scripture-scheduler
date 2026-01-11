<?php

// Uncomment these lines for debugging if needed
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

/*********************************
* Create master functions.
* *******************************/

function create_plan_directory() {
    $plans_dir = 'plans';
    if (!is_dir($plans_dir)) {
        mkdir($plans_dir, 0755, true);
    }
    return $plans_dir;
}

function generate_plan_id($length = 10) {
    $characters = 'abcdefghijklmnopqrstuvwxyz0123456789';
    $plan_id = '';
    for ($i = 0; $i < $length; $i++) {
        $plan_id .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $plan_id;
}

function save_plan($plan_data) {
    $plans_dir = create_plan_directory();
    
    // Generate unique ID
    do {
        $plan_id = generate_plan_id();
        $plan_file = $plans_dir . '/' . $plan_id . '.json';
    } while (file_exists($plan_file));
    
    // Add ID to plan data
    $plan_data['id'] = $plan_id;
    $plan_data['created'] = date('Y-m-d H:i:s');
    
    // Save to file
    $json_data = json_encode($plan_data, JSON_PRETTY_PRINT);
    if (file_put_contents($plan_file, $json_data)) {
        return $plan_id;
    }
    
    return false;
}

if (!function_exists('load_csv_data')) {
function load_csv_data() {
    $csv_file = dirname(__FILE__) . "/data/lds-scriptures.csv";
    
    if (!file_exists($csv_file)) {
        die('Error: CSV file not found at ' . $csv_file);
    }
    
    $data = array();
    if (($handle = fopen($csv_file, "r")) !== FALSE) {
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
            
            $record['volume_id'] = isset($volume_map[$record['volume_title']]) ? $volume_map[$record['volume_title']] : 1;
            
            // Add missing fields for compatibility
            $verse_parts = explode(' ', $record['verse_title']);
            $record['book_title'] = $verse_parts[0]; // Extract book name
            $record['verse_short_title'] = $record['verse_title']; // Use same as verse_title for now
            
            $data[] = $record;
        }
        fclose($handle);
    }
    
    return $data;
}
}

if (!function_exists('load_chapter_data')) {
function load_chapter_data() {
    $csv_file = dirname(__FILE__) . "/data/lds-scriptures-chapters.csv";
    
    if (!file_exists($csv_file)) {
        die('Error: Chapter CSV file not found at ' . $csv_file . '. Please run generate_chapter_data.php first.');
    }
    
    $data = array();
    if (($handle = fopen($csv_file, "r")) !== FALSE) {
        $header = fgetcsv($handle, 1000, ","); // Read header row
        while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
            // Skip empty rows or rows with wrong number of columns
            if (empty($row) || count($row) !== count($header)) {
                continue;
            }
            
            $record = array_combine($header, $row);
            
            // Ensure we have the required fields
            if (!isset($record['volume_title']) || !isset($record['verse_title'])) {
                continue; // Skip malformed records
            }
            
            // Map volume titles to volume IDs for compatibility
            $volume_map = array(
                'Old Testament' => 1,
                'New Testament' => 2,
                'Book of Mormon' => 3,
                'Doctrine and Covenants' => 4,
                'Pearl of Great Price' => 5
            );
            
            $record['volume_id'] = isset($volume_map[$record['volume_title']]) ? $volume_map[$record['volume_title']] : 1;
            
            // Debug: Check the record after volume_id mapping
            if (isset($record['verse_title']) && $record['verse_title'] === '2 Nephi 1') {
                echo "<p><strong>After volume mapping:</strong></p>";
                echo "<p>volume_title: '" . $record['volume_title'] . "'</p>";
                echo "<p>volume_id: " . $record['volume_id'] . "</p>";
                echo "<p>Volume map lookup result: " . (isset($volume_map[$record['volume_title']]) ? $volume_map[$record['volume_title']] : 'NOT FOUND') . "</p>";
            }
            
            // Add missing fields for compatibility
            $verse_parts = explode(' ', $record['verse_title']);
            $record['book_title'] = $verse_parts[0]; // Extract book name
            $record['verse_short_title'] = $record['verse_title']; // Use same as verse_title for now
            
            // Debug: Final record
            if (isset($record['verse_title']) && $record['verse_title'] === '2 Nephi 1') {
                echo "<p><strong>Final processed record:</strong></p>";
                echo "<pre>" . print_r($record, true) . "</pre>";
                echo "<hr>";
            }
            
            $data[] = $record;
        }
        fclose($handle);
    }
    
    return $data;
}
}

function query_csv($criteria = array()) {
    static $verse_data = null;
    static $chapter_data = null;
    
    // Determine if we need chapter or verse data
    $use_chapters = isset($criteria['scheduling_method']) && $criteria['scheduling_method'] === 'chapter';
    
    // Load appropriate data once and cache it
    if ($use_chapters) {
        if ($chapter_data === null) {
            $chapter_data = load_chapter_data();
        }
        $data = $chapter_data;
    } else {
        if ($verse_data === null) {
            $verse_data = load_csv_data();
        }
        $data = $verse_data;
    }
    
    $filtered_data = $data;
    
    // Apply filters based on criteria
    if (!empty($criteria)) {
        $filtered_data = array();
        foreach ($data as $record) {
            $include = true;
            
            // Filter by volumes
            if (isset($criteria['volumes']) && !empty($criteria['volumes'])) {
                if (!isset($record['volume_id']) || !in_array($record['volume_id'], $criteria['volumes'])) {
                    $include = false;
                }
            }
            
            // Filter by starting verse
            if (isset($criteria['min_verse_id']) && $record['verse_id'] < $criteria['min_verse_id']) {
                $include = false;
            }
            
            // Filter by verse title (for verse lookup)
            if (isset($criteria['verse_title']) && $record['verse_title'] != $criteria['verse_title']) {
                $include = false;
            }
            
            if ($include) {
                $filtered_data[] = $record;
            }
        }
    }
    
    return $filtered_data;
}

function days_to_read($start_date, $end_date, $inclusive = true){
	// Calculate the number of days in the range
	$days_to_read = ($end_date - $start_date) / (24*60*60);

	//If you want the start date to be part of the range, set inclusive to TRUE
	if($inclusive)
		$days_to_read++;

	return $days_to_read;
}

function aggregate_by_chapters($verse_data) {
	$chapters = array();
	
	foreach($verse_data as $verse) {
		// Parse chapter from verse_title (e.g., "Genesis 1:1" -> "Genesis 1")
		$verse_title = $verse['verse_title'];
		$colon_pos = strrpos($verse_title, ':');
		if ($colon_pos !== false) {
			$chapter_title = substr($verse_title, 0, $colon_pos);
		} else {
			// Handle special cases where there might not be a colon (single verse chapters)
			$chapter_title = $verse_title;
		}
		
		// If this chapter doesn't exist yet, create it
		if (!isset($chapters[$chapter_title])) {
			$chapters[$chapter_title] = array(
				'verse_id' => $verse['verse_id'], // Use the first verse ID of the chapter
				'volume_title' => $verse['volume_title'],
				'verse_title' => $chapter_title,
				'volume_id' => isset($verse['volume_id']) ? $verse['volume_id'] : 1,
				'book_title' => $verse['book_title'],
				'verse_short_title' => $chapter_title,
				'word_count' => 0,
				'verse_count' => 0 // Track actual verse count
			);
		}
		
		// Add this verse's word count to the chapter total
		$chapters[$chapter_title]['word_count'] += intval($verse['word_count']);
		
		// Increment the actual verse count
		$chapters[$chapter_title]['verse_count']++;
		
		// Update the ending verse ID for this chapter (so we know where the chapter ends)
		$chapters[$chapter_title]['end_verse_id'] = $verse['verse_id'];
	}
	
	// Convert associative array to indexed array and sort by verse_id
	$chapter_array = array_values($chapters);
	usort($chapter_array, function($a, $b) {
		return intval($a['verse_id']) - intval($b['verse_id']);
	});
	
	return $chapter_array;
}

/*********************************
* Get the user inputs
* *******************************/
	//var_dump($_REQUEST); die();

	// Check for required parameters
	if (!isset($_REQUEST['start_date']) || !isset($_REQUEST['end_date']) || !isset($_REQUEST['format'])) {
		die('Error: Missing required parameters (start_date, end_date, format)');
	}

	$start_date = strtotime($_REQUEST['start_date']);
	$end_date = strtotime($_REQUEST['end_date']);
	
	// Validate dates
	if ($start_date === false || $end_date === false) {
		die('Error: Invalid date format');
	}
	
	$format = $_REQUEST['format']; /// 'table' or 'calendar' or 'calendar_plain' or 'csv'
	$scheduling_method = (isset($_REQUEST['scheduling_method']) && $_REQUEST['scheduling_method'] === 'chapter') ? 'chapter' : 'verse';
	$show_word_count = (isset($_REQUEST['show_word_count']) && $_REQUEST['show_word_count'] == 'on');
	$beginning_verse = (isset($_REQUEST['beginning_verse']) && strlen($_REQUEST['beginning_verse']) > 0) ? $_REQUEST['beginning_verse'] : false;

	//var_dump($beginning_verse); die();

	$volumes = array();
		if(isset($_REQUEST['ot']) && $_REQUEST['ot'] == 'on'){
			$volumes[] = 1;
		}

		if(isset($_REQUEST['nt']) && $_REQUEST['nt'] == 'on'){
			$volumes[] = 2;
		}

		if(isset($_REQUEST['bom']) && $_REQUEST['bom'] == 'on'){
			$volumes[] = 3;
		}

		if(isset($_REQUEST['dc']) && $_REQUEST['dc'] == 'on'){
			$volumes[] = 4;
		}

		if(isset($_REQUEST['pgp']) && $_REQUEST['pgp'] == 'on'){
			$volumes[] = 5;
		}
	
	$volumes_to_read = (count($volumes) > 0) ? join(", ", $volumes) : false;
	$books_to_read = false;

/*********************************
* Calculate the number of words to
* be read. 
* *******************************/

// Get the beginning verse from the user
	

	// Check if there even is a beginning verse and whether we have a 
	// verse ID or a verse reference. If we have a reference, copy it into the 
	// _text variable for printing later and grab the verse ID. If not, tell the
	// blurb to ignore the beginning verse by setting it to false. 
	if($beginning_verse !== false && !is_numeric($beginning_verse)){
		$beginning_verse_text = $beginning_verse;
		// Use the appropriate data source for validation (verse or chapter)
		$validation_criteria = array(
			'verse_title' => $beginning_verse_text,
			'scheduling_method' => $scheduling_method
		);
		
		// For validation, we should check if verse exists in ANY volume first
		$all_data_criteria = array('scheduling_method' => $scheduling_method);
		$all_data = query_csv($all_data_criteria);
		$verse_exists_anywhere = false;
		foreach ($all_data as $record) {
			if ($record['verse_title'] === $beginning_verse_text) {
				$verse_exists_anywhere = true;
				break;
			}
		}
		
		if (!$verse_exists_anywhere) {
			die('Error: Beginning verse reference "' . $beginning_verse_text . '" not found in scripture database. Please check the reference format.');
		}
		
		// Now check if it exists in the selected volumes
		if (!empty($volumes)) {
			$validation_criteria['volumes'] = $volumes;
		}
		        $result = query_csv($validation_criteria);
        if (empty($result) || !isset($result[0]['verse_id'])) {
            $available_volumes = array(1 => 'Old Testament', 2 => 'New Testament', 3 => 'Book of Mormon', 4 => 'Doctrine and Covenants', 5 => 'Pearl of Great Price');
            $selected_volume_names = array();
            foreach ($volumes as $vol_id) {
                if (isset($available_volumes[$vol_id])) {
                    $selected_volume_names[] = $available_volumes[$vol_id];
                }
            }
            die('Error: Beginning verse reference "' . $beginning_verse_text . '" not found in selected volumes (' . implode(', ', $selected_volume_names) . '). Please check your volume selection.');
        }
		$beginning_verse = $result[0]['verse_id'];
	}else if($beginning_verse === false){
		$beginning_verse_text = false;
	}else{
		// If beginning_verse is numeric, we don't have text to display
		$beginning_verse_text = false;
	}

// Get the books/volumes to be read from the user
	


// Note: These functions are not currently implemented
// function num_words_to_read($beginning_verse = false, $vol_books){
// 	// If there is a starting verse, eliminate all books and chapters before that verse
// }	

// function get_verse_id_from_ref($reference){
// }






// Get the total words in the selected books/volumes from the CSV
$criteria = array();

if($volumes_to_read !== false) {
	$volume_ids = explode(", ", $volumes_to_read);
	$criteria['volumes'] = array_map('intval', $volume_ids);
}

if($beginning_verse !== false) {
	$criteria['min_verse_id'] = $beginning_verse;
}

// Add scheduling method to criteria for proper data loading
$criteria['scheduling_method'] = $scheduling_method;

$filtered_data = query_csv($criteria);

// Calculate total words
$total_words = 0;
foreach($filtered_data as $record) {
	$total_words += intval($record['word_count']);
}

/*********************************
* Calculate the number of words
* that need to be read on a daily
* basis.
* *******************************/

// Divide number of words by number of days, rounded to the nearest integer
$days_to_read =  days_to_read($start_date, $end_date);

if ($days_to_read <= 0) {
	die('Error: Invalid date range - end date must be after start date');
}

if ($total_words <= 0) {
	die('Error: No scripture content found with the selected criteria');
}

$words_per_day = ceil($total_words / $days_to_read);
$original_words_per_day = $words_per_day; // Save the original for stats

$blurb = '<p class="noprint">';
if($beginning_verse_text !== false) $blurb .= 'Beginning in '.$beginning_verse_text.', y';
	else $blurb .='Y';
$blurb .= 'ou will read an average of '.number_format($words_per_day, 0).' words per day for '.$days_to_read.' days.</p>';

/*********************************
* Generate a reading schedule
* based on the number of words
* that should be read in a day.
* *******************************/

// For chapter-based scheduling, we already have the correct chapter data from query_csv()
// For verse-based scheduling, use the verse data as-is
$scriptures = $filtered_data;

$running_total = 0;
$verse_count = 0;
$chapter_count = 0; // Track chapters separately for chapter-based scheduling
$schedule = array();
$months = array();
$day = 0;
$remaining_words = $total_words;

// Handle starting verse by creating a virtual "previous day" reading
$virtual_previous_reading = null;
if ($beginning_verse !== false && $beginning_verse_text) {
    // Parse the starting verse to determine what the "previous" reading should be
    if ($scheduling_method === 'chapter') {
        // For chapter-based, previous reading should end at the chapter before
        // e.g., if starting at D&C 60:1, previous should be "D&C 59"
        if (preg_match('/^(.+?)\s+(\d+)/', $beginning_verse_text, $matches)) {
            $book_name = trim($matches[1]);
            $chapter_num = intval($matches[2]);
            if ($chapter_num > 1) {
                $virtual_previous_reading = $book_name . ' ' . ($chapter_num - 1);
            }
        }
    } else {
        // For verse-based, previous reading should end at the verse before
        // e.g., if starting at D&C 60:1, we need to find the last verse of D&C 59
        if (preg_match('/^(.+?)\s+(\d+):(\d+)/', $beginning_verse_text, $matches)) {
            $book_name = trim($matches[1]);
            $chapter_num = intval($matches[2]);
            $verse_num = intval($matches[3]);
            
            if ($verse_num > 1) {
                // Same chapter, previous verse
                $virtual_previous_reading = $book_name . ' ' . $chapter_num . ':' . ($verse_num - 1);
            } else if ($chapter_num > 1) {
                // Need to find the last verse of the previous chapter
                // For now, we'll use a reasonable default - this could be enhanced with actual data
                $virtual_previous_reading = $book_name . ' ' . ($chapter_num - 1);
            }
        }
    }
}

//var_dump($scriptures); die();

foreach($scriptures as $k => $row){
	$last_total = $running_total;
	$running_total += $row['word_count'];

	if ($scheduling_method === 'chapter') {
		$chapter_count++;
		$verse_count += $row['verse_count']; // Add actual verse count from this chapter
	} else {
		$verse_count++;
	}
	
	if($running_total >= $words_per_day ){
		
		$todays_datetime = strtotime(date('Y-m-d', $start_date)." +$day days");

		$schedule[$day]["date"] = date('D, M j', $todays_datetime);
		$schedule[$day]["year"] = date('Y', $todays_datetime);
		$schedule[$day]["month"] = date('n', $todays_datetime);
		$schedule[$day]["month_text"] = date('F', $todays_datetime);
		$schedule[$day]["day_of_week"] = date('N', $todays_datetime);
		$schedule[$day]["day_of_month"] = date('j', $todays_datetime);

		if(!isset($months[$schedule[$day]["year"]][$schedule[$day]["month"]])){
			if($day == 0){
				$months[$schedule[$day]["year"]][$schedule[$day]["month"]]["min"] = intval($scriptures[0]['verse_id']);
			}else if($day !=0){
				$months[$schedule[$day]["year"]][$schedule[$day]["month"]]["min"] = intval($months[$schedule[$day-1]["year"]][$schedule[$day-1]["month"]]["max"]+1);
			}
		}else{
			// For chapters, use end_verse_id if available, otherwise use verse_id
			$verse_id_to_use = ($scheduling_method === 'chapter' && isset($row['end_verse_id'])) ? $row['end_verse_id'] : $row['verse_id'];
			$months[$schedule[$day]["year"]][$schedule[$day]["month"]]["max"] = intval($verse_id_to_use);
		}

		$diff_current = ($running_total / $words_per_day) - 1;
		$diff_last = 1 - ($last_total / $words_per_day);
		// Check to see if we are closer to the average we need by exceeding 
		// the total by a verse or going back a verse
		if($diff_current < $diff_last){
			if(strlen($row['book_title']) <= 10){
				$schedule[$day]["verse"] = $row['verse_title'];
			}else{
				$schedule[$day]["verse"] = $row['verse_short_title'];
			}
			$schedule[$day]["word_count"] = $running_total;
			if ($scheduling_method === 'chapter') {
				$schedule[$day]["chapter_count"] = $chapter_count;
				$schedule[$day]["verse_count"] = $verse_count;
			} else {
				$schedule[$day]["verse_count"] = $verse_count;
			}
		}else{
			$running_total = $last_total;
			if(strlen($scriptures[$k-1]['book_title']) <= 10){
				$schedule[$day]["verse"] = $scriptures[$k-1]['verse_title'];
			}else{
				$schedule[$day]["verse"] = $scriptures[$k-1]['verse_short_title'];
			}
			//$schedule[$day]["verse"] = $scriptures[$k-1]['verse_title'];
			$schedule[$day]["word_count"] = $running_total;
			if ($scheduling_method === 'chapter') {
				$schedule[$day]["chapter_count"] = $chapter_count - 1;
				$schedule[$day]["verse_count"] = $verse_count - $row['verse_count']; // Subtract this chapter's verses
			} else {
				$schedule[$day]["verse_count"] = $verse_count - 1;
			}
		}

		// Store the virtual previous reading for the first day if we have one
		if ($day == 0 && $virtual_previous_reading) {
			$schedule[$day]["virtual_previous_reading"] = $virtual_previous_reading;
		}

		$day++;
		$remaining_words -= $running_total;

		$words_per_day = ($days_to_read !== $day) ? $remaining_words/($days_to_read-$day) : 1;
		$running_total = ($diff_current < $diff_last) ? 0 : $row['word_count'];
		
		// Reset counters for next day
		if ($scheduling_method === 'chapter') {
			$chapter_count = ($diff_current < $diff_last) ? 0 : 1;
			$verse_count = ($diff_current < $diff_last) ? 0 : $row['verse_count'];
		} else {
			$verse_count = ($diff_current < $diff_last) ? 0 : 1; // Fix: should be 1, not 0 for verse-based
		}
	}
}

// Handle the final day if there are remaining verses
if($running_total > 0 && $day < $days_to_read) {
	$todays_datetime = strtotime(date('Y-m-d', $start_date)." +$day days");
	
	$schedule[$day]["date"] = date('D, M j', $todays_datetime);
	$schedule[$day]["year"] = date('Y', $todays_datetime);
	$schedule[$day]["month"] = date('n', $todays_datetime);
	$schedule[$day]["month_text"] = date('F', $todays_datetime);
	$schedule[$day]["day_of_week"] = date('N', $todays_datetime);
	$schedule[$day]["day_of_month"] = date('j', $todays_datetime);
	
	// Use the last scripture entry
	$last_row = end($scriptures);
	if(strlen($last_row['book_title']) <= 10){
		$schedule[$day]["verse"] = $last_row['verse_title'];
	}else{
		$schedule[$day]["verse"] = $last_row['verse_short_title'];
	}
	$schedule[$day]["word_count"] = $running_total;
	if ($scheduling_method === 'chapter') {
		$schedule[$day]["chapter_count"] = $chapter_count;
		$schedule[$day]["verse_count"] = $verse_count;
	} else {
		$schedule[$day]["verse_count"] = $verse_count;
	}
}

//var_dump($months);
// It may be necessary to manually create the last day of the schedule by populating the last verse of the last book to be read

/*********************************
* Create and Save Reading Plan (if requested)
* *******************************/

// Check if we should create a persistent plan (new parameter)
if (isset($_REQUEST['create_plan']) && $_REQUEST['create_plan'] == 'on') {
    // Debug: Add some validation
    if (empty($schedule)) {
        die('Error: No schedule generated - this indicates a problem with the plan generation logic');
    }
    
    // Prepare plan data
    $plan_data = array(
        'parameters' => array(
            'start_date' => $_REQUEST['start_date'],
            'end_date' => $_REQUEST['end_date'],
            'scheduling_method' => $scheduling_method,
            'beginning_verse' => $beginning_verse_text ? $beginning_verse_text : '',
            'show_word_count' => $show_word_count,
            'volumes' => $volumes
        ),
        'schedule' => array(),
        'progress' => array(),
        'stats' => array(
            'total_words' => $total_words,
            'words_per_day' => $original_words_per_day,
            'days_to_read' => $days_to_read
        )
    );
    
    // Convert schedule to a more plan-friendly format
    foreach ($schedule as $day) {
        if (!empty($day['verse'])) { // Only include days with actual readings
            $date_key = $day['year'] . '-' . str_pad($day['month'], 2, '0', STR_PAD_LEFT) . '-' . str_pad($day['day_of_month'], 2, '0', STR_PAD_LEFT);
            $reading_data = array(
                'date' => $date_key,
                'reading' => $day['verse'],
                'word_count' => intval($day['word_count']), // Ensure it's an integer
                'verse_count' => intval($day['verse_count']) // Ensure it's an integer
            );
            
            // Add chapter count for chapter-based schedules
            if ($scheduling_method === 'chapter' && isset($day['chapter_count'])) {
                $reading_data['chapter_count'] = intval($day['chapter_count']);
            }
            
            // Add virtual previous reading for the first day if it exists
            if (isset($day['virtual_previous_reading'])) {
                $reading_data['virtual_previous_reading'] = $day['virtual_previous_reading'];
            }
            
            $plan_data['schedule'][] = $reading_data;
            // Initialize progress as not completed
            $plan_data['progress'][$date_key] = false;
        }
    }
    
    // Save the plan
    $plan_id = save_plan($plan_data);
    
    if ($plan_id) {
        // Redirect to plan viewer using clean URL
        header('Location: /schedule/plan/' . $plan_id);
        exit;
    } else {
        // If saving failed, continue with normal output
        // You could add error handling here
    }
}

/*********************************
* Display the reading schedule in
* a formatted table. 
* *******************************/

// For CSV format, output directly. For other formats, capture HTML and convert to PDF
if($format == 'csv'){
	header('Content-Type: application/csv');
	header('Content-Disposition: attachment; filename="SmallSimpleScriptureSchedule.csv"');
	$ending_label = ($scheduling_method === 'chapter') ? 'ending_chapter' : 'ending_verse';
	$count_label = ($scheduling_method === 'chapter') ? 'chapter_count' : 'verse_count';
	echo "date,{$ending_label},{$count_label},word_count\n";
	foreach($schedule as $k => $day){
		echo "{$day['month']}/{$day['day_of_month']}/{$day['year']},";
		echo "\"{$day['verse']}\",";
		echo "{$day['verse_count']},";
		echo "{$day['word_count']}\n";
	}
	exit; // Stop execution for CSV
}

// For non-CSV formats, start output buffering to capture HTML
ob_start();

echo '<html>
		<head>
			<link rel="stylesheet" href="https://unpkg.com/purecss@1.0.0/build/base-min.css">
			<link rel="stylesheet" href="https://unpkg.com/purecss@1.0.0/build/pure-min.css" integrity="sha384-nn4HPE8lTHyVtfCBi5yW9d20FjT8BJwUXyWZT9InLYax14RDjBj46LmSztkmNP9w" crossorigin="anonymous">
			<!--[if lte IE 8]>
				<link rel="stylesheet" href="https://unpkg.com/purecss@1.0.0/build/grids-responsive-old-ie-min.css">
			<![endif]-->
			<!--[if gt IE 8]><!-->
				<link rel="stylesheet" href="https://unpkg.com/purecss@1.0.0/build/grids-responsive-min.css">
			<!--<![endif]-->
			<style>
				/* PDF-optimized styles */
				@page {
					size: Letter landscape;
					margin: 0.25in;
				}

				body {
					font-family: Arial, sans-serif;
					font-size: 10px;
					margin: 0;
					padding: 0;
				}

				h1 {
					font-size: 16px;
					margin-bottom: 10px;
				}

				h2 {
					font-size: 14px;
					margin: 0;
					padding: 0;
				}

				/* Calendar month container - optimized for Letter Landscape */
				.month-container {
					page-break-inside: avoid !important;
					page-break-after: avoid;
					height: 6.0in; /* Significantly reduced to ensure it fits */
					width: 100%;
					overflow: hidden;
					margin: 0;
					padding: 0;

				}

				/* Only subsequent months without hero images get page breaks */
				.month-container.no-hero:not(:first-child) {
					/* This is now handled by .page-break-before */
				}

				.page-break-before {
					page-break-before: always;
				}

				.page-break-after {
					page-break-after: always;
				}

				/* Calendar table styling */
				table.calendar {
					width: 100%;
					table-layout: fixed;
					border-collapse: collapse;
					page-break-inside: avoid !important;
					page-break-before: avoid;
					page-break-after: avoid;
				}

				table.calendar tr {
					page-break-inside: avoid !important;
				}

				table.calendar th {
					background-color: #f0f0f0;
					padding: 5px;
					text-align: center;
					font-weight: bold;
					border: 1px solid #ccc;
				}

				th.calendar-title {
					background: white !important;
					border-left: none !important;
					border-right: none !important;
					border-top: none !important;
					padding-bottom: 10px;
					font-size: 14px;
				}

				table.calendar td {
					border: 1px solid #ccc;
					padding: 1px;
					vertical-align: top;
					width: 14.28%; /* 100% / 7 days */
					position: relative;
					height: 0.88 in; /* Fixed height for 6-week month compatibility */
				}

				.innerwrapper {
					width: 100%;
					height: 100%;
					position: relative;
					padding: 2px;
				}

				td span.date {
					position: absolute;
					font-weight: bold;
					right: 7px;
					top: 1px;
					font-size: 12px; /* Larger font for better readability */
				}

				span.scripture { 
					position: absolute;	
					font-size: 10px; /* Larger font for better readability */
					text-align: center;
					width: 95%;
					bottom: 1px;
					left: 2.5%;
					line-height: 11px; /* Adjusted line height */
				}

				span.scripture .word-count {
					font-size: 8px;
					font-style: italic;
					color: #666;
				}

				/* Table format styling */
				table.pure-table {
					width: 100%;
					border-collapse: collapse;
					page-break-inside: avoid;
				}

				table.pure-table th,
				table.pure-table td {
					border: 1px solid #ccc;
					padding: 4px;
					text-align: left;
				}

				table.pure-table th {
					background-color: #f0f0f0;
					font-weight: bold;
				}

				/* Hero image styling - optimized for Letter Landscape */
				.hero { 
					width: 100%;
					height: 6.0in; /* Significantly reduced to ensure it fits */
					page-break-after: always;
					text-align: center;
					margin: 0;
					padding: 0;
					display: flex;
					align-items: center;
					justify-content: center;
					box-sizing: border-box;

				}

				.hero img {
					width: auto; /* Preserve aspect ratio */
					max-width: 95%;
					max-height: 95%;
					object-fit: contain;
					margin: 0;
					padding: 0;

				}

				/* Remove noprint elements for PDF */
				.noprint {
					display: none;
				}
				
			</style>
			<meta name="viewport" content="width=device-width, initial-scale=1">
		</head>
		<body>';

// Remove all extra content for clean PDF layout
// No blurb, no wrapper divs - just the calendar content

if($format == 'table'){
		echo '<table class="pure-table pure-table-striped">';
		$read_to_label = ($scheduling_method === 'chapter') ? 'Read to Chapter' : 'Read to Verse';
		$count_label = ($scheduling_method === 'chapter') ? 'Number of Chapters' : 'Number of Verses';
		echo '<thead>
				<tr>
					<th>Date</th>
					<th>' . $read_to_label . '</th>
					<th>' . $count_label . '</th>
					<th>Number of Words</th>
				</tr>
			   </thead>
			   <tbody>';
	foreach($schedule as $k => $tr){
	//if($k > 0 && $k % 3 == 0) echo "</tr>";
	//if($k % 3 == 0)	echo "<tr>";
		echo "<tr>";
			echo "<td>{$tr['date']}</td>";
			echo "<td>{$tr['verse']}</td>";
			echo "<td>{$tr['verse_count']}</td>";
			echo "<td>".number_format($tr['word_count'],0)."</td>";
		echo "</tr>";
	}

	echo "</div></div></tbody></table>";
}else if($format == 'calendar' || $format == 'calendar_plain'){
	$last_month = false;
	$is_first_page_element = true;

	// If the first date of the schedule is not the first of a month, add the
	// days of that month leading up to the first day as empty entries so as
	// to produce a full month's calendar
	if($schedule[0]['day_of_month'] > 1){
		$s = $schedule[0];
		
		for($i = $s['day_of_month']-1; $i > 0; $i--){
			$diff = $s['day_of_month'] - $i;
			$todays_datetime = strtotime("{$s['month_text']} {$s['day_of_month']}, {$s['year']} -$diff days");
			//var_dump($todays_datetime);
			$arr = 	array(	"date" => date('D, M j', $todays_datetime),
							"year" => date('Y', $todays_datetime),
							"month" => date('n', $todays_datetime),
							"month_text" => date('F', $todays_datetime),
							"day_of_week" => date('N', $todays_datetime),
							"day_of_month" => date('j', $todays_datetime),
							"verse" => "",
							"word_count" => 0,
							"verse_count" => 0
					);
			array_unshift($schedule, $arr);
		}
		//var_dump($schedule);
	}

	foreach($schedule as $k => $day){
		// Handle the first month, or transitioning between months
		if($k == 0 || $day['month_text'] != $last_month){
			// If we are closing a month and opening a new one, generate
			// empty calendar days for the remaining days of the week.
			if($k > 0){ 
				for($i = $schedule[$k-1]['day_of_week']; $i <7; $i++){
					echo '<td></td>';
				}
				echo '</tr></tbody></table></div>'; // Close table and month container
			}

			// Initialize or re-set the month-tracking variable
			$last_month = $day['month_text'];

			$has_hero_image = false;
			
			if($format !== 'calendar_plain'){
				// Insert an image that we have available  
				if(is_dir('cal_images')){
					$directory = 'cal_images';
					$img_files = array_diff(scandir($directory), array('..', '.'));
					
					foreach($img_files as $i => $filename){
						if(intval(substr($filename, 0, strlen($filename)-4)) > $months[$day['year']][$day['month']]['max']
							|| intval(substr($filename, 0, strlen($filename)-4)) < $months[$day['year']][$day['month']]['min']){
							unset($img_files[$i]);
						}
					}

					if(!empty($img_files)){
						$selected_image = $img_files[array_rand($img_files)];
						$image_path = $directory . '/' . $selected_image;
						
						// Convert image to base64 for wkhtmltopdf compatibility
						if(file_exists($image_path)) {
							$image_data = file_get_contents($image_path);
							$image_info = getimagesize($image_path);
							$mime_type = $image_info['mime'];
							$base64_image = base64_encode($image_data);
							
							echo '<div class="hero">';
							echo '<img src="data:' . $mime_type . ';base64,' . $base64_image . '" />';
							echo '</div>';
							
							$has_hero_image = true;
							$is_first_page_element = false;
						}
					}
				}
			}
			
			// Start a new month container with appropriate class
			$month_class = 'month-container';
			// Only add page-break-before to month container if there was no hero image and this isn't the first page element
			if (!$is_first_page_element && !$has_hero_image) {
				$month_class .= ' page-break-before';
			}
			if (!$has_hero_image) {
				$month_class .= ' no-hero';
			}
			echo '<div class="' . $month_class . '">';
			$is_first_page_element = false;

			echo '<h2 style="text-align: center; margin-bottom: 10px;">'.$day['month_text'].' '.$day['year'].'</h2>';
			
			// Start the calendar table
			echo '<table class="calendar">';
			echo '<thead>
					<tr>
						<th>Monday</th>
						<th>Tuesday</th>
						<th>Wednesday</th>
						<th>Thursday</th>
						<th>Friday</th>
						<th>Saturday</th>
						<th>Sunday</th>
					</tr>
				</thead>
				<tbody>
					<tr>';


			// Insert empty cells for the days of the first week that are not
			// in the new month
			for($i = 1; $i < $day['day_of_week']; $i++){
				echo "<td></td>";
			}
		}

		// If this is a Monday, start a new row
		if(	$day['day_of_week']  == 1 && 
			$day['month_text']   == $last_month &&
			$day['day_of_month'] != 1)
			echo '<tr>';

		// Create the calendar entry
		echo '<td><div class="innerwrapper">';
		echo '<span class="date">'.$day['day_of_month'].'</span><br/>';
		echo '<span class="spacer"></span><br/>';
		echo '<span class="scripture">'.$day['verse'];
		if ($show_word_count && $day['word_count'] > 0) {
			echo '<br/><span class="word-count">(' . number_format($day['word_count']) . ' words)</span>';
		}
		echo '</span>';
		echo '</div></td>';

		// If this is a Sunday, end the row
		if($day['day_of_week'] == 7) echo '</tr>';
	}

	// If the last day processed wasn't a Sunday, create the padding calendar
	// cells to finish off the week
	if($day['day_of_week'] != 7){
		for($i = $day['day_of_week']; $i < 7; $i++){
			echo "<td></td>";
		}
		echo "</tr>";
	}
	echo "</tbody></table></div>"; // Close table and month container
}

// Close HTML tags
echo "</body></html>";

// Get the HTML content
$html_content = ob_get_clean();

// Generate PDF using wkhtmltopdf
$temp_html = tempnam(sys_get_temp_dir(), 'scripture_schedule_') . '.html';
$temp_pdf = tempnam(sys_get_temp_dir(), 'scripture_schedule_') . '.pdf';

// Write HTML to temporary file
file_put_contents($temp_html, $html_content);

// Convert HTML to PDF
$wkhtmltopdf_path = '/usr/local/bin/wkhtmltopdf';
$command = escapeshellcmd($wkhtmltopdf_path) . ' --page-size Letter --orientation Landscape --margin-top 0.25in --margin-bottom 0.25in --margin-left 0.4in --margin-right 0.4in --disable-smart-shrinking --zoom 1.0 --print-media-type ' . escapeshellarg($temp_html) . ' ' . escapeshellarg($temp_pdf);

exec($command, $output, $return_var);

// Check if PDF was created successfully (ignore return code as wkhtmltopdf can return warnings)
if (!file_exists($temp_pdf) || filesize($temp_pdf) == 0) {
    // PDF generation failed, fall back to HTML
    header('Content-Type: text/html');
    echo $html_content;
    unlink($temp_html);
    if (file_exists($temp_pdf)) unlink($temp_pdf);
} else {
    // PDF generation successful
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="ScriptureSchedule_' . date('Y-m-d') . '.pdf"');
    header('Content-Length: ' . filesize($temp_pdf));
    
    // Output PDF content
    readfile($temp_pdf);
    
    // Clean up temporary files
    unlink($temp_html);
    unlink($temp_pdf);
}
?>