<?php
session_start();
include "../includes/db_config.php";
include "../includes/auth.php";

// ตรวจสอบว่าผู้ใช้ได้ล็อกอินหรือไม่
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // รับค่าจากฟอร์ม
    $name = $_POST['name'] ?? '';
    $role = $_POST['role'] ?? NULL;
    $position = $_POST['position'] ?? NULL;
    $jersey_number = !empty($_POST['jersey_number']) ? $_POST['jersey_number'] : NULL;
    $rating = !empty($_POST['rating']) ? floatval($_POST['rating']) : 5.00;

    try {
        $query = "INSERT INTO Players (name, role, position, jersey_number, user_id, appearances, goals, assists, clean_sheets, yellow_cards, red_cards, rating)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->execute([$name, $role, $position, $jersey_number, $user_id, 0, 0, 0, 0, 0, 0, $rating]);

        $_SESSION['toast_message'] = [
            'type' => 'success',
            'message' => 'เพิ่มนักเตะเรียบร้อยแล้ว!'
        ];
        header("Location: manage_players.php");
        exit;
    } catch (PDOException $e) {
        $_SESSION['toast_message'] = [
            'type' => 'error',
            'message' => 'เกิดข้อผิดพลาด: ' . htmlspecialchars($e->getMessage())
        ];
        header("Location: add_player.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>เพิ่มนักเตะใหม่ | FMFC Manager</title>
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
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4 text-gray-500 shadow-sm border border-gray-200">
                        <i data-lucide="user-plus" class="w-8 h-8"></i>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900 tracking-tight">เพิ่มนักเตะใหม่</h1>
                    <p class="text-gray-500 mt-1 text-sm">กรอกข้อมูลเพื่อลงทะเบียนนักเตะเข้าสู่ทีมชุดใหญ่</p>
                </div>

                <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100">
                    <form action="add_player.php" method="POST" class="space-y-6">
                        
                        <div>
                            <label for="name" class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">ชื่อนักเตะ</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <i data-lucide="user" class="w-5 h-5 text-gray-400"></i>
                                </div>
                                <input type="text" id="name" name="name" required placeholder="ระบุชื่อนักเตะ"
                                    class="w-full pl-11 pr-4 py-3 bg-gray-50 border-gray-200 border rounded-xl focus:bg-white focus:ring-2 focus:ring-black focus:border-transparent transition-all font-medium text-gray-900 placeholder-gray-400">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="role" class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">บทบาท (Role)</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                        <i data-lucide="shield" class="w-5 h-5 text-gray-400"></i>
                                    </div>
                                    <select id="role" name="role"
                                        class="w-full pl-11 pr-4 py-3 bg-gray-50 border-gray-200 border rounded-xl focus:bg-white focus:ring-2 focus:ring-black focus:border-transparent transition-all font-medium text-gray-900 appearance-none cursor-pointer">
                                        <option value="crucial">Crucial</option>
                                        <option value="important">Important</option>
                                        <option value="rotation">Rotation</option>
                                        <option value="sporadic">Sporadic</option>
                                        <option value="prospect">Prospect</option>
                                    </select>
                                    <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                                        <i data-lucide="chevron-down" class="w-4 h-4 text-gray-400"></i>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label for="position" class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">ตำแหน่ง</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                        <i data-lucide="crosshair" class="w-5 h-5 text-gray-400"></i>
                                    </div>
                                    <select id="position" name="position"
                                        class="w-full pl-11 pr-4 py-3 bg-gray-50 border-gray-200 border rounded-xl focus:bg-white focus:ring-2 focus:ring-black focus:border-transparent transition-all font-medium text-gray-900 appearance-none cursor-pointer">
                                        <option value="st">ST</option>
                                        <option value="cf">CF</option>
                                        <option value="lw">LW</option>
                                        <option value="rw">RW</option>
                                        <option value="lm">LM</option>
                                        <option value="rm">RM</option>
                                        <option value="cam">CAM</option>
                                        <option value="cm">CM</option>
                                        <option value="cdm">CDM</option>
                                        <option value="rb">RB</option>
                                        <option value="lb">LB</option>
                                        <option value="cb">CB</option>
                                        <option value="gk">GK</option>
                                    </select>
                                    <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                                        <i data-lucide="chevron-down" class="w-4 h-4 text-gray-400"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="jersey_number" class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">เบอร์เสื้อ</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                        <i data-lucide="shirt" class="w-5 h-5 text-gray-400"></i>
                                    </div>
                                    <input type="number" id="jersey_number" name="jersey_number" placeholder="#"
                                        class="w-full pl-11 pr-4 py-3 bg-gray-50 border-gray-200 border rounded-xl focus:bg-white focus:ring-2 focus:ring-black focus:border-transparent transition-all font-medium text-gray-900">
                                </div>
                            </div>

                            <div>
                                <label for="rating" class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Rating (0.01 - 10.00)</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                        <i data-lucide="star" class="w-5 h-5 text-gray-400"></i>
                                    </div>
                                    <input type="number" id="rating" name="rating" step="0.01" min="0.01" max="10.00" value="5.00" required
                                        class="w-full pl-11 pr-4 py-3 bg-gray-50 border-gray-200 border rounded-xl focus:bg-white focus:ring-2 focus:ring-black focus:border-transparent transition-all font-medium text-gray-900">
                                </div>
                            </div>
                        </div>

                        <div class="pt-6 border-t border-gray-100 flex flex-col-reverse sm:flex-row items-center justify-between gap-4">
                            <a href="manage_players.php" class="w-full sm:w-auto text-center px-6 py-3 rounded-xl border border-gray-200 text-gray-600 font-bold text-sm hover:bg-gray-50 hover:text-black transition">
                                ยกเลิก
                            </a>
                            <button type="submit"
                                class="w-full sm:w-auto px-8 py-3 rounded-xl bg-black text-white font-bold text-sm hover:bg-gray-800 transition shadow-lg shadow-gray-200 flex items-center justify-center gap-2 transform active:scale-95">
                                <i data-lucide="plus-circle" class="w-4 h-4"></i> เพิ่มนักเตะ
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script>
        lucide.createIcons();

        // Show Toast Function
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
    </script>
</body>
</html>
