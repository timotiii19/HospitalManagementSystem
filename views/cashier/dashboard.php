<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'Cashier') {
    header("Location: ../../auth/cashier_login.php");
    exit();
}
include('../../config/db.php');
include('../../includes/cashier_header.php');
include('../../includes/cashier_sidebar.php');

$cashier_name = $_SESSION['username'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Cashier Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #ffffff;
            margin: 0;
            padding: 0;
            transition: all 0.3s ease;
            margin-top: 30px;
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
            grid-template-columns: repeat(2, 1fr); /* 2 cards per row */
            gap: 50px;
            margin-top: 40px;
            width: 100%;
        }

        .card {
            padding: 60px;
            background-color: #fff;
            min-height: 100px;
            min-width: 130px;
            border-radius: 15px;
            border: 6px solid #c34b4b;
            box-shadow: 8px 8px 0px #e58585;
            text-align: center;
            transition: all 0.3s ease-in-out;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-decoration: none;
            color: inherit;
        }

        .card:hover {
            transform: translateY(-6px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.25);
            border-color: #a42c2c;
        }

        .card i {
            font-size: 36px;
            margin-bottom: 12px;
            color: rgb(233, 0, 0);
        }

        .card-title {
            font-size: 18px;
            font-weight: bold;
            color: #730000;
        }
    </style>
</head>
<body>
<div class="content">
    <h3>Welcome, Cashier <?php echo htmlspecialchars($cashier_name); ?>!</h3>
    <p>This is your cashier dashboard. Use the cards below to manage your tasks.</p>

    <div class="dashboard-cards">
        <a href="/HMS-main/views/cashier/doctor.php" class="card">
            <i class="fas fa-user-md"></i>  <!-- matched icon for Doctor -->
            <div class="card-title">Doctor</div>
        </a>

        <a href="/HMS-main/views/cashier/patient_billing.php" class="card">
            <i class="fas fa-file-invoice-dollar"></i> <!-- matched icon for Billing -->
            <div class="card-title">Patient Billing</div>
        </a>

        <a href="/HMS-main/views/cashier/pharmacy.php" class="card">
            <i class="fas fa-pills"></i> <!-- matched icon for Pharmacy -->
            <div class="card-title">Pharmacy</div>
        </a>
    </div>
</div>

</body>
</html>
