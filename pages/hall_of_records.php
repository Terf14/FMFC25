<?php
session_start();
include "../includes/db_config.php";
include "../includes/auth.php";

$user_id = $_SESSION['user_id'];

// --- ส่วนที่ 1: เพิ่มสถิติพิเศษ (Manual Record) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_record'])) {
    $title = $_POST['title'];
    $player_name = $_POST['player_name'];
    $record_value = $_POST['record_value'];
    $description = $_POST['description'];

    try {
        $stmt = $conn->prepare("INSERT INTO team_records (user_id, title, player_name, record_value, description) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $title, $player_name, $record_value, $description]);
        $_SESSION['toast_message'] = ['type' => 'success', 'message' => 'เพิ่มสถิติใหม่เรียบร้อย!'];
        header("Location: hall_of_records.php");
        exit;
    } catch (PDOException $e) { /* Handle Error */ }
}

// --- ส่วนที่ 2: ลบสถิติพิเศษ ---
if (isset($_GET['delete_id'])) {
    $del_id = $_GET['delete_id'];
    $stmt = $conn->prepare("DELETE FROM team_records WHERE record_id = ? AND user_id = ?");
    $stmt->execute([$del_id, $user_id]);
    $_SESSION['toast_message'] = ['type' => 'success', 'message' => 'ลบสถิติเรียบร้อย'];
    header("Location: hall_of_records.php");
    exit;
}

// --- ส่วนที่ 3: ดึงข้อมูล ---

// 3.1 สถิติอัตโนมัติจาก player_total_stats (รวมทุกคนทั้งอดีตและปัจจุบัน)
$auto_stats = [];
$stats_types = [
    'goals' => ['label' => 'ดาวซัลโวสูงสุด', 'icon' => 'goal', 'unit' => 'ประตู'],
    'assists' => ['label' => 'จอมแอสซิสต์', 'icon' => 'footprints', 'unit' => 'ครั้ง'],
    'appearances' => ['label' => 'ลงเล่นมากที่สุด', 'icon' => 'shirt', 'unit' => 'นัด'],
    'clean_sheets' => ['label' => 'คลีนชีทสูงสุด', 'icon' => 'shield-check', 'unit' => 'นัด'],
    'yellow_cards' => ['label' => 'จอมโหด (ใบเหลือง)', 'icon' => 'rectangle-vertical', 'unit' => 'ใบ', 'color' => 'text-yellow-600'],
    'rating' => ['label' => 'Rating เฉลี่ยสูงสุด', 'icon' => 'star', 'unit' => 'คะแนน']
];

foreach ($stats_types as $col => $info) {
    // ดึงคนที่ค่ามากที่สุดในแต่ละด้าน
    $stmt = $conn->prepare("SELECT name, $col as value, position FROM player_total_stats WHERE user_id = ? AND $col > 0 ORDER BY $col DESC LIMIT 1");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result) {
        $auto_stats[] = array_merge($info, $result);
    }
}

// 3.2 สถิติพิเศษจาก team_records
$custom_records = [];
$stmt = $conn->prepare("SELECT * FROM team_records WHERE user_id = ? ORDER BY record_id DESC");
$stmt->execute([$user_id]);
$custom_records = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>หอเกียรติยศสถิติ | FMFC Manager</title>
    <link rel="icon" type="image/png" href="../img/logo/fm25_logo_2.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <style> body { font-family: 'Kanit', sans-serif; } .modal { transition: opacity 0.25s ease; } body.modal-active { overflow-x: hidden; overflow-y: hidden !important; } </style>
</head>
<body class="bg-[#F9FAFB] text-gray-800">
    <div class="flex h-screen overflow-hidden">
        <?php include '../includes/navbar.php'; ?>

        <main class="flex-1 px-8 py-10 overflow-y-auto">
            
            <div class="flex justify-between items-center mb-10">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 flex items-center gap-3">
                        <div class="bg-black text-white p-2 rounded-xl shadow-lg">
                            <i data-lucide="medal" class="w-6 h-6"></i>
                        </div>
                        Club Records
                    </h1>
                    <p class="text-gray-500 mt-2 text-sm">ที่สุดของสถิติสโมสร ทั้งแบบอัตโนมัติและบันทึกพิเศษ</p>
                </div>
                <button onclick="toggleModal('recordModal')" class="bg-black text-white px-6 py-3 rounded-xl hover:bg-gray-800 transition shadow-lg flex items-center gap-2 font-medium">
                    <i data-lucide="plus-circle" class="w-5 h-5"></i> เพิ่มสถิติพิเศษ
                </button>
            </div>

            <div class="mb-12">
                <h2 class="text-lg font-bold text-gray-900 mb-6 flex items-center gap-2">
                    <i data-lucide="bar-chart-big" class="w-5 h-5 text-gray-400"></i> สถิติตลอดกาล (อัตโนมัติ)
                </h2>
                
                <?php if (!empty($auto_stats)): ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach ($auto_stats as $stat): ?>
                        <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm hover:shadow-md transition-all group relative overflow-hidden">
                            <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                                <i data-lucide="<?= $stat['icon'] ?>" class="w-24 h-24 text-gray-900"></i>
                            </div>
                            
                            <div class="relative z-10">
                                <div class="flex items-center gap-2 mb-4">
                                    <span class="p-2 bg-gray-50 rounded-lg text-gray-600">
                                        <i data-lucide="<?= $stat['icon'] ?>" class="w-5 h-5"></i>
                                    </span>
                                    <span class="text-xs font-bold text-gray-400 uppercase tracking-widest"><?= $stat['label'] ?></span>
                                </div>
                                
                                <h3 class="text-xl font-bold text-gray-900 truncate"><?= htmlspecialchars($stat['name']) ?></h3>
                                <p class="text-sm text-gray-500 mb-4 uppercase tracking-wide"><?= $stat['position'] ?></p>
                                
                                <div class="flex items-baseline gap-2">
                                    <span class="text-4xl font-black <?= $stat['color'] ?? 'text-black' ?>">
                                        <?= is_numeric($stat['value']) ? number_format($stat['value']) : $stat['value'] ?>
                                    </span>
                                    <span class="text-sm font-medium text-gray-400"><?= $stat['unit'] ?></span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="bg-gray-50 rounded-2xl p-8 text-center text-gray-400">
                        ยังไม่มีข้อมูลสถิติรวม (ต้องกด "รวมสถิติ" ในหน้า Total Stats ก่อน)
                    </div>
                <?php endif; ?>
            </div>

            <div>
                <h2 class="text-lg font-bold text-gray-900 mb-6 flex items-center gap-2">
                    <i data-lucide="bookmark-plus" class="w-5 h-5 text-gray-400"></i> สถิติพิเศษ (บันทึกเอง)
                </h2>

                <?php if (!empty($custom_records)): ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <?php foreach ($custom_records as $record): ?>
                        <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm flex items-start justify-between group">
                            <div>
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="bg-yellow-100 text-yellow-700 text-[10px] font-bold px-2 py-1 rounded uppercase tracking-wider">New Record</span>
                                    <h3 class="text-lg font-bold text-gray-900"><?= htmlspecialchars($record['title']) ?></h3>
                                </div>
                                
                                <div class="mb-3">
                                    <p class="text-2xl font-black text-gray-900"><?= htmlspecialchars($record['record_value']) ?></p>
                                    <p class="text-sm font-medium text-gray-600">โดย: <?= htmlspecialchars($record['player_name']) ?></p>
                                </div>
                                
                                <?php if($record['description']): ?>
                                    <p class="text-xs text-gray-400 leading-relaxed bg-gray-50 p-2 rounded-lg inline-block">
                                        <?= htmlspecialchars($record['description']) ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                            
                            <a href="?delete_id=<?= $record['record_id'] ?>" onclick="return confirm('ลบสถิตินี้?')" 
                               class="text-gray-300 hover:text-red-500 transition p-2">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="border-2 border-dashed border-gray-200 rounded-2xl p-10 text-center">
                        <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4 text-gray-400">
                            <i data-lucide="pen-tool" class="w-8 h-8"></i>
                        </div>
                        <p class="text-gray-500 text-sm">ยังไม่มีสถิติพิเศษที่บันทึกไว้</p>
                        <button onclick="toggleModal('recordModal')" class="text-black font-bold text-sm mt-2 hover:underline">เพิ่มรายการแรก</button>
                    </div>
                <?php endif; ?>
            </div>

        </main>
    </div>

    <div id="recordModal" class="modal opacity-0 pointer-events-none fixed w-full h-full top-0 left-0 flex items-center justify-center z-50">
        <div class="modal-overlay absolute w-full h-full bg-black opacity-50"></div>
        <div class="modal-container bg-white w-11/12 md:max-w-md mx-auto rounded-3xl shadow-2xl z-50 overflow-y-auto transform scale-95 transition-transform duration-300" id="modalContent">
            <div class="modal-content py-6 text-left px-6">
                <div class="flex justify-between items-center pb-3 mb-4">
                    <p class="text-xl font-bold text-gray-900">เพิ่มสถิติพิเศษ</p>
                    <div class="modal-close cursor-pointer z-50" onclick="toggleModal('recordModal')">
                        <i data-lucide="x" class="w-6 h-6 text-gray-500"></i>
                    </div>
                </div>

                <form method="POST">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">หัวข้อสถิติ</label>
                            <input type="text" name="title" required placeholder="เช่น ยิงประตูเร็วที่สุด, ผู้รักษาประตูยิงเยอะสุด" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-black outline-none text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">ชื่อนักเตะเจ้าของสถิติ</label>
                            <input type="text" name="player_name" required placeholder="ระบุชื่อนักเตะ" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-black outline-none text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">ค่าสถิติที่ทำได้</label>
                            <input type="text" name="record_value" required placeholder="เช่น 12 วินาที, 5 ประตู" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-black outline-none text-sm font-bold">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">รายละเอียดเพิ่มเติม (Optional)</label>
                            <textarea name="description" rows="2" placeholder="เช่น ในแมตช์พบกับ..., ฤดูกาล 2024/25" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-black outline-none text-sm"></textarea>
                        </div>
                    </div>
                    <div class="mt-6">
                        <button type="submit" name="add_record" class="w-full bg-black text-white py-3 rounded-xl font-bold hover:bg-gray-800 transition shadow-lg">บันทึกสถิติ</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script>
        lucide.createIcons();

        function toggleModal(modalID) {
            const modal = document.getElementById(modalID);
            const content = document.getElementById('modalContent');
            
            modal.classList.toggle('opacity-0');
            modal.classList.toggle('pointer-events-none');
            document.body.classList.toggle('modal-active');

            if (!modal.classList.contains('opacity-0')) {
                content.classList.remove('scale-95');
                content.classList.add('scale-100');
            } else {
                content.classList.remove('scale-100');
                content.classList.add('scale-95');
            }
        }

        <?php if (isset($_SESSION['toast_message'])): ?>
            Toastify({
                text: "<?= $_SESSION['toast_message']['message'] ?>",
                duration: 3000,
                close: true,
                gravity: "top",
                position: "right",
                style: { background: "#18181b", borderRadius: "12px" }
            }).showToast();
            <?php unset($_SESSION['toast_message']); ?>
        <?php endif; ?>
    </script>
</body>
</html>