# AGENTS.md - Scripture Scheduler Codebase Guidelines

## Project Overview

A PHP web application for creating personalized scripture reading schedules. Users can select scripture volumes (Old Testament, New Testament, Book of Mormon, D&C, Pearl of Great Price), set date ranges, and generate balanced reading plans based on word count rather than chapter count.

**Tech Stack**: PHP 7+, Bootstrap 5, Vanilla JavaScript, SQLite/CSV data storage, wkhtmltopdf for PDF generation

---

## Build/Run Commands

```bash
# No build step required - PHP served directly
# Ensure PHP is installed and web server configured

# Local development with PHP built-in server:
php -S localhost:8000

# The application expects to run in a subdirectory called 'schedule'
# URL structure: /schedule/index.php, /schedule/plan/{id}, etc.
```

### PDF Generation

PDF generation requires `wkhtmltopdf`:
```bash
# macOS
brew install wkhtmltopdf

# Expected path: /usr/local/bin/wkhtmltopdf
```

### No Test Suite

This project has no automated tests. Manual testing required.

---

## Code Style Guidelines

### PHP Conventions

**File Structure**:
- Entry points: `index.php`, `calc.php`, `plan.php`, `edit.php`
- API endpoints: `get_verses.php`, `get_chapters.php`
- Data files: `data/*.csv`, `data/*.db`
- Plan storage: `plans/*.json`

**Error Handling**:
```php
// Pattern: Enable debugging at top of file when needed
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Validation pattern: die() with descriptive error
if (!isset($_REQUEST['required_param'])) {
    die('Error: Missing required parameters');
}

// File existence checks
if (!file_exists($plan_file)) {
    die('Error: Plan not found');
}
```

**Function Definitions**:
```php
// Guard against redefinition (used throughout)
if (!function_exists('load_csv_data')) {
    function load_csv_data() {
        // ...
    }
}
```

**Naming Conventions**:
- Functions: `snake_case` (e.g., `load_csv_data`, `generate_plan_id`, `days_to_read`)
- Variables: `$snake_case` (e.g., `$plan_data`, `$total_words`, `$scheduling_method`)
- Constants: Volume IDs are integers (1=OT, 2=NT, 3=BoM, 4=D&C, 5=PGP)

**Array Style**:
```php
// Use array() syntax (legacy PHP compatibility)
$data = array(
    'key' => 'value',
    'nested' => array('item1', 'item2')
);

// Associative arrays for data structures
$plan_data = array(
    'parameters' => array(...),
    'schedule' => array(...),
    'progress' => array(...)
);
```

**HTML in PHP**:
```php
// Short echo syntax for inline values
<input value="<?= date('Y-m-d') ?>">

// Heredoc-style mixing for complex output
echo '<table class="pure-table">';
foreach($data as $row) {
    echo "<tr><td>{$row['value']}</td></tr>";
}
echo '</table>';
```

### JavaScript Conventions

**Pattern**: Vanilla JS, no frameworks. All JS is inline in PHP files within `<script>` tags.

```javascript
// DOM ready pattern
document.addEventListener('DOMContentLoaded', function() {
    // Initialize
});

// Variable naming: camelCase
let allVerses = [];
let currentMode = 'verse';
const schedulingMethodInteractive = document.getElementById('scheduling_method_interactive');

// Event listeners
input.addEventListener('input', function() {
    // ...
});

// Fetch pattern
fetch('get_verses.php')
    .then(response => response.json())
    .then(data => { /* ... */ })
    .catch(error => console.error('Error:', error));
```

### CSS Conventions

**File**: `theme.css` - Comprehensive theme with dark/light mode support

**CSS Variables**:
```css
:root {
    --bg-primary: #000000;
    --bg-secondary: #1a1a1a;
    --text-primary: #ffffff;
    --accent-primary: #9D8255;  /* Subdued gold */
    --border-color: #444444;
}
```

**Theme Toggle**:
```css
body.light-mode {
    --bg-primary: var(--bg-primary-light);
    /* etc. */
}
```

---

## Data Structures

### Plan JSON Schema (`plans/{id}.json`)

```json
{
    "id": "abc123xyz",
    "created": "2024-01-15 10:30:00",
    "updated": "2024-01-16 08:00:00",
    "parameters": {
        "start_date": "2024-01-01",
        "end_date": "2024-12-31",
        "scheduling_method": "verse|chapter",
        "beginning_verse": "1 Nephi 1:1",
        "show_word_count": true,
        "volumes": [3]
    },
    "schedule": [
        {
            "date": "2024-01-01",
            "reading": "1 Nephi 1:20",
            "word_count": 1500,
            "verse_count": 20,
            "chapter_count": 1,
            "virtual_previous_reading": "1 Nephi 1:0"
        }
    ],
    "progress": {
        "2024-01-01": true,
        "2024-01-02": false
    },
    "stats": {
        "total_words": 268163,
        "words_per_day": 735,
        "days_to_read": 365
    }
}
```

### Scripture Reference Parsing

```php
// Pattern: "Book Chapter:Verse" or "Book Chapter"
preg_match('/^(.+?)\s+(\d+)(?::(\d+))?$/', $reference, $matches);
// $matches[1] = book name (e.g., "1 Nephi", "Doctrine and Covenants")
// $matches[2] = chapter number
// $matches[3] = verse number (optional)
```

---

## URL Routing

**Clean URLs via `.htaccess`**:
```
/schedule/plan/{id}        → plan.php?id={id}
/schedule/plan/{id}/edit   → edit.php?id={id}
```

**Fallback parsing in PHP**:
```php
// Try GET parameter first, then parse REQUEST_URI
if (isset($_GET['id'])) {
    $plan_id = $_GET['id'];
} else if (preg_match('#/plan/([a-z0-9]+)#', $_SERVER['REQUEST_URI'], $matches)) {
    $plan_id = $matches[1];
}
```

---

## Key Files Reference

| File | Purpose |
|------|---------|
| `index.php` | Main entry form |
| `calc.php` | Schedule generation, PDF output |
| `plan.php` | Interactive plan viewer |
| `edit.php` | Edit existing plans |
| `calendar.php` | ICS calendar feed generation |
| `get_verses.php` | JSON API for verse autocomplete |
| `get_chapters.php` | JSON API for chapter autocomplete |
| `theme.css` | All styling, dark/light themes |
| `data/lds-scriptures.csv` | Scripture verse data |
| `data/lds-scriptures-chapters.csv` | Chapter-level aggregated data |

---

## Common Patterns

### Volume ID Mapping

```php
$volume_map = array(
    'Old Testament' => 1,
    'New Testament' => 2,
    'Book of Mormon' => 3,
    'Doctrine and Covenants' => 4,
    'Pearl of Great Price' => 5
);

// Reverse for form handling
// ot=1, nt=2, bom=3, dc=4, pgp=5
```

### Form Parameter Handling

```php
// Checkbox volumes
if (isset($_REQUEST['bom']) && $_REQUEST['bom'] == 'on') {
    $volumes[] = 3;
}

// Date handling
$start_date = strtotime($_REQUEST['start_date']);
$end_date = strtotime($_REQUEST['end_date']);
```

### Progress Toggle Pattern

```php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_progress'])) {
    $date = $_POST['date'];
    $plan_data['progress'][$date] = !$plan_data['progress'][$date];
    file_put_contents($plan_file, json_encode($plan_data, JSON_PRETTY_PRINT));
    header('Location: plan.php?id=' . $plan_id);
    exit;
}
```

---

## Important Notes

1. **No ORM/Framework**: Direct file operations and array manipulation
2. **No Package Manager**: No composer.json, no npm - all dependencies via CDN
3. **Session Usage**: Minimal - `session_start()` only in teacher dashboard
4. **Security**: Input sanitization via `htmlspecialchars()`, basic parameter validation
5. **PDF Generation**: Uses `wkhtmltopdf` command line tool
6. **Data Storage**: JSON files in `plans/` directory, CSV files for scripture data

---

## TODO from Code Comments

From `plan.php`:
- Make progress toggle not reload page (AJAX instead of form submit)
- Fix "Open on your phone" button formatting
- Consider: Email/SMS reminders, progress streaks, sharing features
