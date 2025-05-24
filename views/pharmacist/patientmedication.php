<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'Pharmacist') {
    header("Location: ../../auth/pharmacist_login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['dispense'])) {
    include('../../config/db.php');
    $medication_id = $_POST['medication_id'];

    $update_query = "UPDATE patientmedication SET Status = 'Already Dispensed' WHERE PatientMedicationID = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("i", $medication_id);
    
    if ($stmt->execute()) {
        header("Location: patientmedication.php?status=success");
        exit();
    } else {
        header("Location: patientmedication.php?status=error");
        exit();
    }
}

// Only include UI after redirects
include('../../includes/pharmacist_sidebar.php');
include('../../includes/pharmacist_header.php');
include('../../config/db.php');


$medications = $conn->query("SELECT pm.*, p.Name AS PatientName, d.DoctorName AS DoctorName 
                            FROM patientmedication pm
                            JOIN patients p ON pm.PatientID = p.PatientID
                            JOIN doctor d ON pm.DoctorID = d.DoctorID");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Patient Medication</title>
    <link rel="stylesheet" type="text/css" href="../../css/style.css">
</head>
<body>

<div class="content">
    <h2>Patient Medication</h2>

    <?php if (isset($_GET['status'])): ?>
        <?php if ($_GET['status'] == 'success'): ?>
            <p style="color: green;">Medication has been successfully dispensed!</p>
        <?php elseif ($_GET['status'] == 'error'): ?>
            <p style="color: red;">There was an error dispensing the medication.</p>
        <?php endif; ?>
    <?php endif; ?>

    <table border="1">
        <tr>
            <th>Patient Name</th>
            <th>Doctor Name</th>
            <th>Medication</th>
            <th>Dosage</th>
            <th>Frequency</th> 
            <th>Start Date</th>
            <th>End Date</th>
            <th>Action</th> 
        </tr>
        <?php while ($row = $medications->fetch_assoc()) { ?>
        <tr>
            <td><?= $row['PatientName'] ?></td>
            <td><?= $row['DoctorName'] ?></td>
            <td><?= $row['MedicineName'] ?></td>
            <td><?= $row['Dosage'] ?></td>
            <td><?= $row['Frequency'] ?></td>
            <td><?= $row['StartDate'] ?></td>
            <td><?= $row['EndDate'] ?></td>
            <td>
                <?php if ($row['Status'] == 'Not Yet Dispensed') { ?>
                    <form method="post" action="patientmedication.php">
                        <input type="hidden" name="medication_id" value="<?= $row['PatientMedicationID'] ?>">
                        <button type="submit" name="dispense" class="dispense-btn">Dispense</button>
                    </form>
                <?php } else { ?>
                    <span>Already Dispensed</span>
                <?php } ?>
            </td>
        </tr>
        <?php } ?>
    </table>
</div>

</body>
</html>

<style>
    body {
        font-family: Arial, sans-serif;
        background-color: #ffffff;
    }

    .content {
        padding: 40px;
        margin-top: -20px;
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
        background-color:#810000;
        color:rgb(255, 255, 255);
    }

    form input, form button {
        padding: 5px 10px;
        margin-top: 5px;
    }

    button.dispense-btn {
        background-color: #dc3545;
        color: white;
        border: none;
        border-radius: 6px;
        padding: 8px 16px;
        cursor: pointer;
    }

    button.dispense-btn:hover {
        background-color: #c82333;
    }
</style>