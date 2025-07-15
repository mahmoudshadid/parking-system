<?php
require_once 'includes/auth.php';
require_once 'includes/permissions.php';

$allowed_roles = ['entry', 'cashier', 'admin', 'supervisor'];
if (!in_array($_SESSION['role'], $allowed_roles)) {
    die("🚫 لا يمكنك الدخول إلى هذه الصفحة.");
}

$pdo = new PDO("mysql:host=localhost;dbname=parking_system", "root", "");

$role       = $_SESSION['role'];
$full_name  = $_SESSION['full_name'];
$time_now   = date("Y-m-d H:i:s");
$today      = date("Y-m-d");

$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['card_number'])) {
    $card_number = $_POST['card_number'];
    $type = ($role === 'entry') ? 'entry' : 'cashier';

    $stmt = $pdo->prepare("INSERT INTO master_cards_logs (card_number, action_type, employee_name, action_time) VALUES (?, ?, ?, ?)");
    $stmt->execute([$card_number, $type, $full_name, $time_now]);
    $success = true;
}

// جلب الكروت
$stmt = $pdo->query("SELECT card_number FROM master_cards ORDER BY card_number ASC");
$cards = $stmt->fetchAll(PDO::FETCH_COLUMN);

// جلب استخدامات اليوم فقط
$cardUsageCounts = [];
if (in_array($role, ['admin', 'supervisor'])) {
    $stmt = $pdo->prepare("SELECT card_number, COUNT(*) AS count FROM master_cards_logs WHERE DATE(action_time) = ? GROUP BY card_number");
    $stmt->execute([$today]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $cardUsageCounts[$row['card_number']] = $row['count'];
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>كروت المستر</title>
    <style>
        body { font-family: Arial; padding: 20px; direction: rtl; background: #f7f7f7; }
        .card-container { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 20px; }
        .card-box {
            background: #fff;
            border: 2px solid #3498db;
            border-radius: 10px;
            padding: 20px 10px;
            text-align: center;
            width: 100px;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: 0.2s;
            position: relative;
        }
        .card-box:hover { background: #3498db; color: #fff; }
        .success { color: green; margin-top: 10px; }
        .info { margin-top: 10px; font-size: 16px; }
        .admin-buttons { margin-top: 20px; }
        .admin-buttons a {
            padding: 10px 15px;
            background: #e67e22;
            color: #fff;
            border-radius: 5px;
            margin-left: 10px;
            text-decoration: none;
        }
        .green { background-color: #d4edda; border-color: #28a745; }
        .yellow { background-color: #fff3cd; border-color: #ffc107; }
        .usage-count {
            margin-top: 5px;
            font-size: 12px;
            color: #555;
        }
    </style>
</head>
<body>

<h2>كروت المستر - <?= $role === 'entry' ? 'موظف دخول' : ($role === 'cashier' ? 'موظف كاشير' : 'مدير') ?></h2>

<div class="info">
    👤 اسم الموظف: <strong><?= htmlspecialchars($full_name) ?></strong><br>
    🕒 الوقت: <strong><?= $time_now ?></strong>
</div>

<?php if ($success): ?>
    <div class="success">✅ تم تسجيل الكارت بنجاح!</div>
<?php endif; ?>

<form method="POST">
    <div class="card-container">
        <?php foreach ($cards as $card): ?>
            <?php
                $extraClass = '';
                $usageToday = $cardUsageCounts[$card] ?? 0;

                if (in_array($role, ['admin', 'supervisor'])) {
                    $extraClass = ($usageToday >= 2) ? 'green' : ($usageToday === 1 ? 'yellow' : '');
                }
            ?>
            <button class="card-box <?= $extraClass ?>" type="submit" name="card_number" value="<?= htmlspecialchars($card) ?>">
                <?= htmlspecialchars($card) ?>
                <?php if (in_array($role, ['admin', 'supervisor'])): ?>
                    <div class="usage-count">اليوم: <?= $usageToday ?></div>
                <?php endif; ?>
            </button>
        <?php endforeach; ?>
    </div>
</form>

<?php if ($_SESSION['role'] === 'admin'): ?>
    <div class="admin-buttons">
        <a href="add_master_card.php">➕ إضافة كارت</a>
        <a href="edit_master_cards.php">🛠️ تعديل الكروت</a>
    </div>
<?php endif; ?>

</body>
</html>
