<?php
session_start();
include "../includes/db_config.php";
include "../includes/auth.php";
include "../includes/ui_helpers.php";

$user_id = $_SESSION['user_id'];

// Fetch Players Data
$players = [];
if ($conn instanceof PDO) {
    // ดึงข้อมูลรวมถึง score_change และ change_count เพื่อนำมาแสดงผล
    $query = $conn->prepare("
        SELECT player_id, name, position, role, jersey_number, performance_score, score_change, change_count, injured 
        FROM Players 
        WHERE user_id = ? AND status != 'on_loan' 
        ORDER BY FIELD(COALESCE(role, 'prospect'), 'crucial', 'important', 'rotation', 'sporadic', 'prospect'), name ASC
    ");
    $query->execute([$user_id]);
    $players = $query->fetchAll(PDO::FETCH_ASSOC);
}

// Field Grid Structure
$positions = [
    ['lw', 'st', 'rw'],
    ['lm', 'cam', 'rm'],
    ['', 'cm', ''],
    ['', 'cdm', ''],
    ['lb', 'cb', 'rb'],
    ['', 'gk', '']
];

// Helper for Initials
if (!function_exists('getInitials')) {
    function getInitials($name) {
        $words = explode(' ', $name);
        $initials = '';
        foreach ($words as $word) $initials .= strtoupper(substr($word, 0, 1));
        if (strlen($initials) > 2) $initials = substr($initials, 0, 1) . substr($initials, -1);
        elseif (empty($initials) && !empty($name)) $initials = strtoupper(substr($name, 0, min(2, strlen($name))));
        return $initials;
    }
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการฟอร์ม | FMFC Manager</title>
    <link rel="icon" type="image/png" href="../img/logo/fm25_logo_2.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Itim&family=Kanit:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <style>
        body { font-family: 'Kanit', sans-serif; }
        /* Custom Scrollbar for the main area if needed */
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #9ca3af; }
    </style>
</head>

<body class="bg-[#F9FAFB] text-gray-800">
    <div class="flex h-screen overflow-hidden">
        <?php include '../includes/navbar.php'; ?>

        <main class="flex-1 px-8 py-10 overflow-y-auto custom-scrollbar">
            
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 tracking-tight flex items-center gap-3">
                        <i data-lucide="square-activity" class="w-8 h-8 text-black"></i>
                        จัดการฟอร์มการเล่น
                    </h1>
                    <p class="text-gray-500 mt-1 text-sm">ปรับปรุงคะแนน Performance ของนักเตะหลังจบการแข่งขัน</p>
                </div>
                <button id="reset-score"
                    class="bg-white border border-red-200 text-red-600 px-6 py-3 rounded-xl hover:bg-red-50 hover:border-red-300 transition shadow-sm flex items-center gap-2 font-medium group">
                    <i data-lucide="rotate-ccw" class="w-5 h-5 group-hover:-rotate-90 transition-transform"></i>
                    รีเซ็ตคะแนนทั้งหมด
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 pb-10">
                <?php foreach ($positions as $row): ?>
                    <?php foreach ($row as $pos): ?>
                        <?php if ($pos): ?>
                            <div class="flex flex-col h-full bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden">
                                <div class="bg-gray-50 border-b border-gray-100 px-4 py-3 flex justify-between items-center">
                                    <?= fmfc_position_badge($pos) ?>
                                    <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider"><?= count(array_filter($players, fn($p) => $p['position'] === $pos)) ?> คน</span>
                                </div>

                                <div class="p-3 space-y-3 flex-1">
                                    <?php
                                    $players_in_pos = array_filter($players, fn($p) => $p['position'] === $pos);
                                    if (!empty($players_in_pos)):
                                        foreach ($players_in_pos as $player):
                                            $initials = getInitials($player['name']);
                                            $score = $player['performance_score'];
                                            $change_status = $player['score_change'];
                                            $change_count = $player['change_count'];
                                            
                                            // Determine styles based on score/change
                                            $scoreClass = $score > 0 ? 'text-green-600' : ($score < 0 ? 'text-red-600' : 'text-gray-400');
                                            
                                            $changeIcon = '';
                                            $changeClass = 'hidden'; // Default hidden
                                            $changeText = '';

                                            if ($change_status === 'increase') {
                                                $changeIcon = 'arrow-up';
                                                $changeClass = 'text-green-500 bg-green-50';
                                                $changeText = '+' . $change_count;
                                            } elseif ($change_status === 'decrease') {
                                                $changeIcon = 'arrow-down';
                                                $changeClass = 'text-red-500 bg-red-50';
                                                $changeText = '-' . $change_count;
                                            }
                                    ?>
                                        <div class="flex flex-col p-3 rounded-xl border border-gray-100 hover:border-gray-300 transition-colors bg-white shadow-sm group">
                                            
                                            <div class="flex items-center gap-3 mb-3">
                                                <div class="w-10 h-10 rounded-full bg-black text-white flex items-center justify-center text-xs font-bold shadow-sm">
                                                    <?= $initials ?>
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <div class="flex items-center gap-2">
                                                        <span class="font-bold text-gray-800 text-sm truncate"><?= htmlspecialchars($player['name']) ?></span>
                                                        <span id="change-<?= $player['player_id'] ?>" class="text-[10px] font-bold px-1.5 py-0.5 rounded-md flex items-center gap-0.5 <?= $changeClass ?>">
                                                            <?php if($changeIcon): ?><i data-lucide="<?= $changeIcon ?>" class="w-3 h-3"></i><?php endif; ?>
                                                            <?= $changeText ?>
                                                        </span>
                                                    </div>
                                                    <span class="text-xs text-gray-400">#<?= $player['jersey_number'] ?? '-' ?></span>
                                                </div>
                                            </div>

                                            <div class="flex items-center justify-between bg-gray-50 rounded-lg p-1">
                                                <button class="decrease-score w-10 h-10 rounded-lg bg-white border border-gray-200 text-gray-400 hover:text-red-600 hover:border-red-200 hover:bg-red-50 transition flex items-center justify-center shadow-sm active:scale-95"
                                                    data-id="<?= $player['player_id'] ?>">
                                                    <i data-lucide="minus" class="w-5 h-5"></i>
                                                </button>

                                                <span id="score-<?= $player['player_id'] ?>" class="text-xl font-bold <?= $scoreClass ?> w-12 text-center transition-all duration-300">
                                                    <?= $score ?>
                                                </span>

                                                <button class="increase-score w-10 h-10 rounded-lg bg-white border border-gray-200 text-gray-400 hover:text-green-600 hover:border-green-200 hover:bg-green-50 transition flex items-center justify-center shadow-sm active:scale-95"
                                                    data-id="<?= $player['player_id'] ?>">
                                                    <i data-lucide="plus" class="w-5 h-5"></i>
                                                </button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="h-full flex items-center justify-center py-6 text-gray-300">
                                            <span class="text-xs">ว่าง</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="hidden md:block"></div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </div>
        </main>
    </div>

    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script>
        lucide.createIcons();

        const showToast = window.fmfcShowToast || window.showToast;

        // --- Core Logic ---

        // Handle Score Update
        function updateScore(playerId, action) {
            const btn = document.querySelector(`button[data-id="${playerId}"].${action === 'increase' ? 'increase' : 'decrease'}-score`);
            // Add loading state visual (optional)
            
            fetch('update_score.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `player_id=${playerId}&action=${action}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update Score Text & Color
                    const scoreEl = document.getElementById(`score-${playerId}`);
                    scoreEl.innerText = data.new_score;
                    
                    scoreEl.classList.remove('text-green-600', 'text-red-600', 'text-gray-400');
                    if (data.new_score > 0) scoreEl.classList.add('text-green-600');
                    else if (data.new_score < 0) scoreEl.classList.add('text-red-600');
                    else scoreEl.classList.add('text-gray-400');

                    // Update Change Indicator
                    const changeEl = document.getElementById(`change-${playerId}`);
                    changeEl.className = 'text-[10px] font-bold px-1.5 py-0.5 rounded-md flex items-center gap-0.5'; // Reset base classes
                    
                    let iconHtml = '';
                    if (data.change_status === 'increase') {
                        changeEl.classList.add('text-green-500', 'bg-green-50');
                        changeEl.classList.remove('hidden');
                        iconHtml = '<i data-lucide="arrow-up" class="w-3 h-3"></i>';
                        changeEl.innerHTML = `${iconHtml}+${data.change_count}`;
                    } else if (data.change_status === 'decrease') {
                        changeEl.classList.add('text-red-500', 'bg-red-50');
                        changeEl.classList.remove('hidden');
                        iconHtml = '<i data-lucide="arrow-down" class="w-3 h-3"></i>';
                        changeEl.innerHTML = `${iconHtml}-${data.change_count}`;
                    } else {
                        changeEl.classList.add('hidden');
                        changeEl.innerHTML = '';
                    }
                    
                    lucide.createIcons(); // Re-render new icons
                } else {
                    showToast('error', data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('error', 'การเชื่อมต่อผิดพลาด');
            });
        }

        // Attach Event Listeners
        document.querySelectorAll('.increase-score').forEach(btn => {
            btn.addEventListener('click', function() { updateScore(this.getAttribute('data-id'), 'increase'); });
        });

        document.querySelectorAll('.decrease-score').forEach(btn => {
            btn.addEventListener('click', function() { updateScore(this.getAttribute('data-id'), 'decrease'); });
        });

        // Handle Reset
        document.getElementById('reset-score').addEventListener('click', function() {
            if (confirm('แน่ใจหรือไม่ว่าต้องการรีเซ็ตคะแนนทั้งหมด? ค่า Score จะกลับเป็น 0')) {
                fetch('reset_score.php', { method: 'POST' })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Reset DOM elements directly without reload
                        document.querySelectorAll('[id^="score-"]').forEach(el => {
                            el.innerText = '0';
                            el.className = 'text-xl font-bold text-gray-400 w-12 text-center transition-all duration-300';
                        });
                        document.querySelectorAll('[id^="change-"]').forEach(el => {
                            el.classList.add('hidden');
                            el.innerHTML = '';
                        });
                        showToast('success', 'รีเซ็ตคะแนนเรียบร้อย');
                    } else {
                        showToast('error', data.message || 'เกิดข้อผิดพลาด');
                    }
                })
                .catch(error => showToast('error', 'การเชื่อมต่อผิดพลาด'));
            }
        });

    </script>
</body>
</html>
