# Scripture Scheduler

A Laravel web application for creating personalized scripture reading schedules. Balance your reading by **word count** rather than chapter count for a more consistent daily reading experience.

## Features

- **Word-Balanced Schedules**: Reading plans distributed by word count ensure consistent daily reading time
- **Multiple Scripture Volumes**: Old Testament, New Testament, Book of Mormon, Doctrine & Covenants, Pearl of Great Price
- **Flexible Scheduling**: Choose verse-by-verse or chapter-by-chapter progression
- **Custom Start Points**: Begin reading from any verse or chapter
- **Progress Tracking**: Mark readings complete with AJAX (no page reload)
- **Schedule Recalculation**: Catch up when you fall behind - preserves completed readings
- **Export Options**: CSV, printable table, calendar view, ICS calendar feed
- **Public Sharing**: Share read-only view of your plan via link
- **Dark Mode**: Toggle between light and dark themes
- **Curricula System**: Teachers create curricula, students join via enrollment codes

## Tech Stack

- **Backend**: Laravel 12, PHP 8.2+
- **Frontend**: Livewire 3, Alpine.js, Tailwind CSS
- **Database**: SQLite (41,995 scripture verses pre-loaded)
- **Build**: Vite

## Quick Start

```bash
# Clone and install
git clone <repository-url>
cd schedule
composer install
npm install

# Configure environment
cp .env.example .env
php artisan key:generate

# Database setup
php artisan migrate

# Build assets
npm run build

# Start development server
php artisan serve
# In another terminal:
npm run dev
```

Visit `http://localhost:8000` to create your first reading plan.

## Usage

### Creating a Plan

1. Select scripture volumes to read
2. Set start and end dates
3. Optionally choose a starting verse/chapter
4. Preview word count statistics
5. Create your plan

### Tracking Progress

- Click readings to mark them complete
- Use "Recalculate" to redistribute remaining readings from today
- Export to calendar apps via ICS feed

### Teacher Features

1. Navigate to `/curricula` to create a curriculum
2. Search and add required chapters
3. Share the enrollment code with students
4. Students join via `/enrollments/join`

## Project Structure

```
app/
├── Http/Controllers/    # Route controllers
├── Livewire/           # Interactive components
├── Models/             # Eloquent models
└── Services/           # Business logic

resources/views/
├── layouts/            # App layouts with dark mode
├── livewire/           # Livewire component views
├── plans/              # Plan-related views
├── curricula/          # Curriculum views
└── exports/            # Export views
```

## Routes

| Route | Purpose |
|-------|---------|
| `/plans/create` | Create new reading plan |
| `/plans/{id}` | View and track plan |
| `/plans/{id}/edit` | Edit plan dates |
| `/plans/{id}/calendar.ics` | ICS calendar feed |
| `/share/{token}` | Public plan view |
| `/curricula` | Teacher dashboard |
| `/enrollments` | Student enrollments |

## Development

```bash
# Clear caches
php artisan cache:clear && php artisan view:clear

# Check routes
php artisan route:list

# Fresh database (loses data!)
php artisan migrate:fresh
```

## Legacy Code

Original PHP implementation preserved in `legacy/` folder for reference. Do not delete.

## License

MIT
