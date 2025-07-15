<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$pdo = new PDO("mysql:host=localhost;dbname=parking_system;charset=utf8", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// جلب أسماء الموظفين
$users = $pdo->query("SELECT full_name FROM users ORDER BY full_name")->fetchAll(PDO::FETCH_COLUMN);

// فلتر الاسم
$filterName = $_GET['employee_name'] ?? '';

// شروط WHERE
$where = $filterName ? "WHERE employee_name = ?" : "";
$params = $filterName ? [$filterName] : [];

// بيانات cashier_shifts
$cashierStmt = $pdo->prepare("SELECT * FROM cashier_shifts $where ORDER BY date DESC, time DESC");
$cashierStmt->execute($params);
$cashierRows = $cashierStmt->fetchAll(PDO::FETCH_ASSOC);

// بيانات entry_shifts
$entryStmt = $pdo->prepare("SELECT * FROM entry_shifts $where ORDER BY date DESC, time DESC");
$entryStmt->execute($params);
$entryRows = $entryStmt->fetchAll(PDO::FETCH_ASSOC);

// تجهيز بيانات التصدير
$cashierExport = [];
foreach ($cashierRows as $row) {
    $cashierExport[] = [
        'الموظف'       => $row['employee_name'],
        'الموقع'       => $row['service_location'],
        'رولز الفيزا'   => $row['visa_rolls'],
        'رولز الكاشير' => $row['cashier_rolls'],
        'جهاز الفيزا'   => $row['visa_device'],
        'جهاز الكاشير' => $row['cashier_device'],
        'شاحن الفيزا'  => $row['visa_charger'],
        'الأنوار'      => $row['light_status'],
        'المروحة'      => $row['fan_status'],
        'كرسي الكاشير' => $row['cashier_chair'],
        'حالة الكشك'   => $row['kiosk_status'],
        'التاريخ'      => $row['date'],
        'الوقت'        => $row['time'],
    ];
}

$entryExport = [];
foreach ($entryRows as $row) {
    $entryExport[] = [
        'الموظف'            => $row['employee_name'],
        'الموقع'            => $row['service_location'],
        'عدد كروت الماستر'   => $row['master_cards_count'],
        'رولز التذاكر'      => $row['ticket_rolls'],
        'البوابة'           => $row['barrier_status'],
        'جهاز التذاكر'      => $row['ticket_machine_status'],
        'الكرسي'            => $row['service_chair'],
        'التاريخ'           => $row['date'],
        'الوقت'             => $row['time'],
    ];
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تقرير استلام الشيفت</title>
    <style>
        body { font-family: Tahoma; background: #f2f2f2; padding: 20px; direction: rtl; }
        h1 { text-align: center; }
        form { margin-bottom: 20px; background: #fff; padding: 15px; border-radius: 8px; box-shadow: 0 0 5px #ccc; }
        select, button { padding: 8px; margin-left: 10px; }
        .shift-box { margin-top: 30px; background: #fff; border-radius: 10px; padding: 15px; box-shadow: 0 0 5px #ccc; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 8px; border: 1px solid #ddd; text-align: center; font-size: 14px; }
        th { background-color: #2980b9; color: white; }
        .entry th { background-color: #27ae60; }
        .empty-msg { padding: 10px; color: #777; text-align: center; }
        .export-buttons { text-align: center; margin: 20px 0; }
        .export-buttons form { display: inline-block; margin: 0 10px; }
    </style>
</head>
<body>

<h1>📋 تقرير استلام الشيفت</h1>

<form method="GET">
    <label>فلتر حسب الموظف:</label>
    <select name="employee_name">
        <option value="">الكل</option>
        <?php foreach ($users as $name): ?>
            <option value="<?= htmlspecialchars($name) ?>" <?= $filterName == $name ? 'selected' : '' ?>>
                <?= htmlspecialchars($name) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <button type="submit">🔍 بحث</button>
    <a href="shift_logs.php"><button type="button">🔄 إعادة تعيين</button></a>
</form>

<?php if ($cashierRows || $entryRows): ?>
<div class="export-buttons">
    <form method="POST" action="export/export_helper.php" target="_blank">
        <input type="hidden" name="export_type" value="excel">
        <input type="hidden" name="report_title" value="شيفتات الكاشير">
        <input type="hidden" name="table_data" value='<?= json_encode($cashierExport, JSON_UNESCAPED_UNICODE) ?>'>
        <button>📥 تصدير الكاشير Excel</button>
    </form>

    <form method="POST" action="export/export_helper.php" target="_blank">
        <input type="hidden" name="export_type" value="excel">
        <input type="hidden" name="report_title" value="شيفتات موظف الدخول">
        <input type="hidden" name="table_data" value='<?= json_encode($entryExport, JSON_UNESCAPED_UNICODE) ?>'>
        <button>📥 تصدير الدخول Excel</button>
    </form>
</div>
<?php endif; ?>

<div class="shift-box">
    <h2>🧾 شيفتات الكاشير</h2>
    <?php if ($cashierRows): ?>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>الموظف</th>
                <th>الموقع</th>
                <th>رولز الفيزا</th>
                <th>رولز الكاشير</th>
                <th>جهاز الفيزا</th>
                <th>جهاز الكاشير</th>
                <th>شاحن الفيزا</th>
                <th>الأنوار</th>
                <th>المروحة</th>
                <th>كرسي الكاشير</th>
                <th>حالة الكشك</th>
                <th>التاريخ</th>
                <th>الوقت</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($cashierRows as $i => $row): ?>
                <tr>
                    <td><?= $i+1 ?></td>
                    <td><?= htmlspecialchars($row['employee_name']) ?></td>
                    <td><?= htmlspecialchars($row['service_location']) ?></td>
                    <td><?= $row['visa_rolls'] ?></td>
                    <td><?= $row['cashier_rolls'] ?></td>
                    <td><?= $row['visa_device'] ?></td>
                    <td><?= $row['cashier_device'] ?></td>
                    <td><?= $row['visa_charger'] ?></td>
                    <td><?= $row['light_status'] ?></td>
                    <td><?= $row['fan_status'] ?></td>
                    <td><?= $row['cashier_chair'] ?></td>
                    <td><?= $row['kiosk_status'] ?></td>
                    <td><?= $row['date'] ?></td>
                    <td><?= $row['time'] ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
        <div class="empty-msg">لا توجد بيانات.</div>
    <?php endif; ?>
</div>

<div class="shift-box">
    <h2 class="entry">🚧 شيفتات موظف الدخول</h2>
    <?php if ($entryRows): ?>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>الموظف</th>
                <th>الموقع</th>
                <th>عدد كروت الماستر</th>
                <th>رولز التذاكر</th>
                <th>البوابة</th>
                <th>جهاز التذاكر</th>
                <th>الكرسي</th>
                <th>التاريخ</th>
                <th>الوقت</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($entryRows as $i => $row): ?>
                <tr>
                    <td><?= $i+1 ?></td>
                    <td><?= htmlspecialchars($row['employee_name']) ?></td>
                    <td><?= htmlspecialchars($row['service_location']) ?></td>
                    <td><?= $row['master_cards_count'] ?></td>
                    <td><?= $row['ticket_rolls'] ?></td>
                    <td><?= $row['barrier_status'] ?></td>
                    <td><?= $row['ticket_machine_status'] ?></td>
                    <td><?= $row['service_chair'] ?></td>
                    <td><?= $row['date'] ?></td>
                    <td><?= $row['time'] ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
        <div class="empty-msg">لا توجد بيانات.</div>
    <?php endif; ?>
</div>

</body>
</html>
