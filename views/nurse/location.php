<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'Nurse') {
    header("Location: ../../auth/login.php");
    exit();
}
include('../../includes/nurse_header.php');
include('../../includes/nurse_sidebar.php');
include('../../config/db.php');

// Get all locations
$locations = $conn->query("SELECT * FROM locations");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Locations - Nurse Dashboard</title>
    <link rel="stylesheet" type="text/css" href="../../css/style.css">
    <style>
        body{
            background-color: #fffdfd;
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
            background-color:rgb(227, 227, 227);
        }
        
    </style>
</head>
<body>

<div class="content">
    <h2>Locations (View-Only)</h2>
    
    <h3>Location List</h3>
    <table>
        <thead>
            <tr>
                <th>Location ID</th>
                <th>Building</th>
                <th>Room Number</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $locations->fetch_assoc()) { ?>
            <tr>
                <td><?= $row['LocationID'] ?></td>
                <td><?= $row['Building'] ?></td>
                <td><?= $row['RoomNumber'] ?></td>
            </tr>
            <?php } ?>
        </tbody>
    </table>
</div>

</body>
</html>