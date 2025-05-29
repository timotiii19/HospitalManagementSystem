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

// Get all users
function getUsers($conn) {
    $query = "SELECT UserID, username, full_name, email, role, ContactNumber FROM users";
    $result = mysqli_query($conn, $query);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

// Handle Add User - NO username or password, send activation link instead
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['full_name']) && isset($_POST['email'])) {
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    $contact = mysqli_real_escape_string($conn, $_POST['ContactNumber']);

    // Check if email already exists
    $check_query = "SELECT * FROM users WHERE email = '$email'";
    $check_result = mysqli_query($conn, $check_query);
    if (mysqli_num_rows($check_result) > 0) {
        $error_message = "Email already exists!";
    } else {
        $token = bin2hex(random_bytes(16));
          $expiry = date('Y-m-d H:i:s', strtotime('+1 day'));
        $query = "INSERT INTO users (full_name, email, role, ContactNumber, token, token_expiry) 
          VALUES ('$full_name', '$email', '$role', '$contact', '$token', '$expiry')";
        if (mysqli_query($conn, $query)) {
            $user_id = mysqli_insert_id($conn);
            switch ($role) {
                case 'Doctor':
                    mysqli_query($conn, "INSERT INTO doctor (UserID, DoctorName, Email) VALUES ('$user_id', '$full_name', '$email')");
                    break;
                case 'Nurse':
                    mysqli_query($conn, "INSERT INTO nurse (UserID, Name, Email) VALUES ('$user_id', '$full_name', '$email')");
                    break;
                case 'Pharmacist':
                    mysqli_query($conn, "INSERT INTO pharmacist (UserID, Name, Email) VALUES ('$user_id', '$full_name', '$email')");
                    break;
                case 'Cashier':
                    mysqli_query($conn, "INSERT INTO cashier (UserID, Name) VALUES ('$user_id', '$full_name')");
                    break;
                case 'Admin':
                default:
                    mysqli_query($conn, "INSERT INTO admin (UserID) VALUES ('$user_id')");
                    break;
            }
            // Send activation email
            // Prepare activation link URL (replace localhost with your real domain when deploying)
            $link = "http://localhost/HMS-main/auth/complete_registration.php?token=$token";

            $subject = "Set up your account";

            $message = "
            Hi $full_name,
            Please click the button below to set up your account:
            $link Set Up Your Account
            Or copy and paste the following URL into your browser:
            $link 
            $link
            Thank you!
            ";

            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headers .= 'From: Your Company <no-reply@yourdomain.com>' . "\r\n";

            mail($email, $subject, $message, $headers);


           if (sendMail($email, $subject, $message)) {
                $_SESSION['success_message'] = "User added and activation email sent successfully!";
                header("Location: employees.php");
                exit();
            } else {
                $error_message = "User added, but failed to send activation email.";
            }

                }
            }
}

// Handle delete request
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);

    // Optional: Delete from role-specific table first
    $role_result = mysqli_query($conn, "SELECT role FROM users WHERE UserID = $delete_id");
    if ($role_result && mysqli_num_rows($role_result) > 0) {
        $role_row = mysqli_fetch_assoc($role_result);
        $role = $role_row['role'];

        switch ($role) {
            case 'Doctor':
                mysqli_query($conn, "DELETE FROM doctor WHERE UserID = $delete_id");
                break;
            case 'Nurse':
                mysqli_query($conn, "DELETE FROM nurse WHERE UserID = $delete_id");
                break;
            case 'Pharmacist':
                mysqli_query($conn, "DELETE FROM pharmacist WHERE UserID = $delete_id");
                break;
            case 'Cashier':
                mysqli_query($conn, "DELETE FROM cashier WHERE UserID = $delete_id");
                break;
            case 'Admin':
                mysqli_query($conn, "DELETE FROM admin WHERE UserID = $delete_id");
                break;
        }
    }

    // Now delete from main users table
    mysqli_query($conn, "DELETE FROM users WHERE UserID = $delete_id");

    // Redirect to prevent resubmission on page refresh
    header("Location: employees.php");
    exit();
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

<div class="content">
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
                        <a href="javascript:void(0);" class="edit-btn"
                           data-id="<?= $user['UserID'] ?>"
                           data-username="<?= htmlspecialchars($user['username']) ?>"
                           data-full_name="<?= htmlspecialchars($user['full_name']) ?>"
                           data-email="<?= htmlspecialchars($user['email']) ?>"
                           data-contact="<?= htmlspecialchars($user['ContactNumber']) ?>"
                           data-role="<?= htmlspecialchars($user['role']) ?>" ></a> 
                        <?php endif; ?>
                        <a href="employees.php?delete=<?= $user['UserID'] ?>" onclick="return confirm('Delete this user?');" class="delete-link">Delete</a>
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
                <!-- Removed Username and Password inputs -->
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
            <?php if (isset($error_message)) echo "<p style='color:red;'>$error_message</p>"; ?>

        </div>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" style="display:none; position:fixed; top:20%; left:50%; transform:translateX(-50%); background:#fff; padding:15px; box-shadow:0 0 8px rgba(0,0,0,0.2); z-index:999; font-size:14px; width:350px;">
    <form method="POST" action="employees.php">
        <input type="hidden" name="edit_user_id" id="edit_user_id">
        <label>Username</label>
        <input type="text" name="edit_username" id="edit_username" required>
        <label>Full Name</label>
        <input type="text" name="edit_full_name" id="edit_full_name" required>
        <label>Email</label>
        <input type="email" name="edit_email" id="edit_email" required>
        <label>Contact Number</label>
        <input type="text" name="edit_ContactNumber" id="edit_ContactNumber" required>
        <label>Role</label>
        <select name="edit_role" id="edit_role" required>
            <option value="Admin">Admin</option>
            <option value="Doctor">Doctor</option>
            <option value="Nurse">Nurse</option>
            <option value="Pharmacist">Pharmacist</option>
            <option value="Cashier">Cashier</option>
        </select>
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
