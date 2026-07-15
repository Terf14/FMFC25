<?php
session_start();
include "../includes/db_config.php";
include "../includes/auth.php";

$user_id = $_SESSION['user_id'];

// Fetch Total Trophies Count
$total_trophies = 0;
try {
    $sql_count = "SELECT COUNT(*) FROM team_trophies WHERE user_id = :user_id";
    $stmt_count = $conn->prepare($sql_count);
    $stmt_count->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_count->execute();
    $total_trophies = $stmt_count->fetchColumn();
} catch (PDOException $e) {
    error_log("Error: " . $e->getMessage());
}

// Fetch All Trophies
$trophies = [];
try {
    $sql = "SELECT * FROM team_trophies WHERE user_id = :user_id ORDER BY trophy_id DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $trophies = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Error handled in UI
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>ความสำเร็จ | FMFC Manager</title>
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
            
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-10">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 tracking-tight flex items-center gap-3">
                        <i data-lucide="trophy" class="w-8 h-8 text-yellow-500 fill-current"></i>
                        Hall of Fame
                    </h1>
                    <p class="text-gray-500 mt-1 text-sm">บันทึกเกียรติยศและความสำเร็จของสโมสร</p>
                </div>
                <a href="add_trophies.php" 
                   class="bg-black text-white px-6 py-3 rounded-xl hover:bg-gray-800 transition shadow-lg shadow-gray-200 flex items-center gap-2 font-medium group">
                    <i data-lucide="plus" class="w-5 h-5 group-hover:rotate-90 transition-transform"></i> 
                    เพิ่มความสำเร็จ
                </a>
            </div>

            <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 mb-10 flex items-center gap-5 max-w-sm">
                <div class="w-14 h-14 bg-yellow-50 rounded-2xl flex items-center justify-center border border-yellow-100">
                    <i data-lucide="award" class="w-8 h-8 text-yellow-600"></i>
                </div>
                <div>
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">ถ้วยรางวัลทั้งหมด</p>
                    <h2 class="text-3xl font-black text-gray-900 leading-none mt-1"><?= $total_trophies ?> <span class="text-base font-medium text-gray-400">ใบ</span></h2>
                </div>
            </div>

            <?php if (!empty($trophies)): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($trophies as $trophy): ?>
                        <div class="bg-white rounded-3xl border border-gray-100 shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-300 overflow-hidden flex flex-col h-full group">
                            
                            <div class="p-6 pb-4 border-b border-gray-50">
                                <div class="flex justify-between items-start mb-2">
                                    <div class="w-10 h-10 rounded-full bg-gray-50 flex items-center justify-center text-gray-400 group-hover:bg-yellow-50 group-hover:text-yellow-500 transition-colors">
                                        <i data-lucide="crown" class="w-5 h-5 fill-current"></i>
                                    </div>
                                    <?php if (!empty($trophy['season'])): ?>
                                        <span class="px-3 py-1 rounded-full bg-black text-white text-[10px] font-bold tracking-wider">
                                            <?= htmlspecialchars($trophy['season']) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <h3 class="text-lg font-bold text-gray-900 line-clamp-2 leading-tight">
                                    <?= htmlspecialchars($trophy['title']) ?>
                                </h3>
                            </div>

                            <div class="p-6 py-4 flex-1">
                                <div class="grid grid-cols-3 gap-2 text-center mb-4">
                                    <div class="bg-gray-50 rounded-lg p-2">
                                        <span class="block text-[10px] text-gray-400 font-bold uppercase">Played</span>
                                        <span class="block text-sm font-bold text-gray-800"><?= $trophy['played'] ?></span>
                                    </div>
                                    <div class="bg-green-50 rounded-lg p-2">
                                        <span class="block text-[10px] text-green-600 font-bold uppercase">Won</span>
                                        <span class="block text-sm font-bold text-green-700"><?= $trophy['wins'] ?></span>
                                    </div>
                                    <div class="bg-red-50 rounded-lg p-2">
                                        <span class="block text-[10px] text-red-600 font-bold uppercase">Lost</span>
                                        <span class="block text-sm font-bold text-red-700"><?= $trophy['losses'] ?></span>
                                    </div>
                                </div>
                                
                                <div class="flex items-center justify-between text-xs text-gray-500 px-1">
                                    <span class="flex items-center gap-1"><i data-lucide="minus-circle" class="w-3 h-3"></i> เสมอ: <b class="text-gray-700"><?= $trophy['draws'] ?></b></span>
                                    <span class="flex items-center gap-1"><i data-lucide="goal" class="w-3 h-3"></i> ได้/เสีย: <b class="text-gray-700"><?= $trophy['goals_for'] ?>/<?= $trophy['goals_against'] ?></b></span>
                                </div>

                                <?php if (!empty($trophy['description'])): ?>
                                    <div class="mt-4 pt-4 border-t border-gray-50 text-sm text-gray-500 leading-relaxed italic">
                                        "<?= nl2br(htmlspecialchars($trophy['description'])) ?>"
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="flex flex-col items-center justify-center py-20 bg-white border border-gray-100 rounded-3xl shadow-sm text-center">
                    <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                        <i data-lucide="award" class="w-10 h-10 text-gray-300"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900">ยังไม่มีรายการความสำเร็จ</h3>
                    <p class="text-gray-500 text-sm mt-1 mb-6">เริ่มต้นบันทึกประวัติศาสตร์สโมสรของคุณได้เลย</p>
                    <a href="add_trophies.php" class="text-black font-semibold hover:underline text-sm">
                        + เพิ่มถ้วยรางวัลแรก
                    </a>
                </div>
            <?php endif; ?>

        </main>
    </div>

    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script>
        lucide.createIcons();

        // Handle Session Toast
        <?php if (isset($_SESSION['toast_message'])): ?>
            const msg = <?php echo json_encode($_SESSION['toast_message']); ?>;
            let bg = msg.type === 'success' ? "#18181b" : "#ef4444";
            
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