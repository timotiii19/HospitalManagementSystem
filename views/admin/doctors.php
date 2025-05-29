<?php
ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if not admin logged in
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../../auth/admin_login.php");
    exit();
}

include('../../includes/admin_header.php');
include('../../includes/admin_sidebar.php');
require('../../config/db.php'); // DB connection

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle update form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $doctor_id = (int)$_POST['DoctorID'];
    $department_id = $_POST['DepartmentID'];
    $doctor_type = $_POST['DoctorType'];
    $doctor_fee = floatval($_POST['DoctorFee']);

    $stmt = $conn->prepare("UPDATE doctor SET DepartmentID=?, DoctorType=?, DoctorFee=? WHERE DoctorID=?");
    $stmt->bind_param("ssdi", $department_id, $doctor_type, $doctor_fee, $doctor_id);
    $stmt->execute();
    $stmt->close();

    // Update availability
    $conn->query("DELETE FROM doctoravailability WHERE DoctorID = $doctor_id");
    if (isset($_POST['availability']) && is_array($_POST['availability'])) {
        $availability = $_POST['availability'];
        $stmt = $conn->prepare("INSERT INTO doctoravailability (DoctorID, DayOfWeek, StartTime, EndTime) VALUES (?, ?, ?, ?)");
        foreach ($availability as $day => $times) {
            if (!empty($times['start']) && !empty($times['end'])) {
                $stmt->bind_param("isss", $doctor_id, $day, $times['start'], $times['end']);
                $stmt->execute();
            }
        }
        $stmt->close();
    }

    header("Location: doctors.php");
    exit();
}

// Fetch doctors with joined user, department, availability info
$result = $conn->query("
    SELECT d.DoctorID, u.username AS DoctorName, u.email AS Email,
       GROUP_CONCAT(
           CONCAT(a.DayOfWeek, ' - ', TIME_FORMAT(a.StartTime, '%H:%i'), ' - ', TIME_FORMAT(a.EndTime, '%H:%i'))
           SEPARATOR '<br>'
       ) AS Availability,
       u.ContactNumber, d.DoctorType, dep.DepartmentID, dep.DepartmentName, d.DoctorFee, d.UserID
    FROM doctor d
    JOIN users u ON d.UserID = u.UserID
    LEFT JOIN department dep ON d.DepartmentID = dep.DepartmentID
    LEFT JOIN doctoravailability a ON d.DoctorID = a.DoctorID
    GROUP BY d.DoctorID
");

// Fetch all departments for dropdown
$departments_result = $conn->query("SELECT DepartmentID, DepartmentName FROM department");
$departments = [];
while ($row = $departments_result->fetch_assoc()) {
    $departments[] = $row;
}

// Handle doctor delete
if (isset($_GET['delete'])) {
    $doctor_id = (int)$_GET['delete'];
    $result_del = $conn->query("SELECT UserID FROM doctor WHERE DoctorID = $doctor_id");
    if ($row_del = $result_del->fetch_assoc()) {
        $user_id = $row_del['UserID'];
        $conn->query("DELETE FROM doctoravailability WHERE DoctorID = $doctor_id");
        $conn->query("DELETE FROM doctor WHERE DoctorID = $doctor_id");
        $conn->query("DELETE FROM users WHERE UserID = $user_id");
    }
    header("Location: doctors.php");
    exit();
}

ob_end_flush();
?>

<div class="content">
    <h2>Doctor Management</h2>
    <table>
        <thead>
            <tr>
                <th>DoctorID</th>
                <th>DoctorName</th>
                <th>Email</th>
                <th>Availability</th>
                <th>ContactNumber</th>
                <th>DoctorType</th>
                <th>Department</th>
                <th>DoctorFee</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['DoctorID'] ?></td>
                    <td><?= htmlspecialchars($row['DoctorName']) ?></td>
                    <td><?= htmlspecialchars($row['Email']) ?></td>
                    <td class="availability"><?= $row['Availability'] ?? '' ?></td>
                    <td><?= htmlspecialchars($row['ContactNumber'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['DoctorType']) ?></td>
                    <td><?= htmlspecialchars($row['DepartmentName'] ?? '') ?></td>
                    <td><?= htmlspecialchars(number_format($row['DoctorFee'], 2)) ?></td>
                    <td>
                        <a href="#" class="edit-btn" 
                            data-id="<?= $row['DoctorID'] ?>" 
                            data-dept="<?= $row['DepartmentID'] ?>" 
                            data-type="<?= htmlspecialchars($row['DoctorType']) ?>" 
                            data-fee="<?= htmlspecialchars($row['DoctorFee']) ?>" 
                            data-availability="<?= htmlspecialchars($row['Availability'] ?? '') ?>">
                            Edit
                        </a> |
                        <a href="doctors.php?delete=<?= $row['DoctorID'] ?>" 
                            onclick="return confirm('Are you sure you want to delete this doctor?');" 
                            class="delete-link">Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="9">No doctor records found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Edit Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close" title="Close">&times;</span>
        <h3>Edit Doctor Details</h3>
        <form method="POST" class="edit-form" novalidate>
            <input type="hidden" name="DoctorID" id="editDoctorID">

            <div class="form-columns">
                <div class="left-column">
                    <label for="editDepartmentID">Department</label>
                    <select name="DepartmentID" id="editDepartmentID" required>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?= $dept['DepartmentID'] ?>"><?= htmlspecialchars($dept['DepartmentName']) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label for="editDoctorType">Doctor Type</label>
                    <input type="text" name="DoctorType" id="editDoctorType" required placeholder="e.g. General Practitioner">

                    <label for="editDoctorFee">Doctor Fee (₱)</label>
                    <input type="number" name="DoctorFee" id="editDoctorFee" required min="0" step="0.01" placeholder="Enter fee in pesos">
                </div>

                <div class="right-column">
                    <label>Availability</label>
                    <fieldset class="availability-fieldset">
                        <legend>Set working hours per day</legend>
                        <?php
                        $days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
                        foreach ($days as $day):
                        ?>
                        <div class="availability-row">
                            <label class="day-label"><?= $day ?></label>
                            <div class="time-inputs">
                                <label>
                                    From:
                                    <input type="time" name="availability[<?= $day ?>][start]" class="time-start" >
                                </label>
                                <label>
                                    To:
                                    <input type="time" name="availability[<?= $day ?>][end]" class="time-end" >
                                </label>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </fieldset>
                </div>
            </div>

            <button type="submit" name="update">Update</button>
        </form>
    </div>
</div>

<style>
/* General container and layout */
.content {
    padding: 40px;
    font-family: Arial, sans-serif;
    background-color: #ffffff;
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

td.availability {
    white-space: nowrap;
}

.edit-btn {
    color: #6f42c1;
    cursor: pointer;
    text-decoration: underline;
}

.edit-btn:hover {
    color: #512da8;
}

.delete-link {
    color: red;
    text-decoration: underline;
}

.delete-link:hover {
    color: darkred;
}

/* Modal styles */
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
    padding: 20px 30px; /* reduced from 40px */
    background-color: #fff;
    width: 90%;
    max-width: 850px; /* slightly narrower */
    max-height: 80vh; /* limit max height */
    overflow-y: auto; /* scroll if content too tall */
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

/* Form columns */
.form-columns {
    display: flex;
    gap: 15px;
    margin-bottom: 15px;
    flex-wrap: wrap;
}

.left-column, .right-column {
    flex: 1 1 300px;
    min-width: 250px;
}

.left-column label, .right-column label {
    font-weight: 600;
    display: block;
    margin-top: 10px;
    margin-right: 10px;
}

.left-column select,
.left-column input[type="text"],
.left-column input[type="number"] {
    width: 100%;
    padding: 7px 10px;
    margin-top: 5px;
    box-sizing: border-box;
    border: 1px solid #ccc;
    border-radius: 6px;
    font-size: 1em;
}

/* Availability styling */
.availability-fieldset {
    border: 1px solid #ccc;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    margin-right: 20px;
}

.availability-fieldset legend {
    font-weight: bold;
    padding: 0 10px;
    margin-bottom: 15px;
}

.availability-row {
    display: flex;
    align-items: center;
    margin-bottom: 12px;
    gap: 10px;
}

.day-label {
    width: 90px;
    font-weight: 600;
    text-align: right;
}

.time-inputs {
    display: flex;
    gap: 12px;
}

.time-inputs label {
    font-weight: normal;
    display: flex;
    flex-direction: column;
    font-size: 0.85em;
}

.time-inputs input[type="time"] {
    padding: 5px 8px;
    border-radius: 6px;
    border: 1px solid #ccc;
}

/* Button */
button[type="submit"] {
    background-color: #e73a57;
    color: white;
    border: none;
    border-radius: 10px;
    padding: 12px 35px;
    font-size: 1.1em;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.3s ease;
    margin-top: 15px;
    margin-bottom: 8px; /* reduce vertical spacing */
    gap: 8px;
}

button[type="submit"]:hover {
    background-color:rgb(241, 122, 141);
}
</style>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const modal = document.getElementById("editModal");
    const closeBtn = modal.querySelector(".close");
    const editButtons = document.querySelectorAll(".edit-btn");

    // Show modal on edit button click and populate fields
    editButtons.forEach(button => {
        button.addEventListener("click", e => {
            e.preventDefault();

            const doctorId = button.getAttribute("data-id");
            const departmentId = button.getAttribute("data-dept");
            const doctorType = button.getAttribute("data-type");
            const doctorFee = button.getAttribute("data-fee");

            // Set hidden DoctorID
            document.getElementById("editDoctorID").value = doctorId;

            // Set department select
            document.getElementById("editDepartmentID").value = departmentId;

            // Set doctor type and fee
            document.getElementById("editDoctorType").value = doctorType;
            document.getElementById("editDoctorFee").value = doctorFee;

            // Reset all availability time inputs to blank
            document.querySelectorAll(".availability-fieldset input[type='time']").forEach(input => {
                input.value = "";
            });

            // Populate availability times
            const availabilityRaw = button.getAttribute("data-availability");
            if (availabilityRaw) {
                // availabilityRaw example: Monday - 08:00 - 12:00<br>Tuesday - 09:00 - 15:00
                const availLines = availabilityRaw.split('<br>');
                availLines.forEach(line => {
                    const parts = line.split(' - ');
                    if (parts.length === 3) {
                        const day = parts[0].trim();
                        const start = parts[1].trim();
                        const end = parts[2].trim();

                        // Find inputs for this day
                        const startInput = document.querySelector(`input[name='availability[${day}][start]']`);
                        const endInput = document.querySelector(`input[name='availability[${day}][end]']`);

                        if (startInput && endInput) {
                            startInput.value = start;
                            endInput.value = end;
                        }
                    }
                });
            }

            modal.style.display = "flex";
        });
    });

    // Close modal on click X
    closeBtn.addEventListener("click", () => {
        modal.style.display = "none";
    });

    // Close modal on outside click
    window.addEventListener("click", (event) => {
        if (event.target === modal) {
            modal.style.display = "none";
        }
    });
});
</script>
