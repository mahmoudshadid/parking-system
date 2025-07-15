<?php
// عرض الأخطاء للتصحيح
ini_set('display_errors', 1);
error_reporting(E_ALL);

// إعداد الاتصال بقاعدة البيانات
$pdo = new PDO("mysql:host=localhost;dbname=parking_system;charset=utf8", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// حساب الوقت من 10 دقايق مضت
$since = date('Y-m-d H:i:s', strtotime('-10 minutes'));

$notifications = [];

// إشعارات الدخول والخروج
$stmt = $pdo->prepare("SELECT card_number, action_type, employee_name FROM master_cards_logs WHERE action_time >= ?");
$stmt->execute([$since]);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $notifications[] = "كرت {$row['card_number']} - {$row['action_type']} بواسطة {$row['employee_name']}";
}

// إشعارات المنويل والوسط
$stmt = $pdo->prepare("SELECT type, employee_name FROM manual_requests WHERE created_at >= ?");
$stmt->execute([$since]);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $notifications[] = "طلب {$row['type']} من {$row['employee_name']}";
}

// الكروت الزيادة
$stmt = $pdo->prepare("SELECT card_type, card_count, employee_name FROM extra_cards WHERE CONCAT(date, ' ', time) >= ?");
$stmt->execute([$since]);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $notifications[] = "إضافة {$row['card_count']} كروت من نوع {$row['card_type']} بواسطة {$row['employee_name']}";
}

// سيارات المبيت
$stmt = $pdo->prepare("SELECT car_number, location, created_by FROM overnight_vehicles WHERE created_at >= ?");
$stmt->execute([$since]);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $notifications[] = "🚗 سيارة {$row['car_number']} تم تسجيلها للمبيت في {$row['location']} بواسطة {$row['created_by']}";
}

// إرسال البيانات كـ JSON
header('Content-Type: application/json; charset=utf-8');
echo json_encode($notifications);
