<?php
session_start();

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'cashier') {
    header("Location: login.php");
    exit();
}

$pdo = new PDO("mysql:host=localhost;dbname=parking_system;charset=utf8", "root", "");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'], $_POST['penalty_amount'])) {
    $car_id = intval($_POST['id']);
    $penalty_amount = floatval($_POST['penalty_amount']);
    $cashier_name = $_SESSION['full_name'];
    $pay_time = date("H:i:s");
    $pay_date = date("Y-m-d");

    // تحديث بيانات السيارة وتسجيل السداد
    $stmt = $pdo->prepare("UPDATE overnight_cars 
        SET penalty_paid = 1, penalty_amount = ?, cashier_name = ?, pay_time = ?, pay_date = ?
        WHERE id = ?");
    $stmt->execute([
        $penalty_amount,
        $cashier_name,
        $pay_time,
        $pay_date,
        $car_id
    ]);

    // رجوع للصفحة الرئيسية
    header("Location: overnight.php");
    exit();
} else {
    echo "❌ بيانات غير صالحة!";
}
?>
