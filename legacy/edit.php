<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define CSV loading functions needed for calculateNextStartingVerse
function load_csv_data() {
    $csv_file = "data/lds-scriptures.csv";
    
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

function load_chapter_data() {
    $csv_file = "data/lds-scriptures-chapters.csv";
    
    if (!file_exists($csv_file)) {
        die('Error: Chapter CSV file not found at ' . $csv_file . '. Please run generate_chapter_data.php first.');
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

// Get plan ID from URL
$plan_id = '';
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $plan_id = $_GET['id'];
} else {
    die('Error: No plan ID specified');
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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
    
    // Determine the new starting verse
    $new_beginning_verse = '';
    if ($last_completed_reading) {
        // Calculate what comes after the last completed reading
        $new_beginning_verse = calculateNextStartingVerse($last_completed_reading['reading'], $_POST['scheduling_method']);
    } else {
        // No progress made, use the user's input or original beginning verse
        $new_beginning_verse = !empty($_POST['beginning_verse']) ? $_POST['beginning_verse'] : $plan_data['parameters']['beginning_verse'];
    }
    
    // Convert volume format to match calc.php expectations
    $volumes_mapping = array(
        'ot' => 'ot',
        'nt' => 'nt', 
        'bofm' => 'bom',
        'dc-testament' => 'dc',
        'pgp' => 'pgp'
    );
    
    // Prepare form data for plan regeneration using calc.php format
    $calc_params = array(
        'start_date' => $_POST['start_date'],
        'end_date' => $_POST['end_date'],
        'format' => 'table', // We'll override this later
        'scheduling_method' => $_POST['scheduling_method'],
        'beginning_verse' => $new_beginning_verse,
                         'show_word_count' => 'on', // Always on for interactive plans
        'create_plan' => 'on'
    );
    
    // Add volume checkboxes in calc.php format
    if (isset($_POST['volumes']) && is_array($_POST['volumes'])) {
        foreach ($_POST['volumes'] as $volume) {
            if (isset($volumes_mapping[$volume])) {
                $calc_params[$volumes_mapping[$volume]] = 'on';
            }
        }
    }
    
    // Set up environment for calc.php
    $_REQUEST = $calc_params;
    $_POST = $calc_params;
    $_GET = array();
    
    // Temporarily disable plan creation redirect in calc.php by unsetting create_plan
    $original_create_plan = $_REQUEST['create_plan'];
    unset($_REQUEST['create_plan']);
    unset($_POST['create_plan']);
    
    // Include calc.php to generate the schedule data
    ob_start();
    include 'calc.php';
    $calc_output = ob_get_contents();
    ob_end_clean();
    
    // Now we need to access the generated schedule data
    // The calc.php file should have generated $schedule array
    if (isset($schedule) && !empty($schedule)) {
        // Restore create_plan mode and generate plan data
        $_REQUEST['create_plan'] = $original_create_plan;
        $_POST['create_plan'] = $original_create_plan;
        
        // Create new plan data structure
        $new_plan_data = array(
            'parameters' => array(
                'start_date' => $_POST['start_date'],
                'end_date' => $_POST['end_date'],
                'scheduling_method' => $_POST['scheduling_method'],
                'beginning_verse' => $new_beginning_verse,
                'show_word_count' => 'on', // Always on for interactive plans
                'volumes' => $_POST['volumes']
            ),
            'schedule' => array(),
            'progress' => array(),
            'stats' => array(
                'total_words' => isset($total_words) ? $total_words : 0,
                'words_per_day' => isset($original_words_per_day) ? $original_words_per_day : 0,
                'days_to_read' => isset($days_to_read) ? $days_to_read : 0
            )
        );
        
        // Convert schedule to plan format
        foreach ($schedule as $day) {
            if (!empty($day['verse'])) {
                $date_key = $day['year'] . '-' . str_pad($day['month'], 2, '0', STR_PAD_LEFT) . '-' . str_pad($day['day_of_month'], 2, '0', STR_PAD_LEFT);
                $reading_data = array(
                    'date' => $date_key,
                    'reading' => $day['verse'],
                    'word_count' => intval($day['word_count']),
                    'verse_count' => intval($day['verse_count'])
                );
                
                if ($_POST['scheduling_method'] === 'chapter' && isset($day['chapter_count'])) {
                    $reading_data['chapter_count'] = intval($day['chapter_count']);
                }
                
                if (isset($day['virtual_previous_reading'])) {
                    $reading_data['virtual_previous_reading'] = $day['virtual_previous_reading'];
                }
                
                $new_plan_data['schedule'][] = $reading_data;
            }
        }
        
        // Preserve existing progress for dates that still exist in the new plan
        $preserved_progress = array();
        foreach ($new_plan_data['schedule'] as $new_reading) {
            $date = $new_reading['date'];
            if (isset($plan_data['progress'][$date])) {
                $preserved_progress[$date] = $plan_data['progress'][$date];
            } else {
                $preserved_progress[$date] = false;
            }
        }
        
        // Update the existing plan
        $new_plan_data['id'] = $plan_id;
        $new_plan_data['created'] = isset($plan_data['created']) ? $plan_data['created'] : date('Y-m-d H:i:s');
        $new_plan_data['updated'] = date('Y-m-d H:i:s');
        $new_plan_data['progress'] = $preserved_progress;
        
        // Save the updated plan
        if (file_put_contents($plan_file, json_encode($new_plan_data, JSON_PRETTY_PRINT))) {
            // Redirect back to the plan view using clean URL
            header('Location: /schedule/plan/' . $plan_id);
            exit;
        } else {
            $error = 'Failed to save updated plan';
        }
    } else {
        $error = 'Failed to generate schedule data. Please check your parameters.';
    }
}

// CSV loading functions are available from calc.php (included above)

// Helper function to calculate the next starting verse using existing CSV data
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
        // Return the next item in sequence - this is the preferred method
        return $data_source[$current_index + 1]['verse_title'];
    }
    
    // Fallback: Try to match by parsing the reference and finding the verse_id
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
    
    // Ultimate fallback - return the original verse if nothing else works
    return $last_verse;
}

// Find last completed reading for display
$last_completed_reading = null;
$completed_count = 0;
foreach ($plan_data['schedule'] as $reading) {
    if (isset($plan_data['progress'][$reading['date']]) && $plan_data['progress'][$reading['date']]) {
        $completed_count++;
        if (!$last_completed_reading || $reading['date'] > $last_completed_reading['date']) {
            $last_completed_reading = $reading;
        }
    }
}

// Calculate the next starting verse for display when there's progress
$display_next_verse = '';
if ($completed_count > 0 && $last_completed_reading) {
    $display_next_verse = calculateNextStartingVerse($last_completed_reading['reading'], $plan_data['parameters']['scheduling_method']);
}

// Load available volumes for the form
$volumes_data = array(
    'ot' => 'Old Testament',
    'nt' => 'New Testament', 
    'bofm' => 'Book of Mormon',
    'dc-testament' => 'Doctrine and Covenants',
    'pgp' => 'Pearl of Great Price'
);

// Map volume IDs to volume keys for form population
$volume_id_to_key = array(
    1 => 'ot',
    2 => 'nt',
    3 => 'bofm',
    4 => 'dc-testament',
    5 => 'pgp'
);

// Convert numeric volume IDs to string keys for form display
$selected_volumes = array();
if (isset($plan_data['parameters']['volumes']) && is_array($plan_data['parameters']['volumes'])) {
    foreach ($plan_data['parameters']['volumes'] as $volume_id) {
        if (isset($volume_id_to_key[$volume_id])) {
            $selected_volumes[] = $volume_id_to_key[$volume_id];
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">
    <title>Edit Reading Plan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="theme.css?v=<?= time() ?>" rel="stylesheet">
</head>
<body>
    <button class="theme-toggle" id="theme-toggle" title="Toggle theme">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="5"/>
            <path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/>
        </svg>
    </button>

    <div class="container mt-4">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1>Edit Reading Plan</h1>
                    <a href="/schedule/plan/<?= $plan_id ?>" class="btn btn-outline-secondary">
                        &#8592; Back to Plan
                    </a>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <?php
                // Variables already calculated above
                ?>

                <?php if ($completed_count > 0): ?>
                    <div class="alert alert-info">
                        <h6>&#128202; Your Progress Will Be Preserved</h6>
                        <p class="mb-1">You have completed <strong><?= $completed_count ?></strong> reading(s) so far.</p>
                        <?php if ($last_completed_reading): ?>
                            <p class="mb-0">Last completed: <strong><?= htmlspecialchars($last_completed_reading['reading']) ?></strong> on <?= date('M j, Y', strtotime($last_completed_reading['date'])) ?></p>
                            <small class="text-muted">Your new plan will automatically start from where you left off.</small>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <form method="post">
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title">Reading Schedule</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <label for="start_date" class="form-label">When will you start reading?</label>
                                    <input id="start_date" name="start_date" type="date" class="form-control" 
                                           value="<?= htmlspecialchars($plan_data['parameters']['start_date']) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="end_date" class="form-label">When will you finish reading?</label>
                                    <input id="end_date" name="end_date" type="date" class="form-control" 
                                           value="<?= htmlspecialchars($plan_data['parameters']['end_date']) ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title">What do you want to read?</h5>
                            <div class="row">
                                <?php foreach ($volumes_data as $volume_key => $volume_name): ?>
                                    <div class="col-md-6 col-lg-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="volumes[]" 
                                                   value="<?= $volume_key ?>" id="<?= $volume_key ?>"
                                                   <?= in_array($volume_key, $selected_volumes) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="<?= $volume_key ?>">
                                                <?= htmlspecialchars($volume_name) ?>
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title">Reading Method</h5>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="scheduling_method" 
                                       value="verse" id="verse_method"
                                       <?= $plan_data['parameters']['scheduling_method'] === 'verse' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="verse_method">
                                    <strong>By verse</strong> - More precise daily readings
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="scheduling_method" 
                                       value="chapter" id="chapter_method"
                                       <?= $plan_data['parameters']['scheduling_method'] === 'chapter' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="chapter_method">
                                    <strong>By chapter</strong> - Complete chapters each day
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title">Starting Point</h5>
                            <?php if ($completed_count > 0): ?>
                                <div class="alert alert-warning">
                                    <strong>Note:</strong> Since you have progress on this plan, the starting <?= $plan_data['parameters']['scheduling_method'] === 'chapter' ? 'chapter' : 'verse' ?> will be automatically calculated based on your last completed reading.
                                </div>
                            <?php endif; ?>
                            <label for="beginning_verse" class="form-label" id="beginning_verse_label">
                                Starting <?= $plan_data['parameters']['scheduling_method'] === 'chapter' ? 'chapter' : 'verse' ?> <?php if ($completed_count == 0): ?>(optional - leave blank to start from the beginning)<?php endif; ?>
                            </label>
                            <div class="autocomplete-container">
                                <input type="text" class="form-control verse-input" id="beginning_verse" name="beginning_verse" 
                                       value="<?= $completed_count > 0 ? htmlspecialchars($display_next_verse) : htmlspecialchars($plan_data['parameters']['beginning_verse']) ?>"
                                       placeholder="e.g., 1 Nephi 3:7 or Genesis 1:1"
                                       autocomplete="off"
                                       <?= $completed_count > 0 ? 'disabled readonly' : '' ?>>
                                <div id="autocomplete-suggestions" class="autocomplete-suggestions"></div>
                            </div>
                            <div id="validation-message" class="validation-message"></div>
                            <?php if ($completed_count == 0): ?>
                            <small class="form-text text-muted">
                                Examples: "1 Nephi 3:7", "Genesis 1:1", "Doctrine and Covenants 20:1"
                            </small>
                            <?php endif; ?>
                        </div>
                    </div>



                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="../<?= $plan_id ?>" class="btn btn-outline-secondary me-md-2">Cancel</a>
                        <button type="submit" class="btn btn-primary">Update Reading Plan</button>
                    </div>
                </form>
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

        // Autocomplete functionality for beginning verse
        document.addEventListener('DOMContentLoaded', function() {
            const input = document.getElementById('beginning_verse');
            const suggestionsContainer = document.getElementById('autocomplete-suggestions');
            const validationMessage = document.getElementById('validation-message');
            const verseMethodRadio = document.getElementById('verse_method');
            const chapterMethodRadio = document.getElementById('chapter_method');
            const volumeCheckboxes = document.querySelectorAll('input[name="volumes[]"]');
            
            let allVerses = [];
            let allChapters = [];
            let filteredData = [];
            let selectedIndex = -1;
            let currentMode = '<?= $plan_data['parameters']['scheduling_method'] ?>';

            // Always load verse and chapter data (needed for validation even when input is disabled)
            Promise.all([
                fetch('get_verses.php').then(response => response.json()),
                fetch('get_chapters.php').then(response => response.json())
            ]).then(([verses, chapters]) => {
                allVerses = verses;
                allChapters = chapters;
                updateFilteredData();
            }).catch(error => {
                console.error('Error loading scripture data:', error);
            });

            // Update current mode based on selected scheduling method
            function updateCurrentMode() {
                // Check which radio button is actually selected
                const newCurrentMode = verseMethodRadio.checked ? 'verse' : 'chapter';
                currentMode = newCurrentMode;
                
                // Update label text
                const label = document.getElementById('beginning_verse_label');
                const completedCount = <?= $completed_count ?>;
                const labelText = currentMode === 'chapter' ? 'chapter' : 'verse';
                
                if (completedCount > 0) {
                    label.innerHTML = `Starting ${labelText}`;
                } else {
                    label.innerHTML = `Starting ${labelText} <small class="text-muted">(optional - leave blank to start from the beginning)</small>`;
                }
                
                // Update placeholder text
                if (currentMode === 'chapter') {
                    input.placeholder = 'e.g., 1 Nephi 3 or Genesis 1';
                } else {
                    input.placeholder = 'e.g., 1 Nephi 3:7 or Genesis 1:1';
                }
                
                // Update alert text if there's progress
                if (completedCount > 0) {
                    const alertDiv = document.querySelector('.alert-warning');
                    if (alertDiv) {
                        alertDiv.innerHTML = `<strong>Note:</strong> Since you have progress on this plan, the starting ${labelText} will be automatically calculated based on your last completed reading.`;
                    }
                    
                    // Update the input value to show the correct next reference based on the selected method
                    updateStartingVerseForSchedulingMethod();
                }
                
                updateFilteredData();
            }

            // Function to update the starting verse value based on scheduling method when there's progress
            function updateStartingVerseForSchedulingMethod() {
                const completedCount = <?= $completed_count ?>;
                
                if (completedCount > 0) {
                    // Get the current value in the input field (which should already be the correct next reading)
                    const currentValue = input.value.trim();
                    
                    if (currentValue) {
                        // Convert the current value to the appropriate format for the new mode
                        const convertedReference = convertReferenceFormat(currentValue, currentMode);
                        input.value = convertedReference;
                        validateInput();
                    } else {
                        // Fallback: if input is empty, calculate from last reading
                        const lastReading = '<?= $last_completed_reading ? addslashes($last_completed_reading['reading']) : '' ?>';
                        
                        if (lastReading) {
                            const nextVerse = calculateNextStartingVerse(lastReading, currentMode);
                            const convertedReference = convertReferenceFormat(nextVerse, currentMode);
                            input.value = convertedReference;
                            validateInput();
                        }
                    }
                }
            }
            
            // Function to convert reference format between chapter and verse modes
            function convertReferenceFormat(reference, targetMode) {
                // Parse the reference
                const match = reference.match(/^(.+?)\s+(\d+)(?::(\d+))?$/);
                if (!match) {
                    return reference;
                }
                
                const book = match[1].trim();
                const chapter = parseInt(match[2]);
                const verse = match[3] ? parseInt(match[3]) : null;
                
                if (targetMode === 'verse') {
                    // Convert to verse format
                    if (verse === null) {
                        // No verse specified, add ":1" to make it the first verse
                        return `${book} ${chapter}:1`;
                    } else {
                        // Already has verse, return as-is
                        return reference;
                    }
                } else {
                    // Convert to chapter format
                    if (verse !== null) {
                        // Has verse, remove it to get chapter only
                        return `${book} ${chapter}`;
                    } else {
                        // Already in chapter format, return as-is
                        return reference;
                    }
                }
            }

            // Function to calculate next starting verse in JavaScript using existing data
            function calculateNextStartingVerse(lastVerse, schedulingMethod) {
                // Use the appropriate data source (already loaded)
                const dataSource = schedulingMethod === 'chapter' ? allChapters : allVerses;
                
                // Find the current verse/chapter in the data
                const currentIndex = dataSource.findIndex(item => item.verse_title === lastVerse);
                
                if (currentIndex >= 0 && currentIndex + 1 < dataSource.length) {
                    // Return the next item in sequence - this is the preferred method
                    return dataSource[currentIndex + 1].verse_title;
                }
                
                // Fallback: Try to match by parsing the reference
                const match = lastVerse.match(/^(.+?)\s+(\d+)(?::(\d+))?$/);
                if (match) {
                    const bookName = match[1].trim();
                    const chapterNum = parseInt(match[2]);
                    const verseNum = match[3] ? parseInt(match[3]) : null;
                    
                    // Look for entries that match this parsed reference
                    for (let i = 0; i < dataSource.length; i++) {
                        const row = dataSource[i];
                        const rowTitle = row.verse_title;
                        
                        // Parse the row title to compare
                        const rowMatch = rowTitle.match(/^(.+?)\s+(\d+)(?::(\d+))?$/);
                        if (rowMatch) {
                            const rowBook = rowMatch[1].trim();
                            const rowChapter = parseInt(rowMatch[2]);
                            const rowVerse = rowMatch[3] ? parseInt(rowMatch[3]) : null;
                            
                            // Check if this matches our search
                            const bookMatch = (rowBook === bookName);
                            const chapterMatch = (rowChapter === chapterNum);
                            const verseMatch = (verseNum === null && rowVerse === null) || (verseNum === rowVerse);
                            
                            if (bookMatch && chapterMatch && verseMatch) {
                                // Found a match, return the next entry if it exists
                                if (i + 1 < dataSource.length) {
                                    return dataSource[i + 1].verse_title;
                                }
                                break;
                            }
                        }
                    }
                }
                
                // Ultimate fallback - return the original verse if nothing else works
                return lastVerse;
            }

            // Update filtered data based on selected volumes and current mode
            function updateFilteredData() {
                const selectedVolumes = getSelectedVolumes();
                
                if (selectedVolumes.length === 0) {
                    filteredData = [];
                } else {
                    const sourceData = currentMode === 'chapter' ? allChapters : allVerses;
                    filteredData = sourceData.filter(item => selectedVolumes.includes(item.volume_id));
                }
                
                // Re-validate input with new filtered data
                validateInput();
            }

            // Get selected volume IDs
            function getSelectedVolumes() {
                const volumes = [];
                volumeCheckboxes.forEach(checkbox => {
                    if (checkbox.checked) {
                        switch(checkbox.value) {
                            case 'ot': volumes.push(1); break;
                            case 'nt': volumes.push(2); break;
                            case 'bofm': volumes.push(3); break;
                            case 'dc-testament': volumes.push(4); break;
                            case 'pgp': volumes.push(5); break;
                        }
                    }
                });
                return volumes;
            }

            // Validation function
            function validateInput() {
                const value = input.value.trim();
                
                if (value === '' || value.length < 2) {
                    input.classList.remove('is-valid', 'is-invalid');
                    validationMessage.className = 'validation-message';
                    validationMessage.innerHTML = '&nbsp;';
                    return;
                }
                
                // Check for exact match first
                const isExactMatch = filteredData.some(item => item.verse_title === value);
                
                if (isExactMatch) {
                    input.classList.remove('is-invalid');
                    input.classList.add('is-valid');
                    const referenceType = currentMode === 'chapter' ? 'chapter' : 'verse';
                    validationMessage.className = 'validation-message valid text-success small';
                    validationMessage.innerHTML = `&#10003; Valid ${referenceType} reference`;
                    return;
                }
                
                // If not exact match, check if there are suggestions (partial matches)
                const suggestions = filterSuggestions(value);
                
                if (suggestions.length > 0) {
                    // There are suggestions, so this could be a valid partial input
                    input.classList.remove('is-valid', 'is-invalid');
                    validationMessage.className = 'validation-message';
                    validationMessage.innerHTML = '&nbsp;';
                } else {
                    // No suggestions and no exact match
                    input.classList.remove('is-valid');
                    input.classList.add('is-invalid');
                    validationMessage.className = 'validation-message invalid text-danger small';
                    if (filteredData.length === 0) {
                        validationMessage.innerHTML = '&#10005; Please select at least one volume';
                    } else {
                        const referenceType = currentMode === 'chapter' ? 'chapter' : 'verse';
                        validationMessage.innerHTML = `&#10005; ${referenceType.charAt(0).toUpperCase() + referenceType.slice(1)} reference not found in selected volumes`;
                    }
                }
            }

            			function filterSuggestions(query) {
				if (query.length < 2 || filteredData.length === 0) return [];
				
				const queryLower = query.toLowerCase();
				return filteredData
					.filter(item => item.verse_title.toLowerCase().includes(queryLower))
					.map(item => item.verse_title)
					.sort((a, b) => {
						const aIndex = a.toLowerCase().indexOf(queryLower);
						const bIndex = b.toLowerCase().indexOf(queryLower);
						if (aIndex !== bIndex) return aIndex - bIndex;
						// Use natural/alphanumeric sorting for proper number ordering
						return a.localeCompare(b, undefined, { numeric: true, sensitivity: 'base' });
					});
			}

            function showSuggestions(suggestions) {
                suggestionsContainer.innerHTML = '';
                selectedIndex = -1;
                
                if (suggestions.length === 0) {
                    suggestionsContainer.style.display = 'none';
                    return;
                }
                
                suggestions.slice(0, 10).forEach((suggestion, index) => {
                    const div = document.createElement('div');
                    div.className = 'autocomplete-suggestion';
                    div.textContent = suggestion;
                    div.addEventListener('click', function() {
                        input.value = suggestion;
                        suggestionsContainer.style.display = 'none';
                        validateInput();
                    });
                    suggestionsContainer.appendChild(div);
                });
                
                suggestionsContainer.style.display = 'block';
            }

            // Event listeners
            input.addEventListener('input', function() {
                const query = this.value.trim();
                
                if (query.length >= 2) {
                    const suggestions = filterSuggestions(query);
                    showSuggestions(suggestions);
                } else {
                    suggestionsContainer.style.display = 'none';
                }
                
                validateInput();
            });

            // Keyboard navigation
            input.addEventListener('keydown', function(e) {
                const suggestions = suggestionsContainer.children;
                
                if (suggestions.length === 0) return;
                
                switch(e.key) {
                    case 'ArrowDown':
                        e.preventDefault();
                        selectedIndex = Math.min(selectedIndex + 1, suggestions.length - 1);
                        updateSelection();
                        break;
                    case 'ArrowUp':
                        e.preventDefault();
                        selectedIndex = Math.max(selectedIndex - 1, -1);
                        updateSelection();
                        break;
                    case 'Enter':
                        e.preventDefault();
                        if (selectedIndex >= 0) {
                            input.value = suggestions[selectedIndex].textContent;
                            suggestionsContainer.style.display = 'none';
                            validateInput();
                        }
                        break;
                    case 'Escape':
                        suggestionsContainer.style.display = 'none';
                        break;
                }
            });

            function updateSelection() {
                const suggestions = suggestionsContainer.children;
                
                for (let i = 0; i < suggestions.length; i++) {
                    suggestions[i].classList.remove('selected');
                }
                
                if (selectedIndex >= 0) {
                    suggestions[selectedIndex].classList.add('selected');
                }
            }

            // Hide suggestions when clicking outside
            document.addEventListener('click', function(e) {
                if (!input.contains(e.target) && !suggestionsContainer.contains(e.target)) {
                    suggestionsContainer.style.display = 'none';
                }
            });

            // Listen for scheduling method changes
            if (verseMethodRadio) {
                verseMethodRadio.addEventListener('change', updateCurrentMode);
            }
            
            if (chapterMethodRadio) {
                chapterMethodRadio.addEventListener('change', updateCurrentMode);
            }
            
            // Fallback - listen to all radio buttons with name="scheduling_method"
            const allSchedulingRadios = document.querySelectorAll('input[name="scheduling_method"]');
            allSchedulingRadios.forEach((radio) => {
                radio.addEventListener('change', updateCurrentMode);
            });

            // Listen for volume checkbox changes
            volumeCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updateFilteredData);
            });

            // Initialize - run on page load
            updateCurrentMode();
            
            // Update the starting verse value immediately on page load for progress cases
            if (<?= $completed_count ?> > 0) {
                updateStartingVerseForSchedulingMethod();
            }
        });
    </script>
</body>
</html> 