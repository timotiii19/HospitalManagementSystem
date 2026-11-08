<?php
session_start();
include('../config/db.php');

$token = trim($_GET['token'] ?? '');
$stmt = $conn->prepare("SELECT email FROM password_resets WHERE token = ? AND expires_at > NOW() LIMIT 1");
$stmt->bind_param("s", $token);
$stmt->execute();
$reset = $stmt->get_result()->fetch_assoc();
$stmt->close();

$error = "";
$success = "";

if (!$reset) {
    http_response_code(410);
    $error = "This reset link is invalid or expired. Request a new one.";
} elseif ($_SERVER["REQUEST_METHOD"] === "POST") {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (strlen($password) < 8) {
        $error = "Password must be at least 8 characters.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        try {
            $conn->begin_transaction();
            $lock = $conn->prepare(
                "SELECT email FROM password_resets WHERE token = ? AND expires_at > NOW() LIMIT 1 FOR UPDATE"
            );
            $lock->bind_param("s", $token);
            $lock->execute();
            $lockedReset = $lock->get_result()->fetch_assoc();
            $lock->close();

            if (!$lockedReset) {
                $conn->rollback();
                http_response_code(410);
                $error = "This reset link is invalid or expired. Request a new one.";
                $reset = null;
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $update = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
                $update->bind_param("ss", $hashedPassword, $lockedReset['email']);
                $update->execute();
                $updated = $update->affected_rows === 1;
                $update->close();

                if (!$updated) {
                    $conn->rollback();
                    $error = "The account could not be updated. Please request a new reset link.";
                } else {
                    $delete = $conn->prepare("DELETE FROM password_resets WHERE token = ?");
                    $delete->bind_param("s", $token);
                    $delete->execute();
                    $delete->close();
                    $conn->commit();
                    $success = "Password reset successful! You can now <a href='role_selection.php'>login</a>.";
                }
            }
        } catch (mysqli_sql_exception $exception) {
            $conn->rollback();
            error_log('Unable to reset account password: ' . $exception->getMessage());
            $error = "The password could not be reset. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reset Password</title>
<link rel="stylesheet" href="../css/style.css">
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
        <input type="password" name="password" required minlength="8" /><br />

        <label>Confirm New Password</label><br />
        <input type="password" name="confirm_password" required minlength="8" /><br />

        <button type="submit">Reset Password</button>
    </form>
    <?php endif; ?>
</div>
</body>
</html>
