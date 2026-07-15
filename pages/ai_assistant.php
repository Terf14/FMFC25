<?php
session_start();
include "../includes/db_config.php";
include "../includes/auth.php";

$user_id = $_SESSION['user_id'];
$response_text = "";
$error_msg = "";
$debug_info = "";

// --- 1. เตรียมข้อมูลทีม (Data Context) ---
$team_data_json = "";
try {
    $stmt = $conn->prepare("SELECT name, position, rating, performance_score, status, role FROM Players WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $players = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
$stmt_stats = $conn->prepare("SELECT SUM(goals_for) as total_goals, SUM(wins) as total_wins FROM team_trophies WHERE user_id = ?");    $stmt_stats->execute([$user_id]);
    $team_stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);

    $data_packet = [
        "team_summary" => $team_stats,
        "total_players" => count($players),
        "players" => $players
    ];
    $team_data_json = json_encode($data_packet, JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) { $team_data_json = "Error fetching data"; }

// --- 2. ฟังก์ชันค้นหา Model แบบดึงจากรายการจริง (Real-time List) ---
function getWorkingModel($apiKey) {
    $url = "https://generativelanguage.googleapis.com/v1beta/models?key=$apiKey";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($httpCode !== 200) {
        return ["error" => "Connection Error ($httpCode): " . ($curlError ?: $response)];
    }
    
    $data = json_decode($response, true);
    if (!isset($data['models'])) {
        return ["error" => "No models found in response."];
    }

    // สูตรการเลือก: หา Flash ก่อน -> หา Pro -> เอาตัวแรกสุดที่ใช้ได้
    $candidates = [];
    foreach ($data['models'] as $model) {
        if (isset($model['supportedGenerationMethods']) && in_array("generateContent", $model['supportedGenerationMethods'])) {
            $candidates[] = $model['name'];
        }
    }

    // 1. ลองหา Flash
    foreach ($candidates as $name) {
        if (stripos($name, 'flash') !== false) return ["success" => $name];
    }
    
    // 2. ลองหา Pro
    foreach ($candidates as $name) {
        if (stripos($name, 'pro') !== false) return ["success" => $name];
    }

    // 3. เอาตัวแรกที่เจอ (กันตาย)
    if (count($candidates) > 0) {
        return ["success" => $candidates[0]];
    }

    return ["error" => "No suitable model found for generateContent."];
}

// --- 3. เริ่มการทำงาน ---
$user_question = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['question']) && !empty($_POST['question'])) {
    $user_question = trim($_POST['question']);
}

// --- API KEY ---
$apiKey = getenv('FMFC_GEMINI_API_KEY') ?: '';
// ---------------

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($apiKey === '') {
        $error_msg = "ยังไม่ได้ตั้งค่า FMFC_GEMINI_API_KEY สำหรับใช้งาน AI";
    } elseif ($user_question === '') {
        $error_msg = "กรุณาระบุคำถามก่อนส่งให้ AI วิเคราะห์";
    } else {
        // ค้นหาโมเดล
        $modelResult = getWorkingModel($apiKey);

        if (isset($modelResult['success'])) {
            $selectedModel = $modelResult['success']; // ได้ชื่อโมเดลมาแล้ว เช่น models/gemini-1.5-flash-001
            $apiUrl = "https://generativelanguage.googleapis.com/v1beta/$selectedModel:generateContent?key=$apiKey";
            $debug_info = "Connected: " . str_replace('models/', '', $selectedModel);

            if ($team_data_json && !empty($players)) {
                $prompt = "
        Role: คุณคือผู้ช่วยผู้จัดการทีมฟุตบอลมืออาชีพ (Assistant Manager) ของสโมสร FC25.
        Task: วิเคราะห์ข้อมูลทีมจาก JSON ด้านล่าง และตอบคำถามผู้ใช้
        Context Data: $team_data_json
        
        User Question: $user_question
        
        Response Guidelines:
        - ตอบเป็นภาษาไทย
        - วิเคราะห์เจาะจงรายบุคคลถ้าจำเป็น
        - ใช้ Emoji ประกอบ
        - ใช้ Markdown จัดรูปแบบ (ตัวหนา, หัวข้อ)
        ";

                $data = [ "contents" => [ [ "parts" => [ ["text" => $prompt] ] ] ] ];

                $ch = curl_init($apiUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        
                $result = curl_exec($ch);
        
                if (curl_errno($ch)) {
                    $error_msg = "Curl Error: " . curl_error($ch);
                } else {
                    $decoded = json_decode($result, true);
                    if (isset($decoded['error'])) {
                        $error_msg = "AI Error (" . $decoded['error']['code'] . "): " . $decoded['error']['message'];
                    } elseif (isset($decoded['candidates'][0]['content']['parts'][0]['text'])) {
                        $response_text = $decoded['candidates'][0]['content']['parts'][0]['text'];
                    } else {
                        $error_msg = "AI ไม่ตอบสนอง (Empty Response)";
                    }
                }
                curl_close($ch);
            } else {
                $response_text = "ไม่พบข้อมูลนักเตะในทีม กรุณาเพิ่มนักเตะก่อนเริ่มใช้งาน";
            }
        } else {
            // แสดง Error จากการค้นหาโมเดล
            $error_msg = "Model Error: " . $modelResult['error'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>AI Assistant | FMFC Manager</title>
    <link rel="icon" type="image/png" href="../img/logo/fm25_logo_2.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style> 
        body { font-family: 'Kanit', sans-serif; } 
        .prose h1, .prose h2, .prose h3 { font-weight: 600; margin-top: 1em; margin-bottom: 0.5em; }
        .prose ul { list-style-type: disc; padding-left: 20px; margin-bottom: 1em; }
        .prose p { margin-bottom: 0.8em; line-height: 1.6; }
        .prose strong { color: #111827; }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #d1d5db; }
    </style>
</head>
<body class="bg-[#F9FAFB] text-gray-800 h-screen flex overflow-hidden">
    <?php include '../includes/navbar.php'; ?>

    <main class="flex-1 flex flex-col h-full relative">
        <div class="px-8 py-6 border-b border-gray-200 bg-white flex justify-between items-center z-10 shadow-sm">
            <div>
                <h1 class="text-2xl font-bold flex items-center gap-2">
                    <span class="bg-gradient-to-r from-blue-600 to-purple-600 text-transparent bg-clip-text">Gemini Football Analyst</span>
                    <span class="text-xs bg-black text-white px-2 py-1 rounded-full">AUTO</span>
                </h1>
                <p class="text-xs text-gray-500 mt-1">ผู้ช่วยอัจฉริยะวิเคราะห์ทีมแบบ Real-time</p>
            </div>
            <?php if($debug_info): ?>
                <span class="text-[10px] text-green-600 bg-green-50 px-3 py-1 rounded-full border border-green-100 font-mono">
                    ✅ <?= htmlspecialchars($debug_info) ?>
                </span>
            <?php endif; ?>
        </div>

        <div class="flex-1 overflow-y-auto p-8 custom-scrollbar">
            <div class="flex gap-4 max-w-3xl mx-auto mb-6">
                <div class="w-10 h-10 rounded-full bg-black flex items-center justify-center text-white shrink-0 shadow-lg">
                    <i data-lucide="bot" class="w-6 h-6"></i>
                </div>
                <div class="bg-white p-5 rounded-2xl rounded-tl-none shadow-sm border border-gray-100 max-w-2xl">
                    <p class="text-gray-800 text-sm leading-relaxed">
                        สวัสดีครับบอส! 👋 ผมพร้อมวิเคราะห์ข้อมูลนักเตะทั้ง <strong><?= count($players) ?> คน</strong> ในทีมของคุณแล้วครับ
                    </p>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <button onclick="setQuestion('วิเคราะห์จุดอ่อนของทีมชุดนี้ให้หน่อย')" class="text-xs bg-gray-50 border border-gray-200 hover:bg-gray-100 hover:border-gray-300 px-3 py-1.5 rounded-full transition text-gray-600">
                            วิเคราะห์จุดอ่อน
                        </button>
                        <button onclick="setQuestion('ใครควรเป็นกัปตันทีมและทำไม?')" class="text-xs bg-gray-50 border border-gray-200 hover:bg-gray-100 hover:border-gray-300 px-3 py-1.5 rounded-full transition text-gray-600">
                            หาตัวกัปตันทีม
                        </button>
                        <button onclick="setQuestion('แนะนำแผนการเล่นที่เหมาะกับนักเตะที่มี')" class="text-xs bg-gray-50 border border-gray-200 hover:bg-gray-100 hover:border-gray-300 px-3 py-1.5 rounded-full transition text-gray-600">
                            แนะนำแผนการเล่น
                        </button>
                    </div>
                </div>
            </div>

            <?php if ($error_msg): ?>
            <div class="max-w-3xl mx-auto mb-6 p-4 bg-red-50 border border-red-200 rounded-xl text-red-600 flex gap-3 items-start animate-pulse">
                <i data-lucide="alert-circle" class="w-5 h-5 mt-0.5 shrink-0"></i>
                <div>
                    <h3 class="font-bold text-sm">เกิดข้อผิดพลาด</h3>
                    <p class="text-xs mt-1"><?= htmlspecialchars($error_msg) ?></p>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($response_text): ?>
            <div class="flex gap-4 max-w-3xl mx-auto animate-fade-in-up pb-10">
                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white shrink-0 shadow-lg">
                    <i data-lucide="sparkles" class="w-5 h-5"></i>
                </div>
                <div class="bg-white p-8 rounded-2xl rounded-tl-none shadow-lg border border-purple-50 w-full relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-purple-100 rounded-full blur-3xl -mr-16 -mt-16 opacity-50 pointer-events-none"></div>
                    <div class="prose prose-sm max-w-none text-gray-700 leading-relaxed relative z-10" id="ai-content"></div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="p-6 bg-white border-t border-gray-200">
            <form method="POST" class="max-w-3xl mx-auto relative flex items-center gap-3">
                <div class="relative flex-1">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <i data-lucide="message-square" class="w-5 h-5 text-gray-400"></i>
                    </div>
                    <input type="text" name="question" id="questionInput" 
                        placeholder="ถามเพิ่มเติม... (เช่น นักเตะคนไหนฟอร์มดีที่สุด?)" 
                        class="w-full pl-11 pr-4 py-4 bg-gray-50 border-gray-200 border rounded-2xl focus:bg-white focus:ring-2 focus:ring-purple-600 focus:border-transparent transition-all shadow-inner text-sm">
                </div>
                <button name="ask_ai" type="submit" class="bg-black text-white p-4 rounded-2xl hover:bg-gray-800 transition shadow-lg flex items-center justify-center group active:scale-95">
                    <i data-lucide="send-horizontal" class="w-5 h-5 group-hover:translate-x-1 transition-transform"></i>
                </button>
            </form>
            <p class="text-center text-[10px] text-gray-400 mt-3 font-medium">Powered by Google Gemini (Auto-Detected Model)</p>
        </div>
    </main>

    <div id="ai-loading" class="fixed inset-0 bg-white/70 backdrop-blur-sm flex items-center justify-center z-50 hidden transition-opacity">
        <div class="bg-white p-8 rounded-3xl shadow-2xl flex flex-col items-center border border-gray-100">
            <div class="relative w-16 h-16 mb-4">
                <div class="absolute inset-0 border-4 border-gray-100 rounded-full"></div>
                <div class="absolute inset-0 border-4 border-purple-600 border-t-transparent rounded-full animate-spin"></div>
            </div>
            <p class="text-lg font-bold text-gray-900">กำลังวิเคราะห์ข้อมูล...</p>
            <p class="text-xs text-gray-500 mt-1">AI กำลังเลือกโมเดลที่ดีที่สุดและประมวลผล</p>
        </div>
    </div>

    <script>
        lucide.createIcons();
        <?php if ($response_text): ?>
            document.getElementById('ai-content').innerHTML = marked.parse(<?= json_encode($response_text) ?>);
        <?php endif; ?>

        function setQuestion(q) { 
            document.getElementById('questionInput').value = q;
            document.getElementById('questionInput').focus();
        }
        
        document.querySelector('form').addEventListener('submit', () => { 
            document.getElementById('ai-loading').classList.remove('hidden'); 
        });
        
        const container = document.querySelector('.custom-scrollbar');
        if(container) container.scrollTop = container.scrollHeight;
    </script>
</body>
</html>
