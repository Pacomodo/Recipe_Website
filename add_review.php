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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $recipe_id = intval($_POST['recipe_id']);
    $review_text = trim($_POST['review_text']);
    $rating = intval($_POST['rating']);

    // 입력값 유효성 검사
    if (empty($review_text) || $rating < 1 || $rating > 5) {
        $error = "리뷰와 평점을 올바르게 입력해 주세요.";
    } else {
        // 이미 리뷰가 있는지 확인
        $stmt = $conn->prepare("SELECT * FROM review WHERE user_id = ? AND recipe_id = ?");
        $stmt->bind_param("si", $user_id, $recipe_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error = "이미 해당 레시피에 리뷰를 남겼습니다.";
        } else {
            // 리뷰 추가
            $stmt = $conn->prepare("INSERT INTO review (user_id, recipe_id, review_text, rating) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sisi", $user_id, $recipe_id, $review_text, $rating);

            if ($stmt->execute()) {
                header("Location: recipe.php?id=" . $recipe_id);
                exit();
            } else {
                $error = "리뷰 추가 중 오류가 발생했습니다. 다시 시도해 주세요.";
            }
        }
    }
}

if (isset($error)) {
    echo "<p class='error'>" . htmlspecialchars($error) . "</p>";
    echo "<p><a href='recipe.php?id=" . htmlspecialchars($recipe_id) . "'>돌아가기</a></p>";
}

$conn->close(); // 데이터베이스 연결 종료
?>
