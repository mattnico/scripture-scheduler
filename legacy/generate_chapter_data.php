<?php
/*
 * Generate Chapter-based Scripture Data File
 * 
 * This script reads the verse-based CSV file and creates a new chapter-based CSV file
 * with the same structure, but grouped by chapters with correct chapter references.
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

function load_verse_data() {
    $csv_file = "data/lds-scriptures.csv";
    
    if (!file_exists($csv_file)) {
        die('Error: CSV file not found at ' . $csv_file);
    }
    
    $data = array();
    if (($handle = fopen($csv_file, "r")) !== FALSE) {
        $header = fgetcsv($handle, 1000, ","); // Read header row
        while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $record = array_combine($header, $row);
            $data[] = $record;
        }
        fclose($handle);
    }
    
    return $data;
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
                'verse_title' => $chapter_title, // This will be the chapter title (e.g., "Genesis 1")
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

function write_chapter_csv($chapter_data, $output_file) {
    if (($handle = fopen($output_file, "w")) !== FALSE) {
        // Write header
        fputcsv($handle, array('verse_id', 'volume_title', 'verse_title', 'word_count', 'verse_count'));
        
        // Write chapter data
        foreach ($chapter_data as $chapter) {
            fputcsv($handle, array(
                $chapter['verse_id'],
                $chapter['volume_title'],
                $chapter['verse_title'], // This is the chapter reference (e.g., "Genesis 1")
                $chapter['word_count'],
                $chapter['verse_count']
            ));
        }
        
        fclose($handle);
        return true;
    }
    
    return false;
}

// Main execution
echo "Starting chapter data generation...\n";

// Load verse data
echo "Loading verse data...\n";
$verse_data = load_verse_data();
echo "Loaded " . count($verse_data) . " verses.\n";

// Aggregate by chapters
echo "Aggregating by chapters...\n";
$chapter_data = aggregate_by_chapters($verse_data);
echo "Created " . count($chapter_data) . " chapters.\n";

// Write chapter CSV
$output_file = "data/lds-scriptures-chapters.csv";
echo "Writing chapter data to $output_file...\n";
if (write_chapter_csv($chapter_data, $output_file)) {
    echo "Success! Chapter data written to $output_file\n";
    echo "Sample entries:\n";
    
    // Show first 10 entries as examples
    foreach (array_slice($chapter_data, 0, 10) as $chapter) {
        echo "  {$chapter['verse_title']}: {$chapter['verse_count']} verses, {$chapter['word_count']} words\n";
    }
    
    // Show some Book of Mormon entries as examples
    echo "\nBook of Mormon examples:\n";
    foreach ($chapter_data as $chapter) {
        if ($chapter['volume_title'] === 'Book of Mormon' && strpos($chapter['verse_title'], '1 Nephi') === 0) {
            echo "  {$chapter['verse_title']}: {$chapter['verse_count']} verses, {$chapter['word_count']} words\n";
            if (strpos($chapter['verse_title'], '1 Nephi 5') === 0) break; // Stop after showing first few
        }
    }
} else {
    echo "Error: Could not write chapter data file.\n";
}

echo "Done!\n";
?> 