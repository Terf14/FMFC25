<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: pages/login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="icon" type="image/png" href="img/logo/fm25_logo_2.png">
    <title>ยินดีต้อนรับ | FMFC Manager</title>
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

<body class="min-h-screen flex items-center justify-center bg-[#F9FAFB] text-gray-800 relative overflow-hidden">
    
    <div class="absolute top-0 left-0 w-full h-full overflow-hidden z-0 pointer-events-none">
        <div class="absolute -top-24 -right-24 w-96 h-96 bg-gray-100 rounded-full blur-3xl opacity-50"></div>
        <div class="absolute -bottom-24 -left-24 w-80 h-80 bg-gray-100 rounded-full blur-3xl opacity-50"></div>
    </div>

    <div class="bg-white border border-gray-100 rounded-[2rem] shadow-[0_20px_50px_-12px_rgba(0,0,0,0.08)] w-full max-w-md p-10 text-center relative z-10">
        
        <div class="flex justify-center mb-8">
            <div class="relative">
                <div class="w-24 h-24 bg-gray-50 rounded-full flex items-center justify-center border border-gray-100 shadow-sm">
                    <i data-lucide="user" class="w-10 h-10 text-gray-800"></i>
                </div>
                <div class="absolute bottom-0 right-0 bg-green-500 w-6 h-6 rounded-full border-4 border-white"></div>
            </div>
        </div>

        <h1 class="text-3xl font-bold tracking-tight text-gray-900 mb-2">
            สวัสดี, <span class="text-transparent bg-clip-text bg-gradient-to-br from-black to-gray-600"><?= htmlspecialchars($_SESSION['username']) ?></span>!
        </h1>
        <p class="text-gray-500 mb-10 text-sm font-medium">เข้าสู่ระบบจัดการข้อมูลสโมสรเรียบร้อยแล้ว</p>

        <div class="space-y-4">
            <a href="pages/dashboard.php" 
               class="group block w-full bg-black text-white py-4 rounded-2xl font-bold hover:bg-gray-900 transition-all shadow-xl shadow-gray-200 transform hover:-translate-y-1 flex items-center justify-center gap-3">
                <i data-lucide="layout-dashboard" class="w-5 h-5 group-hover:scale-110 transition-transform duration-200"></i>
                เข้าสู่แดชบอร์ด
            </a>

            <a href="pages/logout.php" 
               class="block w-full bg-white border border-gray-200 text-gray-600 py-3.5 rounded-2xl font-semibold hover:bg-red-50 hover:text-red-600 hover:border-red-100 transition-all flex items-center justify-center gap-2">
                <i data-lucide="log-out" class="w-4 h-4"></i>
                ออกจากระบบ
            </a>
        </div>

        <div class="mt-10 text-[10px] uppercase tracking-widest text-gray-300">
            FMFC Management System © 2025
        </div>
    </div>

    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script>
        lucide.createIcons();

        // Check for session toast message
        <?php if (isset($_SESSION['toast_message'])): ?>
            const msg = <?php echo json_encode($_SESSION['toast_message']); ?>;
            let bg = msg.type === 'success' ? "#18181b" : (msg.type === 'error' ? "#ef4444" : "#3f3f46");
            
            Toastify({
                text: msg.message,
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
            <?php unset($_SESSION['toast_message']); ?>
        <?php endif; ?>
    </script>
</body>

</html>