<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'Admin') {
    header("Location: ../../auth/admin_login.php");
    exit();
}

date_default_timezone_set('Asia/Manila'); 

include('../../includes/admin_header.php');
include('../../includes/admin_sidebar.php');
include('../../config/db.php');

$admin_name = $_SESSION['username'];

$hour = date('H');
if ($hour < 12) {
    $greet = "Good Morning";
} elseif ($hour < 18) {
    $greet = "Good Afternoon";
} else {
    $greet = "Good Evening";
}

// Get counts from respective tables
$doctor_count     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM doctor"))['total'];
$nurse_count      = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM nurse"))['total'];
$pharmacist_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM pharmacist"))['total'];
$cashier_count    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM cashier"))['total'];
$admin_count      = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM admin"))['total'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <link rel="stylesheet" type="text/css" href="../../css/style.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #ffffff;
            margin: 0;
            padding: 0;
            transition: all 0.3s ease;
        }

        .content {
            padding: 40px;
            margin-left: 220px;
            width: calc(100% - 220px);
            box-sizing: border-box;
            transition: all 0.3s ease;
        }

        body.sidebar-collapsed .content {
            margin-left: 60px;
            width: calc(100% - 60px);
        }

        h3 {
            font-size: 28px;
            color: #730000;
        }

        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            grid-auto-rows: 180px;
            gap: 30px;
            margin-top: 40px;
            width: 100%;
        }

        .card {
            padding: 40px;
            background-color: #fff;
            border-radius: 15px;
            border: 6px solid #c34b4b;
            box-shadow: 8px 8px 0px #e58585;
            text-align: center;
            transition: all 0.3s ease-in-out;
            display: flex;
            flex-direction: column;
            justify-content: center;
            text-decoration: none;
            color: inherit;
        }

        .card:hover {
            transform: translateY(-6px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.25);
            border-color: #a42c2c;
        }

        .card-title {
            font-size: 30px;
            font-weight: bold;
            color: #730000;
            margin-bottom: 15px;
        }

        .card-value {
            font-size: 48px;
            color: #444;
        }

        .dashboard-cards > .card:nth-child(4) {
            grid-column: 1 / span 1.5;
        }

        .dashboard-cards > .card:nth-child(5) {
            grid-column: 2.5 / span 1.5;
        }

        #liveClock {
            position: fixed;
            top: 100px;
            right: 20px;
            background-color: #9c335a;
            color: white;
            padding: 8px 15px;
            border-radius: 8px;
            font-family: 'Courier New', Courier, monospace;
            font-size: 16px;
            font-weight: bold;
            box-shadow: 2px 2px 6px rgba(0,0,0,0.2);
            z-index: 1;
            user-select: none;
        }
    </style>
</head>
<body>

<div class="content">
    <div style="margin-top: 70px;">
        <h3><?php echo $greet . ", " . htmlspecialchars($admin_name); ?>!</h3>
        <p>This is your admin dashboard. Use the sidebar to manage the system.</p>
    </div>

    <div class="dashboard-cards">
        <a href="../admin/doctors.php" class="card">
            <div class="card-title">👨‍⚕️ Doctors</div>
            <div class="card-value"><?php echo $doctor_count; ?></div>
        </a>
        <a href="../admin/nurses.php" class="card">
            <div class="card-title">👩‍⚕️ Nurses</div>
            <div class="card-value"><?php echo $nurse_count; ?></div>
        </a>
        <a href="../admin/pharmacists.php" class="card">
            <div class="card-title">💊 Pharmacists</div>
            <div class="card-value"><?php echo $pharmacist_count; ?></div>
        </a>
        <a href="../admin/cashiers.php" class="card">
            <div class="card-title">💵 Cashiers</div>
            <div class="card-value"><?php echo $cashier_count; ?></div>
        </a>
        <a href="../admin/admin.php" class="card">
            <div class="card-title">🛠️ Admins</div>
            <div class="card-value"><?php echo $admin_count; ?></div>
        </a>
    </div>
</div>

<!-- Live Clock -->
<div id="liveClock"></div>

<script>
function updateClock() {
    const now = new Date();
    const options = { 
        hour: '2-digit', 
        minute: '2-digit', 
        second: '2-digit', 
        hour12: false 
    };
    const timeString = now.toLocaleTimeString([], options);
    document.getElementById('liveClock').textContent = timeString;
}
updateClock();
setInterval(updateClock, 1000);

// Optional: Toggle sidebar collapsed class
document.addEventListener('DOMContentLoaded', () => {
    const body = document.body;
    const sidebarToggle = document.getElementById('sidebarToggle');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', () => {
            body.classList.toggle('sidebar-collapsed');
        });
    }
});
</script>

</body>
</html>
