<?php
session_start();
include('../../config/db.php');
include('../../includes/doctor_header.php');
include('../../includes/doctor_sidebar.php');

// Handle AJAX POST for assigning location
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'assign_location') {
    $inpatientId = intval($_POST['inpatientId'] ?? 0);
    $locationId = intval($_POST['locationId'] ?? 0);

    if (!$inpatientId || !$locationId) {
        http_response_code(400);
        echo "Invalid input.";
        exit;
    }

    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare("SELECT RoomCapacity FROM locations WHERE LocationID = ? FOR UPDATE");
        $stmt->bind_param("i", $locationId);
        $stmt->execute();
        $result = $stmt->get_result();
        $location = $result->fetch_assoc();
        $stmt->close();

        if (!$location) {
            throw new Exception("Location not found.");
        }

        $stmt = $conn->prepare("
            SELECT COUNT(*) as occupied 
            FROM inpatients 
            WHERE LocationID = ? AND (DischargeDate IS NULL OR DischargeDate > CURRENT_DATE())
        ");
        $stmt->bind_param("i", $locationId);
        $stmt->execute();
        $res = $stmt->get_result();
        $countData = $res->fetch_assoc();
        $stmt->close();

        if ($countData['occupied'] >= $location['RoomCapacity']) {
            throw new Exception("Selected location is full.");
        }

        $stmt = $conn->prepare("UPDATE inpatients SET LocationID = ? WHERE InpatientID = ?");
        $stmt->bind_param("ii", $locationId, $inpatientId);
        if (!$stmt->execute()) {
            throw new Exception("Failed to update inpatient location.");
        }
        $stmt->close();

        $conn->commit();
        echo "success";
    } catch (Exception $e) {
        $conn->rollback();
        http_response_code(500);
        echo "Failed: " . $e->getMessage();
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'discharge_inpatient') {
    $inpatientId = intval($_POST['inpatientId'] ?? 0);

    if (!$inpatientId) {
        http_response_code(400);
        echo "Invalid inpatient ID.";
        exit;
    }

    $stmt = $conn->prepare("UPDATE inpatients SET DischargeDate = NOW() WHERE InpatientID = ? AND DischargeDate IS NULL");
    $stmt->bind_param("i", $inpatientId);

    if ($stmt->execute() && $stmt->affected_rows > 0) {
        $dateStmt = $conn->prepare("SELECT DischargeDate FROM inpatients WHERE InpatientID = ?");
        $dateStmt->bind_param("i", $inpatientId);
        $dateStmt->execute();
        $result = $dateStmt->get_result();
        $dateRow = $result->fetch_assoc();
        echo "success|" . $dateRow['DischargeDate'];
        $dateStmt->close();
    } else {
        echo "Failed to discharge. Either already discharged or invalid ID.";
    }

    $stmt->close();
    exit;
}
// AJAX handler to get patient vitals
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'get_patient_vitals') {
    $patientId = intval($_POST['PatientID'] ?? 0);

    if (!$patientId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid Patient ID']);
        exit;
    }

    $stmt = $conn->prepare("SELECT Temperature, BloodPressure, Pulse, NurseNotes, RecordedAt FROM patientvitals WHERE PatientID = ?");
    $stmt->bind_param("i", $patientId);
    $stmt->execute();
    $result = $stmt->get_result();
    $vitals = $result->fetch_assoc();
    $stmt->close();

    if ($vitals) {
        echo json_encode($vitals);
    } else {
        echo json_encode(['error' => 'No vitals found for this patient']);
    }
    exit;
}



$sql = "SELECT PatientID, DoctorID, ScheduleDate, EndTime FROM doctorschedule WHERE Status = 'Inpatient'";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $patientID = $row['PatientID'];
        $doctorID = $row['DoctorID'];
        $scheduleDate = $row['ScheduleDate'];
        $endTime = $row['EndTime'];
        
        // Combine date and time into a datetime string
        $admissionDate = $scheduleDate . ' ' . $endTime;

        $check = $conn->prepare("SELECT * FROM inpatients WHERE PatientID = ? AND DoctorID = ?");
        $check->bind_param("ii", $patientID, $doctorID);
        $check->execute();
        $checkResult = $check->get_result();

        if ($checkResult->num_rows === 0) {
            $stmt = $conn->prepare("INSERT INTO inpatients (PatientID, DoctorID, DepartmentID, AdmissionDate, DischargeDate, MedicalRecord, LocationID) VALUES (?, ?, ?, ?, NULL, NULL, NULL)");
            $stmt->bind_param("iiis", $patientID, $doctorID, $departmentID, $admissionDate);
            $stmt->execute();
            $stmt->close();
        }
        $check->close();
    }
}

$inpatients = $conn->query("
    SELECT i.*, l.LocationName, p.Name
    FROM inpatients i
    LEFT JOIN locations l ON i.LocationID = l.LocationID
    LEFT JOIN patients p ON i.PatientID = p.PatientID
    ORDER BY i.AdmissionDate DESC
");



$locationsSql = "
    SELECT 
        l.LocationID, l.LocationName, l.ConditionType, l.RoomType, l.RoomCapacity,
        l.Building, l.Floor, l.RoomNumber,
        IFNULL(inpatient_counts.occupied, 0) AS OccupiedBeds
    FROM locations l
    LEFT JOIN (
        SELECT LocationID, COUNT(*) AS occupied
        FROM inpatients
        WHERE DischargeDate IS NULL OR DischargeDate > NOW()
        GROUP BY LocationID
    ) inpatient_counts ON inpatient_counts.LocationID = l.LocationID
    ORDER BY l.Building, l.Floor, l.RoomNumber
";

$locationsResult = $conn->query($locationsSql);

$locations = [];
if ($locationsResult) {
    while ($loc = $locationsResult->fetch_assoc()) {
        $building = $loc['Building'];
        $floor = $loc['Floor'];
        $locations[$building][$floor][] = $loc;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Inpatients - Assign Location</title>
<style>
/* Add your CSS here */
body {
    font-family: Arial, sans-serif;
    margin: 0px;
    margin-top: 30px;
}
.tabs {
    display: flex;
    margin-bottom: 10px;
}
.tab {
    padding: 10px 20px;
    margin-right: 5px;
    background: #eb6d9b;
    color: white;
    cursor: pointer;
    border-radius: 5px 5px 0 0;
}
.tab.active {
    background: #c13d70;
}
.floor-section {
    border: 1px solid #ddd;
    margin-bottom: 15px;
    border-radius: 0 5px 5px 5px;
    padding: 10px;
}
.floor-header {
    font-weight: bold;
    cursor: pointer;
    margin-bottom: 10px;
}
.floor-header:hover {
    color: #c13d70;
}
.room-table {
    width: 100%;
    border-collapse: collapse;
}
.room-table th, .room-table td {
    border: 1px solid #ccc;
    padding: 8px;
    text-align: center;
}
.room-table th {
    background-color: #f5a4bf;
    color: white;
}
.room-available {
    background-color: #d4edda;
    color: #155724;
}
.room-full {
    background-color: #f8d7da;
    color: #721c24;
}
.assign-btn {
    padding: 5px 10px;
    cursor: pointer;
    background-color: #4caf50;
    border: none;
    color: white;
    border-radius: 3px;
}
.assign-btn:disabled {
    background-color: #aaa;
    cursor: not-allowed;
}
.inpatients-table {
    margin-bottom: 40px;
    width: 100%;
    border-collapse: collapse;
}
.inpatients-table th, .inpatients-table td {
    border: 1px solid #ccc;
    padding: 6px;
    text-align: center;
}
.inpatients-table th {
    background-color: #eb6d9b;
    color: white;
}
.content {
            margin-left: 220px; /* matches sidebar width */
            padding: 40px; /* padding top accounts for fixed header */
            background-color: #f4f9f9;
            min-height: 90vh;
            margin-top: -30px;
}
</style>
</head>
<body>
<div class="content">
    <!-- Modal for Patient Vitals -->
<div id="vitalsModal" style="display:none; position:fixed; top:50%; left:50%; transform: translate(-50%, -50%);
     background:white; border:1px solid #ccc; padding:20px; box-shadow: 0 0 10px rgba(0,0,0,0.3); z-index: 1000; width: 350px;">
    <h3>Patient Vital Signs</h3>
    <div id="vitalsContent">
        Loading...
    </div>
    <button id="closeVitalsModal" style="margin-top:10px; padding:5px 10px; cursor:pointer;">Close</button>
</div>

<!-- Overlay -->
<div id="modalOverlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; 
     background:rgba(0,0,0,0.5); z-index:999;"></div>

<h2>Current Inpatients</h2>
<table class="inpatients-table">
    <thead>
        <tr>
            <th>InpatientID</th>
            <th>Patient Name</th>
            <th>AdmissionDate</th>
            <th>DischargeDate</th>
            <th>MedicalRecord</th>
            <th>Location</th>
            <th>Assign Room</th>
        </tr>
    </thead>
    <tbody>
    <?php while ($row = $inpatients->fetch_assoc()): ?>
        <tr data-inpatientid="<?= htmlspecialchars($row['InpatientID']) ?>">
            <td><?= htmlspecialchars($row['InpatientID']) ?></td>
            <td><?= htmlspecialchars($row['Name']) ?></td>
            <td><?= htmlspecialchars($row['AdmissionDate']) ?></td>
            <td class="discharge-cell" data-inpatientid="<?= htmlspecialchars($row['InpatientID']) ?>">
                <?php if ($row['DischargeDate']): ?>
                    <?= htmlspecialchars($row['DischargeDate']) ?>
                <?php else: ?>
                    <button class="discharge-btn">Discharge</button>
                <?php endif; ?>
            </td>
            <td>
            <button class="view-record-btn" data-patientid="<?= htmlspecialchars($row['PatientID']) ?>">View Record</button>
            </td>
            <td>
                <?= isset($row['LocationName']) && $row['LocationName'] ? " " . htmlspecialchars($row['LocationName']) : "Not assigned" ?>
            </td>
            <td>
                <button class="assign-room-btn" data-inpatientid="<?= htmlspecialchars($row['InpatientID']) ?>">Assign Room</button>
            </td>
        </tr>
    <?php endwhile; ?>
    </tbody>
</table>

<h2>Assign Rooms</h2>

<div class="tabs" id="buildingTabs">
    <?php
    $buildings = array_keys($locations);
    foreach ($buildings as $index => $building) {
        $activeClass = $index === 0 ? "active" : "";
        echo "<div class='tab $activeClass' data-building='$building'>Building $building</div>";
    }
    ?>
</div>

<div id="floorsContainer">
<?php
foreach ($locations as $building => $floors) {
    $style = ($building === $buildings[0]) ? "" : "style='display:none'";
    echo "<div class='building-floors' data-building='$building' $style>";
    foreach ($floors as $floor => $rooms) {
        echo "<div class='floor-section'>";
        echo "<div class='floor-header'>Floor $floor</div>";
        echo "<table class='room-table'>";
        echo "<thead><tr><th>Room #</th><th>Type</th><th>Capacity</th><th>Occupied</th><th>Status</th><th>Assign</th></tr></thead><tbody>";
        foreach ($rooms as $room) {
            $availableBeds = $room['RoomCapacity'] - $room['OccupiedBeds'];
            $isFull = $availableBeds <= 0;
            $statusClass = $isFull ? 'room-full' : 'room-available';
            echo "<tr>";
            echo "<td>" . htmlspecialchars($room['RoomNumber']) . "</td>";
            echo "<td>" . htmlspecialchars($room['RoomType']) . "</td>";
            echo "<td>" . intval($room['RoomCapacity']) . "</td>";
            echo "<td>" . intval($room['OccupiedBeds']) . "</td>";
            echo "<td class='$statusClass'>" . ($isFull ? "Full" : "Available") . "</td>";
            echo "<td>";
            $btnDisabled = $isFull ? "disabled" : "";
            echo "<button class='assign-room-btn' data-locationid='" . intval($room['LocationID']) . "' $btnDisabled>Assign</button>";
            echo "</td>";
            echo "</tr>";
        }
        echo "</tbody></table>";
        echo "</div>";
    }
    echo "</div>";
}
?>

</div>

<script>

// Modal elements
const vitalsModal = document.getElementById('vitalsModal');
const vitalsContent = document.getElementById('vitalsContent');
const modalOverlay = document.getElementById('modalOverlay');
const closeVitalsModalBtn = document.getElementById('closeVitalsModal');

// Open modal function
// Open modal function
function openVitalsModal(patientId) {
    vitalsContent.textContent = "Loading...";
    vitalsModal.style.display = 'block';
    modalOverlay.style.display = 'block';

    fetch("", {
        method: "POST",
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=get_patient_vitals&PatientID=${encodeURIComponent(patientId)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.error || Object.keys(data).length === 0) {
            vitalsContent.innerHTML = `<p>No record yet.</p>`;
        } else {
            vitalsContent.innerHTML = `
                <p><strong>Temperature:</strong> ${data.Temperature ?? 'N/A'} °C</p>
                <p><strong>Blood Pressure:</strong> ${data.BloodPressure ?? 'N/A'} mmHg</p>
                <p><strong>Pulse:</strong> ${data.Pulse ?? 'N/A'} bpm</p>
                <p><strong>Nurse Notes:</strong> ${data.NurseNotes ?? 'N/A'}</p>
                <p><strong>Recorded At:</strong> ${data.RecordedAt ?? 'N/A'}</p>
            `;
        }
    })
    .catch(error => {
        console.error("No record to display yet", error);
        vitalsContent.innerHTML = `<p style="color:red;">No record to display yet.</p>`;
    });
}






// Close modal
closeVitalsModalBtn.addEventListener('click', () => {
    vitalsModal.style.display = 'none';
    modalOverlay.style.display = 'none';
});

// View patient record button
document.querySelectorAll('.view-record-btn').forEach(button => {
    button.addEventListener('click', () => {
        const patientId = button.getAttribute('data-patientid');
        openVitalsModal(patientId);
    });
});


// Discharge button
// Discharge button
document.querySelectorAll('.discharge-btn').forEach(button => {
    button.addEventListener('click', () => {
        const row = button.closest('tr');
        const inpatientId = row.getAttribute('data-inpatientid');

        if (confirm("Are you sure you want to discharge this patient?")) {
            fetch("", {
                method: "POST",
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=discharge_inpatient&inpatientId=${inpatientId}`
            })
            .then(response => response.text())
            .then(result => {
                if (result.startsWith("success|")) {
                    // Optionally you can parse and show the date here before reload if needed
                    alert("Patient discharged successfully.");
                    // Reload the page to refresh data and UI cleanly
                    window.location.reload();
                } else {
                    alert(result);
                }
            })
            .catch(() => {
                alert("Failed to discharge patient. Please try again.");
            });
        }
    });
});


// Assign room from inpatients table
let selectedInpatientId = null;

document.querySelectorAll('.assign-room-btn[data-inpatientid]').forEach(button => {
    button.addEventListener('click', () => {
        selectedInpatientId = button.getAttribute('data-inpatientid');
        alert("Now scroll down and click on the desired room to assign.");
    });
});

// Assign room from room list
// Assign room from room list
document.querySelectorAll('.assign-room-btn[data-locationid]').forEach(button => {
    button.addEventListener('click', () => {
        const locationId = button.getAttribute('data-locationid');

        if (!selectedInpatientId) {
            alert("Please select an inpatient first by clicking 'Assign Room' next to the patient.");
            return;
        }

        fetch("", {
            method: "POST",
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=assign_location&inpatientId=${selectedInpatientId}&locationId=${locationId}`
        })
        .then(response => response.text())
        .then(result => {
            if (result.trim() === "success") {
                alert("Room assigned successfully!");
                window.location.reload(); // Refresh page to update the UI
            } else {
                alert("Failed to assign room: " + result);
            }
        })
        .catch(error => {
            alert("Error occurred while assigning room: " + error.message);
        });
    });
});


// Building tab switching
document.querySelectorAll(".tab").forEach(tab => {
    tab.addEventListener('click', () => {
        const selectedBuilding = tab.getAttribute('data-building');

        document.querySelectorAll(".tab").forEach(t => t.classList.remove('active'));
        tab.classList.add('active');

        document.querySelectorAll(".building-floors").forEach(floorDiv => {
            floorDiv.style.display = floorDiv.getAttribute('data-building') === selectedBuilding ? 'block' : 'none';
        });
    });
});
</script>
</body>
</html>