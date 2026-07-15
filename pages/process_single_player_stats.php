<?php
session_start();
include "../includes/db_config.php";
include "../includes/auth.php";

header('Content-Type: application/json'); // Respond with JSON for AJAX calls, though this will be direct redirect

if (!isset($_SESSION['user_id'])) {
    $_SESSION['toast_message'] = [
        'type' => 'error',
        'message' => 'คุณต้องเข้าสู่ระบบก่อน'
    ];
    header("Location: login.php");
    exit();
}

if (!isset($_POST['player_id'])) {
    $_SESSION['toast_message'] = [
        'type' => 'error',
        'message' => 'ไม่พบข้อมูลนักเตะที่ต้องการรวมสถิติ'
    ];
    header("Location: manage_players.php"); // Redirect if player_id is missing
    exit();
}

$user_id = $_SESSION['user_id'];
$player_id_to_process = intval($_POST['player_id']); // Ensure it's an integer

try {
    $conn->beginTransaction(); // Start a transaction for atomicity

    // 1. Fetch the specific player from the 'players' table for the current user
    $stmt_player = $conn->prepare("SELECT * FROM Players WHERE user_id = ? AND player_id = ?");
    $stmt_player->execute([$user_id, $player_id_to_process]);
    $player = $stmt_player->fetch(PDO::FETCH_ASSOC);

    if (!$player) {
        throw new Exception("ไม่พบนักเตะที่ระบุ หรือคุณไม่มีสิทธิ์เข้าถึง");
    }

    $player_id_original = $player['player_id'];

    // Convert score_change enum to numeric value for summing
    $score_change_numeric = 0;
    if ($player['score_change'] === 'increase') {
        $score_change_numeric = $player['change_count'];
    } elseif ($player['score_change'] === 'decrease') {
        $score_change_numeric = -$player['change_count'];
    }

    // 2. Check if the player exists in 'player_total_stats'
    $stmt_check = $conn->prepare("SELECT * FROM player_total_stats WHERE user_id = ? AND player_id_original = ?");
    $stmt_check->execute([$user_id, $player_id_original]);
    $total_player_data = $stmt_check->fetch(PDO::FETCH_ASSOC);

    if (!$total_player_data) {
        // Player does NOT exist in player_total_stats, so INSERT
        $stmt_insert = $conn->prepare("
            INSERT INTO player_total_stats (
                user_id, player_id_original, name, role, position, jersey_number,
                status, injured, injured_count, is_academy_product, performance_score,
                rating, score_change_sum, change_count_sum, appearances, goals, assists,
                clean_sheets, yellow_cards, red_cards
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            )
        ");
        $stmt_insert->execute([
            $user_id,
            $player_id_original,
            $player['name'],
            $player['role'],
            $player['position'],
            $player['jersey_number'],
            $player['status'],
            $player['injured'],
            $player['injured_count'],
            $player['is_academy_product'],
            $player['performance_score'], // Latest performance score
            $player['rating'], // Latest rating
            $score_change_numeric, // Sum of current change_count (or its value)
            $player['change_count'], // Sum of current change_count
            $player['appearances'],
            $player['goals'],
            $player['assists'],
            $player['clean_sheets'],
            $player['yellow_cards'],
            $player['red_cards']
        ]);
    } else {
        // Player EXISTS in player_total_stats, so UPDATE (summing stats)
        $stmt_update = $conn->prepare("
            UPDATE player_total_stats SET
                name = ?,
                role = ?,
                position = ?,
                jersey_number = ?,
                status = ?,
                injured = ?,
                injured_count = ?, -- อัปเดตค่าล่าสุดโดยไม่บวกเพิ่ม
                is_academy_product = ?,
                performance_score = ?, -- Update with latest performance score
                rating = ?, -- Update with latest rating
                score_change_sum = score_change_sum + ?, -- Sum score_change_numeric
                change_count_sum = change_count_sum + ?, -- Sum change_count
                appearances = appearances + ?,
                goals = goals + ?,
                assists = assists + ?,
                clean_sheets = clean_sheets + ?,
                yellow_cards = yellow_cards + ?,
                red_cards = red_cards + ?
            WHERE user_id = ? AND player_id_original = ?
        ");
        $stmt_update->execute([
            $player['name'],
            $player['role'],
            $player['position'],
            $player['jersey_number'],
            $player['status'],
            $player['injured'],
            $player['injured_count'], // ส่งค่าล่าสุดเข้ามา
            $player['is_academy_product'],
            $player['performance_score'],
            $player['rating'],
            $score_change_numeric,
            $player['change_count'],
            $player['appearances'],
            $player['goals'],
            $player['assists'],
            $player['clean_sheets'],
            $player['yellow_cards'],
            $player['red_cards'],
            $user_id,
            $player_id_original
        ]);
    }

    // 3. Reset stats in the 'players' table for the specific player
    $stmt_reset = $conn->prepare("
        UPDATE Players SET
            appearances = 0,
            goals = 0,
            assists = 0,
            clean_sheets = 0,
            yellow_cards = 0,
            red_cards = 0,
            performance_score = 0,
            score_change = 'neutral',
            change_count = 0
        WHERE user_id = ? AND player_id = ?
    ");
    $stmt_reset->execute([$user_id, $player_id_to_process]);

    $conn->commit(); // Commit the transaction

    $_SESSION['toast_message'] = [
        'type' => 'success',
        'message' => 'รวมสถิตินักเตะ ' . htmlspecialchars($player['name']) . ' และรีเซ็ตข้อมูลปัจจุบันสำเร็จ!'
    ];
    header("Location: edit_player.php?id=" . $player_id_to_process); // Redirect back to edit page
    exit();

} catch (Exception $e) {
    $conn->rollBack(); // Rollback on error
    $_SESSION['toast_message'] = [
        'type' => 'error',
        'message' => 'เกิดข้อผิดพลาดในการรวมสถิติ: ' . htmlspecialchars($e->getMessage())
    ];
    header("Location: edit_player.php?id=" . $player_id_to_process); // Redirect back on error
    exit();
}
?>