<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['username']) || $_SESSION['role'] != 'Admin') {
    header("Location: ../../auth/admin_login.php");
    exit();
}

include('../../includes/admin_header.php');
include('../../includes/admin_sidebar.php');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle update submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $doctor_id = (int)$_POST['DoctorID'];
    $department_id = $_POST['DepartmentID'];
    $doctor_type = $_POST['DoctorType'];
    $doctor_fee = $_POST['DoctorFee'];
    $availability = $_POST['Availability'];

    $stmt = $conn->prepare("UPDATE doctor SET DepartmentID=?, DoctorType=?, DoctorFee=?, Availability=? WHERE DoctorID=?");
    $stmt->bind_param("ssdsi", $department_id, $doctor_type, $doctor_fee, $availability, $doctor_id);
    $stmt->execute();
    $stmt->close();

    header("Location: doctors.php");
    exit();
}

// Fetch doctors with user info
$result = $conn->query("SELECT d.DoctorID, u.username AS DoctorName, u.email AS Email, d.Availability, u.ContactNumber, d.DoctorType, dep.DepartmentID, dep.DepartmentName, d.DoctorFee
                        FROM doctor d 
                        JOIN users u ON d.UserID = u.UserID
                        LEFT JOIN department dep ON d.DepartmentID = dep.DepartmentID");

// Fetch all departments for the dropdown
$departments_result = $conn->query("SELECT DepartmentID, DepartmentName FROM department");
$departments = [];
while ($row = $departments_result->fetch_assoc()) {
    $departments[] = $row;
}

// Handle delete doctor and user
if (isset($_GET['delete'])) {
    $doctor_id = (int)$_GET['delete'];
    $result = $conn->query("SELECT UserID FROM doctor WHERE DoctorID = $doctor_id");
    if ($row = $result->fetch_assoc()) {
        $user_id = $row['UserID'];
        $conn->query("DELETE FROM doctor WHERE DoctorID = $doctor_id");
        $conn->query("DELETE FROM users WHERE id = $user_id");
    }
    header("Location: doctors.php");
    exit();
}


?>

<div class="content">
    <h2>Doctor Management</h2>
    <table>
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
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['DoctorID'] ?></td>
                    <td><?= htmlspecialchars($row['DoctorName']) ?></td>
                    <td><?= htmlspecialchars($row['Email']) ?></td>
                    <td><?= htmlspecialchars($row['Availability'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['ContactNumber'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['DoctorType']) ?></td>
                    <td><?= htmlspecialchars($row['DepartmentName'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['DoctorFee']) ?></td>
                    <td>
                        <a href="#" class="edit-btn" 
                        data-id="<?= $row['DoctorID'] ?>" 
                        data-dept="<?= $row['DepartmentID'] ?>" 
                        data-type="<?= htmlspecialchars($row['DoctorType']) ?>" 
                        data-fee="<?= htmlspecialchars($row['DoctorFee']) ?>" 
                        data-availability="<?= htmlspecialchars($row['Availability'] ?? '') ?>">
                        Edit
                        </a>        
 |
                        <a href="doctors.php?delete=<?= $row['DoctorID'] ?>" 
                           onclick="return confirm('Are you sure?');" 
                           class="delete-link">Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="9">No doctor records found.</td></tr>
        <?php endif; ?>
    </table>
</div>

<!-- Edit Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h3>Edit Doctor Details</h3>
        <form method="POST">
            <input type="hidden" name="DoctorID" id="editDoctorID">

            <label for="DepartmentID">Department</label>
            <select name="DepartmentID" id="editDepartmentID" required>
                <?php foreach ($departments as $dept): ?>
                    <option value="<?= $dept['DepartmentID'] ?>"><?= $dept['DepartmentName'] ?></option>
                <?php endforeach; ?>
            </select><br><br>

            <label for="DoctorType">Doctor Type</label>
            <input type="text" name="DoctorType" id="editDoctorType" required><br><br>

            <label for="DoctorFee">Doctor Fee</label>
            <input type="number" name="DoctorFee" id="editDoctorFee" required><br><br>

            <label for="Availability">Availability</label>
            <input type="text" name="Availability" id="editAvailability" required><br><br>


            <button type="submit" name="update">Update</button>
        </form>
    </div>
</div>

<link rel="stylesheet" href="../../css/style.css" />
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

    form input, form button, form select {
        padding: 5px 10px;
        margin-top: 5px;
        width: 100%;
        box-sizing: border-box;
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

    .delete-link {
        color: red;
    }
</style>

<script>
document.querySelectorAll('.edit-btn').forEach(button => {
    button.addEventListener('click', function () {
        document.getElementById('editDoctorID').value = this.dataset.id;
        document.getElementById('editDepartmentID').value = this.dataset.dept;
        document.getElementById('editDoctorType').value = this.dataset.type;
        document.getElementById('editDoctorFee').value = this.dataset.fee;
        document.getElementById('editAvailability').value = this.dataset.availability;
        document.getElementById('editModal').style.display = 'flex';
    });
});

document.querySelector('.close').addEventListener('click', function () {
    document.getElementById('editModal').style.display = 'none';
});

window.onclick = function(event) {
    if (event.target == document.getElementById('editModal')) {
        document.getElementById('editModal').style.display = 'none';
    }
}
</script>
</body>
</html>
