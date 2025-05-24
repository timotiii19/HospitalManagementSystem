<?php
session_start();
include('../../config/db.php');
include('../../includes/doctor_header.php');
include('../../includes/doctor_sidebar.php');

// Fetch outpatients
$sql = "SELECT o.OutpatientID, o.PatientID, p.Name AS PatientName, o.DoctorID, o.VisitDate, o.Reason
        FROM outpatients o
        JOIN patients p ON o.PatientID = p.PatientID";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Outpatients Table</title>
    <style>
        .main-content {
            margin-left: 220px; 
            padding: 80px 20px 20px; 
            background-color: #f4f9f9;
            min-height: 100vh;
            margin-top: -50px;
        }

        .outpatient-table {
            width: 95%;
            margin: 0 auto;
            border-collapse: collapse;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        .outpatient-table th, .outpatient-table td {
            border: 1px solid #ccc;
            padding: 12px;
            text-align: center;
        }

        .outpatient-table th {
            background-color: #f5a4bf;
        }

        .outpatient-table tr:nth-child(even) {
            background-color: #fafafa;
        }

        .section-title {
            text-align: center;
            margin-bottom: 20px;
            color: #2e2e2e;
            font-size: 24px;
            font-weight: bold;
        }



        @media screen and (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding-top: 100px;
            }

            .outpatient-table {
                width: 100%;
                font-size: 14px;
            }

            .sidebar {
                display: none;
            }
        }
    </style>
</head>
<body>

<div class="main-content">
    <h2 class="section-title">Outpatients Records</h2>
    <table class="outpatient-table">
        <tr>
            <th>OutpatientID</th>
            <th>PatientID</th>
            <th>Patient Name</th>
            <th>DoctorID</th>
            <th>VisitDate</th>
            <th>Reason</th>
        </tr>
        <?php
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
            echo "<tr>
                    <td>{$row['OutpatientID']}</td>
                    <td>{$row['PatientID']}</td>
                    <td>{$row['PatientName']}</td>
                    <td>{$row['DoctorID']}</td>
                    <td>{$row['VisitDate']}</td>
                    <td>{$row['Reason']}</td>
                </tr>";
            }
        } else {
            echo "<tr><td colspan='5'>No records found</td></tr>";
        }
        $conn->close();
        ?>
    </table>
</div>

</body>
</html>