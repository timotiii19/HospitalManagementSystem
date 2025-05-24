<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'Nurse') {
    header("Location: ../../auth/login.php");
    exit();
}

include('../../config/db.php');

// Insert emergency entry
if (isset($_POST['log_emergency'])) {
    $patient_id = $_POST['patient_id'];
    $symptoms = $_POST['symptoms'];
    $urgency = $_POST['urgency'];

    $stmt = $conn->prepare("INSERT INTO emergency (PatientID, Symptoms, UrgencyLevel) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $patient_id, $symptoms, $urgency);
    $stmt->execute();
    header("Location: emergency.php");
    exit();
}

// Get all patients for dropdown
$patients = $conn->query("SELECT PatientID, Name FROM patients");

// Get emergency logs
$emergencies = $conn->query("
    SELECT e.*, p.Name AS PatientName 
    FROM emergency e
    JOIN patients p ON e.PatientID = p.PatientID
    ORDER BY e.LoggedAt DESC
");
include('../../includes/nurse_header.php');
include('../../includes/nurse_sidebar.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Emergency Log - Nurse Dashboard</title>
    <link rel="stylesheet" type="text/css" href="../../css/style.css">
    <style>
        body {
            background-color:rgb(255, 255, 255);
            font-family: Arial, sans-serif;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .content {
            padding: 40px;
        }

        form {
            background-color: #fff;
            padding: 20px;
            border-radius: 6px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        label {
            display: block;
            margin-top: 10px;
            font-weight: bold;
        }

        select, textarea, input[type="text"] {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
            border-radius: 4px;
            border: 1px solid #ccc;
        }

        textarea {
            resize: vertical;
            height: 80px;
        }

        button {
            margin-top: 15px;
            padding: 10px 20px;
            background-color: #007bff;
            border: none;
            color: white;
            font-size: 14px;
            border-radius: 5px;
            cursor: pointer;
        }

        button:hover {
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
    <h2>Emergency Log</h2>

    <form method="post">
        <label for="patient_id">Patient:</label>
        <select name="patient_id" id="patient_id" required>
            <option value="">Select Patient</option>
            <?php while ($p = $patients->fetch_assoc()) {
                echo "<option value='".$p['PatientID']."'>".$p['Name']."</option>";
            } ?>
        </select>

        <label for="symptoms">Symptoms:</label>
        <textarea name="symptoms" id="symptoms" required></textarea>

        <label for="urgency">Urgency Level:</label>
        <select name="urgency" id="urgency" required>
            <option value="">Select Urgency</option>
            <option value="Low">Low</option>
            <option value="Medium">Medium</option>
            <option value="High">High</option>
        </select>

        <button type="submit" name="log_emergency">Log Emergency</button>
    </form>

    <h3>Recent Emergency Records</h3>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Patient</th>
                <th>Symptoms</th>
                <th>Urgency</th>
                <th>Logged At</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $emergencies->fetch_assoc()) { ?>
            <tr>
                <td><?= $row['EmergencyID'] ?></td>
                <td><?= $row['PatientName'] ?></td>
                <td><?= $row['Symptoms'] ?></td>
                <td><?= $row['UrgencyLevel'] ?></td>
                <td><?= $row['LoggedAt'] ?></td>
            </tr>
            <?php } ?>
        </tbody>
    </table>
</div>

</body>
</html>
