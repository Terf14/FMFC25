<?php
session_start();
include "../includes/db_config.php";
include "../includes/auth.php";

// Check User
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

// Fetch Academy Players
$query = $conn->prepare("SELECT * FROM academy_players WHERE user_id = :user_id ORDER BY academy_player_id ASC");
$query->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$query->execute();
$academyPlayers = $query->fetchAll(PDO::FETCH_ASSOC);

// Initials Helper
if (!function_exists('getInitials')) {
    function getInitials($name) {
        $words = explode(' ', $name);
        $initials = '';
        foreach ($words as $word) {
            if (!empty($word)) $initials .= strtoupper(substr($word, 0, 1));
        }
        if (strlen($initials) > 2 && count($words) > 1) $initials = substr($initials, 0, 1) . substr($initials, -1);
        elseif (empty($initials) && !empty($name)) $initials = strtoupper(substr($name, 0, min(2, strlen($name))));
        return $initials;
    }
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Academy | FMFC Manager</title>
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

        <main class="flex-1 px-8 py-10 overflow-y-auto">
            
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-10">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 tracking-tight flex items-center gap-3">
                        <i data-lucide="graduation-cap" class="w-8 h-8 text-black"></i>
                        Academy
                    </h1>
                    <p class="text-gray-500 mt-1 text-sm">ศูนย์ฝึกเยาวชนและดาวรุ่งอนาคตไกล (<?= count($academyPlayers) ?> คน)</p>
                </div>
                <a href="add_academy_player.php" class="bg-black text-white px-6 py-3 rounded-xl hover:bg-gray-800 transition-all shadow-lg shadow-gray-200 flex items-center gap-2 font-medium">
                    <i data-lucide="plus" class="w-5 h-5"></i> เพิ่มนักเตะเยาวชน
                </a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-6">
                <?php if (count($academyPlayers) > 0): ?>
                    <?php foreach ($academyPlayers as $player): ?>
                        <?php $initials = getInitials($player['name']); ?>
                        <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 hover:shadow-lg hover:-translate-y-1 transition-all duration-300 group flex flex-col items-center relative overflow-hidden">
                            
                            <div class="absolute top-0 left-0 w-full h-24 bg-gradient-to-b from-gray-50 to-transparent z-0"></div>

                            <div class="w-20 h-20 rounded-full bg-white border-4 border-gray-50 flex items-center justify-center text-gray-800 font-bold text-2xl mb-4 shadow-sm z-10 group-hover:border-black group-hover:bg-black group-hover:text-white transition-all duration-300">
                                <?= htmlspecialchars($initials); ?>
                            </div>

                            <h3 class="text-lg font-bold text-gray-900 truncate w-full text-center px-2 mb-1 z-10">
                                <?= htmlspecialchars($player['name']); ?>
                            </h3>
                            <span class="px-3 py-1 rounded-full bg-gray-100 text-gray-600 text-xs font-bold uppercase tracking-wider mb-6 z-10">
                                <?= htmlspecialchars($player['position']); ?>
                            </span>

                            <div class="flex flex-col gap-2 w-full mt-auto z-10">
                                <button onclick="promotePlayer(<?= $player['academy_player_id']; ?>, '<?= htmlspecialchars($player['name']); ?>')"
                                    class="w-full bg-black text-white py-2.5 rounded-xl hover:bg-gray-800 transition shadow-md flex items-center justify-center gap-2 text-sm font-medium">
                                    <i data-lucide="arrow-up-circle" class="w-4 h-4"></i> ดันขึ้นชุดใหญ่
                                </button>
                                <a href="edit_academy_player.php?id=<?= $player['academy_player_id']; ?>"
                                    class="w-full bg-white border border-gray-200 text-gray-600 py-2.5 rounded-xl hover:bg-gray-50 hover:text-black hover:border-gray-300 transition flex items-center justify-center gap-2 text-sm font-medium">
                                    <i data-lucide="settings-2" class="w-4 h-4"></i> แก้ไข
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-span-full py-20 flex flex-col items-center justify-center text-center border-2 border-dashed border-gray-200 rounded-3xl bg-gray-50/50">
                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4 text-gray-400">
                            <i data-lucide="users" class="w-8 h-8"></i>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900">ยังไม่มีนักเตะเยาวชน</h3>
                        <p class="text-gray-500 text-sm mt-1 mb-6">เริ่มสร้างรากฐานทีมของคุณด้วยการเพิ่มนักเตะเยาวชน</p>
                        <a href="add_academy_player.php" class="text-black font-semibold hover:underline">เพิ่มนักเตะคนแรก</a>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script>
        lucide.createIcons();

        // Show Toast Function (Reusable)
        function showToast(type, message, redirectUrl = null) {
            let bg = type === 'success' ? "#18181b" : "#ef4444"; // Black or Red
            Toastify({
                text: message,
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
                },
                callback: function() {
                    if (redirectUrl) window.location.href = redirectUrl;
                }
            }).showToast();
        }

        // Handle Session Toast
        <?php if (isset($_SESSION['toast_message'])): ?>
            showToast('<?= $_SESSION['toast_message']['type'] ?>', '<?= $_SESSION['toast_message']['message'] ?>');
            <?php unset($_SESSION['toast_message']); ?>
        <?php endif; ?>

        // Promote Player Function
        function promotePlayer(playerId, playerName) {
            if (confirm(`ยืนยันที่จะดัน "${playerName}" ขึ้นสู่ทีมชุดใหญ่?`)) {
                fetch('promote_academy_player.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'player_id=' + playerId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast('success', `${playerName} ถูกดันขึ้นชุดใหญ่แล้ว!`);
                        setTimeout(() => window.location.reload(), 1500);
                    } else {
                        showToast('error', 'เกิดข้อผิดพลาด: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('error', 'เกิดข้อผิดพลาดในการเชื่อมต่อ');
                });
            }
        }
    </script>
</body>
</html>