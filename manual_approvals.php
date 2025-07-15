<?php
session_start();
if (!isset($_SESSION['username']) || !in_array($_SESSION['role'], ['admin', 'supervisor'])) {
    header("Location: dashboard.php");
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

$username = $_SESSION['username'];
$fullName = $_SESSION['full_name'];

// الموافقة أو الرفض
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['request_id']);
    $action = $_POST['action'];
    $notes = trim($_POST['notes']);

    if (in_array($action, ['approved', 'rejected'])) {
        $stmt = $pdo->prepare("UPDATE manual_requests SET status = ?, notes = ?, supervisor_name = ? WHERE id = ?");
        $stmt->execute([$action, $notes, $fullName, $id]);
    }
}

// جلب الطلبات المعلقة
$stmt = $pdo->query("SELECT * FROM manual_requests WHERE status = 'pending' ORDER BY created_at DESC");
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>مراجعة طلبات المنوال / اللوست</title>
    <style>
        body { font-family: Arial; background: #f9f9f9; padding: 20px; }
        .request { background: #fff; padding: 15px; margin-bottom: 20px; border: 1px solid #ddd; border-radius: 8px; }
        form { margin-top: 10px; }
        textarea { width: 100%; padding: 8px; margin-top: 5px; }
        button { margin-top: 10px; padding: 8px 12px; border: none; border-radius: 4px; cursor: pointer; }
        .approve { background-color: #2ecc71; color: #fff; }
        .reject { background-color: #e74c3c; color: #fff; }
        .label { font-weight: bold; }
    </style>
</head>
<body>

<h2>طلبات المنوال / اللوست المعلقة</h2>

<?php if (empty($requests)): ?>
    <p>لا توجد طلبات حالياً.</p>
<?php else: ?>
    <?php foreach ($requests as $req): ?>
        <div class="request">
            <p><span class="label">النوع:</span> <?= $req['type'] == 'manual' ? 'منوال' : 'لوست' ?></p>
            <p><span class="label">الموظف:</span> <?= htmlspecialchars($req['employee_name']) ?></p>
            <p><span class="label">الوقت:</span> <?= $req['created_at'] ?></p>

            <?php if ($req['type'] == 'manual'): ?>
                <p><span class="label">السبب:</span> <?= nl2br(htmlspecialchars($req['reason'])) ?></p>
            <?php else: ?>
                <p><span class="label">السعر:</span> <?= $req['lost_price'] ?> جنيه</p>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                <label>ملاحظات (اختياري):</label>
                <textarea name="notes" rows="2" placeholder="اكتب ملاحظات هنا..."></textarea><br>

                <button type="submit" name="action" value="approved" class="approve">✅ قبول</button>
                <button type="submit" name="action" value="rejected" class="reject" onclick="return confirm('هل تريد رفض الطلب؟')">❌ رفض</button>
            </form>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

</body>
</html>
