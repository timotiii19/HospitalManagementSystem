<!-- auth/role_selection.php -->
 <!-- background: url('../images/2.png') no-repeat center center fixed; -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Select Role</title>
    <style>
    body {
        font-family: 'Segoe UI', sans-serif;
        margin: 0;
        padding: 0;
        text-align: center;
        background: url('../images/2.png') no-repeat center center fixed;
        background-size: cover;
        overflow: hidden; /* prevents scrollbars */
    }

    .overlay {
        height: 100vh;
        padding: 10px 20px;
        box-sizing: border-box; /* ensures padding doesn’t expand height */
    }

    h2 {
        font-size: 28px;
        margin-bottom: 40px;
        color: #fff;
        background-color: rgba(115, 0, 0, 0.85); /* maroon with transparency */
        padding: 15px 25px;
        border-radius: 12px;
        display: inline-block;
        box-shadow: 0 0 10px rgba(115, 0, 0, 0.6); /* glow */
    }

    .container {
        display: flex;
        flex-direction: column;
        gap: 30px;
        align-items: center;
    }

    .row {
        display: flex;
        gap: 30px;
        flex-wrap: wrap;
        justify-content: center;
    }

    .role-card {
        width: 180px;
        padding: 20px 15px;
        border-radius: 20px;
        background: radial-gradient(circle at center, rgb(244, 240, 240) 40%, rgba(250, 176, 165, 0.92) 100%);
        border: 1px solid #e18585;
        box-shadow:
            -5px 5px 15px 2px rgba(249, 173, 173, 0.3),   
            5px 5px 15px 2px rgba(249, 173, 173, 0.3),   
            0 8px 20px 2px rgba(249, 173, 173, 0.25);  
        text-decoration: none;
        color: #333;
        text-align: center;
        transition: transform 0.4s ease, box-shadow 0.4s ease;
        transform-style: preserve-3d;
        position: relative;
        backface-visibility: hidden;
    }

    .role-card:hover {
        transform: translateY(-8px) scale(1.05) rotateX(4deg);
        box-shadow:
            0 12px 30px rgba(188, 67, 67, 0.77),        
            inset -3px -3px 6px rgba(255, 255, 255, 0.6), 
            inset 3px 3px 8px rgba(0, 0, 0, 0.1);        
        z-index: 10;
    }



    .role-card img {
        width: 100px;
        height: 100px;
        object-fit: contain;
        margin-top: 10px;
        margin-bottom: 15px;
    }

    .role-card img.pharmacist-img {
        width: 130px;
        height: 100px;
        margin-top: 8px;
    }


    .role-name {
        font-weight: 600;
        font-size: 20px;
        margin-top: 0px;
        color: #730000;
    }

    .subtext {
        font-size: 13px;
        color: #b14e4e;
        margin-top: 5px;
    }
    </style>
</head>
<body>
    <div class="overlay">
        <h2>Proceed by selecting your designated role</h2>
        <div class="container">

            <!-- First Row -->
            <div class="row">
                <a href="doctor_login.php" class="role-card">
                    <img src="../images/doctor1.png" alt="Doctor">
                    <div class="role-name">Doctor</div>
                    <div class="subtext">Kindly click here!</div>
                </a>
                <a href="nurse_login.php" class="role-card">
                    <img src="../images/nurse1.png" alt="Nurse">
                    <div class="role-name">Nurse</div>
                    <div class="subtext">Kindly click here!</div>
                </a>
                <a href="pharmacist_login.php" class="role-card">
                    <img src="../images/pharmacist1.png" alt="Pharmacist" class="pharmacist-img">
                    <div class="role-name">Pharmacist</div>
                    <div class="subtext">Kindly click here!</div>
                </a>
            </div>

            <!-- Second Row -->
            <div class="row">
                <a href="admin_login.php" class="role-card">
                    <img src="../images/admin1.png" alt="Admin">
                    <div class="role-name">Admin</div>
                    <div class="subtext">Kindly click here!</div>
                </a>
                <a href="cashier_login.php" class="role-card">
                    <img src="../images/cashier1.png" alt="Cashier">
                    <div class="role-name">Cashier</div>
                    <div class="subtext">Kindly click here!</div>
                </a>
            </div>

        </div>
    </div>
</body>
</html>
