<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'Nurse') {
    header("Location: ../../auth/nurse_login.php");
    exit();
}
include('../../includes/nurse_header.php');
include('../../includes/nurse_sidebar.php');
include('../../config/db.php');

// Get all departments for view-only access (nurses can only view)
$departments = $conn->query("SELECT * FROM department");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Departments - Nurse Dashboard</title>
    <link rel="stylesheet" type="text/css" href="../../css/style.css">
    <style>
        body {
            background-color:rgb(255, 255, 255); /* Light background color */
        }  
        .content {
            padding: 40px;
        }

        .button {
            margin-right: 15px;
            padding: 10px 20px;
            text-decoration: none;
            background-color: #007bff;
            color: white;
            border-radius: 5px;
            font-size: 14px;
            margin-bottom: 20px;
        }

        .button:hover {
            background-color: #0056b3;
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

        .table-striped tbody tr:nth-of-type(odd) {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>

<div class="content">
    <h2>Departments (View-Only)</h2>
    
    <!-- Department Table (View-Only for Nurses) -->
    <h3>Department List</h3>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Department ID</th>
                <th>Department Name</th>
                <th>Department Location</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $departments->fetch_assoc()) { ?>
            <tr>
                <td><?= $row['DepartmentID'] ?></td>
                <td><?= $row['DepartmentName'] ?></td>
                <td><?= $row['DepartmentLocation'] ?></td>
            </tr>
            <?php } ?>
        </tbody>
    </table>
</div>

</body>
</html>