<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'Pharmacist') {
    header("Location: ../../auth/pharmacist_login.php");
    exit();
}

include('../../config/db.php');

$pharmacistID = $_SESSION['role_id'];
$medicines = $conn->query("SELECT * FROM pharmacy");

if (isset($_POST['add_medicine'])) {
    $medicine_name = $_POST['medicine_name'];
    $quantity = $_POST['quantity'];
    $price = $_POST['price'];
    $stmt = $conn->prepare("INSERT INTO pharmacy (MedicineName, StockQuantity, Price, PharmacistID) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("siii", $medicine_name, $quantity, $price, $pharmacistID);
    $stmt->execute();
    header("Location: pharmacy.php");
    exit();
}

if (isset($_POST['update_medicine'])) {
    $medicine_id = $_POST['medicine_id'];
    $quantity = $_POST['quantity'];
    $price = $_POST['price'];
    $stmt = $conn->prepare("UPDATE pharmacy SET StockQuantity = ?, Price = ? WHERE MedicineID = ?");
    $stmt->bind_param("idi", $quantity, $price, $medicine_id);
    $stmt->execute();
    header("Location: pharmacy.php");
    exit();
}

if (isset($_GET['delete'])) {
    $medicine_id = $_GET['delete'];
    $conn->query("DELETE FROM pharmacy WHERE MedicineID = $medicine_id");
    header("Location: pharmacy.php");
    exit();
}
include('../../includes/pharmacist_sidebar.php');
include('../../includes/pharmacist_header.php');
?>

<!DOCTYPE html>
<html>
<head>
    <title>Pharmacy Management</title>
    <link rel="stylesheet" type="text/css" href="../../css/style.css">
    <style>
        a.delete-btn {
            color: white;
            background-color:rgba(237, 29, 50, 0.64);
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            display: inline-block;
        }

        a.delete-btn:hover {
            background-color: #c82333;
        }

        .update-btn {
            color: white;
            background-color:rgba(212, 89, 121, 0.9);;
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }

        .update-btn:hover {
            background-color:rgba(212, 89, 121, 0.9);;
        }

        input[type="number"]::-webkit-outer-spin-button,
        input[type="number"]::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        input[type="number"] {
            -moz-appearance: textfield;
        }

        form.inline-form input {
            width: 80px;
            padding: 5px;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
            justify-content: center;
            align-items: center;
        }
    </style>
</head>
<body>

<div class="content">
    <h2>Pharmacy Management</h2>

    <form method="POST">
        <label>Medicine Name:</label>
        <input type="text" name="medicine_name" required>
        <label>Quantity:</label>
        <input type="number" name="quantity" required>
        <label>Price:</label>
        <input type="number" step="0.01" name="price" required>
        <button type="submit" name="add_medicine">Add Medicine</button>
    </form>

    <table border="1">
        <tr>
            <th>Medicine ID</th>
            <th>Medicine Name</th>
            <th>Quantity</th>
            <th>Price</th>
            <th>Action</th>
        </tr>
        <?php while ($row = $medicines->fetch_assoc()) { ?>
        <tr>
            <form method="POST" class="inline-form">
                <td><?= $row['MedicineID'] ?></td>
                <td><?= $row['MedicineName'] ?></td>
                <td>
                    <input type="hidden" name="medicine_id" value="<?= $row['MedicineID'] ?>">
                    <input type="number" name="quantity" value="<?= $row['StockQuantity'] ?>" required>
                </td>
                <td>
                    <input type="number" step="0.01" name="price" value="<?= number_format($row['Price'], 2, '.', '') ?>" required>
                </td>
                <td class="action-buttons">
                    <button type="submit" name="update_medicine" class="update-btn">Update</button>
                    <a href="?delete=<?= $row['MedicineID'] ?>" class="delete-btn" onclick="return confirm('Are you sure?')">Delete</a>
                </td>
            </form>
        </tr>
        <?php } ?>
    </table>
</div>

</body>
</html>



<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Patient Management</title>
<link rel="stylesheet" href="../../css/style.css" />
<style>
    body {
        font-family: Arial, sans-serif;
        background-color: #ffffff;
    }

    .content {
        padding: 40px;
        margin-top: -20px;
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
        background-color:rgb(187, 27, 27);
        color:rgb(255, 255, 255);
    }

    form input, form button {
        padding: 5px 10px;
        margin-top: 5px;
    }

    button.view-btn {
        background-color:rgb(207, 93, 122);
        color: white;
        border: none;
        border-radius: 6px;
        padding: 8px 16px;
        cursor: pointer;
    }

    button.view-btn:hover {
        background-color:rgb(218, 80, 108);
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
        background-color:rgb(237, 105, 140);
        padding: 10px 20px;
        border-radius: 6px;
        font-size: 14px;
    }

    .back-link:hover {
        background-color:rgb(234, 90, 131);
    }
</style>
</head>
<body>