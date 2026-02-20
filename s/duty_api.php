<?php

date_default_timezone_set('Asia/Seoul');

class DutyDataManager
{
    private $dataDir;
    private $backupDir;

    public function __construct()
    {
        $this->dataDir = dirname(__FILE__) . '/data';
        $this->backupDir = $this->dataDir . '/duty_bak';

        if (!is_dir($this->dataDir)) {
            mkdir($this->dataDir, 0755, true);
        }
    }

    private function getFilePath($year)
    {
        return $this->dataDir . '/duty_' . (int)$year . '.json';
    }

    public function load($year)
    {
        $file = $this->getFilePath($year);
        if (!file_exists($file)) {
            return $this->getEmptyData($year);
        }
        $content = file_get_contents($file);
        $data = json_decode($content, true);
        if (!is_array($data) || !isset($data['months'])) {
            return $this->getEmptyData($year);
        }
        return $data;
    }

    public function save($year, $data)
    {
        if (!is_array($data) || !isset($data['months'])) {
            return false;
        }

        $file = $this->getFilePath($year);

        // backup
        if (file_exists($file)) {
            if (!is_dir($this->backupDir)) {
                mkdir($this->backupDir, 0755, true);
            }
            $timestamp = date('YmdHis');
            copy($file, $this->backupDir . '/duty_' . $year . '_' . $timestamp . '.json');
        }

        $data['year'] = (int)$year;
        $result = file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        if ($result !== false) {
            $this->cleanupOldBackups();
        }

        return $result !== false;
    }

    private function getEmptyData($year)
    {
        $months = array();
        for ($m = 1; $m <= 12; $m++) {
            $months[(string)$m] = array(
                'cleaning_group' => '',
                'first_half' => array(
                    'mic1' => '', 'mic2' => '', 'mic_assist' => '',
                    'att_hall1' => '', 'att_hall2' => '', 'att_entrance' => ''
                ),
                'second_half' => array(
                    'mic1' => '', 'mic2' => '', 'mic_assist' => '',
                    'att_hall1' => '', 'att_hall2' => '', 'att_entrance' => ''
                ),
                'drink_main' => '',
                'drink_assist' => ''
            );
        }
        return array('year' => (int)$year, 'months' => $months);
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

// AJAX handler
if (isset($_POST['action'])) {
    header('Content-Type: application/json; charset=utf-8');

    $action = $_POST['action'];
    $manager = new DutyDataManager();

    switch ($action) {
        case 'load':
            $year = isset($_POST['year']) ? (int)$_POST['year'] : (int)date('Y');
            $data = $manager->load($year);
            echo json_encode(array('success' => true, 'data' => $data));
            break;

        case 'save':
            $year = isset($_POST['year']) ? (int)$_POST['year'] : (int)date('Y');
            $dataJson = isset($_POST['data']) ? $_POST['data'] : '{}';
            $data = json_decode($dataJson, true);
            if (!is_array($data) || !isset($data['months'])) {
                echo json_encode(array('success' => false, 'error' => '잘못된 데이터 형식입니다.'));
                break;
            }
            $result = $manager->save($year, $data);
            echo json_encode(array('success' => $result));
            break;

        default:
            echo json_encode(array('success' => false, 'error' => '알 수 없는 액션입니다.'));
            break;
    }
    exit;
}
