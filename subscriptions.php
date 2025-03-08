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

// 구독한 사용자 가져오기
$stmt = $conn->prepare("SELECT u.user_id, u.email FROM subscribe s JOIN users u ON s.subscribe_id = u.user_id WHERE s.user_id = ?");
if ($stmt === false) {
    die('prepare() failed: ' . htmlspecialchars($conn->error));
}
$stmt->bind_param("s", $current_user_id);
$stmt->execute();
$subscribed_users = $stmt->get_result();

// 모든 사용자 가져오기 (자신과 이미 구독한 사용자는 제외)
$stmt = $conn->prepare("SELECT user_id, email FROM users WHERE user_id != ? AND user_id NOT IN (SELECT subscribe_id FROM subscribe WHERE user_id = ?)");
if ($stmt === false) {
    die('prepare() failed: ' . htmlspecialchars($conn->error));
}
$stmt->bind_param("ss", $current_user_id, $current_user_id);
$stmt->execute();
$all_users = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>구독 관리 - 레시피 세상</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <h1>레시피 세상</h1>
        <nav>
            <ul>
                <li><a href="index.php">홈</a></li>
                <li><a href="profile.php">프로필</a></li>
                <li><a href="logout.php">로그아웃</a></li>
                <li><a href="add_recipe.php">레시피 추가</a></li>
                <li><a href="index.php#favorites">즐겨찾기</a></li>
                <li><a href="subscriptions.php">구독 관리</a></li>
                <li><a href="search_user.php">사용자 검색</a></li>
            </ul>
        </nav>
    </header>
    
    <main>
        <section>
            <h2>내가 구독한 사용자</h2>
            <div class="user-list">
                <?php if ($subscribed_users->num_rows > 0): ?>
                    <?php while($row = $subscribed_users->fetch_assoc()): ?>
                        <div class="user">
                            <p>사용자명: <?= htmlspecialchars($row['user_id']) ?></p>
                            <p>이메일: <?= htmlspecialchars($row['email']) ?></p>
                            <a href="unsubscribe.php?subscribe_id=<?= $row['user_id'] ?>" onclick="return confirm('정말로 구독을 취소하시겠습니까?')">구독 취소</a>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>구독한 사용자가 없습니다.</p>
                <?php endif; ?>
            </div>
        </section>

        <section>
            <h2>다른 사용자 구독</h2>
            <div class="user-list">
                <?php if ($all_users->num_rows > 0): ?>
                    <?php while($row = $all_users->fetch_assoc()): ?>
                        <div class="user">
                            <p>사용자명: <?= htmlspecialchars($row['user_id']) ?></p>
                            <p>이메일: <?= htmlspecialchars($row['email']) ?></p>
                            <a href="subscribe.php?user_id=<?= $row['user_id'] ?>">구독하기</a>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>구독할 사용자가 없습니다.</p>
                <?php endif; ?>
            </div>
        </section>
    </main>
    
    <footer>
        <p>&copy; 2024 레시피 세상. All right reserved.</p>
    </footer>
</body>
</html>

<?php
$conn->close(); // 데이터베이스 연결 종료
?>
