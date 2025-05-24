<?php
session_start();
include('../config/db.php');

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['email']) && isset($_POST['password'])) {
        $email = $_POST['email'];
        $password = $_POST['password'];

        $sql = "SELECT * FROM users WHERE email = '$email'";
        $result = $conn->query($sql);

        if ($result && $result->num_rows == 1) {
            $user = $result->fetch_assoc();

            if (password_get_info($user['password'])['algo'] == 0) {
                $passwordMatch = ($password === $user['password']);
            } else {
                $passwordMatch = password_verify($password, $user['password']);
            }

            if ($passwordMatch) {
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['user_id'] = $user['UserID'];

            switch ($user['role']) {
                case 'admin':
                    $query = "SELECT AdminID AS role_id FROM admin WHERE UserID = {$user['UserID']}";
                    break;
                case 'doctor':
                    $query = "SELECT DoctorID AS role_id FROM doctor WHERE UserID = {$user['UserID']}";
                    break;
                case 'nurse':
                    $query = "SELECT NurseID AS role_id FROM nurse WHERE UserID = {$user['UserID']}";
                    break;
                case 'cashier':
                    $query = "SELECT CashierID AS role_id FROM cashier WHERE UserID = {$user['UserID']}";
                    break;
                case 'pharmacist':
                    $query = "SELECT PharmacistID AS role_id FROM pharmacist WHERE UserID = {$user['UserID']}";
                    break;
                case 'receptionist':
                    $query = "SELECT ReceptionistID AS role_id FROM receptionist WHERE UserID = {$user['UserID']}";
                    break;
                default:
                    $query = "";
            }


            if (!empty($query)) {
                $roleResult = $conn->query($query);
                if ($roleResult && $roleResult->num_rows == 1) {
                    $roleData = $roleResult->fetch_assoc();
                    $_SESSION['role_id'] = $roleData['role_id'];
                }
            }

                // ⭐ Redirect based on role ⭐
                switch ($_SESSION['role']) {
                    case 'admin':
                        header("Location: ../views/admin/dashboard.php");
                        break;
                    case 'doctor':
                        header("Location: ../views/doctors/dashboard.php");
                        break;
                    case 'nurse':
                        header("Location: ../views/nurse/dashboard.php");
                        break;
                    case 'cashier':
                        header("Location: ../views/cashier/dashboard.php");
                        break;
                    case 'pharmacist':
                        header("Location: ../views/pharmacist/dashboard.php");
                        break;
                    default:
                        header("Location: ../views/admin/dashboard.php");
                        break;
                }
                exit();
            } else {
                $error = "Invalid email or password!";
            }
        } else {
            $error = "Invalid email or password!";
        }
    } else {
        $error = "Please fill in both fields!";
    }
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