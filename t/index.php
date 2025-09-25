<?php
session_start();

        // 타이머 시작 시 설정 저장 처리
        if ($_POST && isset($_POST['start_timer'])) {
            $settings = array(
                'title' => isset($_POST['title']) ? $_POST['title'] : '타이머',
                'minutes' => (int)(isset($_POST['minutes']) ? $_POST['minutes'] : 5),
                'seconds' => (int)(isset($_POST['seconds']) ? $_POST['seconds'] : 0),
                'end_message' => isset($_POST['end_message']) ? $_POST['end_message'] : '완료!',
                'online_music' => isset($_POST['online_music']) ? $_POST['online_music'] : '',
                'music_category' => isset($_POST['music_category']) ? $_POST['music_category'] : 'all',
                'auto_start_hour' => (int)(isset($_POST['auto_start_hour']) ? $_POST['auto_start_hour'] : -1),
                'auto_start_minute' => (int)(isset($_POST['auto_start_minute']) ? $_POST['auto_start_minute'] : 0)
            );
    
    // JSON 파일로 저장 (모든 설정 포함)
    file_put_contents('timer_settings.json', json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    // 세션에도 저장 (하위 호환성)
    $_SESSION['timer_settings'] = $settings;
    
    // 타이머 페이지로 리다이렉트
    header('Location: timer.php');
    exit;
}

        // 저장된 설정 불러오기 (JSON 파일 우선, 없으면 기본값)
        // 요일에 따른 기본 자동 시작시간 설정 (타이머 시간만큼 앞당김)
        $today = date('w'); // 0=일요일, 1=월요일, ..., 6=토요일
        
        $default_settings = array(
            'title' => '타이머',
            'minutes' => 5,
            'seconds' => 0,
            'end_message' => '완료!',
            'online_music' => '',
            'music_category' => 'all',
            'auto_start_hour' => -1,
            'auto_start_minute' => 0
        );

if (file_exists('timer_settings.json')) {
    $json_settings = json_decode(file_get_contents('timer_settings.json'), true);
    $settings = $json_settings ? $json_settings : $default_settings;
} else {
    $settings = isset($_SESSION['timer_settings']) ? $_SESSION['timer_settings'] : $default_settings;
}

// 타이머 시간을 고려한 자동 시작시간 계산
$timer_minutes = isset($settings['minutes']) ? $settings['minutes'] : 5;
$timer_seconds = isset($settings['seconds']) ? $settings['seconds'] : 0;
$total_timer_seconds = ($timer_minutes * 60) + $timer_seconds;

// 목표 완료 시간 설정
if ($today == 0) {
    // 일요일: 13시 0분에 완료되도록
    $target_hour = 13;
    $target_minute = 0;
} else {
    // 평일: 19시 30분에 완료되도록
    $target_hour = 19;
    $target_minute = 30;
}

// 목표 시간에서 타이머 시간만큼 빼기
$target_total_minutes = ($target_hour * 60) + $target_minute;
$start_total_minutes = $target_total_minutes - ceil($total_timer_seconds / 60);

// 음수가 되면 전날로 넘어가므로 보정
if ($start_total_minutes < 0) {
    $start_total_minutes += 24 * 60; // 24시간 추가
}

$auto_start_hour = floor($start_total_minutes / 60);
$auto_start_minute = $start_total_minutes % 60;

// 계산된 자동 시작시간 적용
$settings['auto_start_hour'] = $auto_start_hour;
$settings['auto_start_minute'] = $auto_start_minute;

// 설정 페이지에서는 음악 설정을 항상 빈 값으로 시작 (랜덤 선택을 위해)
$settings['online_music'] = '';

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>타이머 설정</title>
    <link rel="stylesheet" href="style.css?v=<?= filemtime('style.css') ?>">
</head>
<body>
    <div class="container">
        <div class="setup-page">
            <h1 class="title">타이머 설정</h1>
            
            <form method="POST" class="setup-form">
                       <div class="input-group">
                           <label for="title">제목 :</label>
                           <textarea id="title" name="title" placeholder="타이머 제목을 입력하세요" rows="3"><?= htmlspecialchars($settings['title']) ?></textarea>
                       </div>
                
                <div class="input-group">
                    <label for="end_message">안내 메시지 :</label>
                    <textarea id="end_message" name="end_message" rows="3" placeholder="타이머 진행 중 안내 메시지 (줄바꿈으로 여러 줄 입력 가능)"><?= htmlspecialchars($settings['end_message']) ?></textarea>
                </div>
                
                        <div class="input-group">
                            <label for="time">타이머 :</label>
                            <div class="time-select-container">
                                <select id="minutes" name="minutes">
                                    <?php
                                    $selected_minutes = isset($settings['minutes']) ? $settings['minutes'] : 5;
                                    for ($i = 0; $i <= 10; $i++) {
                                        $selected = ($selected_minutes == $i) ? 'selected' : '';
                                        echo "<option value=\"$i\" $selected>{$i}분</option>";
                                    }
                                    ?>
                                </select>
                                <select id="seconds" name="seconds">
                                    <?php
                                    $selected_seconds = isset($settings['seconds']) ? $settings['seconds'] : 0;
                                    for ($i = 0; $i <= 55; $i += 5) {
                                        $selected = ($selected_seconds == $i) ? 'selected' : '';
                                        echo "<option value=\"$i\" $selected>{$i}초</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                
                <div class="input-group">
                    <label for="auto_start">자동 시작시간 :</label>
                    <div class="auto-start-container">
                        <select id="auto_start_hour" name="auto_start_hour" style="display: inline-block; width: auto; margin-right: 10px;">
                            <?php 
                            $selected_hour = isset($settings['auto_start_hour']) ? $settings['auto_start_hour'] : -1; 
                            ?>
                            <option value="-1" <?= ($selected_hour == -1) ? 'selected' : '' ?>>사용 안함</option>
                            <?php
                            for ($h = 0; $h <= 23; $h++) {
                                $selected = ($selected_hour == $h) ? 'selected' : '';
                                echo "<option value=\"$h\" $selected>{$h}시</option>";
                            }
                            ?>
                        </select>
                        <select id="auto_start_minute" name="auto_start_minute" style="display: inline-block; width: auto;">
                            <?php
                            $selected_minute = isset($settings['auto_start_minute']) ? $settings['auto_start_minute'] : 0;
                            for ($m = 0; $m <= 59; $m++) {
                                $selected = ($selected_minute == $m) ? 'selected' : '';
                                echo "<option value=\"$m\" $selected>{$m}분</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
                
                       <div class="input-group">
                           <label for="music_category">음악 카테고리 :</label>
                            <div class="category-select-container">
                                <select id="music_category" name="music_category">
                                    <?php
                                    $selected_category = isset($settings['music_category']) ? $settings['music_category'] : 'all';
                                    $categories = array(
                                        'all' => '전체',
                                        '집회' => '집회 (161곡)',
                                        '노래' => '노래 (79곡)',
                                        '연주' => '연주 (3곡)',
                                        '어린이' => '어린이 (47곡)'
                                    );
                                    
                                    foreach ($categories as $value => $label) {
                                        $selected = ($selected_category === $value) ? 'selected' : '';
                                        echo "<option value=\"$value\" $selected>$label</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        
                       <div class="input-group">
                           <label for="online_music">배경음악 :</label>
                            <div class="music-select-container">
                                <select id="online_music" name="online_music">
                                    <option value="">음악을 선택하세요</option>
                                    <?php
                                    if (file_exists('music_list.json')) {
                                        $music_list = json_decode(file_get_contents('music_list.json'), true);
                                        $selected_music = isset($settings['online_music']) ? $settings['online_music'] : '';
                                        
                                        // PHP에서는 모든 곡을 출력하고, JavaScript에서 필터링
                                        foreach ($music_list['songs'] as $song) {
                                            $selected = ($selected_music === $song['url']) ? 'selected' : '';
                                            // 카테고리 정보를 data 속성으로 추가
                                            $category = '';
                                            if (strpos($song['title'], '(집회)') !== false) $category = '집회';
                                            elseif (strpos($song['title'], '(노래)') !== false) $category = '노래';
                                            elseif (strpos($song['title'], '(연주)') !== false) $category = '연주';
                                            elseif (strpos($song['title'], '(어린이)') !== false) $category = '어린이';
                                            
                                            echo "<option value=\"{$song['url']}\" data-category=\"$category\" $selected>{$song['title']}</option>";
                                        }
                                    }
                                    ?>
                                </select>
                                <button type="button" id="randomMusicBtn" class="random-btn" title="랜덤 음악 선택">🎲</button>
                            </div>
                        </div>
                
                <button type="submit" name="start_timer" class="start-button">타이머 시작</button>
            </form>
            
            <div class="info-section">
                <div class="info-row">
                    <div class="info-box left">
                        <h4>자동 시작시간 설정(기본값)</h4>
                    <p>일요일은 13시, 평일은 19시 30분에 타이머 종료</br>예) 3분 타이머(일요일 12시 57분, 평일 19시 27분에 자동 시작)</p>
                    </div>
                    
                    <div class="info-box right">
                        <h4>자동 시작 작동 방식</h4>
                    <p>먼저 <strong style="color: #3498db;">타이머 시작</strong> 버튼을 누른 후 <strong style="color: #3498db;">대기 화면</strong>에서 시작시간이 되면 타이머가 작동합니다. (수동 시작은 스페이스바)</p>
                    </div>
                </div>
                
                <div class="fullscreen-notice">
                    💡 <kbd>스페이스바</kbd> 또는 "타이머 시작" 버튼으로 시작하세요
                </div>
            </div>
                   
        </div>
    </div>
    
    <script>
        // 음악 선택 시 미리보기 및 디버깅
        document.getElementById('online_music').addEventListener('change', function(e) {
            const selectedOption = e.target.selectedOptions[0];
            if (selectedOption && selectedOption.value) {
                console.log('선택된 음악:', selectedOption.text);
                console.log('음악 URL:', selectedOption.value);
                
                // 프록시를 통한 음악 URL 유효성 테스트
                const proxyUrl = 'music_proxy.php?url=' + encodeURIComponent(selectedOption.value);
                const testAudio = new Audio();
                testAudio.addEventListener('loadstart', () => {
                    console.log('음악 URL 테스트: 로드 시작');
                });
                testAudio.addEventListener('error', (e) => {
                    console.error('음악 URL 테스트 실패:', e);
                });
                testAudio.addEventListener('canplay', () => {
                    console.log('음악 URL 테스트: 재생 가능');
                });
                console.log('프록시 테스트 URL:', proxyUrl);
                testAudio.src = proxyUrl;
            }
        });
        
            // 키보드 단축키 이벤트
            document.addEventListener('keydown', function(e) {
                // 스페이스바로 타이머 시작
                if (e.code === 'Space' && !e.target.matches('input, textarea, select')) {
                    e.preventDefault(); // 기본 스크롤 동작 방지
                    
                    // 시간 유효성 검사
                    const minutes = parseInt(document.getElementById('minutes').value);
                    const seconds = parseInt(document.getElementById('seconds').value);
                    
                    if (minutes === 0 && seconds === 0) {
                        alert('타이머 시간을 1초 이상 설정해주세요.');
                        return;
                    }
                    
                    // start_timer 히든 필드 추가 후 폼 제출
                    const form = document.querySelector('form');
                    if (form) {
                        // 기존 start_timer 필드가 있으면 제거
                        const existingField = form.querySelector('input[name="start_timer"]');
                        if (existingField) {
                            existingField.remove();
                        }
                        
                        // 새로운 start_timer 히든 필드 추가
                        const hiddenField = document.createElement('input');
                        hiddenField.type = 'hidden';
                        hiddenField.name = 'start_timer';
                        hiddenField.value = '1';
                        form.appendChild(hiddenField);
                        
                        // 폼 제출
                        form.submit();
                        console.log('스페이스바로 타이머 시작');
                    }
                }
            });
        
        // 카테고리별 음악 필터링 함수
        function filterMusicByCategory() {
            const categorySelect = document.getElementById('music_category');
            const musicSelect = document.getElementById('online_music');
            const selectedCategory = categorySelect.value;
            
            // 모든 옵션을 가져와서 필터링
            const allOptions = musicSelect.querySelectorAll('option');
            
            // 첫 번째 옵션 (빈 값) 제외하고 모든 옵션 숨기기
            allOptions.forEach((option, index) => {
                if (index === 0) {
                    // 첫 번째 옵션 ("음악을 선택하세요")은 항상 표시
                    option.style.display = '';
                } else if (selectedCategory === 'all') {
                    // 전체 선택 시 모든 옵션 표시
                    option.style.display = '';
                } else {
                    // 선택된 카테고리와 일치하는 옵션만 표시
                    const optionCategory = option.getAttribute('data-category');
                    option.style.display = (optionCategory === selectedCategory) ? '' : 'none';
                }
            });
            
            // 현재 선택된 음악이 필터링된 카테고리에 속하지 않으면 초기화
            const currentSelected = musicSelect.value;
            if (currentSelected && selectedCategory !== 'all') {
                const currentOption = musicSelect.querySelector(`option[value="${currentSelected}"]`);
                if (currentOption && currentOption.getAttribute('data-category') !== selectedCategory) {
                    musicSelect.value = '';
                }
            }
            
            console.log('카테고리 필터링:', selectedCategory);
        }
        
        // 랜덤 음악 선택 함수 (카테고리 필터링 적용)
        function selectRandomMusic() {
            const musicSelect = document.getElementById('online_music');
            const categorySelect = document.getElementById('music_category');
            const selectedCategory = categorySelect.value;
            
            const allOptions = musicSelect.querySelectorAll('option');
            let options;
            
            if (selectedCategory === 'all') {
                // 전체 카테고리에서 선택 (빈 값 제외)
                options = Array.from(allOptions).filter(option => option.value !== '');
            } else {
                // 선택된 카테고리에서만 선택
                options = Array.from(allOptions).filter(option => 
                    option.value !== '' && option.getAttribute('data-category') === selectedCategory
                );
            }
            
            if (options.length > 0) {
                // 랜덤 인덱스 선택
                const randomIndex = Math.floor(Math.random() * options.length);
                const randomOption = options[randomIndex];
                
                // 선택 적용
                musicSelect.value = randomOption.value;
                
                // 선택된 음악 정보 출력
                console.log('랜덤 음악 선택 (' + selectedCategory + '):', randomOption.textContent);
                
                // 음악 테스트 (CDN 직접 연결만 시도)
                const selectedOption = randomOption;
                if (selectedOption && selectedOption.value) {
                    const directUrl = selectedOption.value;
                    const testAudio = new Audio();
                    testAudio.addEventListener('loadstart', () => { console.log('랜덤 음악 CDN 직접 테스트: 로드 시작'); });
                    testAudio.addEventListener('error', (e) => { console.log('랜덤 음악 CDN 직접 테스트 실패 (정상 - 프록시 사용 안 함)'); });
                    testAudio.addEventListener('canplay', () => { console.log('랜덤 음악 CDN 직접 테스트: 재생 가능'); });
                    console.log('랜덤 선택 CDN 직접 테스트 URL:', directUrl);
                    testAudio.src = directUrl;
                }
                
                return true; // 성공
            } else {
                console.log('선택된 카테고리에 음악이 없습니다:', selectedCategory);
                return false; // 실패
            }
        }
        
        // 페이지 로드 시 초기화
        document.addEventListener('DOMContentLoaded', function() {
            const musicSelect = document.getElementById('online_music');
            const categorySelect = document.getElementById('music_category');
            
            // 카테고리 변경 이벤트 리스너 추가
            categorySelect.addEventListener('change', function() {
                filterMusicByCategory();
                
                // 카테고리 변경 후 음악이 선택되지 않았으면 랜덤 선택
                if (!musicSelect.value || musicSelect.value === '') {
                    selectRandomMusic();
                }
            });
            
            // 초기 필터링 적용
            filterMusicByCategory();
            
            // 현재 선택된 값이 없거나 빈 값일 때만 랜덤 선택
            if (!musicSelect.value || musicSelect.value === '') {
                console.log('페이지 로드 시 랜덤 음악 선택 실행');
                selectRandomMusic();
            } else {
                console.log('기존 설정된 음악 사용:', musicSelect.options[musicSelect.selectedIndex].textContent);
            }
        });
        
        // 랜덤 음악 선택 버튼 기능
        document.getElementById('randomMusicBtn').addEventListener('click', function() {
            // 공통 랜덤 선택 함수 사용
            if (selectRandomMusic()) {
                // 시각적 피드백 (버튼 회전)
                this.style.transform = 'rotate(360deg)';
                setTimeout(() => {
                    this.style.transform = '';
                }, 300);
            }
        });
        
        // 타이머 시작 시 설정 확인 (폼 제출 전 로깅)
        document.querySelector('form').addEventListener('submit', function(e) {
            const title = document.getElementById('title').value;
            const minutes = parseInt(document.getElementById('minutes').value);
            const seconds = parseInt(document.getElementById('seconds').value);
            const endMessage = document.getElementById('end_message').value;
            const selectedMusic = document.getElementById('online_music').value;
            const selectedCategory = document.getElementById('music_category').value;
            
            // 0분 0초 체크
            if (minutes === 0 && seconds === 0) {
                e.preventDefault();
                alert('타이머 시간을 1초 이상 설정해주세요.');
                return;
            }
            
            console.log('타이머 시작 - 설정 정보:');
            console.log('- 제목:', title);
            console.log('- 시간:', minutes, '분', seconds, '초');
            console.log('- 안내 메시지:', endMessage);
            console.log('- 음악 카테고리:', selectedCategory);
            console.log('- 선택된 음악:', selectedMusic);
            
            if (!selectedMusic) {
                console.warn('음악이 선택되지 않았습니다.');
            }
            
            // 폼이 정상적으로 제출되도록 함
        });
    </script>
</body>
</html>
