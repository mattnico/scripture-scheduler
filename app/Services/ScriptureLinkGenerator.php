<?php

namespace App\Services;

class ScriptureLinkGenerator
{
    protected static array $volumePaths = [
        1 => 'ot',
        2 => 'nt',
        3 => 'bofm',
        4 => 'dc-testament/dc',
        5 => 'pgp',
    ];

    protected static array $bookSlugs = [
        'Genesis' => 'gen',
        'Exodus' => 'ex',
        'Leviticus' => 'lev',
        'Numbers' => 'num',
        'Deuteronomy' => 'deut',
        'Joshua' => 'josh',
        'Judges' => 'judg',
        'Ruth' => 'ruth',
        '1 Samuel' => '1-sam',
        '2 Samuel' => '2-sam',
        '1 Kings' => '1-kgs',
        '2 Kings' => '2-kgs',
        '1 Chronicles' => '1-chr',
        '2 Chronicles' => '2-chr',
        'Ezra' => 'ezra',
        'Nehemiah' => 'neh',
        'Esther' => 'esth',
        'Job' => 'job',
        'Psalms' => 'ps',
        'Proverbs' => 'prov',
        'Ecclesiastes' => 'eccl',
        'Song of Solomon' => 'song',
        'Isaiah' => 'isa',
        'Jeremiah' => 'jer',
        'Lamentations' => 'lam',
        'Ezekiel' => 'ezek',
        'Daniel' => 'dan',
        'Hosea' => 'hosea',
        'Joel' => 'joel',
        'Amos' => 'amos',
        'Obadiah' => 'obad',
        'Jonah' => 'jonah',
        'Micah' => 'micah',
        'Nahum' => 'nahum',
        'Habakkuk' => 'hab',
        'Zephaniah' => 'zeph',
        'Haggai' => 'hag',
        'Zechariah' => 'zech',
        'Malachi' => 'mal',
        'Matthew' => 'matt',
        'Mark' => 'mark',
        'Luke' => 'luke',
        'John' => 'john',
        'Acts' => 'acts',
        'Romans' => 'rom',
        '1 Corinthians' => '1-cor',
        '2 Corinthians' => '2-cor',
        'Galatians' => 'gal',
        'Ephesians' => 'eph',
        'Philippians' => 'philip',
        'Colossians' => 'col',
        '1 Thessalonians' => '1-thes',
        '2 Thessalonians' => '2-thes',
        '1 Timothy' => '1-tim',
        '2 Timothy' => '2-tim',
        'Titus' => 'titus',
        'Philemon' => 'philem',
        'Hebrews' => 'heb',
        'James' => 'james',
        '1 Peter' => '1-pet',
        '2 Peter' => '2-pet',
        '1 John' => '1-jn',
        '2 John' => '2-jn',
        '3 John' => '3-jn',
        'Jude' => 'jude',
        'Revelation' => 'rev',
        '1 Nephi' => '1-ne',
        '2 Nephi' => '2-ne',
        'Jacob' => 'jacob',
        'Enos' => 'enos',
        'Jarom' => 'jarom',
        'Omni' => 'omni',
        'Words of Mormon' => 'w-of-m',
        'Mosiah' => 'mosiah',
        'Alma' => 'alma',
        'Helaman' => 'hel',
        '3 Nephi' => '3-ne',
        '4 Nephi' => '4-ne',
        'Mormon' => 'morm',
        'Ether' => 'ether',
        'Moroni' => 'moro',
        'Doctrine and Covenants' => '',
        'D&C' => '',
        'Moses' => 'moses',
        'Abraham' => 'abr',
        'Joseph Smith—Matthew' => 'js-m',
        'Joseph Smith—History' => 'js-h',
        'Articles of Faith' => 'a-of-f',
    ];

    public static function generateUrl(string $reference, int $volumeId): ?string
    {
        // Pattern to match: "Book Chapter:StartVerse-EndVerse" or "Book Chapter:StartVerse - Chapter:EndVerse" or "Book Chapter-Chapter"
        // Examples: "Moses 1:1-3", "1 Nephi 1:5 - 2:10", "Alma 5-6", "Genesis 1:1"
        
        // First, try to match verse range within same chapter: "Book Chapter:Verse-Verse"
        if (preg_match('/^(.+?)\s+(\d+):(\d+)\s*[-–]\s*(\d+)$/', $reference, $matches)) {
            $bookTitle = trim($matches[1]);
            $chapter = (int) $matches[2];
            $startVerse = (int) $matches[3];
            $endVerse = (int) $matches[4];
            
            return self::buildUrl($bookTitle, $chapter, $startVerse, $volumeId, $endVerse);
        }
        
        // Cross-chapter verse range: "Book Chapter:Verse - Chapter:Verse"
        if (preg_match('/^(.+?)\s+(\d+):(\d+)\s*[-–]\s*(\d+):(\d+)$/', $reference, $matches)) {
            $bookTitle = trim($matches[1]);
            $chapter = (int) $matches[2];
            $startVerse = (int) $matches[3];
            // For cross-chapter ranges, link to the starting verse only
            
            return self::buildUrl($bookTitle, $chapter, $startVerse, $volumeId);
        }
        
        // Chapter range: "Book Chapter-Chapter"
        if (preg_match('/^(.+?)\s+(\d+)\s*[-–]\s*(\d+)$/', $reference, $matches)) {
            $bookTitle = trim($matches[1]);
            $chapter = (int) $matches[2];
            // Link to the start of the first chapter
            
            return self::buildUrl($bookTitle, $chapter, null, $volumeId);
        }
        
        // Single verse or chapter: "Book Chapter:Verse" or "Book Chapter"
        if (preg_match('/^(.+?)\s+(\d+)(?::(\d+))?$/', $reference, $matches)) {
            $bookTitle = trim($matches[1]);
            $chapter = (int) $matches[2];
            $verse = isset($matches[3]) ? (int) $matches[3] : null;
            
            return self::buildUrl($bookTitle, $chapter, $verse, $volumeId);
        }
        
        return null;
    }

    protected static function buildUrl(string $bookTitle, int $chapter, ?int $verse, int $volumeId, ?int $endVerse = null): ?string
    {
        $volumePath = self::$volumePaths[$volumeId] ?? null;
        if (!$volumePath) return null;

        if ($volumeId === 4) {
            $url = "https://www.churchofjesuschrist.org/study/scriptures/dc-testament/dc/{$chapter}";
        } else {
            $bookSlug = self::$bookSlugs[$bookTitle] ?? self::generateSlug($bookTitle);
            if (!$bookSlug) return null;
            
            $url = "https://www.churchofjesuschrist.org/study/scriptures/{$volumePath}/{$bookSlug}/{$chapter}";
        }

        if ($verse) {
            if ($endVerse && $endVerse > $verse) {
                // Verse range within same chapter: id=p1-3#p1
                $url .= "?lang=eng&id=p{$verse}-{$endVerse}#p{$verse}";
            } else {
                // Single verse
                $url .= "?lang=eng&id=p{$verse}#p{$verse}";
            }
        } else {
            $url .= "?lang=eng";
        }

        return $url;
    }

    protected static function generateSlug(string $bookTitle): string
    {
        return strtolower(str_replace([' ', '.'], ['-', ''], $bookTitle));
    }
}
