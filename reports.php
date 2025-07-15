<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$fullName = $_SESSION['full_name'];
$role     = $_SESSION['role'];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>📊 التقارير - Parking System</title>
    <style>
        body {
            font-family: Tahoma, sans-serif;
            background: #f4f6f8;
            margin: 0;
            padding: 0;
            direction: rtl;
        }

        .top-bar {
            background: #2c3e50;
            color: white;
            padding: 15px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .top-bar h1 {
            margin: 0;
            font-size: 20px;
        }

        .container {
            padding: 30px;
        }

        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 3px 6px rgba(0,0,0,0.1);
            text-align: center;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            background-color: #e8f4ff;
        }

        .card-icon {
            font-size: 40px;
            margin-bottom: 10px;
        }

        .section-title {
            background: #1abc9c;
            color: white;
            padding: 12px;
            text-align: center;
            font-size: 22px;
        }

        a.back {
            display: inline-block;
            margin-top: 20px;
            text-decoration: none;
            color: #2980b9;
            font-weight: bold;
        }
    </style>
</head>
<body>

<div class="section-title">📊 لوحة تقارير النظام</div>

<div class="top-bar">
    <h1>👤 المستخدم: <?= htmlspecialchars($fullName) ?> (<?= htmlspecialchars($role) ?>)</h1>
    <a href="dashboard.php" style="color: white;">↩ الرجوع للوحة التحكم</a>
</div>

<div class="container">

    <div class="cards-grid">

        <div class="card" onclick="location.href='reports/manual_reports.php'">
            <div class="card-icon">📝</div>
             واجهة تقرير المنوال
        </div>

        <div class="card" onclick="location.href='reports/lost_report.php'">
            <div class="card-icon">🧍‍♂️</div>
            تقرير لوست تيكت
        </div>

        <div class="card" onclick="location.href='reports/master_cards.php'">
            <div class="card-icon">🎴</div>
            كروت الماستر (استخدام وتحليل)
        </div>

        <div class="card" onclick="location.href='reports/extra_cards.php'">
            <div class="card-icon">➕</div>
            الكروت الزيادة والفري
        </div>

        <div class="card" onclick="location.href='reports/overnight.php'">
            <div class="card-icon">🚗</div>
            تقرير مبيت السيارات
        </div>

        <div class="card" onclick="location.href='reports/shift_logs.php'">
            <div class="card-icon">🕒</div>
            تقرير استلام الشيفت
        </div>

    </div>

</div>

</body>
</html>
