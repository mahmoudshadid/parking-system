<?php
session_start();
if (!isset($_SESSION['username']) || !in_array($_SESSION['role'], ['entry', 'cashier', 'supervisor', 'admin'])) {
    header("Location: login.php");
    exit();
}

$pdo = new PDO("mysql:host=localhost;dbname=parking_system;charset=utf8", "root", "");
$role = $_SESSION['role'];
$full_name = $_SESSION['full_name'];
$date = date("Y-m-d H:i:s");

// موظف الدخول - تسجيل بيانات المبيت
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_entry']) && $role === 'entry') {
    $image_path = null;
    if (!empty($_FILES['car_image']['tmp_name'])) {
        $target_dir = "uploads/";
        $image_path = $target_dir . basename($_FILES["car_image"]["name"]);
        move_uploaded_file($_FILES["car_image"]["tmp_name"], $image_path);
    }

    $stmt = $pdo->prepare("INSERT INTO overnight_vehicles 
        (car_number, car_color, car_type, car_image, location, column_number, owner_name, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $_POST['car_number'],
        $_POST['car_color'],
        $_POST['car_type'],
        $image_path,
        $_POST['location'],
        $_POST['column_number'],
        $_POST['owner_name'],
        $full_name
    ]);
}

// موظف الكاشير - سداد الغرامة
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_payment']) && $role === 'cashier') {
    $stmt = $pdo->prepare("UPDATE overnight_vehicles SET is_paid = 1, fine_amount = ?, paid_by = ?, paid_at = NOW() WHERE id = ?");
    $stmt->execute([$_POST['fine_amount'], $full_name, $_POST['vehicle_id']]);
}

$vehicles = $pdo->query("SELECT * FROM overnight_vehicles ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>سيارات المبيت</title>
    <style>
        body { font-family: Arial; background: #f7f7f7; padding: 20px; direction: rtl; }
        form, .card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); margin-bottom: 20px; }
        input, select { width: 100%; padding: 8px; margin-top: 5px; margin-bottom: 10px; }
        label { font-weight: bold; }
        .card { margin-top: 10px; }
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
        .card img { max-width: 100%; height: auto; border-radius: 5px; }
        .paid { background: #d4edda; }
        .unpaid { background: #f8d7da; }
    </style>
</head>
<body>

<h2>🚗 سيارات المبيت</h2>

<?php if ($role === 'entry'): ?>
    <form method="POST" enctype="multipart/form-data">
        <label>رقم السيارة:</label>
        <input type="text" name="car_number" required>

        <label>لون السيارة:</label>
        <input type="text" name="car_color" required>

        <label>نوع السيارة:</label>
        <input type="text" name="car_type" required>

        <label>صورة السيارة (اختياري):</label>
        <input type="file" name="car_image">

        <label>مكان السيارة:</label>
        <select name="location">
            <option value="B1">B1</option>
            <option value="B2">B2</option>
        </select>

        <label>رقم العمود:</label>
        <input type="text" name="column_number" required>

        <label>ملاحظات  :</label>
        <input type="text" name="owner_name" required>

        <button type="submit" name="submit_entry">📥 تسجيل السيارة</button>
    </form>
<?php endif; ?>

<div class="grid">
<?php foreach ($vehicles as $v): ?>
    <div class="card <?= $v['is_paid'] ? 'paid' : 'unpaid' ?>">
        <h3>🚗 <?= htmlspecialchars($v['car_number']) ?></h3>
        <p>🎨 اللون: <?= htmlspecialchars($v['car_color']) ?></p>
        <p>🚘 النوع: <?= htmlspecialchars($v['car_type']) ?></p>
        <p>📍 المكان: <?= htmlspecialchars($v['location']) ?> - عمود <?= htmlspecialchars($v['column_number']) ?></p>
        <p>👤 الاسم: <?= htmlspecialchars($v['owner_name']) ?></p>
        <p>🕒 تم التسجيل: <?= $v['created_at'] ?> بواسطة <?= $v['created_by'] ?></p>
        <?php if (!empty($v['car_image'])): ?>
            <img src="<?= htmlspecialchars($v['car_image']) ?>" alt="صورة السيارة">
        <?php endif; ?>

        <?php if ($role === 'cashier' && !$v['is_paid']): ?>
            <form method="POST">
                <input type="hidden" name="vehicle_id" value="<?= $v['id'] ?>">
                <label>💰 مبلغ الغرامة:</label>
                <input type="number" step="0.01" name="fine_amount" required>
                <button type="submit" name="submit_payment">✅ سداد</button>
            </form>
        <?php elseif ($v['is_paid']): ?>
            <p>💰 الغرامة: <?= $v['fine_amount'] ?> جنيه</p>
            <p>✅ تم السداد بواسطة <?= $v['paid_by'] ?> بتاريخ <?= $v['paid_at'] ?></p>
        <?php endif; ?>
    </div>
<?php endforeach; ?>
</div>

</body>
</html>
