<?php
session_start();
include 'config.php'; // 데이터베이스 연결 설정 파일

// 데이터베이스 연결
$conn = connectDatabase();

$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$ingredient = isset($_GET['ingredient']) ? trim($_GET['ingredient']) : '';
$category = isset($_GET['category']) ? trim($_GET['category']) : '';
$cook_time = isset($_GET['cook_time']) ? trim($_GET['cook_time']) : '';

// SQL 쿼리 생성
$sql = "SELECT DISTINCT r.recipe_id, r.recipe_name, r.description, r.recipe_image 
        FROM recipe r 
        LEFT JOIN recipe_ingredient ri ON r.recipe_id = ri.recipe_id 
        LEFT JOIN ingredients i ON ri.ingredient_id = i.ingredient_id
        LEFT JOIN recipe_category rc ON r.recipe_id = rc.recipe_id 
        LEFT JOIN recipe_cook_time rct ON r.recipe_id = rct.recipe_id 
        LEFT JOIN cook_times ct ON rct.cook_time_id = ct.cook_time_id
        WHERE 1=1";

$params = [];
$types = '';

if ($keyword) {
    $sql .= " AND (r.recipe_name LIKE ? OR r.description LIKE ?)";
    $params[] = "%$keyword%";
    $params[] = "%$keyword%";
    $types .= 'ss';
}

if ($ingredient) {
    $sql .= " AND i.ingredient = ?";
    $params[] = $ingredient;
    $types .= 's';
}

if ($category) {
    $sql .= " AND rc.category_name = ?";
    $params[] = $category;
    $types .= 's';
}

if ($cook_time) {
    $sql .= " AND ct.cook_time = ?";
    $params[] = $cook_time;
    $types .= 's';
}

$stmt = $conn->prepare($sql);

if ($stmt === false) {
    die('prepare() failed: ' . htmlspecialchars($conn->error));
}

if ($params) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$results = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>검색 결과 - 레시피 세상</title>
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
        <h2>검색 결과</h2>
        <div class="recipe-list">
            <?php if ($results->num_rows > 0): ?>
                <?php while($row = $results->fetch_assoc()): ?>
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

