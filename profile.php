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
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $new_password = trim($_POST['new_password']);
    $profile_image = isset($_FILES['profile_image']) ? $_FILES['profile_image']['tmp_name'] : null;

    // 입력값 유효성 검사
    if (empty($email)) {
        $error = "이메일은 필수 항목입니다.";
    } elseif (!empty($new_password) && (strlen($new_password) < 8 || !preg_match('/[!@#$%^&*(),.?":{}|<>]/', $new_password))) {
        $error = "비밀번호는 최소 8자 이상이어야 하며 특수 문자를 포함해야 합니다.";
    } else {
        // 이메일 중복 확인
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND user_id != ?");
        $stmt->bind_param("ss", $email, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error = "이미 사용 중인 이메일입니다.";
        } else {
            // 비밀번호 해시
            $hashedPassword = !empty($new_password) ? password_hash($new_password, PASSWORD_DEFAULT) : null;

            // 프로필 이미지 처리
            $profileImageContent = $profile_image ? file_get_contents($profile_image) : null;

            // 사용자 정보 업데이트
            $sql = "UPDATE users SET email = ?";
            if ($hashedPassword) {
                $sql .= ", password = '$hashedPassword'";
            }
            if ($profileImageContent) {
                $sql .= ", profile_image = ?";
            }
            $sql .= " WHERE user_id = ?";

            $stmt = $conn->prepare($sql);
            if ($profileImageContent) {
                $stmt->bind_param("sbss", $email, $profileImageContent, $profileImageContent, $user_id);
            } else {
                $stmt->bind_param("ss", $email, $user_id);
            }

            if ($stmt->execute()) {
                $success = "프로필이 성공적으로 업데이트되었습니다.";
            } else {
                $error = "프로필 업데이트 중 오류가 발생했습니다. 다시 시도해주세요.";
            }
        }
    }
}

// 사용자 정보 가져오기
$stmt = $conn->prepare("SELECT user_id, email, profile_image FROM users WHERE user_id = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>프로필 관리 - 레시피 세상</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <h1>레시피 세상</h1>
        <nav>
            <ul>
                <li><a href="index.php">홈</a></li>
                <li><a href="logout.php">로그아웃</a></li>
            </ul>
        </nav>
    </header>
    
    <main>
        <h2>프로필 관리</h2>
        <?php if (isset($error)): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <?php if (isset($success)): ?>
            <p class="success"><?= htmlspecialchars($success) ?></p>
        <?php endif; ?>
        <form action="profile.php" method="post" enctype="multipart/form-data">
            <label for="user_id">사용자명:</label>
            <input type="text" id="user_id" name="user_id" value="<?= htmlspecialchars($user['user_id']) ?>" disabled>

            <label for="email">이메일:</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>

            <label for="new_password">새 비밀번호 (선택 사항):</label>
            <input type="password" id="new_password" name="new_password">

            <label for="profile_image">프로필 사진 (선택 사항):</label>
            <input type="file" id="profile_image" name="profile_image" accept="image/*">
            <?php if (!empty($user['profile_image'])): ?>
                <img src="data:image/jpeg;base64,<?= base64_encode($user['profile_image']) ?>" alt="프로필 이미지" style="width:100px;">
            <?php endif; ?>

            <button type="submit">업데이트</button>
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
