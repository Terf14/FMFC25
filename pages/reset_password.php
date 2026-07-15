<?php
session_start();
include "../includes/db_config.php"; // เชื่อมต่อฐานข้อมูล

$error = '';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['toast_message'] = [
        'type' => 'error',
        'message' => 'กรุณาเข้าสู่ระบบก่อนเปลี่ยนรหัสผ่าน'
    ];
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (strlen($new_password) < 8) {
        $error = "รหัสผ่านใหม่ต้องมีอย่างน้อย 8 ตัวอักษร";
    } elseif ($new_password !== $confirm_password) {
        $error = "รหัสผ่านไม่ตรงกัน";
    } else {
        try {
            $stmt = $conn->prepare("SELECT id, password FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($current_password, $user['password'])) {
                $error = 'รหัสผ่านปัจจุบันไม่ถูกต้อง';
            } else {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $update->execute([$hashed_password, $user['id']]);

                $_SESSION['toast_message'] = [
                    'type' => 'success',
                    'message' => 'รีเซ็ตรหัสผ่านเรียบร้อยแล้ว'
                ];
                header("Location: login.php"); // Redirect to login on success
                exit();
            }
        } catch (PDOException $e) {
            error_log("Password reset failed: " . $e->getMessage());
            $error = 'เกิดข้อผิดพลาดในการดำเนินการ กรุณาลองใหม่อีกครั้ง';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รีเซ็ตรหัสผ่าน | FMFC Manager</title>
    <link rel="icon" type="image/png" href="../img/logo/fm25_logo_2.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Itim&family=Kanit:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <style>
        body {
            font-family: 'Kanit', sans-serif;
        }
    </style>
</head>

<body class="min-h-screen flex items-center justify-center bg-[#F9FAFB] text-gray-800">
    <div class="bg-white border border-gray-100 rounded-3xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] w-full max-w-sm p-10 relative overflow-hidden">
        
        <div class="text-center mb-8 relative z-10">
            <div class="w-16 h-16 bg-black text-white rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg shadow-gray-200">
                <i data-lucide="key-round" class="w-8 h-8"></i>
            </div>
            <h2 class="text-2xl font-bold tracking-tight text-gray-900">Change Password</h2>
            <p class="text-sm text-gray-500 mt-2">เปลี่ยนรหัสผ่านของบัญชีที่กำลังใช้งาน</p>
        </div>

        <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
        <script>
            // Function to show toast (Reusable)
            function showToast(type, message) {
                let bg = type === 'success' ? "#18181b" : "#ef4444"; // Black for success, Red for error
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
            lucide.createIcons();
        </script>

        <?php if (!empty($error)): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    showToast('error', '<?= htmlspecialchars($error) ?>');
                });
            </script>
        <?php endif; ?>

        <form method="POST" class="space-y-5 relative z-10">
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2" for="current_password">Current Password</label>
                <input type="password" id="current_password" name="current_password" required
                    class="w-full px-4 py-3 bg-gray-50 border-gray-200 border rounded-xl focus:bg-white focus:ring-2 focus:ring-black focus:border-transparent transition-all outline-none text-gray-900 placeholder-gray-400"
                    placeholder="รหัสผ่านปัจจุบัน">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2" for="new_password">New Password</label>
                <input type="password" id="new_password" name="new_password" required
                    class="w-full px-4 py-3 bg-gray-50 border-gray-200 border rounded-xl focus:bg-white focus:ring-2 focus:ring-black focus:border-transparent transition-all outline-none text-gray-900 placeholder-gray-400"
                    placeholder="รหัสผ่านใหม่">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2" for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required
                    class="w-full px-4 py-3 bg-gray-50 border-gray-200 border rounded-xl focus:bg-white focus:ring-2 focus:ring-black focus:border-transparent transition-all outline-none text-gray-900 placeholder-gray-400"
                    placeholder="ยืนยันรหัสผ่านใหม่">
            </div>

            <button type="submit"
                class="w-full bg-black text-white py-3.5 rounded-xl font-bold hover:bg-gray-800 transition-all shadow-lg shadow-gray-200 transform active:scale-95 mt-2">
                เปลี่ยนรหัสผ่าน
            </button>

            <div class="text-center mt-6">
                <a href="settings.php" class="text-sm text-gray-500 hover:text-black font-medium transition-colors flex items-center justify-center gap-1">
                    <i data-lucide="arrow-left" class="w-3 h-3"></i> กลับไปหน้าตั้งค่า
                </a>
            </div>
        </form>
    </div>
</body>

</html>
