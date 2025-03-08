<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include 'config.php'; // 데이터베이스 연결 설정 파일

// 사용자 인증 확인
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 데이터베이스 연결
$conn = connectDatabase();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $recipe_name = trim($_POST['recipe_name']);
    $description = trim($_POST['description']);
    $recipe_text = trim($_POST['recipe_text']);
    $cook_time = trim($_POST['cook_time']);
    $category = trim($_POST['category']);
    $ingredients = trim($_POST['ingredients']);
    $recipe_image = isset($_FILES['recipe_image']) ? $_FILES['recipe_image']['tmp_name'] : null;

    // 입력값 유효성 검사
    if (empty($recipe_name) || empty($recipe_text) || empty($cook_time) || empty($category) || empty($ingredients)) {
        $error = "모든 필수 입력란을 채워주세요.";
    } else {
        // 레시피 이미지 처리
        $recipeImageContent = $recipe_image ? file_get_contents($recipe_image) : null;

        // 트랜잭션 시작
        $conn->begin_transaction();

        try {
            // 레시피 추가
            $stmt = $conn->prepare("INSERT INTO recipe (user_id, recipe_name, description, recipe_text, recipe_image) VALUES (?, ?, ?, ?, ?)");
            if ($stmt === false) {
                throw new Exception('prepare() failed: ' . htmlspecialchars($conn->error));
            }
            $stmt->bind_param("sssss", $user_id, $recipe_name, $description, $recipe_text, $recipeImageContent);
            $stmt->execute();
            $recipe_id = $stmt->insert_id;

            // 조리 시간 추가
            $stmt = $conn->prepare("INSERT INTO cook_times (cook_time) VALUES (?) ON DUPLICATE KEY UPDATE cook_time_id = LAST_INSERT_ID(cook_time_id)");
            if ($stmt === false) {
                throw new Exception('prepare() failed: ' . htmlspecialchars($conn->error));
            }
            $stmt->bind_param("s", $cook_time);
            $stmt->execute();
            $cook_time_id = $stmt->insert_id;

            $stmt = $conn->prepare("INSERT INTO recipe_cook_time (recipe_id, cook_time_id) VALUES (?, ?)");
            if ($stmt === false) {
                throw new Exception('prepare() failed: ' . htmlspecialchars($conn->error));
            }
            $stmt->bind_param("ii", $recipe_id, $cook_time_id);
            $stmt->execute();

            // 카테고리 추가
            $stmt = $conn->prepare("INSERT INTO category (category_name) VALUES (?) ON DUPLICATE KEY UPDATE category_name = category_name");
            if ($stmt === false) {
                throw new Exception('prepare() failed: ' . htmlspecialchars($conn->error));
            }
            $stmt->bind_param("s", $category);
            $stmt->execute();

            $stmt = $conn->prepare("INSERT INTO recipe_category (recipe_id, category_name) VALUES (?, ?)");
            if ($stmt === false) {
                throw new Exception('prepare() failed: ' . htmlspecialchars($conn->error));
            }
            $stmt->bind_param("is", $recipe_id, $category);
            $stmt->execute();

            // 재료 추가
            $ingredients_list = explode(",", $ingredients);
            foreach ($ingredients_list as $ingredient) {
                $stmt = $conn->prepare("INSERT INTO ingredients (ingredient) VALUES (?) ON DUPLICATE KEY UPDATE ingredient_id = LAST_INSERT_ID(ingredient_id)");
                if ($stmt === false) {
                    throw new Exception('prepare() failed: ' . htmlspecialchars($conn->error));
                }
                $stmt->bind_param("s", trim($ingredient));
                $stmt->execute();
                $ingredient_id = $stmt->insert_id;

                $stmt = $conn->prepare("INSERT INTO recipe_ingredient (recipe_id, ingredient_id) VALUES (?, ?)");
                if ($stmt === false) {
                    throw new Exception('prepare() failed: ' . htmlspecialchars($conn->error));
                }
                $stmt->bind_param("ii", $recipe_id, $ingredient_id);
                $stmt->execute();
            }

            // 트랜잭션 커밋
            $conn->commit();

            header("Location: recipe.php?id=" . $recipe_id);
            exit();
        } catch (Exception $e) {
            // 트랜잭션 롤백
            $conn->rollback();
            $error = "레시피 추가 중 오류가 발생했습니다. 다시 시도해 주세요.";
            error_log("Error adding recipe: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta_name="viewport" content="width=device-width, initial-scale=1.0">
    <title>레시피 추가 - 레시피 세상</title>
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
        <h2>레시피 추가</h2>
        <?php if (isset($error)): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <form action="add_recipe.php" method="post" enctype="multipart/form-data">
            <label for="recipe_name">레시피 이름:</label>
            <input type="text" id="recipe_name" name="recipe_name" required>

            <label for="description">설명:</label>
            <textarea id="description" name="description"></textarea>

            <label for="recipe_text">조리법:</label>
            <textarea id="recipe_text" name="recipe_text" required></textarea>

            <label for="cook_time">조리 시간:</label>
            <input type="text" id="cook_time" name="cook_time" required>

            <label for="category">카테고리:</label>
            <input type="text" id="category" name="category" required>

            <label for="ingredients">재료 (쉼표로 구분):</label>
            <textarea id="ingredients" name="ingredients" required></textarea>

            <label for="recipe_image">레시피 이미지 (선택 사항):</label>
            <input type="file" id="recipe_image" name="recipe_image" accept="image/*">

            <button type="submit">레시피 추가</button>
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