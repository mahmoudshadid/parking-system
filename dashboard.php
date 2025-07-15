<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$fullName = $_SESSION['full_name'];
$role = $_SESSION['role'];
$userPages = explode(',', $_SESSION['permissions'] ?? []);

$isAdminOrSupervisor = in_array($role, ['admin', 'supervisor']);

$pdo = new PDO("mysql:host=localhost;dbname=parking_system;charset=utf8", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$notifCounts = [
    'entry_exit' => $pdo->query("SELECT COUNT(*) FROM master_cards_logs WHERE action_type IN ('entry', 'exit') AND action_time >= NOW() - INTERVAL 10 MINUTE")->fetchColumn(),
    'manual' => $pdo->query("SELECT COUNT(*) FROM manual_requests WHERE created_at = CURDATE() AND created_at >= CURTIME() - INTERVAL 10 MINUTE")->fetchColumn(),
    'extra' => $pdo->query("SELECT COUNT(*) FROM extra_cards WHERE date = CURDATE() AND time >= CURTIME() - INTERVAL 10 MINUTE")->fetchColumn(),
    'overnight' => $pdo->query("SELECT COUNT(*) FROM overnight_vehicles WHERE created_at = CURDATE() AND created_at >= CURTIME() - INTERVAL 10 MINUTE")->fetchColumn(),
];

$connectedUsers = [];
if ($isAdminOrSupervisor) {
    $stmt = $pdo->query("SELECT full_name, role FROM users WHERE is_logged_in = 1");
    $connectedUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>لوحة التحكم - Parking System</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, sans-serif;
            background: #f0f2f5;
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

        .top-bar .user-info {
            font-size: 14px;
        }

        .logout {
            color: #e74c3c;
            text-decoration: none;
            font-weight: bold;
            margin-right: 20px;
        }

        .container {
            padding: 20px;
        }

        .dashboard {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .card {
            background: #ffffff;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 3px 6px rgba(0,0,0,0.1);
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }

        .card:hover {
            background: #eaf4ff;
        }

        .card-icon {
            font-size: 30px;
            margin-bottom: 10px;
            color: #2980b9;
        }

        .notification {
            position: absolute;
            top: 8px;
            left: 8px;
            background: red;
            color: white;
            font-size: 12px;
            padding: 3px 7px;
            border-radius: 10px;
        }

        .connected-users {
            background: #fff;
            border: 1px solid #ccc;
            padding: 15px;
            margin-top: 25px;
            border-radius: 8px;
        }

        .connected-users h3 {
            margin: 0 0 10px;
            font-size: 16px;
        }

        .connected-users ul {
            list-style: none;
            padding: 0;
        }

        .connected-users li {
            padding: 5px 0;
            border-bottom: 1px solid #eee;
        }

        .system-title {
            background: #1abc9c;
            color: white;
            padding: 12px 20px;
            font-size: 22px;
            text-align: center;
        }
    </style>
</head>

<body>

<div class="system-title">
    🚗 Parking System - نظام تشغيل الباركينج للموظفين
</div>

<div class="top-bar">
    <h1>👋 أهلاً، <?= htmlspecialchars($fullName) ?></h1>
    <div class="user-info">
        الدور: <strong><?= htmlspecialchars($role) ?></strong>
        <a href="logout.php" class="logout">تسجيل الخروج</a>
    </div>
</div>

<!-- 🔔 الإشعارات -->
<div style="position: relative; margin: 10px;">
    <div id="notif-icon" style="cursor:pointer; font-size: 24px; position: relative;">
        🔔<span id="notif-count" style="color:red; font-weight:bold;"></span>
    </div>
    <div id="notif-box" style="display:none; position:absolute; top:30px; right:0; background:white; color:black; width:300px; border:1px solid #ccc; border-radius:5px; box-shadow:0 2px 6px rgba(0,0,0,0.2); z-index:999;">
        <div style="padding:10px; font-weight:bold; border-bottom:1px solid #eee;">📢 الإشعارات الحديثة</div>
        <ul id="notif-list" style="list-style:none; margin:0; padding:10px; max-height:300px; overflow-y:auto;"></ul>
    </div>
</div>

<!-- 🔊 الصوت -->
<audio id="notif-sound" src="notif.mp3" preload="auto"></audio>

<div class="container">
    <div class="dashboard">

        <?php if (in_array('manual', $userPages)): ?>
            <div class="card" onclick="location.href='manual.php'">
                <div class="card-icon">📝</div>
                المنويل والوسط
                <?php if ($notifCounts['manual']): ?>
                    <div class="notification"><?= $notifCounts['manual'] ?></div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (in_array('master_cards', $userPages)): ?>
            <div class="card" onclick="location.href='master_cards.php'">
                <div class="card-icon">🎴</div>
                كروت المستر
            </div>
        <?php endif; ?>

        <?php if (in_array('shift', $userPages)): ?>
            <div class="card" onclick="location.href='shift.php'">
                <div class="card-icon">⏰</div>
                استلام الشيفت
            </div>
        <?php endif; ?>

        <?php if (in_array('extra_cards', $userPages)): ?>
            <div class="card" onclick="location.href='extra_cards.php'">
                <div class="card-icon">➕</div>
                الكروت الزيادة والفري
                <?php if ($notifCounts['extra']): ?>
                    <div class="notification"><?= $notifCounts['extra'] ?></div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (in_array('overnight', $userPages)): ?>
            <div class="card" onclick="location.href='overnight.php'">
                <div class="card-icon">🚗</div>
                سيارات المبيت
                <?php if ($notifCounts['overnight']): ?>
                    <div class="notification"><?= $notifCounts['overnight'] ?></div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (in_array('users', $userPages)): ?>
            <div class="card" onclick="location.href='users.php'">
                <div class="card-icon">👥</div>
                المستخدمين
            </div>
        <?php endif; ?>

        <?php if (in_array('reports', $userPages)): ?>
            <div class="card" onclick="location.href='reports.php'">
                <div class="card-icon">📊</div>
                التقارير
            </div>
        <?php endif; ?>

        <?php if (in_array('manual_approvals', $userPages)): ?>
            <div class="card" onclick="location.href='manual_approvals.php'">
                <div class="card-icon">✅</div>
                الموافقات
            </div>
        <?php endif; ?>

    </div>

    <?php if ($isAdminOrSupervisor && count($connectedUsers)): ?>
        <div class="connected-users">
            <h3>🟢 الحسابات المتصلة الآن</h3>
            <ul>
                <?php foreach ($connectedUsers as $user): ?>
                    <li><?= htmlspecialchars($user['full_name']) ?> - (<?= htmlspecialchars($user['role']) ?>)</li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
</div>

<!-- ✅ تشغيل صوت الإشعارات -->
<audio id="notif-sound" src="notif.mp3" preload="auto"></audio>

<script>
    let previousNotifs = [];

    function checkNotifications() {
        fetch('notifications_list.php')
            .then(res => res.json())
            .then(data => {
                // ✅ تشغيل الصوت عند وجود إشعار جديد
                if (data.length > previousNotifs.length) {
                    const audio = document.getElementById('notif-sound');
                    if (audio) {
                        audio.play().catch(() => {
                            console.warn('⚠️ لم يتم تشغيل الصوت تلقائيًا. يتطلب تفاعل المستخدم أولاً.');
                        });
                    }
                }

                // ✅ تحديث واجهة الإشعارات
                const notifBox = document.getElementById('notif-box');
                const notifList = document.getElementById('notif-list');
                const notifCount = document.getElementById('notif-count');

                if (data.length > 0) {
                    notifCount.innerText = `(${data.length})`;
                } else {
                    notifCount.innerText = '';
                }

                // عرض القائمة فقط عند فتح الصندوق
                if (notifBox.style.display === 'block') {
                    notifList.innerHTML = '';
                    if (data.length === 0) {
                        notifList.innerHTML = '<li>لا توجد إشعارات جديدة</li>';
                    } else {
                        data.forEach(msg => {
                            const li = document.createElement('li');
                            li.textContent = msg;
                            li.style.padding = '5px 0';
                            li.style.borderBottom = '1px solid #eee';
                            notifList.appendChild(li);
                        });
                    }
                }

                previousNotifs = data;
            })
            .catch(err => console.error('خطأ في تحميل الإشعارات:', err));
    }

    // ✅ فتح/إغلاق صندوق الإشعارات عند الضغط على 🔔
    document.getElementById('notif-icon').addEventListener('click', function () {
        const box = document.getElementById('notif-box');
        box.style.display = (box.style.display === 'block') ? 'none' : 'block';

        // عرض الإشعارات في الصندوق بعد الفتح مباشرة
        if (box.style.display === 'block') {
            checkNotifications();
        }
    });

    // ✅ أول تحميل للصفحة
    checkNotifications();

    // ✅ تحديث كل دقيقة
    setInterval(checkNotifications, 60000);
</script>


</body>
</html>
