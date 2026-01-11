<?php
// Curriculum Import System - Parse official seminary manuals
require_once 'calc.php';

session_start();
$teacher_id = isset($_SESSION['teacher_id']) ? $_SESSION['teacher_id'] : 'demo_teacher';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'import_manual':
                handle_import_manual();
                break;
            case 'create_from_import':
                handle_create_from_import();
                break;
        }
    }
}

function handle_import_manual() {
    $manual_url = $_POST['manual_url'];
    
    // Validate URL
    if (!filter_var($manual_url, FILTER_VALIDATE_URL) || 
        !strpos($manual_url, 'churchofjesuschrist.org')) {
        $error = "Please provide a valid Church manual URL";
        return;
    }
    
    // Parse the manual
    $lessons = parse_seminary_manual($manual_url);
    
    if ($lessons) {
        $_SESSION['imported_lessons'] = $lessons;
        $_SESSION['manual_url'] = $manual_url;
        header('Location: curriculum_import.php?step=review');
        exit;
    } else {
        $error = "Failed to parse manual. Please check the URL and try again.";
    }
}

function handle_create_from_import() {
    global $teacher_id;
    
    if (!isset($_SESSION['imported_lessons'])) {
        die('No imported lessons found');
    }
    
    $lessons = $_SESSION['imported_lessons'];
    $selected_lessons = isset($_POST['selected_lessons']) ? $_POST['selected_lessons'] : array();
    
    // Create curriculum data
    $curriculum_data = array(
        'id' => generate_plan_id(),
        'type' => 'seminary_curriculum',
        'teacher_id' => $teacher_id,
        'class_name' => $_POST['class_name'],
        'seminary_year' => $_POST['seminary_year'],
        'school_year' => $_POST['school_year'],
        'start_date' => $_POST['start_date'],
        'end_date' => $_POST['end_date'],
        'meeting_days' => isset($_POST['meeting_days']) ? $_POST['meeting_days'] : array(),
        'source_manual' => $_SESSION['manual_url'],
        'assignments' => array(),
        'created' => date('Y-m-d H:i:s'),
        'enrollment_code' => strtoupper(generate_plan_id(6))
    );
    
    // Generate assignments from selected lessons and dates
    $assignments = generate_assignments_from_import($selected_lessons, $lessons, $_POST['start_date'], $_POST['end_date'], $_POST['meeting_days']);
    $curriculum_data['assignments'] = $assignments;
    
    // Save curriculum
    require_once 'teacher_curriculum.php';
    $curriculum_id = save_curriculum($curriculum_data);
    
    if ($curriculum_id) {
        // Clean up session
        unset($_SESSION['imported_lessons']);
        unset($_SESSION['manual_url']);
        
        header('Location: teacher_curriculum.php?id=' . $curriculum_id . '&mode=edit&imported=1');
        exit;
    }
}

function parse_seminary_manual($url) {
    // Get the manual content
    $html = fetch_manual_content($url);
    if (!$html) return false;
    
    $lessons = array();
    
    // Create a DOM parser
    $dom = new DOMDocument();
    @$dom->loadHTML($html);
    $xpath = new DOMXPath($dom);
    
    // Look for lesson links in the table of contents
    // The Church manual uses a specific structure for lesson links
    $lesson_links = $xpath->query('//a[contains(@href, "lesson") or contains(text(), "Lesson")]');
    
    foreach ($lesson_links as $link) {
        $lesson_text = trim($link->textContent);
        $lesson_href = $link->getAttribute('href');
        
        // Skip if it doesn't look like a lesson
        if (empty($lesson_text) || strlen($lesson_text) > 200) continue;
        
        // Parse lesson structure - looking for patterns like:
        // "Lesson 4—Doctrine and Covenants 1"
        // "Doctrine and Covenants 1:30–33"
        if (preg_match('/^(Lesson\s+\d+[—-]\s*)?(.+)$/i', $lesson_text, $matches)) {
            $lesson_title = trim($matches[2]);
            
            // Extract scripture reference and topic
            $scripture_ref = '';
            $topic = '';
            
            if (preg_match('/^([^:]+(?:\s+\d+(?:[—–-]\d+)?)?(?:[:;]\d+(?:[—–-]\d+)?)?)\s*(.*)$/i', $lesson_title, $ref_matches)) {
                $scripture_ref = trim($ref_matches[1]);
                $topic = trim($ref_matches[2]);
                
                // Clean up common patterns
                $scripture_ref = preg_replace('/^(Doctrine and Covenants|D&C)\s*/i', 'D&C ', $scripture_ref);
                $scripture_ref = preg_replace('/Joseph Smith[—–-]History/i', 'JS-H', $scripture_ref);
                
                // If no topic extracted, use the scripture ref as topic
                if (empty($topic)) {
                    $topic = $scripture_ref;
                }
            } else {
                $scripture_ref = $lesson_title;  
                $topic = $lesson_title;
            }
            
            // Determine if this should be a major deadline
            $is_major = false;
            if (preg_match('/\b(test|exam|assessment|mastery)\b/i', $topic) ||
                preg_match('/\b(vision|revelation|restoration|first vision|priesthood)\b/i', $topic)) {
                $is_major = true;
            }
            
            $lessons[] = array(
                'lesson_number' => extract_lesson_number($lesson_text),
                'scripture' => $scripture_ref,
                'topic' => $topic,
                'original_text' => $lesson_text,
                'url' => $lesson_href,
                'is_major_deadline' => $is_major
            );
        }
    }
    
    // Also try to parse from structured content sections
    $content_sections = $xpath->query('//div[contains(@class, "content") or contains(@class, "lesson")]//li');
    foreach ($content_sections as $section) {
        $text = trim($section->textContent);
        if (preg_match('/^(Doctrine and Covenants|D&C|Joseph Smith)/i', $text) && strlen($text) < 150) {
            // Additional parsing logic for content sections
            // This catches lessons that might be structured differently
        }
    }
    
    return array_slice($lessons, 0, 200); // Limit to reasonable number
}

function fetch_manual_content($url) {
    // Use cURL to fetch the manual content
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; Seminary Curriculum Parser)');
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $html = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code !== 200 || !$html) {
        return false;
    }
    
    return $html;
}

function extract_lesson_number($text) {
    if (preg_match('/Lesson\s+(\d+)/i', $text, $matches)) {
        return intval($matches[1]);
    }
    return 0;
}

function generate_assignments_from_import($selected_lessons, $all_lessons, $start_date, $end_date, $meeting_days) {
    $assignments = array();
    $current_date = strtotime($start_date);
    $end_date_ts = strtotime($end_date);
    
    $lesson_index = 0;
    while ($current_date <= $end_date_ts && $lesson_index < count($selected_lessons)) {
        $day_of_week = date('N', $current_date); // 1 = Monday, 7 = Sunday
        
        if (in_array($day_of_week, $meeting_days)) {
            $lesson_id = $selected_lessons[$lesson_index];
            $lesson = $all_lessons[$lesson_id];
            
            $assignments[] = array(
                'date' => date('Y-m-d', $current_date),
                'scripture' => $lesson['scripture'],
                'lesson_topic' => $lesson['topic'],
                'is_major_deadline' => $lesson['is_major_deadline'],
                'notes' => 'Imported from: ' . basename($lesson['original_text']),
                'source_url' => $lesson['url']
            );
            
            $lesson_index++;
        }
        
        $current_date = strtotime('+1 day', $current_date);
    }
    
    return $assignments;
}

// Determine current step
$step = isset($_GET['step']) ? $_GET['step'] : 'import';
$imported_lessons = isset($_SESSION['imported_lessons']) ? $_SESSION['imported_lessons'] : null;

?><!DOCTYPE html>
<html>
<head>
    <title>Import Seminary Curriculum</title>
    <link rel="stylesheet" href="https://unpkg.com/purecss@1.0.0/build/pure-min.css">
    <link rel="stylesheet" href="theme.css">
    <style>
        .import-container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .step-indicator { display: flex; justify-content: center; margin: 20px 0; }
        .step { padding: 10px 20px; margin: 0 10px; border-radius: 20px; background: #f0f0f0; }
        .step.active { background: #007cba; color: white; }
        .lesson-grid { display: grid; grid-template-columns: 40px 200px 1fr 80px 30px; gap: 10px; align-items: center; padding: 5px; border-bottom: 1px solid #eee; }
        .lesson-grid:hover { background: #f9f9f9; }
        .lesson-major { border-left: 3px solid #e74c3c; background: #fdf2f2; }
        .import-preview { max-height: 400px; overflow-y: auto; border: 1px solid #ddd; border-radius: 4px; }
        .manual-examples { background: #f8f9fa; padding: 15px; border-radius: 4px; margin: 15px 0; }
        .manual-examples h4 { margin-top: 0; }
        .import-stats { background: #e8f4f8; padding: 15px; border-radius: 4px; margin: 15px 0; }
    </style>
</head>
<body>

<div class="import-container">
    <h1>Import Seminary Curriculum</h1>
    
    <!-- Step Indicator -->
    <div class="step-indicator">
        <div class="step <?php echo $step === 'import' ? 'active' : ''; ?>">1. Import Manual</div>
        <div class="step <?php echo $step === 'review' ? 'active' : ''; ?>">2. Review Lessons</div>
        <div class="step <?php echo $step === 'create' ? 'active' : ''; ?>">3. Create Curriculum</div>
    </div>

    <?php if ($step === 'import'): ?>
        <!-- Step 1: Import Manual -->
        <div class="pure-g">
            <div class="pure-u-2-3">
                <h2>Import from Official Seminary Manual</h2>
                
                <form method="post" class="pure-form pure-form-stacked">
                    <input type="hidden" name="action" value="import_manual">
                    
                    <label for="manual_url">Seminary Manual URL:</label>
                    <input type="url" name="manual_url" id="manual_url" 
                           placeholder="https://www.churchofjesuschrist.org/study/manual/..." 
                           value="<?php echo isset($_POST['manual_url']) ? htmlspecialchars($_POST['manual_url']) : ''; ?>"
                           style="width: 100%;" required>
                    
                    <button type="submit" class="pure-button pure-button-primary" style="margin-top: 10px;">
                        Import Manual
                    </button>
                </form>
                
                <?php if (isset($error)): ?>
                    <div style="background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin: 10px 0;">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="pure-u-1-3" style="padding-left: 20px;">
                <div class="manual-examples">
                    <h4>2025 Seminary Manuals</h4>
                    <p><strong>Doctrine & Covenants:</strong><br/>
                    <a href="https://www.churchofjesuschrist.org/study/manual/doctrine-and-covenants-seminary-teacher-manual-2025?lang=eng" target="_blank">
                        D&C Seminary Teacher Manual 2025
                    </a></p>
                    
                    <p><strong>Book of Mormon:</strong><br/>
                    <a href="https://www.churchofjesuschrist.org/study/manual/book-of-mormon-seminary-teacher-manual-2024?lang=eng" target="_blank">
                        Book of Mormon Seminary Manual
                    </a></p>
                    
                    <p><strong>New Testament:</strong><br/>
                    <a href="https://www.churchofjesuschrist.org/study/manual/new-testament-seminary-teacher-manual-2023?lang=eng" target="_blank">
                        New Testament Seminary Manual
                    </a></p>
                    
                    <p><strong>Old Testament:</strong><br/>
                    <a href="https://www.churchofjesuschrist.org/study/manual/old-testament-seminary-teacher-manual-2022?lang=eng" target="_blank">
                        Old Testament Seminary Manual
                    </a></p>
                </div>
                
                <h4>How It Works</h4>
                <ol>
                    <li>Paste the URL of an official seminary manual</li>
                    <li>System extracts lesson titles and scripture references</li>
                    <li>Review and select which lessons to include</li>
                    <li>Assign dates and create your curriculum</li>
                </ol>
            </div>
        </div>

    <?php elseif ($step === 'review' && $imported_lessons): ?>
        <!-- Step 2: Review Imported Lessons -->
        <h2>Review Imported Lessons</h2>
        
        <div class="import-stats">
            <strong>Import Results:</strong> Found <?php echo count($imported_lessons); ?> lessons from manual
            | Major deadlines: <?php echo count(array_filter($imported_lessons, function($l) { return $l['is_major_deadline']; })); ?>
        </div>
        
        <form method="post" class="pure-form">
            <input type="hidden" name="action" value="create_from_import">
            
            <div class="pure-g">
                <div class="pure-u-1-3">
                    <h3>Curriculum Details</h3>
                    
                    <label for="class_name">Class Name:</label>
                    <input type="text" name="class_name" id="class_name" 
                           placeholder="e.g., Period 1 Seminary" required class="pure-input-1">
                    
                    <label for="seminary_year">Seminary Year:</label>
                    <select name="seminary_year" id="seminary_year" required class="pure-input-1">
                        <option value="">Select...</option>
                        <option value="doctrine_covenants">Doctrine & Covenants</option>
                        <option value="book_of_mormon">Book of Mormon</option>
                        <option value="new_testament">New Testament</option>
                        <option value="old_testament">Old Testament</option>
                    </select>
                    
                    <label for="school_year">School Year:</label>
                    <input type="text" name="school_year" id="school_year" 
                           placeholder="2024-2025" required class="pure-input-1">
                    
                    <label for="start_date">Start Date:</label>
                    <input type="date" name="start_date" id="start_date" required class="pure-input-1">
                    
                    <label for="end_date">End Date:</label>
                    <input type="date" name="end_date" id="end_date" required class="pure-input-1">
                    
                    <label>Meeting Days:</label>
                    <label class="pure-checkbox"><input type="checkbox" name="meeting_days[]" value="1"> Monday</label>
                    <label class="pure-checkbox"><input type="checkbox" name="meeting_days[]" value="2"> Tuesday</label>
                    <label class="pure-checkbox"><input type="checkbox" name="meeting_days[]" value="3"> Wednesday</label>
                    <label class="pure-checkbox"><input type="checkbox" name="meeting_days[]" value="4"> Thursday</label>
                    <label class="pure-checkbox"><input type="checkbox" name="meeting_days[]" value="5"> Friday</label>
                    
                    <button type="submit" class="pure-button pure-button-primary" style="margin-top: 15px;">
                        Create Curriculum with Selected Lessons
                    </button>
                </div>
                
                <div class="pure-u-2-3" style="padding-left: 20px;">
                    <h3>Select Lessons to Include 
                        <button type="button" onclick="selectAll()" class="pure-button" style="font-size: 12px;">Select All</button>
                        <button type="button" onclick="selectNone()" class="pure-button" style="font-size: 12px;">Select None</button>
                    </h3>
                    
                    <div class="import-preview">
                        <div class="lesson-grid" style="font-weight: bold; background: #f0f0f0;">
                            <div>Select</div>
                            <div>Scripture</div>
                            <div>Topic</div>
                            <div>Major</div>
                            <div>#</div>
                        </div>
                        
                        <?php foreach ($imported_lessons as $index => $lesson): ?>
                        <div class="lesson-grid <?php echo $lesson['is_major_deadline'] ? 'lesson-major' : ''; ?>">
                            <div>
                                <input type="checkbox" name="selected_lessons[]" value="<?php echo $index; ?>" checked>
                            </div>
                            <div title="<?php echo htmlspecialchars($lesson['original_text']); ?>">
                                <strong><?php echo htmlspecialchars($lesson['scripture']); ?></strong>
                            </div>
                            <div>
                                <?php echo htmlspecialchars($lesson['topic']); ?>
                            </div>
                            <div>
                                <?php if ($lesson['is_major_deadline']): ?>
                                    <span style="color: #e74c3c;">●</span>
                                <?php endif; ?>
                            </div>
                            <div style="font-size: 12px; color: #666;">
                                <?php echo $lesson['lesson_number'] ?: '-'; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </form>

    <?php else: ?>
        <!-- Error or invalid step -->
        <div style="text-align: center; padding: 40px;">
            <h3>Import Session Expired</h3>
            <p>Please start the import process again.</p>
            <a href="curriculum_import.php" class="pure-button pure-button-primary">Start Over</a>
        </div>
    <?php endif; ?>

    <div style="margin-top: 30px; text-align: center;">
        <a href="teacher_curriculum.php" class="pure-button">Back to Curriculum Management</a>
    </div>
</div>

<script>
function selectAll() {
    const checkboxes = document.querySelectorAll('input[name="selected_lessons[]"]');
    checkboxes.forEach(cb => cb.checked = true);
}

function selectNone() {
    const checkboxes = document.querySelectorAll('input[name="selected_lessons[]"]');
    checkboxes.forEach(cb => cb.checked = false);
}
</script>

</body>
</html> 