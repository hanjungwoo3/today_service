# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a PHP-based service management system with multiple sub-applications:
- **Main App** (root): Meeting and ministry service scheduling system with MySQL backend
- **Calendar** (`c/`): Monthly calendar with schedule notes and JSON storage
- **Meeting Program Manager** (`s/`): Weekly meeting program management with web scraping
- **Timer** (`t/`): Presentation timer with music playback

## Development Environment

### Local Server (Podman + nginx + PHP)

로컬 개발 환경은 Podman 컨테이너(nginx) + PHP 내장 서버로 구성:

```bash
# PHP 내장 서버 (nginx에서 proxy_pass로 연결)
php -S localhost:9000

# nginx 컨테이너 (podman) — SSL 역방향 프록시
# 포트: 0.0.0.0:8443 → 443 (macOS에서 443은 권한 필요하므로 8443 사용)
# 프록시: https://ys1914.com:8443 → http://host.containers.internal:9000
podman start nginx-ssl

# 포트포워딩: 443 → 8443 (sudo 필요 — 터미널에서 직접 실행)
# 재부팅 시 초기화됨
echo "rdr pass on lo0 inet proto tcp from any to 127.0.0.1 port 443 -> 127.0.0.1 port 8443" | sudo pfctl -ef -
```

- **접속 URL**: `https://ys1914.com` (포트포워딩 후) 또는 `https://ys1914.com:8443`
- **hosts 파일**: `127.0.0.1 ys1914.com`
- **DB**: MariaDB 컨테이너 (`mysql-test`, 포트 3306)

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

## Custom Files (Upstream 머지 시 주의)

이 프로젝트는 upstream 기본 시스템 위에 커스텀 파일을 추가하여 사용합니다.
Upstream 머지 시 아래 파일들은 충돌이 발생하지 않도록 주의하세요.

### 신규 생성 파일 (upstream에 없음 — 충돌 없음)

| 파일 | 설명 |
|------|------|
| `include/custom_board_top.php` | 게시판 상단 커스텀 네비게이션 카드 (평일집회/주말집회/봉사인도/청소·마이크/짝배정) |
| `include/custom_home_assignments.php` | 홈 화면 "나의 배정 특권" 섹션 |
| `pages/meeting_program.php` | 평일집회 계획표 (`s/` iframe 래퍼) |
| `pages/public_talk.php` | 주말집회(공개강연) 계획표 (`s/` iframe 래퍼) |
| `pages/service_guide_calendar.php` | 봉사인도 계획표 (`c/` iframe 래퍼) |
| `pages/duty_schedule.php` | 청소/마이크/안내인/연사음료 계획표 (`s/duty_view.php` iframe 래퍼) |
| `pages/ministry_record.php` | 호별봉사 짝배정 현황 (`m/` iframe 래퍼) |
| `s/talk_view.php` | 공개강연 읽기 전용 뷰 |
| `s/talk_admin.php` | 공개강연 관리자 편집 |
| `s/talk_api.php` | 공개강연 API (JSON 스토리지) |
| `s/talk_print.php` | 공개강연 인쇄용 |
| `s/duty_view.php` | 청소/마이크/안내인/연사음료 읽기 전용 뷰 |
| `s/duty_admin.php` | 청소/마이크/안내인/연사음료 관리자 편집 |
| `s/duty_api.php` | 청소/마이크/안내인/연사음료 API (JSON 스토리지) |
| `s/duty_print.php` | 청소/마이크/안내인/연사음료 인쇄용 |

### 기존 파일 수정 내역 (upstream 머지 시 충돌 가능)

| 파일 | 변경량 | 충돌위험 | 수정 내용 |
|------|--------|----------|-----------|
| `.gitignore` | +6줄 | 낮음 | `.dev/`, `docs/` 무시 규칙 추가 (파일 끝에 append) |
| `config.php` | +4/-2줄 | **중간** | `BASE_PATH` 계산 조건에 `/s/`, `/c/` 경로 추가 |
| `index.php` | +2줄 | 낮음 | `custom_home_assignments.php` include 1줄 (`file_exists` 가드) |
| `pages/board_list.php` | +1줄 | 낮음 | `custom_board_top.php` include 1줄 (`file_exists` 가드) |
| `pages/admin_member_form.php` | +1줄 | 낮음 | `$mb` 변수 기본값 초기화 (신규 등록 시 undefined 방지) |
| `pages/guide_assign_step.php` | +36/-1줄 | **중간** | navbar에 "짝배정으로 돌아가기" 버튼 + preselect 자동선택 JS |
| `m/index.php` | +83/-7줄 | **중간** | SQL에 `ms_id` 추가, 클릭 가능한 추천짝 카드, `goToAssign()`, localStorage 필터 저장 |
| `m/api/meetings.php` | +2/-1줄 | 낮음 | SQL/응답에 `ms_id` 필드 추가 |

#### 머지 후 수동 확인 필요 사항

1. **`config.php`** — `BASE_PATH` 계산 분기문이 upstream에서 변경되었는지 확인. `/s/`, `/c/` 경로 조건이 누락되면 하위 모듈 동작 불가
2. **`pages/guide_assign_step.php`** — navbar 구조(`<a>` → `<div>` 래핑)와 하단 preselect JS 블록 유지 확인
3. **`m/index.php`** — 변경량이 가장 크므로 upstream 변경과 수동 비교 필요

### 독립 모듈 (upstream과 무관)

| 디렉토리 | 설명 |
|-----------|------|
| `c/` | 봉사인도 캘린더 (JSON 스토리지) |
| `s/` | 평일집회/주말집회/청소마이크 프로그램 관리 |
| `m/` | 호별봉사 전도인 기록 |
| `t/` | 프레젠테이션 타이머 |

## Korean Holiday Integration

Calendar module fetches holidays from: https://holidays.hyunbin.page/basic.ics
- Holidays displayed in red (like Sundays)
- Data cached in memory during request
