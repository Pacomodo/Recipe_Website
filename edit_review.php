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

// 리뷰 ID와 레시피 ID 확인
if (!isset($_GET['review_id']) || !isset($_GET['recipe_id'])) {
    header("Location: index.php");
    exit();
}

$review_id = intval($_GET['review_id']);
$recipe_id = intval($_GET['recipe_id']);

// 리뷰 정보 가져오기
$stmt = $conn->prepare("SELECT review_text, rating FROM review WHERE review_id = ? AND user_id = ?");
$stmt->bind_param("is", $review_id, $_SESSION['user_id']);
$stmt->execute();
$review = $stmt->get_result()->fetch_assoc();

// 리뷰가 존재하지 않거나 작성자가 아닌 경우
if (!$review) {
    header("Location: recipe.php?id=" . $recipe_id);
    exit();
}

// 리뷰 수정 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $review_text = trim($_POST['review_text']);
    $rating = intval($_POST['rating']);

    // 입력값 유효성 검사
    if (empty($review_text) || $rating < 1 || $rating > 5) {
        $error = "리뷰와 평점을 올바르게 입력해 주세요.";
    } else {
        // 리뷰 수정
        $stmt = $conn->prepare("UPDATE review SET review_text = ?, rating = ? WHERE review_id = ? AND user_id = ?");
        $stmt->bind_param("siii", $review_text, $rating, $review_id, $_SESSION['user_id']);

        if ($stmt->execute()) {
            header("Location: recipe.php?id=" . $recipe_id);
            exit();
        } else {
            $error = "리뷰 수정 중 오류가 발생했습니다. 다시 시도해 주세요.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>리뷰 수정 - 레시피 세상</title>
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
            </ul>
        </nav>
    </header>
    
    <main>
        <h2>리뷰 수정</h2>
        <?php if (isset($error)): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <form action="edit_review.php?review_id=<?= $review_id ?>&recipe_id=<?= $recipe_id ?>" method="post">
            <label for="review_text">리뷰:</label>
            <textarea id="review_text" name="review_text" required><?= htmlspecialchars($review['review_text']) ?></textarea>
            <label for="rating">평점:</label>
            <select id="rating" name="rating" required>
                <option value="1" <?= $review['rating'] == 1 ? 'selected' : '' ?>>1</option>
                <option value="2" <?= $review['rating'] == 2 ? 'selected' : '' ?>>2</option>
                <option value="3" <?= $review['rating'] == 3 ? 'selected' : '' ?>>3</option>
                <option value="4" <?= $review['rating'] == 4 ? 'selected' : '' ?>>4</option>
                <option value="5" <?= $review['rating'] == 5 ? 'selected' : '' ?>>5</option>
            </select>
            <button type="submit">리뷰 수정</button>
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
