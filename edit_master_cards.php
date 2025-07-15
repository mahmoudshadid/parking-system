<?php
session_start();

// التحقق من أن المستخدم Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("🚫 غير مصرح لك بالدخول إلى هذه الصفحة.");
}

$pdo = new PDO("mysql:host=localhost;dbname=parking_system", "root", "");

// حذف كارت
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $pdo->prepare("DELETE FROM master_cards WHERE id = ?")->execute([$id]);
    header("Location: edit_master_cards.php");
    exit();
}

// تحديث كارت
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_id'])) {
    $id = intval($_POST['update_id']);
    $new_card = trim($_POST['card_number']);
    $stmt = $pdo->prepare("UPDATE master_cards SET card_number = ? WHERE id = ?");
    $stmt->execute([$new_card, $id]);
    header("Location: edit_master_cards.php");
    exit();
}

// جلب كل الكروت
$cards = $pdo->query("SELECT * FROM master_cards ORDER BY card_number ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تعديل كروت المستر</title>
    <style>
        body { font-family: Arial; background: #f4f4f4; padding: 20px; direction: rtl; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; background: #fff; }
        th, td { border: 1px solid #ccc; padding: 10px; text-align: center; }
        th { background: #3498db; color: white; }
        form { display: inline; }
        input[type="text"] { width: 80px; padding: 5px; }
        .actions button, .actions a {
            padding: 6px 10px;
            border: none;
            background: #2ecc71;
            color: white;
            cursor: pointer;
            border-radius: 5px;
        }
        .actions .delete {
            background: #e74c3c;
        }
    </style>
</head>
<body>

<h2>🛠️ تعديل كروت المستر</h2>

<table>
    <tr>
        <th>رقم</th>
        <th>رقم الكارت</th>
        <th>تاريخ الإضافة</th>
        <th>إجراءات</th>
    </tr>
    <?php foreach ($cards as $card): ?>
        <tr>
            <td><?= $card['id'] ?></td>
            <td>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="update_id" value="<?= $card['id'] ?>">
                    <input type="text" name="card_number" value="<?= htmlspecialchars($card['card_number']) ?>">
            </td>
            <td><?= $card['created_at'] ?></td>
            <td class="actions">
                    <button type="submit">💾 حفظ</button>
                </form>
                <a class="delete" href="?delete=<?= $card['id'] ?>" onclick="return confirm('هل أنت متأكد من حذف هذا الكارت؟')">🗑️ حذف</a>
            </td>
        </tr>
    <?php endforeach; ?>
</table>

</body>
</html>
