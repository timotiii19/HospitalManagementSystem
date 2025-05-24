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
if (isset($_POST['update_pharmacist'])) {
    $pharmacist_id = $_POST['pharmacist_id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $contact = $_POST['contact'];

    // Get UserID from pharmacist
    $getUser = $conn->prepare("SELECT UserID FROM pharmacist WHERE PharmacistID = ?");
    $getUser->bind_param("i", $pharmacist_id);
    $getUser->execute();
    $getUser->bind_result($user_id);
    $getUser->fetch();
    $getUser->close();

    // Update pharmacist info
    $stmt = $conn->prepare("UPDATE pharmacist SET Name=?, Email=? WHERE PharmacistID=?");
    $stmt->bind_param("ssi", $name, $email, $pharmacist_id);
    $stmt->execute();
    $stmt->close();

    // Update user contact number
    $stmt2 = $conn->prepare("UPDATE users SET ContactNumber=? WHERE UserID=?");
    $stmt2->bind_param("si", $contact, $user_id);
    $stmt2->execute();
    $stmt2->close();

    header("Location: pharmacists.php");
    exit();
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM pharmacist WHERE PharmacistID = $id");
    header("Location: pharmacists.php");
    exit();
}

// Fetch pharmacists with contact number from users table
$result = $conn->query("
    SELECT p.PharmacistID, p.Name, p.Email, u.ContactNumber
    FROM pharmacist p
    JOIN users u ON p.UserID = u.UserID
");

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Pharmacists Management</title>
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

    /* Modal styles (based on your patient details page) */
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

    .profile-img {
        width: 100px;
        height: 100px;
        margin: 0 auto 30px;
        border-radius: 50%;
        background-color: #f0f0f0;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .profile-img img {
        width: 60px;
        height: 60px;
    }

    .info-row {
        display: flex;
        justify-content: space-between;
        margin: 12px 0;
        font-size: 16px;
        color: #555;
    }

    .info-row strong {
        font-weight: 600;
        color: #444;
    }

    .back-link {
        display: inline-block;
        margin-top: 30px;
        text-decoration: none;
        color: #fff;
        background-color: #6f42c1;
        padding: 10px 20px;
        border-radius: 6px;
        font-size: 14px;
    }

    .back-link:hover {
        background-color: #512da8;
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
        .form-group { /*Edit pharm*/
        margin-bottom: 15px;
        display: flex;
        flex-direction: column;
        }
        .form-group label {
        margin-bottom: 5px;
        font-weight: bold;
        }
        .form-group input {
        padding: 8px;
        border: 1px solid #ccc;
        border-radius: 4px;
        }

        /* Modal Styles */
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
  </style>
</head>
<body>

<div class="content">
    <h2>Pharmacist Management</h2>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Pharmacist Name</th>
                    <th>Email</th>
                    <th>Contact Number</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['PharmacistID'] ?></td>
                        <td><?= htmlspecialchars($row['Name']) ?></td>
                        <td><?= htmlspecialchars($row['Email']) ?></td>
                        <td><?= htmlspecialchars($row['ContactNumber'] ?? '') ?></td>
                        <td>
                             <?php if ($can_edit): ?>
                            <span class="edit-link" onclick="openModal(
                                <?= $row['PharmacistID'] ?>,
                                '<?= htmlspecialchars($row['Name'], ENT_QUOTES) ?>',
                                '<?= htmlspecialchars($row['Email'], ENT_QUOTES) ?>',
                                '<?= htmlspecialchars($row['ContactNumber'], ENT_QUOTES) ?>'
                            )">Edit</span>
                            |
                             <?php endif; ?>
                            <a href="?delete=<?= $row['PharmacistID'] ?>" class="delete-link" onclick="return confirm('Are you sure?')">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="5">No pharmacist records found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">×</span>
        <h3>Edit Pharmacist Details</h3>
        <form method="post" action="pharmacists.php">
            <input type="hidden" name="pharmacist_id" id="modal_pharmacist_id">

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
            <button type="submit" name="update_pharmacist" class="sbtn">Save Changes</button>
        </form>
    </div>
</div>

<script>
function openModal(id, name, email, contact) {
    document.getElementById('modal_pharmacist_id').value = id;
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
