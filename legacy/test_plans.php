<?php
/*
 * Test Script: Validate Reading Plans
 * 
 * This script checks all existing reading plans to ensure:
 * 1. All references are valid scripture references
 * 2. References are in the correct format for the scheduling method
 * 3. Plans are being calculated correctly after edits
 */

// Set up error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Increase execution time limit for large datasets
set_time_limit(120);

// Define data loading functions directly (to avoid calc.php form processing)
function load_csv_data() {
    $csv_file = "data/lds-scriptures.csv";
    
    if (!file_exists($csv_file)) {
        return array(); // Return empty array if file doesn't exist
    }
    
    $data = array();
    if (($handle = fopen($csv_file, "r")) !== FALSE) {
        $header = fgetcsv($handle, 1000, ",");
        while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $record = array_combine($header, $row);
            $data[] = $record;
        }
        fclose($handle);
    }
    
    return $data;
}

function load_chapter_data() {
    $csv_file = "data/lds-scriptures-chapters.csv";
    
    if (!file_exists($csv_file)) {
        return array(); // Return empty array if file doesn't exist
    }
    
    $data = array();
    if (($handle = fopen($csv_file, "r")) !== FALSE) {
        $header = fgetcsv($handle, 1000, ",");
        while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $record = array_combine($header, $row);
            $data[] = $record;
        }
        fclose($handle);
    }
    
    return $data;
}

// Load all verse and chapter data for validation
$all_verses = load_csv_data();
$all_chapters = load_chapter_data();

// Create lookup arrays for fast validation (O(1) instead of O(n))
$verse_lookup = array();
$chapter_lookup = array();

foreach ($all_verses as $verse) {
    $verse_lookup[$verse['verse_title']] = true;
}

foreach ($all_chapters as $chapter) {
    $chapter_lookup[$chapter['verse_title']] = true;
}

echo "<html><head><title>Reading Plan Validation Test</title>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; }
.plan { margin-bottom: 30px; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
.plan-header { background: #f5f5f5; padding: 10px; margin: -15px -15px 15px -15px; border-radius: 5px 5px 0 0; }
.valid { color: green; }
.invalid { color: red; font-weight: bold; }
.warning { color: orange; }
table { border-collapse: collapse; width: 100%; margin-top: 10px; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
.summary { background: #e6f3ff; padding: 15px; margin-bottom: 20px; border-radius: 5px; }
</style></head><body>";

echo "<h1>Reading Plan Validation Test</h1>";

// Get all plan files
function getAllPlanFiles() {
    $plan_files = array();
    $plans_dir = 'plans/';
    
    if (is_dir($plans_dir)) {
        $files = scandir($plans_dir);
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'json') {
                $plan_files[] = $file;
            }
        }
    }
    
    return $plan_files;
}

// Load a plan from file
function loadPlan($filename) {
    $filepath = 'plans/' . $filename;
    if (file_exists($filepath)) {
        $content = file_get_contents($filepath);
        return json_decode($content, true);
    }
    return null;
}

// Validate a scripture reference (optimized version using lookups)
function validateReference($reference, $scheduling_method, $verse_lookup, $chapter_lookup) {
    // Check for exact match using fast O(1) lookup
    if ($scheduling_method === 'chapter') {
        if (isset($chapter_lookup[$reference])) {
            return array('valid' => true, 'message' => 'Valid reference');
        }
    } else {
        if (isset($verse_lookup[$reference])) {
            return array('valid' => true, 'message' => 'Valid reference');
        }
    }
    
    // Check if it's a format mismatch (fast lookup)
    if ($scheduling_method === 'verse') {
        if (isset($chapter_lookup[$reference])) {
            return array('valid' => false, 'message' => 'Chapter reference in verse mode - should end with :X');
        }
    } else {
        if (isset($verse_lookup[$reference])) {
            return array('valid' => false, 'message' => 'Verse reference in chapter mode - should not have :X');
        }
    }
    
    // Simple heuristic checks for common invalid patterns
    if (preg_match('/^(.+?)\s+(\d+)(?::(\d+))?$/', $reference, $matches)) {
        $book = trim($matches[1]);
        $chapter = (int)$matches[2];
        
        // Quick check for obviously invalid chapter numbers
        if ($chapter > 150) {
            return array('valid' => false, 'message' => "Chapter $chapter is too high (no book has >150 chapters)");
        }
        
        // Check specific known limits for Book of Mormon books
        $known_limits = array(
            '1 Nephi' => 22,
            '2 Nephi' => 33,
            '3 Nephi' => 30,
            '4 Nephi' => 1,
            'Ether' => 15,
            'Moroni' => 10
        );
        
        if (isset($known_limits[$book]) && $chapter > $known_limits[$book]) {
            return array('valid' => false, 'message' => "Chapter $chapter does not exist in $book (max: {$known_limits[$book]})");
        }
    }
    
    return array('valid' => false, 'message' => 'Reference not found in scripture data');
}

// Get summary statistics
$total_plans = 0;
$plans_with_errors = 0;
$total_readings = 0;
$invalid_readings = 0;

$plan_files = getAllPlanFiles();

echo "<div class='summary'>";
echo "<h2>Summary</h2>";
echo "<p>Found " . count($plan_files) . " plan files to validate.</p>";
echo "</div>";

// Process each plan
foreach ($plan_files as $filename) {
    $plan_data = loadPlan($filename);
    if (!$plan_data) {
        echo "<div class='plan'>";
        echo "<div class='plan-header'><strong>$filename</strong></div>";
        echo "<p class='invalid'>Error: Could not load plan file</p>";
        echo "</div>";
        continue;
    }
    
    $total_plans++;
    $plan_id = pathinfo($filename, PATHINFO_FILENAME);
    $scheduling_method = isset($plan_data['parameters']['scheduling_method']) ? $plan_data['parameters']['scheduling_method'] : 'unknown';
    $volumes = isset($plan_data['parameters']['volumes']) ? $plan_data['parameters']['volumes'] : array();
    $plan_errors = 0;
    
    echo "<div class='plan'>";
    echo "<div class='plan-header'>";
    echo "<strong>Plan ID: $plan_id</strong> | ";
    echo "Method: <em>$scheduling_method</em> | ";
    $volume_names = array(1 => 'OT', 2 => 'NT', 3 => 'BoM', 4 => 'D&C', 5 => 'PoGP');
    $volume_labels = array();
    foreach ($volumes as $v) {
        $volume_labels[] = isset($volume_names[$v]) ? $volume_names[$v] : $v;
    }
    echo "Volumes: " . implode(', ', $volume_labels);
    echo "</div>";
    
    if (!isset($plan_data['schedule']) || empty($plan_data['schedule'])) {
        echo "<p class='warning'>Warning: No schedule found in plan</p>";
        echo "</div>";
        continue;
    }
    
    echo "<table>";
    echo "<tr><th>Date</th><th>Reading</th><th>Status</th><th>Message</th></tr>";
    
    foreach ($plan_data['schedule'] as $reading) {
        $total_readings++;
        $date = isset($reading['date']) ? $reading['date'] : 'Unknown';
        $reference = isset($reading['reading']) ? $reading['reading'] : '';
        
        if (empty($reference)) {
            echo "<tr><td>$date</td><td><em>Empty</em></td><td class='invalid'>INVALID</td><td>Empty reference</td></tr>";
            $plan_errors++;
            $invalid_readings++;
            continue;
        }
        
        $validation = validateReference($reference, $scheduling_method, $verse_lookup, $chapter_lookup);
        
        $status_class = $validation['valid'] ? 'valid' : 'invalid';
        $status_text = $validation['valid'] ? 'VALID' : 'INVALID';
        
        if (!$validation['valid']) {
            $plan_errors++;
            $invalid_readings++;
        }
        
        echo "<tr>";
        echo "<td>$date</td>";
        echo "<td>$reference</td>";
        echo "<td class='$status_class'>$status_text</td>";
        echo "<td>{$validation['message']}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    if ($plan_errors > 0) {
        $plans_with_errors++;
        echo "<p class='invalid'><strong>Plan has $plan_errors invalid reading(s)</strong></p>";
    } else {
        echo "<p class='valid'><strong>All readings are valid!</strong></p>";
    }
    
    echo "</div>";
}

// Final summary
echo "<div class='summary'>";
echo "<h2>Final Results</h2>";
echo "<p><strong>Plans processed:</strong> $total_plans</p>";
echo "<p><strong>Plans with errors:</strong> $plans_with_errors</p>";
echo "<p><strong>Total readings:</strong> $total_readings</p>";
echo "<p><strong>Invalid readings:</strong> $invalid_readings</p>";

if ($invalid_readings > 0) {
    $error_percentage = round(($invalid_readings / $total_readings) * 100, 2);
    echo "<p class='invalid'><strong>Error rate:</strong> $error_percentage%</p>";
} else {
    echo "<p class='valid'><strong>All readings are valid! 🎉</strong></p>";
}
echo "</div>";

echo "</body></html>";
?> 