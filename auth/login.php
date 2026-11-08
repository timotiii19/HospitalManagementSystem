<?php
session_start();
include('../config/db.php');
require_once __DIR__ . '/authenticate.php';

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = authenticateStaffUser(
        $conn,
        $_POST['email'] ?? '',
        $_POST['password'] ?? ''
    );

    if ($user) {
        header('Location: ' . $user['redirect']);
        exit();
    }

    $error = 'Invalid email or password.';
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Hospital Login</title>
    <link rel="stylesheet" type="text/css" href="../css/style.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body {
            background: url('../images/2.png') no-repeat center center fixed;
            background-size: full;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            color: #333;
        }

        .login-box {   /* box mismo */ 
            width: 350px;
            height: 340px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            margin: 180px auto;
            background: rgba(255, 255, 255, 0.9); /* semi-transparent background */
            padding: 20px 30px 30px 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .login-box h2 { /*  login enter email */
            margin-bottom: 20px;
            margin-top: 10px;
            margin-left: 40px;
            margin-right: 40px;
            color:#999999;
            line-height: 1;
            
        }

        .login-box input {  /* inside box */
            width: 90%;
            padding: 10px;
            margin: 8px;
            border: 1px solid#911037;
            border-radius: 8px;
        }

        .login-box button { /* login button */
            width: 90%;
            padding: 10px;
            margin-top: 20px;
            margin-bottom: 10px;
            background-color:#911037;
            border: none;
            color: white;
            font-size: 16px;
            border-radius: 8px;
            cursor: pointer;
        }
        
        .login-box label { /*Email, password*/
        font-size: 12px;
        font-family: Montserrat, serif;
        color: #555;
        margin-left: 20px;
        display: block;
        text-align: left;
        margin-top: 10px;
        }



        .login-box button:hover {
            background-color:#ff6b97;
        }

        .error {
        color: red;
        font-size: 12px;
        margin-bottom: 10px;
        height: 18px; /* Adjust based on font size */
        display: flex;
        align-items: center;
        justify-content: center;
        }

    </style>
</head>
<body style="display: flex; flex-direction: column; min-height: 100vh; margin: 0;">

    <div style="flex: 1;">
        <div class="login-box">
        <img src="../images/hosplogo.png" alt="Hospital Logo" style="width: 190px; height: 40px;">
            <h2><span style="font-size: 12px;">Enter your email address and password to access admin panel.</span></h2>
            <p class="error"><?php echo $error; ?></p>

            <form method="POST">
    <label for="email">Email</label>
    <input type="text" id="email" name="email" placeholder="Enter your email" required><br>

    <label for="password">Password</label>
    <input type="password" id="password" name="password" placeholder="Enter your password" required><br>

    <button type="submit">Login</button>
</form>

        </div>
    </div>

    <?php include('../includes/footer.php'); ?>
</body>