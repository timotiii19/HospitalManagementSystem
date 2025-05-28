<?php
include('../config/db.php');

if (!isset($_GET['token'])) {
    die("Invalid token");
}

$token = mysqli_real_escape_string($conn, $_GET['token']);
$result = mysqli_query($conn, "SELECT * FROM users WHERE token='$token' AND token_expiry > NOW()");

if (mysqli_num_rows($result) === 0) {
    die("Link expired or invalid.");
}

$user = mysqli_fetch_assoc($result);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    
    mysqli_query($conn, "UPDATE users SET username='$username', password='$password', token=NULL, token_expiry=NULL WHERE UserID={$user['UserID']}");
    
    // Show confirmation message with nice UI
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
      <meta charset="UTF-8" />
      <meta name="viewport" content="width=device-width, initial-scale=1" />
      <title>Account Setup Complete</title>
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
        .message-box {
          background: white;
          padding: 30px 40px;
          border-radius: 8px;
          box-shadow: 0 4px 12px rgba(0,0,0,0.1);
          max-width: 400px;
          text-align: center;
        }
        .message-box h2 {
          color:rgb(192, 3, 3);
          margin-bottom: 20px;
        }
        .message-box a {
          color:rgb(233, 36, 69);
          text-decoration: none;
          font-weight: 600;
        }
        .message-box a:hover {
          text-decoration: underline;
        }
      </style>
    </head>
    <body>
      <div class="message-box">
        <h2>Account setup complete.</h2>
        <p>You can now <a href="../auth/role_selection.php">log in</a>.</p>
      </div>
    </body>
    </html>
    <?php
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Complete Registration</title>
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

<form method="POST" autocomplete="off" novalidate>
  <label for="username">Choose Username</label>
  <input type="text" id="username" name="username" required autocomplete="off" />
  
  <label for="password">Create Password</label>
  <input type="password" id="password" name="password" required autocomplete="new-password" />
  
  <button type="submit">Complete Registration</button>
</form>

</body>
</html>
