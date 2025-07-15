<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// تحقق من الصلاحيات
$userPermissions = explode(',', $_SESSION['permissions'] ?? '');
if (!in_array('master_cards', $userPermissions)) {
    header("Location: dashboard.php");
    exit();
}

$pdo = new PDO("mysql:host=localhost;dbname=parking_system", "root", "");

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $card_number = trim($_POST['card_number']);

    if (empty($card_number)) {
        $errors[] = "رقم الكارت مطلوب.";
    } else {
        // تحقق من التكرار
        $stmt = $pdo->prepare("SELECT id FROM master_cards WHERE card_number = ?");
        $stmt->execute([$card_number]);

        if ($stmt->fetch()) {
            $errors[] = "الكارت موجود بالفعل.";
        } else {
            $created_by = $_SESSION['full_name'];
            $created_at = date("Y-m-d H:i:s");

            $stmt = $pdo->prepare("INSERT INTO master_cards (card_number, created_by, created_at) VALUES (?, ?, ?)");
            $stmt->execute([$card_number, $created_by, $created_at]);
            $success = true;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>إضافة كارت مستر</title>
    <style>
        body { font-family: Arial; background: #f9f9f9; padding: 20px; }
        form { background: white; padding: 20px; max-width: 500px; margin: auto; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        label { font-weight: bold; margin-top: 10px; display: block; }
        input { width: 100%; padding: 10px; margin-top: 5px; margin-bottom: 15px; }
        button { background-color: #3498db; color: white; border: none; padding: 10px 20px; border-radius: 5px; }
        .error { color: red; margin-bottom: 10px; }
        .success { color: green; margin-bottom: 10px; }
    </style>
</head>
<body>

<h2>➕ إضافة كارت مستر جديد</h2>

<?php foreach ($errors as $error): ?>
    <p class="error"><?= htmlspecialchars($error) ?></p>
<?php endforeach; ?>

<?php if ($success): ?>
    <p class="success">✅ تم إضافة الكارت بنجاح.</p>
<?php endif; ?>

<form method="POST">
    <label>رقم الكارت:</label>
    <input type="text" name="card_number" required>

    <button type="submit">إضافة الكارت</button>
</form>

</body>
</html>
