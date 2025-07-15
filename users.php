<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

// التأكد إن المستخدم "مدير"
if ($_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

// حذف مستخدم إذا تم الضغط على زر الحذف
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: users.php");
    exit();
}

// جلب كل المستخدمين
$stmt = $pdo->query("SELECT * FROM users ORDER BY id DESC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>المستخدمون</title>
    <style>
        body { font-family: Arial; background: #f4f4f4; padding: 20px; direction: rtl; }
        table { width: 100%; border-collapse: collapse; background: #fff; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: right; }
        th { background: #eee; }
        a.button { padding: 6px 10px; text-decoration: none; border-radius: 5px; }
        .edit { background: #3498db; color: white; }
        .delete { background: #e74c3c; color: white; }
        .add { background: #2ecc71; color: white; margin-bottom: 10px; display: inline-block; }
    </style>
</head>
<body>

<h2>إدارة المستخدمين</h2>

<a href="add_user.php" class="button add">➕ إضافة مستخدم جديد</a>

<table>
    <thead>
        <tr>
            <th>الرقم</th>
            <th>الاسم الكامل</th>
            <th>اسم الدخول</th>
            <th>الدور</th>
            <th>الصلاحيات</th>
            <th>تاريخ الإنشاء</th>
            <th>إجراءات</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($users as $user): ?>
        <tr>
            <td><?= $user['id'] ?></td>
            <td><?= htmlspecialchars($user['full_name']) ?></td>
            <td><?= htmlspecialchars($user['username']) ?></td>
            <td><?= htmlspecialchars($user['role']) ?></td>
            <td>
                <?php
                    if ($user['permissions']) {
                        $permissions = explode(',', $user['permissions']);
                        echo implode('، ', $permissions);
                    } else {
                        echo '-';
                    }
                ?>
            </td>
            <td><?= $user['created_at'] ?></td>
            <td>
                <a href="edit_user.php?id=<?= $user['id'] ?>" class="button edit">تعديل</a>
                <a href="users.php?delete=<?= $user['id'] ?>" class="button delete" onclick="return confirm('هل أنت متأكد من الحذف؟')">حذف</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

</body>
</html>
