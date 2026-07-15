<?php
session_start();
include "../includes/db_config.php";
include "../includes/auth.php";

// Check for toast message in session
if (isset($_SESSION['toast_message'])) {
    $toast_type = $_SESSION['toast_message']['type'];
    $toast_message = $_SESSION['toast_message']['message'];
    unset($_SESSION['toast_message']);
?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof showToast !== 'undefined') {
            showToast('<?= $toast_type ?>', '<?= htmlspecialchars($toast_message) ?>', null);
        }
    });
</script>
<?php
}

$user_id = $_SESSION['user_id'];

// --- Data Fetching Logic (Keeping original logic intact) ---

// Status Counts
$status_counts = ['sell' => 0, 'for_loan' => 0, 'on_loan' => 0, 'in_loan' => 0, 'no' => 0];
if ($conn instanceof PDO) {
    $statusQuery = $conn->prepare("SELECT status, COUNT(*) as count FROM Players WHERE user_id = ? GROUP BY status");
    $statusQuery->execute([$user_id]);
    $statusResults = $statusQuery->fetchAll(PDO::FETCH_ASSOC);
    foreach ($statusResults as $row) $status_counts[$row['status']] = $row['count'];
}

// Total Players
$totalPlayers = 0;
if ($conn instanceof PDO) {
    $queryTotal = $conn->prepare("SELECT COUNT(*) as total FROM Players WHERE user_id = ?");
    $queryTotal->execute([$user_id]);
    $totalPlayers = $queryTotal->fetch(PDO::FETCH_ASSOC)['total'];
}

// Fetch Players
$players = [];
$position_counts = [];
if ($conn instanceof PDO) {
    $query = $conn->prepare("
        SELECT player_id, name, position, role, jersey_number, status, injured, performance_score, is_academy_product 
        FROM Players
        WHERE user_id = ? AND status != 'on_loan'
        ORDER BY FIELD(COALESCE(role, 'prospect'), 'crucial', 'important', 'rotation', 'sporadic', 'prospect'), name ASC
    ");
    $query->execute([$user_id]);
    $players = $query->fetchAll(PDO::FETCH_ASSOC);
}

// Count positions
foreach ($players as $player) {
    $position_counts[$player['position']] = ($position_counts[$player['position']] ?? 0) + 1;
}

// Initials Helper
if (!function_exists('getInitials')) {
    function getInitials($name) {
        $words = explode(' ', $name);
        $initials = '';
        foreach ($words as $word) $initials .= strtoupper(substr($word, 0, 1));
        if (strlen($initials) > 2 && count($words) > 1) $initials = substr($initials, 0, 1) . substr($initials, -1);
        elseif (empty($initials) && !empty($name)) $initials = strtoupper(substr($name, 0, min(2, strlen($name))));
        return $initials;
    }
}

// Grid Layout
$positions_grid = [
    ['lw', 'st', 'rw'],
    ['', 'cf', ''],
    ['lm', 'cam', 'rm'],
    ['', 'cm', ''],
    ['', 'cdm', ''],
    ['lb', 'cb', 'rb'],
    ['', 'gk', '']
];

// --- Recommendation Logic ---
$recommendations = [];
function getPlayerCountRecommendation($count, $min, $max) {
    if ($count < $min) {
        $diff = $min - $count;
        return "<span class='flex items-center text-red-600 text-xs font-bold bg-red-50 px-2 py-1 rounded-md'><i data-lucide='alert-circle' class='w-3 h-3 mr-1'></i> ขาด $diff</span>";
    } elseif ($count > $max) {
        $diff = $count - $max;
        return "<span class='flex items-center text-orange-600 text-xs font-bold bg-orange-50 px-2 py-1 rounded-md'><i data-lucide='alert-triangle' class='w-3 h-3 mr-1'></i> เกิน $diff</span>";
    } else {
        return "<span class='flex items-center text-green-600 text-xs font-bold bg-green-50 px-2 py-1 rounded-md'><i data-lucide='check-circle-2' class='w-3 h-3 mr-1'></i> พอดี</span>";
    }
}

$st_count = ($position_counts['st'] ?? 0) + ($position_counts['cf'] ?? 0);
$recommendations['st_cf'] = ['name' => 'ST/CF', 'message' => getPlayerCountRecommendation($st_count, 2, 3)];

$lw_count = $position_counts['lw'] ?? 0;
$lm_count = $position_counts['lm'] ?? 0;
$recommendations['lw'] = ($lw_count == 0 && $lm_count >= 2) ? ['name' => 'LW', 'message' => "<span class='flex items-center text-green-600 text-xs font-bold bg-green-50 px-2 py-1 rounded-md'><i data-lucide='check-circle-2' class='w-3 h-3 mr-1'></i> ทดแทนได้</span>"] : ['name' => 'LW', 'message' => getPlayerCountRecommendation($lw_count, 2, 3)];
$recommendations['lm'] = ($lm_count == 0 && $lw_count >= 2) ? ['name' => 'LM', 'message' => "<span class='flex items-center text-green-600 text-xs font-bold bg-green-50 px-2 py-1 rounded-md'><i data-lucide='check-circle-2' class='w-3 h-3 mr-1'></i> ทดแทนได้</span>"] : ['name' => 'LM', 'message' => getPlayerCountRecommendation($lm_count, 2, 3)];

$rw_count = $position_counts['rw'] ?? 0;
$rm_count = $position_counts['rm'] ?? 0;
$recommendations['rw'] = ($rw_count == 0 && $rm_count >= 2) ? ['name' => 'RW', 'message' => "<span class='flex items-center text-green-600 text-xs font-bold bg-green-50 px-2 py-1 rounded-md'><i data-lucide='check-circle-2' class='w-3 h-3 mr-1'></i> ทดแทนได้</span>"] : ['name' => 'RW', 'message' => getPlayerCountRecommendation($rw_count, 2, 3)];
$recommendations['rm'] = ($rm_count == 0 && $rw_count >= 2) ? ['name' => 'RM', 'message' => "<span class='flex items-center text-green-600 text-xs font-bold bg-green-50 px-2 py-1 rounded-md'><i data-lucide='check-circle-2' class='w-3 h-3 mr-1'></i> ทดแทนได้</span>"] : ['name' => 'RM', 'message' => getPlayerCountRecommendation($rm_count, 2, 3)];

$cam_count = $position_counts['cam'] ?? 0;
$cm_count = $position_counts['cm'] ?? 0;
$recommendations['cam'] = ($cam_count == 0 && $cm_count >= 3) ? ['name' => 'CAM', 'message' => "<span class='flex items-center text-green-600 text-xs font-bold bg-green-50 px-2 py-1 rounded-md'><i data-lucide='check-circle-2' class='w-3 h-3 mr-1'></i> ทดแทนได้</span>"] : ['name' => 'CAM', 'message' => getPlayerCountRecommendation($cam_count, 2, 3)];
$recommendations['cm'] = ($cm_count == 0 && $cam_count >= 3) ? ['name' => 'CM', 'message' => "<span class='flex items-center text-green-600 text-xs font-bold bg-green-50 px-2 py-1 rounded-md'><i data-lucide='check-circle-2' class='w-3 h-3 mr-1'></i> ทดแทนได้</span>"] : ['name' => 'CM', 'message' => getPlayerCountRecommendation($cm_count, 2, 3)];

$recommendations['cdm'] = ['name' => 'CDM', 'message' => getPlayerCountRecommendation($position_counts['cdm'] ?? 0, 4, 5)];
$recommendations['cb'] = ['name' => 'CB', 'message' => getPlayerCountRecommendation($position_counts['cb'] ?? 0, 4, 5)];
$recommendations['lb'] = ['name' => 'LB', 'message' => getPlayerCountRecommendation($position_counts['lb'] ?? 0, 2, 3)];
$recommendations['rb'] = ['name' => 'RB', 'message' => getPlayerCountRecommendation($position_counts['rb'] ?? 0, 2, 3)];
$recommendations['gk'] = ['name' => 'GK', 'message' => getPlayerCountRecommendation($position_counts['gk'] ?? 0, 2, 3)];
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แผนการเล่น | FMFC Manager</title>
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

        <main class="flex-1 px-8 py-10 overflow-y-auto">
            
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 tracking-tight flex items-center gap-3">
                        <i data-lucide="layout-template" class="w-8 h-8 text-black"></i> แผนการเล่น
                    </h1>
                    <p class="text-gray-500 mt-1 text-sm">ตรวจสอบความสมดุลของทีมในแต่ละตำแหน่ง</p>
                </div>
                
                <div class="flex gap-2">
                    <div class="bg-white border border-gray-200 px-4 py-2 rounded-xl shadow-sm text-center min-w-[100px]">
                        <p class="text-[10px] text-gray-400 uppercase font-bold tracking-wider">ทั้งหมด</p>
                        <p class="text-xl font-bold text-gray-900"><?= $totalPlayers ?></p>
                    </div>
                    <div class="bg-white border border-gray-200 px-4 py-2 rounded-xl shadow-sm text-center min-w-[100px]">
                        <p class="text-[10px] text-green-500 uppercase font-bold tracking-wider">พร้อมเล่น</p>
                        <p class="text-xl font-bold text-green-600"><?= $status_counts['no'] ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm mb-8">
                <h2 class="text-sm font-bold text-gray-900 mb-4 flex items-center gap-2 uppercase tracking-wide">
                    <i data-lucide="brain-circuit" class="w-4 h-4 text-purple-600"></i>
                    AI Analysis: ความสมดุลของทีม
                </h2>
                <div class="flex flex-wrap gap-3">
                    <?php foreach ($recommendations as $rec): ?>
                        <div class="flex items-center gap-2 px-3 py-2 bg-gray-50 border border-gray-100 rounded-lg">
                            <span class="text-xs font-bold text-gray-700"><?= htmlspecialchars($rec['name']); ?></span>
                            <?= $rec['message']; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 pb-10">
                <?php foreach ($positions_grid as $row): ?>
                    <?php foreach ($row as $pos): ?>
                        <div class="flex flex-col h-full">
                            <?php if ($pos): ?>
                                <div class="bg-white border border-gray-200 rounded-t-2xl p-3 flex justify-between items-center shadow-sm z-10 relative">
                                    <span class="text-sm font-black text-gray-900 uppercase tracking-widest"><?= htmlspecialchars($pos); ?></span>
                                    <span class="text-xs font-medium bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">
                                        <?= $position_counts[$pos] ?? 0; ?> คน
                                    </span>
                                </div>

                                <div class="bg-gray-50 border-x border-b border-gray-200 rounded-b-2xl p-3 flex-1 min-h-[120px] shadow-inner space-y-2">
                                    <?php
                                    $players_in_pos = array_filter($players, fn($p) => $p['position'] === $pos);
                                    if (!empty($players_in_pos)):
                                        foreach ($players_in_pos as $player):
                                            $initials = getInitials($player['name']);
                                            $isInjured = ($player['injured'] ?? 0) > 0;
                                            $score = $player['performance_score'] ?? 0;
                                            
                                            // Dynamic Classes
                                            $cardBorder = $isInjured ? 'border-red-200 bg-red-50' : 'border-gray-200 bg-white hover:border-gray-300';
                                            $scoreColor = $score > 0 ? 'text-green-600' : ($score < 0 ? 'text-red-600' : 'text-gray-400');
                                            $scoreIcon = $score > 0 ? 'arrow-up' : ($score < 0 ? 'arrow-down' : 'minus');
                                    ?>
                                        <a href="edit_player1.php?id=<?= $player['player_id']; ?>" 
                                           class="block p-2 rounded-xl border <?= $cardBorder ?> shadow-sm transition-all duration-200 hover:shadow-md group">
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 rounded-full bg-black text-white flex items-center justify-center text-[10px] font-bold shadow-sm group-hover:scale-105 transition-transform">
                                                    <?= $initials ?>
                                                </div>
                                                
                                                <div class="flex-1 min-w-0">
                                                    <div class="flex justify-between items-center">
                                                        <span class="text-xs font-bold text-gray-800 truncate pr-1"><?= htmlspecialchars($player['name']); ?></span>
                                                        <?php if ($score != 0): ?>
                                                            <div class="flex items-center <?= $scoreColor ?> text-[10px] font-bold bg-white px-1 rounded shadow-sm border border-gray-100">
                                                                <i data-lucide="<?= $scoreIcon ?>" class="w-2.5 h-2.5 mr-0.5"></i><?= abs($score) ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    
                                                    <div class="flex items-center gap-1.5 mt-0.5">
                                                        <span class="text-[10px] text-gray-500 uppercase"><?= htmlspecialchars($player['role']); ?></span>
                                                        <?php if ($player['jersey_number']): ?>
                                                            <span class="text-[9px] bg-gray-100 text-gray-600 px-1 rounded border border-gray-200">#<?= $player['jersey_number'] ?></span>
                                                        <?php endif; ?>
                                                        
                                                        <?php if ($isInjured): ?>
                                                            <span class="text-[9px] bg-red-100 text-red-600 px-1 rounded font-bold">เจ็บ</span>
                                                        <?php elseif ($player['status'] == 'sell'): ?>
                                                            <span class="text-[9px] bg-red-50 text-red-500 px-1 rounded border border-red-100">ขาย</span>
                                                        <?php elseif ($player['is_academy_product']): ?>
                                                            <i data-lucide="graduation-cap" class="w-3 h-3 text-purple-500"></i>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="h-full flex flex-col items-center justify-center text-gray-300 py-4 opacity-50">
                                            <i data-lucide="user-x" class="w-6 h-6 mb-1"></i>
                                            <span class="text-xs font-medium">ว่าง</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div class="hidden lg:block min-h-[50px]"></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </div>

        </main>
    </div>

    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script>
        lucide.createIcons();

        // Toast Handler
        <?php if (isset($_SESSION['toast_message'])): ?>
            const msg = <?php echo json_encode($_SESSION['toast_message']); ?>;
            let bg = msg.type === 'success' ? "#18181b" : (msg.type === 'error' ? "#ef4444" : "#3f3f46");
            
            Toastify({
                text: msg.message,
                duration: 3000,
                close: true,
                gravity: "top",
                position: "right",
                style: {
                    background: bg,
                    borderRadius: "12px",
                    boxShadow: "0 4px 12px rgba(0,0,0,0.1)",
                    fontSize: "14px",
                    padding: "12px 20px",
                    fontFamily: "'Kanit', sans-serif"
                }
            }).showToast();
            <?php unset($_SESSION['toast_message']); ?>
        <?php endif; ?>
    </script>
</body>
</html>