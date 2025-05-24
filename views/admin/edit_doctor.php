<?php
include('../../includes/admin_header.php');
include('../../includes/admin_sidebar.php');
include('../../config/db.php');

$doctor_id = $_GET['doctor_id'] ?? null;

if (!$doctor_id) {
    header("Location: doctors.php");
    exit();
}

// Fetch doctor details with user's contact number and email
$doctor_stmt = $conn->prepare("
    SELECT d.*, u.email, u.ContactNumber, u.UserID
    FROM doctor d 
    JOIN users u ON d.UserID = u.UserID 
    WHERE d.DoctorID = ?
");
$doctor_stmt->bind_param("i", $doctor_id);
$doctor_stmt->execute();
$doctor_result = $doctor_stmt->get_result();
$doctor = $doctor_result->fetch_assoc();

if (!$doctor) {
    // If no doctor found, redirect
    header("Location: doctors.php");
    exit();
}

// Handle update
if (isset($_POST['update_doctor'])) {
    $availability = $_POST['availability'];
    $contact = $_POST['contact'];            // contact from users table
    $doctor_type = $_POST['doctor_type'];
    $department_id = $_POST['department_id'];
    $doctor_fee = $_POST['doctor_fee'];

    // Update doctor table (except contact)
    $stmt = $conn->prepare("UPDATE doctor SET Availability=?, DoctorType=?, DepartmentID=?, DoctorFee=? WHERE DoctorID=?");
    $stmt->bind_param("sssii", $availability, $doctor_type, $department_id, $doctor_fee, $doctor_id);
    $stmt->execute();

    // Update contact in users table
    $user_id = $doctor['UserID'];
    $user_stmt = $conn->prepare("UPDATE users SET ContactNumber=? WHERE UserID=?");
    $user_stmt->bind_param("si", $contact, $user_id);
    $user_stmt->execute();

    header("Location: doctors.php");
    exit();
}

// Fetch department list
$departments = $conn->query("SELECT DepartmentID, DepartmentName FROM department");

// Fetch assigned inpatients for this doctor
$locations_result = $conn->query("
    SELECT DISTINCT l.LocationID, l.LocationName 
    FROM inpatients i
    JOIN locations l ON i.LocationID = l.LocationID
    WHERE i.DoctorID = $doctor_id
");
?>

<div class="content" style="display: flex; gap: 40px;">
    <!-- Left: Edit Form -->
    <div style="flex: 1;">
        <h2>Edit Doctor Details</h2>
        <form method="post" action="">
            <div class="form-group">
                <label>Availability</label>
                <input type="datetime-local" name="availability" value="<?= date('Y-m-d\TH:i', strtotime($doctor['Availability'])) ?>" required>
            </div>
            <div class="form-group">
                <label>Contact Number</label>
                <input type="text" name="contact" value="<?= htmlspecialchars($doctor['ContactNumber']) ?>" required>
            </div>
            <div class="form-row">
                <div class="form-group" style="flex: 1; margin-right: 10px;">
                    <label>Doctor Type</label>
                    <select name="doctor_type" required>
                        <option value="Regular" <?= $doctor['DoctorType'] === 'Regular' ? 'selected' : '' ?>>Regular</option>
                        <option value="Visiting" <?= $doctor['DoctorType'] === 'Visiting' ? 'selected' : '' ?>>Visiting</option>
                    </select>
                </div>
                <div class="form-group" style="flex: 1;">
                    <label>Department</label>
                    <select name="department_id" required>
                        <?php while ($dept = $departments->fetch_assoc()): ?>
                            <option value="<?= $dept['DepartmentID'] ?>" <?= $doctor['DepartmentID'] == $dept['DepartmentID'] ? 'selected' : '' ?>>
                                <?= $dept['DepartmentName'] ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Doctor Fee</label>
                <input type="number" name="doctor_fee" step="100" value="<?= htmlspecialchars($doctor['DoctorFee']) ?>" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" value="<?= htmlspecialchars($doctor['email']) ?>" disabled>
            </div>
            <button type="submit" name="update_doctor" class="save-btn">Save Changes</button>
        </form>
    </div>

    <!-- Right: Assigned Locations -->
    <div style="flex: 1;">
        <h3>Assigned Locations</h3>
        <?php if ($locations_result->num_rows > 0): ?>
            <table>
                <tr>
                    <th>Location ID</th>
                    <th>Location Name</th>
                </tr>
                <?php while ($row = $locations_result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['LocationID']) ?></td>
                        <td><?= htmlspecialchars($row['LocationName']) ?></td>
                    </tr>
                <?php endwhile; ?>
            </table>
        <?php else: ?>
            <p>No locations assigned to this doctor.</p>
        <?php endif; ?>
    </div>
</div>

<style>
     body {
        font-family: Arial, sans-serif;
        background-color: #ffffff;
    }
    .content {
        padding: 20px;
    }
    h2, h3 {
        margin-bottom: 20px;
    }
    .form-group {
        margin-bottom: 20px;
        max-width: 600px;
    }
    .form-group label {
        font-weight: bold;
        display: block;
        margin-bottom: 6px;
    }
    .form-group input, .form-group select {
        width: 100%;
        padding: 10px;
        font-size: 16px;
    }
    .save-btn {
        padding: 10px 20px;
        background-color: #2196F3;
        color: white;
        border: none;
        font-size: 16px;
        border-radius: 5px;
        cursor: pointer;
    }
    table {
        margin-top: 30px;
        border-collapse: collapse;
        width: 100%;
    }
    th, td {
        padding: 10px;
        border: 1px solid #ccc;
    }
    th {
        background-color: #f2f2f2;
    }
    .form-row {
        display: flex;
        gap: 20px;
        max-width: 600px;
        margin-bottom: 20px;
    }
</style>
