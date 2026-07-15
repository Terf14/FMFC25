<?php
session_start();
include "../includes/db_config.php";
include "../includes/auth.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Helper: Initials
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

// Fetch Legendary Players
$legendary_players = [];
try {
    $legends_query = $conn->prepare("SELECT * FROM legendary_players WHERE user_id = ? ORDER BY player_rating DESC, player_name ASC");
    $legends_query->execute([$user_id]);
    $legendary_players = $legends_query->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>ทำเนียบตำนาน | FMFC Manager</title>
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
            
            <div class="flex items-center gap-4 mb-10">
                <div class="w-16 h-16 bg-black rounded-2xl flex items-center justify-center text-white shadow-lg shadow-gray-200">
                    <i data-lucide="crown" class="w-8 h-8"></i>
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 tracking-tight">Hall of Legends</h1>
                    <p class="text-gray-500 mt-1 text-sm">ทำเนียบนักเตะผู้ยิ่งใหญ่ที่ได้จารึกชื่อไว้กับสโมสร (<?= count($legendary_players) ?> คน)</p>
                </div>
            </div>

            <?php if (!empty($legendary_players)): ?>
                <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto no-scrollbar">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-100">
                                    <th class="px-6 py-5 text-xs font-bold text-gray-400 uppercase tracking-wider sticky left-0 bg-gray-50 z-10">ตำนาน (Legend)</th>
                                    <th class="px-4 py-5 text-xs font-bold text-gray-400 uppercase tracking-wider">ตำแหน่ง</th>
                                    <th class="px-4 py-5 text-xs font-bold text-gray-400 uppercase tracking-wider text-center">Rating</th>
                                    <th class="px-4 py-5 text-xs font-bold text-gray-400 uppercase tracking-wider text-center">ลงเล่น</th>
                                    <th class="px-4 py-5 text-xs font-bold text-gray-400 uppercase tracking-wider text-center">ประตู</th>
                                    <th class="px-4 py-5 text-xs font-bold text-gray-400 uppercase tracking-wider text-center">แอสซิสต์</th>
                                    <th class="px-4 py-5 text-xs font-bold text-gray-400 uppercase tracking-wider text-center">คลีนชีท</th>
                                    <th class="px-4 py-5 text-xs font-bold text-gray-400 uppercase tracking-wider text-center">วินัย (เหลือง/แดง)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php foreach ($legendary_players as $index => $legend): 
                                    $initials = getInitials($legend['player_name']);
                                    // Top 3 Badge styling
                                    $rankBadge = '';
                                    if ($index === 0) $rankBadge = '<span class="absolute -top-1 -right-1 w-4 h-4 bg-yellow-400 rounded-full border-2 border-white"></span>';
                                    elseif ($index === 1) $rankBadge = '<span class="absolute -top-1 -right-1 w-4 h-4 bg-gray-300 rounded-full border-2 border-white"></span>';
                                    elseif ($index === 2) $rankBadge = '<span class="absolute -top-1 -right-1 w-4 h-4 bg-orange-300 rounded-full border-2 border-white"></span>';
                                ?>
                                    <tr class="hover:bg-gray-50 transition-colors group">
                                        <td class="px-6 py-4 sticky left-0 bg-white group-hover:bg-gray-50 transition-colors">
                                            <div class="flex items-center gap-4">
                                                <div class="relative">
                                                    <div class="w-12 h-12 rounded-full bg-black text-white flex items-center justify-center text-sm font-bold shadow-md">
                                                        <?= $initials ?>
                                                    </div>
                                                    <?= $rankBadge ?>
                                                </div>
                                                <div>
                                                    <h3 class="text-sm font-bold text-gray-900"><?= htmlspecialchars($legend['player_name']); ?></h3>
                                                    <div class="flex items-center gap-2 mt-0.5">
                                                        <span class="text-[10px] text-gray-400">#<?= $legend['player_jersey_number'] ?? '-' ?></span>
                                                        <span class="text-[10px] bg-gray-100 text-gray-500 px-1.5 py-0.5 rounded capitalize"><?= htmlspecialchars($legend['player_role']); ?></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-4">
                                            <span class="text-[10px] font-bold bg-gray-100 text-gray-600 px-2 py-1 rounded uppercase tracking-wide">
                                                <?= htmlspecialchars($legend['player_position']); ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-4 text-center">
                                            <span class="text-sm font-bold text-black bg-gray-100 px-2 py-1 rounded-lg">
                                                <?= number_format($legend['player_rating'] ?? 0.00, 2); ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-4 text-center font-medium text-gray-600"><?= number_format($legend['player_appearances']); ?></td>
                                        <td class="px-4 py-4 text-center font-medium text-gray-600"><?= number_format($legend['player_goals']); ?></td>
                                        <td class="px-4 py-4 text-center font-medium text-gray-600"><?= number_format($legend['player_assists']); ?></td>
                                        <td class="px-4 py-4 text-center font-medium text-gray-600"><?= number_format($legend['player_clean_sheets']); ?></td>
                                        <td class="px-4 py-4 text-center">
                                            <div class="flex items-center justify-center gap-1">
                                                <span class="px-2 py-0.5 bg-yellow-50 text-yellow-600 rounded text-xs font-bold"><?= $legend['player_yellow_cards'] ?></span>
                                                <span class="text-gray-300">/</span>
                                                <span class="px-2 py-0.5 bg-red-50 text-red-600 rounded text-xs font-bold"><?= $legend['player_red_cards'] ?></span>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php else: ?>
                <div class="flex flex-col items-center justify-center py-24 bg-white border border-gray-100 rounded-3xl shadow-sm text-center">
                    <div class="w-24 h-24 bg-gray-50 rounded-full flex items-center justify-center mb-6 border-4 border-white shadow-sm">
                        <i data-lucide="shield-off" class="w-10 h-10 text-gray-300"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900">ยังไม่มีตำนานในหอเกียรติยศ</h3>
                    <p class="text-gray-500 mt-2 text-sm max-w-sm mx-auto">
                        เมื่อนักเตะคนสำคัญของคุณเลิกเล่นหรือย้ายทีม คุณสามารถย้ายพวกเขามาไว้ที่นี่เพื่อจารึกประวัติศาสตร์
                    </p>
                    <a href="manage_players.php" class="mt-8 px-6 py-3 bg-black text-white rounded-xl text-sm font-bold hover:bg-gray-800 transition-all shadow-lg shadow-gray-200">
                        กลับไปจัดการนักเตะ
                    </a>
                </div>
            <?php endif; ?>

        </main>
    </div>

    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script>
        lucide.createIcons();

        <?php if (isset($_SESSION['toast_message'])): ?>
            const msg = <?php echo json_encode($_SESSION['toast_message']); ?>;
            let bg = msg.type === 'success' ? "#18181b" : "#ef4444";
            Toastify({
                text: msg.message,
                duration: 3000,
                close: true,
                gravity: "top",
                position: "right",
                style: { background: bg, borderRadius: "12px", boxShadow: "0 4px 12px rgba(0,0,0,0.1)", fontSize: "14px", padding: "12px 20px" }
            }).showToast();
            <?php unset($_SESSION['toast_message']); ?>
        <?php endif; ?>
    </script>
</body>
</html>