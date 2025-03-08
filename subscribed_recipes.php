<?php
session_start();
include 'config.php'; // 데이터베이스 연결 설정 파일

// 데이터베이스 연결
$conn = connectDatabase();

// 사용자 인증 확인
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];

// 구독한 사용자의 레시피 목록 가져오기
$stmt = $conn->prepare("
    SELECT r.recipe_id, r.recipe_name, r.description, r.recipe_image, u.user_id 
    FROM recipe r 
    JOIN subscribe s ON r.user_id = s.subscribe_id 
    JOIN users u ON u.user_id = s.subscribe_id 
    WHERE s.user_id = ?
");
if ($stmt === false) {
    die('prepare() failed: ' . htmlspecialchars($conn->error));
}
$stmt->bind_param("s", $current_user_id);
$stmt->execute();
$subscribed_recipes = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>구독한 사람의 레시피 보기 - 레시피 세상</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <h1>구독한 사람의 레시피 보기</h1>
        <nav>
            <ul>
                <li><a href="index.php">홈</a></li>
                <li><a href="profile.php">프로필</a></li>
                <li><a href="logout.php">로그아웃</a></li>
                <li><a href="subscriptions.php">구독 관리</a></li>
                <li><a href="index.php#favorites">즐겨찾기</a></li>
                <li><a href="search_user.php">사용자 검색</a></li>
            </ul>
        </nav>
    </header>
    
    <main>
        <section>
            <h2>구독한 사용자의 레시피</h2>
            <div class="recipe-list">
                <?php if ($subscribed_recipes->num_rows > 0): ?>
                    <?php while($row = $subscribed_recipes->fetch_assoc()): ?>
                        <div class="recipe">
                            <?php if (!empty($row['recipe_image'])): ?>
                                <img src="data:image/jpeg;base64,<?= base64_encode($row['recipe_image']) ?>" alt="레시피 이미지">
                            <?php else: ?>
                                <img src="default-recipe.png" alt="레시피 이미지">
                            <?php endif; ?>
                            <h3><?= htmlspecialchars($row['recipe_name']) ?></h3>
                            <p><?= htmlspecialchars($row['description']) ?></p>
                            <p>작성자: <?= htmlspecialchars($row['user_id']) ?></p>
                            <a href="recipe.php?id=<?= $row['recipe_id'] ?>">자세히 보기</a>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>구독한 사용자의 레시피가 없습니다.</p>
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
