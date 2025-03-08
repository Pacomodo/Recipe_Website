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

// 사용자가 작성한 레시피 목록 가져오기
$stmt = $conn->prepare("SELECT recipe_id, recipe_name, description, recipe_image FROM recipe WHERE user_id = ?");
if ($stmt === false) {
    die('prepare() failed: ' . htmlspecialchars($conn->error));
}
$stmt->bind_param("s", $current_user_id);
$stmt->execute();
$recipes = $stmt->get_result();

// 레시피 삭제 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_recipe_id'])) {
    $recipe_id_to_delete = $_POST['delete_recipe_id'];

    // 레시피 삭제 쿼리
    $stmt = $conn->prepare("DELETE FROM recipe WHERE recipe_id = ? AND user_id = ?");
    if ($stmt === false) {
        die('prepare() failed: ' . htmlspecialchars($conn->error));
    }
    $stmt->bind_param("is", $recipe_id_to_delete, $current_user_id);
    if ($stmt->execute()) {
        $delete_message = "레시피가 성공적으로 삭제되었습니다.";
    } else {
        $delete_message = "레시피 삭제 중 오류가 발생했습니다. 다시 시도해 주세요.";
    }

    // 페이지 새로고침을 통해 업데이트된 레시피 목록을 반영
    header("Location: manage_recipes.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>레시피 관리 - 레시피 세상</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <h1>레시피 관리</h1>
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
        <h2>내 레시피 관리</h2>
        <?php if (isset($delete_message)): ?>
            <p><?= htmlspecialchars($delete_message) ?></p>
        <?php endif; ?>
        <div class="recipe-list">
            <?php if ($recipes->num_rows > 0): ?>
                <?php while($row = $recipes->fetch_assoc()): ?>
                    <div class="recipe">
                        <?php if (!empty($row['recipe_image'])): ?>
                            <img src="data:image/jpeg;base64,<?= base64_encode($row['recipe_image']) ?>" alt="레시피 이미지">
                        <?php else: ?>
                            <img src="default-recipe.png" alt="레시피 이미지">
                        <?php endif; ?>
                        <h3><?= htmlspecialchars($row['recipe_name']) ?></h3>
                        <p><?= htmlspecialchars($row['description']) ?></p>
                        <a href="edit_recipe.php?id=<?= $row['recipe_id'] ?>">수정</a>
                        <form action="manage_recipes.php" method="post" onsubmit="return confirm('정말로 이 레시피를 삭제하시겠습니까?');">
                            <input type="hidden" name="delete_recipe_id" value="<?= $row['recipe_id'] ?>">
                            <button type="submit">삭제</button>
                        </form>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>아직 작성한 레시피가 없습니다.</p>
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
