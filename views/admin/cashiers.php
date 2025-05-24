<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'Admin') {
    header("Location: ../../auth/admin_login.php");
    exit();
}

$can_edit = false;

include('../../includes/admin_header.php');
include('../../includes/admin_sidebar.php');
include('../../config/db.php');

// Handle update
if (isset($_POST['update_cashier'])) {
    $cashier_id = $_POST['cashier_id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $contact = $_POST['contact'];

    // Get UserID from cashier
    $getUser = $conn->prepare("SELECT UserID FROM cashier WHERE CashierID = ?");
    $getUser->bind_param("i", $cashier_id);
    $getUser->execute();
    $getUser->bind_result($user_id);
    $getUser->fetch();
    $getUser->close();

    // Update cashier
    $stmt1 = $conn->prepare("UPDATE cashier SET Name=? WHERE CashierID=?");
    $stmt1->bind_param("si", $name, $cashier_id);
    $stmt1->execute();
    $stmt1->close();

    // Update user email and contact
    $stmt2 = $conn->prepare("UPDATE users SET Email=?, ContactNumber=? WHERE UserID=?");
    $stmt2->bind_param("ssi", $email, $contact, $user_id);
    $stmt2->execute();
    $stmt2->close();

    header("Location: cashier.php");
    exit();
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM cashier WHERE CashierID = $id");
    header("Location: cashier.php");
    exit();
}

// Fetch cashiers with contact and email from users
$result = $conn->query("
    SELECT c.CashierID, c.Name, u.Email, u.ContactNumber
    FROM cashier c
    JOIN users u ON c.UserID = u.UserID
");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Cashier Management</title>
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

        .edit-link {
            color: #007bff;
            cursor: pointer;
            text-decoration: underline;
        }

        .delete-link {
            color: #dc3545;
            text-decoration: underline;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,0.6);
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            width: 400px;
            position: relative;
        }

        .modal-close {
            position: absolute;
            top: 10px; right: 10px;
            cursor: pointer;
            font-size: 20px;
        }

        .form-group {
            margin-bottom: 15px;
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: bold;
        }

        .form-group input {
            width: 100%;
            padding: 8px;
        }

        .sbtn {
            background-color: #6f42c1;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        .sbtn:hover {
            background-color: #512da8;
        }
    </style>
</head>
<body>

<div class="content">
    <h2>Cashier Management</h2>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Cashier Name</th>
                    <th>Email</th>
                    <th>Contact Number</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['CashierID'] ?></td>
                            <td><?= htmlspecialchars($row['Name']) ?></td>
                            <td><?= htmlspecialchars($row['Email']) ?></td>
                            <td><?= htmlspecialchars($row['ContactNumber']) ?></td>
                            <td>
                                 <?php if ($can_edit): ?>
                                <span class="edit-link" onclick="openModal(
                                    <?= $row['CashierID'] ?>,
                                    '<?= htmlspecialchars($row['Name'], ENT_QUOTES) ?>',
                                    '<?= htmlspecialchars($row['Email'], ENT_QUOTES) ?>',
                                    '<?= htmlspecialchars($row['ContactNumber'], ENT_QUOTES) ?>'
                                )">Edit</span>
                                |
                                <?php endif; ?>
                                <a href="?delete=<?= $row['CashierID'] ?>" class="delete-link" onclick="return confirm('Are you sure?')">Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5">No cashier records found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="modal-close" onclick="closeModal()">×</span>
        <h3>Edit Cashier Details</h3>
        <form method="post" action="cashier.php">
            <input type="hidden" name="cashier_id" id="modal_cashier_id">

            <div class="form-group">
                <label>Name</label>
                <input type="text" name="name" id="modal_name" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" id="modal_email" required>
            </div>
            <div class="form-group">
                <label>Contact Number</label>
                <input type="text" name="contact" id="modal_contact" required>
            </div>

            <button type="submit" name="update_cashier" class="sbtn">Save Changes</button>
        </form>
    </div>
</div>

<script>
function openModal(id, name, email, contact) {
    document.getElementById('modal_cashier_id').value = id;
    document.getElementById('modal_name').value = name;
    document.getElementById('modal_email').value = email;
    document.getElementById('modal_contact').value = contact;
    document.getElementById('editModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('editModal').style.display = 'none';
}
</script>

</body>
</html>
