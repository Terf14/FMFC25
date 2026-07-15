<?php
session_start();
include "../includes/db_config.php";

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    try {
        $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username = :username");
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            // For successful login, redirect to index.php
            $_SESSION['toast_message'] = [
                'type' => 'success',
                'message' => 'เข้าสู่ระบบสำเร็จ!'
            ];
            header("Location: ../index.php"); // Redirect to index.php on success
            exit();
        } else {
            $error = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
        }
    } catch (PDOException $e) {
        error_log("Login failed: " . $e->getMessage());
        $error = "เกิดข้อผิดพลาดในการเข้าสู่ระบบ กรุณาลองใหม่อีกครั้ง";
    }
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>เข้าสู่ระบบ | FMFC Manager</title>
    <link rel="icon" type="image/png" href="../img/logo/fm25_logo_2.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Itim&family=Kanit:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Kanit', sans-serif;
        }
    </style>
</head>

<body class="min-h-screen flex items-center justify-center bg-[#F9FAFB] text-gray-800">
    <div class="bg-white border border-gray-100 rounded-3xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] w-full max-w-sm p-10">
        
        <div class="text-center mb-8">
            <div class="w-16 h-16 bg-black text-white rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg shadow-gray-200">
                <span class="font-bold text-xl">FMFC</span>
            </div>
            <h2 class="text-2xl font-bold tracking-tight text-gray-900">Welcome Back</h2>
            <p class="text-sm text-gray-500 mt-2">เข้าสู่ระบบเพื่อจัดการทีมของคุณ</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="mb-5 rounded-xl border border-red-100 bg-red-50 px-4 py-3 text-sm font-medium text-red-600">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-5">
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Username</label>
                <input type="text" name="username" required
                    class="w-full px-4 py-3 bg-gray-50 border-gray-200 border rounded-xl focus:bg-white focus:ring-2 focus:ring-black focus:border-transparent transition-all outline-none">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Password</label>
                <input type="password" name="password" required
                    class="w-full px-4 py-3 bg-gray-50 border-gray-200 border rounded-xl focus:bg-white focus:ring-2 focus:ring-black focus:border-transparent transition-all outline-none">
            </div>

            <button type="submit"
                class="w-full bg-black text-white py-3.5 rounded-xl font-bold hover:bg-gray-800 transition-all shadow-lg shadow-gray-200 transform active:scale-95">
                เข้าสู่ระบบ
            </button>

            <div class="flex items-center justify-between mt-6 text-xs text-gray-500">
                <a href="register.php" class="hover:text-black font-medium">สมัครสมาชิกใหม่</a>
                <span class="text-gray-300">ติดต่อผู้ดูแลหากลืมรหัสผ่าน</span>
            </div>
        </form>
    </div>
</body>

</html>
