<?php
// 데이터베이스 접속 정보 설정
define('DB_HOST', 'localhost'); // 데이터베이스 호스트 이름
define('DB_USER', "db2020160027"); // 데이터베이스 사용자 이름
define('DB_PASS', "dpdud0824@naver.com"); // 데이터베이스 비밀번호
define('DB_NAME', "db2020160027"); // 데이터베이스 이름

// 데이터베이스에 연결하는 함수
function connectDatabase() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    // 연결 오류 체크
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    return $conn;
}
?>