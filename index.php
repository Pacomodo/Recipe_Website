<?php
session_start();
include 'config.php'; // 데이터베이스 연결 설정 파일

// 데이터베이스 연결
$conn = connectDatabase();

// 최근 추가된 레시피를 가져오는 함수
function getRecentRecipes($conn) {
    $sql = "SELECT recipe_id, recipe_name, description, recipe_image FROM recipe ORDER BY recipe_id DESC LIMIT 5";
    $result = $conn->query($sql);
    return $result;
}

// 즐겨찾기한 레시피를 가져오는 함수
function getFavoriteRecipes($conn, $user_id) {
    $stmt = $conn->prepare("SELECT r.recipe_id, r.recipe_name, r.description, r.recipe_image 
                            FROM recipe r 
                            JOIN favorite f ON r.recipe_id = f.recipe_id 
                            WHERE f.user_id = ?");
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    return $stmt->get_result();
}

// 구독한 사용자의 레시피를 가져오는 함수
function getSubscribedRecipes($conn, $user_id) {
    $stmt = $conn->prepare("
        SELECT r.recipe_id, r.recipe_name, r.description, r.recipe_image, u.user_id 
        FROM recipe r 
        JOIN subscribe s ON r.user_id = s.subscribe_id 
        JOIN users u ON u.user_id = s.subscribe_id 
        WHERE s.user_id = ?
    ");
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    return $stmt->get_result();
}

// 연결 오류 체크
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$recipes = getRecentRecipes($conn);
$favorites = isset($_SESSION['user_id']) ? getFavoriteRecipes($conn, $_SESSION['user_id']) : null;
$subscribed_recipes = isset($_SESSION['user_id']) ? getSubscribedRecipes($conn, $_SESSION['user_id']) : null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>레시피 세상</title>
    <link rel="stylesheet" href="styles.css"> <!-- 스타일시트 연결 -->
</head>
<body>
    <header>
        <h1>레시피 세상</h1>
        <nav>
            <ul>
                <li><a href="index.php">홈</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li>
                        <a href="#">레시피</a>
                        <ul>
                            <li><a href="add_recipe.php">레시피 추가</a></li>
                            <li><a href="manage_recipes.php">레시피 관리</a></li>
                            <li><a href="subscribed_recipes.php">내가 구독한 사람의 레시피 보기</a></li>
                        </ul>
                    </li>
                    <li><a href="profile.php">프로필</a></li>
                    <li><a href="logout.php">로그아웃</a></li>
                    <li><a href="subscriptions.php">구독 관리</a></li>
                    <li><a href="index.php#favorites">즐겨찾기</a></li>
                    <li><a href="search_user.php">사용자 검색</a></li>
                <?php else: ?>
                    <li><a href="login.php">로그인</a></li>
                    <li><a href="register.php">회원 가입</a></li>
                <?php endif; ?>
            </ul>
        </nav>
        <form action="search_results.php" method="get">
            <input type="text" name="keyword" placeholder="키워드 검색">
            <input type="text" name="ingredient" placeholder="재료 검색">
            <input type="text" name="category" placeholder="카테고리 검색">
            <input type="text" name="cook_time" placeholder="조리 시간 검색">
            <button type="submit">검색</button>
        </form>
    </header>
    
    <main>
        <section>
            <h2>최근 추가된 레시피</h2>
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
                            <a href="recipe.php?id=<?= $row['recipe_id'] ?>">자세히 보기</a>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>아직 레시피가 없습니다.</p>
                <?php endif; ?>
            </div>
        </section>
        
        <?php if ($favorites && $favorites->num_rows > 0): ?>
            <section id="favorites">
                <h2>즐겨찾기한 레시피</h2>
                <div class="recipe-list">
                    <?php while($row = $favorites->fetch_assoc()): ?>
                        <div class="recipe">
                            <?php if (!empty($row['recipe_image'])): ?>
                                <img src="data:image/jpeg;base64,<?= base64_encode($row['recipe_image']) ?>" alt="레시피 이미지">
                            <?php else: ?>
                                <img src="default-recipe.png" alt="레시피 이미지">
                            <?php endif; ?>
                            <h3><?= htmlspecialchars($row['recipe_name']) ?></h3>
                            <p><?= htmlspecialchars($row['description']) ?></p>
                            <a href="recipe.php?id=<?= $row['recipe_id'] ?>">자세히 보기</a>
                        </div>
                    <?php endwhile; ?>
                </div>
            </section>
        <?php endif; ?>
        
        <?php if ($subscribed_recipes && $subscribed_recipes->num_rows > 0): ?>
            <section id="subscribed-recipes">
                <h2>구독한 사용자의 레시피</h2>
                <div class="recipe-list">
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
                </div>
            </section>
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

