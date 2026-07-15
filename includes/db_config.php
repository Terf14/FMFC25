<?php
$host = getenv('FMFC_DB_HOST') ?: "localhost";
$dbname = getenv('FMFC_DB_NAME') ?: "fc25";
$username = getenv('FMFC_DB_USER') ?: "root";
$password = getenv('FMFC_DB_PASS') ?: "";

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("ไม่สามารถเชื่อมต่อฐานข้อมูลได้ กรุณาตรวจสอบการตั้งค่าระบบ");
}
?>
