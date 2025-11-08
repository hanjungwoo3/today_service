<?php

/**
 * API 헬퍼 함수들
 */

require_once 'scraper.php';

class MeetingDataManager {

    private $dataDir;
    private $scraper;

    public function __construct() {
        $this->dataDir = dirname(__FILE__) . '/data';
        $this->scraper = new MeetingLinkScraper();

        // data 디렉토리 확인
        if (!is_dir($this->dataDir)) {
            mkdir($this->dataDir, 0755, true);
        }
    }

    /**
     * 평일집회 요일 설정 파일 경로
     */
    private function getWeekdayFile() {
        return $this->dataDir . '/weekday.json';
    }

    /**
     * 평일집회 요일 가져오기 (1=월요일 ~ 7=일요일)
     * 기본값: 3 (수요일)
     */
    public function getMeetingWeekday() {
        $file = $this->getWeekdayFile();
        if (file_exists($file)) {
            $content = file_get_contents($file);
            $data = json_decode($content, true);
            if (isset($data['weekday']) && $data['weekday'] >= 1 && $data['weekday'] <= 7) {
                return (int)$data['weekday'];
            }
        }
        return 3; // 기본값: 수요일
    }

    /**
     * 평일집회 요일 저장하기
     */
    public function setMeetingWeekday($weekday) {
        $weekday = (int)$weekday;
        if ($weekday < 1 || $weekday > 7) {
            return false;
        }
        $data = array('weekday' => $weekday);
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        return file_put_contents($this->getWeekdayFile(), $json) !== false;
    }

    /**
     * 현재 주차 번호 가져오기
     * 평일집회 요일이 지나면 다음 주를 반환
     */
    public function getCurrentWeek() {
        $date = new DateTime();
        $dayOfWeek = (int)$date->format('N'); // 1(월) ~ 7(일)
        $meetingWeekday = $this->getMeetingWeekday();

        // 평일집회 요일이 지나면 다음 주로 이동
        if ($dayOfWeek > $meetingWeekday) {
            $date->modify('+1 week');
        }

        return (int)$date->format('W');
    }

    /**
     * 현재 연도 가져오기
     * 평일집회 요일이 지나면 다음 주 기준 연도 반환
     */
    public function getCurrentYear() {
        $date = new DateTime();
        $dayOfWeek = (int)$date->format('N'); // 1(월) ~ 7(일)
        $meetingWeekday = $this->getMeetingWeekday();

        // 평일집회 요일이 지나면 다음 주로 이동
        if ($dayOfWeek > $meetingWeekday) {
            $date->modify('+1 week');
        }

        return (int)$date->format('o'); // 'o'는 ISO 8601 연도 (주차 기준)
    }

    /**
     * JSON 파일 경로 생성
     */
    private function getFilePath($year, $week) {
        $weekStr = str_pad($week, 2, '0', STR_PAD_LEFT);
        return $this->dataDir . '/' . $year . $weekStr . '.json';
    }

    /**
     * 임시 JSON 파일 경로 생성
     */
    private function getTempFilePath($year, $week) {
        $weekStr = str_pad($week, 2, '0', STR_PAD_LEFT);
        return $this->dataDir . '/' . $year . $weekStr . '_temp.json';
    }

    /**
     * JSON 파일 존재 여부 확인
     */
    public function exists($year, $week) {
        return file_exists($this->getFilePath($year, $week));
    }

    /**
     * 웹에서 데이터 스크래핑하여 가져오기
     */
    public function fetchFromWeb($year, $week) {
        // 1. 주차 링크 가져오기
        $linkData = $this->scraper->getWeeklyLink($year, $week);

        if (!$linkData) {
            return null;
        }

        // 2. 프로그램 파싱
        $program = $this->scraper->parseMeetingProgram($linkData['url']);

        if (!$program) {
            return null;
        }

        // 3. 데이터 구조 생성
        $data = array(
            'year' => $year,
            'week' => $week,
            'url' => $linkData['url'],
            'date' => $this->getMeetingDateForWeek($year, $week), // 평일집회 요일 날짜 (기존 날짜가 있으면 mergeAssignments에서 유지됨)
            'bible_reading' => $program['bible_reading'],
            'sections' => array(
                'treasures' => '성경에 담긴 보물',
                'ministry' => '야외 봉사에 힘쓰십시오',
                'living' => '그리스도인 생활'
            ),
            'program' => array(),
            'assignments' => array(
                'opening_remarks' => '',
                'closing_remarks' => '',
                'opening_prayer' => '',
                'closing_prayer' => ''
            )
        );

        // 프로그램 항목에 assigned와 section 필드 추가
        foreach ($program['program'] as $item) {
            $title = $item['title'];
            $section = 'living'; // 기본값

            // 번호로 섹션 추정
            if (preg_match('/^(\d+)\./', $title, $matches)) {
                $num = (int)$matches[1];
                if ($num >= 1 && $num <= 3) {
                    $section = 'treasures';
                } elseif ($num >= 4 && $num <= 6) {
                    $section = 'ministry';
                } else {
                    $section = 'living';
                }
            }

            $data['program'][] = array(
                'title' => $item['title'],
                'duration' => $item['duration'],
                'assigned' => array('', ''),
                'section' => $section
            );
        }

        return $data;
    }

    /**
     * JSON 파일 로드 (임시 파일 우선)
     */
    public function load($year, $week) {
        // 임시 파일이 있으면 우선 로드
        $tempFilePath = $this->getTempFilePath($year, $week);
        if (file_exists($tempFilePath)) {
            $content = file_get_contents($tempFilePath);
            return json_decode($content, true);
        }

        // 일반 파일 로드
        $filePath = $this->getFilePath($year, $week);
        if (!file_exists($filePath)) {
            return null;
        }

        $content = file_get_contents($filePath);
        return json_decode($content, true);
    }

    /**
     * 실제 JSON 파일만 로드 (임시 파일 무시)
     */
    private function loadActualFile($year, $week) {
        $filePath = $this->getFilePath($year, $week);
        if (!file_exists($filePath)) {
            return null;
        }

        $content = file_get_contents($filePath);
        return json_decode($content, true);
    }

    /**
     * JSON 파일 저장
     */
    public function save($year, $week, $data) {
        $filePath = $this->getFilePath($year, $week);

        // 백업 디렉토리 생성
        $bakDir = $this->dataDir . '/bak';
        if (!is_dir($bakDir)) {
            mkdir($bakDir, 0755, true);
        }

        // 기존 파일이 있으면 백업
        if (file_exists($filePath)) {
            $weekStr = str_pad($week, 2, '0', STR_PAD_LEFT);
            $timestamp = date('YmdHis');
            $bakFileName = $year . $weekStr . '_' . $timestamp . '.json';
            $bakFilePath = $bakDir . '/' . $bakFileName;
            copy($filePath, $bakFilePath);
        }

        // 임시 파일 삭제
        $tempFilePath = $this->getTempFilePath($year, $week);
        if (file_exists($tempFilePath)) {
            unlink($tempFilePath);
        }

        $data['year'] = $year;
        $data['week'] = $week;

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $result = file_put_contents($filePath, $json) !== false;

        // 저장 성공 시 오래된 주차 자동 아카이빙
        if ($result) {
            $this->archiveOldWeeks();
        }

        return $result;
    }

    /**
     * 웹에서 가져온 데이터를 임시 파일에 저장
     */
    public function saveTempData($year, $week, $data) {
        $tempFilePath = $this->getTempFilePath($year, $week);

        $data['year'] = $year;
        $data['week'] = $week;

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        return file_put_contents($tempFilePath, $json) !== false;
    }

    /**
     * 데이터 가져오기 (없으면 웹에서 스크래핑)
     */
    public function getData($year, $week, $forceRefresh = false) {
        if (!$forceRefresh && $this->exists($year, $week)) {
            return $this->load($year, $week);
        }

        // 웹에서 가져오기
        $data = $this->fetchFromWeb($year, $week);

        if ($data && $forceRefresh) {
            // 새로고침 시 기존 배정 정보 병합
            $data = $this->mergeAssignments($year, $week, $data);
        }

        if ($data) {
            $this->save($year, $week, $data);
        }

        return $data;
    }

    /**
     * 기존 배정 정보를 새 데이터에 병합
     */
    public function mergeAssignments($year, $week, $newData) {
        // 실제 저장된 파일만 로드 (임시 파일 무시)
        $oldData = $this->loadActualFile($year, $week);

        if (!$oldData) {
            return $newData;
        }

        // 기존 프로그램 항목을 제목으로 매핑
        $oldProgramMap = array();
        foreach ($oldData['program'] as $item) {
            $oldProgramMap[$item['title']] = $item['assigned'];
        }

        // 새 데이터에 기존 배정 정보 병합
        foreach ($newData['program'] as $index => $item) {
            if (isset($oldProgramMap[$item['title']])) {
                $oldAssigned = $oldProgramMap[$item['title']];
                // 배열인지 확인하고, 값이 있는지 체크
                if (is_array($oldAssigned)) {
                    $hasValue = false;
                    foreach ($oldAssigned as $val) {
                        $trimmedVal = trim($val);
                        if (!empty($trimmedVal)) {
                            $hasValue = true;
                            break;
                        }
                    }
                    if ($hasValue) {
                        $newData['program'][$index]['assigned'] = $oldAssigned;
                    }
                } elseif (!empty($oldAssigned)) {
                    // 하위 호환성: 문자열인 경우 배열로 변환
                    $newData['program'][$index]['assigned'] = array($oldAssigned, '');
                }
            }
        }

        // 기본 배정 정보도 유지
        if (isset($oldData['assignments'])) {
            $filteredAssignments = array();
            foreach ($oldData['assignments'] as $key => $val) {
                if (!empty($val)) {
                    $filteredAssignments[$key] = $val;
                }
            }
            $newData['assignments'] = array_merge($newData['assignments'], $filteredAssignments);
        }

        // 기존 날짜 유지 (웹에서 가져온 날짜로 덮어쓰지 않음)
        if (isset($oldData['date']) && !empty($oldData['date'])) {
            $newData['date'] = $oldData['date'];
        }

        return $newData;
    }

    /**
     * 저장된 주차 목록 가져오기
     */
    public function getAvailableWeeks() {
        $files = glob($this->dataDir . '/*.json');
        $weeks = array();

        foreach ($files as $file) {
            $filename = basename($file, '.json');
            // 형식: YYYYWW (예: 202545)
            if (preg_match('/^(\d{4})(\d{2})$/', $filename, $matches)) {
                $year = (int)$matches[1];
                $week = (int)$matches[2];

                // 파일 내용 읽어서 배정없음 정보 확인
                $data = $this->load($year, $week);
                $noMeeting = !empty($data['no_meeting']) && $data['no_meeting'];
                $noMeetingTitle = $noMeeting ? (isset($data['no_meeting_title']) ? $data['no_meeting_title'] : '') : '';
                $noMeetingReason = $noMeeting ? (isset($data['no_meeting_reason']) ? $data['no_meeting_reason'] : '') : '';
                $date = isset($data['date']) ? $data['date'] : '';

                $weeks[] = array(
                    'year' => $year,
                    'week' => $week,
                    'filename' => $filename,
                    'date' => $date,
                    'no_meeting' => $noMeeting,
                    'no_meeting_title' => $noMeetingTitle,
                    'no_meeting_reason' => $noMeetingReason
                );
            }
        }

        // 연도와 주차로 정렬
        usort($weeks, array($this, 'compareWeeks'));

        return $weeks;
    }

    /**
     * 주차 정렬을 위한 비교 함수
     */
    private function compareWeeks($a, $b) {
        if ($a['year'] !== $b['year']) {
            return $b['year'] - $a['year']; // 연도 내림차순
        }
        return $b['week'] - $a['week']; // 주차 내림차순
    }

    /**
     * 특정 주차의 평일집회 요일 날짜를 계산 (형식: n월 j일)
     */
    public function getMeetingDateForWeek($year, $week) {
        $meetingWeekday = $this->getMeetingWeekday();

        // ISO 8601 주차 계산
        $jan4 = new DateTime($year . '-01-04');
        $jan4Day = (int)$jan4->format('N');
        $weekStart = clone $jan4;
        $weekStart->modify('-' . ($jan4Day - 1) . ' days');
        $weekStart->modify('+' . (($week - 1) * 7) . ' days');

        // 집회 요일로 이동 (월요일=1 기준)
        $currentDay = (int)$weekStart->format('N');
        $daysToAdd = $meetingWeekday - $currentDay;
        if ($daysToAdd < 0) {
            $daysToAdd += 7;
        }
        $meetingDate = clone $weekStart;
        $meetingDate->modify('+' . $daysToAdd . ' days');

        return $meetingDate->format('n월 j일');
    }

    /**
     * 빈 데이터 구조 생성
     */
    public function createEmpty($year, $week) {
        // 집회 요일 기준 날짜 계산
        $defaultDate = $this->getMeetingDateForWeek($year, $week);

        return array(
            'year' => $year,
            'week' => $week,
            'url' => '',
            'date' => $defaultDate,
            'bible_reading' => '',
            'no_meeting' => false,
            'no_meeting_title' => '',
            'no_meeting_reason' => '',
            'sections' => array(
                'treasures' => '성경에 담긴 보물',
                'ministry' => '야외 봉사에 힘쓰십시오',
                'living' => '그리스도인 생활'
            ),
            'program' => array(
                array(
                    'title' => '1. ',
                    'duration' => '10분',
                    'assigned' => array('', ''),
                    'section' => 'treasures'
                ),
                array(
                    'title' => '2. 영적 보물 찾기',
                    'duration' => '10분',
                    'assigned' => array('', ''),
                    'section' => 'treasures'
                ),
                array(
                    'title' => '3. 성경 낭독',
                    'duration' => '4분',
                    'assigned' => array('', ''),
                    'section' => 'treasures'
                ),
                array(
                    'title' => '4. ',
                    'duration' => '3분',
                    'assigned' => array('', ''),
                    'section' => 'ministry'
                ),
                array(
                    'title' => '5. ',
                    'duration' => '4분',
                    'assigned' => array('', ''),
                    'section' => 'ministry'
                ),
                array(
                    'title' => '6. ',
                    'duration' => '5분',
                    'assigned' => array('', ''),
                    'section' => 'ministry'
                ),
                array(
                    'title' => '7. ',
                    'duration' => '15분',
                    'assigned' => array('', ''),
                    'section' => 'living'
                ),
                array(
                    'title' => '8. 회중 성서연구',
                    'duration' => '30분',
                    'assigned' => array('', ''),
                    'section' => 'living'
                )
            ),
            'assignments' => array(
                'opening_remarks' => '',
                'closing_remarks' => '',
                'opening_prayer' => '',
                'closing_prayer' => ''
            )
        );
    }

    /**
     * 오래된 주차 파일 아카이빙
     * 현재 주차 기준 2주 이전 파일들을 archive 폴더로 이동
     */
    public function archiveOldWeeks() {
        $currentYear = $this->getCurrentYear();
        $currentWeek = $this->getCurrentWeek();

        // archive 디렉토리 생성
        $archiveDir = $this->dataDir . '/archive';
        if (!is_dir($archiveDir)) {
            mkdir($archiveDir, 0755, true);
        }

        $files = glob($this->dataDir . '/*.json');
        $archivedCount = 0;

        foreach ($files as $file) {
            $filename = basename($file, '.json');

            // 형식: YYYYWW (예: 202545)
            if (preg_match('/^(\d{4})(\d{2})$/', $filename, $matches)) {
                $fileYear = (int)$matches[1];
                $fileWeek = (int)$matches[2];

                // 현재 주차 기준 2주 이전인지 확인
                if ($this->isOlderThanWeeks($fileYear, $fileWeek, $currentYear, $currentWeek, 2)) {
                    // archive 폴더로 이동
                    $archivePath = $archiveDir . '/' . basename($file);
                    if (rename($file, $archivePath)) {
                        $archivedCount++;
                    }
                }
            }
        }

        return $archivedCount;
    }

    /**
     * 특정 주차가 현재 주차보다 N주 이전인지 확인
     */
    private function isOlderThanWeeks($fileYear, $fileWeek, $currentYear, $currentWeek, $weeksAgo) {
        // ISO 8601 주차 기준으로 날짜 계산
        $fileDate = $this->getDateFromWeek($fileYear, $fileWeek);
        $currentDate = $this->getDateFromWeek($currentYear, $currentWeek);

        // 2주 전 날짜 계산
        $cutoffDate = clone $currentDate;
        $cutoffDate->modify('-' . $weeksAgo . ' weeks');

        return $fileDate < $cutoffDate;
    }

    /**
     * 연도와 주차로부터 DateTime 객체 생성 (ISO 8601)
     */
    private function getDateFromWeek($year, $week) {
        $jan4 = new DateTime($year . '-01-04');
        $jan4Day = (int)$jan4->format('N');
        $weekStart = clone $jan4;
        $weekStart->modify('-' . ($jan4Day - 1) . ' days');
        $weekStart->modify('+' . (($week - 1) * 7) . ' days');
        return $weekStart;
    }

    /**
     * JSON 파일 삭제 (백업 후)
     */
    public function delete($year, $week) {
        $filePath = $this->getFilePath($year, $week);

        // 파일이 없으면 성공으로 처리
        if (!file_exists($filePath)) {
            return true;
        }

        // 백업 디렉토리 생성
        $bakDir = $this->dataDir . '/bak';
        if (!is_dir($bakDir)) {
            mkdir($bakDir, 0755, true);
        }

        // 백업 파일 생성
        $weekStr = str_pad($week, 2, '0', STR_PAD_LEFT);
        $timestamp = date('YmdHis');
        $bakFileName = $year . $weekStr . '_deleted_' . $timestamp . '.json';
        $bakFilePath = $bakDir . '/' . $bakFileName;

        // 백업 후 삭제
        if (copy($filePath, $bakFilePath)) {
            return unlink($filePath);
        }

        return false;
    }
}

// AJAX 요청 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json; charset=utf-8');

    $manager = new MeetingDataManager();
    $action = $_POST['action'];

    switch ($action) {
        case 'load':
            $year = (int)$_POST['year'];
            $week = (int)$_POST['week'];
            $data = $manager->getData($year, $week);

            if ($data === null) {
                $data = $manager->createEmpty($year, $week);
            }

            echo json_encode(array('success' => true, 'data' => $data));
            break;

        case 'save':
            $year = (int)$_POST['year'];
            $week = (int)$_POST['week'];
            $data = json_decode($_POST['data'], true);

            $success = $manager->save($year, $week, $data);
            echo json_encode(array('success' => $success));
            break;

        case 'refresh':
            $year = (int)$_POST['year'];
            $week = (int)$_POST['week'];
            $data = $manager->getData($year, $week, true);

            if ($data === null) {
                echo json_encode(array('success' => false, 'error' => '데이터를 가져올 수 없습니다.'));
            } else {
                echo json_encode(array('success' => true, 'data' => $data));
            }
            break;

        case 'fetch':
            $year = (int)$_POST['year'];
            $week = (int)$_POST['week'];

            // 웹에서 데이터 가져오기
            $data = $manager->fetchFromWeb($year, $week);

            if ($data === null) {
                echo json_encode(array('success' => false, 'error' => '웹에서 데이터를 가져올 수 없습니다.'));
            } else {
                // 기존 배정 정보와 병합
                $data = $manager->mergeAssignments($year, $week, $data);

                // 임시 파일에 저장
                $manager->saveTempData($year, $week, $data);

                echo json_encode(array('success' => true, 'data' => $data));
            }
            break;

        case 'list_weeks':
            $weeks = $manager->getAvailableWeeks();
            echo json_encode(array('success' => true, 'weeks' => $weeks));
            break;

        case 'delete':
            $year = (int)$_POST['year'];
            $week = (int)$_POST['week'];
            $success = $manager->delete($year, $week);

            if ($success) {
                echo json_encode(array('success' => true));
            } else {
                echo json_encode(array('success' => false, 'error' => '삭제에 실패했습니다.'));
            }
            break;

        case 'get_weekday':
            $weekday = $manager->getMeetingWeekday();
            echo json_encode(array('success' => true, 'weekday' => $weekday));
            break;

        case 'set_weekday':
            $weekday = (int)$_POST['weekday'];
            $success = $manager->setMeetingWeekday($weekday);
            echo json_encode(array('success' => $success));
            break;

        default:
            echo json_encode(array('success' => false, 'error' => 'Invalid action'));
    }
    exit;
}

?>
