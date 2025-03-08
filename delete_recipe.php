<?php
session_start();
include 'config.php'; // 데이터베이스 연결 설정 파일

// 사용자 인증 확인
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 데이터베이스 연결
$conn = connectDatabase();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $recipe_id = intval($_POST['recipe_id']);

    // 레시피 삭제
    $stmt = $conn->prepare("DELETE FROM recipe WHERE recipe_id = ? AND user_id = ?");
    $stmt->bind_param("is", $recipe_id, $user_id);

    if ($stmt->execute()) {
        header("Location: index.php");
        exit();
    } else {
        $error = "레시피 삭제 중 오류가 발생했습니다. 다시 시도해 주세요.";
        echo "<p class='error'>$error</p>";
    }
}

$conn->close();
?>
