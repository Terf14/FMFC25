<?php
session_start();
include "../includes/db_config.php";
include "../includes/auth.php";
include "../includes/ui_helpers.php";

// Function to get initials
if (!function_exists('getInitials')) {
    function getInitials($name)
    {
        $words = explode(' ', $name);
        $initials = '';
        foreach ($words as $word) {
            if (!empty($word)) {
                $initials .= strtoupper(substr($word, 0, 1));
            }
        }
        if (strlen($initials) > 2 && count($words) > 1) {
            $initials = substr($initials, 0, 1) . substr($initials, -1);
        } elseif (empty($initials) && !empty($name)) {
            $initials = strtoupper(substr($name, 0, min(2, strlen($name))));
        }
        return $initials;
    }
}

$userId = $_SESSION['user_id'];

// --- 1. ดึงข้อมูลกัปตันทีม ---
$captain_id = null;
if ($conn instanceof PDO) {
    $stmt_cap = $conn->prepare("SELECT player_id FROM team_captain WHERE user_id = ?");
    $stmt_cap->execute([$userId]);
    $captain_id = $stmt_cap->fetchColumn();
}

// Fetch Status Counts
$status_counts = ['sell' => 0, 'for_loan' => 0, 'on_loan' => 0, 'in_loan' => 0, 'no' => 0];
if ($conn instanceof PDO) {
    $statusQuery = $conn->prepare("SELECT status, COUNT(*) as count FROM Players WHERE user_id = :user_id GROUP BY status");
    $statusQuery->execute([':user_id' => $userId]);
    $statusResults = $statusQuery->fetchAll(PDO::FETCH_ASSOC);
    foreach ($statusResults as $row) $status_counts[$row['status']] = $row['count'];
}

// Fetch Total Players
if ($conn instanceof PDO) {
    $queryTotal = $conn->prepare("SELECT COUNT(*) as total FROM Players WHERE user_id = :user_id");
    $queryTotal->execute([':user_id' => $userId]);
    $totalPlayers = $queryTotal->fetch(PDO::FETCH_ASSOC)['total'];
}

// Fetch Roles
$roles = ['crucial', 'important', 'rotation', 'sporadic', 'prospect'];
$role_display_thai = [
    'crucial' => 'ตัวหลัก',
    'important' => 'สำคัญ',
    'rotation' => 'หมุนเวียน',
    'sporadic' => 'สำรอง',
    'prospect' => 'ดาวรุ่ง'
];

// Fetch Players Data
$resultByRole = [];
$resultByJersey = [];
$allPlayersForModal = []; // เก็บรายชื่อนักเตะทั้งหมดสำหรับ Modal เลือกกัปตัน

if ($conn instanceof PDO) {
    // Players by Jersey (และเก็บใส่ตัวแปรสำหรับ Modal ด้วย)
    $queryByJersey = $conn->prepare("SELECT player_id, name, position, jersey_number, status, is_academy_product FROM Players WHERE user_id = :user_id ORDER BY jersey_number ASC");
    $queryByJersey->execute([':user_id' => $userId]);
    $resultByJersey = $queryByJersey->fetchAll(PDO::FETCH_ASSOC);
    $allPlayersForModal = $resultByJersey;

    // Players by Role
    foreach ($roles as $role) {
        $query = $conn->prepare("SELECT player_id, name, position, role, jersey_number, status, injured, injured_count, performance_score, is_academy_product FROM Players WHERE role = :role AND user_id = :user_id ORDER BY FIELD(position, 'gk', 'cb', 'rb', 'lb', 'cdm', 'cm', 'cam', 'rm', 'lm', 'rw', 'lw', 'st')");
        $query->execute([':role' => $role, ':user_id' => $userId]);
        $resultByRole[$role] = $query->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการนักเตะ | FMFC Manager</title>
    <link rel="icon" type="image/png" href="../img/logo/fm25_logo_2.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Itim&family=Kanit:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <style>
        body {
            font-family: 'Kanit', sans-serif;
        }

        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }

        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        /* Modal Animation */
        .modal {
            transition: opacity 0.25s ease;
        }

        body.modal-active {
            overflow-x: hidden;
            overflow-y: hidden !important;
        }
    </style>
</head>

<body class="bg-[#F9FAFB] text-gray-800">
    <div class="flex h-screen overflow-hidden">
        <?php include '../includes/navbar.php'; ?>

        <main class="flex-1 px-8 py-10 overflow-y-auto relative">

            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 tracking-tight flex items-center gap-3">
                        <i data-lucide="users" class="w-8 h-8 text-black"></i> จัดการนักเตะ
                    </h1>
                    <p class="text-gray-500 mt-1 text-sm">จัดการสถานะและบทบาทของนักเตะในทีม (<?= $totalPlayers ?> คน)</p>
                </div>
                <div class="flex gap-3">
                    <button onclick="toggleModal('captainModal')"
                        class="bg-yellow-400 text-black px-6 py-3 rounded-xl hover:bg-yellow-500 transition-all shadow-lg shadow-yellow-100 flex items-center gap-2 font-bold">
                        <i data-lucide="copyright" class="w-5 h-5"></i> แต่งตั้งกัปตัน
                    </button>
                    <a href="add_player.php" class="bg-black text-white px-6 py-3 rounded-xl hover:bg-gray-800 transition-all shadow-lg shadow-gray-200 flex items-center gap-2 font-medium">
                        <i data-lucide="plus" class="w-5 h-5"></i> เพิ่มนักเตะใหม่
                    </a>
                </div>
            </div>

            <div class="flex flex-wrap gap-3 mb-10 overflow-x-auto pb-2 no-scrollbar">
                <div class="flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 rounded-full shadow-sm whitespace-nowrap">
                    <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                    <span class="text-sm text-gray-600">ทีมหลัก: <strong class="text-black"><?= $status_counts['no'] ?></strong></span>
                </div>
                <div class="flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 rounded-full shadow-sm whitespace-nowrap">
                    <span class="w-2 h-2 bg-red-500 rounded-full"></span>
                    <span class="text-sm text-gray-600">ขาย: <strong class="text-black"><?= $status_counts['sell'] ?></strong></span>
                </div>
                <div class="flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 rounded-full shadow-sm whitespace-nowrap">
                    <span class="w-2 h-2 bg-orange-500 rounded-full"></span>
                    <span class="text-sm text-gray-600">พร้อมปล่อยยืม: <strong class="text-black"><?= $status_counts['for_loan'] ?></strong></span>
                </div>
                <div class="flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 rounded-full shadow-sm whitespace-nowrap">
                    <span class="w-2 h-2 bg-blue-500 rounded-full"></span>
                    <span class="text-sm text-gray-600">ถูกยืม: <strong class="text-black"><?= $status_counts['on_loan'] ?></strong></span>
                </div>
                <div class="flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 rounded-full shadow-sm whitespace-nowrap">
                    <span class="w-2 h-2 bg-purple-500 rounded-full"></span>
                    <span class="text-sm text-gray-600">ยืมมา: <strong class="text-black"><?= $status_counts['in_loan'] ?></strong></span>
                </div>
                <div class="flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 rounded-full shadow-sm whitespace-nowrap">
                    <span class="w-2 h-2 bg-yellow-400 rounded-full"></span>
                    <span class="text-sm text-gray-600">กัปตันทีม:
                        <strong class="text-black">
                            <?php
                            // หาชื่อกัปตัน
                            $capName = "ยังไม่ระบุ";
                            foreach ($allPlayersForModal as $p) {
                                if ($p['player_id'] == $captain_id) {
                                    $capName = $p['name'];
                                    break;
                                }
                            }
                            echo htmlspecialchars($capName);
                            ?>
                        </strong>
                    </span>
                </div>
            </div>

            <div class="flex gap-6 overflow-x-auto pb-8 custom-scrollbar">
                <?php foreach ($roles as $role):
                    $playersInRole = $resultByRole[$role] ?? [];
                    // Color coding for role headers
                    $roleColor = match ($role) {
                        'crucial' => 'bg-black text-white',
                        'important' => 'bg-gray-700 text-white',
                        'rotation' => 'bg-gray-200 text-gray-800',
                        'sporadic' => 'bg-gray-100 text-gray-600',
                        'prospect' => 'border border-dashed border-gray-300 text-gray-500',
                        default => 'bg-gray-100'
                    };
                ?>
                    <div class="flex-none w-80 flex flex-col">
                        <div class="mb-4 flex items-center justify-between">
                            <span class="px-3 py-1 rounded-lg text-xs font-bold uppercase tracking-wider <?= $roleColor ?>">
                                <?= $role_display_thai[$role] ?> (<?= count($playersInRole) ?>)
                            </span>
                        </div>

                        <div class="flex flex-col gap-3">
                            <?php if (empty($playersInRole)): ?>
                                <div class="h-24 flex items-center justify-center border-2 border-dashed border-gray-200 rounded-2xl text-gray-400 text-sm">
                                    ไม่มีนักเตะ
                                </div>
                            <?php else: ?>
                                <?php foreach ($playersInRole as $player):
                                    $initials = getInitials($player['name']);
                                    $isInjured = $player['injured'] > 0;
                                    $isCaptain = ($player['player_id'] == $captain_id);

                                    $statusBadge = fmfc_status_badge($player['status']);
                                ?>
                                    <a href="edit_player.php?id=<?= $player['player_id'] ?>"
                                        class="group block bg-white p-4 rounded-2xl border <?= $isCaptain ? 'border-yellow-400 ring-2 ring-yellow-100' : 'border-gray-100' ?> shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-200 relative overflow-hidden">

                                        <?php if ($isCaptain): ?>
                                            <div class="absolute bottom-0 right-0 bg-yellow-400 text-black text-[10px] font-bold px-2 py-1 rounded-tl-xl z-20 shadow-sm flex items-center gap-1">
                                                <i data-lucide="copyright" class="w-3 h-3"></i> Captain
                                            </div>
                                        <?php endif; ?>

                                        <?php if ($isInjured): ?>
                                            <div class="absolute top-0 right-0 w-3 h-3 bg-red-500 rounded-bl-lg z-10" title="บาดเจ็บ"></div>
                                        <?php endif; ?>

                                        <div class="flex items-center gap-4">
                                            <div class="w-12 h-12 rounded-full bg-gray-50 flex items-center justify-center text-gray-700 font-bold text-sm border border-gray-100 group-hover:bg-black group-hover:text-white transition-colors">
                                                <?= $initials ?>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center justify-between">
                                                    <h3 class="font-semibold text-gray-900 text-sm truncate pr-2"><?= htmlspecialchars($player['name']) ?></h3>
                                                    <?php if ($player['performance_score'] != 0): ?>
                                                        <span class="text-xs font-bold <?= $player['performance_score'] > 0 ? 'text-green-600' : 'text-red-600' ?>">
                                                            <?= $player['performance_score'] > 0 ? '+' : '' ?><?= $player['performance_score'] ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="flex items-center gap-2 mt-1">
                                                    <?= fmfc_position_badge($player['position']) ?>
                                                    <span class="text-xs text-gray-400">#<?= $player['jersey_number'] ?? '-' ?></span>
                                                    <?= $statusBadge ?>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="mt-10 bg-white rounded-3xl border border-gray-100 shadow-sm p-8">
                <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center gap-2">
                    <i data-lucide="list-ordered" class="w-5 h-5 text-gray-500"></i> รายชื่อตามเบอร์เสื้อ
                </h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    <?php foreach ($resultByJersey as $player):
                        $isCap = ($player['player_id'] == $captain_id);
                    ?>
                        <a href="edit_player.php?id=<?= $player['player_id'] ?>"
                            class="flex items-center gap-3 p-3 rounded-xl hover:bg-gray-50 transition-colors border <?= $isCap ? 'border-yellow-200 bg-yellow-50/50' : 'border-transparent hover:border-gray-100' ?>">
                            <span class="w-8 h-8 flex items-center justify-center <?= $isCap ? 'bg-yellow-400 text-black' : 'bg-black text-white' ?> text-xs font-bold rounded-lg">
                                <?= $player['jersey_number'] ?? '-' ?>
                            </span>
                            <div class="flex flex-col">
                                <span class="text-sm font-medium text-gray-900 truncate flex items-center gap-1">
                                    <?= htmlspecialchars($player['name']) ?>
                                    <?php if ($isCap): ?><i data-lucide="copyright" class="w-3 h-3 text-yellow-600 fill-current"></i><?php endif; ?>
                                </span>
                                <?= fmfc_position_badge($player['position'], 'mt-1 w-fit') ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

        </main>
    </div>

    <div id="captainModal" class="modal opacity-0 pointer-events-none fixed w-full h-full top-0 left-0 flex items-center justify-center z-50">
        <div class="modal-overlay absolute w-full h-full bg-black opacity-50"></div>

        <div class="modal-container bg-white w-11/12 md:max-w-md mx-auto rounded-[2rem] shadow-2xl z-50 overflow-y-auto max-h-[90vh]">

            <div class="modal-content py-6 text-left px-6">
                <div class="flex justify-between items-center pb-3 border-b border-gray-100 mb-4">
                    <p class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                    <div class="bg-yellow-100 p-2 rounded-xl text-yellow-600"><i data-lucide="copyright" class="w-6 h-6"></i></div>
                    แต่งตั้งกัปตันทีม
                    </p>
                    <div class="modal-close cursor-pointer z-50" onclick="toggleModal('captainModal')">
                        <i data-lucide="x" class="w-6 h-6 text-gray-500"></i>
                    </div>
                </div>

                <div class="mb-4">
                    <input type="text" id="captainSearch" placeholder="ค้นหาชื่อนักเตะ..."
                        class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-black outline-none text-sm">
                </div>

                <div class="overflow-y-auto max-h-[60vh] space-y-2 pr-1 custom-scrollbar">
                    <?php foreach ($allPlayersForModal as $p):
                        $isCurrentCap = ($p['player_id'] == $captain_id);
                    ?>
                        <div class="player-option flex items-center justify-between p-3 rounded-xl border <?= $isCurrentCap ? 'border-yellow-400 bg-yellow-50' : 'border-gray-100 hover:bg-gray-50' ?> cursor-pointer transition-all"
                            onclick="setCaptain(<?= $p['player_id'] ?>)"
                            data-name="<?= strtolower(htmlspecialchars($p['name'])) ?>">

                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-white border border-gray-200 flex items-center justify-center font-bold text-sm text-gray-500">
                                    <?= $p['jersey_number'] ?? '-' ?>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-gray-900"><?= htmlspecialchars($p['name']) ?></p>
                                    <?= fmfc_position_badge($p['position'], 'mt-1') ?>
                                </div>
                            </div>

                            <?php if ($isCurrentCap): ?>
                                <span class="text-xs font-bold bg-yellow-400 text-black px-3 py-1 rounded-full">Current</span>
                            <?php else: ?>
                                <div class="w-6 h-6 rounded-full border-2 border-gray-200"></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="mt-6 pt-4 border-t border-gray-100 text-center">
                    <button onclick="toggleModal('captainModal')" class="text-gray-500 hover:text-black text-sm font-medium">ปิดหน้าต่าง</button>
                </div>
            </div>
        </div>
    </div>

    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script>
        lucide.createIcons();

        // Modal Logic
        function toggleModal(modalID) {
            const modal = document.getElementById(modalID);
            modal.classList.toggle('opacity-0');
            modal.classList.toggle('pointer-events-none');
            document.body.classList.toggle('modal-active');
        }

        // Search Logic
        document.getElementById('captainSearch').addEventListener('keyup', function() {
            const filter = this.value.toLowerCase();
            const options = document.querySelectorAll('.player-option');
            options.forEach(option => {
                const name = option.getAttribute('data-name');
                if (name.includes(filter)) {
                    option.style.display = 'flex';
                } else {
                    option.style.display = 'none';
                }
            });
        });

        // Set Captain Logic
        function setCaptain(playerId) {
            if (!confirm('ยืนยันการแต่งตั้งกัปตันทีมคนใหม่?')) return;

            fetch('set_captain.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'player_id=' + playerId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        toggleModal('captainModal');
                        // Show success toast
                        Toastify({
                            text: data.message,
                            duration: 2000,
                            style: {
                                background: "#18181b",
                                borderRadius: "12px"
                            },
                            callback: function() {
                                window.location.reload();
                            }
                        }).showToast();
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
        }

        // Toast Handler (Session)
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
