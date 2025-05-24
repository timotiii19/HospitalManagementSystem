<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'Pharmacist') {
    header("Location: ../../auth/pharmacist_login.php");
    exit();
}

date_default_timezone_set('Asia/Manila');

include('../../includes/pharmacist_header.php');
include('../../includes/pharmacist_sidebar.php');
include('../../config/db.php');

$pharmacist_name = $_SESSION['username'];

$hour = date('H');
if ($hour < 12) {
    $greet = "Good Morning";
} elseif ($hour < 18) {
    $greet = "Good Afternoon";
} else {
    $greet = "Good Evening";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Pharmacist Dashboard</title>
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
        }

        h3 {
            font-size: 28px;
            color: #730000;
        }

        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 30px;
            margin-top: 40px;
        }

        .card {
            padding: 40px;
            min-height: 100px;
            background-color: #fff;
            border-radius: 15px;
            border: 6px solid #c34b4b;
            box-shadow: 8px 8px 0px #e58585;
            text-align: center;
            text-decoration: none;
            color: inherit;
            transition: all 0.3s ease-in-out;
        }

        .card:hover {
            transform: translateY(-6px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.25);
            color:rgb(233, 0, 0);
        }

        .card-title {
            font-size: 26px;
            font-weight: bold;
            color: #730000;
            margin-bottom: 10px;
        }

        .card-icon {
            font-size: 40px;
            color:rgb(233, 0, 0);
            margin-bottom: 10px;
        }
    </style>
</head>
<body>

<div class="content">
    <div style="margin-top: 70px;">
        <h3><?php echo $greet . ", Pharmacist " . htmlspecialchars($pharmacist_name); ?>!</h3>
        <p>This is your pharmacist dashboard. Use the cards below to manage medications and inventory.</p>
    </div>

    <div class="dashboard-cards">
        <a href="/HMS-main/views/pharmacist/patientmedication.php" class="card">
            <div class="card-icon"><i class="fa fa-notes-medical"></i></div>
            <div class="card-title">Patient Medication</div>
        </a>
        <a href="/HMS-main/views/pharmacist/pharmacy.php" class="card">
            <div class="card-icon"><i class="fa fa-capsules"></i></div>
            <div class="card-title">Pharmacy</div>
        </a>
        <a href="/HMS-main/views/pharmacist/generate_pharmacy_pdf.php" class="card" target="_blank">
            <div class="card-icon"><i class="fa fa-file-pdf"></i></div>
            <div class="card-title">Export Pharmacy PDF</div>
        </a>
    </div>
</div>
</body>
</html>