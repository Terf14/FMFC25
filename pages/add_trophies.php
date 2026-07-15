<?php
session_start();
include "../includes/db_config.php";
include "../includes/auth.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Define the trophy data structure
$leagues_and_trophies = [
    'england' => [
        'Premier League', 'FA Cup', 'EFL Cup (Carabao Cup)', 'Community Shield',
        'Championship', 'League One', 'League Two'
    ],
    'spain' => [
        'La Liga', 'Copa del Rey', 'Supercopa de España', 'Segunda División'
    ],
    'international_club' => [
        'UEFA Champions League (UCL)', 'UEFA Europa League (UEL)',
        'UEFA Europa Conference League (UECL)', 'UEFA Super Cup', 'FIFA Club World Cup'
    ],
    'international_national' => [
        'FIFA World Cup', 'UEFA European Championship (Euro)',
        'Copa América', 'Africa Cup of Nations'
    ]
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $season = $_POST['season'] ?? '';
    $description = $_POST['description'] ?? '';
    $played = $_POST['played'] ?? 0;
    $wins = $_POST['wins'] ?? 0;
    $draws = $_POST['draws'] ?? 0;
    $losses = $_POST['losses'] ?? 0;
    $goals_for = $_POST['goals_for'] ?? 0;
    $goals_against = $_POST['goals_against'] ?? 0;

    try {
        if ($conn instanceof PDO) {
            $query = "INSERT INTO team_trophies (user_id, title, season, description, played, wins, draws, losses, goals_for, goals_against)
                      VALUES (:user_id, :title, :season, :description, :played, :wins, :draws, :losses, :goals_for, :goals_against)";
            $stmt = $conn->prepare($query);
            $stmt->execute([
                ':user_id' => $user_id, ':title' => $title, ':season' => $season,
                ':description' => $description, ':played' => $played, ':wins' => $wins,
                ':draws' => $draws, ':losses' => $losses, ':goals_for' => $goals_for,
                ':goals_against' => $goals_against
            ]);
        } else {
            // MySQLi Fallback
            $query = "INSERT INTO team_trophies (user_id, title, season, description, played, wins, draws, losses, goals_for, goals_against)
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, 'issssiiiiii', $user_id, $title, $season, $description, $played, $wins, $draws, $losses, $goals_for, $goals_against);
            mysqli_stmt_execute($stmt);
        }

        $_SESSION['toast_message'] = ['type' => 'success', 'message' => 'เพิ่มถ้วยรางวัลสำเร็จ!'];
        header("Location: trophies.php");
        exit;
    } catch (PDOException $e) {
        $_SESSION['toast_message'] = ['type' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . htmlspecialchars($e->getMessage())];
        header("Location: add_trophies.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>เพิ่มความสำเร็จ | FMFC Manager</title>
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
            <div class="max-w-3xl mx-auto">
                
                <div class="mb-8 text-center">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4 text-yellow-600 shadow-sm border border-gray-200">
                        <i data-lucide="trophy" class="w-8 h-8 fill-current"></i>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900 tracking-tight">บันทึกความสำเร็จ</h1>
                    <p class="text-gray-500 mt-1 text-sm">เพิ่มรายการถ้วยรางวัลและสถิติประจำฤดูกาล</p>
                </div>

                <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100">
                    <form action="add_trophies.php" method="POST" class="space-y-8">
                        
                        <div class="space-y-6">
                            <h2 class="text-sm font-bold text-gray-900 uppercase tracking-widest flex items-center gap-2 border-b border-gray-100 pb-2">
                                <i data-lucide="info" class="w-4 h-4 text-gray-400"></i> ข้อมูลทั่วไป
                            </h2>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="league_select" class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">ประเภทรายการแข่งขัน</label>
                                    <select id="league_select" name="league_select" required
                                        class="w-full px-4 py-3 bg-gray-50 border-gray-200 border rounded-xl focus:bg-white focus:ring-2 focus:ring-black transition-all appearance-none cursor-pointer">
                                        <option value="">-- เลือกประเภท --</option>
                                        <option value="england">อังกฤษ</option>
                                        <option value="spain">สเปน</option>
                                        <option value="international_club">สโมสรนานาชาติ</option>
                                        <option value="international_national">ทีมชาตินานาชาติ</option>
                                    </select>
                                </div>

                                <div>
                                    <label for="title" class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">ชื่อถ้วยรางวัล</label>
                                    <select id="title" name="title" required disabled
                                        class="w-full px-4 py-3 bg-gray-50 border-gray-200 border rounded-xl focus:bg-white focus:ring-2 focus:ring-black transition-all appearance-none cursor-pointer disabled:bg-gray-100 disabled:text-gray-400">
                                        <option value="">-- เลือกรายการ --</option>
                                    </select>
                                </div>

                                <div>
                                    <label for="season" class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">ฤดูกาล (Season)</label>
                                    <input type="text" id="season" name="season" placeholder="เช่น 2024/25"
                                        class="w-full px-4 py-3 bg-gray-50 border-gray-200 border rounded-xl focus:bg-white focus:ring-2 focus:ring-black transition-all font-medium">
                                </div>
                            </div>

                            <div>
                                <label for="description" class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">บันทึกเพิ่มเติม (Optional)</label>
                                <textarea id="description" name="description" rows="3" placeholder="รายละเอียดแมตช์ชิงชนะเลิศ หรือเหตุการณ์สำคัญ..."
                                    class="w-full px-4 py-3 bg-gray-50 border-gray-200 border rounded-xl focus:bg-white focus:ring-2 focus:ring-black transition-all"></textarea>
                            </div>
                        </div>

                        <div class="space-y-6">
                            <h2 class="text-sm font-bold text-gray-900 uppercase tracking-widest flex items-center gap-2 border-b border-gray-100 pb-2">
                                <i data-lucide="bar-chart-2" class="w-4 h-4 text-gray-400"></i> สถิติฤดูกาล (Stats)
                            </h2>
                            
                            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-4">
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase text-center mb-1">แข่ง (P)</label>
                                    <input type="number" name="played" value="0" min="0" class="w-full px-2 py-2 text-center bg-gray-50 border border-gray-200 rounded-lg focus:ring-black focus:border-black font-semibold">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-green-600 uppercase text-center mb-1">ชนะ (W)</label>
                                    <input type="number" name="wins" value="0" min="0" class="w-full px-2 py-2 text-center bg-green-50 border border-green-100 rounded-lg focus:ring-green-500 focus:border-green-500 font-semibold text-green-700">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-500 uppercase text-center mb-1">เสมอ (D)</label>
                                    <input type="number" name="draws" value="0" min="0" class="w-full px-2 py-2 text-center bg-gray-50 border border-gray-200 rounded-lg focus:ring-gray-500 focus:border-gray-500 font-semibold text-gray-700">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-red-600 uppercase text-center mb-1">แพ้ (L)</label>
                                    <input type="number" name="losses" value="0" min="0" class="w-full px-2 py-2 text-center bg-red-50 border border-red-100 rounded-lg focus:ring-red-500 focus:border-red-500 font-semibold text-red-700">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-blue-600 uppercase text-center mb-1">ได้ (GF)</label>
                                    <input type="number" name="goals_for" value="0" min="0" class="w-full px-2 py-2 text-center bg-blue-50 border border-blue-100 rounded-lg focus:ring-blue-500 focus:border-blue-500 font-semibold text-blue-700">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-orange-600 uppercase text-center mb-1">เสีย (GA)</label>
                                    <input type="number" name="goals_against" value="0" min="0" class="w-full px-2 py-2 text-center bg-orange-50 border border-orange-100 rounded-lg focus:ring-orange-500 focus:border-orange-500 font-semibold text-orange-700">
                                </div>
                            </div>
                        </div>

                        <div class="pt-6 border-t border-gray-100 flex flex-col-reverse sm:flex-row items-center justify-between gap-4">
                            <a href="trophies.php" class="w-full sm:w-auto text-center px-6 py-3 rounded-xl border border-gray-200 text-gray-600 font-bold text-sm hover:bg-gray-50 hover:text-black transition">
                                ยกเลิก
                            </a>
                            <button type="submit"
                                class="w-full sm:w-auto px-8 py-3 rounded-xl bg-black text-white font-bold text-sm hover:bg-gray-800 transition shadow-lg shadow-gray-200 flex items-center justify-center gap-2 transform active:scale-95">
                                <i data-lucide="save" class="w-4 h-4"></i> บันทึกความสำเร็จ
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

        // Toast Helper
        function showToast(type, message) {
            let bg = type === 'success' ? "#18181b" : "#ef4444";
            Toastify({
                text: message, duration: 3000, close: true, gravity: "top", position: "right",
                style: { background: bg, borderRadius: "12px", boxShadow: "0 4px 12px rgba(0,0,0,0.1)", fontSize: "14px", padding: "12px 20px", fontFamily: "'Kanit', sans-serif" }
            }).showToast();
        }

        <?php if (isset($_SESSION['toast_message'])): ?>
            showToast('<?= $_SESSION['toast_message']['type'] ?>', '<?= $_SESSION['toast_message']['message'] ?>');
            <?php unset($_SESSION['toast_message']); ?>
        <?php endif; ?>

        // Dynamic Dropdown Logic
        const allTrophies = <?php echo json_encode($leagues_and_trophies); ?>;
        const leagueSelect = document.getElementById('league_select');
        const titleSelect = document.getElementById('title');

        leagueSelect.addEventListener('change', function() {
            const selectedLeague = this.value;
            titleSelect.innerHTML = '<option value="">-- เลือกรายการ --</option>';
            
            if (selectedLeague && allTrophies[selectedLeague]) {
                allTrophies[selectedLeague].forEach(trophy => {
                    const option = document.createElement('option');
                    option.value = trophy;
                    option.textContent = trophy;
                    titleSelect.appendChild(option);
                });
                titleSelect.disabled = false;
            } else {
                titleSelect.disabled = true;
            }
        });
    </script>
</body>
</html>