<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'Nurse') {
    header("Location: ../../auth/nurse_login.php");
    exit();
}
include('../../includes/nurse_header.php');
include('../../includes/nurse_sidebar.php');
include('../../config/db.php');

// Fetch all departments
$dept_result = $conn->query("SELECT * FROM department");
$departments = [];
while ($row = $dept_result->fetch_assoc()) {
    $departments[$row['DepartmentID']] = $row['DepartmentName'];
}

// Update schedule if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_schedule'])) {
    $id = $_POST['DoctorScheduleID'];
    $doctor_id = $_POST['DoctorID'];
    $department_id = $_POST['DepartmentID'];
    $date = $_POST['ScheduleDate'];
    $start = $_POST['StartTime'];
    $end = $_POST['EndTime'];
    $status = $_POST['Status'];

    $stmt = $conn->prepare("UPDATE doctorschedule SET DoctorID=?, DepartmentID=?, ScheduleDate=?, StartTime=?, EndTime=?, Status=? WHERE DoctorScheduleID=?");
    $stmt->bind_param("iissssi", $doctor_id, $department_id, $date, $start, $end, $status, $id);
    $stmt->execute();
    header("Location: doctor_schedule.php");
    exit();
}

// Fetch doctor schedules with department name
$query = "SELECT ds.*, d.DepartmentName, doc.DoctorName 
          FROM doctorschedule ds 
          LEFT JOIN department d ON ds.DepartmentID = d.DepartmentID
          LEFT JOIN doctor doc ON ds.DoctorID = doc.DoctorID";

$result = $conn->query($query);
$schedules = $result->fetch_all(MYSQLI_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Doctor Schedules - Nurse Dashboard</title>
    <link rel="stylesheet" type="text/css" href="../../css/style.css">
    <style>
        body { background-color: #fff; }
        .content { padding: 40px; }
        .btn {
            padding: 8px 15px;
            font-size: 14px;
            border-radius: 5px;
            text-decoration: none;
            cursor: pointer;
        }
        .btn-warning { background-color:rgb(224, 84, 98); color: white; }
        .btn-warning:hover { background-color:rgb(237, 87, 89); }
        .btn-success { background-color:rgb(223, 75, 93); color: white; }
        .btn-success:hover { background-color:rgb(239, 70, 104); }

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

        .modal {
            display: none;
            position: fixed;
            z-index: 50;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.5);
        }
        .modal-dialog {
            background-color: white;
            margin: 10% auto;
            padding: 20px;
            border-radius: 8px;
            width: 500px;
            max-width: 90%;
        }
        .modal-header, .modal-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-body label {
            display: block;
            margin-top: 10px;
        }
        .modal-body input, .modal-body select {
            width: 100%;
            padding: 6px;
            margin-top: 4px;
        }
        .close {
            background: none;
            border: none;
            font-size: 20px;
        }
    </style>
</head>
<body>
    <div class="content">
        <h2>Doctor Schedules</h2>

        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Doctor ID</th>
                    <th>Doctor Name</th>
                    <th>Department</th>
                    <th>Date</th>
                    <th>Start</th>
                    <th>End</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
           <tbody>
                <?php foreach ($schedules as $s): ?>
                    <tr>
                        <td><?= $s['DoctorID'] ?></td>
                        <td><?= htmlspecialchars($s['DoctorName'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($s['DepartmentName'] ?? 'N/A') ?></td>
                        <td><?= $s['ScheduleDate'] ?></td>
                        <td><?= $s['StartTime'] ?></td>
                        <td><?= $s['EndTime'] ?></td>
                        <td><?= $s['Status'] ?></td>
                        <td>
                            <button class="btn btn-warning" onclick='openEditModal(<?= json_encode($s, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>Edit</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>

        </table>
    </div>

    <!-- Modal -->
    <div class="modal" id="editModal">
        <div class="modal-dialog">
            <form method="POST" action="">
                <div class="modal-header">
                    <h5>Edit Doctor Schedule</h5>
                    <button type="button" onclick="closeModal()" class="close">&times;</button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="DoctorScheduleID" id="edit-id">
                    
                    <label>Doctor ID=</label>
                    <input type="number" name="DoctorID" id="edit-doctor-id" required>

                    <label>Department</label>
                    <select name="DepartmentID" id="edit-department-id" required>
                        <?php foreach ($departments as $dept_id => $dept_name): ?>
                            <option value="<?= $dept_id ?>"><?= htmlspecialchars($dept_name) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label>Date</label>
                    <input type="date" name="ScheduleDate" id="edit-date" required>

                    <label>Start Time</label>
                    <input type="time" name="StartTime" id="edit-start" required>

                    <label>End Time</label>
                    <input type="time" name="EndTime" id="edit-end" required>

                    <label>Status</label>
                    <select name="Status" id="edit-status" required>
                        <option value="Regular">Regular</option>
                        <option value="Resident">Resident</option>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="update_schedule" class="btn btn-success">Update</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openEditModal(schedule) {
            document.getElementById('edit-id').value = schedule.DoctorScheduleID || '';
            document.getElementById('edit-doctor-id').value = schedule.DoctorID || '';
            document.getElementById('edit-department-id').value = schedule.DepartmentID || '';
            document.getElementById('edit-date').value = schedule.ScheduleDate || '';
            document.getElementById('edit-start').value = schedule.StartTime || '';
            document.getElementById('edit-end').value = schedule.EndTime || '';
            document.getElementById('edit-status').value = schedule.Status || '';
            document.getElementById('editModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('editModal').style.display = 'none';
        }
    </script>
</body>
</html>
