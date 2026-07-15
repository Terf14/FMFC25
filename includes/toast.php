<?php
$fmfcToastMessage = $_SESSION['toast_message'] ?? null;
if (isset($_SESSION['toast_message'])) {
    unset($_SESSION['toast_message']);
}
?>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
<script>
    window.fmfcShowToast = function(type, message, redirectUrl = null) {
        const palette = {
            success: { background: "#111827", icon: "check-circle-2" },
            error: { background: "#dc2626", icon: "alert-circle" },
            warning: { background: "#b45309", icon: "triangle-alert" },
            info: { background: "#2563eb", icon: "info" }
        };
        const tone = palette[type] || palette.info;
        Toastify({
            text: message,
            duration: 3200,
            close: true,
            gravity: "top",
            position: "right",
            stopOnFocus: true,
            offset: { x: 18, y: 18 },
            style: {
                background: tone.background,
                borderRadius: "14px",
                boxShadow: "0 18px 40px rgba(15, 23, 42, 0.18)",
                fontSize: "14px",
                lineHeight: "1.45",
                padding: "13px 18px",
                fontFamily: "'Kanit', sans-serif",
                maxWidth: "360px"
            },
            callback: function() {
                if (redirectUrl) window.location.href = redirectUrl;
            }
        }).showToast();
    };

    window.showToast = window.fmfcShowToast;

    <?php if ($fmfcToastMessage): ?>
    document.addEventListener('DOMContentLoaded', function() {
        window.fmfcShowToast(
            <?= json_encode($fmfcToastMessage['type'] ?? 'info') ?>,
            <?= json_encode($fmfcToastMessage['message'] ?? '') ?>
        );
    });
    <?php endif; ?>
</script>
