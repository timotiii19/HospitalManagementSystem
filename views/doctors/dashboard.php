<?php 
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'Doctor') {
    header("Location: ../../auth/doctor_login.php");
    exit();
}

date_default_timezone_set('Asia/Manila'); 

include('../../includes/doctor_header.php');
include('../../includes/doctor_sidebar.php');
include('../../config/db.php');

$doctor_name = $_SESSION['username'];

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
    <title>Doctor Dashboard</title>
    <link rel="stylesheet" type="text/css" href="../../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
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
            margin-top: 40px;
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
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
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
            color:rgb(233, 0, 0);
        }

        .card-title {
            font-size: 18px;
            font-weight: bold;
            color: #730000;
        }

        /* Dropdown card styling */
        .card.dropdown {
            position: relative;
        }

        .card.dropdown .dropdown-content {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            background-color: #fff;
            padding: 10px 15px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
            border-radius: 15px;
            width: 100%;
            z-index: 1000;
            text-align: center;
            border: 6px solid #c34b4b;
            box-shadow: 8px 8px 0px #e58585;
            font-size: 14px;
        }

        .card.dropdown:hover .dropdown-content {
            display: block;
        }

        .card.dropdown .dropdown-content a {
            display: block;
            padding: 8px 0;
            color:rgb(238, 60, 102);
            text-decoration: none;
            font-size: 16px;
            text-align: center;
            border-bottom: 1px solid #ddd;
        }

        .card.dropdown .dropdown-content a:last-child {
            border-bottom: none;
        }

        .card.dropdown .dropdown-content a:hover {
            text-decoration: underline;
        }

        /* Individual Hover Styling for Dropdown Options */
        .card.dropdown .dropdown-content a {
            display: none; /* Hide all initially */
        }

        .card.dropdown:hover .dropdown-content a {
            display: block; /* Show when card is hovered */
        }

        .card.dropdown .dropdown-content .inpatient,
        .card.dropdown .dropdown-content .outpatient,
        .card.dropdown .dropdown-content .view-past-schedule,
        .card.dropdown .dropdown-content .appointment {
            display: none;
        }

        .card.dropdown .dropdown-content .appointment:hover {
            display: block;
        }

        .card.dropdown .dropdown-content .inpatient:hover {
            display: block;
        }

        .card.dropdown .dropdown-content .outpatient:hover {
            display: block;
        }

        .card.dropdown .dropdown-content .view-past-schedule:hover {
            display: block;
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
    <div style="margin-top: 0px;">
        <h3><?php echo $greet . ", Dr. " . htmlspecialchars($doctor_name); ?>!</h3>
        <p>This is your doctor dashboard. Use the cards below to manage your tasks.</p>
    </div>

    <div class="dashboard-cards">
        <!-- Removed Dashboard Card -->

        <a href="/HMS-main/views/doctors/appointments.php" class="card">
            <i class="fa fa-calendar-check"></i>
            <div class="card-title">Patient Confirmation</div>
        </a>

        <a href="/HMS-main/views/doctors/doctorschedule.php" class="card">
            <i class="fa fa-calendar-day"></i>
            <div class="card-title">Appointment</div>
        </a>

        <a href="/HMS-main/views/doctors/inpatient.php" class="card">
            <i class="fa fa-procedures"></i>
            <div class="card-title">Inpatient</div>
        </a>

        <a href="/HMS-main/views/doctors/outpatient.php" class="card">
            <i class="fa fa-user-md"></i>
            <div class="card-title">Outpatient</div>
        </a>

        <a href="/HMS-main/views/doctors/pastschedules.php" class="card">
            <i class="fa fa-history"></i>
            <div class="card-title">Past Schedules</div>
        </a>


        <a href="/HMS-main/views/doctors/labprocedure.php" class="card">
            <i class="fa fa-flask"></i>
            <div class="card-title">Lab Procedure</div>
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