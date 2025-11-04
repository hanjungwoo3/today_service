<?php

/**
 * API 헬퍼 함수들
 */

require_once 'scraper.php';

class MeetingDataManager {

    private $dataDir = __DIR__ . '/data';
    private $scraper;

    public function __construct() {
        $this->scraper = new MeetingLinkScraper();

        // data 디렉토리 확인
        if (!is_dir($this->dataDir)) {
            mkdir($this->dataDir, 0755, true);
        }
    }

    /**
     * 현재 주차 번호 가져오기
     */
    public function getCurrentWeek() {
        $date = new DateTime();
        return (int)$date->format('W');
    }

    /**
     * 현재 연도 가져오기
     */
    public function getCurrentYear() {
        return (int)date('Y');
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
        $data = [
            'year' => $year,
            'week' => $week,
            'url' => $linkData['url'],
            'date' => $program['date'],
            'bible_reading' => $program['bible_reading'],
            'sections' => [
                'treasures' => '성경에 담긴 보물',
                'ministry' => '야외 봉사에 힘쓰십시오',
                'living' => '그리스도인 생활'
            ],
            'program' => [],
            'assignments' => [
                'opening_remarks' => '',
                'closing_remarks' => '',
                'opening_prayer' => '',
                'closing_prayer' => ''
            ]
        ];

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

            $data['program'][] = [
                'title' => $item['title'],
                'duration' => $item['duration'],
                'assigned' => ['', ''],
                'section' => $section
            ];
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
        return file_put_contents($filePath, $json) !== false;
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
        $oldProgramMap = [];
        foreach ($oldData['program'] as $item) {
            $oldProgramMap[$item['title']] = $item['assigned'];
        }

        // 새 데이터에 기존 배정 정보 병합
        foreach ($newData['program'] as &$item) {
            if (isset($oldProgramMap[$item['title']])) {
                $oldAssigned = $oldProgramMap[$item['title']];
                // 배열인지 확인하고, 값이 있는지 체크
                if (is_array($oldAssigned)) {
                    $hasValue = false;
                    foreach ($oldAssigned as $val) {
                        if (!empty(trim($val))) {
                            $hasValue = true;
                            break;
                        }
                    }
                    if ($hasValue) {
                        $item['assigned'] = $oldAssigned;
                    }
                } elseif (!empty($oldAssigned)) {
                    // 하위 호환성: 문자열인 경우 배열로 변환
                    $item['assigned'] = [$oldAssigned, ''];
                }
            }
        }

        // 기본 배정 정보도 유지
        if (isset($oldData['assignments'])) {
            $newData['assignments'] = array_merge($newData['assignments'], array_filter($oldData['assignments'], function($v) {
                return !empty($v);
            }));
        }

        return $newData;
    }

    /**
     * 저장된 주차 목록 가져오기
     */
    public function getAvailableWeeks() {
        $files = glob($this->dataDir . '/*.json');
        $weeks = [];

        foreach ($files as $file) {
            $filename = basename($file, '.json');
            // 형식: YYYYWW (예: 202545)
            if (preg_match('/^(\d{4})(\d{2})$/', $filename, $matches)) {
                $year = (int)$matches[1];
                $week = (int)$matches[2];

                // 파일 내용 읽어서 배정없음 정보 확인
                $data = $this->load($year, $week);
                $noMeeting = !empty($data['no_meeting']) && $data['no_meeting'];
                $noMeetingTitle = $noMeeting ? ($data['no_meeting_title'] ?? '') : '';
                $noMeetingReason = $noMeeting ? ($data['no_meeting_reason'] ?? '') : '';

                $weeks[] = [
                    'year' => $year,
                    'week' => $week,
                    'filename' => $filename,
                    'no_meeting' => $noMeeting,
                    'no_meeting_title' => $noMeetingTitle,
                    'no_meeting_reason' => $noMeetingReason
                ];
            }
        }

        // 연도와 주차로 정렬
        usort($weeks, function($a, $b) {
            if ($a['year'] !== $b['year']) {
                return $b['year'] - $a['year']; // 연도 내림차순
            }
            return $b['week'] - $a['week']; // 주차 내림차순
        });

        return $weeks;
    }

    /**
     * 빈 데이터 구조 생성
     */
    public function createEmpty($year, $week) {
        return [
            'year' => $year,
            'week' => $week,
            'url' => '',
            'date' => '',
            'bible_reading' => '',
            'no_meeting' => false,
            'no_meeting_title' => '',
            'no_meeting_reason' => '',
            'sections' => [
                'treasures' => '성경에 담긴 보물',
                'ministry' => '야외 봉사에 힘쓰십시오',
                'living' => '그리스도인 생활'
            ],
            'program' => [
                [
                    'title' => '1. ',
                    'duration' => '10분',
                    'assigned' => ['', ''],
                    'section' => 'treasures'
                ],
                [
                    'title' => '2. 영적 보물 찾기',
                    'duration' => '10분',
                    'assigned' => ['', ''],
                    'section' => 'treasures'
                ],
                [
                    'title' => '3. 성경 낭독',
                    'duration' => '4분',
                    'assigned' => ['', ''],
                    'section' => 'treasures'
                ],
                [
                    'title' => '4. ',
                    'duration' => '3분',
                    'assigned' => ['', ''],
                    'section' => 'ministry'
                ],
                [
                    'title' => '5. ',
                    'duration' => '4분',
                    'assigned' => ['', ''],
                    'section' => 'ministry'
                ],
                [
                    'title' => '6. ',
                    'duration' => '5분',
                    'assigned' => ['', ''],
                    'section' => 'ministry'
                ],
                [
                    'title' => '7. ',
                    'duration' => '15분',
                    'assigned' => ['', ''],
                    'section' => 'living'
                ],
                [
                    'title' => '8. 회중 성서연구',
                    'duration' => '30분',
                    'assigned' => ['', ''],
                    'section' => 'living'
                ]
            ],
            'assignments' => [
                'opening_remarks' => '',
                'closing_remarks' => '',
                'opening_prayer' => '',
                'closing_prayer' => ''
            ]
        ];
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

            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'save':
            $year = (int)$_POST['year'];
            $week = (int)$_POST['week'];
            $data = json_decode($_POST['data'], true);

            $success = $manager->save($year, $week, $data);
            echo json_encode(['success' => $success]);
            break;

        case 'refresh':
            $year = (int)$_POST['year'];
            $week = (int)$_POST['week'];
            $data = $manager->getData($year, $week, true);

            if ($data === null) {
                echo json_encode(['success' => false, 'error' => '데이터를 가져올 수 없습니다.']);
            } else {
                echo json_encode(['success' => true, 'data' => $data]);
            }
            break;

        case 'fetch':
            $year = (int)$_POST['year'];
            $week = (int)$_POST['week'];

            // 웹에서 데이터 가져오기
            $data = $manager->fetchFromWeb($year, $week);

            if ($data === null) {
                echo json_encode(['success' => false, 'error' => '웹에서 데이터를 가져올 수 없습니다.']);
            } else {
                // 기존 배정 정보와 병합
                $data = $manager->mergeAssignments($year, $week, $data);

                // 임시 파일에 저장
                $manager->saveTempData($year, $week, $data);

                echo json_encode(['success' => true, 'data' => $data]);
            }
            break;

        case 'list_weeks':
            $weeks = $manager->getAvailableWeeks();
            echo json_encode(['success' => true, 'weeks' => $weeks]);
            break;

        case 'delete':
            $year = (int)$_POST['year'];
            $week = (int)$_POST['week'];
            $success = $manager->delete($year, $week);

            if ($success) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => '삭제에 실패했습니다.']);
            }
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
    exit;
}

?>
