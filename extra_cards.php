<?php
session_start();

if (!isset($_SESSION['username']) || !in_array($_SESSION['role'], ['entry', 'cashier', 'supervisor', 'admin'])) {
    header("Location: login.php");
    exit();
}

$pdo = new PDO("mysql:host=localhost;dbname=parking_system;charset=utf8", "root", "");
$role = $_SESSION['role'];
$full_name = $_SESSION['full_name'];
$is_admin = in_array($role, ['admin', 'supervisor']);
$success = false;
$date = date("Y-m-d");
$time = date("H:i:s");

// Get latest service location for this user
$stmt = $pdo->prepare("SELECT location FROM shift_logs WHERE employee_name = ? ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$full_name]);
$location = $stmt->fetchColumn() ?: 'غير معروف';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $card_type = ($role === 'cashier') ? 'فري' : 'زيادة';
    $card_count = (int) $_POST['card_count'];

    $stmt = $pdo->prepare("INSERT INTO extra_cards (employee_name, employee_role, service_location, card_type, card_count, date, time) 
                           VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$full_name, $role, $location, $card_type, $card_count, $date, $time]);
    $success = true;
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تسجيل كروت <?= ($role === 'cashier') ? 'فري' : 'زيادة' ?></title>
    <style>
        body { font-family: Arial; background: #f9f9f9; padding: 20px; direction: rtl; }
        form { background: #fff; padding: 20px; max-width: 600px; margin: auto; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        label { display: block; margin-top: 10px; font-weight: bold; }
        input[type="number"] { width: 100%; padding: 10px; margin-top: 5px; }
        button { background: #3498db; color: white; border: none; padding: 10px 20px; border-radius: 5px; margin-top: 15px; }
        .success { text-align: center; color: green; font-weight: bold; margin-top: 10px; }
        .cards-container { display: flex; flex-wrap: wrap; gap: 20px; justify-content: center; margin-top: 30px; }
        .card { background: #fff; padding: 15px; border-radius: 10px; box-shadow: 0 0 5px rgba(0,0,0,0.2); width: 250px; }
        .card h3 { margin: 0 0 10px; }
        .card p { margin: 5px 0; font-size: 14px; }
    </style>
</head>
<body>

<h2 style="text-align:center;">📦 تسجيل كروت <?= ($role === 'cashier') ? 'فري' : 'زيادة' ?></h2>

<?php if ($success): ?>
    <div class="success">✅ تم تسجيل عدد الكروت بنجاح!</div>
<?php endif; ?>

<?php if (!$is_admin): ?>
    <form method="POST">
        <label>عدد كروت <?= ($role === 'cashier') ? 'الفري' : 'الزيادة' ?>:</label>
        <input type="number" name="card_count" required min="1">

        <p><strong>📍 مكان الخدمة:</strong> <?= htmlspecialchars($location) ?></p>

        <button type="submit" name="submit">💾 تسجيل</button>
    </form>
<?php else: ?>
    <h3 style="text-align:center;">📋 الكروت المسجلة من الموظفين</h3>
    <div class="cards-container">
        <?php
        $stmt = $pdo->query("SELECT * FROM extra_cards ORDER BY date DESC, time DESC");
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($records as $rec):
        ?>
        <div class="card">
            <h3>👤 <?= htmlspecialchars($rec['employee_name']) ?></h3>
            <p>📍 <?= htmlspecialchars($rec['service_location']) ?></p>
            <p>🧾 النوع: <?= htmlspecialchars($rec['card_type']) ?></p>
            <p>🔢 العدد: <?= $rec['card_count'] ?></p>
            <p>🕒 <?= date('H:i', strtotime($rec['time'])) ?> | 📅 <?= $rec['date'] ?></p>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

</body>
</html>
