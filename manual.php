<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$pdo = new PDO("mysql:host=localhost;dbname=parking_system;charset=utf8mb4", "root", "", [
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
]);

$username = $_SESSION['username'];
$fullName = $_SESSION['full_name'];
$role = $_SESSION['role'];

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'];

    if ($type == 'manual') {
        $reason = trim($_POST['reason']);
        if (empty($reason)) {
            $errors[] = "يرجى كتابة سبب المنوال.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO manual_requests (type, reason, employee_name) VALUES (?, ?, ?)");
            $stmt->execute(['manual', $reason, $fullName]);
            $success = true;
        }
    } elseif ($type == 'lost') {
        $price = intval($_POST['lost_price']);
        if (!in_array($price, [50, 100, 200])) {
            $errors[] = "سعر اللوست غير صحيح.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO manual_requests (type, lost_price, employee_name) VALUES (?, ?, ?)");
            $stmt->execute(['lost', $price, $fullName]);
            $success = true;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>طلب منوال / لوست</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f4f4f4; }
        .form-box { background: white; padding: 20px; max-width: 600px; margin: auto; border-radius: 10px; }
        input, select, textarea { width: 100%; padding: 10px; margin: 10px 0; }
        .hidden { display: none; }
        button { background: #3498db; color: white; padding: 10px; border: none; border-radius: 5px; }
        .success { color: green; }
        .error { color: red; }
    </style>
    <script>
        function toggleForm(val) {
            document.getElementById('manualForm').style.display = (val === 'manual') ? 'block' : 'none';
            document.getElementById('lostForm').style.display = (val === 'lost') ? 'block' : 'none';
        }
    </script>
</head>
<body>

<div class="form-box">
    <h2>تسجيل عملية منوال أو لوست</h2>

    <?php foreach ($errors as $e): ?>
        <p class="error"><?= htmlspecialchars($e) ?></p>
    <?php endforeach; ?>

    <?php if ($success): ?>
        <p class="success">✅ تم إرسال الطلب بنجاح، بانتظار موافقة المشرف.</p>
    <?php endif; ?>

    <form method="POST">
        <label>نوع العملية:</label>
        <select name="type" onchange="toggleForm(this.value)" required>
            <option value="">-- اختر --</option>
            <option value="manual">منوال</option>
            <option value="lost">لوست</option>
        </select>

        <div id="manualForm" class="hidden">
            <label>سبب المنوال:</label>
            <textarea name="reason" placeholder="اكتب السبب هنا"></textarea>
            <p>الموظف: <strong><?= htmlspecialchars($fullName) ?></strong></p>
            <p>الوقت: <strong><?= date("Y-m-d H:i:s") ?></strong></p>
        </div>

        <div id="lostForm" class="hidden">
            <label>سعر اللوست:</label>
            <select name="lost_price">
                <option value="50">50</option>
                <option value="100">100</option>
                <option value="200">200</option>
            </select>
            <p>الموظف: <strong><?= htmlspecialchars($fullName) ?></strong></p>
            <p>الوقت: <strong><?= date("Y-m-d H:i:s") ?></strong></p>
        </div>

        <button type="submit">إرسال الطلب</button>
    </form>
</div>

</body>
</html>
