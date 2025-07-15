<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit();
}

$pdo = new PDO("mysql:host=localhost;dbname=parking_system;charset=utf8", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// الفلترة
$fromDate = $_GET['from'] ?? '';
$toDate   = $_GET['to'] ?? '';
$search   = $_GET['search'] ?? '';

$query = "SELECT * FROM manual_requests WHERE 1";
$params = [];

if ($fromDate && $toDate) {
    $query .= " AND DATE(created_at) BETWEEN ? AND ?";
    $params[] = $fromDate;
    $params[] = $toDate;
}

if ($search) {
    $query .= " AND (
        type LIKE ? OR reason LIKE ? OR employee_name LIKE ? OR supervisor_name LIKE ?
    )";
    for ($i = 0; $i < 4; $i++) {
        $params[] = "%$search%";
    }
}

$query .= " ORDER BY created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// تجهيز البيانات للتصدير
$exportData = [];
foreach ($results as $row) {
    $exportData[] = [
        'النوع'      => $row['type'],
        'السبب'      => $row['reason'],
        'السعر'      => $row['lost_price'],
        'الموظف'     => $row['employee_name'],
        'المشرف'     => $row['supervisor_name'],
        'الحالة'     => $row['status'],
        'ملاحظات'    => $row['notes'],
        'التاريخ'    => $row['created_at']
    ];
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تقرير طلبات المنوال</title>
    <style>
        body { font-family: Tahoma; background: #f8f9fa; padding: 30px; direction: rtl; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; background: white; }
        th, td { border: 1px solid #ccc; padding: 10px; font-size: 14px; text-align: center; }
        th { background: #1abc9c; color: white; }
        form { margin-bottom: 20px; display: inline-block; }
        input[type="date"], input[type="text"] {
            padding: 6px;
            margin-left: 10px;
        }
        button {
            padding: 6px 12px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .back {
            margin-bottom: 20px;
            display: inline-block;
            text-decoration: none;
            background: #7f8c8d;
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
        }
        .export-buttons {
            margin-top: 10px;
        }
        .export-buttons form {
            display: inline-block;
            margin-left: 10px;
        }
    </style>
</head>
<body>

<h2>📄 تقرير طلبات المنوال</h2>
<a class="back" href="../reports.php">↩ الرجوع للتقارير</a>

<form method="GET">
    من: <input type="date" name="from" value="<?= htmlspecialchars($fromDate) ?>">
    إلى: <input type="date" name="to" value="<?= htmlspecialchars($toDate) ?>">
    بحث: <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="كلمة مفتاحية...">
    <button type="submit">عرض</button>
</form>

<?php if ($results): ?>
    <div class="export-buttons">
        <form method="POST" action="export/export_helper.php" target="_blank">
            <input type="hidden" name="export_type" value="excel">
            <input type="hidden" name="report_title" value="تقرير طلبات المنوال">
            <input type="hidden" name="table_data" value='<?= json_encode($exportData, JSON_UNESCAPED_UNICODE) ?>'>
            <button>📤 تصدير Excel</button>
        </form>

        <form method="POST" action="export/export_helper.php" target="_blank">
            <input type="hidden" name="export_type" value="pdf">
            <input type="hidden" name="report_title" value="تقرير طلبات المنوال">
            <input type="hidden" name="table_data" value='<?= json_encode($exportData, JSON_UNESCAPED_UNICODE) ?>'>
            <button>📄 تصدير PDF</button>
        </form>
    </div>
<?php endif; ?>

<table>
    <thead>
        <tr>
            <th>النوع</th>
            <th>السبب</th>
            <th>السعر</th>
            <th>الموظف</th>
            <th>المشرف</th>
            <th>الحالة</th>
            <th>ملاحظات</th>
            <th>التاريخ</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($results): ?>
            <?php foreach ($results as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['type']) ?></td>
                    <td><?= htmlspecialchars($row['reason']) ?></td>
                    <td><?= htmlspecialchars($row['lost_price']) ?></td>
                    <td><?= htmlspecialchars($row['employee_name']) ?></td>
                    <td><?= htmlspecialchars($row['supervisor_name']) ?></td>
                    <td><?= htmlspecialchars($row['status']) ?></td>
                    <td><?= htmlspecialchars($row['notes']) ?></td>
                    <td><?= htmlspecialchars($row['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="8">لا توجد نتائج لعرضها.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

</body>
</html>
