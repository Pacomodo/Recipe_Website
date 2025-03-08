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

// 레시피 ID 확인
if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$recipe_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

// 레시피 정보 가져오기
$stmt = $conn->prepare("SELECT * FROM recipe WHERE recipe_id = ? AND user_id = ?");
if ($stmt === false) {
    die('prepare() failed: ' . htmlspecialchars($conn->error));
}
$stmt->bind_param("is", $recipe_id, $user_id);
$stmt->execute();
$recipe = $stmt->get_result()->fetch_assoc();

// 레시피가 존재하지 않거나 작성자가 아닌 경우
if (!$recipe) {
    header("Location: index.php");
    exit();
}

// 기존 카테고리, 재료, 조리 시간 가져오기
$cook_time_stmt = $conn->prepare("SELECT ct.cook_time FROM recipe_cook_time rct JOIN cook_times ct ON rct.cook_time_id = ct.cook_time_id WHERE rct.recipe_id = ?");
if ($cook_time_stmt === false) {
    die('prepare() failed: ' . htmlspecialchars($conn->error));
}
$cook_time_stmt->bind_param("i", $recipe_id);
$cook_time_stmt->execute();
$cook_time = $cook_time_stmt->get_result()->fetch_assoc()['cook_time'];

$category_stmt = $conn->prepare("SELECT rc.category_name FROM recipe_category rc JOIN category c ON rc.category_name = c.category_name WHERE rc.recipe_id = ?");
if ($category_stmt === false) {
    die('prepare() failed: ' . htmlspecialchars($conn->error));
}
$category_stmt->bind_param("i", $recipe_id);
$category_stmt->execute();
$category = $category_stmt->get_result()->fetch_assoc()['category_name'];

$ingredients_stmt = $conn->prepare("SELECT i.ingredient FROM recipe_ingredient ri JOIN ingredients i ON ri.ingredient_id = i.ingredient_id WHERE ri.recipe_id = ?");
if ($ingredients_stmt === false) {
    die('prepare() failed: ' . htmlspecialchars($conn->error));
}
$ingredients_stmt->bind_param("i", $recipe_id);
$ingredients_stmt->execute();
$ingredients_result = $ingredients_stmt->get_result();
$ingredients = [];
while ($row = $ingredients_result->fetch_assoc()) {
    $ingredients[] = $row['ingredient'];
}
$ingredients = implode(", ", $ingredients);

// 레시피 수정 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
            // 레시피 업데이트
            $stmt = $conn->prepare("UPDATE recipe SET recipe_name = ?, description = ?, recipe_text = ?, recipe_image = ? WHERE recipe_id = ? AND user_id = ?");
            if ($stmt === false) {
                throw new Exception('prepare() failed: ' . htmlspecialchars($conn->error));
            }
            $stmt->bind_param("sssssi", $recipe_name, $description, $recipe_text, $recipeImageContent, $recipe_id, $user_id);
            $stmt->execute();

            // 기존 카테고리, 재료, 조리 시간 삭제
            $stmt = $conn->prepare("DELETE FROM recipe_cook_time WHERE recipe_id = ?");
            if ($stmt === false) {
                throw new Exception('prepare() failed: ' . htmlspecialchars($conn->error));
            }
            $stmt->bind_param("i", $recipe_id);
            $stmt->execute();

            $stmt = $conn->prepare("DELETE FROM recipe_category WHERE recipe_id = ?");
            if ($stmt === false) {
                throw new Exception('prepare() failed: ' . htmlspecialchars($conn->error));
            }
            $stmt->bind_param("i", $recipe_id);
            $stmt->execute();

            $stmt = $conn->prepare("DELETE FROM recipe_ingredient WHERE recipe_id = ?");
            if ($stmt === false) {
                throw new Exception('prepare() failed: ' . htmlspecialchars($conn->error));
            }
            $stmt->bind_param("i", $recipe_id);
            $stmt->execute();

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
            $error = "레시피 수정 중 오류가 발생했습니다. 다시 시도해 주세요.";
            error_log("Error editing recipe: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>레시피 수정 - 레시피 세상</title>
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
        <h2>레시피 수정</h2>
        <?php if (isset($error)): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <form action="edit_recipe.php?id=<?= $recipe_id ?>" method="post" enctype="multipart/form-data">
            <label for="recipe_name">레시피 이름:</label>
            <input type="text" id="recipe_name" name="recipe_name" value="<?= htmlspecialchars($recipe['recipe_name']) ?>" required>

            <label for="description">설명:</label>
            <textarea id="description" name="description"><?= htmlspecialchars($recipe['description']) ?></textarea>

            <label for="recipe_text">조리법:</label>
            <textarea id="recipe_text" name="recipe_text" required><?= htmlspecialchars($recipe['recipe_text']) ?></textarea>

            <label for="cook_time">조리 시간:</label>
            <input type="text" id="cook_time" name="cook_time" value="<?= htmlspecialchars($cook_time) ?>" required>

            <label for="category">카테고리:</label>
            <input type="text" id="category" name="category" value="<?= htmlspecialchars($category) ?>" required>

            <label for="ingredients">재료 (쉼표로 구분):</label>
            <textarea id="ingredients" name="ingredients" required><?= htmlspecialchars($ingredients) ?></textarea>

            <label for="recipe_image">레시피 이미지 (선택 사항):</label>
            <input type="file" id="recipe_image" name="recipe_image" accept="image/*">

            <button type="submit">레시피 수정</button>
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
