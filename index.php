<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>تسجيل الدخول - Parking System</title>
    <style>
        body {
            font-family: Arial;
            text-align: center;
            padding-top: 100px;
        }
        input {
            padding: 10px;
            margin: 10px;
            width: 250px;
        }
        button {
            padding: 10px 20px;
            background: #00c2ff;
            color: white;
            border: none;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <h2>Parking System - تسجيل الدخول</h2>

    <?php if (!empty($error)): ?>
        <p style="color:red;"><?php echo $error; ?></p>
    <?php endif; ?>

    <form method="post" action="login.php">
        <input type="text" name="username" placeholder="اسم المستخدم" required><br>
        <input type="password" name="password" placeholder="كلمة المرور" required><br>
        <button type="submit">تسجيل الدخول</button>
    </form>
</body>
</html>
