<?php
session_start();
include 'config.php'; // 데이터베이스 연결 설정 파일

// 데이터베이스 연결
$conn = connectDatabase();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username_or_email = trim($_POST['username_or_email']);
    $password = trim($_POST['password']);

    // 입력값 유효성 검사
    if (empty($username_or_email) || empty($password)) {
        $error = "모든 필수 입력란을 채워주세요.";
    } else {
        // 사용자명 또는 이메일로 사용자 정보 조회
        $stmt = $conn->prepare("SELECT user_id, password FROM users WHERE user_id = ? OR email = ?");
        $stmt->bind_param("ss", $username_or_email, $username_or_email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            // 비밀번호 확인
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['user_id'];
                header("Location: index.php");
                exit();
            } else {
                $error = "잘못된 비밀번호입니다.";
            }
        } else {
            $error = "사용자명 또는 이메일을 찾을 수 없습니다.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>로그인 - 레시피 세상</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <h1>레시피 세상</h1>
        <nav>
            <ul>
                <li><a href="index.php">홈</a></li>
                <li><a href="login.php">로그인</a></li>
                <li><a href="register.php">회원 가입</a></li>
            </ul>
        </nav>
    </header>
    
    <main>
        <h2>로그인</h2>
        <?php if (isset($error)): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <form action="login.php" method="post">
            <label for="username_or_email">사용자명 또는 이메일:</label>
            <input type="text" id="username_or_email" name="username_or_email" required>

            <label for="password">비밀번호:</label>
            <input type="password" id="password" name="password" required>

            <button type="submit">로그인</button>
        </form>
    </main>

    <footer>
        <p>&copy; 2024 레시피 세상. All right reserved.</p>
    </footer>
</body>
</html>

<?php
$conn->close(); // 데이터베이스 연결 종료
?>