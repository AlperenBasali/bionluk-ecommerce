<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
function isUserLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

