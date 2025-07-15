<?php
session_start();
if (!isset($_SESSION['username']) || !in_array($_SESSION['role'], ['entry', 'cashier', 'supervisor', 'admin'])) {
    header("Location: login.php");
    exit();
}

$role = $_SESSION['role'];
$full_name = $_SESSION['full_name'];
$date = date("Y-m-d");
$time = date("H:i:s");
$is_admin = in_array($role, ['admin', 'supervisor']);

$pdo = new PDO("mysql:host=localhost;dbname=parking_system;charset=utf8", "root", "");
$success = false;

// كاشير
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $role === 'cashier' && isset($_POST['submit_cashier'])) {
    $stmt = $pdo->prepare("INSERT INTO cashier_shifts 
        (employee_name, service_location, visa_rolls, cashier_rolls, visa_device, cashier_device, visa_charger, light_status, fan_status, cashier_chair, kiosk_status, date, time)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $full_name,
        $_POST['service_location'],
        $_POST['visa_rolls'],
        $_POST['cashier_rolls'],
        $_POST['visa_device'],
        $_POST['cashier_device'],
        $_POST['visa_charger'],
        $_POST['light_status'],
        $_POST['fan_status'],
        $_POST['cashier_chair'],
        $_POST['kiosk_status'],
        $date,
        $time
    ]);
    $pdo->prepare("INSERT INTO shift_logs (employee_name, employee_role, location, created_at) VALUES (?, 'cashier', ?, NOW())")
        ->execute([$full_name, $_POST['service_location']]);
    $success = true;
}

// دخول
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $role === 'entry' && isset($_POST['submit_entry'])) {
    $stmt = $pdo->prepare("INSERT INTO entry_shifts 
        (employee_name, service_location, master_cards_count, ticket_rolls, barrier_status, ticket_machine_status, service_chair, date, time)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $full_name,
        $_POST['entry_service_location'],
        $_POST['master_cards_count'],
        $_POST['ticket_rolls'],
        $_POST['barrier_status'],
        $_POST['ticket_machine_status'],
        $_POST['service_chair'],
        $date,
        $time
    ]);
    $pdo->prepare("INSERT INTO shift_logs (employee_name, employee_role, location, created_at) VALUES (?, 'entry', ?, NOW())")
        ->execute([$full_name, $_POST['entry_service_location']]);
    $success = true;
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>استلام شيفت</title>
    <style>
        body { font-family: Arial; background: #f4f4f4; padding: 20px; direction: rtl; }
        form { background: #fff; padding: 20px; border-radius: 10px; max-width: 700px; margin: auto; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        label { display: block; margin-top: 10px; font-weight: bold; }
        select, input[type="number"] { width: 100%; padding: 8px; margin-top: 5px; }
        .status-group { display: flex; gap: 10px; margin-top: 5px; }
        .status-group label { font-weight: normal; }
        button { background: #3498db; color: white; padding: 10px 20px; border: none; border-radius: 5px; margin-top: 20px; }
        .success { color: green; font-weight: bold; margin: 10px auto; text-align: center; }
        .cards-container { display: flex; flex-wrap: wrap; gap: 20px; justify-content: center; margin-top: 30px; }
        .card { background: #fff; padding: 15px; border-radius: 10px; box-shadow: 0 0 5px rgba(0,0,0,0.2); width: 250px; }
        .card h3 { margin: 0 0 10px; }
        .card p { margin: 5px 0; font-size: 14px; }
    </style>
</head>
<body>

<h2 style="text-align:center;">📋 استلام شيفت - <?= ($role === 'cashier') ? 'موظف كاشير' : (($role === 'entry') ? 'موظف دخول' : 'مشرف / مدير') ?></h2>

<?php if ($success): ?>
    <div class="success">✅ تم تسجيل الشيفت بنجاح!</div>
<?php endif; ?>

<?php if ($is_admin): ?>
    <h3 style="text-align:center;">👥 قائمة الشيفتات المستلمة</h3>
    <div class="cards-container">
        <?php
        $stmt = $pdo->query("SELECT * FROM shift_logs ORDER BY created_at DESC");
        $shifts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($shifts as $shift):
        ?>
        <div class="card">
            <h3>👤 <?= htmlspecialchars($shift['employee_name']) ?></h3>
            <p>⏰ <?= date('H:i', strtotime($shift['created_at'])) ?> | 📅 <?= date('Y-m-d', strtotime($shift['created_at'])) ?></p>
            <p>🧑‍💼 الدور: <?= htmlspecialchars($shift['employee_role']) ?></p>
            <p>📍 المكان: <?= htmlspecialchars($shift['location']) ?></p>
        </div>
        <?php endforeach; ?>
    </div>

<?php elseif ($role === 'cashier'): ?>
    <form method="POST">
        <label>مكان الخدمة:</label>
        <select name="service_location">
            <option value="الرئيسي">الرئيسي</option>
            <option value="الطوارئ">الطوارئ</option>
        </select>

        <label>عدد رول الفيزا:</label>
        <input type="number" name="visa_rolls" required>

        <label>عدد رول الكاشير:</label>
        <input type="number" name="cashier_rolls" required>

        <label>🖥️ مكنة الفيزا:</label>
        <div class="status-group">
            <label><input type="radio" name="visa_device" value="✅" required> ✅</label>
            <label><input type="radio" name="visa_device" value="❌"> ❌</label>
        </div>

        <label>🖥️ جهاز الكاشير:</label>
        <div class="status-group">
            <label><input type="radio" name="cashier_device" value="✅" required> ✅</label>
            <label><input type="radio" name="cashier_device" value="❌"> ❌</label>
        </div>

        <label>🔌 شاحن الفيزا:</label>
        <div class="status-group">
            <label><input type="radio" name="visa_charger" value="✅" required> ✅</label>
            <label><input type="radio" name="visa_charger" value="❌"> ❌</label>
        </div>

        <label>💡 الإضاءة:</label>
        <div class="status-group">
            <label><input type="radio" name="light_status" value="✅" required> ✅</label>
            <label><input type="radio" name="light_status" value="❌"> ❌</label>
        </div>

        <label>🌀 المروحة:</label>
        <div class="status-group">
            <label><input type="radio" name="fan_status" value="✅" required> ✅</label>
            <label><input type="radio" name="fan_status" value="❌"> ❌</label>
        </div>

        <label>🪑 كرسي الكاشير:</label>
        <div class="status-group">
            <label><input type="radio" name="cashier_chair" value="✅" required> ✅</label>
            <label><input type="radio" name="cashier_chair" value="❌"> ❌</label>
        </div>

        <label>📦 حالة الكشك:</label>
        <div class="status-group">
            <label><input type="radio" name="kiosk_status" value="✅" required> ✅</label>
            <label><input type="radio" name="kiosk_status" value="❌"> ❌</label>
        </div>

        <button type="submit" name="submit_cashier">💾 تسجيل استلام الشيفت</button>
    </form>

<?php elseif ($role === 'entry'): ?>
    <form method="POST">
        <label>عدد كروت المستر:</label>
        <input type="number" name="master_cards_count" required>

        <label>مكان الخدمة:</label>
        <select name="entry_service_location">
            <option value="رامب الرحاب">رامب الرحاب</option>
            <option value="رامب العيادات">رامب العيادات</option>
        </select>

        <label>عدد رول تيكت احتياطي:</label>
        <input type="number" name="ticket_rolls" required>

        <label>⚙️ حالة الدراع:</label>
        <div class="status-group">
            <label><input type="radio" name="barrier_status" value="✅" required> ✅</label>
            <label><input type="radio" name="barrier_status" value="❌"> ❌</label>
        </div>

        <label>🖨️ حالة ماكينة التيكت:</label>
        <div class="status-group">
            <label><input type="radio" name="ticket_machine_status" value="✅" required> ✅</label>
            <label><input type="radio" name="ticket_machine_status" value="❌"> ❌</label>
        </div>

        <label>🪑 كرسي الخدمة:</label>
        <div class="status-group">
            <label><input type="radio" name="service_chair" value="✅" required> ✅</label>
            <label><input type="radio" name="service_chair" value="❌"> ❌</label>
        </div>

        <button type="submit" name="submit_entry">💾 تسجيل استلام الشيفت</button>
    </form>
<?php endif; ?>

</body>
</html>
