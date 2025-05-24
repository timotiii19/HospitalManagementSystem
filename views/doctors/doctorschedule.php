<?php
session_start();
include('../../config/db.php');
include('../../includes/doctor_header.php');
include('../../includes/doctor_sidebar.php');

$doctorName = '';
$schedules = [];

$searchTerm = $_GET['search'] ?? '';
$currentPage = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 5;

if (isset($_SESSION['role']) && $_SESSION['role'] === 'Doctor' && isset($_SESSION['role_id'])) {
    $doctorID = $_SESSION['role_id'];
    date_default_timezone_set('Asia/Manila'); // Adjust timezone accordingly

    // Get doctor name
    $stmt = $conn->prepare("SELECT DoctorName FROM doctor WHERE DoctorID = ?");
    $stmt->bind_param("i", $doctorID);
    $stmt->execute();
    $stmt->bind_result($doctorName);
    $stmt->fetch();
    $stmt->close();

    // Handle status update via POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['schedule_id'], $_POST['status'])) {
        $scheduleID = intval($_POST['schedule_id']);
        $newStatus = $_POST['status'];
        $allowedStatuses = ['Confirmed', 'Outpatient', 'Inpatient', 'Cancelled', 'DNA', 'Follow-up Scheduled'];

        if (in_array($newStatus, $allowedStatuses)) {
            $followUpDate = null;
            if ($newStatus === 'Follow-up Scheduled' && !empty($_POST['follow_up_date'])) {
                $followUpDate = $_POST['follow_up_date'];
            }

            if ($followUpDate) {
                $updateStmt = $conn->prepare("UPDATE doctorschedule SET Status = ?, FollowUpDate = ? WHERE DoctorScheduleID = ?");
                $updateStmt->bind_param("ssi", $newStatus, $followUpDate, $scheduleID);
            } else {
                $updateStmt = $conn->prepare("UPDATE doctorschedule SET Status = ?, FollowUpDate = NULL WHERE DoctorScheduleID = ?");
                $updateStmt->bind_param("si", $newStatus, $scheduleID);
            }

            if ($updateStmt->execute()) {
                $statusMessage = "Status updated successfully!";
            } else {
                $statusMessage = "Failed to update the status.";
            }
            $updateStmt->close();

            // Additional processing for specific statuses
            if (in_array($newStatus, ['Inpatient', 'Outpatient'])) {
                // Fetch related schedule details for further processing
                $selectSQL = "SELECT PatientID, DoctorID, DepartmentID, ScheduleDate, EndTime FROM doctorschedule WHERE DoctorScheduleID = ?";
                $stmt = $conn->prepare($selectSQL);
                $stmt->bind_param("i", $scheduleID);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($row = $result->fetch_assoc()) {
                    $patientID = $row['PatientID'];
                    $doctorID = $row['DoctorID'];
                    $departmentID = $row['DepartmentID'];
                    $scheduleDate = $row['ScheduleDate'];
                    $endTime = $row['EndTime'];

                    if ($newStatus === 'Inpatient') {
                        $admissionDate = $endTime;
                        $assignedLocationID = null; // Assign as needed or get from somewhere

                        // Update patientType in patients table
                        $updatePatientType = $conn->prepare("UPDATE patients SET patientType = 'Inpatient' WHERE PatientID = ?");
                        $updatePatientType->bind_param("i", $patientID);
                        $updatePatientType->execute();
                        $updatePatientType->close();

                        // Insert into inpatients table
                        $insertSQL = "INSERT INTO inpatients (PatientID, DoctorID, DepartmentID, AdmissionDate, AssignedLocationID) VALUES (?, ?, ?, ?, ?)";
                        $stmtInsert = $conn->prepare($insertSQL);
                        $stmtInsert->bind_param("iiisi", $patientID, $doctorID, $departmentID, $admissionDate, $assignedLocationID);
                        $stmtInsert->execute();
                        $stmtInsert->close();
                    }

                    if ($newStatus === 'Outpatient') {
                        $visitDate = $scheduleDate;
                        $reason = "General Checkup";

                        // Update patientType in patients table
                        $updatePatientType = $conn->prepare("UPDATE patients SET patientType = 'Outpatient' WHERE PatientID = ?");
                        $updatePatientType->bind_param("i", $patientID);
                        $updatePatientType->execute();
                        $updatePatientType->close();

                        // Check if outpatient record exists
                        $checkSQL = "SELECT 1 FROM outpatients WHERE PatientID = ? AND DoctorID = ? AND VisitDate = ?";
                        $checkStmt = $conn->prepare($checkSQL);
                        $checkStmt->bind_param("iis", $patientID, $doctorID, $visitDate);
                        $checkStmt->execute();
                        $checkStmt->store_result();

                        if ($checkStmt->num_rows === 0) {
                            $insertSQL = "INSERT INTO outpatients (PatientID, DoctorID, VisitDate, Reason) VALUES (?, ?, ?, ?)";
                            $stmtInsert = $conn->prepare($insertSQL);
                            $stmtInsert->bind_param("iiss", $patientID, $doctorID, $visitDate, $reason);
                            $stmtInsert->execute();
                            $stmtInsert->close();
                        }
                        $checkStmt->close();
                    }
                }
                $stmt->close();
            }
        }
    }

    // Build dynamic WHERE clause for schedule fetching
    $filterStatus = $_GET['filter'] ?? '';

    $whereConditions = "ds.DoctorID = ? AND ds.ScheduleDate >= CURDATE() AND ds.Status IN ('Confirmed', 'Follow-up Scheduled')";
    $params = [$doctorID];
    $types = "i";

    if (!empty($searchTerm)) {
        $whereConditions .= " AND ds.PatientName LIKE ?";
        $params[] = '%' . $searchTerm . '%';
        $types .= "s";
    }

    if (!empty($filterStatus)) {
        $whereConditions .= " AND ds.Status = ?";
        $params[] = $filterStatus;
        $types .= "s";
    }

    $query = "
        SELECT 
            ds.DoctorScheduleID, ds.ScheduleDate, ds.StartTime, ds.EndTime, ds.Status,
            ds.PatientName, ds.PatientAge, ds.FollowUpDate,
            p.Sex, p.PatientID,
            a.Reason
        FROM doctorschedule ds
        LEFT JOIN patients p 
            ON p.Name = ds.PatientName 
            AND TIMESTAMPDIFF(YEAR, p.DateOfBirth, CURDATE()) = ds.PatientAge
        LEFT JOIN appointments a 
            ON a.PatientID = p.PatientID 
            AND a.DoctorID = ds.DoctorID
        WHERE $whereConditions
        GROUP BY ds.DoctorScheduleID
        ORDER BY ds.ScheduleDate ASC, ds.StartTime ASC
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $schedules = [];
    while ($row = $result->fetch_assoc()) {
        $schedules[] = $row;
    }
    $stmt->close();

    // Group schedules by date categories
    $groupedSchedules = ['Today' => [], 'Tomorrow' => [], 'Next Week' => [], 'Next Month' => [], 'Upcoming' => []];

    $today = new DateTime();
    $tomorrow = (clone $today)->modify('+1 day');
    $nextWeek = (clone $today)->modify('+6 days');
    $nextMonth = (clone $today)->modify('+23 days');

    foreach ($schedules as $schedule) {
        $scheduleDate = !empty($schedule['FollowUpDate']) ? new DateTime($schedule['FollowUpDate']) : new DateTime($schedule['ScheduleDate']);

        if ($scheduleDate->format('Y-m-d') === $today->format('Y-m-d')) {
            $groupedSchedules['Today'][] = $schedule;
        } elseif ($scheduleDate->format('Y-m-d') === $tomorrow->format('Y-m-d')) {
            $groupedSchedules['Tomorrow'][] = $schedule;
        } elseif ($scheduleDate <= $nextWeek) {
            $groupedSchedules['Next Week'][] = $schedule;
        } elseif ($scheduleDate <= $nextMonth) {
            $groupedSchedules['Next Month'][] = $schedule;
        } else {
            $groupedSchedules['Upcoming'][] = $schedule;
        }
    }
}
?>

<div class="content">
    <div class="header-bar">
        <h2>My Schedule</h2>
        <form method="GET" class="search-form">
            <input type="text" name="search" placeholder="Search by patient name" value="<?= htmlspecialchars($searchTerm) ?>" />
            <select name="filter" class="filter-dropdown">
                <option value="">All</option>
                <?php
                $statusOptions = ['Confirmed', 'Outpatient', 'Inpatient', 'Cancelled', 'DNA', 'Follow-up Scheduled'];
                foreach ($statusOptions as $option) {
                    $selected = ($filterStatus === $option) ? 'selected' : '';
                    echo "<option value=\"$option\" $selected>$option</option>";
                }
                ?>
            </select>
            <button type="submit">Search</button>
        </form>
    </div>

    <?php if (isset($statusMessage)): ?>
        <div class="status-message float-message"><?= htmlspecialchars($statusMessage) ?></div>
        <script>
            setTimeout(() => {
                const msg = document.querySelector('.float-message');
                if (msg) msg.classList.add('fade-out');
            }, 2000);
        </script>
    <?php endif; ?>

    <?php foreach ($groupedSchedules as $label => $group): ?>
        <details class="group-section" <?= count($group) > 0 ? 'open' : '' ?>>
            <summary><strong><?= $label ?></strong> (<?= count($group) ?>)</summary>
            <div class="card-container">
                <?php
                $total = count($group);
                $start = ($currentPage - 1) * $perPage;
                $pagedGroup = array_slice($group, $start, $perPage);
                ?>
                <?php if (count($pagedGroup) > 0): ?>
                    <?php foreach ($pagedGroup as $schedule): ?>
                        <form method="POST">
                            <div class="card">
                                <input type="hidden" name="schedule_id" value="<?= htmlspecialchars($schedule['DoctorScheduleID']) ?>">
                                <p class="patient-id">Patient ID: <?= htmlspecialchars($schedule['PatientID'] ?? 'N/A') ?></p>
                                <label class="field-label">Name</label>
                                <div class="field-value"><?= htmlspecialchars($schedule['PatientName']) ?></div>

                                <div class="field-row">
                                    <div class="field-group">
                                        <label class="field-label">Age</label>
                                        <div class="field-value"><?= htmlspecialchars($schedule['PatientAge']) ?></div>
                                    </div>
                                    <div class="field-group">
                                        <label class="field-label">Sex</label>
                                        <div class="field-value"><?= htmlspecialchars($schedule['Sex']) ?></div>
                                    </div>
                                </div>

                                <label class="field-label">Condition or Reason</label>
                                <div class="field-value scrollable-text"><?= htmlspecialchars($schedule['Reason']) ?></div>

                                <label class="field-label">Schedule Date</label>
                                <div class="field-value"><?= htmlspecialchars($schedule['ScheduleDate']) ?></div>

                                <div class="field-row">
                                    <div class="field-group">
                                        <label class="field-label">Start Time</label>
                                        <div class="field-value"><?= date("g:i A", strtotime($schedule['StartTime'])) ?></div>
                                    </div>
                                    <div class="field-group">
                                        <label class="field-label">End Time (Estimated)</label>
                                        <div class="field-value"><?= date("g:i A", strtotime($schedule['EndTime'])) ?></div>
                                    </div>
                                </div>

                                <div class="status-container">
                                    <label class="field-label center-label">Status</label>
                                    <div class="status-update-container">
                                        <?php if ($label === 'Today'): ?>
                                            <select class="status-select" name="status" required>
                                                <?php
                                                foreach ($statusOptions as $status) {
                                                    $selected = ($status === $schedule['Status']) ? 'selected' : '';
                                                    echo "<option value=\"$status\" $selected>$status</option>";
                                                }
                                                ?>
                                            </select>
                                            <button type="submit" class="update-btn">Update</button>
                                        <?php else: ?>
                                            <select class="status-select" name="status" disabled>
                                                <option selected><?= htmlspecialchars($schedule['Status']) ?></option>
                                            </select>
                                            <button type="button" class="update-btn" disabled>Update</button>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($schedule['Status'] === 'Follow-up Scheduled'): ?>
                                        <div class="followup-tag">Follow-Up Check Up</div>
                                    <?php endif; ?>
                                </div>

                                <?php if (!empty($schedule['FollowUpDate'])): ?>
                                    <label class="field-label">Follow-Up Date</label>
                                    <div class="field-value"><?= htmlspecialchars($schedule['FollowUpDate']) ?></div>
                                <?php endif; ?>

                                <input type="hidden" name="follow_up_date" class="follow-up-hidden" />
                            </div>
                        </form>
                    <?php endforeach; ?>

                    <!-- Pagination -->
                    <?php if ($total > $perPage): ?>
                        <div class="pagination">
                            <?php for ($i = 1; $i <= ceil($total / $perPage); $i++): ?>
                                <a href="?page=<?= $i ?>&search=<?= urlencode($searchTerm) ?>&filter=<?= urlencode($filterStatus) ?>" class="<?= $i === $currentPage ? 'active' : '' ?>"><?= $i ?></a>
                            <?php endfor; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>

<p>No schedules found for this section.</p> <?php endif; ?> </div> </details> <?php endforeach; ?> </div>


<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('followUpModal');
    const modalDatetime = document.getElementById('modalDatetime');
    let currentForm = null;
    let currentHiddenInput = null;
    let currentCard = null;

    document.querySelectorAll('.card').forEach(card => {
        const form = card.closest('form');
        const select = form.querySelector('.status-select');
        const hiddenInput = form.querySelector('.follow-up-hidden');
        const updateBtn = form.querySelector('.update-btn');

        updateBtn.addEventListener('click', function (e) {
            e.preventDefault(); // Prevent default form submission

            const selectedStatus = select.value;
            const needsConfirmation = ['Inpatient', 'Outpatient', 'DNA', 'Cancelled'];
            currentForm = form;
            currentHiddenInput = hiddenInput;
            currentCard = card;

            if (selectedStatus === 'Follow-up Scheduled') {
                modal.style.display = 'flex';
                modalDatetime.value = '';
                modalDatetime.focus();
                return;
            }

            if (needsConfirmation.includes(selectedStatus)) {
                const confirmChange = confirm(`Are you sure you want to set the status to "${selectedStatus}"?`);
                if (!confirmChange) return;
            }

            sendStatusUpdate(form, card);
        });
    });

    document.getElementById('confirmFollowUp').addEventListener('click', function () {
        const selectedDate = modalDatetime.value;
        if (!selectedDate) {
            alert("Please select a follow-up date and time.");
            return;
        }
        currentHiddenInput.value = selectedDate;
        modal.style.display = 'none';

        // Submit after setting the date
        sendStatusUpdate(currentForm, currentCard);
    });

    document.getElementById('cancelFollowUp').addEventListener('click', function () {
        modal.style.display = 'none';
    });

    window.addEventListener('click', function (e) {
        if (e.target === modal) {
            modal.style.display = 'none';
        }
    });

    function sendStatusUpdate(form, card) {
        const formData = new FormData(form);
        fetch("", {
            method: "POST",
            body: formData,
        })
        .then(res => res.ok ? res.text() : Promise.reject("Failed to update status"))
        .then(() => {
            // Check if the status is one of the ones that should be removed
            const selectedStatus = form.querySelector('.status-select').value;
            const removeStatuses = ['Cancelled', 'Inpatient', 'Outpatient', 'DNA'];
            
            if (removeStatuses.includes(selectedStatus)) {
                // Fade out and remove the card
                card.style.transition = 'opacity 1s ease';
                card.style.opacity = '0';
                setTimeout(() => card.remove(), 1000); // Remove the card after fading out
            }
        })
        .catch(err => {
            alert("An error occurred while updating the status.");
            console.error(err);
        });
    }

});
</script>





<style>
    .content {
        margin-left: 220px; 
        padding: 40px; 
        background-color: #e0f7fa;
        min-height: 100vh;
        box-sizing: border-box;
        margin-top: -30px;
    }

    .header-bar {
        position: sticky;
        top: 80px; 
        z-index: 9; 
        background-color: #e0f7fa;
        padding: 10px 20px;
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid #ccc;
        width: 100%;
    }

    .search-form {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .search-form input,
    .search-form select {
        padding: 8px;
        border-radius: 6px;
        border: 1px solid #ccc;
        font-size: 14px;
    }

    .search-form button {
        padding: 8px 12px;
        border: none;
        border-radius: 6px;
        background: #007b8f;
        color: white;
        cursor: pointer;
    }

    .filter-dropdown {
        background-color: white;
        color: #333;
    }

    .card-container {
        display: flex;
        flex-wrap: wrap;
        gap: 30px;
    }

    .card {
        background-color: #9f2f56;
        border-radius: 20px;
        padding: 20px;
        width: 250px;
        color: white;
        font-family: Arial, sans-serif;
        margin-top:10px;
    }
    .card:hover {
    transform: translateY(-5px);
    }
    .field-group {
        flex: 1; /* Make both take equal space */
    }

    .field-label {
        font-size: 12px;
        color: #e0e0e0;
        margin-bottom: 3px;
    }

    .field-value {
        background-color: #ffffff33;
        padding: 6px 10px;
        border-radius: 8px;
        text-align: center;
        margin-bottom: 8px;
    }

    .scrollable-text {
        display: block;
        max-height: 3.6em;            /* 3 lines at ~1.2em line height */
        overflow-y: auto;             /* Enable vertical scroll if content exceeds */
        overflow-x: hidden;           /* Prevent horizontal scroll */
        white-space: normal;          /* Allow wrapping */
        word-wrap: break-word;        /* Break long words */
        line-height: 1.2em;           /* Line spacing */
        padding-right: 4px;           /* Space for scrollbar */
    }

    .field-row {
        display: flex;
        justify-content: space-between;
        gap: 10px;
    }

    .status-select {
        width: 70%;
        border-radius: 6px;
        padding: 5px;
        background: #c46a8a;
        color: white;
        border: none;
    }

    .update-btn {
        background-color: rgba(255, 0, 0, 0.8);
        padding: 6px 10px;
        color: white;
        border: none;
        border-radius: 6px;
        cursor: pointer;
    }

    .group-section summary {
        font-size: 18px;
        cursor: pointer;
        background: rgba(171, 208, 227, 0.8);
        padding: 10px;
        margin: 10px 0;
        border-radius: 5px;
    }

    .pagination {
        margin-top: 15px;
    }

    .pagination a {
        margin: 0 5px;
        padding: 6px 10px;
        background: #00796b;
        color: white;
        text-decoration: none;
        border-radius: 4px;
    }

    .pagination .active {
        background: #004d40;
    }

    .status-message {
        text-align: center;
        color: green;
        font-size: 16px;
        margin: 10px 0;
    }
    .patient-id {
    text-align: center;
    font-weight: bold;
    margin-bottom: 10px;
    }
    .scrollable-text::-webkit-scrollbar {
        width: 6px;
    }
    .scrollable-text::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.3);
        border-radius: 5px;
    }
    .float-message {
    position: fixed;
    top: 14.5%;
    left: 60%;
    transform: translate(-50%, -50%);
    background: #d0f0c0;
    color: #004d00;
    border: 2px solid #66bb6a;
    padding: 15px 30px;
    font-size: 18px;
    border-radius: 10px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    z-index: 9999;
    opacity: 1;
    transition: opacity 1s ease-out;
    }

    .float-message.fade-out {
        opacity: 0;
        pointer-events: none;
    }
    .followup-tag {
        background-color: #fff176;
        color: #333;
        padding: 4px 8px;
        border-radius: 8px;
        font-size: 12px;
        text-align: center;
        margin-top: 10px;
        font-weight: bold;
    }
    .followup-modal {
        display: none; /* Start hidden */
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5); /* Overlay */
        justify-content: center; /* Center the modal */
        align-items: center;
        z-index: 999; /* Ensure it's above other content */
    }

    .modal-content {
        background: white;
        padding: 20px;
        border-radius: 12px;
        width: 300px;
        text-align: center;
    }

    .modal-content h3 {
        margin-top: 0;
        margin-bottom: 15px;
        color: #333;
    }

    .modal-datetime {
        width: 100%;
        padding: 10px;
        font-size: 14px;
        margin-bottom: 20px;
    }

    .modal-buttons {
        display: flex;
        justify-content: space-between;
    }

    .confirm-btn, .cancel-btn {
        padding: 8px 14px;
        font-size: 14px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
    }

    .confirm-btn {
        background-color: #4caf50;
        color: white;
    }

    .cancel-btn {
        background-color: #f44336;
        color: white;
    }
    .card {
    transition: opacity 1s ease; /* Already in script, but good to include here too */
    }

    
</style>