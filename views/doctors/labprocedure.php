<?php
session_start();

if (!isset($_SESSION['username']) || $_SESSION['role'] != 'Doctor') {
    header("Location: ../../auth/doctor_login.php");
    exit();
}

include('../../config/db.php');
date_default_timezone_set('Asia/Manila');

$doctor_name = $_SESSION['full_name'];
$doctorID = $_SESSION['role_id'] ?? null;

// Handle new lab request submission BEFORE any output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_request'])) {
    $patientID = (int)$_POST['PatientID'];
    $doctorID = (int)$_POST['DoctorID'];
    $testDate = $_POST['TestDate'];
    $procedureName = $_POST['ProcedureName'];
    $status = "Request Submitted";

    $stmt = $conn->prepare("INSERT INTO labprocedure (PatientID, DoctorID, TestDate, ProcedureName, Status) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iisss", $patientID, $doctorID, $testDate, $procedureName, $status);
    $stmt->execute();
    $stmt->close();

    // Redirect to avoid resubmission
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Include page header and sidebar AFTER handling POST
include('../../includes/doctor_header.php');
include('../../includes/doctor_sidebar.php');

// Fetch dropdown data
$procedureResult = mysqli_query($conn, "SELECT ProcedureName FROM procedures");
$procedures = $procedureResult ? mysqli_fetch_all($procedureResult, MYSQLI_ASSOC) : [];

$patientsResult = mysqli_query($conn, "SELECT PatientID, Name FROM patients");
$patients = $patientsResult ? mysqli_fetch_all($patientsResult, MYSQLI_ASSOC) : [];

$doctorsResult = mysqli_query($conn, "SELECT DoctorID, DoctorName FROM doctor");
$doctors = $doctorsResult ? mysqli_fetch_all($doctorsResult, MYSQLI_ASSOC) : [];

// Fetch lab requests from labprocedure directly, no grouping needed
$requestsResult = mysqli_query($conn, "
    SELECT lp.LabReqID, lp.PatientID, lp.DoctorID, lp.TestDate, lp.Result, lp.DateReleased, lp.ProcedureName, lp.Status,
           p.Name AS PatientName,
           d.DoctorName
    FROM labprocedure lp
    JOIN patients p ON lp.PatientID = p.PatientID
    LEFT JOIN doctor d ON lp.DoctorID = d.DoctorID
    ORDER BY lp.LabReqID ASC
");
$labRequests = $requestsResult ? mysqli_fetch_all($requestsResult, MYSQLI_ASSOC) : [];

// Auto-update status based on TestDate difference
$now = new DateTime();
foreach ($labRequests as &$req) {
    $labReqID = (int)$req['LabReqID'];
    $testDate = new DateTime($req['TestDate']);
    $diffDays = $testDate->diff($now)->days;
    $status = $req['Status'];

    if ($status == "Request Submitted" && $diffDays >= 1) {
        $status = "In Progress";
        mysqli_query($conn, "UPDATE labprocedure SET Status='In Progress' WHERE LabReqID=$labReqID");
    } elseif ($status == "In Progress" && $diffDays >= 3) {
        $status = "Done";
        mysqli_query($conn, "UPDATE labprocedure SET Status='Done' WHERE LabReqID=$labReqID");
    }
    $req['Status'] = $status;
}

// Refetch lab requests after status update to get fresh data
$requestsResult = mysqli_query($conn, "
    SELECT lp.LabReqID, lp.PatientID, lp.DoctorID, lp.TestDate, lp.Result, lp.DateReleased, lp.ProcedureName, lp.Status,
           p.Name AS PatientName,
           d.DoctorName
    FROM labprocedure lp
    JOIN patients p ON lp.PatientID = p.PatientID
    LEFT JOIN doctor d ON lp.DoctorID = d.DoctorID
    ORDER BY lp.LabReqID ASC
");
$labRequests = $requestsResult ? mysqli_fetch_all($requestsResult, MYSQLI_ASSOC) : [];

$hour = date("H");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Lab Procedures</title>
    <link rel="stylesheet" href="../../css/style.css">
    <style>
        .content { padding: 40px; margin-left: 230px; background: white; margin-top: -30px;}
        .card-box { background: #fff; padding: 30px; border-radius: 12px; border: 4px solid #c34b4b; box-shadow: 6px 6px 0px #e58585; margin-bottom: 40px; }
        .form-label { font-weight: bold; width: 30; }
        .btn-submit { background-color: rgb(221, 106, 106); color: white; border: none; padding: 12px; width: 100%; border-radius: 10px; font-weight: bold; margin-top: 20px; cursor: pointer; }
        table { width: 100%; border-collapse: collapse; margin-top: 30px; }
        table th, table td { border: 1px solid #ddd; padding: 10px; text-align: center; }
        table th { background-color: #f5bebe; color: rgb(248, 64, 64); }
       .form-row {
            display: flex;
            flex-wrap: nowrap;
            gap: 30px;
            justify-content: space-between;
            align-items: flex-start;
        }
        h3 {
            font-size: 28px;
            color:rgb(27, 26, 26);
        }

        /* General form groups */
        .form-group {
            display: flex;
            flex-direction: column;
            flex: 1 1 260px; /* default: grow & shrink, min 220px */
            min-width: 220px;
        }

        /* Make Test Date smaller */
        .form-group.testdate {
            flex: 0 0 260px;  /* fixed width 160px, no grow or shrink */
            min-width: 160px;
}
        select, input[type="datetime-local"] {
            width: 85%; padding: 10px; border-radius: 8px; border: 2px solid #ccc;
        }
    </style>
</head>
<body>
<div class="content">
    <h3>Submit Lab Requests:</h3>

    <div class="card-box">
        <form method="POST">
            <input type="hidden" name="new_request" value="1">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Patient</label>
                    <select name="PatientID" required>
                        <option disabled selected>Select Patient</option>
                        <?php foreach ($patients as $p): ?>
                            <option value="<?= (int)$p['PatientID'] ?>"><?= htmlspecialchars($p['Name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Doctor</label>
                    <!-- Use hidden input for DoctorID and show doctor's name -->
                    <input type="hidden" name="DoctorID" value="<?= (int)$doctorID ?>">
                    <input type="text" value="<?= htmlspecialchars($doctor_name) ?>" readonly style="width: 90%; padding: 10px; border-radius: 8px; border: 2px solid #ccc; background: #eee;">
                </div>
                <div class="form-group">
                    <label class="form-label">Test Date & Time</label>
                    <input type="datetime-local" name="TestDate" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Procedure</label>
                    <select name="ProcedureName" required>
                        <option disabled selected>Select Procedure</option>
                        <?php foreach ($procedures as $proc): ?>
                            <option value="<?= htmlspecialchars($proc['ProcedureName']) ?>"><?= htmlspecialchars($proc['ProcedureName']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <button class="btn-submit" type="submit">Submit Request</button>
        </form>
    </div>

    <div class="card-box">
        <h4>Lab Requests</h4>

        <?php
        // Debug: Check for duplicates in labRequests
        $seenIDs = [];
        $duplicatesFound = false;
        foreach ($labRequests as $req) {
            if (in_array($req['LabReqID'], $seenIDs)) {
                echo "<p style='color:red; font-weight:bold;'>Duplicate LabReqID found in PHP array: " . htmlspecialchars($req['LabReqID']) . "</p>";
                $duplicatesFound = true;
            } else {
                $seenIDs[] = $req['LabReqID'];
            }
        }
        ?>

        <table>
            <thead>
            <tr>
                <th>ID</th>
                <th>Patient</th>
                <th>Doctor</th>
                <th>Test Date</th>
                <th>Procedure</th>
                <th>Status</th>
                <th>PDF</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($labRequests as $req): ?>
                <tr>
                    <td><?= (int)$req['LabReqID'] ?></td>
                    <td><?= htmlspecialchars($req['PatientName']) ?></td>
                    <td><?= !empty($req['DoctorName']) ? htmlspecialchars($req['DoctorName']) : '<em>Unknown Doctor</em>' ?></td>
                    <td><?= htmlspecialchars($req['TestDate']) ?></td>
                    <td><?= htmlspecialchars($req['ProcedureName']) ?></td>
                    <td><?= htmlspecialchars($req['Status']) ?></td>
                    <td><a href="LabRequest_pdf.php?id=<?= (int)$req['LabReqID'] ?>" target="_blank">Download PDF</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
