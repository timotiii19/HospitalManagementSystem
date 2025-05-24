<?php
session_start();

if (!isset($_SESSION['username']) || $_SESSION['role'] != 'Admin') {
    header("Location: ../../auth/admin_login.php");
    exit();
}

include('../../includes/admin_header.php');
include('../../config/db.php');

if (!$conn) {
    die("Database connection failed");
}

// Update nurse (only availability and department)
if (isset($_POST['update_nurse'])) {
    $nurse_id = $_POST['nurse_id'];
    $availability = $_POST['availability'];
    $department_id = $_POST['department_id'];

    $stmt = $conn->prepare("UPDATE nurse SET Availability=?, DepartmentID=? WHERE NurseID=?");
    $stmt->bind_param("sii", $availability, $department_id, $nurse_id);
    $stmt->execute();

    header("Location: nurses.php");
    exit();
}

// Delete nurse
if (isset($_GET['delete'])) {
    $nurse_id = $_GET['delete'];
    $result3 = $conn->query("SELECT UserID FROM nurse WHERE NurseID = $nurse_id");
    if ($row3 = $result3->fetch_assoc()) {
        $user_id = $row3['UserID'];
        $conn->query("DELETE FROM nurse WHERE NurseID = $nurse_id");
        $conn->query("DELETE FROM users WHERE UserID = $user_id");
    }
    header("Location: nurses.php");
    exit();
}

$filter_availability = isset($_GET['availability']) ? $_GET['availability'] : 'all';
$whereClause = '';
if ($filter_availability !== 'all') {
    $whereClause = " AND n.Availability = '" . $conn->real_escape_string($filter_availability) . "'";
}

// Get departments for dropdown
$departments = [];
$dept_result = $conn->query("SELECT DepartmentID, DepartmentName FROM department");
if ($dept_result && $dept_result->num_rows > 0) {
    while ($dept = $dept_result->fetch_assoc()) {
        $departments[] = $dept;
    }
}

// Get nurse data
$query = "SELECT n.NurseID, u.username AS NurseName, u.email AS Email, u.ContactNumber, n.Availability, n.DepartmentID
          FROM nurse n
          JOIN users u ON n.UserID = u.UserID
          WHERE 1=1 $whereClause";

$result = $conn->query($query);
if (!$result) {
    die("SQL error: " . $conn->error);
}

include('../../includes/admin_sidebar.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Nurses Management</title>
<link rel="stylesheet" href="../../css/style.css" />
<style>
    body {
        font-family: Arial, sans-serif;
        background-color: #ffffff;
    }
    .content {
        padding: 40px;
    }
    .filter-buttons {
        margin-bottom: 20px;
    }
    .filter-buttons button {
        background-color: #f8d7da;
        color: rgb(224, 48, 86);
        margin-right: 10px;
        padding: 10px 20px;
        font-size: 15px;
        font-weight: bold;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        transition: 0.3s ease;
        box-shadow: 0 3px 8px rgba(204, 60, 91, 0.82);
    }
    .filter-buttons button:hover {
        background-color: #f1b0b7;
        box-shadow: 0 4px 12px rgba(223, 51, 88, 0.76);
    }
    .filter-buttons button.active {
        background-color: #a0223f;
        color: white;
        box-shadow: 0 6px 15px rgba(218, 55, 90, 0.79);
    }
    .table-container {
        overflow-x: auto;
        margin-top: 20px;
    }
    .responsive-table {
        width: 100%;
        max-width: 100%;
        border-collapse: collapse;
        min-width: 1000px;
    }
    .responsive-table th, .responsive-table td {
        padding: 12px 15px;
        text-align: center;
        border: 1px solid #ddd;
        white-space: nowrap;
        color: #000000;
    }
    .responsive-table th {
        background-color: #f8f9fa;
        color: #000000;
        font-weight: 600;
    }
    .edit-link {
        color: #007bff;
        cursor: pointer;
        text-decoration: underline;
    }
    .edit-link:hover {
        text-decoration: none;
    }
    .delete-link {
        color: #dc3545;
        text-decoration: underline;
        cursor: pointer;
    }
    .delete-link:hover {
        text-decoration: none;
    }
    .modal {
        position: fixed;
        z-index: 9999;
        left: 0; top: 0;
        width: 100%; height: 100%;
        background-color: rgba(0,0,0,0.5);
        display: none;
        justify-content: center;
        align-items: center;
    }
    .modal-content {
        background-color: #fff;
        padding: 30px;
        border-radius: 12px;
        max-width: 500px;
        width: 90%;
        position: relative;
        box-shadow: 0 0 15px rgba(0,0,0,0.3);
        text-align: left;
    }
    .close {
        position: absolute;
        top: 12px;
        right: 15px;
        font-size: 28px;
        font-weight: bold;
        color: #888;
        cursor: pointer;
    }
    .close:hover {
        color: #000;
    }
    form input, form button, form select {
        padding: 8px 12px;
        margin-top: 10px;
        width: 100%;
        box-sizing: border-box;
    }
    button.save-btn {
        background-color: #6f42c1;
        color: white;
        border: none;
        border-radius: 6px;
        padding: 10px;
        cursor: pointer;
        margin-top: 15px;
        width: 100%;
        font-weight: bold;
        font-size: 16px;
    }
    button.save-btn:hover {
        background-color: #512da8;
    }
</style>
</head>
<body>
<div class="content">
    <h2>Nurse Management</h2>

    <div class="filter-buttons">
        <a href="nurses.php?availability=all"><button class="<?= $filter_availability === 'all' ? 'active' : '' ?>">All</button></a>
        <a href="nurses.php?availability=Available"><button class="<?= $filter_availability === 'Available' ? 'active' : '' ?>">Available</button></a>
        <a href="nurses.php?availability=On Leave"><button class="<?= $filter_availability === 'On Leave' ? 'active' : '' ?>">On Leave</button></a>
    </div>

    <div class="table-container">
        <table class="responsive-table">
            <thead>
                <tr>
                    <th>NurseID</th>
                    <th>NurseName</th>
                    <th>Email</th>
                    <th>Availability</th>
                    <th>ContactNumber</th>
                    <th>Department</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                   <tr>
                        <td><?= $row['NurseID'] ?></td>
                        <td><?= htmlspecialchars($row['NurseName']) ?></td>
                        <td><?= htmlspecialchars($row['Email']) ?></td>
                        <td><?= htmlspecialchars($row['Availability'] ?? '') ?></td>
                        <td><?= htmlspecialchars($row['ContactNumber'] ?? '') ?></td>
                        <td><?= htmlspecialchars($row['DepartmentID'] ?? '') ?></td>
                        <td>
                            <span class="edit-link" 
                                onclick="showEditForm(
                                    <?= $row['NurseID'] ?>,
                                    '<?= htmlspecialchars($row['Availability'] ?? '') ?>',
                                    <?= $row['DepartmentID'] ?>
                                )">Edit</span>
                            |
                            <a href="?delete=<?= $row['NurseID'] ?>" class="delete-link" onclick="return confirm('Are you sure?')">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="7">No nurse records found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Overlay -->
<div id="editModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="closeModal()">&times;</span>
    <h3>Edit Nurse Details</h3>
    <form id="editForm" method="post" action="nurses.php">
      <input type="hidden" name="nurse_id" id="nurse_id">
      <label>Availability</label>
      <select name="availability" id="availability">
        <option value="Available">Available</option>
        <option value="On Leave">On Leave</option>
      </select>
      <label>Department</label>
      <select name="department_id" id="department_id">
        <?php foreach ($departments as $dept): ?>
            <option value="<?= $dept['DepartmentID'] ?>"><?= htmlspecialchars($dept['DepartmentName']) ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" name="update_nurse" class="save-btn">Save Changes</button>
    </form>
  </div>
</div>

<script>
function closeModal() {
    document.getElementById('editModal').style.display = 'none';
}

function showEditForm(nurseID, availability, departmentID) {
    const modal = document.getElementById('editModal');
    modal.style.display = 'flex';

    document.getElementById('nurse_id').value = nurseID;
    document.getElementById('availability').value = availability;
    document.getElementById('department_id').value = departmentID;
}

window.onclick = function(event) {
    const modal = document.getElementById('editModal');
    if (event.target === modal) {
        closeModal();
    }
};
</script>
</body>
</html>
