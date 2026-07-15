<?php
session_start();
include "../includes/db_config.php";
include "../includes/auth.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$players = [];
try {
    $stmt = $conn->prepare("
        SELECT * FROM Players 
        WHERE user_id = ? AND status != 'on_loan' 
        ORDER BY FIELD(COALESCE(role, 'prospect'), 'crucial', 'important', 'rotation', 'sporadic', 'prospect'), name ASC
    ");
    $stmt->execute([$user_id]);
    $players = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { /* Handle Error */ }

// --- Smart Algorithm (No Age Version) ---
function analyzePlayer($p) {
    $score = 0;
    $reasons = [];
    $action = 'keep'; // default
    $css = 'bg-gray-100 text-gray-600 border-gray-200';
    $label = 'ทั่วไป';

    // 1. Role Analysis (แทนที่อายุ)
    // ให้คะแนนบทบาทสำคัญ และให้โอกาสดาวรุ่ง
    if ($p['role'] == 'crucial') {
        $score += 15;
    } elseif ($p['role'] == 'important') {
        $score += 10;
    } elseif ($p['role'] == 'prospect') {
        $score += 5; // ให้คะแนนช่วยนิดหน่อยเพราะเป็นเด็กปั้น
        $reasons[] = "ดาวรุ่งรอโอกาส";
    }

    // 2. Form Trend Analysis (แนวโน้มฟอร์ม)
    if ($p['score_change'] == 'increase') {
        $boost = min($p['change_count'] * 2, 20); 
        $score += $boost;
        $reasons[] = "ฟอร์มกำลังพุ่งแรง (+$boost)";
    } elseif ($p['score_change'] == 'decrease') {
        $penalty = min($p['change_count'] * 2, 20);
        $score -= $penalty;
        $reasons[] = "ฟอร์มตกต่อเนื่อง (-$penalty)";
    }

    // 3. Performance Hard Data (จาก Rating)
    if ($p['rating'] >= 7.5) {
        $score += 20;
        $reasons[] = "Rating สูงระดับท็อป";
    } elseif ($p['rating'] < 6.0 && $p['appearances'] > 5) {
        $score -= 20;
        $reasons[] = "ผลงานต่ำกว่ามาตรฐาน";
    }

    // 4. Availability (การบาดเจ็บ)
    if ($p['injured_count'] > 90) {
        $score -= 15;
        $reasons[] = "ประวัติบาดเจ็บสูง";
    }

    // --- Decision Logic ---
    if ($score >= 25) {
        $action = 'keep';
        $label = '💎 แกนหลักห้ามขาย';
        $css = 'bg-green-50 text-green-700 border-green-200';
    } elseif ($score >= 10) {
        $action = 'keep';
        $label = '✅ เก็บไว้ใช้งาน';
        $css = 'bg-blue-50 text-blue-700 border-blue-200';
    } elseif ($score > -10) {
        // ถ้าเป็นดาวรุ่ง (Prospect) และลงเล่นน้อย -> แนะนำปล่อยยืม
        if ($p['role'] == 'prospect' && $p['appearances'] < 10) {
            $action = 'loan';
            $label = '🚀 ปล่อยยืมเก็บเวล';
            $css = 'bg-yellow-50 text-yellow-700 border-yellow-200';
        } else {
            $label = '🔄 ตัวหมุนเวียน';
        }
    } else {
        $action = 'sell';
        $label = '💰 ควรขายทำกำไร';
        $css = 'bg-red-50 text-red-700 border-red-200';
    }

    return [
        'label' => $label,
        'css' => $css,
        'score' => $score,
        'reason' => empty($reasons) ? "ฟอร์มทรงตัว" : $reasons[0] 
    ];
}

// Group Players
$grouped_players = ['forwards' => [], 'midfielders' => [], 'defenders' => [], 'goalkeepers' => []];
foreach ($players as $player) {
    $analysis = analyzePlayer($player);
    $player['analysis'] = $analysis;
    
    $pos = strtolower($player['position']);
    if (in_array($pos, ['st', 'cf', 'lw', 'rw'])) $grouped_players['forwards'][] = $player;
    elseif (in_array($pos, ['lm', 'rm', 'cam', 'cm', 'cdm'])) $grouped_players['midfielders'][] = $player;
    elseif (in_array($pos, ['cb', 'lb', 'rb'])) $grouped_players['defenders'][] = $player;
    elseif ($pos == 'gk') $grouped_players['goalkeepers'][] = $player;
}

// Helper Initials
if (!function_exists('getInitials')) {
    function getInitials($name) {
        $words = explode(' ', $name);
        $initials = '';
        foreach ($words as $word) if (!empty($word)) $initials .= strtoupper(substr($word, 0, 1));
        return substr($initials, 0, 2);
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Analysis | FMFC Manager</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Kanit', sans-serif; } </style>
</head>
<body class="bg-[#F9FAFB] text-gray-800">
    <div class="flex h-screen overflow-hidden">
        <?php include '../includes/navbar.php'; ?>

        <main class="flex-1 overflow-y-auto px-8 py-10">
            <div class="flex justify-between items-end mb-10">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 flex items-center gap-3">
                        <i data-lucide="brain-circuit" class="w-8 h-8 text-black"></i> Smart Analysis
                    </h1>
                    <p class="text-gray-500 mt-2 text-sm">วิเคราะห์ข้อมูลเชิงลึกด้วยอัลกอริทึม (Internal Logic)</p>
                </div>
                <a href="ai_assistant.php" class="group flex items-center gap-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white px-6 py-3 rounded-2xl shadow-lg hover:shadow-xl transition-all hover:-translate-y-1">
                    <i data-lucide="sparkles" class="w-5 h-5 animate-pulse"></i>
                    <div class="text-left">
                        <p class="text-[10px] font-medium opacity-80 uppercase tracking-wider">Need more details?</p>
                        <p class="text-sm font-bold">ถาม AI Assistant (Gemini)</p>
                    </div>
                </a>
            </div>

            <div class="space-y-10">
                <?php
                $headers = [
                    'forwards' => ['กองหน้า', 'target'], 'midfielders' => ['กองกลาง', 'activity'],
                    'defenders' => ['กองหลัง', 'shield'], 'goalkeepers' => ['ผู้รักษาประตู', 'hand']
                ];
                foreach ($grouped_players as $key => $group): if(empty($group)) continue;
                ?>
                <div class="bg-white p-6 rounded-[2rem] border border-gray-100 shadow-sm">
                    <h2 class="text-lg font-bold mb-6 flex items-center gap-2 text-gray-400 uppercase tracking-widest">
                        <i data-lucide="<?= $headers[$key][1] ?>" class="w-5 h-5"></i> <?= $headers[$key][0] ?>
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                        <?php foreach ($group as $p): ?>
                        <div class="flex items-center gap-4 p-4 rounded-2xl border border-gray-50 bg-gray-50/50 hover:bg-white hover:border-gray-200 hover:shadow-md transition-all group">
                            <div class="w-12 h-12 rounded-full bg-white border border-gray-100 flex items-center justify-center font-bold text-sm text-gray-400 group-hover:bg-black group-hover:text-white transition-colors">
                                <?= getInitials($p['name']) ?>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h3 class="font-bold text-gray-900 truncate"><?= $p['name'] ?></h3>
                                        <p class="text-xs text-gray-400 flex items-center gap-2 uppercase">
                                            <?= $p['position'] ?> • Rating: <?= $p['rating'] ?>
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <span class="inline-block px-2 py-1 rounded-lg text-[10px] font-bold border <?= $p['analysis']['css'] ?>">
                                            <?= $p['analysis']['label'] ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="mt-2 text-xs text-gray-500 flex items-center gap-1">
                                    <i data-lucide="info" class="w-3 h-3"></i>
                                    <?= $p['analysis']['reason'] ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>
    <script>lucide.createIcons();</script>
</body>
</html>