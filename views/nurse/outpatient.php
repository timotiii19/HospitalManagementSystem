<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'Nurse') {
    header("Location: ../../auth/nurse_login.php");
    exit();
}

include('../../config/db.php');
$nurseID = $_SESSION['role_id']; // use assigned nurse ID

$outpatients = $conn->prepare("
    SELECT o.OutpatientID, o.PatientID,
           p.Name AS PatientName, p.Sex,
           v.Temperature, v.BloodPressure, v.Pulse, v.NurseNotes
    FROM outpatients o
    JOIN patients p ON o.PatientID = p.PatientID
    LEFT JOIN patientvitals v ON o.PatientID = v.patientID
    
");

$outpatients->execute();
$result = $outpatients->get_result();

if (isset($_POST['update_outpatient'])) {
    $patient_id = $_POST['patient_id'];
    $temperature = $_POST['temperature'];
    $bloodpressure = $_POST['bloodpressure'];
    $pulse = $_POST['pulse'] / 4;
    $nurse_notes = $_POST['nurse_notes'];

    // Check if vitals already exist for this patient
    $check = $conn->prepare("SELECT patientID FROM patientvitals WHERE patientID = ?");
    $check->bind_param("i", $patient_id);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        // UPDATE with nurse ID
        $stmt = $conn->prepare("UPDATE patientvitals SET Temperature = ?, BloodPressure = ?, Pulse = ?, NurseNotes = ?, AssignedNurseID = ? WHERE patientID = ?");
        $stmt->bind_param("ssssii", $temperature, $bloodpressure, $pulse, $nurse_notes, $nurseID, $patient_id);
    } else {
        // INSERT with nurse ID
        $stmt = $conn->prepare("INSERT INTO patientvitals (patientID, Temperature, BloodPressure, Pulse, NurseNotes, AssignedNurseID) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssi", $patient_id, $temperature, $bloodpressure, $pulse, $nurse_notes, $nurseID);
    }

    $stmt->execute();
    header("Location: outpatient.php");
    exit();
}


include('../../includes/nurse_header.php');
include('../../includes/nurse_sidebar.php');
?>

<!DOCTYPE html>
<html>
<head>
    <title>Outpatient Management</title>
    <link rel="stylesheet" type="text/css" href="../../css/style.css">
</head>
<body>
<div class="content">
    <h2>Outpatient Management</h2>
    <table border="1">
        <tr>
            <th>Outpatient ID</th>
            <th>Patient Name</th>
            <th>Gender</th>
            <th>Temperature (°C)</th>
            <th>Blood Pressure (mmHg)</th>
            <th>Pulse (bpm)</th>
            <th>Nurse Notes</th>
            <th>Action</th>
            <th>View</th>
        </tr>
        <?php
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $pulseDisplayed = isset($row['Pulse']) ? $row['Pulse'] * 4 : '';
        ?>
        <tr>
            <form method="POST">
                <td><?php echo htmlspecialchars($row['OutpatientID']); ?></td>
                <td><?php echo htmlspecialchars($row['PatientName']); ?></td>
                <td><?php echo htmlspecialchars($row['Sex']); ?></td>
                <td><input type="text" name="temperature" value="<?php echo htmlspecialchars($row['Temperature'] ?? ''); ?>" placeholder="e.g. 36.6" required></td>
                <td><input type="text" name="bloodpressure" value="<?php echo htmlspecialchars($row['BloodPressure'] ?? ''); ?>" placeholder="e.g. 120/80" required></td>
                <td><input type="text" name="pulse" value="<?php echo htmlspecialchars($pulseDisplayed); ?>" placeholder="e.g. 72" required></td>
                <td><textarea name="nurse_notes" required><?php echo htmlspecialchars($row['NurseNotes'] ?? ''); ?></textarea></td>
                <td>
                    <input type="hidden" name="patient_id" value="<?php echo $row['PatientID']; ?>">
                    <button class="upd-btn" type="submit" name="update_outpatient">Update</button>
                </td>
            </form>
            <td>
                <button class="view-btn" type="button"
                    onclick="openModal(
                        '<?= htmlspecialchars($row['PatientID']) ?>',
                        '<?= htmlspecialchars($row['PatientName']) ?>',
                        '<?= htmlspecialchars($row['Sex']) ?>',
                        'Temp: <?= htmlspecialchars($row['Temperature'] ?? 'N/A') ?> °C | BP: <?= htmlspecialchars($row['BloodPressure'] ?? 'N/A') ?> | Pulse: <?= $pulseDisplayed !== '' ? htmlspecialchars($pulseDisplayed) : 'N/A' ?> bpm',
                        'Outpatient',
                        '<?= $_SESSION['username'] ?>'
                    )">
                    View Details
                </button>
            </td>
        </tr>
        <?php
            }
        } else {
            echo "<tr><td colspan='9' style='text-align: center; font-style: italic; color: #666;'>No outpatients assigned to you.</td></tr>";
        }
        ?>
    </table>
</div>


<div id="detailModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <div class="profile-img"><img src="../../assets/patient-icon.png" alt="Patient"></div>
        <div class="info-row"><strong>Patient ID:</strong> <span id="modalPatientID"></span></div>
        <div class="info-row"><strong>Name:</strong> <span id="modalName"></span></div>
        <div class="info-row"><strong>Gender:</strong> <span id="modalSex"></span></div>
        <div class="info-row"><strong>Vitals:</strong> <span id="modalVitals"><span></div>
        <div class="info-row"><strong>Type:</strong> <span id="modalType"></span></div>
        <div class="info-row"><strong>Assigned Nurse:</strong> <span id="modalNurse"></span></div>
    </div>
</div>

<script>
function openModal(id, name, sex, vitals, type, nurse) {
    document.getElementById('modalPatientID').innerText = id;
    document.getElementById('modalName').innerText = name;
    document.getElementById('modalSex').innerText = sex;
    document.getElementById('modalVitals').innerText = vitals;
    document.getElementById('modalType').innerText = type;
    document.getElementById('modalNurse').innerText = nurse;
    document.getElementById('detailModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('detailModal').style.display = 'none';
}
</script>

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
    form input, form textarea, form button {
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

    button.upd-btn {
        background-color:rgb(27, 223, 145);
        color: white;
        border: none;
        border-radius: 6px;
        padding: 8px 16px;
        cursor: pointer;
    }
    button.upd-btn:hover {
        background-color:rgb(30, 211, 218);
    }
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
</style>

</body>
</html>