<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'Pharmacist') {
    header("Location: ../../auth/pharmacist_login.php");
    exit();
}
include('../../includes/pharmacist_sidebar.php');
include('../../includes/pharmacist_header.php');
include('../../config/db.php');

// Get patient list (read-only)
$patients = $conn->query("SELECT * FROM patients");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Patient List (Read-Only)</title>
    <link rel="stylesheet" type="text/css" href="../../css/style.css">
</head>
<body>

<div class="content">
    <h2>Patient List (Read-Only)</h2>

    <!-- Patient List -->
    <table border="1">
        <tr>
            <th>Patient ID</th>
            <th>Patient Name</th>
            <th>Date of Birth</th> <!-- For debugging -->
            <th>Age</th>
            <th>Gender</th>
        </tr>
        <?php while ($row = $patients->fetch_assoc()) {
            $age = 'Unknown';
            if (!empty($row['DateOfBirth']) && $row['DateOfBirth'] != '0000-00-00') {
                try {
                    $dob = new DateTime($row['DateOfBirth']);
                    $age = (new DateTime())->diff($dob)->y;
                } catch (Exception $e) {
                    $age = 'Error';
                }
            }
        ?>
        <tr>
            <td><?= $row['PatientID'] ?></td>
            <td><?= $row['Name'] ?></td>
            <td><?= $row['DateOfBirth'] ?></td>
            <td><?= $age ?></td>
            <td><?= $row['Sex'] ?></td>
        </tr>
        <?php } ?>
    </table>
</div>

</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Patient Management</title>
<link rel="stylesheet" href="../../css/style.css" />
<style>
    body {
        font-family: Arial, sans-serif;
        background-color: #ffffff;
    }

    .content {
        padding: 40px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }

    th, td {
        padding: 10px;
        text-align: center;
        border: 1px solid #ddd;
    }

    th {
        background-color: #f8f9fa;
    }

    form input, form button {
        padding: 5px 10px;
        margin-top: 5px;
    }

    button.view-btn {
        background-color: #6f42c1;
        color: white;
        border: none;
        border-radius: 6px;
        padding: 8px 16px;
        cursor: pointer;
    }

    button.view-btn:hover {
        background-color: #512da8;
    }

    /* Modal styles (based on your patient details page) */
    .modal {
        position: fixed;
        z-index: 999;
        left: 0; top: 0;
        width: 100%; height: 100%;
        overflow: auto;
        background-color: rgba(0,0,0,0.5);
        display: none;
        justify-content: center;
        align-items: center;
    }

    .modal-content {
        border: 2px solid purple;
        border-radius: 12px;
        padding: 40px;
        background-color: #fff;
        max-width: 500px;
        width: 90%;
        text-align: center;
        box-shadow: 0 0 12px rgba(0,0,0,0.05);
        position: relative;
    }

    .close {
        position: absolute;
        top: 15px;
        right: 20px;
        font-size: 28px;
        font-weight: bold;
        color: #888;
        cursor: pointer;
    }

    .close:hover {
        color: #000;
    }

    .profile-img {
        width: 100px;
        height: 100px;
        margin: 0 auto 30px;
        border-radius: 50%;
        background-color: #f0f0f0;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .profile-img img {
        width: 60px;
        height: 60px;
    }

    .info-row {
        display: flex;
        justify-content: space-between;
        margin: 12px 0;
        font-size: 16px;
        color: #555;
    }

    .info-row strong {
        font-weight: 600;
        color: #444;
    }

    .back-link {
        display: inline-block;
        margin-top: 30px;
        text-decoration: none;
        color: #fff;
        background-color: #6f42c1;
        padding: 10px 20px;
        border-radius: 6px;
        font-size: 14px;
    }

    .back-link:hover {
        background-color: #512da8;
    }
</style>
</head>
<body>