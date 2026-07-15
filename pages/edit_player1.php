<?php
session_start();
include "../includes/db_config.php";
include "../includes/auth.php";

if (isset($_GET['id'])) {
    $player_id = $_GET['id'];
    $user_id = $_SESSION['user_id'];

    if ($conn instanceof PDO) {
        $query = "SELECT * FROM Players WHERE player_id = ? AND user_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$player_id, $user_id]);
        $player = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        // MySQLi Fallback
        $query = "SELECT * FROM Players WHERE player_id = $player_id AND user_id = $user_id";
        $result = mysqli_query($conn, $query);
        $player = mysqli_fetch_assoc($result);
    }

    if (!$player) {
        $_SESSION['toast_message'] = ['type' => 'error', 'message' => 'ไม่พบข้อมูลนักเตะนี้!'];
        header("Location: position.php");
        exit;
    }

    // Move to Legend Logic
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['move'])) {
        try {
            if ($conn instanceof PDO) {
                $query = "INSERT INTO legendary_players (
                            user_id, player_id_original, player_name, player_role, player_position,
                            player_jersey_number, player_status, player_injured, player_injured_count,
                            player_performance_score, player_is_academy_product,
                            player_appearances, player_goals, player_assists, player_clean_sheets,
                            player_yellow_cards, player_red_cards, contributions, player_rating
                          ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($query);
                $default_contributions = 'ถูกขึ้นแท่นตำนานจากหน้าแก้ไขนักเตะ';
                $stmt->execute([
                    $user_id, $player['player_id'], $player['name'], $player['role'], $player['position'],
                    $player['jersey_number'], $player['status'], $player['injured'], $player['injured_count'],
                    $player['performance_score'], $player['is_academy_product'], $player['appearances'],
                    $player['goals'], $player['assists'], $player['clean_sheets'], $player['yellow_cards'],
                    $player['red_cards'], $default_contributions, $player['rating']
                ]);

                $delQuery = "DELETE FROM Players WHERE player_id = ? AND user_id = ?";
                $delStmt = $conn->prepare($delQuery);
                $delStmt->execute([$player_id, $user_id]);
            }
            $_SESSION['toast_message'] = ['type' => 'success', 'message' => 'นักเตะถูกขึ้นแท่นตำนานสำเร็จ!'];
            header("Location: position.php");
            exit;
        } catch (PDOException $e) {
            $_SESSION['toast_message'] = ['type' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . htmlspecialchars($e->getMessage())];
            header("Location: position.php");
            exit;
        }
    }

    // Delete Logic
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
        try {
            if ($conn instanceof PDO) {
                $query = "DELETE FROM Players WHERE player_id = ? AND user_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->execute([$player_id, $user_id]);
            }
            $_SESSION['toast_message'] = ['type' => 'success', 'message' => 'ลบผู้เล่นสำเร็จ!'];
            header("Location: position.php");
            exit;
        } catch (PDOException $e) {
            $_SESSION['toast_message'] = ['type' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . htmlspecialchars($e->getMessage())];
            header("Location: position.php");
            exit;
        }
    }

    // Update Logic
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['move']) && !isset($_POST['delete'])) {
        $name = $_POST['name'];
        $role = $_POST['role'];
        $position = $_POST['position'];
        $jersey_number = !empty($_POST['jersey_number']) ? $_POST['jersey_number'] : NULL;
        $status = $_POST['status'];
        $rating = !empty($_POST['rating']) ? floatval($_POST['rating']) : 5.00;

        $injured_checkbox = isset($_POST['injured_checkbox']) ? 1 : 0;
        $add_injured_days = isset($_POST['add_injured_days']) ? (int)$_POST['add_injured_days'] : 0;
        $new_injured_count = $player['injured_count'] + $add_injured_days;

        $appearances = isset($_POST['appearances']) ? (int)$_POST['appearances'] : 0;
        $goals = isset($_POST['goals']) ? (int)$_POST['goals'] : 0;
        $assists = isset($_POST['assists']) ? (int)$_POST['assists'] : 0;
        $clean_sheets = isset($_POST['clean_sheets']) ? (int)$_POST['clean_sheets'] : 0;
        $yellow_cards = isset($_POST['yellow_cards']) ? (int)$_POST['yellow_cards'] : 0;
        $red_cards = isset($_POST['red_cards']) ? (int)$_POST['red_cards'] : 0;

        try {
            if ($conn instanceof PDO) {
                $query = "UPDATE Players SET name = ?, role = ?, position = ?, jersey_number = ?, status = ?, injured = ?, injured_count = ?, appearances = ?, goals = ?, assists = ?, clean_sheets = ?, yellow_cards = ?, red_cards = ?, rating = ? WHERE player_id = ? AND user_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->execute([
                    $name, $role, $position, $jersey_number, $status, $injured_checkbox, $new_injured_count,
                    $appearances, $goals, $assists, $clean_sheets, $yellow_cards, $red_cards, $rating,
                    $player_id, $user_id
                ]);
            }
            $_SESSION['toast_message'] = ['type' => 'success', 'message' => 'อัปเดตนักเตะสำเร็จ!'];
            header("Location: position.php");
            exit;
        } catch (PDOException $e) {
            $_SESSION['toast_message'] = ['type' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . htmlspecialchars($e->getMessage())];
            header("Location: position.php");
            exit;
        }
    }
} else {
    $_SESSION['toast_message'] = ['type' => 'error', 'message' => 'ไม่พบข้อมูลนักเตะ!'];
    header("Location: position.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขนักเตะ | FMFC Manager</title>
    <link rel="icon" type="image/png" href="../img/logo/fm25_logo_2.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Itim&family=Kanit:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <style>
        body { font-family: 'Kanit', sans-serif; }
    </style>
</head>

<body class="bg-[#F9FAFB] text-gray-800">
    <div class="flex h-screen overflow-hidden">
        <?php include '../includes/navbar.php'; ?>

        <main class="flex-1 overflow-y-auto px-8 py-10">
            <div class="max-w-4xl mx-auto">
                
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 tracking-tight flex items-center gap-2">
                            <i data-lucide="user-cog" class="w-6 h-6 text-black"></i>
                            แก้ไขข้อมูลนักเตะ
                        </h1>
                        <p class="text-sm text-gray-500 mt-1">จากหน้าแผนการเล่น (Position View)</p>
                    </div>
                    <form action="process_single_player_stats.php" method="POST" onsubmit="return confirmProcessSingleStats();">
                        <input type="hidden" name="player_id" value="<?php echo htmlspecialchars($player['player_id']); ?>">
                        <button type="submit" class="bg-white border border-gray-200 text-gray-700 px-4 py-2 rounded-xl hover:bg-gray-50 hover:text-black transition shadow-sm font-medium flex items-center gap-2">
                            <i data-lucide="archive" class="w-4 h-4"></i> รวมสถิติ & รีเซ็ต
                        </button>
                    </form>
                </div>

                <form action="edit_player1.php?id=<?php echo $player['player_id']; ?>" method="POST" class="space-y-6">
                    
                    <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100">
                        <h2 class="text-sm font-bold text-gray-900 uppercase tracking-widest flex items-center gap-2 border-b border-gray-100 pb-4 mb-6">
                            <i data-lucide="info" class="w-4 h-4 text-gray-400"></i> ข้อมูลทั่วไป
                        </h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="col-span-2">
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">ชื่อนักเตะ</label>
                                <input type="text" name="name" value="<?php echo htmlspecialchars($player['name']); ?>" required
                                    class="w-full px-4 py-3 bg-gray-50 border-gray-200 border rounded-xl focus:bg-white focus:ring-2 focus:ring-black focus:border-transparent transition-all font-medium text-gray-900">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">ตำแหน่ง</label>
                                <select name="position" class="w-full px-4 py-3 bg-gray-50 border-gray-200 border rounded-xl focus:bg-white focus:ring-2 focus:ring-black transition-all">
                                    <?php foreach (['st', 'cf', 'lw', 'rw', 'lm', 'rm', 'cam', 'cm', 'cdm', 'rb', 'lb', 'cb', 'gk'] as $pos) {
                                        $selected = $player['position'] === $pos ? 'selected' : '';
                                        echo "<option value='$pos' $selected>" . strtoupper($pos) . "</option>";
                                    } ?>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Role</label>
                                <select name="role" class="w-full px-4 py-3 bg-gray-50 border-gray-200 border rounded-xl focus:bg-white focus:ring-2 focus:ring-black transition-all">
                                    <option value="crucial" <?= $player['role'] === 'crucial' ? 'selected' : ''; ?>>Crucial</option>
                                    <option value="important" <?= $player['role'] === 'important' ? 'selected' : ''; ?>>Important</option>
                                    <option value="rotation" <?= $player['role'] === 'rotation' ? 'selected' : ''; ?>>Rotation</option>
                                    <option value="sporadic" <?= $player['role'] === 'sporadic' ? 'selected' : ''; ?>>Sporadic</option>
                                    <option value="prospect" <?= $player['role'] === 'prospect' ? 'selected' : ''; ?>>Prospect</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">เบอร์เสื้อ</label>
                                <input type="number" name="jersey_number" value="<?= htmlspecialchars($player['jersey_number']); ?>"
                                    class="w-full px-4 py-3 bg-gray-50 border-gray-200 border rounded-xl focus:bg-white focus:ring-2 focus:ring-black transition-all">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Rating</label>
                                <input type="number" step="0.01" name="rating" value="<?= htmlspecialchars($player['rating']); ?>"
                                    class="w-full px-4 py-3 bg-gray-50 border-gray-200 border rounded-xl focus:bg-white focus:ring-2 focus:ring-black transition-all">
                            </div>

                            <div class="col-span-2">
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">สถานะ</label>
                                <select name="status" class="w-full px-4 py-3 bg-gray-50 border-gray-200 border rounded-xl focus:bg-white focus:ring-2 focus:ring-black transition-all">
                                    <option value="no" <?= $player['status'] === 'no' ? 'selected' : ''; ?>>อยู่กับทีม</option>
                                    <option value="sell" <?= $player['status'] === 'sell' ? 'selected' : ''; ?>>ขาย</option>
                                    <option value="for_loan" <?= $player['status'] === 'for_loan' ? 'selected' : ''; ?>>พร้อมปล่อยยืม</option>
                                    <option value="on_loan" <?= $player['status'] === 'on_loan' ? 'selected' : ''; ?>>ถูกยืมตัว</option>
                                    <option value="in_loan" <?= $player['status'] === 'in_loan' ? 'selected' : ''; ?>>กำลังยืมตัว</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100">
                            <h2 class="text-sm font-bold text-gray-900 uppercase tracking-widest flex items-center gap-2 border-b border-gray-100 pb-4 mb-6">
                                <i data-lucide="bar-chart-2" class="w-4 h-4 text-gray-400"></i> สถิติสะสม
                            </h2>
                            <div class="grid grid-cols-2 gap-4">
                                <?php 
                                $stats = [
                                    'appearances' => 'ลงเล่น', 'goals' => 'ประตู', 
                                    'assists' => 'แอสซิสต์', 'clean_sheets' => 'คลีนชีท',
                                    'yellow_cards' => 'ใบเหลือง', 'red_cards' => 'ใบแดง'
                                ];
                                foreach ($stats as $key => $label): ?>
                                    <div>
                                        <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1"><?= $label ?></label>
                                        <input type="number" name="<?= $key ?>" value="<?= htmlspecialchars($player[$key] ?? 0); ?>" min="0"
                                            class="w-full px-3 py-2 bg-gray-50 border-gray-200 border rounded-lg focus:ring-1 focus:ring-black text-center font-semibold">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="flex flex-col gap-6">
                            <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100 flex-1">
                                <h2 class="text-sm font-bold text-gray-900 uppercase tracking-widest flex items-center gap-2 border-b border-gray-100 pb-4 mb-6">
                                    <i data-lucide="activity" class="w-4 h-4 text-gray-400"></i> การบาดเจ็บ
                                </h2>
                                <label class="flex items-center gap-3 p-3 border border-gray-200 rounded-xl cursor-pointer hover:bg-gray-50 transition-colors mb-4">
                                    <input type="checkbox" name="injured_checkbox" class="w-5 h-5 text-black border-gray-300 rounded focus:ring-black" <?= $player['injured'] > 0 ? 'checked' : ''; ?>>
                                    <span class="text-sm font-medium text-gray-700">กำลังบาดเจ็บ</span>
                                </label>
                                <div class="flex items-center justify-between bg-gray-50 p-3 rounded-xl mb-4">
                                    <span class="text-xs text-gray-500 font-bold">สะสมรวม</span>
                                    <span class="text-lg font-bold text-gray-900"><?= htmlspecialchars($player['injured_count']); ?> วัน</span>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">เพิ่มวันเจ็บ (+)</label>
                                    <input type="number" name="add_injured_days" value="0" min="0" class="w-full px-4 py-2 bg-white border border-gray-200 rounded-xl focus:ring-black">
                                </div>
                            </div>

                            <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-purple-50 rounded-lg">
                                        <i data-lucide="graduation-cap" class="w-5 h-5 text-purple-600"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-gray-900">Academy Product</p>
                                        <p class="text-xs text-gray-500"><?= $player['is_academy_product'] == 1 ? 'ใช่ (Yes)' : 'ไม่ใช่ (No)' ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="pt-6 border-t border-gray-200 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <a href="position.php" class="text-gray-500 hover:text-black font-medium text-sm px-4">ยกเลิก</a>
                        
                        <div class="flex flex-wrap justify-center gap-3">
                            <button type="submit" name="delete" class="px-5 py-3 rounded-xl bg-red-50 text-red-600 font-bold text-sm hover:bg-red-100 transition" onclick="return confirm('ยืนยันการลบ?');">
                                <i data-lucide="trash-2" class="w-4 h-4 inline mr-1"></i> ลบนักเตะ
                            </button>
                            <button type="submit" name="move" class="px-5 py-3 rounded-xl bg-yellow-50 text-yellow-700 font-bold text-sm hover:bg-yellow-100 transition" onclick="return confirm('ยืนยันขึ้นแท่นตำนาน?');">
                                <i data-lucide="crown" class="w-4 h-4 inline mr-1"></i> ขึ้นแท่นตำนาน
                            </button>
                            <button type="submit" class="px-8 py-3 rounded-xl bg-black text-white font-bold text-sm hover:bg-gray-800 transition shadow-lg shadow-gray-200 flex items-center gap-2">
                                <i data-lucide="save" class="w-4 h-4"></i> อัปเดตข้อมูล
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script>
        lucide.createIcons();

        function confirmProcessSingleStats() {
            return confirm('ยืนยันการรวมสถิติ? ข้อมูลปัจจุบันจะถูกรีเซ็ต');
        }

        <?php if (isset($_SESSION['toast_message'])): ?>
            const msg = <?php echo json_encode($_SESSION['toast_message']); ?>;
            let bg = msg.type === 'success' ? "#18181b" : "#ef4444";
            Toastify({
                text: msg.message, duration: 3000, close: true, gravity: "top", position: "right",
                style: { background: bg, borderRadius: "12px", fontSize: "14px", padding: "12px 20px" }
            }).showToast();
            <?php unset($_SESSION['toast_message']); ?>
        <?php endif; ?>
    </script>
</body>
</html>