<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'Admin') {
    header("Location: ../../auth/admin_login.php");
    exit();
}

include('../../config/db.php');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Add department
if (isset($_POST['add_department'])) {
    $dept_name = $_POST['department_name'];
    $dept_building = $_POST['department_building'];
    $dept_floor = $_POST['department_floor'];
    $dept_location = $dept_building . ', Floor ' . $dept_floor;

    $stmt = $conn->prepare("INSERT INTO department (DepartmentName, DepartmentBuilding, DepartmentFloor, DepartmentLocation) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $dept_name, $dept_building, $dept_floor, $dept_location);
    $stmt->execute();
    header("Location: departments.php");
    exit();
}

// Update department
if (isset($_POST['update_department'])) {
    $dept_id = $_POST['department_id'];
    $dept_name = $_POST['department_name'];
    $dept_building = $_POST['department_building'];
    $dept_floor = $_POST['department_floor'];
    $dept_location = $dept_building . ', Floor ' . $dept_floor;

    $stmt = $conn->prepare("UPDATE department SET DepartmentName = ?, DepartmentBuilding = ?, DepartmentFloor = ?, DepartmentLocation = ? WHERE DepartmentID = ?");
    $stmt->bind_param("ssssi", $dept_name, $dept_building, $dept_floor, $dept_location, $dept_id);
    $stmt->execute();
    header("Location: departments.php");
    exit();
}

// Delete department
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM department WHERE DepartmentID = $id");
    header("Location: departments.php");
    exit();
}

include('../../includes/admin_header.php');
include('../../includes/admin_sidebar.php');

// Fetch departments
$result = $conn->query("SELECT * FROM department");

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Department Management</title>
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

    form input, form button {
        padding: 5px 10px;
        margin-top: 5px;
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
        display: none; /* Hidden by default */
        position: fixed;
        z-index: 9999;
        left: 0; top: 0;
        width: 100%; height: 100%;
        background: rgba(0,0,0,0.5);
        justify-content: center;
        align-items: center;
    }

    .modal-content {
        background: white;
        padding: 30px;
        border-radius: 12px;
        max-width: 500px;
        width: 90%;
        position: relative;
        box-shadow: 0 0 12px rgba(0,0,0,0.2);
        text-align: left;
    }

    .close {
        position: absolute;
        top: 15px; right: 20px;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
        color: #888;
    }

    .close:hover {
        color: #000;
    }

    .form-group {
        margin-bottom: 15px;
    }

    label {
        display: block;
        margin-bottom: 6px;
        font-weight: 600;
    }

    input[type="text"] {
        width: 100%;
        padding: 8px 10px;
        border: 1px solid #ccc;
        border-radius: 6px;
        box-sizing: border-box;
    }

    button.save-btn {
        background-color:rgb(123, 120, 128);
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 16px;
    }

    button.save-btn:hover {
        background-color:rgb(190, 190, 191);
    }

    .delete-link {
        color: red;
        cursor: pointer;
        text-decoration: none;
    }

    .delete-link:hover {
        text-decoration: underline;
    }
</style>
</head>
<body>

<div class="content">
    <h2>Department Management</h2>

    <form method="post" action="" style="margin-bottom: 20px; max-width: 600px;">
        <div style="display: flex; gap: 10px; margin-bottom: 10px;">
            <input type="text" name="department_name" placeholder="Department Name" required style="flex: 1; padding:8px;">
            <input type="text" name="department_building" placeholder="Building" required style="flex: 1; padding:8px;">
            <input type="text" name="department_floor" placeholder="Floor" required style="flex: 1; padding:8px;">
        </div>
        <button type="submit" name="add_department" style="padding: 8px 16px;">Add Department</button>
    </form>

    <table>
        <tr>
            <th>ID</th>
            <th>Department Name</th>
            <th>Building</th>
            <th>Floor</th>
            <th>Location</th>
            <th>Action</th>
        </tr>
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['DepartmentID'] ?></td>
                    <td><?= htmlspecialchars($row['DepartmentName']) ?></td>
                    <td><?= htmlspecialchars($row['DepartmentBuilding']) ?></td>
                    <td><?= htmlspecialchars($row['DepartmentFloor']) ?></td>
                    <td><?= htmlspecialchars($row['DepartmentLocation']) ?></td>
                    <td>
                        <a href="javascript:void(0);" class="action-link"
                        onclick="showEditForm(
                            <?= $row['DepartmentID'] ?>,
                            '<?= addslashes(htmlspecialchars($row['DepartmentName'])) ?>',
                            '<?= addslashes(htmlspecialchars($row['DepartmentBuilding'])) ?>',
                            '<?= addslashes(htmlspecialchars($row['DepartmentFloor'])) ?>'
                        )">Edit</a> |
                        <a href="?delete=<?= $row['DepartmentID'] ?>"
                        class="delete-link"
                        onclick="return confirm('Are you sure you want to delete this department?');">Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="6">No departments found.</td></tr>
        <?php endif; ?>
    </table>
</div>

<!-- Edit Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h3>Edit Department</h3>
        <form method="post" action="departments.php" id="editForm">
            <input type="hidden" name="department_id" id="department_id">
            <div class="form-group">
                <label for="department_name">Department Name</label>
                <input type="text" id="department_name" name="department_name" required>
            </div>
            <div class="form-group">
                <label for="department_building">Building</label>
                <input type="text" id="department_building" name="department_building" required>
            </div>
            <div class="form-group">
                <label for="department_floor">Floor</label>
                <input type="text" id="department_floor" name="department_floor" required>
            </div>
            <button type="submit" name="update_department" class="save-btn">Save Changes</button>
        </form>
    </div>
</div>

<script>
function showEditForm(id, name, building, floor) {
    document.getElementById('department_id').value = id;
    document.getElementById('department_name').value = name;
    document.getElementById('department_building').value = building;
    document.getElementById('department_floor').value = floor;
    document.getElementById('editModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('editModal').style.display = 'none';
}

// Close modal when clicking outside modal content
window.onclick = function(event) {
    const modal = document.getElementById('editModal');
    if(event.target === modal) {
        closeModal();
    }
}
</script>

</body>
</html>
