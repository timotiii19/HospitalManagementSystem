<?php
ob_start();
session_start();

$can_edit = true;  // or false depending on your permission logic

if (!isset($_SESSION['username']) || $_SESSION['role'] != 'Admin') {
    header("Location: ../../auth/admin_login.php");
    exit();
}

include('../../includes/admin_header.php');
include('../../includes/admin_sidebar.php');
include('../../config/db.php');
include('../../auth/mailer.php'); // Your mail function

$csrfToken = $_SESSION['csrf_token'] ??= bin2hex(random_bytes(32));
$csrfValid = $_SERVER['REQUEST_METHOD'] !== 'POST'
    || (isset($_POST['csrf_token']) && hash_equals($csrfToken, $_POST['csrf_token']));

$success_message = $_SESSION['success_message'] ?? '';
unset($_SESSION['success_message']);

if (!$csrfValid) {
    http_response_code(403);
    $error_message = 'Your session expired. Refresh the page and try again.';
}

// Get all users
function getUsers($conn) {
    $query = "SELECT UserID, username, full_name, email, role, ContactNumber FROM users";
    $result = mysqli_query($conn, $query);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

if ($csrfValid && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['full_name'], $_POST['email'], $_POST['role'])) {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $contact = trim($_POST['ContactNumber'] ?? '');
    $allowedRoles = ['Admin', 'Doctor', 'Nurse', 'Pharmacist', 'Cashier'];

    if ($full_name === '' || strlen($full_name) > 200) {
        $error_message = 'Enter a name of 1 to 200 characters.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 200) {
        $error_message = 'Enter a valid email address (maximum 200 characters).';
    } elseif ($contact === '' || strlen($contact) > 30) {
        $error_message = 'Enter a contact number of 1 to 30 characters.';
    } elseif (!in_array($role, $allowedRoles, true)) {
        $error_message = 'Select a valid employee role.';
    } else {
        $check = $conn->prepare('SELECT UserID FROM users WHERE email = ? LIMIT 1');
        $check->bind_param('s', $email);
        $check->execute();
        $emailExists = $check->get_result()->num_rows > 0;
        $check->close();

        if ($emailExists) {
            $error_message = 'An account already uses that email address.';
        } else {
            $token = bin2hex(random_bytes(32));
            try {
                $conn->begin_transaction();
                $insertUser = $conn->prepare(
                    'INSERT INTO users (full_name, email, role, ContactNumber, token, token_expiry)
                     VALUES (?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 1 DAY))'
                );
                $insertUser->bind_param('sssss', $full_name, $email, $role, $contact, $token);
                $insertUser->execute();
                $user_id = $conn->insert_id;
                $insertUser->close();

                $roleInsert = match ($role) {
                    'Doctor' => $conn->prepare('INSERT INTO doctor (UserID, DoctorName, Email) VALUES (?, ?, ?)'),
                    'Nurse' => $conn->prepare('INSERT INTO nurse (UserID, Name, Email) VALUES (?, ?, ?)'),
                    'Pharmacist' => $conn->prepare('INSERT INTO pharmacist (UserID, Name, Email) VALUES (?, ?, ?)'),
                    'Cashier' => $conn->prepare('INSERT INTO cashier (UserID, Name) VALUES (?, ?)'),
                    'Admin' => $conn->prepare('INSERT INTO admin (UserID) VALUES (?)'),
                };

                if ($role === 'Admin') {
                    $roleInsert->bind_param('i', $user_id);
                } elseif ($role === 'Cashier') {
                    $roleInsert->bind_param('is', $user_id, $full_name);
                } else {
                    $roleInsert->bind_param('iss', $user_id, $full_name, $email);
                }

                $roleInsert->execute();
                $roleInsert->close();
                $conn->commit();
            } catch (mysqli_sql_exception $exception) {
                $conn->rollback();
                error_log('Unable to create employee account: ' . $exception->getMessage());
                $error_message = 'The account could not be created. No changes were saved.';
            }

            if (!isset($error_message)) {
                $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                $activationLink = $scheme . '://' . $host . APP_BASE_URL
                    . '/auth/complete_registration.php?token=' . rawurlencode($token);
                $message = "Hello $full_name,\n\n"
                    . "Use the link below within 24 hours to set up your Hospital Management System account:\n"
                    . $activationLink . "\n\n"
                    . "If you were not expecting this invitation, contact your hospital administrator.";

                if (sendMail($email, 'Set up your hospital account', $message)) {
                    $_SESSION['success_message'] = 'Employee added and activation email sent.';
                    header('Location: employees.php');
                    exit();
                }

                $error_message = 'The employee account was created, but the activation email was not sent. '
                    . 'Configure SMTP, then give the employee the setup link below. The link expires in 24 hours.';
                $pending_activation_link = $activationLink;
            }
        }
    }
}

if ($csrfValid && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $userId = filter_var($_POST['edit_user_id'] ?? null, FILTER_VALIDATE_INT);
    $username = trim($_POST['edit_username'] ?? '');
    $fullName = trim($_POST['edit_full_name'] ?? '');
    $email = trim($_POST['edit_email'] ?? '');
    $contact = trim($_POST['edit_ContactNumber'] ?? '');

    if (!$userId || strlen($username) < 3 || strlen($username) > 100) {
        $error_message = 'Enter a valid user and a username of 3 to 100 characters.';
    } elseif ($fullName === '' || strlen($fullName) > 200) {
        $error_message = 'Enter a name of 1 to 200 characters.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 200) {
        $error_message = 'Enter a valid email address (maximum 200 characters).';
    } elseif ($contact === '' || strlen($contact) > 30) {
        $error_message = 'Enter a contact number of 1 to 30 characters.';
    } else {
        $check = $conn->prepare(
            'SELECT UserID FROM users WHERE (username = ? OR email = ?) AND UserID <> ? LIMIT 1'
        );
        $check->bind_param('ssi', $username, $email, $userId);
        $check->execute();
        $duplicate = $check->get_result()->num_rows > 0;
        $check->close();

        if ($duplicate) {
            $error_message = 'That username or email is already used by another account.';
        } else {
            $existingUser = $conn->prepare('SELECT role FROM users WHERE UserID = ? LIMIT 1');
            $existingUser->bind_param('i', $userId);
            $existingUser->execute();
            $userData = $existingUser->get_result()->fetch_assoc();
            $existingUser->close();

            if (!$userData) {
                $error_message = 'That employee account no longer exists.';
            } else {
                try {
                    $conn->begin_transaction();
                    $update = $conn->prepare(
                        'UPDATE users SET username = ?, full_name = ?, email = ?, ContactNumber = ?
                         WHERE UserID = ?'
                    );
                    $update->bind_param('ssssi', $username, $fullName, $email, $contact, $userId);
                    $update->execute();
                    $update->close();

                    $roleUpdate = match ($userData['role']) {
                        'Doctor' => $conn->prepare('UPDATE doctor SET DoctorName = ?, Email = ? WHERE UserID = ?'),
                        'Nurse' => $conn->prepare('UPDATE nurse SET Name = ?, Email = ? WHERE UserID = ?'),
                        'Pharmacist' => $conn->prepare('UPDATE pharmacist SET Name = ?, Email = ? WHERE UserID = ?'),
                        'Cashier' => $conn->prepare('UPDATE cashier SET Name = ? WHERE UserID = ?'),
                        default => null,
                    };

                    if ($roleUpdate) {
                        if ($userData['role'] === 'Cashier') {
                            $roleUpdate->bind_param('si', $fullName, $userId);
                        } else {
                            $roleUpdate->bind_param('ssi', $fullName, $email, $userId);
                        }
                        $roleUpdate->execute();
                        $roleUpdate->close();
                    }

                    $conn->commit();
                    $_SESSION['success_message'] = 'Employee details updated.';
                    header('Location: employees.php');
                    exit();
                } catch (mysqli_sql_exception $exception) {
                    $conn->rollback();
                    error_log('Unable to update employee account: ' . $exception->getMessage());
                    $error_message = 'The account could not be updated. No changes were saved.';
                }
            }
        }
    }
}

if ($csrfValid && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $deleteId = filter_var($_POST['delete_user'], FILTER_VALIDATE_INT);
    $roleTables = [
        'Admin' => 'admin',
        'Doctor' => 'doctor',
        'Nurse' => 'nurse',
        'Pharmacist' => 'pharmacist',
        'Cashier' => 'cashier',
    ];

    if (!$deleteId) {
        $error_message = 'Select a valid employee account to delete.';
    } else {
        try {
            $conn->begin_transaction();
            $roleLookup = $conn->prepare('SELECT role, email FROM users WHERE UserID = ? LIMIT 1 FOR UPDATE');
            $roleLookup->bind_param('i', $deleteId);
            $roleLookup->execute();
            $userRole = $roleLookup->get_result()->fetch_assoc();
            $roleLookup->close();

            if (!$userRole || !isset($roleTables[$userRole['role']])) {
                $conn->rollback();
                $error_message = 'That employee account no longer exists.';
            } elseif ($userRole['role'] === 'Admin' && (int) $deleteId === (int) $_SESSION['UserID']) {
                $conn->rollback();
                $error_message = 'You cannot delete the account you are currently using.';
            } else {
                if ($userRole['role'] === 'Admin') {
                    $adminCount = $conn->query(
                        "SELECT COUNT(*) AS total FROM users u
                         INNER JOIN admin a ON a.UserID = u.UserID
                         WHERE u.role = 'Admin'"
                    )
                        ->fetch_assoc()['total'];
                    if ((int) $adminCount <= 1) {
                        $conn->rollback();
                        $error_message = 'The last administrator account cannot be deleted.';
                    }
                }

                if (!isset($error_message)) {
                    $roleDelete = $conn->prepare('DELETE FROM `' . $roleTables[$userRole['role']] . '` WHERE UserID = ?');
                    $roleDelete->bind_param('i', $deleteId);
                    $roleDelete->execute();
                    $roleDelete->close();

                    $resetDelete = $conn->prepare('DELETE FROM password_resets WHERE email = ?');
                    $resetDelete->bind_param('s', $userRole['email']);
                    $resetDelete->execute();
                    $resetDelete->close();

                    $userDelete = $conn->prepare('DELETE FROM users WHERE UserID = ?');
                    $userDelete->bind_param('i', $deleteId);
                    $userDelete->execute();
                    $userDelete->close();
                    $conn->commit();
                    $_SESSION['success_message'] = 'Employee account deleted.';
                    header('Location: employees.php');
                    exit();
                }
            }
        } catch (mysqli_sql_exception $exception) {
            $conn->rollback();
            error_log('Unable to delete employee account: ' . $exception->getMessage());
            $error_message = 'The account could not be deleted. No changes were saved.';
        }
    }
}


$users = getUsers($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Employee Management</title>
    <link rel="stylesheet" href="../../css/style.css" />
    <style>
            
         body {
            font-family: Arial, sans-serif;
            background-color: #ffffff;
        }

        .content {
            display: flex;
            gap: 20px;
            
        }

        /* Left column (list) takes the remaining width minus the fixed right column */
       .left-column {
    flex: 1 1 auto;
    margin-right: 320px; /* reserve space */
}

        .right-column {
            position: fixed;
            right: 10px; /* distance from right edge */
            top: 90px;   /* distance from top (adjust if you have a header) */
            width: 300px;
            background: rgb(226, 136, 173);
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 8px rgba(0,0,0,0.1);
            height: auto;
            
        }

        .right-column label {
            display: block;       /* Make labels appear on their own line */
            margin-bottom: 5px;   /* Space below label */
            font-weight: 600;
            }

.right-column input,
.right-column select,
.right-column textarea,
.right-column button {
  width: 100%;          /* Make inputs full width inside the container */
  padding: 8px 10px;    /* Some padding for comfort */
  margin-bottom: 15px;  /* Space below each input/select */
  border: 1px solid #ccc;
  border-radius: 4px;
  box-sizing: border-box;
}
        td:nth-child(4),
        th:nth-child(4) {
            max-width: 200px;
            word-break: break-word;
            white-space: normal;
        }

.right-column button {
  background-color:rgb(232, 157, 188);  /* Your pink shade */
  color: white;
  font-weight: 700;
  cursor: pointer;
  transition: background-color 0.3s ease;
}

.right-column button:hover {
  background-color: #eb5191;  /* Slightly darker on hover */
}

.right-column::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    height: 100%;
    width: 1.5px;
    background-color: #ccc;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}

th, td {
    border: 1px solid #ddd;
    padding: 10px;
    text-align: center; /* from first style, centered text */
}

th {
    background-color: #f8f9fa;
}

tr:hover {
    background-color: #f5f5f5;
}

.form-container input,
.form-container select,
.form-container button {
    margin: 6px 0;        /* was 10px, now tighter */
    padding: 6px 10px;    /* slightly smaller padding */
    width: 100%;
    box-sizing: border-box;
    font-size: 14px;
}

.form-container label {
    display: block;
    margin-top: 5px;
    font-weight: 600;
}

.right-column h2 {
    margin-top: 0px;
    margin-bottom: 15px;
}

.delete-link {
    color: red;
}

form input, form button {
    padding: 5px 10px;
    margin-top: 0px;
}

/* Buttons from first style */
button.view-btn {
    background-color:rgb(232, 66, 96);
    color: white;
    border: none;
    border-radius: 6px;
    padding: 8px 16px;
    cursor: pointer;
}

button.view-btn:hover {
    background-color:rgb(223, 84, 105);
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

        #filterModal {
            position: fixed;
            bottom: 25px;
            right: 20px;
            background: #fff;
            border: 1px solid #ccc;
            padding: 20px 25px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 10000;
            font-family: Arial, sans-serif;
            width: 250px;
        }

        #filterModal select {
            width: 100%;
            margin-top: 10px;
            padding: 8px 10px;
            font-size: 14px;
            border: 1px solid #ccc;
            border-radius: 5px;
            background: #fafafa;
            cursor: pointer;
        }

        #filterModal label {
            font-weight: 600;
            font-size: 14px;
            color: #333;
        }

    </style>
</head>
<body>

<div class="content employee-layout">
    <?php if ($success_message !== ''): ?>
        <p class="notice notice-success" role="status"><?= htmlspecialchars($success_message) ?></p>
    <?php endif; ?>
    <?php if (isset($error_message)): ?>
        <div class="notice notice-error" role="alert">
            <p><?= htmlspecialchars($error_message) ?></p>
            <?php if (isset($pending_activation_link)): ?>
                <p><a href="<?= htmlspecialchars($pending_activation_link) ?>">Open activation link</a></p>
                <label for="pendingActivationLink">Or copy this link to the employee:</label>
                <input id="pendingActivationLink" type="text" readonly value="<?= htmlspecialchars($pending_activation_link) ?>">
            <?php endif; ?>
        </div>
    <?php endif; ?>
    <div class="left-column">
        <h2>User List</h2>
        <table>
            <thead>
            <tr>
                <th>ID</th><th>Username</th><th>Full Name</th><th>Email</th><th>Contact</th><th>Role</th><th>Action</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= $user['UserID'] ?></td>
                    <td><?= htmlspecialchars($user['username']) ?></td>
                    <td><?= htmlspecialchars($user['full_name']) ?></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td><?= htmlspecialchars($user['ContactNumber'] ?? '') ?></td>
                    <td><?= ucfirst(htmlspecialchars($user['role'])) ?></td>
                    <td>
                        <?php if ($can_edit): ?>
                        <button type="button" class="edit-btn"
                           aria-label="Edit <?= htmlspecialchars($user['username'] ?? 'employee', ENT_QUOTES, 'UTF-8') ?>"
                           data-id="<?= $user['UserID'] ?>"
                           data-username="<?= htmlspecialchars($user['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                           data-full_name="<?= htmlspecialchars($user['full_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                           data-email="<?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?>"
                           data-contact="<?= htmlspecialchars($user['ContactNumber'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                           data-role="<?= htmlspecialchars($user['role'], ENT_QUOTES, 'UTF-8') ?>">Edit</button>
                        <?php endif; ?>
                        <form method="POST" action="employees.php" class="delete-user-form" onsubmit="return confirm('Delete this user?');">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="delete_user" value="<?= (int) $user['UserID'] ?>">
                            <button type="submit" class="delete-link">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="right-column">
        <h2>Add Employee</h2>
        <div class="form-container">
            <form method="POST" action="employees.php">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <label for="full_name">Full Name</label>
                <input type="text" name="full_name" required>

                <label for="email">Email</label>
                <input type="email" name="email" required>

                <label for="ContactNumber">Contact Number</label>
                <input type="text" name="ContactNumber" required>

                <label for="role">Role</label>
                <select name="role" required>
                    <option value="">Select Role</option>
                    <option value="Admin">Admin</option>
                    <option value="Doctor">Doctor</option>
                    <option value="Nurse">Nurse</option>
                    <option value="Pharmacist">Pharmacist</option>
                    <option value="Cashier">Cashier</option>
                </select>

                <button type="submit">Add User</button>
            </form>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" style="display:none; position:fixed; top:20%; left:50%; transform:translateX(-50%); background:#fff; padding:15px; box-shadow:0 0 8px rgba(0,0,0,0.2); z-index:999; font-size:14px; width:350px;">
    <form method="POST" action="employees.php">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="edit_user_id" id="edit_user_id">
        <label>Username</label>
        <input type="text" name="edit_username" id="edit_username" required>
        <label>Full Name</label>
        <input type="text" name="edit_full_name" id="edit_full_name" required>
        <label>Email</label>
        <input type="email" name="edit_email" id="edit_email" required>
        <label>Contact Number</label>
        <input type="text" name="edit_ContactNumber" id="edit_ContactNumber" required>
        <label for="edit_role">Role (managed separately)</label>
        <input type="text" id="edit_role" readonly>
        <div style="margin-top:10px; text-align:right;">
            <button type="submit" name="update_user">Update</button>
            <button type="button" onclick="document.getElementById('editModal').style.display='none'">Cancel</button>
        </div>
    </form>
</div>

<!-- PDF Button Container -->
<div id="pdf-button-container" style="position:fixed; bottom:160px; right:120px;">
    <button id="filterToggleBtn" class="btn btn-primary" style="
        padding: 10px 15px;
        background-color: #e888ad;
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-weight: bold;
    ">Generate PDF</button>
</div>

<!-- Filter PDF Modal -->
<div id="filterModal" style="
    display: none;
    position: fixed;
    bottom: 5px;
    right: 30px;
    background: #fff;
    border: 1px solid #ccc;
    padding: 10px 15px;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    z-index: 10000;
    font-family: Arial, sans-serif;
    width: 250px;
">
    <form method="POST" action="generate_user_pdf.php" style="display: flex; flex-direction: column; gap: 15px;">
        <label for="role" style="font-weight: 600; font-size: 14px; color: #333;">Filter Role:</label>
        <select name="role" id="role" style="
            padding: 8px 10px;
            font-size: 14px;
            border: 1px solid #ccc;
            border-radius: 5px;
            background: #fafafa;
            cursor: pointer;
            transition: border-color 0.3s ease;
        " onfocus="this.style.borderColor='#e888ad'" onblur="this.style.borderColor='#ccc'">
            <option value="all">All</option>
            <option value="Doctor">Doctor</option>
            <option value="Nurse">Nurse</option>
            <option value="Pharmacist">Pharmacist</option>
            <option value="Admin">Admin</option>
            <option value="Cashier">Cashier</option>
        </select>
        <button type="submit" style="
            padding: 10px;
            background-color: #e888ad;
            color: white;
            border: none;
            border-radius: 5px;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        " onmouseover="this.style.backgroundColor='#d46e92'" onmouseout="this.style.backgroundColor='#e888ad'">
            Generate PDF
        </button>
    </form>
</div>

<!-- Toggle Modal Script -->
<script>
    const toggleBtn = document.getElementById('filterToggleBtn');
    const filterModal = document.getElementById('filterModal');

    toggleBtn.addEventListener('click', () => {
        if (filterModal.style.display === 'none' || filterModal.style.display === '') {
            filterModal.style.display = 'block';
        } else {
            filterModal.style.display = 'none';
        }
    });
</script>



<script>
document.querySelectorAll('.edit-btn').forEach(btn => {
    btn.addEventListener('click', function () {
        document.getElementById('edit_user_id').value = this.dataset.id;
        document.getElementById('edit_username').value = this.dataset.username;
        document.getElementById('edit_full_name').value = this.dataset.full_name;
        document.getElementById('edit_email').value = this.dataset.email;
        document.getElementById('edit_ContactNumber').value = this.dataset.contact;
        document.getElementById('edit_role').value = this.dataset.role;
        document.getElementById('editModal').style.display = 'block';
    });
});
</script>

</body>
</html>

<?php ob_end_flush(); ?>
