<?php
session_start();
include "../includes/db_config.php";
include "../includes/auth.php";

$user_id = $_SESSION['user_id'];

// Fetch current user data
$stmt = $conn->prepare("SELECT username, password FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Update Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_username = trim($_POST['username'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['password'] ?? '';

    try {
        if (mb_strlen($new_username) < 3 || mb_strlen($new_username) > 50) {
            $_SESSION['toast_message'] = [
                'type' => 'error',
                'message' => 'ชื่อผู้ใช้ต้องมีความยาว 3-50 ตัวอักษร'
            ];
            header("Location: settings.php");
            exit;
        }

        if (!empty($new_password) && strlen($new_password) < 8) {
            $_SESSION['toast_message'] = [
                'type' => 'error',
                'message' => 'รหัสผ่านใหม่ต้องมีอย่างน้อย 8 ตัวอักษร'
            ];
            header("Location: settings.php");
            exit;
        }

        if (!empty($new_password) && !password_verify($current_password, $user['password'])) {
            $_SESSION['toast_message'] = [
                'type' => 'error',
                'message' => 'รหัสผ่านปัจจุบันไม่ถูกต้อง'
            ];
            header("Location: settings.php");
            exit;
        }

        if ($new_username !== $user['username']) {
            $update_stmt = $conn->prepare("UPDATE users SET username = ? WHERE id = ?");
            $update_stmt->execute([$new_username, $user_id]);
            $_SESSION['username'] = $new_username; // Update session
        }

        if (!empty($new_password)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update_stmt->execute([$hashed_password, $user_id]);
        }

        $_SESSION['toast_message'] = [
            'type' => 'success',
            'message' => 'บันทึกการเปลี่ยนแปลงเรียบร้อยแล้ว!'
        ];
        header("Location: settings.php");
        exit;
    } catch (PDOException $e) {
        $_SESSION['toast_message'] = [
            'type' => 'error',
            'message' => 'เกิดข้อผิดพลาดในการบันทึกข้อมูล'
        ];
        error_log("Settings update failed: " . $e->getMessage());
        header("Location: settings.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>ตั้งค่า | FMFC Manager</title>
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
                
                <div class="mb-10">
                    <h1 class="text-3xl font-bold text-gray-900 tracking-tight flex items-center gap-3">
                        <i data-lucide="settings-2" class="w-8 h-8 text-black"></i>
                        ตั้งค่าบัญชี
                    </h1>
                    <p class="text-gray-500 mt-2 text-sm">จัดการข้อมูลส่วนตัวและความปลอดภัยของบัญชีของคุณ</p>
                </div>

                <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100">
                    <form method="POST" class="space-y-8">
                        
                        <div class="space-y-6">
                            <h2 class="text-sm font-bold text-gray-900 uppercase tracking-widest flex items-center gap-2 border-b border-gray-100 pb-2">
                                <i data-lucide="user" class="w-4 h-4 text-gray-400"></i> ข้อมูลทั่วไป
                            </h2>
                            
                            <div>
                                <label for="username" class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">ชื่อผู้ใช้ (Username)</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                        <i data-lucide="at-sign" class="w-5 h-5 text-gray-400"></i>
                                    </div>
                                    <input type="text" id="username" name="username" value="<?= htmlspecialchars($user['username']) ?>" required
                                        class="w-full pl-11 pr-4 py-3 bg-gray-50 border-gray-200 border rounded-xl focus:bg-white focus:ring-2 focus:ring-black focus:border-transparent transition-all font-medium text-gray-900">
                                </div>
                            </div>
                        </div>

                        <div class="space-y-6">
                            <h2 class="text-sm font-bold text-gray-900 uppercase tracking-widest flex items-center gap-2 border-b border-gray-100 pb-2">
                                <i data-lucide="shield-check" class="w-4 h-4 text-gray-400"></i> ความปลอดภัย
                            </h2>
                            
                            <div>
                                <label for="current_password" class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">รหัสผ่านปัจจุบัน (จำเป็นเมื่อเปลี่ยนรหัสผ่าน)</label>
                                <div class="relative mb-4">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                        <i data-lucide="key-round" class="w-5 h-5 text-gray-400"></i>
                                    </div>
                                    <input type="password" id="current_password" name="current_password" placeholder="รหัสผ่านปัจจุบัน"
                                        class="w-full pl-11 pr-4 py-3 bg-gray-50 border-gray-200 border rounded-xl focus:bg-white focus:ring-2 focus:ring-black focus:border-transparent transition-all font-medium text-gray-900 placeholder-gray-400">
                                </div>

                                <label for="password" class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">เปลี่ยนรหัสผ่านใหม่ (เว้นว่างถ้าไม่เปลี่ยน)</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                        <i data-lucide="lock" class="w-5 h-5 text-gray-400"></i>
                                    </div>
                                    <input type="password" id="password" name="password" placeholder="••••••••"
                                        class="w-full pl-11 pr-4 py-3 bg-gray-50 border-gray-200 border rounded-xl focus:bg-white focus:ring-2 focus:ring-black focus:border-transparent transition-all font-medium text-gray-900 placeholder-gray-400">
                                </div>
                                <p class="text-[10px] text-gray-400 mt-2 flex items-center gap-1">
                                    <i data-lucide="info" class="w-3 h-3"></i> รหัสผ่านที่ดีควรมีความยาวอย่างน้อย 8 ตัวอักษร
                                </p>
                            </div>
                        </div>

                        <div class="pt-6 border-t border-gray-100 flex flex-col-reverse sm:flex-row items-center justify-between gap-4">
                            <a href="dashboard.php" class="w-full sm:w-auto text-center px-6 py-3 rounded-xl border border-gray-200 text-gray-600 font-bold text-sm hover:bg-gray-50 hover:text-black transition">
                                ยกเลิก
                            </a>
                            <button type="submit"
                                class="w-full sm:w-auto px-8 py-3 rounded-xl bg-black text-white font-bold text-sm hover:bg-gray-800 transition shadow-lg shadow-gray-200 flex items-center justify-center gap-2 transform active:scale-95">
                                <i data-lucide="save" class="w-4 h-4"></i> บันทึกการเปลี่ยนแปลง
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
