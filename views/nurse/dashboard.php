<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'Nurse') {
    header("Location: ../../auth/login.php");
    exit();
}

date_default_timezone_set('Asia/Manila');

include('../../includes/nurse_header.php');
include('../../includes/nurse_sidebar.php');

$nurse_name = $_SESSION['username'];
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
    <title>Nurse Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #fefefe;
            margin: 0;
            padding: 0;
        }

        .content {
            padding: 40px;
            margin-left: 220px;
            box-sizing: border-box;
            transition: all 0.3s ease;
            margin-top: 40px
        }

        h3 {
            font-size: 28px;
            color: rgb(216, 43, 71);
        }

        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
            margin-top: 40px;
        }


        .card {
            background-color: #fff;
            padding: 30px;
            border-radius: 15px;
            border: 5px solid #e25d6a;
            box-shadow: 6px 6px 0px #f4a6ac;
            text-align: center;
            transition: 0.3s;
            text-decoration: none;
            color: inherit;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .card:hover {
            transform: translateY(-5px);
            border-color: #c72646;
            box-shadow: 0 10px 25px rgba(255, 0, 64, 0.2);
        }

        .card-title {
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #c72646;
        }

        .card-icon {
            font-size: 40px;
            margin-bottom: 10px;
        }

        #liveClock {
            position: fixed;
            top: 100px;
            right: 20px;
            background-color: #c72646;
            color: white;
            padding: 8px 15px;
            border-radius: 8px;
            font-family: 'Courier New', Courier, monospace;
            font-size: 16px;
            font-weight: bold;
            box-shadow: 2px 2px 6px rgba(0,0,0,0.2);
            z-index: 1;
        }
    </style>
</head>
<body>

<div class="content">
    <h3><?php echo $greet . ", Nurse " . htmlspecialchars($nurse_name); ?>!</h3>
    <p>This is your nurse dashboard. Use the cards below to navigate quickly.</p>

    <div class="dashboard-cards">
        <a href="add_patient.php" class="card">
            <div class="card-icon"><i class="fa fa-user-plus"></i></div>
            <div class="card-title">Add Patient</div>
        </a>
        <a href="patient.php" class="card">
            <div class="card-icon"><i class="fa fa-users"></i></div>
            <div class="card-title">View Patients</div>
        </a>
        <a href="inpatient.php" class="card">
            <div class="card-icon"><i class="fa fa-bed"></i></div>
            <div class="card-title">Inpatients</div>
        </a>
        <a href="outpatient.php" class="card">
            <div class="card-icon"><i class="fa fa-walking"></i></div>
            <div class="card-title">Outpatients</div>
        </a>

        <a href="doctorschedule.php" class="card">
            <div class="card-icon"><i class="fa fa-user-clock"></i></div>
            <div class="card-title">Doctor Schedule</div>
        </a>
        <a href="department.php" class="card">
            <div class="card-icon"><i class="fa fa-building"></i></div>
            <div class="card-title">Departments</div>
        </a>
        <a href="location.php" class="card">
            <div class="card-icon"><i class="fa fa-map-marker-alt"></i></div>
            <div class="card-title">Location</div>
        </a>
        <a href="emergency.php" class="card">
            <div class="card-icon"><i class="fa fa-ambulance"></i></div>
            <div class="card-title">Emergency</div>
        </a>
    </div>
</div>

<div id="liveClock"></div>

<script>
    function updateClock() {
        const now = new Date();
        const timeString = now.toLocaleTimeString([], {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: false
        });
        document.getElementById('liveClock').textContent = timeString;
    }
    updateClock();
    setInterval(updateClock, 1000);
</script>

</body>
</html>
