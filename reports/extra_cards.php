<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit();
}

$pdo = new PDO("mysql:host=localhost;dbname=parking_system;charset=utf8", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// استقبال الفلاتر
$employeeName = $_GET['employee_name'] ?? '';
$employeeRole = $_GET['employee_role'] ?? '';
$cardType     = $_GET['card_type'] ?? '';
$fromDate     = $_GET['from'] ?? '';
$toDate       = $_GET['to'] ?? '';

// بناء الشرط
$conditions = [];
$params = [];

if ($employeeName) {
    $conditions[] = "employee_name LIKE ?";
    $params[] = "%$employeeName%";
}
if ($employeeRole) {
    $conditions[] = "employee_role LIKE ?";
    $params[] = "%$employeeRole%";
}
if ($cardType) {
    $conditions[] = "card_type = ?";
    $params[] = $cardType;
}
if ($fromDate) {
    $conditions[] = "DATE(date) >= ?";
    $params[] = $fromDate;
}
if ($toDate) {
    $conditions[] = "DATE(date) <= ?";
    $params[] = $toDate;
}

$where = $conditions ? "WHERE " . implode(" AND ", $conditions) : "";

$query = "SELECT * FROM extra_cards $where ORDER BY date DESC, time DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// تجهيز البيانات للتصدير
$reportData = [];
foreach ($rows as $row) {
    $reportData[] = [
        'رقم'            => $row['id'],
        'اسم الموظف'     => $row['employee_name'],
        'الوظيفة'         => $row['employee_role'],
        'الموقع'         => $row['service_location'],
        'نوع الكارت'     => $row['card_type'],
        'عدد الكروت'     => $row['card_count'],
        'التاريخ'        => $row['date'],
        'الوقت'          => $row['time'],
    ];
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تقرير الكروت الزيادة والفري</title>
    <style>
        body { font-family: Arial; background: #f8f8f8; padding: 20px; }
        table { width: 100%; border-collapse: collapse; background: #fff; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: center; }
        th { background: #2c3e50; color: #fff; }
        form { background: #fff; padding: 15px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 0 5px #ccc; }
        input, select, button { padding: 6px; margin: 5px; }
    </style>
</head>
<body>

<h2>تقرير الكروت الزيادة والفري</h2>

<form method="GET">
    <input type="text" name="employee_name" placeholder="اسم الموظف" value="<?= htmlspecialchars($employeeName) ?>">
    <input type="text" name="employee_role" placeholder="الوظيفة" value="<?= htmlspecialchars($employeeRole) ?>">

    <select name="card_type">
        <option value="">نوع الكارت</option>
        <option value="زيادة" <?= $cardType == 'زيادة' ? 'selected' : '' ?>>زيادة</option>
        <option value="فري" <?= $cardType == 'فري' ? 'selected' : '' ?>>فري</option>
    </select>

    من: <input type="date" name="from" value="<?= htmlspecialchars($fromDate) ?>">
    إلى: <input type="date" name="to" value="<?= htmlspecialchars($toDate) ?>">

    <button type="submit">🔍 بحث</button>
    <a href="extra_cards.php"><button type="button">🔄 إعادة تعيين</button></a>
</form>

<?php if ($rows): ?>
    <div class="export-buttons" style="margin-bottom: 15px;">
        <form method="POST" action="export/export_helper.php" target="_blank" style="display:inline-block;">
            <input type="hidden" name="export_type" value="excel">
            <input type="hidden" name="report_title" value="تقرير الكروت الزيادة والفري">
            <input type="hidden" name="table_data" value='<?= json_encode($reportData, JSON_UNESCAPED_UNICODE) ?>'>
            <button type="submit">📥 تصدير Excel</button>
        </form>

        <form method="POST" action="export/export_helper.php" target="_blank" style="display:inline-block;">
            <input type="hidden" name="export_type" value="pdf">
            <input type="hidden" name="report_title" value="تقرير الكروت الزيادة والفري">
            <input type="hidden" name="table_data" value='<?= json_encode($reportData, JSON_UNESCAPED_UNICODE) ?>'>
            <button type="submit">📄 تصدير PDF</button>
        </form>
    </div>

    <table>
        <thead>
            <tr>
                <th>رقم</th>
                <th>اسم الموظف</th>
                <th>الوظيفة</th>
                <th>الموقع</th>
                <th>نوع الكارت</th>
                <th>عدد الكروت</th>
                <th>التاريخ</th>
                <th>الوقت</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $row): ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= htmlspecialchars($row['employee_name']) ?></td>
                <td><?= htmlspecialchars($row['employee_role']) ?></td>
                <td><?= htmlspecialchars($row['service_location']) ?></td>
                <td><?= htmlspecialchars($row['card_type']) ?></td>
                <td><?= $row['card_count'] ?></td>
                <td><?= $row['date'] ?></td>
                <td><?= $row['time'] ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>لا توجد نتائج مطابقة.</p>
<?php endif; ?>

</body>
</html>
