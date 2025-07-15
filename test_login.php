<?php
$entered_password = 'admin123';

// الباسورد اللي موجود في قاعدة البيانات (من SELECT)
$hashed_password = '$2y$10$ak1/EDa4HVOoMK6UTZGuD.Sby93NbcTvbNvU2h0CeZTkUEjRQbeKS';

if (password_verify($entered_password, $hashed_password)) {
    echo "✅ كلمة المرور صحيحة";
} else {
    echo "❌ كلمة المرور غير صحيحة";
}
