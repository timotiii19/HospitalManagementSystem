<?php
ob_start();
session_start();

if (!isset($_SESSION['username']) || $_SESSION['role'] != 'Admin') {
    header("Location: ../../auth/admin_login.php");
    exit();
}

include('../../includes/admin_header.php');
include('../../includes/admin_sidebar.php');
include('../../config/db.php');

$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

function getAdmins($conn, $filter = 'all') {
    $baseQuery = "SELECT u.UserID, u.username, u.email, u.full_name, u.ContactNumber, a.superadmin, u.role
                  FROM admin a
                  JOIN users u ON a.UserID = u.UserID";

    if ($filter === 'superadmin') {
        $baseQuery .= " WHERE a.superadmin = 1";
    } elseif ($filter === 'admin') {
        $baseQuery .= " WHERE a.superadmin = 0";
    }

    $result = mysqli_query($conn, $baseQuery);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

function isLastAdmin($conn) {
    $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM admin");
    $count = mysqli_fetch_assoc($result);
    return $count['total'] <= 1;
}

// Promote admin to superadmin
if (isset($_GET['make_superadmin']) && is_numeric($_GET['make_superadmin'])) {
    $target_id = $_GET['make_superadmin'];
    $current_user_id = $_SESSION['UserID'];

    $is_current_superadmin = mysqli_fetch_assoc(
        mysqli_query($conn, "SELECT superadmin FROM admin WHERE UserID = '$current_user_id'")
    )['superadmin'];

    if ($is_current_superadmin) {
        mysqli_query($conn, "UPDATE admin SET superadmin = 1 WHERE UserID = '$target_id'");
    }

    header("Location: admin.php?filter=$filter");
    exit();
}

// Remove admin
if (isset($_GET['remove']) && is_numeric($_GET['remove'])) {
    $target_id = $_GET['remove'];
    $current_user_id = $_SESSION['user_id'];

    $admin_query = mysqli_query($conn, "SELECT superadmin FROM admin WHERE UserID = '$current_user_id'");
    $target_query = mysqli_query($conn, "SELECT superadmin FROM admin WHERE UserID = '$target_id'");

    if (!$admin_query || !$target_query) {
        die("Database query failed: " . mysqli_error($conn));
    }

    $is_superadmin = mysqli_fetch_assoc($admin_query)['superadmin'];
    $is_target_superadmin = mysqli_fetch_assoc($target_query)['superadmin'];

    if ($current_user_id == $target_id) {
        $error = "You cannot remove yourself as admin.";
    } elseif (isLastAdmin($conn)) {
        $error = "You cannot remove the last remaining admin.";
    } elseif (!$is_superadmin && $is_target_superadmin) {
        $error = "Only superadmins can remove other superadmins.";
    } else {
        mysqli_query($conn, "DELETE FROM admin WHERE UserID = '$target_id'");
        mysqli_query($conn, "UPDATE users SET role = 'user' WHERE UserID = '$target_id'");
        header("Location: admin.php?filter=$filter");
        exit();
    }
}

$admins = getAdmins($conn, $filter);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Management</title>
    <link rel="stylesheet" href="../../css/style.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #ffffff;
        }

        .content {
            padding: 40px;
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

        .filter-buttons {
            margin-bottom: 20px;
        }

        .filter-buttons button {
            background-color: #f8d7da;
            color:rgb(224, 48, 86);
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

        a {
            color:rgb(222, 180, 189);
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
            color:rgb(228, 80, 112);
        }

        .remove-link {
            color:rgb(218, 50, 50);
            font-weight: bold;
        }

        .remove-link:hover {
            color: #a0223f;
            text-decoration: underline;
        }
    </style>
</head>
<body>
<div class="content">
    <h2>Admin Management</h2>

    <div class="filter-buttons">
        <a href="admin.php?filter=all"><button class="<?= $filter === 'all' ? 'active' : '' ?>">All Admins</button></a>
        <a href="admin.php?filter=superadmin"><button class="<?= $filter === 'superadmin' ? 'active' : '' ?>">Superadmins</button></a>
        <a href="admin.php?filter=admin"><button class="<?= $filter === 'admin' ? 'active' : '' ?>">Admins</button></a>
    </div>
    <br>

    <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>

    <div class="table-container">
        <table class="responsive-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Contact Number</th>
                    <th>Role</th>
                    <th>Superadmin</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($admins as $admin): ?>
                <tr>
                    <td><?= $admin['UserID'] ?></td>
                    <td><?= htmlspecialchars($admin['username']) ?></td>
                    <td><?= htmlspecialchars($admin['full_name']) ?></td>
                    <td><?= htmlspecialchars($admin['email']) ?></td>
                    <td><?= htmlspecialchars($admin['ContactNumber'] ?? '') ?></td>
                    <td><?= htmlspecialchars($admin['role']) ?></td>
                    <td><?= $admin['superadmin'] ? 'Yes' : 'No' ?></td>
                    <td>
                        <?php if ($_SESSION['UserID'] != $admin['UserID']): ?>
                            <a class="remove-link" href="admin.php?remove=<?= $admin['UserID'] ?>&filter=<?= $filter ?>" onclick="return confirm('Are you sure you want to remove admin rights from <?= htmlspecialchars($admin['username']) ?>? This cannot be undone.');">Remove</a>
                        <?php else: ?>
                            (You)
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>

<?php ob_end_flush(); ?>
