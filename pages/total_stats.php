<?php
session_start();
include "../includes/db_config.php";
include "../includes/auth.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// --- Logic ส่วนเดิม (Fetch Timestamp & Calculation) ---
$last_aggregation_timestamp = null;
try {
    $stmt_user = $conn->prepare("SELECT last_total_stats_aggregation FROM users WHERE id = ?");
    $stmt_user->execute([$user_id]);
    $user_data = $stmt_user->fetch(PDO::FETCH_ASSOC);
    if ($user_data && $user_data['last_total_stats_aggregation']) {
        $last_aggregation_timestamp = $user_data['last_total_stats_aggregation'];
    }
} catch (PDOException $e) {
    error_log("Error fetching last aggregation timestamp: " . $e->getMessage());
}

$days_since_last_update = null;
$last_update_display = 'ยังไม่เคยรวมสถิติ';
$last_update_message = '';

if ($last_aggregation_timestamp) {
    $last_update_datetime = new DateTime($last_aggregation_timestamp);
    $current_datetime = new DateTime();
    $interval = $current_datetime->diff($last_update_datetime);
    $days_since_last_update = $interval->days;

    $last_update_display = $last_update_datetime->format('d/m/Y H:i'); 

    if ($days_since_last_update === 0) {
        $last_update_message = 'อัปเดตล่าสุดวันนี้';
    } else {
        $last_update_message = "ไม่ได้อัปเดตมาแล้ว {$days_since_last_update} วัน";
    }
} else {
    $last_update_message = 'แนะนำให้กดรวมสถิติเมื่อจบฤดูกาล';
}

// --- Sorting Logic ---
$sort_column = $_GET['sort_column'] ?? 'name'; 
$sort_order = $_GET['sort_order'] ?? 'ASC'; 

$allowed_sort_columns = [
    'name', 'position', 'rating', 'role', 'jersey_number', 'status',
    'injured_count', 'performance_score', 'appearances', 'goals',
    'assists', 'clean_sheets', 'yellow_cards', 'red_cards', 'is_academy_product'
];

if (!in_array($sort_column, $allowed_sort_columns)) $sort_column = 'name';
if (!in_array(strtoupper($sort_order), ['ASC', 'DESC'])) $sort_order = 'ASC';

function getSortLink($column, $display_name, $current_sort_column, $current_sort_order) {
    $new_order = ($current_sort_column === $column && $current_sort_order === 'ASC') ? 'DESC' : 'ASC';
    $activeClass = ($current_sort_column === $column) ? 'text-black font-bold' : 'text-gray-400 hover:text-gray-600';
    $icon = '';
    if ($current_sort_column === $column) {
        $icon = $new_order === 'ASC' 
            ? '<i data-lucide="chevron-down" class="inline-block w-3 h-3 ml-1"></i>' 
            : '<i data-lucide="chevron-up" class="inline-block w-3 h-3 ml-1"></i>';
    }
    return '<a href="?sort_column=' . $column . '&sort_order=' . $new_order . '" class="flex items-center transition-colors ' . $activeClass . '">' . $display_name . $icon . '</a>';
}

// --- Fetch Players ---
$players_in_team = [];
try {
    $query = "SELECT pts.* FROM player_total_stats AS pts INNER JOIN Players AS p ON pts.player_id_original = p.player_id WHERE pts.user_id = ? ORDER BY " . $sort_column . " " . $sort_order;
    $stmt = $conn->prepare($query);
    $stmt->execute([$user_id]);
    $players_in_team = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { error_log($e->getMessage()); }

$former_players = [];
try {
    $query = "SELECT pts.* FROM player_total_stats AS pts LEFT JOIN Players AS p ON pts.player_id_original = p.player_id WHERE p.player_id IS NULL AND pts.user_id = ? ORDER BY " . $sort_column . " " . $sort_order;
    $stmt = $conn->prepare($query);
    $stmt->execute([$user_id]);
    $former_players = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { error_log($e->getMessage()); }
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>สถิติรวมนักเตะ | FMFC Manager</title>
    <link rel="icon" type="image/png" href="../img/logo/fm25_logo_2.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Itim&family=Kanit:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <style>
        body { font-family: 'Kanit', sans-serif; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>

<body class="bg-[#F9FAFB] text-gray-800">
    <div class="flex h-screen overflow-hidden">
        <?php include '../includes/navbar.php'; ?>

        <main class="flex-1 overflow-y-auto px-8 py-10">
            
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 tracking-tight flex items-center gap-3">
                        <i data-lucide="line-chart" class="w-8 h-8 text-black"></i>
                        สถิติรวมนักเตะ
                    </h1>
                    <p class="text-gray-500 mt-1 text-sm">ประวัติผลงานทั้งหมดตั้งแต่อดีตจนถึงปัจจุบัน</p>
                </div>
                
                <form action="process_total_stats.php" method="POST" onsubmit="return confirmProcessStats();">
                    <button type="submit"
                        class="bg-black text-white px-6 py-3 rounded-xl hover:bg-gray-800 transition shadow-lg shadow-gray-200 flex items-center gap-2 font-medium">
                        <i data-lucide="archive" class="w-5 h-5"></i> รวมสถิติประจำช่วง
                    </button>
                </form>
            </div>

            <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-sm mb-10 flex items-center gap-4 max-w-fit">
                <div class="w-10 h-10 rounded-full bg-gray-50 flex items-center justify-center text-gray-500">
                    <i data-lucide="clock" class="w-5 h-5"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-400 uppercase font-bold tracking-wider">อัปเดตล่าสุด</p>
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-semibold text-gray-900"><?= htmlspecialchars($last_update_display); ?></span>
                        <span class="text-xs text-gray-500">(<?= htmlspecialchars($last_update_message); ?>)</span>
                    </div>
                </div>
            </div>

            <div class="mb-12">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-8 h-8 rounded-lg bg-green-50 flex items-center justify-center text-green-600">
                        <i data-lucide="users" class="w-5 h-5"></i>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900">นักเตะในทีมปัจจุบัน</h2>
                </div>

                <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto no-scrollbar">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-100">
                                    <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider sticky left-0 bg-gray-50 z-10"><?= getSortLink('name', 'ชื่อ', $sort_column, $sort_order); ?></th>
                                    <th class="px-4 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider"><?= getSortLink('position', 'ตำแหน่ง', $sort_column, $sort_order); ?></th>
                                    <th class="px-4 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider text-center"><?= getSortLink('rating', 'Rating', $sort_column, $sort_order); ?></th>
                                    <th class="px-4 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider"><?= getSortLink('role', 'บทบาท', $sort_column, $sort_order); ?></th>
                                    <th class="px-4 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider"><?= getSortLink('status', 'สถานะ', $sort_column, $sort_order); ?></th>
                                    <th class="px-4 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider text-center"><?= getSortLink('appearances', 'นัด', $sort_column, $sort_order); ?></th>
                                    <th class="px-4 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider text-center"><?= getSortLink('goals', 'ยิง', $sort_column, $sort_order); ?></th>
                                    <th class="px-4 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider text-center"><?= getSortLink('assists', 'จ่าย', $sort_column, $sort_order); ?></th>
                                    <th class="px-4 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider text-center"><?= getSortLink('clean_sheets', 'คลีน', $sort_column, $sort_order); ?></th>
                                    <th class="px-4 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider text-center text-yellow-500"><i data-lucide="rectangle-vertical" class="w-4 h-4 mx-auto fill-current"></i></th>
                                    <th class="px-4 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider text-center text-red-500"><i data-lucide="rectangle-vertical" class="w-4 h-4 mx-auto fill-current"></i></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php if (!empty($players_in_team)): ?>
                                    <?php foreach ($players_in_team as $player): ?>
                                        <tr class="hover:bg-gray-50 transition-colors group">
                                            <td class="px-6 py-3 sticky left-0 bg-white group-hover:bg-gray-50 transition-colors">
                                                <div class="flex items-center gap-3">
                                                    <span class="font-bold text-gray-900 text-sm"><?= htmlspecialchars($player['name']); ?></span>
                                                    <?php if ($player['is_academy_product'] == 1): ?>
                                                        <i data-lucide="graduation-cap" class="w-3 h-3 text-purple-500"></i>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3">
                                                <span class="text-[10px] font-bold bg-gray-100 text-gray-600 px-2 py-1 rounded uppercase"><?= htmlspecialchars($player['position']); ?></span>
                                            </td>
                                            <td class="px-4 py-3 text-center font-medium text-gray-700"><?= htmlspecialchars(number_format($player['rating'] ?? 0.00, 2)); ?></td>
                                            <td class="px-4 py-3 text-xs capitalize text-gray-500"><?= htmlspecialchars($player['role']); ?></td>
                                            <td class="px-4 py-3">
                                                <?php
                                                $statusClass = match($player['status']) {
                                                    'sell' => 'bg-red-50 text-red-600',
                                                    'for_loan' => 'bg-orange-50 text-orange-600',
                                                    'on_loan' => 'bg-blue-50 text-blue-600',
                                                    'in_loan' => 'bg-purple-50 text-purple-600',
                                                    default => 'text-gray-500'
                                                };
                                                $statusText = match($player['status']) {
                                                    'no' => 'อยู่กับทีม', 'sell' => 'ขาย', 'for_loan' => 'ปล่อยยืม',
                                                    'on_loan' => 'ถูกยืม', 'in_loan' => 'ยืมมา', default => '-'
                                                };
                                                ?>
                                                <span class="text-[10px] font-bold px-2 py-1 rounded <?= $statusClass ?>"><?= htmlspecialchars($statusText); ?></span>
                                            </td>
                                            <td class="px-4 py-3 text-center font-bold text-gray-800"><?= htmlspecialchars($player['appearances'] ?? 0); ?></td>
                                            <td class="px-4 py-3 text-center text-sm text-gray-600"><?= htmlspecialchars($player['goals'] ?? 0); ?></td>
                                            <td class="px-4 py-3 text-center text-sm text-gray-600"><?= htmlspecialchars($player['assists'] ?? 0); ?></td>
                                            <td class="px-4 py-3 text-center text-sm text-gray-600"><?= htmlspecialchars($player['clean_sheets'] ?? 0); ?></td>
                                            <td class="px-4 py-3 text-center text-sm font-medium text-yellow-600 bg-yellow-50/50"><?= htmlspecialchars($player['yellow_cards'] ?? 0); ?></td>
                                            <td class="px-4 py-3 text-center text-sm font-medium text-red-600 bg-red-50/50"><?= htmlspecialchars($player['red_cards'] ?? 0); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="11" class="px-6 py-8 text-center text-gray-400">ยังไม่มีสถิติรวม</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div>
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center text-gray-500">
                        <i data-lucide="history" class="w-5 h-5"></i>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900">อดีตนักเตะ (ย้ายออกแล้ว)</h2>
                </div>

                <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden opacity-80 hover:opacity-100 transition-opacity">
                    <div class="overflow-x-auto no-scrollbar">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-100">
                                    <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider sticky left-0 bg-gray-50 z-10">ชื่อ</th>
                                    <th class="px-4 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider">ตำแหน่ง</th>
                                    <th class="px-4 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider text-center">Rating</th>
                                    <th class="px-4 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider text-center">นัด</th>
                                    <th class="px-4 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider text-center">ยิง</th>
                                    <th class="px-4 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider text-center">จ่าย</th>
                                    <th class="px-4 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider text-center">คลีน</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php if (!empty($former_players)): ?>
                                    <?php foreach ($former_players as $player): ?>
                                        <tr class="hover:bg-gray-50 transition-colors">
                                            <td class="px-6 py-3 font-medium text-gray-600 sticky left-0 bg-white"><?= htmlspecialchars($player['name']); ?></td>
                                            <td class="px-4 py-3"><span class="text-[10px] font-bold bg-gray-100 text-gray-500 px-2 py-1 rounded uppercase"><?= htmlspecialchars($player['position']); ?></span></td>
                                            <td class="px-4 py-3 text-center text-gray-500"><?= htmlspecialchars(number_format($player['rating'] ?? 0.00, 2)); ?></td>
                                            <td class="px-4 py-3 text-center text-gray-600"><?= htmlspecialchars($player['appearances'] ?? 0); ?></td>
                                            <td class="px-4 py-3 text-center text-gray-600"><?= htmlspecialchars($player['goals'] ?? 0); ?></td>
                                            <td class="px-4 py-3 text-center text-gray-600"><?= htmlspecialchars($player['assists'] ?? 0); ?></td>
                                            <td class="px-4 py-3 text-center text-gray-600"><?= htmlspecialchars($player['clean_sheets'] ?? 0); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="7" class="px-6 py-8 text-center text-gray-400">ไม่มีข้อมูลอดีตนักเตะ</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script>
        lucide.createIcons();

        function confirmProcessStats() {
            return confirm('ยืนยันการรวมสถิติ? ข้อมูลปัจจุบันจะถูกรีเซ็ตและบันทึกลงในสถิติรวม');
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