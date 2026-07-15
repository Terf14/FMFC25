<?php
session_start();
include "../includes/db_config.php";
include "../includes/auth.php";

$user_id = $_SESSION['user_id'];

$status_counts = [
    'sell' => 0,
    'for_loan' => 0,
    'on_loan' => 0,
    'in_loan' => 0,
    'no' => 0
];

$status_display = [
    'no' => 'อยู่กับทีม ',
    'sell' => 'เตรียมขาย ',
    'for_loan' => 'เตรียมปล่อยยืม ',
    'on_loan' => 'กำลังปล่อยยืม ',
    'in_loan' => 'กำลังยืมตัว '
];

// ดึงข้อมูลนักเตะ
$stmt_players = $conn->prepare("SELECT * FROM players WHERE user_id = ?");
$stmt_players->execute([$user_id]);
$players = $stmt_players->fetchAll(PDO::FETCH_ASSOC);

$total_players = count($players);
// นับจำนวน Prospect
$prospect_players = count(array_filter($players, fn($p) => $p['role'] === 'prospect'));

// รวมสถานะนักเตะ
foreach ($players as $p) {
    if (!empty($p['status']) && isset($status_counts[$p['status']])) {
        $status_counts[$p['status']]++;
    }
}

// ดึงข้อมูลอาคาเดมี่
$stmt_academy = $conn->prepare("SELECT COUNT(*) FROM academy_players WHERE user_id = ?");
$stmt_academy->execute([$user_id]);
$total_academy = $stmt_academy->fetchColumn();

// ดึงข้อมูลนักเตะในตำนาน
$stmt_legendary = $conn->prepare("SELECT COUNT(*) FROM legendary_players WHERE user_id = ?");
$stmt_legendary->execute([$user_id]);
$total_legendary = $stmt_legendary->fetchColumn();

$roles = ['crucial', 'important', 'rotation', 'sporadic', 'prospect'];
$role_display = [
    'crucial' => 'ตัวหลัก (Crucial)',
    'important' => 'สำคัญ (Important)',
    'rotation' => 'หมุนเวียน (Rotation)',
    'sporadic' => 'สำรอง (Sporadic)',
    'prospect' => 'ดาวรุ่ง (Prospect)'
];

$players_by_role = [];
foreach ($roles as $role) {
    $filtered = array_filter($players, fn($p) => $p['role'] === $role);
    $players_by_role[$role] = [
        'count' => count($filtered)
    ];
}

// ดีเด่น (Top 5 เพิ่มขึ้นเยอะสุด)
$top_performers = array_filter($players, fn($p) => $p['score_change'] === 'increase' && $p['change_count'] > 0);
usort($top_performers, fn($a, $b) => $b['change_count'] <=> $a['change_count']);
$top_performers = array_slice($top_performers, 0, 5);

// ยอดแย่ (Bottom 5 ลดลงเยอะสุด) - นำมาแสดงผลแล้ว
$bottom_performers = array_filter($players, fn($p) => $p['score_change'] === 'decrease' && $p['change_count'] > 0);
usort($bottom_performers, fn($a, $b) => $b['change_count'] <=> $a['change_count']);
$bottom_performers = array_slice($bottom_performers, 0, 5);
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>แดชบอร์ด | FMFC Manager</title>
    <link rel="icon" type="image/png" href="../img/logo/fm25_logo_2.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Itim&family=Kanit:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
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
            
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 tracking-tight flex items-center gap-3">
                    <i data-lucide="layout-dashboard" class="w-8 h-8 text-black"></i> Dashboard
                </h1>
                <p class="text-gray-500 mt-1 text-sm">ภาพรวมสโมสรและสถานะนักเตะปัจจุบัน</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4 mb-8">
                <?php
                $cards = [
                    ['title' => 'นักเตะทั้งหมด', 'value' => $total_players, 'icon' => 'users', 'color' => 'bg-black text-white'],
                    ['title' => 'ดาวรุ่ง (Prospects)', 'value' => $prospect_players, 'icon' => 'sprout', 'color' => 'bg-green-100 text-green-700'],
                    ['title' => 'อาคาเดมี่', 'value' => $total_academy, 'icon' => 'graduation-cap', 'color' => 'bg-blue-100 text-blue-700'],
                    ['title' => 'ปล่อยยืมตัว', 'value' => $status_counts['on_loan'], 'icon' => 'plane', 'color' => 'bg-orange-100 text-orange-700'],
                    ['title' => 'ตำนานสโมสร', 'value' => $total_legendary, 'icon' => 'crown', 'color' => 'bg-yellow-100 text-yellow-700'],
                ];
                foreach ($cards as $card): ?>
                <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-all duration-300 flex flex-col justify-between h-32">
                    <div class="flex justify-between items-start">
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest"><?= $card['title'] ?></p>
                        <div class="p-2 rounded-lg <?= $card['color'] ?>">
                            <i data-lucide="<?= $card['icon'] ?>" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <h3 class="text-3xl font-black text-gray-900"><?= $card['value'] ?></h3>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="flex flex-wrap gap-3 mb-10">
                <div class="px-4 py-2 bg-white border border-gray-200 rounded-full text-xs font-bold text-gray-600 shadow-sm flex items-center gap-2">
                    <div class="w-2 h-2 rounded-full bg-green-500"></div> อยู่กับทีม: <span class="text-black text-sm"><?= $status_counts['no'] ?></span>
                </div>
                <div class="px-4 py-2 bg-white border border-gray-200 rounded-full text-xs font-bold text-gray-600 shadow-sm flex items-center gap-2">
                    <div class="w-2 h-2 rounded-full bg-red-500"></div> เตรียมขาย: <span class="text-black text-sm"><?= $status_counts['sell'] ?></span>
                </div>
                <div class="px-4 py-2 bg-white border border-gray-200 rounded-full text-xs font-bold text-gray-600 shadow-sm flex items-center gap-2">
                    <div class="w-2 h-2 rounded-full bg-orange-500"></div> พร้อมปล่อยยืม: <span class="text-black text-sm"><?= $status_counts['for_loan'] ?></span>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-lg font-bold flex items-center gap-2">
                            <i data-lucide="trending-up" class="w-5 h-5 text-green-600"></i> ฟอร์มดีที่สุด
                        </h2>
                    </div>
                    <div class="space-y-3">
                        <?php if(empty($top_performers)): ?>
                            <p class="text-center text-gray-400 text-sm py-4">ยังไม่มีข้อมูลการเปลี่ยนแปลงคะแนน</p>
                        <?php else: ?>
                            <?php foreach ($top_performers as $p): ?>
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-2xl border border-gray-100">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-green-100 text-green-700 flex items-center justify-center font-bold text-xs">
                                        <?= substr($p['name'], 0, 1) ?>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-bold text-gray-900 truncate w-32"><?= htmlspecialchars($p['name']) ?></p>
                                        <p class="text-[10px] text-gray-400 uppercase"><?= $p['position'] ?></p>
                                    </div>
                                </div>
                                <span class="text-xs font-black text-green-600 bg-green-50 px-2 py-1 rounded-lg">+<?= $p['change_count'] ?></span>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-lg font-bold flex items-center gap-2">
                            <i data-lucide="trending-down" class="w-5 h-5 text-red-600"></i> ต้องเร่งฟอร์ม
                        </h2>
                    </div>
                    <div class="space-y-3">
                        <?php if(empty($bottom_performers)): ?>
                            <p class="text-center text-gray-400 text-sm py-4">ไม่มีนักเตะที่ฟอร์มตกในขณะนี้</p>
                        <?php else: ?>
                            <?php foreach ($bottom_performers as $p): ?>
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-2xl border border-gray-100">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-red-100 text-red-700 flex items-center justify-center font-bold text-xs">
                                        <?= substr($p['name'], 0, 1) ?>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-bold text-gray-900 truncate w-32"><?= htmlspecialchars($p['name']) ?></p>
                                        <p class="text-[10px] text-gray-400 uppercase"><?= $p['position'] ?></p>
                                    </div>
                                </div>
                                <span class="text-xs font-black text-red-600 bg-red-50 px-2 py-1 rounded-lg">-<?= $p['change_count'] ?></span>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm">
                    <h2 class="text-lg font-bold mb-6 flex items-center gap-2">
                        <i data-lucide="pie-chart" class="w-5 h-5 text-gray-600"></i> โครงสร้างทีม
                    </h2>
                    <div class="space-y-5">
                        <?php foreach ($roles as $role): 
                            $count = $players_by_role[$role]['count'];
                            $percent = ($total_players > 0) ? ($count / $total_players) * 100 : 0;
                        ?>
                        <div>
                            <div class="flex justify-between text-xs font-bold mb-2">
                                <span class="text-gray-600 uppercase tracking-wide"><?= $role_display[$role] ?></span>
                                <span class="text-black"><?= $count ?> คน</span>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden">
                                <div class="bg-black h-full rounded-full transition-all duration-500" style="width: <?= $percent ?>%"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

            </div>
        </main>
    </div>
    <script>lucide.createIcons();</script>
</body>

</html>