<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit();
}

$pdo = new PDO("mysql:host=localhost;dbname=parking_system;charset=utf8", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// استلام الفلاتر
$cardNumber   = $_GET['card_number'] ?? '';
$employeeName = $_GET['employee_name'] ?? '';
$actionType   = $_GET['action_type'] ?? '';
$fromDate     = $_GET['from'] ?? '';
$toDate       = $_GET['to'] ?? '';

// بناء الشرط
$conditions = [];
$params = [];

if ($cardNumber) {
    $conditions[] = "card_number LIKE ?";
    $params[] = "%$cardNumber%";
}
if ($employeeName) {
    $conditions[] = "employee_name LIKE ?";
    $params[] = "%$employeeName%";
}
if ($actionType) {
    $conditions[] = "action_type = ?";
    $params[] = $actionType;
}
if ($fromDate) {
    $conditions[] = "DATE(action_time) >= ?";
    $params[] = $fromDate;
}
if ($toDate) {
    $conditions[] = "DATE(action_time) <= ?";
    $params[] = $toDate;
}

$where = $conditions ? "WHERE " . implode(" AND ", $conditions) : "";

// استعلام الاستخدامات
$query = "SELECT card_number, action_type, employee_name, action_time
          FROM master_cards_logs
          $where
          ORDER BY action_time DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// تجهيز بيانات التصدير
$exportData = [];
foreach ($logs as $row) {
    $exportData[] = [
        'رقم الكارت' => $row['card_number'],
        'النوع'      => $row['action_type'] == 'entry' ? 'دخول' : 'خروج',
        'الموظف'     => $row['employee_name'],
        'الوقت'      => $row['action_time']
    ];
}

// استعلام الإحصائيات
$statsQuery = "SELECT card_number, COUNT(*) AS usage_count
               FROM master_cards_logs
               $where
               GROUP BY card_number
               ORDER BY usage_count DESC
               LIMIT 10";
$stmtStats = $pdo->prepare($statsQuery);
$stmtStats->execute($params);
$mostUsedCards = $stmtStats->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تقرير كروت الماستر</title>
    <style>
        body { font-family: Arial; background: #f9f9f9; padding: 20px; }
        h2, h3 { text-align: center; margin-bottom: 20px; }
        form { background: #fff; padding: 15px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 0 5px #ccc; }
        input, select, button { padding: 7px; margin: 5px; }
        table { width: 100%; border-collapse: collapse; background: #fff; margin-bottom: 30px; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: center; }
        th { background: #3498db; color: white; }
        .export-buttons { margin-top: 15px; text-align: center; }
        .export-buttons form { display: inline-block; margin: 0 10px; }
    </style>
</head>
<body>

<h2>تقرير استخدام كروت الماستر</h2>

<form method="GET">
    <input type="text" name="card_number" placeholder="رقم الكارت" value="<?= htmlspecialchars($cardNumber) ?>">
    <input type="text" name="employee_name" placeholder="اسم الموظف" value="<?= htmlspecialchars($employeeName) ?>">
    
    <select name="action_type">
        <option value="">الكل</option>
        <option value="entry" <?= $actionType == 'entry' ? 'selected' : '' ?>>دخول</option>
        <option value="exit" <?= $actionType == 'exit' ? 'selected' : '' ?>>خروج</option>
    </select>

    من: <input type="date" name="from" value="<?= htmlspecialchars($fromDate) ?>">
    إلى: <input type="date" name="to" value="<?= htmlspecialchars($toDate) ?>">

    <button type="submit">🔍 بحث</button>
    <a href="master_cards.php"><button type="button">🔄 إعادة تعيين</button></a>
</form>

<?php if ($logs): ?>
<div class="export-buttons">
    <form method="POST" action="export/export_helper.php" target="_blank">
        <input type="hidden" name="export_type" value="excel">
        <input type="hidden" name="report_title" value="تقرير استخدام كروت الماستر">
        <input type="hidden" name="table_data" value='<?= json_encode($exportData, JSON_UNESCAPED_UNICODE) ?>'>
        <button>📥 تصدير Excel</button>
    </form>

    <form method="POST" action="export/export_helper.php" target="_blank">
        <input type="hidden" name="export_type" value="pdf">
        <input type="hidden" name="report_title" value="تقرير استخدام كروت الماستر">
        <input type="hidden" name="table_data" value='<?= json_encode($exportData, JSON_UNESCAPED_UNICODE) ?>'>
        <button>📄 تصدير PDF</button>
    </form>
</div>
<?php endif; ?>

<?php if ($logs): ?>
    <table>
        <thead>
            <tr>
                <th>رقم الكارت</th>
                <th>النوع</th>
                <th>الموظف</th>
                <th>الوقت</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($logs as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['card_number']) ?></td>
                    <td><?= $row['action_type'] == 'entry' ? 'دخول' : 'خروج' ?></td>
                    <td><?= htmlspecialchars($row['employee_name']) ?></td>
                    <td><?= htmlspecialchars($row['action_time']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>لا توجد نتائج مطابقة.</p>
<?php endif; ?>

<h3>📈 أكثر كروت الماستر استخدامًا</h3>

<?php if ($mostUsedCards): ?>
    <table>
        <thead>
            <tr>
                <th>الترتيب</th>
                <th>رقم الكارت</th>
                <th>عدد الاستخدام</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($mostUsedCards as $index => $row): ?>
                <tr>
                    <td><?= $index + 1 ?></td>
                    <td><?= htmlspecialchars($row['card_number']) ?></td>
                    <td><?= $row['usage_count'] ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>لا توجد بيانات للإحصائيات.</p>
<?php endif; ?>

</body>
</html>
