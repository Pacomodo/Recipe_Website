<?php
session_start();
include 'config.php'; // 데이터베이스 연결 설정 파일

// 데이터베이스 연결
$conn = connectDatabase();

// 레시피 ID 확인
if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$recipe_id = intval($_GET['id']);
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// 레시피 정보 가져오기
$stmt = $conn->prepare("SELECT r.recipe_name, r.description, r.recipe_text, r.recipe_image, u.user_id, u.email FROM recipe r JOIN users u ON r.user_id = u.user_id WHERE r.recipe_id = ?");
$stmt->bind_param("i", $recipe_id);
$stmt->execute();
$recipe = $stmt->get_result()->fetch_assoc();

// 레시피가 존재하지 않는 경우
if (!$recipe) {
    header("Location: index.php");
    exit();
}

// 리뷰 및 평점 가져오기
$review_stmt = $conn->prepare("SELECT r.review_id, r.review_text, r.rating, u.user_id FROM review r JOIN users u ON r.user_id = u.user_id WHERE r.recipe_id = ?");
$review_stmt->bind_param("i", $recipe_id);
$review_stmt->execute();
$reviews = $review_stmt->get_result();

// 즐겨찾기 여부 확인
$is_favorite = false;
if ($user_id) {
    $favorite_stmt = $conn->prepare("SELECT * FROM favorite WHERE user_id = ? AND recipe_id = ?");
    $favorite_stmt->bind_param("si", $user_id, $recipe_id);
    $favorite_stmt->execute();
    $is_favorite = $favorite_stmt->get_result()->num_rows > 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($recipe['recipe_name']) ?> - 레시피 상세 정보</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <h1>레시피 세상</h1>
        <nav>
            <ul>
                <li><a href="index.php">홈</a></li>
                <?php if ($user_id): ?>
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
        <h2><?= htmlspecialchars($recipe['recipe_name']) ?></h2>
        <?php if (!empty($recipe['recipe_image'])): ?>
            <img src="data:image/jpeg;base64,<?= base64_encode($recipe['recipe_image']) ?>" alt="레시피 이미지" style="width:300px;">
        <?php endif; ?>
        <p><strong>설명:</strong> <?= nl2br(htmlspecialchars($recipe['description'])) ?></p>
        <p><strong>조리법:</strong> <?= nl2br(htmlspecialchars($recipe['recipe_text'])) ?></p>
        <p><strong>작성자:</strong> <?= htmlspecialchars($recipe['user_id']) ?> (<?= htmlspecialchars($recipe['email']) ?>)</p>
        
        <?php if ($user_id && $user_id == $recipe['user_id']): ?>
            <a href="edit_recipe.php?id=<?= $recipe_id ?>">레시피 수정</a>
            <form action="delete_recipe.php" method="post" style="display:inline;">
                <input type="hidden" name="recipe_id" value="<?= $recipe_id ?>">
                <button type="submit">레시피 삭제</button>
            </form>
        <?php endif; ?>
        
        <?php if ($user_id): ?>
            <?php if ($is_favorite): ?>
                <form action="remove_favorite.php" method="post" style="display:inline;">
                    <input type="hidden" name="recipe_id" value="<?= $recipe_id ?>">
                    <button type="submit">즐겨찾기 제거</button>
                </form>
            <?php else: ?>
                <form action="add_favorite.php" method="post" style="display:inline;">
                    <input type="hidden" name="recipe_id" value="<?= $recipe_id ?>">
                    <button type="submit">즐겨찾기 추가</button>
                </form>
            <?php endif; ?>
        <?php endif; ?>

        <section>
            <h3>리뷰 및 평점</h3>
            <?php if ($reviews->num_rows > 0): ?>
                <?php while($review = $reviews->fetch_assoc()): ?>
                    <div class="review">
                        <p><strong><?= htmlspecialchars($review['user_id']) ?>:</strong> <?= nl2br(htmlspecialchars($review['review_text'])) ?></p>
                        <p>평점: <?= htmlspecialchars($review['rating']) ?>/5</p>
                        <?php if ($user_id && $user_id == $review['user_id']): ?>
                            <a href="edit_review.php?review_id=<?= $review['review_id'] ?>&recipe_id=<?= $recipe_id ?>">리뷰 수정</a>
                            <form action="delete_review.php" method="post" style="display:inline;">
                                <input type="hidden" name="review_id" value="<?= $review['review_id'] ?>">
                                <input type="hidden" name="recipe_id" value="<?= $recipe_id ?>">
                                <button type="submit">리뷰 삭제</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>아직 리뷰가 없습니다.</p>
            <?php endif; ?>
        </section>
        
        <?php if ($user_id): ?>
            <section>
                <h3>리뷰 작성</h3>
                <form action="add_review.php" method="post">
                    <input type="hidden" name="recipe_id" value="<?= $recipe_id ?>">
                    <label for="review_text">리뷰:</label>
                    <textarea id="review_text" name="review_text" required></textarea>
                    <label for="rating">평점:</label>
                    <select id="rating" name="rating" required>
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                        <option value="5">5</option>
                    </select>
                    <button type="submit">리뷰 제출</button>
                </form>
            </section>
        <?php else: ?>
            <p><a href="login.php">로그인</a> 후 리뷰를 작성할 수 있습니다.</p>
        <?php endif; ?>
    </main>

    <footer>
        <p>&copy; 2024 레시피 세상. All right reserved.</p>
    </footer>
</body>
</html>

<?php
$conn->close(); // 데이터베이스 연결 종료
?>
