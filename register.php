<?php
session_start();
include 'config.php'; // 데이터베이스 연결 설정 파일

// 데이터베이스 연결
$conn = connectDatabase();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $email = trim($_POST['email']);
    $profileImage = isset($_FILES['profile_image']) ? $_FILES['profile_image']['tmp_name'] : null;

    // 입력값 유효성 검사
    if (empty($username) || empty($password) || empty($email)) {
        $error = "모든 필수 입력란을 채워주세요.";
    } elseif (strlen($password) < 8 || !preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
        $error = "비밀번호는 최소 8자 이상이어야 하며 특수 문자를 포함해야 합니다.";
    } else {
        // 사용자명 또는 이메일 중복 확인
        $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "이미 존재하는 사용자명 또는 이메일입니다.";
        } else {
            // 비밀번호 해시
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // 프로필 이미지 처리
            $profileImageContent = $profileImage ? file_get_contents($profileImage) : null;

            // 사용자 정보 저장
            $stmt = $conn->prepare("INSERT INTO users (user_id, password, email, profile_image) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $username, $hashedPassword, $email, $profileImageContent);

            if ($stmt->execute()) {
                $_SESSION['user_id'] = $username;
                header("Location: index.php");
                exit();
            } else {
                $error = "회원 가입 중 오류가 발생했습니다. 다시 시도해주세요.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>회원 가입 - 레시피 세상</title>
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
        <h2>회원 가입</h2>
        <?php if (isset($error)): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <form action="register.php" method="post" enctype="multipart/form-data">
            <label for="username">사용자명:</label>
            <input type="text" id="username" name="username" required>

            <label for="password">비밀번호:</label>
            <input type="password" id="password" name="password" required>

            <label for="email">이메일:</label>
            <input type="email" id="email" name="email" required>

            <label for="profile_image">프로필 사진 (선택 사항):</label>
            <input type="file" id="profile_image" name="profile_image" accept="image/*">

            <button type="submit">회원 가입</button>
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
