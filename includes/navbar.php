<aside class="w-72 h-full border-r border-gray-100 bg-white px-6 py-8 flex flex-col shadow-[4px_0_24px_rgba(0,0,0,0.02)] z-10">
    <div class="mb-10 flex items-center gap-3 px-2">
        <div class="w-10 h-10 bg-black rounded-xl flex items-center justify-center text-white">
            <i data-lucide="activity" class="w-6 h-6"></i>
        </div>
        <div>
            <h1 class="text-xl font-bold tracking-tight text-gray-900">FMFC</h1>
            <p class="text-xs text-gray-400 font-medium tracking-wide">MANAGER SYSTEM</p>
        </div>
    </div>

    <div class="flex-1 space-y-8 overflow-y-auto pr-2 custom-scrollbar">
        <div>
            <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400 mb-4 px-3">Main Menu</p>
            <nav class="space-y-1">
                <?php
                $current_page = basename($_SERVER['PHP_SELF']);
                $menu_items = [
                    'dashboard.php' => ['icon' => 'layout-grid', 'label' => 'แดชบอร์ด'],
                    'manage_players.php' => ['icon' => 'users', 'label' => 'จัดการนักเตะ'],
                    'position.php' => ['icon' => 'file-badge', 'label' => 'ตำแหน่ง & แผน'],
                    'academy.php' => ['icon' => 'graduation-cap', 'label' => 'อาคาเดมี่'],
                ];

                foreach ($menu_items as $file => $item) {
                    $active = ($current_page == $file) ? 'bg-black text-white shadow-lg shadow-gray-200' : 'text-gray-500 hover:bg-gray-50 hover:text-black';
                    echo "<a href='$file' class='flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-all duration-200 $active'>
                            <i data-lucide='{$item['icon']}' class='w-5 h-5'></i> {$item['label']}
                          </a>";
                }
                ?>
            </nav>
        </div>

        <div>
            <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400 mb-4 px-3">Data Center</p>
            <nav class="space-y-1">
                <?php
                $data_items = [
                    'stats.php' => ['icon' => 'bar-chart-2', 'label' => 'จัดการฟอร์ม'],
                    'total_stats.php' => ['icon' => 'line-chart', 'label' => 'สถิติรวม'],
                    'player_recommendations.php' => ['icon' => 'sparkles', 'label' => 'AI แนะนำ'],
                    'ai_assistant.php' => ['icon' => 'bot', 'label' => 'พูดคุยกับ AI'],
                ];
                foreach ($data_items as $file => $item) {
                    $active = ($current_page == $file) ? 'bg-black text-white shadow-lg shadow-gray-200' : 'text-gray-500 hover:bg-gray-50 hover:text-black';
                    echo "<a href='$file' class='flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-all duration-200 $active'>
                            <i data-lucide='{$item['icon']}' class='w-5 h-5'></i> {$item['label']}
                          </a>";
                }
                ?>
            </nav>
        </div>

        <div>
            <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400 mb-4 px-3">History</p>
            <nav class="space-y-1">
                <?php
                $hist_items = [
                    'trophies.php' => ['icon' => 'trophy', 'label' => 'ความสำเร็จ'],
                    'hall_of_records.php' => ['icon' => 'medal', 'label' => 'สถิติสโมสร'], // เพิ่มเมนูใหม่ตรงนี้
                    'legendary_players.php' => ['icon' => 'crown', 'label' => 'ตำนานสโมสร'],
                ];
                foreach ($hist_items as $file => $item) {
                    $active = ($current_page == $file) ? 'bg-black text-white shadow-lg shadow-gray-200' : 'text-gray-500 hover:bg-gray-50 hover:text-black';
                    echo "<a href='$file' class='flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-all duration-200 $active'>
                            <i data-lucide='{$item['icon']}' class='w-5 h-5'></i> {$item['label']}
                          </a>";
                }
                ?>
            </nav>
        </div>
    </div>

    <div class="mt-6 border-t border-gray-100 pt-6">
        <a href="settings.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-gray-500 hover:bg-gray-50 hover:text-black transition-all">
            <i data-lucide="settings" class="w-5 h-5"></i> ตั้งค่า
        </a>
        <a href="logout.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-red-500 hover:bg-red-50 transition-all mt-1">
            <i data-lucide="log-out" class="w-5 h-5"></i> ออกจากระบบ
        </a>
    </div>

    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #f3f4f6; border-radius: 4px; }
        .custom-scrollbar:hover::-webkit-scrollbar-thumb { background: #d1d5db; }
    </style>
</aside>

<div id="loading-overlay" class="fixed inset-0 bg-white/80 backdrop-blur-sm flex items-center justify-center z-50 hidden transition-opacity duration-300">
    <div class="flex flex-col items-center">
        <div class="relative w-16 h-16">
            <div class="absolute top-0 left-0 w-full h-full border-4 border-gray-100 rounded-full"></div>
            <div class="absolute top-0 left-0 w-full h-full border-4 border-black rounded-full border-t-transparent animate-spin"></div>
        </div>
        <p class="text-gray-900 mt-4 text-sm font-medium tracking-wide">กำลังประมวลผล...</p>
    </div>
</div>

<script>
    function showLoading() { document.getElementById('loading-overlay').classList.remove('hidden'); }
    function hideLoading() { document.getElementById('loading-overlay').classList.add('hidden'); }
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('form').forEach(f => f.addEventListener('submit', showLoading));
        window.addEventListener('load', hideLoading);
        window.addEventListener('pageshow', (e) => { if (e.persisted) hideLoading(); });
    });
</script>
<?php include_once __DIR__ . '/toast.php'; ?>
