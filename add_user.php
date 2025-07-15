<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$pdo = new PDO("mysql:host=localhost;dbname=parking_system", "root", "");

// قائمة الصلاحيات (تم إضافة الموافقات)
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

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username    = trim($_POST['username']);
    $password    = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $full_name   = trim($_POST['full_name']);
    $role        = $_POST['role'];
    $permissions = isset($_POST['permissions']) ? implode(',', $_POST['permissions']) : '';

    if (empty($username) || empty($_POST['password']) || empty($role) || empty($full_name)) {
        $errors[] = "كل الحقول مطلوبة.";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $errors[] = "اسم المستخدم موجود بالفعل.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, role, permissions) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$username, $password, $full_name, $role, $permissions]);
            header("Location: users.php");
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>إضافة مستخدم جديد</title>
    <style>
        body { font-family: Arial; background: #f4f4f4; padding: 20px; }
        form { background: white; padding: 20px; max-width: 500px; margin: auto; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        input, select { width: 100%; padding: 10px; margin-bottom: 10px; }
        label { display: block; margin-top: 10px; }
        .permissions { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-top: 10px; }
        button { background: #2ecc71; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; }
        .error { color: red; margin-bottom: 10px; }
    </style>
</head>
<body>

<h2>➕ إضافة مستخدم جديد</h2>

<?php foreach ($errors as $err): ?>
    <p class="error"><?= htmlspecialchars($err) ?></p>
<?php endforeach; ?>

<form method="POST">
    <label>اسم المستخدم:</label>
    <input type="text" name="username" required>

    <label>الاسم الكامل:</label>
    <input type="text" name="full_name" required>

    <label>كلمة المرور:</label>
    <input type="password" name="password" required>

    <label>الدور:</label>
    <select name="role" required>
        <option value="cashier">موظف كاشير</option>
        <option value="entry">موظف دخول</option>
        <option value="supervisor">مشرف</option>
        <option value="admin">مدير</option>
    </select>

    <label>الصلاحيات:</label>
    <div class="permissions">
        <?php foreach ($permissionsList as $key => $label): ?>
            <label><input type="checkbox" name="permissions[]" value="<?= $key ?>"> <?= $label ?></label>
        <?php endforeach; ?>
    </div>

    <button type="submit">إضافة المستخدم</button>
</form>

</body>
</html>
