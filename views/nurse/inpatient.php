<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'Nurse') {
    header("Location: ../../auth/nurse_login.php");
    exit();
}
include('../../config/db.php');

// Fetch nurse ID from session
$nurse_id = $_SESSION['role_id'];

// Fetch all locations for dropdown
$locations_result = $conn->query("SELECT LocationID, LocationName FROM locations");
$locations = [];
while ($loc = $locations_result->fetch_assoc()) {
    $locations[] = $loc;
}

// Get inpatients (assigned to this nurse or all – adjust if filtering is needed)
$inpatients = $conn->prepare("
    SELECT i.InpatientID, i.PatientID, i.LocationID, 
           p.Name AS PatientName, p.Sex, p.NurseID,
           v.Temperature, v.BloodPressure, v.Pulse, v.NurseNotes
    FROM inpatients i
    JOIN patients p ON i.PatientID = p.PatientID
    LEFT JOIN patientvitals v ON i.PatientID = v.PatientID
");
$inpatients->execute();
$inpatients = $inpatients->get_result();

// Handle update
if (isset($_POST['update_inpatient'])) {
    $inpatient_id = $_POST['inpatient_id'];
    $location = $_POST['location_id'];
    $temperature = $_POST['temperature'];
    $blood_pressure = $_POST['blood_pressure'];
    $pulse = $_POST['pulse'];
    $nurse_notes = $_POST['nurse_notes'];

    // Step 1: Get PatientID
    $stmt = $conn->prepare("SELECT p.PatientID 
                            FROM inpatients i
                            JOIN patients p ON i.PatientID = p.PatientID
                            WHERE i.InpatientID = ?");
    $stmt->bind_param("i", $inpatient_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $inpatient = $res->fetch_assoc();
    $patient_id = $inpatient['PatientID'];

    // Step 1.5: Update assigned nurse
    $updateNurse = $conn->prepare("UPDATE patients SET NurseID = ? WHERE PatientID = ?");
    $updateNurse->bind_param("ii", $nurse_id, $patient_id);
    $updateNurse->execute();

    // Step 2: Update location
    $stmt = $conn->prepare("UPDATE inpatients SET LocationID = ? WHERE InpatientID = ?");
    $stmt->bind_param("ii", $location, $inpatient_id);
    $stmt->execute();

    // Step 3: Check if vitals exist
    $check = $conn->prepare("SELECT VitalID FROM patientvitals WHERE PatientID = ?");
    $check->bind_param("i", $patient_id);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        // Update vitals
        $stmt3 = $conn->prepare("UPDATE patientvitals 
            SET Temperature = ?, BloodPressure = ?, Pulse = ?, NurseNotes = ?, NurseID = ? 
            WHERE PatientID = ?");
        $stmt3->bind_param("ssssii", $temperature, $blood_pressure, $pulse, $nurse_notes, $nurse_id, $patient_id);
        $stmt3->execute();
    } else {
        // Insert new vitals
        $stmt3 = $conn->prepare("INSERT INTO patientvitals 
            (PatientID, Temperature, BloodPressure, Pulse, NurseNotes, NurseID) 
            VALUES (?, ?, ?, ?, ?, ?)");
        $stmt3->bind_param("issssi", $patient_id, $temperature, $blood_pressure, $pulse, $nurse_notes, $nurse_id);
        $stmt3->execute();
    }

    header("Location: inpatient.php");
    exit();
}

include('../../includes/nurse_header.php');
include('../../includes/nurse_sidebar.php');
?>



<!DOCTYPE html>
<html>
<head>
    <title>Inpatient Management</title>
    <link rel="stylesheet" href="../../css/style.css">
</head>
<body>

<div class="content">
    <h2>Inpatient Management</h2>

    <table border="1">
        <tr>
            <th>Inpatient ID</th>
            <th>Patient Name</th>
            <th>Temperature (°C)</th>
            <th>Blood Pressure</th>
            <th>Pulse (bpm)</th>
            <th>Nurse Notes</th>
            <th>Location</th>
            <th>Update</th>
            <th>Action</th>
        </tr>
        <?php if ($inpatients->num_rows > 0): ?>
    <?php while ($row = $inpatients->fetch_assoc()): ?>
        <tr>
            <form method="POST" style="margin: 0;">
                <td><?= $row['InpatientID'] ?></td>
                <td><?= htmlspecialchars($row['PatientName']) ?></td>
                <td><input type="text" name="temperature" value="<?= htmlspecialchars($row['Temperature']) ?>" placeholder="e.g. 36.6" required></td>
                <td><input type="text" name="blood_pressure" value="<?= htmlspecialchars($row['BloodPressure']) ?>" placeholder="e.g. 120/80" required></td>
                <td><input type="text" name="pulse" value="<?= htmlspecialchars($row['Pulse']) ?>" placeholder="e.g. 72" required></td>
                <td><textarea name="nurse_notes" placeholder="Additional notes..." required><?= htmlspecialchars($row['NurseNotes']) ?></textarea></td>
                <td>
                    <select name="location_id" required>
                        <?php foreach ($locations as $loc): ?>
                            <option value="<?= $loc['LocationID'] ?>" <?= $loc['LocationID'] == $row['LocationID'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($loc['LocationName']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td>
                    <input type="hidden" name="inpatient_id" value="<?= $row['InpatientID'] ?>">
                    <button class="upd-btn" type="submit" name="update_inpatient">Update</button>
                </td>
                <td>
                    <button class="view-btn" type="button"
                        onclick="openModal(
                            '<?= htmlspecialchars($row['PatientID']) ?>',
                            '<?= htmlspecialchars($row['PatientName']) ?>',
                            '<?= htmlspecialchars($row['Sex']) ?>',
                            'Temp: <?= htmlspecialchars($row['Temperature']) ?> °C | BP: <?= htmlspecialchars($row['BloodPressure']) ?> | Pulse: <?= htmlspecialchars($row['Pulse']) ?> bpm',
                            'Inpatient',
                            '<?= $_SESSION['username'] ?>'
                        )">
                        View Details
                    </button>
                </td>
            </form>
        </tr>
         <?php endwhile; ?>
<?php else: ?>
    <tr>
        <td colspan="9" style="text-align: center; font-style: italic; color: #666;">
            No inpatients.
        </td>
    </tr>
<?php endif; ?>

<!-- Modal for viewing patient -->
<div id="detailModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <div class="profile-img"><img src="../../images/profile.png" alt="Patient"></div>
        <div class="info-row"><strong>Patient ID:</strong> <span id="modalPatientID"></span></div>
        <div class="info-row"><strong>Name:</strong> <span id="modalName"></span></div>
        <div class="info-row"><strong>Gender:</strong> <span id="modalSex"></span></div>
        <div class="info-row"><strong>Vital Signs:</strong> <span id="modalVitals"></span></div>
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
/* Same style definitions as your original */
body {
    font-family: Arial, sans-serif;
    background-color: #ffffff;
}
.content {
    overflow-x: auto;
    max-width: 100%;
    padding: 40px;
    box-sizing: border-box;
}

table {
    table-layout: fixed;
    width: 100%;
    max-width: 100%;
    margin: 20px auto;
    border-collapse: collapse;
}


th, td {
    padding: 10px;
    text-align: center;
    border: 1px solid #ddd;
    white-space: normal;
    word-wrap: break-word;
}
th {
    background-color: #f8f9fa;
}

form input[type="text"],
textarea,
select {
    max-width: 100px;     /* maximum width */
    width: 100%;          /* fill the cell width */
    box-sizing: border-box; /* include padding & border inside width */
    font-size: 14px;
    padding: 4px 8px;
}

input[name="temperature"],
input[name="blood_pressure"],
input[name="pulse"] {
    width: 80px;           /* fixed width suitable for short inputs */
    max-width: 80px;       /* prevent stretching */
    padding: 4px 8px;
    font-size: 14px;
    box-sizing: border-box;
    text-align: center;    /* center the numbers for neatness */
}

/* Target first column (Inpatient ID) */
table th:first-child,
table td:first-child {
    width: 90px;       /* or smaller, like 50px */
    max-width: 100px;
    white-space: nowrap;  /* keep ID on one line */
    text-align: center;
    padding: 8px 4px;     /* less padding to save space */
    overflow: hidden;
    text-overflow: ellipsis;
}

button {
    max-width: 100px;
    font-size: 14px;
    padding: 4px 8px;
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
button.upd-btn {
    background-color: rgb(27, 223, 145);
    color: white;
    border: none;
    border-radius: 6px;
    padding: 8px 16px;
    cursor: pointer;
}
button.upd-btn:hover {
    background-color: rgb(30, 211, 218);
}
</style>

</body>
</html>