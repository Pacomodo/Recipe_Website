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

    // 즐겨찾기 제거
    $stmt = $conn->prepare("DELETE FROM favorite WHERE user_id = ? AND recipe_id = ?");
    $stmt->bind_param("si", $user_id, $recipe_id);

    if ($stmt->execute()) {
        header("Location: recipe.php?id=" . $recipe_id);
        exit();
    } else {
        $error = "즐겨찾기 제거 중 오류가 발생했습니다. 다시 시도해 주세요.";
        echo "<p class='error'>$error</p>";
    }
}

$conn->close();
?>
