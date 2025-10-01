# 월간 일정 관리 캘린더

PHP 기반의 월별 일정 관리 시스템입니다. 날짜별로 일정 메모와 3명의 이름을 입력하고, JSON 파일로 저장하며 자동 백업을 제공합니다.

## 기능

- 📅 월별 달력 뷰 (이전/다음/이번 달 이동)
- ✍️ 날짜별 일정 메모 및 이름 3명 입력
- 💾 JSON 파일로 데이터 저장 (년-월별)
- 🔄 자동 백업 (수정 시 기존 파일을 타임스탬프와 함께 백업)
- 🎨 날짜별 색상 구분:
  - 오늘: 빨간 배경에 흰 글씨
  - 지난 날: 회색 배경에 흰 글씨
  - 토요일: 파란색
  - 일요일/공휴일: 빨간색
- 📱 iframe 삽입용 읽기 전용 뷰 제공
- 🎯 요일별 시간대(오전/오후/저녁) 안내 메시지 및 색상 지정
- 🔁 이전 달 값 불러오기 (같은 주차 같은 요일 자동 매칭)
- 🏖️ 대한민국 공휴일 자동 표시

## 디렉토리 구조

```
google-cal/
├── admin.php              # 관리자 편집 페이지
├── index.php              # iframe용 읽기 전용 페이지
├── lib/
│   └── helpers.php        # PHP 헬퍼 함수
├── api/
│   └── calendar.php       # REST API 엔드포인트
├── assets/
│   ├── css/
│   │   └── style.css      # 메인 스타일시트
│   └── js/
│       └── app.js         # 프론트엔드 로직
└── storage/               # 데이터 저장 디렉토리
    ├── YYYY-MM.json       # 월별 데이터
    └── backups/
        └── YYYY-MM/
            └── timestamp.json  # 백업 파일
```

## 설치 및 실행

### 1. 저장소 클론
```bash
git clone <repository-url>
cd google-cal
```

### 2. 저장 디렉토리 권한 설정
```bash
mkdir -p storage/backups
chmod 775 storage
chmod 775 storage/backups
```

### 3. PHP 개발 서버 실행
```bash
php -S 0.0.0.0:8000
```

### 4. 브라우저 접속
- 읽기 전용 뷰: `http://localhost:8000/` 또는 `http://localhost:8000/index.php`
- 관리자 편집 페이지: `http://localhost:8000/admin.php`

## 사용 방법

### 관리자 편집 모드 (admin.php)
1. 브라우저에서 `http://localhost:8000/admin.php` 접속
2. 날짜별로 일정 메모와 이름 3명 입력
3. 요일별 시간대 안내 메시지 및 색상 설정 (선택사항)
4. "이전달 값 불러오기" 버튼으로 이전 달 데이터 자동 복사 (선택사항)
5. "저장하기" 버튼 클릭
6. 데이터는 `storage/YYYY-MM.json`에 저장되며, 기존 파일은 `storage/backups/YYYY-MM/timestamp.json`으로 백업됨

### 이전 달 값 불러오기
- 같은 주차의 같은 요일 데이터를 자동으로 매칭합니다
- 예: 11월 1일(토, 1주차) ← 10월 4일(토, 1주차)
- 요일별 시간대 안내도 함께 복사됩니다
- 입력만 되고 자동 저장되지 않으므로, "저장하기"를 눌러야 실제 저장됩니다

### iframe 삽입용 뷰 (index.php)
모바일 친화적인 작은 화면으로 최적화된 읽기 전용 달력입니다.

**주요 특징:**
- 모바일 최적화 디자인 (최대 너비 340px)
- 월별 일정 메모 목록 (날짜, 요일 포함)
- 요일별 시간대 안내 표 (입력값이 있는 요일만 표시)
- 이름별 색상 표시 (글자 색상으로 구분)
- 공휴일 자동 표시 (빨간색)
- 오늘 날짜 강조 (빨간색 테두리)

#### HTML에 iframe 삽입 예시:
```html
<iframe 
  src="http://localhost:8000/" 
  width="100%" 
  height="600"
  frameborder="0"
  style="border: none; border-radius: 8px;">
</iframe>
```

#### 특정 월 표시:
```html
<iframe 
  src="http://localhost:8000/?year=2025&month=10" 
  width="100%" 
  height="600"
  frameborder="0">
</iframe>
```

## 데이터 구조

### JSON 파일 형식 (storage/YYYY-MM.json)
```json
{
  "dates": {
    "2025-10-01": {
      "note": "일정 메모",
      "names": ["이름1", "이름2", "이름3"]
    },
    "2025-10-02": {
      "note": "",
      "names": ["", "이름2", ""]
    }
  },
  "schedule_guide": {
    "monday": {
      "morning": { "text": "오전 일정 안내", "color": "blue" },
      "afternoon": { "text": "오후 일정 안내", "color": "green" },
      "evening": { "text": "저녁 일정 안내", "color": "white" }
    },
    "tuesday": { ... },
    ...
  }
}
```

**색상 옵션:** `white`, `green`, `blue`, `red`

## API 엔드포인트

### GET `/api/calendar.php?year=YYYY&month=M`
특정 월의 데이터를 가져옵니다.

**응답 예시:**
```json
{
  "dates": {
    "2025-10-01": {
      "note": "일정 메모",
      "names": ["이름1", "이름2", "이름3"]
    }
  }
}
```

### POST `/api/calendar.php`
데이터를 저장합니다. 기존 파일은 자동으로 백업됩니다.

**요청 본문:**
```json
{
  "year": 2025,
  "month": 10,
  "entries": {
    "2025-10-01": {
      "note": "일정 메모",
      "names": ["이름1", "이름2", "이름3"]
    }
  }
}
```

### GET `/api/calendar.php?year=YYYY&month=M&backups=1`
특정 월의 백업 파일 목록을 가져옵니다.

## 배포

### Apache/Nginx 환경
1. 프로젝트 루트 디렉토리를 웹 서버의 document root로 설정
2. `storage/` 디렉토리가 웹 서버에서 쓰기 가능하도록 권한 설정
3. PHP 8.1 이상 필요

### 다른 프로젝트에 통합
이 프로그램을 다른 프로젝트의 서브 디렉토리에 넣어 사용할 수 있습니다:
```
your-project/
├── calendar/          # 이 프로젝트
│   ├── admin.php     # 관리자 페이지
│   ├── index.php     # 읽기 전용 뷰
│   ├── api/
│   ├── assets/
│   ├── lib/
│   └── storage/
└── ...
```

### 환경 변수
필요시 `admin.php`, `index.php`, `api/calendar.php` 상단의 타임존을 변경:
```php
date_default_timezone_set('Asia/Seoul');
```

## 공휴일 데이터
대한민국 공휴일 데이터는 https://holidays.hyunbin.page/basic.ics 에서 자동으로 가져옵니다.
- 공휴일은 일요일과 동일하게 빨간색으로 표시됩니다
- 데이터는 메모리에 캐시되어 성능에 영향을 주지 않습니다

## 주의사항
- `storage/` 디렉토리는 `.gitignore`에 포함되어 있으므로 Git에 커밋되지 않습니다
- 백업 파일은 자동으로 생성되며 수동 삭제가 필요할 수 있습니다
- PHP 8.1 이상이 필요합니다 (match 표현식 사용)

## 라이선스
MIT License
