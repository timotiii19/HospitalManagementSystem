<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'Nurse') {
    header("Location: ../../auth/nurse_login.php");
    exit();
}

include('../../config/db.php');

// Fetch patients from the database
function getPatients($conn) {
    $query = "SELECT * FROM patients";
    $result = mysqli_query($conn, $query);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

$patients = getPatients($conn);
include('../../includes/nurse_header.php');
include('../../includes/nurse_sidebar.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Patients</title>
    <link rel="stylesheet" type="text/css" href="../../css/style.css">
    <style>
         body {
            font-family: Arial, sans-serif;
            background-color: #ffffff;
        }
        .content {
            padding: 40px;
        }
        h2 {
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        table th, table td {
            padding: 12px;
            border: 1px solid #ccc;
            text-align: left;
        }
        table th {
            background-color: #f4f4f4;
        }
        tr:hover {
            background-color: #f9f9f9;
        }
        .btn {
            padding: 8px 16px;
            text-decoration: none;
            background-color: #007bff;
            color: white;
            border-radius: 4px;
            margin-right: 10px;
            display: inline-block;
        }
        .btn:hover {
            background-color: #0056b3;
        }
        .btn-sm {
            padding: 6px 12px;
            font-size: 14px;
            border-radius: 4px;
        }
        .btn-primary {
            background-color: #17a2b8;
        }
        .btn-primary:hover {
            background-color: #117a8b;
        }
        .btn-success {
            background-color: #28a745;
        }
        .btn-success:hover {
            background-color: #1e7e34;
        }
    </style>
</head>
<body>
<div class="content">
    <h2>View Patients</h2>
<div style="margin-bottom: 20px;">
    <a href="export_inpatient_pdf.php" class="btn btn-success" target="_blank">
        <i class="fa fa-file-pdf"></i> Export Inpatients PDF
    </a>
    <a href="export_outpatient_pdf.php" class="btn btn-primary" target="_blank">
        <i class="fa fa-file-pdf"></i> Export Outpatients PDF
    </a>
</div>

    <table>
        <thead>
            <tr>
                <th>Patient ID</th>
                <th>Name</th>
                <th>Date of Birth</th>
                <th>Contact</th>
                <th>Sex</th>
                <th>Address</th>
                <th>Patient Type</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($patients) > 0): ?>
                <?php foreach ($patients as $patient): ?>
                    <tr>
                        <td><?= htmlspecialchars($patient['PatientID']) ?></td>
                        <td><?= htmlspecialchars($patient['Name']) ?></td>
                        <td><?= htmlspecialchars($patient['DateOfBirth']) ?></td>
                        <td><?= htmlspecialchars($patient['Contact']) ?></td>
                        <td><?= htmlspecialchars($patient['Sex']) ?></td>
                        <td><?= htmlspecialchars($patient['Address']) ?></td>
                        <td><?= htmlspecialchars($patient['PatientType']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="7">No patients found</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>
