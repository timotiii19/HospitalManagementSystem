<?php
include('../../config/db.php');
session_start();

if (!isset($_SESSION['role_id'])) {
    echo "Access denied.";
    exit;
}

$patientID = isset($_GET['patient_id']) ? intval($_GET['patient_id']) : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patientID = $_POST['patient_id'];
    $doctorID = $_POST['doctor_id'];
    $appointmentDate = $_POST['appointment_date'];
    $appointmentTime = $_POST['appointment_time'];
    $reason = $_POST['reason'];
    $status = 'Waiting'; // Set automatically

    $stmt = $conn->prepare("INSERT INTO appointments (PatientID, DoctorID, AppointmentDate, AppointmentTime, Reason, Status)
                            VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iissss", $patientID, $doctorID, $appointmentDate, $appointmentTime, $reason, $status);

    if ($stmt->execute()) {
        echo "<script>window.onload = function() { showSuccessModal(); }</script>";
    } else {
        echo "<script>alert('Error: " . $stmt->error . "');</script>";
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Schedule Appointment</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 30px;
            background-color: #f8f9fa;
        }

        h2 {
            margin-bottom: 20px;
            color: rgb(119, 91, 172);
        }

        form label {
            display: block;
            margin-top: 15px;
            font-weight: bold;
            color: #333;
        }

        form input, form select, form textarea {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border-radius: 6px;
            border: 1px solid #ccc;
            font-size: 14px;
            box-sizing: border-box;
        }

        button {
            background-color: #6f42c1;
            color: white;
            border: none;
            border-radius: 6px;
            padding: 12px 20px;
            margin-top: 25px;
            cursor: pointer;
            font-size: 16px;
        }

        button:hover {
            background-color: #512da8;
        }

        /* Blur effect */
        #mainContent.blurred {
            filter: blur(2px);
            pointer-events: none;
            user-select: none;
        }

        /* Modal styles */
        #successModal {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            background-color: rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(2px);
        }

        #successModalContent {
            background: white;
            padding: 20px;
            border-radius: 10px;
            max-width: 300px;
            text-align: center;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
        }

        #successModalContent button {
            margin-top: 15px;
            padding: 10px 20px;
            background-color: #6f42c1;
            border: none;
            color: white;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
        }

        #successModalContent button:hover {
            background-color: #512da8;
        }
    </style>

    <script>
    function showSuccessModal() {
        document.getElementById('successModal').style.display = 'flex';
        document.getElementById('mainContent').classList.add('blurred');
    }

    function closeModal() {
        document.getElementById('successModal').style.display = 'none';
        document.getElementById('mainContent').classList.remove('blurred');
        window.parent.postMessage('closeModal', '*');
    }
    </script>
</head>
<body>
    <h2>Schedule Appointment</h2>

    <!-- Main content wrapped -->
    <div id="mainContent">
        <form method="POST">
            <input type="hidden" name="patient_id" value="<?= htmlspecialchars($patientID) ?>">

            <label for="doctor_id">Select Doctor:</label>
            <select name="doctor_id" id="doctor_id" required>
                <option value="">Select Doctor</option>
                <?php
                $doctors = $conn->query("SELECT DoctorID, DoctorName FROM doctor");
                while ($doc = $doctors->fetch_assoc()) {
                    echo "<option value='" . htmlspecialchars($doc['DoctorID']) . "'>" . htmlspecialchars($doc['DoctorName']) . "</option>";
                }
                ?>
            </select>

            <label for="appointment_date">Appointment Date:</label>
            <input type="date" name="appointment_date" id="appointment_date" min="<?= date('Y-m-d'); ?>" required>


            <label for="appointment_time">Appointment Time:</label>
            <input type="time" name="appointment_time" id="appointment_time" required>

            <label for="reason">Reason for Visit:</label>
            <textarea name="reason" id="reason" rows="4" required></textarea>

            <button type="submit">Save Appointment</button>
        </form>
    </div>

    <!-- Success Modal -->
    <div id="successModal">
        <div id="successModalContent">
            <p>Appointment successfully added!</p>
            <button onclick="closeModal()">OK</button>
        </div>
    </div>
</body>
</html>