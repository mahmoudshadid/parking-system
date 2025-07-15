<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit();
}

$pdo = new PDO(
    "mysql:host=localhost;dbname=parking_system;charset=utf8mb4",
    "root",
    "",
    [
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_general_ci"
    ]
);

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// استقبال الفلاتر
$employeeName   = $_GET['employee_name'] ?? '';
$supervisorName = $_GET['supervisor_name'] ?? '';
$status         = $_GET['status'] ?? '';
$fromDate       = $_GET['from'] ?? '';
$toDate         = $_GET['to'] ?? '';

// بناء شرط WHERE
$conditions = ["type = 'lost'"];
$params = [];

if ($employeeName) {
    $conditions[] = "employee_name LIKE ?";
    $params[] = "%$employeeName%";
}

if ($supervisorName) {
    $conditions[] = "supervisor_name LIKE ?";
    $params[] = "%$supervisorName%";
}

if ($status) {
    $conditions[] = "status = ?";
    $params[] = $status;
}

if ($fromDate) {
    $conditions[] = "DATE(created_at) >= ?";
    $params[] = $fromDate;
}

if ($toDate) {
    $conditions[] = "DATE(created_at) <= ?";
    $params[] = $toDate;
}

$where = "WHERE " . implode(" AND ", $conditions);

$query = "SELECT * FROM manual_requests $where ORDER BY created_at DESC";
$stmt  = $pdo->prepare($query);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// تجهيز بيانات التصدير
$exportData = [];
foreach ($rows as $row) {
    $exportData[] = [
        'رقم'         => $row['id'],
        'اسم الموظف'  => $row['employee_name'],
        'اسم المشرف'  => $row['supervisor_name'],
        'السعر'       => $row['lost_price'],
        'السبب'       => $row['reason'],
        'الحالة'      => $row['status'],
        'ملاحظات'     => $row['notes'],
        'التاريخ'     => $row['created_at']
    ];
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تقرير الطلبات الفاقدة</title>
    <style>
        body { font-family: Arial; background: #f2f2f2; padding: 20px; direction: rtl; }
        table { width: 100%; border-collapse: collapse; background: #fff; }
        th, td { border: 1px solid #ccc; padding: 10px; text-align: center; }
        th { background: #3498db; color: #fff; }
        form { margin-bottom: 20px; background: #fff; padding: 10px; border-radius: 8px; display: inline-block; }
        input, select, button { margin: 5px; padding: 6px; }
        .export-buttons { margin: 10px 0; }
    </style>
</head>
<body>

<h2>📄 تقرير الطلبات الفاقدة</h2>

<form method="GET">
    <input type="text" name="employee_name" placeholder="اسم الموظف" value="<?= htmlspecialchars($employeeName) ?>">
    <input type="text" name="supervisor_name" placeholder="اسم المشرف" value="<?= htmlspecialchars($supervisorName) ?>">
    
    <select name="status">
        <option value="">الحالة</option>
        <option value="موافق عليه" <?= $status == 'موافق عليه' ? 'selected' : '' ?>>موافق عليه</option>
        <option value="مرفوض" <?= $status == 'مرفوض' ? 'selected' : '' ?>>مرفوض</option>
        <option value="معلق" <?= $status == 'معلق' ? 'selected' : '' ?>>معلق</option>
    </select>

    من: <input type="date" name="from" value="<?= htmlspecialchars($fromDate) ?>">
    إلى: <input type="date" name="to" value="<?= htmlspecialchars($toDate) ?>">

    <button type="submit">🔍 بحث</button>
    <a href="lost_report.php"><button type="button">🔄 إعادة تعيين</button></a>
</form>

<?php if ($rows): ?>
<div class="export-buttons">
    <form method="POST" action="export/export_helper.php" target="_blank">
        <input type="hidden" name="export_type" value="excel">
        <input type="hidden" name="report_title" value="تقرير الطلبات الفاقدة">
        <input type="hidden" name="table_data" value='<?= json_encode($exportData, JSON_UNESCAPED_UNICODE) ?>'>
        <button>📥 تصدير Excel</button>
    </form>

    <form method="POST" action="export/export_helper.php" target="_blank">
        <input type="hidden" name="export_type" value="pdf">
        <input type="hidden" name="report_title" value="تقرير الطلبات الفاقدة">
        <input type="hidden" name="table_data" value='<?= json_encode($exportData, JSON_UNESCAPED_UNICODE) ?>'>
        <button>📄 تصدير PDF</button>
    </form>
</div>
<?php endif; ?>

<?php if ($rows): ?>
<table>
    <thead>
        <tr>
            <th>رقم</th>
            <th>اسم الموظف</th>
            <th>اسم المشرف</th>
            <th>السعر</th>
            <th>السبب</th>
            <th>الحالة</th>
            <th>ملاحظات</th>
            <th>التاريخ</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($rows as $row): ?>
        <tr>
            <td><?= $row['id'] ?></td>
            <td><?= htmlspecialchars($row['employee_name']) ?></td>
            <td><?= htmlspecialchars($row['supervisor_name']) ?></td>
            <td><?= $row['lost_price'] ?></td>
            <td><?= htmlspecialchars($row['reason']) ?></td>
            <td><?= htmlspecialchars($row['status']) ?></td>
            <td><?= htmlspecialchars($row['notes']) ?></td>
            <td><?= $row['created_at'] ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php else: ?>
    <p>لا توجد نتائج مطابقة.</p>
<?php endif; ?>

</body>
</html>
