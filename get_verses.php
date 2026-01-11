<?php
header('Content-Type: application/json');

function load_csv_data() {
    $csv_file = "data/lds-scriptures.csv";
    
    if (!file_exists($csv_file)) {
        die('Error: CSV file not found at ' . $csv_file);
    }
    
    $verses = array();
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
            
            $verses[] = array(
                'verse_title' => $record['verse_title'],
                'volume_id' => $volume_id
            );
        }
        fclose($handle);
    }
    
    return $verses;
}

$verses = load_csv_data();
echo json_encode($verses);
?> 