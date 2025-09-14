<!DOCTYPE html>
<html lang="ko">
    <head>
        <title>오늘의 봉사</title>
        <meta charset="utf-8">
        <meta name="description" content="JW Ministry"/>
        <meta name="robots" content="noindex,nofollow"/>
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, shrink-to-fit=no, minimum-scale=1.0, user-scalable=no">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/css/bootstrap.min.css" integrity="sha384-TX8t27EcRE3e/ihU7zmQxVncDAy5uIKz4rEkgIXeMed4M0jlfIDPvg6uqKI2xXr2" crossorigin="anonymous">
        <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/js/bootstrap.min.js" integrity="sha384-w1Q4orYjBQndcko6MimVbzY0tgp4pWB4lZ7lr30WKz0vr/aWKhXdBNmNb5D92v7s" crossorigin="anonymous"></script>
        <style>
            html, html > * {
                font-family: 'NanumSquare', sans-serif;
            }
        </style>
    </head>
    <body>
        <div class="container-sm py-4">
        <?php
        if(!file_exists('config_custom.php')){
            echo "'config_custom.php' 파일이 존재하지 않습니다.";
            exit;
        }

        include_once('config_custom.php'); // 커스텀 설정

        if(empty($host) || empty($user) || empty($password) || empty($dbname)){
            echo "'config_custom.php' 파일내의 정보가 비어있습니다.";
            exit;
        }

        $mysqli = new mysqli($host, $user, $password, $dbname);
        // 연결 오류 발생 시 스크립트 종료
        if ($mysqli->connect_errno) {
            die('Connect Error: '.$mysqli->connect_error);
        }

        include_once('config_table.php'); // 테이블 설정

        if(!$mysqli->query("SHOW TABLES LIKE '".MEMBER_TABLE."'")->fetch_row()):?>
            <h3>오늘의 봉사 신규 설치</h3>
            <form action="<?=($_SERVER['HTTPS'] == 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/')?>/update_work.php" method="post" class="needs-validation" novalidate>
                <input type="hidden" name="type" value="new" required>
                <div class="form-group">
                    <label for="db_name">데이터베이스 아이디</label>
                    <input type="text" class="form-control" id="db_name" name="db_name" aria-describedby="db_nameHelp" required>
                    <div class="invalid-feedback">
                        데이터베이스 아이디를 입력해주세요.
                    </div>
                </div>
                <div class="form-group">
                    <label for="db_password">데이터베이스 비밀번호</label>
                    <input type="password" class="form-control" id="db_password" name="db_password" aria-describedby="db_passwordHelp" required  style="font-family: arial;">
                    <div class="invalid-feedback">
                        데이터베이스 비밀번호를 입력해주세요.
                    </div>
                </div>
                <div class="form-group">
                    <label for="name">관리자 이름</label>
                    <input type="text" class="form-control" id="name" name="name" aria-describedby="nameHelp" required>
                    <div class="invalid-feedback">
                        관리자의 이름을 입력해주세요.
                    </div>
                    <small id="nameHelp" class="form-text text-muted">최초 등록할 관리자의 이름을 입력해주세요.</small>
                </div>
                <div class="form-group">
                    <label for="password">관리자 비밀번호</label>
                    <input type="password" class="form-control" id="password" name="password" aria-describedby="passwordHelp" required  style="font-family: arial;">
                    <div class="invalid-feedback">
                        관리자의 비밀번호를 입력해주세요.
                    </div>
                    <small id="passwordHelp" class="form-text text-muted">최초 등록할 관리자의 비밀번호를 입력해주세요.</small>
                </div>
                <div class="form-group">
                    <label for="password_confirm">관리자 비밀번호 확인</label>
                    <input type="password" class="form-control" id="password_confirm" name="password_confirm" aria-describedby="password_c_Help" required  style="font-family: arial;">
                    <div class="invalid-feedback">
                        관리자의 비밀번호를 한번 더 입력해주세요.
                    </div>
                    <small id="password_c_Help" class="form-text text-muted">최초 등록할 관리자의 비밀번호를 한번 더 입력해주세요.</small>
                </div>
                <button type="submit" class="btn btn btn-outline-primary">설치 진행하기</button>
            </form>
          <?php else:?>
            <h3>오늘의 봉사 DB 업데이트</h3>
            <form action="<?=($_SERVER['HTTPS'] == 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/')?>/update_work.php" method="post" class="needs-validation" novalidate>
                <input type="hidden" name="type" value="update" required>
                <div class="form-group">
                    <label for="db_name">데이터베이스 아이디</label>
                    <input type="text" class="form-control" id="db_name" name="db_name" aria-describedby="db_nameHelp" required>
                    <div class="invalid-feedback">
                        데이터베이스 아이디를 입력해주세요.
                    </div>
                </div>
                <div class="form-group">
                    <label for="db_password">데이터베이스 비밀번호</label>
                    <input type="password" class="form-control" id="db_password" name="db_password" aria-describedby="db_passwordHelp" required  style="font-family: arial;">
                    <div class="invalid-feedback">
                        데이터베이스 비밀번호를 입력해주세요.
                    </div>
                </div>
                <!-- <p>DB 업데이트 버전 : 2.4.6</p>
                <p>DATA 업데이트 버전 : 2.4.7</p> -->
                <p class="text-danger">업데이트를 진행하기 전 백업은 필수 입니다.</p>
                <button type="submit" class="btn btn btn-outline-primary">DB 업데이트 진행하기</button>
            </form>
            <hr>
            <p>
                <button class="btn btn-outline-danger btn-sm" type="button" data-toggle="collapse" data-target="#collapseExample" aria-expanded="false" aria-controls="collapseExample">
                    관리자 계정 생성
                </button>
                <small id="passwordHelp" class="form-text text-muted">관리자 계정 삭제, 비밀번호 분실 등의 이유로 관리자 계정을 추가할 필요가 있을때만 사용해주세요</small>
            </p>
            <div class="collapse" id="collapseExample">
                <form action="<?=($_SERVER['HTTPS'] == 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/')?>/update_work.php" method="post" class="needs-validation" novalidate>
                    <input type="hidden" name="type" value="insert_admin" required>
                    <div class="form-group">
                        <label for="db_name">데이터베이스 아이디</label>
                        <input type="text" class="form-control" id="db_name" name="db_name" aria-describedby="db_nameHelp" required>
                        <div class="invalid-feedback">
                            데이터베이스 아이디를 입력해주세요.
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="db_password">데이터베이스 비밀번호</label>
                        <input type="password" class="form-control" id="db_password" name="db_password" aria-describedby="db_passwordHelp" required  style="font-family: arial;">
                        <div class="invalid-feedback">
                            데이터베이스 비밀번호를 입력해주세요.
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="name">추가할 관리자 이름</label>
                        <input type="text" class="form-control" id="name" name="name" aria-describedby="nameHelp" required>
                        <div class="invalid-feedback">
                            관리자의 이름을 입력해주세요.
                        </div>
                        <small id="nameHelp" class="form-text text-muted">최초 등록할 관리자의 이름을 입력해주세요.</small>
                    </div>
                    <div class="form-group">
                        <label for="password">추가할 관리자 비밀번호</label>
                        <input type="password" class="form-control" id="password" name="password" aria-describedby="passwordHelp" required  style="font-family: arial;">
                        <div class="invalid-feedback">
                            관리자의 비밀번호를 입력해주세요.
                        </div>
                        <small id="passwordHelp" class="form-text text-muted">최초 등록할 관리자의 비밀번호를 입력해주세요.</small>
                    </div>
                    <div class="form-group">
                        <label for="password_confirm">추가할 관리자 비밀번호 확인</label>
                        <input type="password" class="form-control" id="password_confirm" name="password_confirm" aria-describedby="password_c_Help" required  style="font-family: arial;">
                        <div class="invalid-feedback">
                            관리자의 비밀번호를 한번 더 입력해주세요.
                        </div>
                        <small id="password_c_Help" class="form-text text-muted">최초 등록할 관리자의 비밀번호를 한번 더 입력해주세요.</small>
                    </div>

                    <button type="submit" class="btn btn btn-outline-primary">관리자 계정 생성하기</button>
                </form>
            </div>
          <?php endif;?>

        <script>
        (function() { // Example starter JavaScript for disabling form submissions if there are invalid fields
          'use strict';
          window.addEventListener('load', function() {
              // Fetch all the forms we want to apply custom Bootstrap validation styles to
              var forms = document.getElementsByClassName('needs-validation');
              // Loop over them and prevent submission
              var validation = Array.prototype.filter.call(forms, function(form) {
              form.addEventListener('submit', function(event) {
                  if (form.checkValidity() === false) {
                  event.preventDefault();
                  event.stopPropagation();
                  }
                  form.classList.add('was-validated');
              }, false);
              });
          }, false);
        })();
        </script>

        </div>
    </body>
</html>
