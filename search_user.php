<?php
session_start();
include 'config.php'; // 데이터베이스 연결 설정 파일

// 데이터베이스 연결
$conn = connectDatabase();

$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';

// SQL 쿼리 생성
$sql = "SELECT user_id, email FROM users WHERE user_id LIKE ? OR email LIKE ?";
$params = ["%$keyword%", "%$keyword%"];

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die('prepare() failed: ' . htmlspecialchars($conn->error));
}

$stmt->bind_param("ss", ...$params);
$stmt->execute();
$results = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>사용자 검색 - 레시피 세상</title>
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
                <?php else: ?>
                    <li><a href="login.php">로그인</a></li>
                    <li><a href="register.php">회원 가입</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>
    
    <main>
        <h2>사용자 검색</h2>
        <form method="get" action="search_user.php">
            <input type="text" name="keyword" placeholder="사용자명 또는 이메일 검색">
            <button type="submit">검색</button>
        </form>

        <div class="user-list">
            <?php if ($results->num_rows > 0): ?>
                <?php while($row = $results->fetch_assoc()): ?>
                    <div class="user">
                        <p>사용자명: <?= htmlspecialchars($row['user_id']) ?></p>
                        <p>이메일: <?= htmlspecialchars($row['email']) ?></p>
                        <a href="subscribe.php?user_id=<?= $row['user_id'] ?>">구독하기</a>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>검색 결과가 없습니다.</p>
            <?php endif; ?>
        </div>
    </main>
    
    <footer>
        <p>&copy; 2024 레시피 세상. All right reserved.</p>
    </footer>
</body>
</html>

<?php
$conn->close(); // 데이터베이스 연결 종료
?>
