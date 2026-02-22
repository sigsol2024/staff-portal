<?php
/**
 * One-time script to create default admin.
 * Run this once after importing staff_portal.sql, then delete this file.
 * Default: admin@example.com / Admin@123
 */
require_once dirname(__DIR__) . '/config/config.php';

$email = 'admin@example.com';
$password = 'Admin@123';
$hash = password_hash($password, PASSWORD_DEFAULT);

try {
    $stmt = $pdo->prepare("SELECT id FROM admins WHERE email = ?");
    $stmt->execute([$email]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        $stmt = $pdo->prepare("UPDATE admins SET password = ? WHERE email = ?");
        $stmt->execute([$hash, $email]);
        echo "Admin password updated. Email: $email, Password: $password<br>DELETE THIS FILE NOW.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO admins (email, password) VALUES (?, ?)");
        $stmt->execute([$email, $hash]);
        echo "Admin created. Email: $email, Password: $password<br>DELETE THIS FILE NOW.";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
