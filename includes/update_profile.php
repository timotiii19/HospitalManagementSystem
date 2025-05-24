<?php
session_start();
header('Content-Type: application/json');
include('../config/db.php');

// Check if user is logged in
if (!isset($_SESSION['UserID'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$userId = $_SESSION['UserID'];
$full_name = trim($_POST['full_name'] ?? '');
$username = trim($_POST['username'] ?? ''); // username input name is "username"
$email = trim($_POST['email'] ?? '');       // email input name is "email" (disabled but included for completeness)
$contact = trim($_POST['ContactNumber'] ?? ''); // form input name "contact_number"

// Validate only required fields (full_name and username)
if (!$full_name || !$username) {
    echo json_encode(['success' => false, 'message' => 'Full name and username are required']);
    exit;
}

// Validate username format (alphanumeric + underscores, length 3-20)
if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
    echo json_encode(['success' => false, 'message' => 'Invalid username format']);
    exit;
}

// Check if username is already taken by another user
$stmtCheck = $conn->prepare("SELECT UserID FROM users WHERE username = ? AND UserID != ?");
$stmtCheck->bind_param("si", $username, $userId);
$stmtCheck->execute();
$stmtCheck->store_result();
if ($stmtCheck->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Username is already taken']);
    $stmtCheck->close();
    exit;
}
$stmtCheck->close();

// Update user data in the database (including username, email, ContactNumber)
$stmt = $conn->prepare("UPDATE users SET full_name = ?, username = ?, email = ?, ContactNumber = ? WHERE UserID = ?");
$stmt->bind_param("ssssi", $full_name, $username, $email, $contact, $userId);

if ($stmt->execute()) {
    // Update session values
    $_SESSION['full_name'] = $full_name;
    $_SESSION['username'] = $username;
    $_SESSION['email'] = $email;
    $_SESSION['ContactNumber'] = $contact;

    // Get updated role
    $stmt2 = $conn->prepare("SELECT role FROM users WHERE UserID = ?");
    $stmt2->bind_param("i", $userId);
    $stmt2->execute();
    $result = $stmt2->get_result();
    $role = '';
    if ($row = $result->fetch_assoc()) {
        $role = $row['role'];
        $_SESSION['role'] = $role;
    }
    $stmt2->close();

    echo json_encode([
        'success' => true,
        'full_name' => $full_name,
        'username' => $username,
        'email' => $email,
        'ContactNumber' => $contact,
        'role' => $role
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database update failed']);
}
$stmt->close();
$conn->close();
?>
