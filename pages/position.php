<?php
session_start();
include "../includes/db_config.php";
include "../includes/auth.php";

// รับ `user_id` จาก session หรือการล็อกอิน
$user_id = $_SESSION['user_id']; // สมมติว่า session เก็บ `user_id`

// คำนวณจำนวนของนักเตะในแต่ละสถานะที่ผู้ใช้เพิ่ม
$status_counts = [
  'sell' => 0,
  'for_loan' => 0,
  'on_loan' => 0,
  'in_loan' => 0,
  'no' => 0
];

if ($conn instanceof PDO) {
  $statusQuery = $conn->prepare("SELECT status, COUNT(*) as count FROM Players WHERE user_id = ? GROUP BY status");
  $statusQuery->execute([$user_id]);
  $statusResults = $statusQuery->fetchAll(PDO::FETCH_ASSOC);
  foreach ($statusResults as $row) {
    if (isset($status_counts[$row['status']])) {
      $status_counts[$row['status']] = $row['count'];
    }
  }
} else {
  $statusQuery = mysqli_prepare($conn, "SELECT status, COUNT(*) as count FROM Players WHERE user_id = ? GROUP BY status");
  mysqli_stmt_bind_param($statusQuery, "i", $user_id);
  mysqli_stmt_execute($statusQuery);
  $statusResult = mysqli_stmt_get_result($statusQuery);
  while ($row = mysqli_fetch_assoc($statusResult)) {
    if (isset($status_counts[$row['status']])) {
      $status_counts[$row['status']] = $row['count'];
    }
  }
}

// คำนวณจำนวนนักเตะทั้งหมดที่ผู้ใช้เพิ่ม
$totalPlayers = 0;
if ($conn instanceof PDO) {
  $queryTotal = $conn->prepare("SELECT COUNT(*) as total FROM Players WHERE user_id = ?");
  $queryTotal->execute([$user_id]);
  $totalPlayers = $queryTotal->fetch(PDO::FETCH_ASSOC)['total'];
} else {
  $queryTotal = mysqli_prepare($conn, "SELECT COUNT(*) as total FROM Players WHERE user_id = ?");
  mysqli_stmt_bind_param($queryTotal, "i", $user_id);
  mysqli_stmt_execute($queryTotal);
  $rowTotal = mysqli_stmt_get_result($queryTotal);
  $totalPlayers = mysqli_fetch_assoc($rowTotal)['total'];
}

// ดึงข้อมูลนักเตะทั้งหมดที่ผู้ใช้เพิ่ม และเรียงตาม role (ดึงข้อมูลเพิ่มเติมสำหรับแสดงใน Card)
$players = [];
$position_counts = [];

if ($conn instanceof PDO) {
  $query = $conn->prepare("
        SELECT player_id, name, position, role, jersey_number, status, injured, performance_score, is_academy_product FROM Players 
        WHERE user_id = ? 
        ORDER BY FIELD(COALESCE(role, 'prospect'), 'crucial', 'important', 'rotation', 'sporadic', 'prospect'), name ASC
    ");
  $query->execute([$user_id]);
  $players = $query->fetchAll(PDO::FETCH_ASSOC);
} else {
  $query = mysqli_prepare($conn, "
        SELECT player_id, name, position, role, jersey_number, status, injured, performance_score, is_academy_product FROM Players 
        WHERE user_id = ? 
        ORDER BY FIELD(COALESCE(role, 'prospect'), 'crucial', 'important', 'rotation', 'sporadic', 'prospect'), name ASC
    ");
  mysqli_stmt_bind_param($query, "i", $user_id);
  mysqli_stmt_execute($query);
  $result = mysqli_stmt_get_result($query);
  while ($row = mysqli_fetch_assoc($result)) {
    $players[] = $row;
  }
}

// นับจำนวนของนักเตะในแต่ละตำแหน่ง
foreach ($players as $player) {
  $position_counts[$player['position']] = isset($position_counts[$player['position']]) ? $position_counts[$player['position']] + 1 : 1;
}

// ฟังก์ชันสำหรับดึง Initial (ถ้ายังไม่มี)
if (!function_exists('getInitials')) {
    function getInitials($name) {
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


// กำหนดตำแหน่งให้ตรงกับ Grid (โครงสร้างเดิม)
$positions_grid = [
  ['lw', 'st', 'rw'],
  ['', 'cf', ''],
  ['lm', 'cam', 'rm'],
  ['', 'cm', ''],
  ['', 'cdm', ''],
  ['lb', 'cb', 'rb'],
  ['', 'gk', '']
];

?>

<!DOCTYPE html>
<html lang="th">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ตำแหน่ง | FM25 Manager</title>
  <link rel="icon" type="image/png" href="../img/logo/fm25_logo_2.png">
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
  <link href="https://fonts.googleapis.com/css2?family=Itim&family=Kanit:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Kanit', sans-serif;
    }
  </style>
</head>

<body class="bg-gray-50 text-gray-800">
  <div class="flex h-screen overflow-hidden">
    <?php include '../includes/navbar.php'; /* */ ?>

    <main class="flex-1 overflow-y-auto px-10 py-8">
      <h1 class="text-2xl font-semibold flex items-center gap-2 mb-6">
        <i data-lucide="file-badge" class="w-5 h-5 text-gray-600"></i>
        ตำแหน่ง
      </h1>

      <div class="text-sm text-gray-900 mb-6 flex flex-wrap gap-x-4 gap-y-2 font-semibold">
        <span class="text-gray-900">นักเตะทั้งหมดในทีม: <?php echo $totalPlayers; ?> คน</span>
        <span class="text-sky-500">อยู่กับทีม: <?php echo $status_counts['no'] ?? 0; ?> คน</span>
        <span class="text-red-500">เตรียมขาย: <?php echo $status_counts['sell'] ?? 0; ?> คน</span>
        <span class="text-pink-500">เตรียมปล่อยยืมตัว: <?php echo $status_counts['for_loan'] ?? 0; ?> คน</span>
        <span class="text-blue-700">กำลังปล่อยยืมตัว: <?php echo $status_counts['on_loan'] ?? 0; ?> คน</span>
        <span class="text-green-700">กำลังยืมตัว: <?php echo $status_counts['in_loan'] ?? 0; ?> คน</span>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($positions_grid as $row): ?>
          <?php foreach ($row as $pos): ?>
            <div class="<?php echo $pos ? 'bg-gray-100' : 'bg-transparent'; ?> p-4 rounded-lg shadow-sm">
              <?php if ($pos): ?>
                <div class="text-center font-bold uppercase text-base mb-3 flex items-center justify-center gap-2">
                  <?= htmlspecialchars($pos); ?> <span class="text-gray-500 text-sm">(<?= $position_counts[$pos] ?? 0; ?>)</span>
                </div>

                <div class="flex flex-col gap-2">
                  <?php 
                  $players_in_pos = array_filter($players, fn($p) => $p['position'] === $pos);
                  if (!empty($players_in_pos)): 
                    foreach ($players_in_pos as $player): 
                      $initials = getInitials($player['name']);
                      $injuredDotColor = $player['injured'] > 0 ? 'bg-red-500' : 'bg-gray-400';

                      // กำหนดสีพื้นหลังของ Card ตามสถานะ (เพื่อให้สอดคล้องกัน)
                      $cardBgClass = 'bg-white'; 
                      switch ($player['status']) {
                          case 'no': $cardBgClass = 'bg-white'; break;
                          case 'sell': $cardBgClass = 'bg-red-50'; break;
                          case 'for_loan': $cardBgClass = 'bg-pink-50'; break;
                          case 'on_loan': $cardBgClass = 'bg-blue-50'; break;
                          case 'in_loan': $cardBgClass = 'bg-green-50'; break;
                      }

                      // กำหนดสีและไอคอนของ performance_score
                      $scoreColorClass = 'text-gray-500'; 
                      $scoreIcon = 'arrow-right'; 
                      if ($player['performance_score'] > 0) {
                          $scoreColorClass = 'text-green-500';
                          $scoreIcon = 'arrow-up';
                      } elseif ($player['performance_score'] < 0) {
                          $scoreColorClass = 'text-red-500';
                          $scoreIcon = 'arrow-down';
                      }
                  ?>
                      <a href="edit_player1.php?id=<?= $player['player_id']; /* */ ?>"
                          class="flex items-center gap-2 p-2 <?= $cardBgClass; ?> border border-gray-200 rounded-md shadow-sm hover:shadow-md transition">
                          <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-800 font-bold text-xs flex-shrink-0">
                              <?= htmlspecialchars($initials); ?>
                          </div>
                          <div class="flex-1 overflow-hidden">
                              <div class="flex items-center justify-between gap-1">
                                  <span class="font-semibold text-sm truncate text-gray-800">
                                      <?= htmlspecialchars($player['name']); ?>
                                      <?php if ($player['is_academy_product'] == 1): ?>
                                          <i data-lucide="graduation-cap" class="inline-block w-3 h-3 ml-0.5 text-purple-600 fill-current"></i>
                                      <?php endif; ?>
                                  </span>
                                  <?php if ($player['performance_score'] !== null): ?>
                                      <span class="flex items-center text-xs font-medium flex-shrink-0 <?= $scoreColorClass; ?>">
                                          <i data-lucide="<?= $scoreIcon; ?>" class="w-3 h-3 mr-0.5"></i>
                                          <?= htmlspecialchars($player['performance_score']); ?>
                                      </span>
                                  <?php endif; ?>
                              </div>
                              <div class="flex items-center gap-1 text-xs text-gray-500">
                                  <span class="uppercase flex-shrink-0"><?= htmlspecialchars($player['role']); ?></span>
                                  <?php if ($player['jersey_number'] !== null): ?>
                                      <span class="font-medium text-gray-600">#<?= htmlspecialchars($player['jersey_number']); ?></span>
                                  <?php endif; ?>
                                  <span class="w-1.5 h-1.5 rounded-full <?= $injuredDotColor; ?> flex-shrink-0"></span>
                              </div>
                          </div>
                      </a>
                  <?php endforeach; ?>
                  <?php else: ?>
                    <p class="text-sm text-gray-500 text-center py-2">ไม่มีนักเตะในตำแหน่งนี้</p>
                  <?php endif; ?>
                </div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        <?php endforeach; ?>
      </div>
    </main>
  </div>

  <script>
    lucide.createIcons();
  </script>
</body>

</html>