# AGENTS.md - Scripture Scheduler Codebase Guidelines

## Project Overview

A Laravel web application for creating personalized scripture reading schedules. Teachers create curricula with required chapters, students join via enrollment codes and create personalized reading plans balanced by word count rather than chapter count.

**Tech Stack**: Laravel 12, Livewire 3, Tailwind CSS, Alpine.js, SQLite

---

## Build/Run Commands

```bash
# Install dependencies
composer install
npm install

# Set up environment
cp .env.example .env
php artisan key:generate

# Database setup
php artisan migrate

# Build frontend assets
npm run build

# Local development
php artisan serve
npm run dev  # (in separate terminal for Vite HMR)

# Production build
npm run build
```

### Database

SQLite database at `database/database.sqlite` with 41,995 scripture verses pre-loaded.

### Legacy Code

Legacy PHP files are preserved in `legacy/` folder. Do NOT delete this folder.

---

## Code Style Guidelines

### Laravel Conventions

**Directory Structure**:
```
app/
├── Http/Controllers/     # Route controllers
├── Livewire/            # Livewire components
├── Models/              # Eloquent models
└── Services/            # Business logic (ScheduleCalculator, ScriptureLinkGenerator)

resources/views/
├── layouts/             # app.blade.php, guest.blade.php
├── components/          # Blade components
├── livewire/            # Livewire component views
├── plans/               # Plan-related views
├── curricula/           # Curriculum views
└── exports/             # Export views (table, calendar)
```

**Naming Conventions**:
- Controllers: `PascalCase` with `Controller` suffix (e.g., `PlanController`)
- Models: `PascalCase` singular (e.g., `Plan`, `Curriculum`)
- Livewire: `PascalCase` (e.g., `PlanGenerator`, `PlanViewer`)
- Views: `kebab-case.blade.php`
- Routes: `plans.show`, `curricula.index` (resource naming)

**Eloquent Patterns**:
```php
// Relationships
public function user(): BelongsTo
{
    return $this->belongsTo(User::class);
}

// JSON casting for schedule/progress data
protected $casts = [
    'volumes' => 'array',
    'schedule' => 'array',
    'progress' => 'array',
];
```

### Livewire Conventions

```php
// Component properties with validation
public string $startDate = '';
public array $selectedVolumes = [3];

// Wire model binding
public function updatedSearchQuery(): void
{
    // React to property changes
}

// Actions
public function toggleProgress(string $date): void
{
    $this->plan->toggleProgress($date);
}
```

**Blade with Livewire**:
```blade
{{-- Wire model binding --}}
<input type="date" wire:model="startDate">

{{-- Click handlers with loading states --}}
<button wire:click="toggleProgress('{{ $date }}')"
        wire:loading.attr="disabled"
        wire:target="toggleProgress('{{ $date }}')">
    <span wire:loading wire:target="toggleProgress('{{ $date }}')">...</span>
    <span wire:loading.remove wire:target="toggleProgress('{{ $date }}')">Toggle</span>
</button>
```

### Alpine.js Conventions

```javascript
// Theme store (in layout)
Alpine.store('theme', {
    dark: localStorage.getItem('darkMode') === 'true',
    toggle() {
        this.dark = !this.dark;
        localStorage.setItem('darkMode', this.dark);
        document.documentElement.classList.toggle('dark', this.dark);
    }
});
```

### Tailwind CSS Conventions

**Dark Mode**: Uses `class` strategy via `darkMode: 'class'` in tailwind.config.js

```blade
{{-- Always include both light and dark variants --}}
<div class="bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100">
```

**Common Classes**:
```blade
{{-- Cards --}}
<div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">

{{-- Buttons --}}
<button class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md">

{{-- Form inputs --}}
<input class="w-full rounded-md border-gray-300 dark:border-gray-600 
              dark:bg-gray-700 dark:text-gray-100 
              focus:border-indigo-500 focus:ring-indigo-500">
```

---

## Data Structures

### Plan Model

```php
// Key fields
'user_id'           // Owner
'start_date'        // Carbon date
'end_date'          // Carbon date
'scheduling_method' // 'verse' or 'chapter'
'beginning_verse'   // Optional start point
'volumes'           // Array [1,2,3,4,5]
'total_words'       // Calculated total
'words_per_day'     // Target daily reading
'schedule'          // Array of daily readings
'progress'          // Array of date => boolean
'public_token'      // For sharing (nullable)
```

**Schedule Entry Structure**:
```php
[
    'date' => '2024-01-01',
    'reading' => '1 Nephi 1:20',
    'word_count' => 1500,
    'verse_count' => 20,           // or chapter_count
    'previous_reading' => '1 Nephi 1:0',
]
```

### Volume IDs

| ID | Volume |
|----|--------|
| 1 | Old Testament |
| 2 | New Testament |
| 3 | Book of Mormon |
| 4 | Doctrine and Covenants |
| 5 | Pearl of Great Price |

---

## URL Routing

| Route | Controller/View | Purpose |
|-------|-----------------|---------|
| `GET /` | redirect | → `/plans/create` |
| `GET /plans/create` | PlanGenerator (Livewire) | Create new plan |
| `GET /plans/{plan}` | PlanViewer (Livewire) | View/track plan |
| `GET /plans/{plan}/edit` | PlanEditor (Livewire) | Edit plan dates |
| `GET /plans/{plan}/calendar.ics` | CalendarController | ICS feed |
| `GET /plans/{plan}/export/csv` | ExportController | CSV download |
| `GET /plans/{plan}/export/table` | ExportController | Printable table |
| `GET /plans/{plan}/export/calendar` | ExportController | Calendar view |
| `GET /share/{token}` | PublicPlanController | Public share link |
| `GET /curricula` | CurriculumController | Teacher dashboard |
| `GET /curricula/create` | CurriculumEditor (Livewire) | Create curriculum |
| `GET /enrollments` | EnrollmentController | Student enrollments |

---

## Key Files Reference

| File | Purpose |
|------|---------|
| `app/Livewire/PlanGenerator.php` | Create new reading plans |
| `app/Livewire/PlanViewer.php` | View plan, toggle progress, recalculate |
| `app/Livewire/CurriculumEditor.php` | Create/edit curricula with chapter search |
| `app/Services/ScheduleCalculator.php` | Word-balanced schedule generation |
| `app/Services/ScriptureLinkGenerator.php` | ChurchofJesusChrist.org links |
| `app/Http/Controllers/CalendarController.php` | ICS feed generation |
| `app/Http/Controllers/ExportController.php` | CSV, table, calendar exports |
| `resources/js/app.js` | Alpine.js initialization |
| `resources/css/app.css` | Tailwind imports |
| `tailwind.config.js` | Tailwind config with dark mode |

---

## Common Patterns

### Progress Toggle (AJAX via Livewire)

```php
// In Livewire component
public function toggleProgress(string $date): void
{
    $this->plan->toggleProgress($date);
}

// In Plan model
public function toggleProgress(string $date): void
{
    $progress = $this->progress ?? [];
    $progress[$date] = !($progress[$date] ?? false);
    $this->update(['progress' => $progress]);
}
```

### Schedule Recalculation

```php
// Preserves completed readings, redistributes remaining
public function recalculate(): void
{
    $completedDates = array_keys(array_filter($this->plan->progress ?? []));
    // ... recalculate from today with remaining content
}
```

### Dark Mode (FOUC Prevention)

```blade
{{-- In <head> before CSS loads --}}
<script>
    if (localStorage.getItem('darkMode') === 'true') {
        document.documentElement.classList.add('dark');
    }
</script>
```

### Scripture Link Generation

```php
$generator = new ScriptureLinkGenerator();
$url = $generator->generate('1 Nephi 3:7');
// Returns: https://www.churchofjesuschrist.org/study/scriptures/bofm/1-ne/3?lang=eng#p7
```

---

## Important Notes

1. **Authentication**: Laravel Breeze with email/password
2. **Database**: SQLite with Eloquent ORM
3. **Frontend Build**: Vite with Tailwind CSS and Alpine.js
4. **Session**: Laravel default session handling
5. **No PDF Generation**: wkhtmltopdf not yet integrated (use print views)
6. **Legacy Folder**: `legacy/` contains original PHP files - DO NOT DELETE

---

## Development Tips

```bash
# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# Rebuild assets
npm run build

# Check routes
php artisan route:list

# Fresh database (caution: loses data)
php artisan migrate:fresh
```
