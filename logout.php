<?php
session_start();

if (isset($_SESSION['user_id'])) {
    // الاتصال بقاعدة البيانات
    $pdo = new PDO("mysql:host=localhost;dbname=parking_system;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // تحديث حالة الاتصال للمستخدم الحالي إلى غير متصل
    $stmt = $pdo->prepare("UPDATE users SET is_logged_in = 0 WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
}

// إنهاء الجلسة
session_unset();
session_destroy();

// إعادة التوجيه إلى صفحة تسجيل الدخول
header("Location: login.php");
exit();
