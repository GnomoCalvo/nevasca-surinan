<?php
session_start();

$last_cleanup = isset($_SESSION['last_cleanup']) ? $_SESSION['last_cleanup'] : 0;
$cleanup_interval = 3600; // 1 hora em segundos

if (time() - $last_cleanup >= $cleanup_interval) {
    require_once __DIR__ . '/scripts/cleanup_sessions.php';
    $_SESSION['last_cleanup'] = time();
}