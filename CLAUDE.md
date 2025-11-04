# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a PHP-based service management system with multiple sub-applications:
- **Main App** (root): Meeting and ministry service scheduling system with MySQL backend
- **Calendar** (`c/`): Monthly calendar with schedule notes and JSON storage
- **Meeting Program Manager** (`s/`): Weekly meeting program management with web scraping
- **Timer** (`t/`): Presentation timer with music playback

## Development Environment

### Running the Development Server

```bash
# Run from repository root
php -S localhost:8000
```

### Calendar Module (c/)
```bash
cd c/
php -S localhost:8000
# Access at: http://localhost:8000/view.php (read-only)
#            http://localhost:8000/index.php (admin edit)
```

### Git Configuration

This repository uses two GitHub accounts. Before pushing:

```bash
# Switch to appropriate account
gh auth switch --user hanjungwoo3    # For this repo
gh auth switch --user cafe24-jwhan   # For cafe24 repo

# Push to origin
git push origin main
```

Local git config is already set for `hanjungwoo3` account.

## Architecture

### Main Application (Root Directory)

**Entry Points:**
- `index.php` - Main dashboard showing today's meetings
- `login.php` / `logincheck.php` / `logout.php` - Authentication flow
- `config.php` - Database connection, security filters, session management
- `functions.php` - Core utility functions
- `header.php` / `footer.php` - Common layout components

**Core Structure:**
- Database-driven with MariaDB/MySQL backend
- Session-based authentication (60-minute expiration)
- SQL injection protection via `sql_escape_string()` and `array_map_deep()`
- Extract protection for super-globals ($_POST, $_GET, etc.)
- `BASE_PATH` dynamic calculation for subfolder deployments

**Key Directories:**
- `pages/` - Feature pages (meeting lists, schedules, etc.)
- `include/` - Reusable components
- `core/` - Core classes (class.core.php, class.territory.php, etc.)
- `v_data/` - Data views
- `classes/` - Third-party libraries (PHPExcel, ZipStream, PhpSpreadsheet)

### Calendar Module (c/)

**Architecture:**
- Standalone PHP application with JSON file storage
- No database dependency
- Mobile-first responsive design (max-width: 380px)

**Key Files:**
- `view.php` - Read-only calendar view (iframe-embeddable)
- `index.php` - Admin edit interface
- `lib/helpers.php` - Calendar logic (week building, data loading, holidays)
- `api/calendar.php` - REST API for CRUD operations
- `storage/YYYY-MM.json` - Monthly data files
- `storage/backups/YYYY-MM/timestamp.json` - Automatic backups

**Data Structure:**
```json
{
  "dates": {
    "YYYY-MM-DD": {
      "note": "메모",
      "names": ["이름1", "이름2", "이름3"]
    }
  },
  "schedule_guide": {
    "monday": {
      "morning": {"text": "안내", "color": "blue"},
      "afternoon": {"text": "안내", "color": "green"},
      "evening": {"text": "안내", "color": "white"}
    }
  }
}
```

**Integration Modes:**
1. **Local Development**: Create `config.php` with `define('LOCAL_MODE', true);` to bypass authentication
2. **Production**: Integrates with parent directory's `config.php` for user authentication and admin checks

**Mobile Optimizations:**
- Time labels ("오전/오후/저녁") hidden on mobile via `@media (max-width: 768px)`
- No right padding/spacing on mobile for full-width calendar display

### Meeting Program Manager (s/)

**Architecture:**
- Web scraper that fetches meeting programs from external source
- JSON-based data storage (weekly files: `YYYY-WW.json`)
- Week-based navigation

**Key Files:**
- `index.php` - UI for viewing/editing weekly programs
- `api.php` - Data management class (`MeetingDataManager`)
- `scraper.php` - Web scraping logic for fetching program data
- `service.php` - Service interface for program operations
- `data/YYYY-WW.json` - Weekly program data

**Program Structure:**
Programs are categorized into three sections:
- `treasures` (보물) - Items 1-3
- `ministry` (봉사) - Items 4-6
- `living` (생활) - Items 7+

### Timer Application (t/)

**Architecture:**
- Session + JSON file for settings persistence
- Auto-start functionality based on day of week
- Music playback integration

**Key Files:**
- `index.php` - Timer configuration page
- `timer.php` - Timer display page
- `timer_settings.json` - Saved timer settings
- `music_list.json` - Music library data

**Auto-Start Logic:**
- Sunday: Timer completes at 13:00
- Weekdays: Timer completes at 19:30
- Auto-start time calculated backwards from completion time

## Common Development Tasks

### Calendar Module

**Creating Local Development Environment:**
```bash
cd c/
cp config.php.sample config.php
# Edit config.php and set: define('LOCAL_MODE', true);
mkdir -p storage/backups
chmod 775 storage storage/backups
```

**Testing Calendar Changes:**
Mobile view requires testing at different screen widths. The design is optimized for 380px max-width.

### Working with JSON Data

Calendar and Meeting Manager use JSON files for storage:
- Always backup before modifying
- Maintain the expected JSON structure
- Validate JSON after manual edits

### Database Work (Main App)

Database credentials are in `config_custom.php` (not in repo).
Table definitions are in `config_table.php`.

Common table naming pattern:
- Tables are defined as constants (e.g., `MEETING_TABLE`, `MEETING_ADD_TABLE`)
- Use constants in queries, not hardcoded names

## PHP Version Compatibility

- Main app: PHP 7.3+ or PHP 8.3+ (MariaDB 5.5+ or 10.6+)
- Calendar module: PHP 8.1+ (uses match expressions)
- Code uses legacy patterns (`@extract()`, `$mysqli` global) for backward compatibility

## Security Notes

- SQL injection protection via custom escape functions (not prepared statements)
- Super-global extraction protection in `config.php`
- P3P header for cross-domain cookie compatibility
- Admin checks via `is_admin()` function integration

## Mobile Responsiveness

All modules support mobile:
- Calendar: 380px max container width
- Main app: Bootstrap-based responsive grid
- Timer: Full-screen optimized display

## Korean Holiday Integration

Calendar module fetches holidays from: https://holidays.hyunbin.page/basic.ics
- Holidays displayed in red (like Sundays)
- Data cached in memory during request
