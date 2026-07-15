<?php
session_start();
include '../includes/db_config.php';
include '../includes/auth.php';

// Check ID
if (!isset($_GET['id'])) {
    header("Location: academy.php");
    exit;
}

$player_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// Fetch Player Data
$query = $conn->prepare("SELECT * FROM Academy_Players WHERE academy_player_id = ? AND user_id = ?");
$query->execute([$player_id, $user_id]);
$player = $query->fetch(PDO::FETCH_ASSOC);

if (!$player) {
    $_SESSION['toast_message'] = ['type' => 'error', 'message' => 'ไม่พบข้อมูลนักเตะเยาวชนนี้!'];
    header("Location: academy.php");
    exit;
}

// Update Logic
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update'])) {
    $name = $_POST['name'];
    $position = $_POST['position'];

    try {
        $updateQuery = $conn->prepare("UPDATE Academy_Players SET name = ?, position = ? WHERE academy_player_id = ? AND user_id = ?");
        $updateQuery->execute([$name, $position, $player_id, $user_id]);

        $_SESSION['toast_message'] = ['type' => 'success', 'message' => 'อัปเดตนักเตะเยาวชนสำเร็จ!'];
        header("Location: academy.php");
        exit;
    } catch (PDOException $e) {
        $_SESSION['toast_message'] = ['type' => 'error', 'message' => 'เกิดข้อผิดพลาดในการอัปเดต: ' . htmlspecialchars($e->getMessage())];
        header("Location: edit_academy_player.php?id=" . $player_id);
        exit;
    }
}

// Delete Logic
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete'])) {
    try {
        $deleteQuery = $conn->prepare("DELETE FROM Academy_Players WHERE academy_player_id = ? AND user_id = ?");
        $deleteQuery->execute([$player_id, $user_id]);

        $_SESSION['toast_message'] = ['type' => 'success', 'message' => 'ลบนักเตะเยาวชนสำเร็จ!'];
        header("Location: academy.php");
        exit;
    } catch (PDOException $e) {
        $_SESSION['toast_message'] = ['type' => 'error', 'message' => 'เกิดข้อผิดพลาดในการลบ: ' . htmlspecialchars($e->getMessage())];
        header("Location: edit_academy_player.php?id=" . $player_id);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>แก้ไขนักเตะเยาวชน | FMFC Manager</title>
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
            <div class="max-w-2xl mx-auto">
                
                <div class="mb-8 text-center">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4 text-gray-500">
                        <i data-lucide="user-pen" class="w-8 h-8"></i>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900 tracking-tight">แก้ไขข้อมูลนักเตะเยาวชน</h1>
                    <p class="text-gray-500 mt-1 text-sm">ปรับปรุงรายละเอียดของ <?= htmlspecialchars($player['name']) ?></p>
                </div>

                <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100">
                    <form method="POST" class="space-y-6">
                        
                        <div>
                            <label for="name" class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">ชื่อนักเตะ</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <i data-lucide="user" class="w-5 h-5 text-gray-400"></i>
                                </div>
                                <input type="text" id="name" name="name" value="<?= htmlspecialchars($player['name']) ?>" required
                                    class="w-full pl-11 pr-4 py-3 bg-gray-50 border-gray-200 border rounded-xl focus:bg-white focus:ring-2 focus:ring-black focus:border-transparent transition-all font-medium text-gray-900">
                            </div>
                        </div>

                        <div>
                            <label for="position" class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">ตำแหน่ง</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <i data-lucide="crosshair" class="w-5 h-5 text-gray-400"></i>
                                </div>
                                <select id="position" name="position" required
                                    class="w-full pl-11 pr-4 py-3 bg-gray-50 border-gray-200 border rounded-xl focus:bg-white focus:ring-2 focus:ring-black focus:border-transparent transition-all font-medium text-gray-900 appearance-none cursor-pointer">
                                    <?php
                                    $positions = ['st', 'cf', 'lw', 'rw', 'lm', 'rm', 'cam', 'cm', 'cdm', 'cb', 'lb', 'rb', 'gk'];
                                    foreach ($positions as $pos) {
                                        $selected = ($player['position'] == $pos) ? "selected" : "";
                                        echo "<option value=\"$pos\" $selected>" . strtoupper($pos) . "</option>";
                                    }
                                    ?>
                                </select>
                                <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                                    <i data-lucide="chevron-down" class="w-4 h-4 text-gray-400"></i>
                                </div>
                            </div>
                        </div>

                        <div class="pt-6 border-t border-gray-100 flex flex-col-reverse sm:flex-row items-center justify-between gap-4">
                            <a href="academy.php" class="w-full sm:w-auto text-center px-6 py-3 rounded-xl border border-gray-200 text-gray-600 font-bold text-sm hover:bg-gray-50 hover:text-black transition">
                                ยกเลิก
                            </a>
                            
                            <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                                <button type="button" onclick="confirmDelete()"
                                    class="w-full sm:w-auto px-6 py-3 rounded-xl bg-red-50 text-red-600 font-bold text-sm hover:bg-red-100 transition flex items-center justify-center gap-2">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i> ลบนักเตะ
                                </button>
                                <button type="submit" name="update"
                                    class="w-full sm:w-auto px-8 py-3 rounded-xl bg-black text-white font-bold text-sm hover:bg-gray-800 transition shadow-lg shadow-gray-200 flex items-center justify-center gap-2">
                                    <i data-lucide="save" class="w-4 h-4"></i> บันทึก
                                </button>
                            </div>
                        </div>
                    </form>
                    
                    <form id="deleteForm" method="POST" class="hidden">
                        <input type="hidden" name="delete" value="1">
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script>
        lucide.createIcons();

        // Show Toast Function (Reusable)
        function showToast(type, message) {
            let bg = type === 'success' ? "#18181b" : "#ef4444";
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
                }
            }).showToast();
        }

        // Handle Session Toast
        <?php if (isset($_SESSION['toast_message'])): ?>
            showToast('<?= $_SESSION['toast_message']['type'] ?>', '<?= $_SESSION['toast_message']['message'] ?>');
            <?php unset($_SESSION['toast_message']); ?>
        <?php endif; ?>

        // Confirm Delete
        function confirmDelete() {
            if (confirm('คุณต้องการลบนักเตะเยาวชนคนนี้หรือไม่? การกระทำนี้ไม่สามารถย้อนกลับได้')) {
                document.getElementById('deleteForm').submit();
            }
        }
    </script>
</body>
</html>