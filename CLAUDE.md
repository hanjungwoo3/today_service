# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a PHP-based service management system with multiple sub-applications:
- **Main App** (root): Meeting and ministry service scheduling system with MySQL backend
- **Calendar** (`c/`): 봉사인도 월별 캘린더 (JSON storage)
- **Program Manager** (`s/`): 평일집회 프로그램, 공개강연 계획표, 청소/마이크/안내인/연사음료 계획표
- **Ministry Record** (`m/`): 호별봉사 짝배정 현황 및 전도인 기록
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
- `pages/` - Feature pages (meeting lists, schedules, iframe 래퍼 등)
- `include/` - Reusable components (`custom_board_top.php`: 홈 네비게이션 카드, `custom_home_assignments.php`: 홈 배정특권)
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

### Program Manager (s/)

`s/` 디렉토리는 세 가지 독립 모듈을 포함:

#### 1) 평일집회 프로그램 (index, api, view, print, print2)
- Web scraper로 외부 소스에서 프로그램 데이터 수집
- JSON 스토리지 (주간 파일: `data/YYYY-WW.json`)
- `api.php` — `MeetingDataManager` 클래스
- 프로그램 섹션: `treasures`(보물), `ministry`(봉사), `living`(생활)

#### 2) 공개강연 계획표 (talk_view, talk_admin, talk_api, talk_print)
- JSON 스토리지 (`data/talks.json`)
- `talk_api.php` — `TalkDataManager` 클래스
- 연사/사회/낭독/기도 배정 관리

#### 3) 청소/마이크/안내인/연사음료 (duty_view, duty_admin, duty_api, duty_print)
- JSON 스토리지 (연도별: `data/duty_YYYY.json`)
- `duty_api.php` — `DutyDataManager` 클래스
- 월별 상반기/하반기 배정, 청소집단, 연사음료 관리

**공통 패턴:** 각 모듈은 `*_view.php`(읽기), `*_admin.php`(편집), `*_api.php`(데이터), `*_print.php`(인쇄) 구조

### Ministry Record (m/)

**Architecture:**
- 호별봉사 전도인 기록 및 추천짝 배정
- MySQL backend (t_meeting 테이블 활용)
- 구역배정(guide_assign_step.php)과 연동

**Key Files:**
- `index.php` - 전도인 기록 메인 UI (추천짝 카드, goToAssign 연동)
- `api/meetings.php` - 모임 목록 AJAX API
- `config.php` - 로컬 개발 모드 설정

### Territory Messaging (구역 쪽지)

**Architecture:**
- 배정된 구역 멤버 간 간단한 쪽지(채팅) 기능
- MySQL backend (독립 테이블 2개)
- 팝업 채팅 창 방식 (fixed position overlay) — 페이지 DOM과 완전 독립
- `footer.php`에 포함되어 모든 페이지에서 동작 가능
- 적응형 폴링 (5초→10초→30초→60초), 패널 닫으면 폴링 중지
- 데스크톱: 화면 중앙 340px 모달 + 반투명 백드롭, 모바일: 하단 전폭 패널 (55vh)
- Bootstrap 모달이 열리면 자동으로 쪽지 패널 닫힘 (`show.bs.modal` 이벤트)
- 새 쪽지 도착 시 토스트 알림 (z-index 99999, Bootstrap 모달 위에 표시)

**DB Tables (upstream과 무관, 독립 테이블):**
- `t_territory_message` — 쪽지 내용 (tm_id, tt_id, tm_type, mb_id, mb_name, tm_message, tm_datetime)
- `t_territory_message_read` — 사용자별 읽음 포인터 (tt_id, tm_type, mb_id, last_read_id)
- `tm_type`: 'T'=호별구역, 'D'=전시대

**Key Files:**
- `pages/territory_msg_api.php` — 메시지 CRUD API (unread_counts, load, poll, send)
- `js/territory_msg.js` — 클라이언트 팝업/폴링/전송 로직 (TerritoryMsg 모듈)
- `footer.php` — 팝업 컨테이너 (#tmsg-popup), 백드롭 (#tmsg-backdrop), JS 로드, CSS, 클릭 핸들러

**Auto Cleanup:**
- API 호출 1/50 확률로 오래된 메시지 자동 정리
- 배정일(`tt_assigned_date`) 지난 구역 메시지 삭제
- 안전망: 하루 이상 된 메시지 삭제

**서버 배포 시 테이블 생성 필요:**
```sql
CREATE TABLE t_territory_message (
    tm_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tt_id INT UNSIGNED NOT NULL,
    tm_type CHAR(1) NOT NULL DEFAULT 'T',
    mb_id INT UNSIGNED NOT NULL,
    mb_name VARCHAR(50) NOT NULL,
    tm_message TEXT NOT NULL,
    tm_datetime DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tt_type_datetime (tt_id, tm_type, tm_datetime)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE t_territory_message_read (
    tt_id INT UNSIGNED NOT NULL,
    tm_type CHAR(1) NOT NULL DEFAULT 'T',
    mb_id INT UNSIGNED NOT NULL,
    last_read_id INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (tt_id, tm_type, mb_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

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

Calendar, Meeting Program, Public Talk, Duty Schedule 모듈은 JSON 파일 스토리지 사용:
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
| `include/custom_board_top.php` | 홈 화면 커스텀 네비게이션 카드 (평일집회/주말집회/봉사인도/청소·마이크/짝배정, PC 5열 모바일 3열) |
| `include/custom_home_assignments.php` | 홈 화면 "나의 배정 특권" 섹션 |
| `pages/meeting_program.php` | 평일집회 계획표 (`s/` iframe 래퍼) |
| `pages/public_talk.php` | 주말집회(공개강연) 계획표 (`s/` iframe 래퍼) |
| `pages/service_guide_calendar.php` | 봉사인도 계획표 (`c/` iframe 래퍼) |
| `pages/duty_schedule.php` | 청소/마이크/안내인/연사음료 계획표 (`s/duty_view.php` iframe 래퍼) |
| `pages/ministry_record.php` | 호별봉사 짝 배정 (`m/` iframe 래퍼) |
| `s/talk_view.php` | 공개강연 읽기 전용 뷰 |
| `s/talk_admin.php` | 공개강연 관리자 편집 |
| `s/talk_api.php` | 공개강연 API (JSON 스토리지) |
| `s/talk_print.php` | 공개강연 인쇄용 |
| `s/duty_view.php` | 청소/마이크/안내인/연사음료 읽기 전용 뷰 |
| `s/duty_admin.php` | 청소/마이크/안내인/연사음료 관리자 편집 |
| `s/duty_api.php` | 청소/마이크/안내인/연사음료 API (JSON 스토리지) |
| `s/duty_print.php` | 청소/마이크/안내인/연사음료 인쇄용 |
| `pages/territory_msg_api.php` | 구역 쪽지 API (MySQL, 4개 액션: unread_counts/load/poll/send) |
| `js/territory_msg.js` | 구역 쪽지 클라이언트 (팝업 채팅 창, 적응형 폴링, TerritoryMsg 모듈) |

### 기존 파일 수정 내역 (upstream 머지 시 충돌 가능)

| 파일 | 변경량 | 충돌위험 | 수정 내용 |
|------|--------|----------|-----------|
| `.gitignore` | +6줄 | 낮음 | `.dev/`, `docs/` 무시 규칙 추가 (파일 끝에 append) |
| `config.php` | +4/-2줄 | **중간** | `BASE_PATH` 계산 조건에 `/s/`, `/c/` 경로 추가 |
| `index.php` | +5줄 | 낮음 | `custom_board_top.php`, `custom_home_assignments.php` include |
| `footer.php` | +35줄 | 낮음 | 구역 쪽지 팝업 컨테이너/JS/CSS (</body> 직전) |
| `pages/admin_member_form.php` | +1줄 | 낮음 | `$mb` 변수 기본값 초기화 (신규 등록 시 undefined 방지) |
| `pages/guide_assign_step.php` | +40줄 | **중간** | 탭 내비에 "호별봉사 짝 배정" 탭 추가 + preselect 자동선택 JS |
| `m/index.php` | +85/-7줄 | **중간** | SQL에 `ms_id` 추가, 클릭 가능한 추천짝 카드, `goToAssign()`, localStorage 필터 저장, 툴바 헤더 숨김 |
| `m/api/meetings.php` | +2/-1줄 | 낮음 | SQL/응답에 `ms_id` 필드 추가 |
| `pages/today_service_list.php` | +5줄 | 낮음 | 배정 카드에 구역 쪽지 버튼 추가 |
| `include/territory_view_list.php` | +1줄 | 낮음 | `$new_compare_address` 변수 초기화 (PHP 8 경고 수정) |

#### 머지 후 수동 확인 필요 사항

1. **`config.php`** — `BASE_PATH` 계산 분기문이 upstream에서 변경되었는지 확인. `/s/`, `/c/` 경로 조건이 누락되면 하위 모듈 동작 불가
2. **`pages/guide_assign_step.php`** — 탭 내비에 "호별봉사 짝 배정" 탭과 하단 preselect JS 블록 유지 확인
3. **`m/index.php`** — 변경량이 가장 크므로 upstream 변경과 수동 비교 필요

### 독립 모듈 (upstream과 무관)

| 디렉토리 | 설명 |
|-----------|------|
| `c/` | 봉사인도 캘린더 (JSON 스토리지) |
| `s/` | 평일집회/주말집회/청소마이크 프로그램 관리 |
| `m/` | 호별봉사 전도인 기록 |
| `t/` | 프레젠테이션 타이머 |

### Upstream 머지 시 주의사항 (v2.5.14 경험 기반)

1. **modify/delete 충돌 주의**: upstream에서 `c/`, `m/`, `s/`, `t/` 파일이 삭제되면 git이 modify/delete 충돌을 발생시킨다. `git checkout --ours`로 우리 쪽을 유지해야 하는데, **upstream에도 있던 파일**(예: `s/scraper.php`)이 함께 삭제될 수 있다. 머지 후 반드시 `git diff HEAD~1 HEAD --name-status -- c/ m/ s/ t/ | grep "^D"`로 의도치 않은 삭제 확인 필요.
2. **`index.php`**: v2.5.14에서 취소 봉사 쿼리가 서브쿼리 → INNER JOIN으로 변경됨. `custom_board_top.php`, `custom_home_assignments.php` include 라인 유지 확인.
3. **`config.php`**: `/s/`, `/c/` 경로 조건이 `BASE_PATH` 분기문에 포함되어야 함.

## Upstream Version

현재 적용된 upstream 버전: **v2.5.14**
- upstream 브랜치: 원본 배포 파일 (커스텀 변경 없음)
- main 브랜치: upstream + 커스텀 파일/수정

## Korean Holiday Integration

Calendar module fetches holidays from: https://holidays.hyunbin.page/basic.ics
- Holidays displayed in red (like Sundays)
- Data cached in memory during request
