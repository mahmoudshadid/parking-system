<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit();
}

$pdo = new PDO("mysql:host=localhost;dbname=parking_system;charset=utf8", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// استقبال الفلاتر
$carNumber  = $_GET['car_number'] ?? '';
$location   = $_GET['location'] ?? '';
$isPaid     = $_GET['is_paid'] ?? '';
$fromDate   = $_GET['from'] ?? '';
$toDate     = $_GET['to'] ?? '';

// بناء الشرط
$conditions = [];
$params = [];

if ($carNumber) {
    $conditions[] = "car_number LIKE ?";
    $params[] = "%$carNumber%";
}
if ($location) {
    $conditions[] = "location LIKE ?";
    $params[] = "%$location%";
}
if ($isPaid !== '') {
    $conditions[] = "is_paid = ?";
    $params[] = $isPaid;
}
if ($fromDate) {
    $conditions[] = "DATE(created_at) >= ?";
    $params[] = $fromDate;
}
if ($toDate) {
    $conditions[] = "DATE(created_at) <= ?";
    $params[] = $toDate;
}

$where = $conditions ? "WHERE " . implode(" AND ", $conditions) : "";

$query = "SELECT * FROM overnight_vehicles $where ORDER BY created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// تجهيز بيانات التصدير
$exportData = [];
foreach ($rows as $row) {
    $exportData[] = [
        'رقم السيارة'   => $row['car_number'],
        'اللون'         => $row['car_color'],
        'النوع'         => $row['car_type'],
        'الموقع'        => $row['location'],
        'العمود'        => $row['column_number'],
        'المالك'        => $row['owner_name'],
        'أنشئ بواسطة'   => $row['created_by'],
        'وقت الإدخال'   => $row['created_at'],
        'مدفوع؟'        => $row['is_paid'] ? 'نعم' : 'لا',
        'الغرامة (جنيه)' => number_format($row['fine_amount'], 2),
        'تم الدفع بواسطة' => $row['paid_by'] ?: '-',
        'وقت الدفع'      => $row['paid_at'] ?: '-'
    ];
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تقرير مبيت السيارات</title>
    <style>
        body { font-family: Arial; background: #f8f8f8; padding: 20px; }
        table { width: 100%; border-collapse: collapse; background: #fff; }
        th, td { padding: 8px; border: 1px solid #ccc; text-align: center; }
        th { background: #2c3e50; color: #fff; }
        form { background: #fff; padding: 10px; margin-bottom: 20px; border-radius: 8px; box-shadow: 0 0 5px #ccc; }
        input, select, button { padding: 6px; margin: 5px; }
        img { width: 70px; height: auto; border-radius: 4px; }
        .export-buttons { margin: 15px 0; text-align: center; }
        .export-buttons form { display: inline-block; margin: 0 10px; }
    </style>
</head>
<body>

<h2>تقرير مبيت السيارات</h2>

<form method="GET">
    <input type="text" name="car_number" placeholder="رقم السيارة" value="<?= htmlspecialchars($carNumber) ?>">
    <input type="text" name="location" placeholder="الموقع" value="<?= htmlspecialchars($location) ?>">

    <select name="is_paid">
        <option value="">هل مدفوع؟</option>
        <option value="1" <?= $isPaid === '1' ? 'selected' : '' ?>>نعم</option>
        <option value="0" <?= $isPaid === '0' ? 'selected' : '' ?>>لا</option>
    </select>

    من: <input type="date" name="from" value="<?= htmlspecialchars($fromDate) ?>">
    إلى: <input type="date" name="to" value="<?= htmlspecialchars($toDate) ?>">

    <button type="submit">🔍 بحث</button>
    <a href="overnight_report.php"><button type="button">🔄 إعادة تعيين</button></a>
</form>

<?php if ($rows): ?>
<div class="export-buttons">
    <form method="POST" action="export/export_helper.php" target="_blank">
        <input type="hidden" name="export_type" value="excel">
        <input type="hidden" name="report_title" value="تقرير مبيت السيارات">
        <input type="hidden" name="table_data" value='<?= json_encode($exportData, JSON_UNESCAPED_UNICODE) ?>'>
        <button>📥 تصدير Excel</button>
    </form>

    <form method="POST" action="export/export_helper.php" target="_blank">
        <input type="hidden" name="export_type" value="pdf">
        <input type="hidden" name="report_title" value="تقرير مبيت السيارات">
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
                <th>رقم السيارة</th>
                <th>اللون</th>
                <th>النوع</th>
                <th>صورة</th>
                <th>الموقع</th>
                <th>العمود</th>
                <th>المالك</th>
                <th>أنشئ بواسطة</th>
                <th>وقت الإدخال</th>
                <th>مدفوع؟</th>
                <th>الغرامة</th>
                <th>تم الدفع بواسطة</th>
                <th>وقت الدفع</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td><?= htmlspecialchars($row['car_number']) ?></td>
                    <td><?= htmlspecialchars($row['car_color']) ?></td>
                    <td><?= htmlspecialchars($row['car_type']) ?></td>
                    <td>
                        <?php if (!empty($row['car_image'])): ?>
                            <img src="../uploads/<?= $row['car_image'] ?>" alt="صورة">
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($row['location']) ?></td>
                    <td><?= htmlspecialchars($row['column_number']) ?></td>
                    <td><?= htmlspecialchars($row['owner_name']) ?></td>
                    <td><?= htmlspecialchars($row['created_by']) ?></td>
                    <td><?= htmlspecialchars($row['created_at']) ?></td>
                    <td><?= $row['is_paid'] ? '✅' : '❌' ?></td>
                    <td><?= number_format($row['fine_amount'], 2) ?> ج</td>
                    <td><?= htmlspecialchars($row['paid_by']) ?: '-' ?></td>
                    <td><?= htmlspecialchars($row['paid_at']) ?: '-' ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>لا توجد بيانات.</p>
<?php endif; ?>

</body>
</html>
