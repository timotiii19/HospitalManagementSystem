<?php
include('../config/db.php');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $contactNumber = $_POST['contactNumber'];
    $symptoms = !empty($_POST['symptoms']) ? $_POST['symptoms'] : null;

    if (!preg_match('/^[0-9]{10,15}$/', $contactNumber)) {
        $message = "Please enter a valid contact number.";
    } else {
        $patientID = 1; // Placeholder or dynamic logic here

        $stmt = $conn->prepare("INSERT INTO Emergency (PatientID, Symptoms, Name, ContactNumber) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("issi", $patientID, $symptoms, $name, $contactNumber);

        if ($stmt->execute()) {
            $message = "Emergency record submitted successfully.";
        } else {
            $message = "Error: " . $stmt->error;
        }
        $stmt->close();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Emergency Form</title>
    <style>
    body {
        margin: 0;
        font-family: Arial, sans-serif;
        background: linear-gradient(to right, #7a2e2e, #d4b9b9);
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        flex-wrap: wrap;
    }
    .form-container {
        background: white;
        border-radius: 25px;
        padding: 50px 40px;
        width: 420px;
        min-height: 500px;
        box-shadow: 0 0 20px rgba(0,0,0,0.1);
        text-align: center;
        position: relative;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .form-container img.redcross {
        position: absolute;
        top: -75px;
        left: calc(50% - 75px);
        width: 150px;
        height: 150px;
    }

    .form-container h2 {
        color: #0056b3;
        margin: 60px 0 20px; /* ensures space below logo */
    }

    .form-container form {
        flex-grow: 1;
        display: flex;
        flex-direction: column;
        justify-content: space-evenly;
    }

    .form-container input,
    .form-container select {
        width: 100%;
        padding: 18px;
        margin-bottom: 20px;
        border: 1px solid #ccc;
        border-radius: 25px;
        font-size: 18px;
        box-sizing: border-box;
        flex-shrink: 0;
    }

    .form-container input[type="submit"] {
        background-color: #e60000;
        color: white;
        font-weight: bold;
        cursor: pointer;
        padding: 18px;
        font-size: 20px;
        margin-top: 40px;
    }

    .logo-container img {
        width: 100%;
        max-width: 600px;
        margin-top: 30px;
        margin-left: 30px;
    }

    .error {
        color: red;
        margin-bottom: 10px;
    }

    .success {
        color: green;
        margin-bottom: 10px;
    }

    </style>
    <script>
        function validateForm() {
            const contact = document.forms["emergencyForm"]["contactNumber"].value;
            const regex = /^[0-9]{10,15}$/;
            if (!regex.test(contact)) {
                alert("Please enter a valid contact number (10-15 digits).");
                return false;
            }
            return true;
        }
    </script>
</head>
<body>

<div class="form-container">
    <img src="../images/redcross.png" alt="Red Cross" class="redcross">
    <h2>EMERGENCY</h2>
    <?php if ($message): ?>
        <p class="<?php echo strpos($message, 'successfully') !== false ? 'success' : 'error'; ?>"><?php echo $message; ?></p>
    <?php endif; ?>
    <form name="emergencyForm" method="post" onsubmit="return validateForm()">
        <input type="text" name="name" placeholder="Full Name" required>
        <input type="text" name="contactNumber" placeholder="Contact Number" required>
        <select name="symptoms">
            <option value="">Cause of Emergency</option>
            <option value="Accident">Accident</option>
            <option value="Heart Attack">Heart Attack</option>
            <option value="Fever">Fever</option>
            <option value="Stroke">Stroke</option>
            <option value="Other">Other</option>
        </select>
        <input type="submit" value="Submit">
    </form>
</div>

<div class="logo-container">
    <img src="../images/hosplogo.png" alt="Chart Memorial Hospital">
</div>

</body>
</html>