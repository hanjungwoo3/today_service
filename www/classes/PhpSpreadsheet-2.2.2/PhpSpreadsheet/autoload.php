<?php
spl_autoload_register(function ($class) {
    // 프로젝트의 기본 디렉토리 경로를 설정합니다.
    $prefix = 'PhpOffice\\PhpSpreadsheet\\';
    $base_dir = __DIR__.'/';

    // 클래스 이름에서 네임스페이스 접두사를 제거합니다.
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // 클래스가 네임스페이스 접두사와 일치하지 않으면 넘어갑니다.
        return;
    }

    // 남은 클래스 이름 부분을 가져옵니다.
    $relative_class = substr($class, $len);

    // 파일 경로를 만듭니다.
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    // 파일이 존재하면 로드합니다.
    if (file_exists($file)) {
        require $file;
    }
});

?>