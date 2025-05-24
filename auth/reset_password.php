<?php
session_start();
include('../config/db.php');

if (!isset($_GET['token'])) {
    die("No token provided.");
}

$token = $_GET['token'];

// Debug info (remove in production)
echo "Token from URL: " . htmlspecialchars($token) . "<br>";
echo "Server time (PHP): " . date("Y-m-d H:i:s") . "<br>";

$current_php_time = date("Y-m-d H:i:s");

// Fetch token data without expiry check in SQL
$stmt = $conn->prepare("SELECT email, expires_at FROM password_resets WHERE token = ?");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows !== 1) {
    die("Invalid token.");
}

$row = $result->fetch_assoc();

// Check expiry using PHP time
if ($row['expires_at'] < $current_php_time) {
    die("Token expired.");
}

$email = $row['email'];

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Hash new password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Update user password
        $updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
        $updateStmt->bind_param("ss", $hashed_password, $email);
        $updateStmt->execute();

        if ($updateStmt->affected_rows === 1) {
            // Delete used token
            $delStmt = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
            $delStmt->bind_param("s", $email);
            $delStmt->execute();

            $success = "Password reset successful! You can now <a href='role_selection.php'>login</a>.";
        } else {
            $error = "Failed to update password. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Reset Password</title>
<style>
    body {
        font-family: Arial, sans-serif;
        background: #f0f2f5;
        padding: 30px;
    }
    .container {
        max-width: 400px;
        margin: 30px auto;
        background: white;
        padding: 25px 30px;
        border-radius: 8px;
        box-shadow: 0 0 12px rgba(0,0,0,0.1);
    }
    h2 {
        margin-bottom: 20px;
        color: #333;
    }
    input[type=password] {
        width: 100%;
        padding: 10px;
        margin: 12px 0 18px 0;
        border: 1px solid #ccc;
        border-radius: 6px;
        font-size: 16px;
    }
    button {
        width: 100%;
        background: #c94141;
        color: white;
        padding: 12px;
        font-size: 16px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
    }
    button:hover {
        background: #ce6b6b;
    }
    .error {
        color: red;
        margin-bottom: 12px;
        font-size: 14px;
    }
    .success {
        color: green;
        margin-bottom: 12px;
        font-size: 14px;
    }
    a {
        color: #c94141;
        text-decoration: none;
    }
</style>
</head>
<body>
<div class="container">
    <h2>Reset Your Password</h2>

    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="success"><?= $success ?></div>
    <?php else: ?>
    <form method="POST" autocomplete="off">
        <label>New Password</label><br />
        <input type="password" name="password" required minlength="6" /><br />

        <label>Confirm New Password</label><br />
        <input type="password" name="confirm_password" required minlength="6" /><br />

        <button type="submit">Reset Password</button>
    </form>
    <?php endif; ?>
</div>
</body>
</html>
