<?php

/**
 * JW.ORG 생활과 봉사 집회 자료 링크 수집기
 */

class MeetingLinkScraper {

    private $baseUrl = 'https://wol.jw.org';

    /**
     * 특정 주의 생활과 봉사 집회 링크를 가져옵니다
     *
     * @param int $year 연도 (예: 2025)
     * @param int $week 주차 (예: 45)
     * @return array|null
     */
    public function getWeeklyLink($year, $week) {
        $url = "{$this->baseUrl}/ko/wol/meetings/r8/lp-ko/{$year}/{$week}";

        // HTML 가져오기
        $html = $this->fetchHtml($url);
        if (!$html) {
            return null;
        }

        // DOMDocument로 파싱
        $dom = new DOMDocument();
        @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new DOMXPath($dom);

        // "생활과 봉사" 섹션 찾기
        // 클래스명이나 ID로 찾아야 하는데, 일반적으로 제목이나 특정 섹션으로 찾을 수 있습니다

        // 방법 1: 모든 링크를 검색하고 패턴 매칭
        $links = $xpath->query('//a[@href]');
        $meetingLink = null;

        foreach ($links as $link) {
            $href = $link->getAttribute('href');
            $text = trim($link->textContent);

            // "생활과 봉사 집회 교재" 패턴 찾기
            // URL 패턴: /ko/wol/d/r8/lp-ko/202025401 형식
            if (preg_match('#/ko/wol/d/r8/lp-ko/(\d+)#', $href, $matches)) {
                // 생활과 봉사 집회 자료로 보이는 경우
                if (strpos($text, '생활과 봉사') !== false ||
                    strpos($href, '/d/r8/lp-ko/') !== false) {

                    // 전체 URL 생성
                    $fullUrl = (strpos($href, 'http') === 0) ? $href : $this->baseUrl . $href;

                    $meetingLink = array(
                        'year' => $year,
                        'week' => $week,
                        'url' => $fullUrl,
                        'title' => $text,
                        'document_id' => $matches[1]
                    );
                    break;
                }
            }
        }

        return $meetingLink;
    }

    /**
     * 특정 URL의 집회 프로그램을 파싱합니다
     *
     * @param string $url 집회 자료 URL
     * @return array|null
     */
    public function parseMeetingProgram($url) {
        $html = $this->fetchHtml($url);
        if (!$html) {
            return null;
        }

        $dom = new DOMDocument();
        @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new DOMXPath($dom);

        $result = array(
            'date' => '',
            'bible_reading' => '',
            'program' => array()
        );

        // 날짜 추출 (예: "11월 3-9일")
        $h1 = $xpath->query('//h1');
        if ($h1->length > 0) {
            $result['date'] = trim($h1->item(0)->textContent);
        }

        // 성경 읽기 추출 (예: "솔로몬의 노래 1-2장")
        $h2Tags = $xpath->query('//h2');
        foreach ($h2Tags as $h2) {
            $text = trim($h2->textContent);
            if (preg_match('/[가-힣]+ \d+-?\d*장?/', $text)) {
                $result['bible_reading'] = $text;
                break;
            }
        }

        // 프로그램 항목 추출
        $h3Tags = $xpath->query('//h3');

        foreach ($h3Tags as $h3) {
            $text = trim($h3->textContent);

            // 노래, 기도, 소개말, 맺음말 제외
            if (strpos($text, '노래 ') !== false && strpos($text, '기도') === false) {
                // 노래만 있는 경우 (예: "노래 46")
                continue;
            }
            if (strpos($text, '노래') !== false && strpos($text, '및 기도') !== false) {
                continue;
            }
            if (strpos($text, '맺음말') !== false) {
                continue;
            }

            // 시간 추출 - h3 태그 자체에 있는 경우
            $time = '';
            if (preg_match('/\((\d+분)\)/', $text, $timeMatch)) {
                $time = $timeMatch[1];
                $text = trim(str_replace($timeMatch[0], '', $text));
            }

            // 시간이 없으면 다음 div 태그에서 찾기
            if (empty($time)) {
                $nextDiv = $xpath->query('following-sibling::div[1]', $h3);
                if ($nextDiv->length > 0) {
                    $divText = trim($nextDiv->item(0)->textContent);
                    // 시간 패턴 찾기: "(10분)" 형태
                    if (preg_match('/^\((\d+분)\)/', $divText, $timeMatch)) {
                        $time = $timeMatch[1];
                    }
                }
            }

            // 큰따옴표, 불필요한 문자 제거 (숫자는 유지)
            $title = str_replace(array('"', '"', '|', '및 기도'), '', $text);
            $title = trim($title);

            // 빈 제목이나 시간이 없으면 건너뛰기
            if (empty($title) || empty($time)) {
                continue;
            }

            $result['program'][] = array(
                'title' => $title,
                'duration' => $time
            );
        }

        return $result;
    }

    /**
     * 여러 주의 링크를 한 번에 수집합니다
     *
     * @param int $year 연도
     * @param int $startWeek 시작 주차
     * @param int $endWeek 끝 주차
     * @return array
     */
    public function getMultipleWeeks($year, $startWeek, $endWeek) {
        $results = array();

        for ($week = $startWeek; $week <= $endWeek; $week++) {
            echo "주차 {$week} 수집 중...\n";

            $link = $this->getWeeklyLink($year, $week);

            if ($link) {
                $results[] = $link;
                echo "✓ 완료: {$year}>{$week}>{$link['url']}\n";
            } else {
                echo "✗ 실패: {$year}>{$week}\n";
            }

            // 서버 부하를 줄이기 위한 딜레이
            sleep(1);
        }

        return $results;
    }

    /**
     * HTML을 가져옵니다
     *
     * @param string $url
     * @return string|false
     */
    private function fetchHtml($url) {
        // cURL 사용
        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');

            $html = curl_exec($ch);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                echo "cURL 오류: {$error}\n";
                return false;
            }

            return $html;
        }

        // file_get_contents 대안
        $context = stream_context_create(array(
            'http' => array(
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
            )
        ));

        return @file_get_contents($url, false, $context);
    }

    /**
     * 결과를 원하는 형식으로 출력합니다
     *
     * @param array $results
     * @param string $format 'simple' | 'json' | 'csv'
     */
    public function exportResults($results, $format = 'simple') {
        switch ($format) {
            case 'json':
                echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                break;

            case 'csv':
                foreach ($results as $item) {
                    echo "{$item['year']},{$item['week']},{$item['url']}\n";
                }
                break;

            case 'simple':
            default:
                foreach ($results as $item) {
                    echo "{$item['year']}>{$item['week']}>{$item['url']}\n";
                }
                break;
        }
    }
}

// 사용 예시
if (php_sapi_name() === 'cli') {
    echo "=== JW.ORG 생활과 봉사 집회 자료 링크 수집기 ===\n\n";

    $scraper = new MeetingLinkScraper();

    // 단일 주차 예시
    echo "단일 주차 테스트 (2025년 45주):\n";
    $result = $scraper->getWeeklyLink(2025, 45);
    if ($result) {
        echo "{$result['year']}>{$result['week']}>{$result['url']}\n\n";

        // 해당 주의 프로그램 파싱
        echo "\n=== 집회 프로그램 파싱 ===\n";
        $program = $scraper->parseMeetingProgram($result['url']);

        if ($program) {
            echo "날짜: {$program['date']}\n";
            echo "성경 읽기: {$program['bible_reading']}\n\n";

            echo "프로그램:\n";
            foreach ($program['program'] as $item) {
                echo "{$item['title']} ({$item['duration']})\n";
            }

            echo "\n=== JSON 형식 ===\n";
            echo json_encode($program, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }
    }

    echo "\n\n";

    // 여러 주차 예시 (45주부터 47주까지)
    echo "\n여러 주차 수집 (45-47주):\n";
    $results = $scraper->getMultipleWeeks(2025, 45, 47);

    echo "\n=== 최종 결과 ===\n";
    $scraper->exportResults($results, 'simple');
}

?>
