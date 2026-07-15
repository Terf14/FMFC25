<?php
session_start();
include "../includes/db_config.php";
include "../includes/auth.php";

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['player_id'])) {
    $user_id = $_SESSION['user_id'];
    $player_id = intval($_POST['player_id']);

    try {
        $stmt_name = $conn->prepare("SELECT name FROM players WHERE player_id = ? AND user_id = ?");
        $stmt_name->execute([$player_id, $user_id]);
        $player_name = $stmt_name->fetchColumn();

        if (!$player_name) {
            echo json_encode(['success' => false, 'message' => 'ไม่พบนักเตะในบัญชีของคุณ']);
            exit();
        }

        // ใช้ ON DUPLICATE KEY UPDATE เพื่อให้ user 1 คนมีกัปตันได้แค่คนเดียวเสมอ
        $stmt = $conn->prepare("
            INSERT INTO team_captain (user_id, player_id) 
            VALUES (:user_id, :player_id) 
            ON DUPLICATE KEY UPDATE player_id = :player_id
        ");
        $stmt->execute(['user_id' => $user_id, 'player_id' => $player_id]);

        echo json_encode(['success' => true, 'message' => "แต่งตั้ง $player_name เป็นกัปตันทีมแล้ว!"]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>
