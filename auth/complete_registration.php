<?php
require_once __DIR__ . '/../config/db.php';

$token = trim($_GET['token'] ?? '');
$error = '';
$complete = false;
$userId = 0;

if ($token === '') {
    http_response_code(400);
    $error = 'The setup link is missing a token.';
} else {
    $stmt = $conn->prepare('SELECT UserID FROM users WHERE token = ? AND token_expiry > NOW() LIMIT 1');
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user) {
        http_response_code(410);
        $error = 'This setup link is invalid or expired. Ask your administrator for a new invitation.';
    } else {
        $userId = (int) $user['UserID'];
    }
}

if ($userId > 0 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (strlen($username) < 3 || strlen($username) > 100) {
        $error = 'Username must be between 3 and 100 characters.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirmPassword) {
        $error = 'The passwords do not match.';
    } else {
        $check = $conn->prepare('SELECT UserID FROM users WHERE username = ? AND UserID <> ? LIMIT 1');
        $check->bind_param('si', $username, $userId);
        $check->execute();
        $usernameExists = $check->get_result()->num_rows > 0;
        $check->close();

        if ($usernameExists) {
            $error = 'That username is already in use.';
        } else {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $update = $conn->prepare(
                'UPDATE users SET username = ?, password = ?, token = NULL, token_expiry = NULL
                 WHERE UserID = ? AND token = ? AND token_expiry > NOW()'
            );
            $update->bind_param('ssis', $username, $passwordHash, $userId, $token);
            $update->execute();
            $complete = $update->affected_rows === 1;
            $update->close();

            if (!$complete) {
                http_response_code(410);
                $error = 'This setup link is invalid or expired. Ask your administrator for a new invitation.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Complete Registration</title>
  <link rel="stylesheet" href="../css/style.css">
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f4f7fa;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      margin: 0;
    }
    form {
      background: white;
      padding: 30px 40px;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
      width: 100%;
      max-width: 400px;
      box-sizing: border-box;
    }
    label {
      display: block;
      margin-bottom: 8px;
      font-weight: 600;
      color: #333;
    }
    input[type="text"],
    input[type="password"] {
      width: 100%;
      padding: 10px 12px;
      margin-bottom: 20px;
      border: 1.5px solid #ccc;
      border-radius: 6px;
      font-size: 16px;
      box-sizing: border-box;
      transition: border-color 0.3s ease;
    }
    input[type="text"]:focus,
    input[type="password"]:focus {
      border-color:rgb(245, 48, 75);
      outline: none;
    }
    button {
      width: 100%;
      padding: 12px;
      background-color:rgb(240, 40, 87);
      border: none;
      border-radius: 6px;
      color: white;
      font-size: 18px;
      cursor: pointer;
      font-weight: 600;
      transition: background-color 0.3s ease;
    }
    button:hover {
      background-color:rgb(230, 23, 55);
    }
  </style>
</head>
<body>

<form method="POST" autocomplete="off">
  <?php if ($complete): ?>
    <h2>Account setup complete</h2>
    <p>You can now <a href="role_selection.php">sign in</a>.</p>
  <?php elseif ($error !== ''): ?>
    <p role="alert" style="color:#9b1c31;"><?= htmlspecialchars($error) ?></p>
  <?php endif; ?>
  <?php if ($userId > 0 && !$complete): ?>
  <label for="username">Choose Username</label>
  <input type="text" id="username" name="username" minlength="3" maxlength="100" required autocomplete="username" />
  
  <label for="password">Create Password</label>
  <input type="password" id="password" name="password" minlength="8" required autocomplete="new-password" />

  <label for="confirm_password">Confirm Password</label>
  <input type="password" id="confirm_password" name="confirm_password" minlength="8" required autocomplete="new-password" />
  
  <button type="submit">Complete Registration</button>
  <?php endif; ?>
</form>

</body>
</html>
