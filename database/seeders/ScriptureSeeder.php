<?php

namespace Database\Seeders;

use App\Models\Scripture;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ScriptureSeeder extends Seeder
{
    private const VOLUME_MAP = [
        'Old Testament' => 1,
        'New Testament' => 2,
        'Book of Mormon' => 3,
        'Doctrine and Covenants' => 4,
        'Pearl of Great Price' => 5,
    ];

    public function run(): void
    {
        $csvPath = base_path('legacy/data/lds-scriptures.csv');
        
        if (!file_exists($csvPath)) {
            $this->command->error("CSV file not found at: {$csvPath}");
            return;
        }

        $this->command->info('Importing scripture data from CSV...');
        
        DB::table('scriptures')->truncate();
        
        $handle = fopen($csvPath, 'r');
        $header = fgetcsv($handle);
        
        $batch = [];
        $count = 0;
        $batchSize = 1000;
        
        while (($row = fgetcsv($handle)) !== false) {
            $record = array_combine($header, $row);
            
            $verseTitle = $record['verse_title'];
            $parsed = $this->parseVerseTitle($verseTitle);
            
            $batch[] = [
                'id' => (int) $record['verse_id'],
                'volume_id' => self::VOLUME_MAP[$record['volume_title']] ?? 1,
                'volume_title' => $record['volume_title'],
                'book_title' => $parsed['book'],
                'chapter' => $parsed['chapter'],
                'verse' => $parsed['verse'],
                'verse_title' => $verseTitle,
                'word_count' => (int) $record['word_count'],
                'scripture_text' => null,
            ];
            
            $count++;
            
            if (count($batch) >= $batchSize) {
                DB::table('scriptures')->insert($batch);
                $batch = [];
                $this->command->info("Imported {$count} verses...");
            }
        }
        
        if (!empty($batch)) {
            DB::table('scriptures')->insert($batch);
        }
        
        fclose($handle);
        
        $this->command->info("Completed: {$count} verses imported.");
    }

    private function parseVerseTitle(string $verseTitle): array
    {
        $book = '';
        $chapter = 0;
        $verse = null;
        
        if (preg_match('/^(.+?)\s+(\d+)(?::(\d+))?$/', $verseTitle, $matches)) {
            $book = trim($matches[1]);
            $chapter = (int) $matches[2];
            $verse = isset($matches[3]) ? (int) $matches[3] : null;
        }
        
        return compact('book', 'chapter', 'verse');
    }
}
