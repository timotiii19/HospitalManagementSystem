<?php
session_start();
include('../../config/db.php');
include('../../includes/doctor_header.php');
include('../../includes/doctor_sidebar.php');

$doctorName = '';
$pastSchedules = [];

if (isset($_SESSION['role']) && $_SESSION['role'] === 'Doctor' && isset($_SESSION['role_id'])) {
    $doctorID = $_SESSION['role_id'];

    // Get doctor name
    $stmt = $conn->prepare("SELECT DoctorName FROM doctor WHERE DoctorID = ?");
    $stmt->bind_param("i", $doctorID);
    $stmt->execute();
    $stmt->bind_result($doctorName);
    $stmt->fetch();
    $stmt->close();

    // Fetch past schedules
// Fetch past schedules
    $query = "
        SELECT 
            ds.ScheduleDate, ds.StartTime, ds.EndTime, ds.Status,
            ds.PatientName, ds.PatientAge,
            p.Sex, p.PatientID,
            a.Reason
        FROM doctorschedule ds
        LEFT JOIN patients p 
            ON p.Name = ds.PatientName 
            AND TIMESTAMPDIFF(YEAR, p.DateOfBirth, CURDATE()) = ds.PatientAge
        LEFT JOIN (
            SELECT a1.*
            FROM appointments a1
            INNER JOIN (
                SELECT PatientID, DoctorID, MIN(AppointmentID) AS FirstAppID
                FROM appointments
                GROUP BY PatientID, DoctorID
            ) AS a2 ON a1.AppointmentID = a2.FirstAppID
        ) a ON a.PatientID = p.PatientID AND a.DoctorID = ds.DoctorID
        WHERE ds.DoctorID = ?
        AND ds.ScheduleDate < CURDATE()
        ORDER BY ds.ScheduleDate DESC, ds.StartTime DESC
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $doctorID);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $pastSchedules[] = $row;
    }

    $stmt->close();
}
?>

<div class="content">
    <h1>Past Schedules</h1>

    <?php if (count($pastSchedules) > 0): ?>
        <div class="table-container">
            <table class="schedule-table">
                <thead>
                    <tr>
                        <th>Patient ID</th>
                        <th>Name</th>
                        <th>Age</th>
                        <th>Sex</th>
                        <th>Condition / Reason</th>
                        <th>Schedule Date</th>
                        <th>Start Time</th>
                        <th>End Time</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pastSchedules as $schedule): ?>
                        <tr>
                            <td><?= htmlspecialchars($schedule['PatientID'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($schedule['PatientName']) ?></td>
                            <td><?= htmlspecialchars($schedule['PatientAge']) ?></td>
                            <td><?= htmlspecialchars($schedule['Sex']) ?></td>
                            <td><?= htmlspecialchars($schedule['Reason']) ?></td>
                            <td><?= htmlspecialchars($schedule['ScheduleDate']) ?></td>
                            <td><?= date("g:i A", strtotime($schedule['StartTime'])) ?></td>
                            <td><?= date("g:i A", strtotime($schedule['EndTime'])) ?></td>
                            <td><?= htmlspecialchars($schedule['Status']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p>No past schedules found.</p>
    <?php endif; ?>
</div>

<style>
    .content {
        margin-left: 230px;
        padding: 20px 40px;
        background-color:rgb(255, 255, 255);
        min-height: 100vh;
    }

    h1 {
        margin-bottom: 20px;
        color: #a00037;
    }
    
     h2 {
        color:rgb(255, 255, 255);
    }

    .table-container {
        overflow-x: auto;
    }

    .schedule-table {
        width: 100%;
        border-collapse: collapse;
        background-color: #fff;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
    }

    .schedule-table thead {
        background-color: #9f2f56;
        color: white;
    }

    .schedule-table th,
    .schedule-table td {
        padding: 10px;
        text-align: center;
        border: 1px solid #ddd;
        font-size: 14px;
    }

    .schedule-table tbody tr:nth-child(even) {
        background-color: #f9f9f9;
    }

    .schedule-table tbody tr:hover {
        background-color: #f1f1f1;
    }

    @media (max-width: 768px) {
        .schedule-table th, .schedule-table td {
            font-size: 12px;
            padding: 8px;
        }
    }
</style>