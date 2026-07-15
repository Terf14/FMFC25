<?php
if (!function_exists('fmfc_position_badge_class')) {
    function fmfc_position_badge_class($position) {
        return match (strtolower((string)$position)) {
            'st', 'cf', 'lw', 'rw' => 'bg-red-50 text-red-700 border-red-100',
            'lm', 'rm', 'cam', 'cm', 'cdm' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
            'cb', 'lb', 'rb' => 'bg-blue-50 text-blue-700 border-blue-100',
            'gk' => 'bg-amber-100 text-amber-800 border-amber-200',
            default => 'bg-gray-100 text-gray-600 border-gray-200',
        };
    }
}

if (!function_exists('fmfc_position_badge')) {
    function fmfc_position_badge($position, $extraClass = '') {
        $position = strtolower((string)$position);
        $class = fmfc_position_badge_class($position);
        return "<span class=\"inline-flex items-center justify-center min-w-10 px-2 py-1 rounded-lg border text-[10px] font-black uppercase tracking-wider {$class} {$extraClass}\">" . htmlspecialchars($position) . "</span>";
    }
}

if (!function_exists('fmfc_status_badge')) {
    function fmfc_status_badge($status, $extraClass = '') {
        $map = [
            'no' => ['อยู่กับทีม', 'bg-green-50 text-green-700 border-green-100'],
            'sell' => ['ขาย', 'bg-red-50 text-red-700 border-red-100'],
            'for_loan' => ['พร้อมปล่อยยืม', 'bg-orange-50 text-orange-700 border-orange-100'],
            'on_loan' => ['ถูกยืม', 'bg-blue-50 text-blue-700 border-blue-100'],
            'in_loan' => ['ยืมมา', 'bg-purple-50 text-purple-700 border-purple-100'],
        ];
        [$label, $class] = $map[$status] ?? ['ไม่ระบุ', 'bg-gray-100 text-gray-600 border-gray-200'];
        return "<span class=\"inline-flex items-center rounded-lg border px-2 py-1 text-[10px] font-bold {$class} {$extraClass}\">{$label}</span>";
    }
}
?>
