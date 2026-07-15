<?php
session_start();
include "../includes/db_config.php";

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (mb_strlen($username) < 3 || mb_strlen($username) > 50) {
        $error = "ชื่อผู้ใช้ต้องมีความยาว 3-50 ตัวอักษร";
    } elseif (strlen($password) < 8) {
        $error = "รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร";
    } elseif ($password !== $confirm_password) {
        $error = "รหัสผ่านไม่ตรงกัน";
    } else {
        try {
            $stmt = $conn->prepare("SELECT * FROM users WHERE username = :username");
            $stmt->execute(['username' => $username]);

            if ($stmt->rowCount() > 0) {
                $error = "ชื่อผู้ใช้นี้มีอยู่แล้ว";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (:username, :password)");
                $stmt->execute(['username' => $username, 'password' => $hashed_password]);

                $_SESSION['toast_message'] = [
                    'type' => 'success',
                    'message' => 'สมัครสมาชิกสำเร็จ! กรุณาเข้าสู่ระบบ'
                ];
                header("Location: login.php"); // Redirect to login.php on success
                exit();
            }
        } catch (PDOException $e) {
            error_log("Registration failed: " . $e->getMessage());
            $error = "เกิดข้อผิดพลาดในการสมัครสมาชิก กรุณาลองใหม่อีกครั้ง";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>สมัครสมาชิก | FMFC Manager</title>
    <link rel="icon" type="image/png" href="../img/logo/fm25_logo_2.png">
    <script src="https://cdn.tailwindcss.com"></script>
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
                <span class="font-bold text-xl">FMFC</span>
            </div>
            <h2 class="text-2xl font-bold tracking-tight text-gray-900">Create Account</h2>
            <p class="text-sm text-gray-500 mt-2">สมัครสมาชิกเพื่อเริ่มต้นจัดการทีมของคุณ</p>
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
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Username</label>
                <input type="text" name="username" required
                    class="w-full px-4 py-3 bg-gray-50 border-gray-200 border rounded-xl focus:bg-white focus:ring-2 focus:ring-black focus:border-transparent transition-all outline-none text-gray-900 placeholder-gray-400"
                    placeholder="ตั้งชื่อผู้ใช้">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Password</label>
                <input type="password" name="password" required
                    class="w-full px-4 py-3 bg-gray-50 border-gray-200 border rounded-xl focus:bg-white focus:ring-2 focus:ring-black focus:border-transparent transition-all outline-none text-gray-900 placeholder-gray-400"
                    placeholder="รหัสผ่าน">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Confirm Password</label>
                <input type="password" name="confirm_password" required
                    class="w-full px-4 py-3 bg-gray-50 border-gray-200 border rounded-xl focus:bg-white focus:ring-2 focus:ring-black focus:border-transparent transition-all outline-none text-gray-900 placeholder-gray-400"
                    placeholder="ยืนยันรหัสผ่านอีกครั้ง">
            </div>

            <button type="submit"
                class="w-full bg-black text-white py-3.5 rounded-xl font-bold hover:bg-gray-800 transition-all shadow-lg shadow-gray-200 transform active:scale-95 mt-2">
                สมัครสมาชิก
            </button>

            <div class="text-center mt-6">
                <p class="text-xs text-gray-500">
                    มีบัญชีอยู่แล้ว? 
                    <a href="login.php" class="text-black font-semibold hover:underline ml-1">เข้าสู่ระบบ</a>
                </p>
            </div>
        </form>
    </div>
</body>

</html>
