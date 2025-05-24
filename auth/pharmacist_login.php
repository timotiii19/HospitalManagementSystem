<?php
session_start();
include('../config/db.php');
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];
    // Use prepared statement for security
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND role = 'Pharmacist'");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows == 1) {
        $user = $result->fetch_assoc();
        $passwordMatch = password_verify($password, $user['password']) || $password === $user['password'];

        if ($passwordMatch) {
            // ✅ Set all needed session values
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name']; // Must be real name
            $_SESSION['role'] = $user['role'];
            $_SESSION['UserID'] = $user['UserID'];

            // Get pharmacist-specific role ID
            $roleStmt = $conn->prepare("SELECT PharmacistID AS role_id FROM pharmacist WHERE UserID = ?");
            $roleStmt->bind_param("i", $user['UserID']);
            $roleStmt->execute();
            $roleResult = $roleStmt->get_result();
            if ($roleResult && $roleResult->num_rows == 1) {
                $_SESSION['role_id'] = $roleResult->fetch_assoc()['role_id'];
            }

            header("Location: ../views/pharmacist/dashboard.php");
            exit();
        } else {
            $error = "Invalid credentials!";
        }
    } else {
        $error = "No Pharmacist found with those credentials!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Pharmacist Login</title>
    <style>
        body {
            position: relative;
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            overflow: hidden;
        }

        body::before {
            content: "";
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: url('../images/pharmacist_bg3.png') no-repeat center center;
            background-size: cover;
            filter: blur(0px);
            z-index: -1;
        }

        .login-box {
            position: relative;
            width: 450px;
            height: 450px;
            margin: 45px auto 0 160px;
            padding: 30px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            text-align: center;
            z-index: 1;
        }
        h2 {
            font-size: 30px;
            margin-bottom: 20px;
        }

        input[type="text"],
        input[type="password"] {
            width: 90%;
            padding: 10px 40px 10px 10px; /* space for eye icon */
            margin: 15px 0;
            border-radius: 8px;
            border: 1px solid #ccc;
            font-size: 16px;
            box-sizing: border-box;
        }

        button {
            width: 95%;
            padding: 10px;
            background-color: rgb(201, 65, 65);
            color: white;
            border: none;
            font-size: 16px;
            border-radius: 8px;
            cursor: pointer;
        }
        button:hover {
            background-color: rgb(206, 107, 118);
        }
        .error {
            color: red;
            font-size: 12px;
            margin: 10px 0;
        }
        .top-img {
            width: 100px;
        }
        .back-btn {
            display: inline-block;
            margin-top: 15px;
            margin-left: 10px;
            text-decoration: none;
            font-size: 14px;
            padding: 10px 20px;
            border-radius: 8px;
            background-color: #fff;
            color: rgb(201, 65, 65);
            border: 1px solid rgb(201, 65, 65);
            transition: background-color 0.2s, color 0.2s;
        }

        .back-btn:hover {
            background-color: rgb(201, 65, 65);
            color: #fff;
        }

        .password-container {
            position: relative;
            width: 100%;
            margin: 15px auto;
        }

        #togglePassword {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            opacity: 0.6;
            transition: opacity 0.3s ease;
            font-size: 22px;
            user-select: none;
            color: #888;
        }
        #togglePassword:hover {
            opacity: 1;
            color: rgb(201, 65, 65);
        }
    </style>
</head>
<body>
    <a href="role_selection.php" class="back-btn">← Back to Role Selection</a>
    <div class="login-box">
        <img src="../images/pharmacist1.png" class="top-img" alt="Pharmacist">
        <h2>Good day, Pharmacist!<br><span style="font-size: 18px;">Welcome!</span></h2>
        <form method="POST" autocomplete="off">
            <input type="text" name="email" placeholder="Email" required><br>
            <div class="password-container">
                <input type="password" id="password" name="password" placeholder="Password" required>
                <span id="togglePassword" title="Show password" aria-label="Show password" role="button" tabindex="0">
                    <!-- Eye SVG icon -->
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" style="transform: translateX(-25px);"
                         fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" 
                         stroke-linejoin="round" class="feather feather-eye" viewBox="0 0 24 24">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                </span>
            </div>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <button type="submit">LOG IN</button>
        </form>
        <a href="../auth/forgot_password.php" style="font-size: 13px; color: rgb(201, 65, 65); display: inline-block; margin-top: 10px;">Forgot Password?</a>
    </div>

    <script>
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');

        togglePassword.addEventListener('click', () => {
            passwordInput.type = 'text';
            togglePassword.style.color = 'rgb(201, 65, 65)';

            setTimeout(() => {
                passwordInput.type = 'password';
                togglePassword.style.color = '#888';
            }, 100);
        });

        togglePassword.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                togglePassword.click();
            }
        });
    </script>
</body>
</html>
