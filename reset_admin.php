<?php
// ================================
// PetCare Admin Reset Script
// ================================

require_once('config.php'); // includes firebase logic

// Create new admin account
$username = 'admin';
$password = password_hash('admin123', PASSWORD_DEFAULT); // Secure hashed password

// Delete old admin accounts
firebase_delete('Admins');

$res = firebase_insert('Admins', ['username' => $username, 'password' => $password]);

if ($res) {
    echo "<h2 style='color:green;'>✅ Admin account has been reset successfully on Firebase!</h2>";
    echo "<p><strong>Username:</strong> admin</p>";
    echo "<p><strong>Password:</strong> admin123</p>";
} else {
    echo "<h3 style='color:red;'>Error resetting admin</h3>";
}
?>
