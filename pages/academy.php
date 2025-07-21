<?php
session_start();
include "../includes/db_config.php";
include "../includes/auth.php";

// ตรวจสอบว่า user_id อยู่ใน session หรือไม่
if (!isset($_SESSION['user_id'])) {
    // ถ้าไม่มี session ของ user_id ให้รีไดเร็กต์ไปหน้าเข้าสู่ระบบ
    header("Location: login.php");
    exit();
}

// รับ user_id จาก session
$user_id = $_SESSION['user_id'];

// แก้ไขคำสั่ง SQL เพื่อแสดงผลเฉพาะข้อมูลของ user นั้นๆ
$query = $conn->prepare("SELECT * FROM academy_players WHERE user_id = :user_id ORDER BY academy_player_id ASC");
$query->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$query->execute();
$academyPlayers = $query->fetchAll(PDO::FETCH_ASSOC);

// Function to get initials (เพื่อให้ใช้ใน Card View ได้)
if (!function_exists('getInitials')) {
    function getInitials($name) {
        $words = explode(' ', $name);
        $initials = '';
        foreach ($words as $word) {
            if (!empty($word)) {
                $initials .= strtoupper(substr($word, 0, 1));
            }
        }
        if (strlen($initials) > 2 && count($words) > 1) {
            $initials = substr($initials, 0, 1) . substr($initials, -1);
        } elseif (empty($initials) && !empty($name)) {
            $initials = strtoupper(substr($name, 0, min(2, strlen($name))));
        }
        return $initials;
    }
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>จัดการนักเตะเยาวชน | FM25 Manager</title>
    <link rel="icon" type="image/png" href="../img/logo/fm25_logo_2.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Itim&family=Kanit:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Kanit', sans-serif;
        }
    </style>
</head>

<body class="bg-gray-50 text-gray-800">
    <div class="flex h-screen overflow-hidden">
        <?php include '../includes/navbar.php'; /* */ ?>

        <main class="flex-1 overflow-y-auto px-10 py-8">
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-2xl font-semibold flex items-center gap-2 mb-6">
                    <i data-lucide="graduation-cap" class="w-5 h-5 text-gray-600"></i>
                    จัดการนักเตะเยาวชน
                </h1>
                <a href="add_academy_player.php"
                    class="bg-gray-900 text-white px-4 py-2 rounded-md hover:bg-gray-700 transition">เพิ่มนักเตะ</a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                <?php if (count($academyPlayers) > 0): ?>
                    <?php foreach ($academyPlayers as $player): ?>
                        <?php $initials = getInitials($player['name']); ?>
                        <div class="bg-white p-4 rounded-lg shadow-md border border-gray-200 flex flex-col items-center text-center">
                            <div class="w-16 h-16 rounded-full bg-blue-100 flex items-center justify-center text-blue-800 font-bold text-xl mb-3">
                                <?= htmlspecialchars($initials); ?>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-800 truncate w-full px-2 mb-1">
                                <?= htmlspecialchars($player['name']); ?>
                            </h3>
                            <p class="text-sm text-gray-500 uppercase mb-4"><?= htmlspecialchars($player['position']); ?></p>

                            <div class="flex flex-col gap-2 w-full mt-auto">
                                <a href="edit_academy_player.php?id=<?= $player['academy_player_id']; /* */ ?>"
                                    class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-300 transition text-sm font-medium flex items-center justify-center gap-2">
                                    <i data-lucide="user-pen" class="w-4 h-4"></i> แก้ไข
                                </a>
                                <button
                                    class="bg-yellow-500 text-white px-4 py-2 rounded-md hover:bg-yellow-600 transition text-sm font-medium flex items-center justify-center gap-2"
                                    onclick="promotePlayer(<?= $player['academy_player_id']; ?>, '<?= htmlspecialchars($player['name']); ?>')">
                                    <i data-lucide="arrow-up-circle" class="w-4 h-4"></i> ดันขึ้นชุดใหญ่
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-span-full bg-white p-6 rounded-lg shadow-sm border border-gray-200 text-center text-gray-500">
                        <p class="mb-3">ยังไม่มีนักเตะเยาวชนใน Academy</p>
                        <a href="add_academy_player.php" class="text-blue-600 hover:underline">เพิ่มนักเตะเยาวชนคนแรก</a>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        lucide.createIcons();

        function promotePlayer(playerId, playerName) {
            if (confirm("คุณแน่ใจหรือไม่ว่าต้องการดันนักเตะ " + playerName + " ขึ้นชุดใหญ่?")) {
                fetch('promote_academy_player.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'player_id=' + playerId
                })
                .then(response => response.text()) // รับ text response ก่อน
                .then(text => {
                    console.log('Server response:', text); // สำหรับ Debug
                    try {
                        const data = JSON.parse(text); // ลองแปลงเป็น JSON
                        if (data.success) {
                            showToastAndRedirect('success', playerName + ' ถูกดันขึ้นชุดใหญ่แล้ว!', 'academy.php');
                        } else {
                            showToastAndRedirect('error', 'เกิดข้อผิดพลาด: ' + data.message, 'academy.php');
                        }
                    } catch (e) {
                        // ถ้าไม่ใช่ JSON แสดงว่ามี Error จาก PHP ตรงๆ (เช่น Syntax Error)
                        showToastAndRedirect('error', 'เกิดข้อผิดพลาดในการประมวลผล: ' + text.substring(0, 100) + '...', 'academy.php');
                    }
                })
                .catch(error => {
                    console.error('Fetch Error:', error);
                    showToastAndRedirect('error', 'เกิดข้อผิดพลาดในการส่งข้อมูล: ' + error.message, 'academy.php');
                });
            }
        }
    </script>
</body>

</html>