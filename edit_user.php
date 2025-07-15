<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

$pdo = new PDO("mysql:host=localhost;dbname=parking_system", "root", "");

$permissionsList = [
    'manual'           => 'المنويل والوسط',
    'master_cards'     => 'كروت المستر',
    'shift'            => 'استلام الشيفت',
    'extra_cards'      => 'الكروت الزيادة والفري',
    'users'            => 'المستخدمين',
    'reports'          => 'التقارير',
    'overnight'        => 'سيارات مبيت',
    'manual_approvals' => 'الموافقات' // ✅ تمت إضافتها
];

if (!isset($_GET['id'])) {
    header("Location: users.php");
    exit();
}

$id = intval($_GET['id']);
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("المستخدم غير موجود.");
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name  = trim($_POST['full_name']);
    $role       = $_POST['role'];
    $permissions = isset($_POST['permissions']) ? implode(',', $_POST['permissions']) : '';
    $password   = $_POST['password'];

    if (empty($full_name) || empty($role)) {
        $errors[] = "الاسم والدور مطلوبان.";
    } else {
        if (!empty($password)) {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $update = $pdo->prepare("UPDATE users SET full_name = ?, role = ?, permissions = ?, password = ? WHERE id = ?");
            $update->execute([$full_name, $role, $permissions, $hashed, $id]);
        } else {
            $update = $pdo->prepare("UPDATE users SET full_name = ?, role = ?, permissions = ? WHERE id = ?");
            $update->execute([$full_name, $role, $permissions, $id]);
        }
        header("Location: users.php");
        exit();
    }
}

$checkedPermissions = explode(',', $user['permissions'] ?? '');
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تعديل مستخدم</title>
    <style>
        body { font-family: Arial; background: #f4f4f4; padding: 20px; }
        form { background: white; padding: 20px; max-width: 500px; margin: auto; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        input, select { width: 100%; padding: 10px; margin-bottom: 10px; }
        label { display: block; margin-top: 10px; }
        .permissions { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-top: 10px; }
        button { background: #3498db; color: white; border: none; padding: 10px 20px; border-radius: 5px; }
        .error { color: red; margin-bottom: 10px; }
    </style>
</head>
<body>

<h2>تعديل بيانات المستخدم: <?= htmlspecialchars($user['username']) ?></h2>

<?php foreach ($errors as $err): ?>
    <p class="error"><?= htmlspecialchars($err) ?></p>
<?php endforeach; ?>

<form method="POST">
    <label>الاسم الكامل:</label>
    <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>" required>

    <label>الدور:</label>
    <select name="role" required>
        <option value="cashier" <?= $user['role'] === 'cashier' ? 'selected' : '' ?>>موظف كاشير</option>
        <option value="entry" <?= $user['role'] === 'entry' ? 'selected' : '' ?>>موظف دخول</option>
        <option value="supervisor" <?= $user['role'] === 'supervisor' ? 'selected' : '' ?>>مشرف</option>
        <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>مدير</option>
    </select>

    <label>كلمة المرور (اتركها فارغة إذا لم تُرد تغييرها):</label>
    <input type="password" name="password">

    <label>الصلاحيات:</label>
    <div class="permissions">
        <?php foreach ($permissionsList as $key => $label): ?>
            <label>
                <input type="checkbox" name="permissions[]" value="<?= $key ?>" <?= in_array($key, $checkedPermissions) ? 'checked' : '' ?>>
                <?= $label ?>
            </label>
        <?php endforeach; ?>
    </div>

    <button type="submit">حفظ التعديلات</button>
</form>

</body>
</html>
