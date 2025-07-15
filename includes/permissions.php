<?php
function hasPermission($key) {
    $permissions = explode(',', $_SESSION['permissions'] ?? '');
    return in_array($key, $permissions);
}
