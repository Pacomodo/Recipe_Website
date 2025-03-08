<?php
session_start();
include 'config.php'; // 데이터베이스 연결 설정 파일

// 데이터베이스 연결
$conn = connectDatabase();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];
$subscribe_user_id = isset($_GET['subscribe_id']) ? trim($_GET['subscribe_id']) : '';

if ($subscribe_user_id) {
    // 구독 취소
    $stmt = $conn->prepare("DELETE FROM subscribe WHERE user_id = ? AND subscribe_id = ?");
    if ($stmt === false) {
        die('prepare() failed: ' . htmlspecialchars($conn->error));
    }
    $stmt->bind_param("ss", $current_user_id, $subscribe_user_id);
    if ($stmt->execute()) {
        $message = "구독이 성공적으로 취소되었습니다.";
    } else {
        $message = "구독 취소 중 오류가 발생했습니다. 다시 시도해 주세요.";
    }
} else {
    $message = "잘못된 사용자 ID입니다.";
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>구독 취소 - 레시피 세상</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <h1>레시피 세상</h1>
        <nav>
            <ul>
                <li><a href="index.php">홈</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li><a href="profile.php">프로필</a></li>
                    <li><a href="logout.php">로그아웃</a></li>
                    <li><a href="add_recipe.php">레시피 추가</a></li>
                    <li><a href="index.php#favorites">즐겨찾기</a></li>
                    <li><a href="search_user.php">사용자 검색</a></li>
                <?php else: ?>
                    <li><a href="login.php">로그인</a></li>
                    <li><a href="register.php">회원 가입</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>
    
    <main>
        <h2>구독 취소 결과</h2>
        <p><?= htmlspecialchars($message) ?></p>
        <a href="index.php">돌아가기</a>
    </main>
    
    <footer>
        <p>&copy; 2024 레시피 세상. All right reserved.</p>
    </footer>
</body>
</html>

<?php
$conn->close(); // 데이터베이스 연결 종료
?>
