<?php

date_default_timezone_set('Asia/Seoul');

class TalkDataManager
{
    private $dataDir;
    private $dataFile;
    private $backupDir;

    public function __construct()
    {
        $this->dataDir = dirname(__FILE__) . '/data';
        $this->dataFile = $this->dataDir . '/talks.json';
        $this->backupDir = $this->dataDir . '/talks_bak';

        if (!is_dir($this->dataDir)) {
            mkdir($this->dataDir, 0755, true);
        }
    }

    public function load()
    {
        if (!file_exists($this->dataFile)) {
            return array('talks' => array());
        }
        $content = file_get_contents($this->dataFile);
        $data = json_decode($content, true);
        if (!is_array($data) || !isset($data['talks'])) {
            return array('talks' => array());
        }
        return $data;
    }

    public function getDisplayStartDate()
    {
        $data = $this->load();
        return isset($data['display_start_date']) ? $data['display_start_date'] : '';
    }

    public function setDisplayStartDate($date)
    {
        $data = $this->load();
        $data['display_start_date'] = $date;
        return file_put_contents($this->dataFile, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) !== false;
    }

    public function save($talks)
    {
        if (!is_array($talks)) {
            return false;
        }

        // 날짜순 정렬
        usort($talks, function ($a, $b) {
            return strcmp($a['date'], $b['date']);
        });

        // 백업
        if (file_exists($this->dataFile)) {
            if (!is_dir($this->backupDir)) {
                mkdir($this->backupDir, 0755, true);
            }
            $timestamp = date('YmdHis');
            $backupFile = $this->backupDir . '/talks_' . $timestamp . '.json';
            copy($this->dataFile, $backupFile);
        }

        // 기존 설정 유지
        $existing = $this->load();
        $data = array('talks' => $talks);
        if (isset($existing['display_start_date'])) {
            $data['display_start_date'] = $existing['display_start_date'];
        }
        $result = file_put_contents($this->dataFile, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        if ($result !== false) {
            $this->cleanupOldBackups();
        }

        return $result !== false;
    }

    public function cleanupOldBackups()
    {
        if (!is_dir($this->backupDir)) {
            return;
        }

        $cutoff = new DateTime();
        $cutoff->modify('-6 months');
        $cutoffTimestamp = $cutoff->getTimestamp();

        $files = glob($this->backupDir . '/*.json');
        foreach ($files as $file) {
            if (filemtime($file) < $cutoffTimestamp) {
                @unlink($file);
            }
        }
    }
}

// AJAX 핸들러
if (isset($_POST['action']) || (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']))) {
    header('Content-Type: application/json; charset=utf-8');

    $action = $_POST['action'];
    $manager = new TalkDataManager();

    switch ($action) {
        case 'load':
            $data = $manager->load();
            echo json_encode(array('success' => true, 'data' => $data));
            break;

        case 'save':
            $talksJson = isset($_POST['talks']) ? $_POST['talks'] : '[]';
            $talks = json_decode($talksJson, true);
            if (!is_array($talks)) {
                echo json_encode(array('success' => false, 'error' => '잘못된 데이터 형식입니다.'));
                break;
            }

            // 데이터 정규화
            $normalized = array();
            foreach ($talks as $talk) {
                $normalized[] = array(
                    'date' => isset($talk['date']) ? trim($talk['date']) : '',
                    'speaker' => isset($talk['speaker']) ? trim($talk['speaker']) : '',
                    'congregation' => isset($talk['congregation']) ? trim($talk['congregation']) : '',
                    'topic' => isset($talk['topic']) ? trim($talk['topic']) : '',
                    'topic_type' => isset($talk['topic_type']) && in_array($talk['topic_type'], array('normal', 'circuit_visit', 'special_talk')) ? $talk['topic_type'] : 'normal',
                    'chairman' => isset($talk['chairman']) ? trim($talk['chairman']) : '',
                    'reader' => isset($talk['reader']) ? trim($talk['reader']) : '',
                    'prayer' => isset($talk['prayer']) ? trim($talk['prayer']) : ''
                );
            }

            $result = $manager->save($normalized);
            echo json_encode(array('success' => $result));
            break;

        case 'set_start_date':
            $date = isset($_POST['date']) ? trim($_POST['date']) : '';
            $result = $manager->setDisplayStartDate($date);
            echo json_encode(array('success' => $result));
            break;

        default:
            echo json_encode(array('success' => false, 'error' => '알 수 없는 액션입니다.'));
            break;
    }
    exit;
}
