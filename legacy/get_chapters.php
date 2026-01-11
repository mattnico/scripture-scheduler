<?php
header('Content-Type: application/json');

function load_chapter_data() {
    $csv_file = "data/lds-scriptures-chapters.csv";
    
    if (!file_exists($csv_file)) {
        die('Error: Chapter CSV file not found at ' . $csv_file);
    }
    
    $chapters = array();
    if (($handle = fopen($csv_file, "r")) !== FALSE) {
        $header = fgetcsv($handle, 1000, ","); // Read header row
        while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $record = array_combine($header, $row);
            
            // Map volume titles to volume IDs
            $volume_map = array(
                'Old Testament' => 1,
                'New Testament' => 2,
                'Book of Mormon' => 3,
                'Doctrine and Covenants' => 4,
                'Pearl of Great Price' => 5
            );
            
            $volume_id = isset($volume_map[$record['volume_title']]) ? $volume_map[$record['volume_title']] : 1;
            
            $chapters[] = array(
                'verse_title' => $record['verse_title'], // This is actually the chapter title (e.g., "Genesis 1")
                'volume_id' => $volume_id
            );
        }
        fclose($handle);
    }
    
    return $chapters;
}

$chapters = load_chapter_data();
echo json_encode($chapters);
?> 