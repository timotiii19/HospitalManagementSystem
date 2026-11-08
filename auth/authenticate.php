<?php

function authenticateStaffUser(mysqli $conn, string $email, string $password, ?string $requiredRole = null): ?array
{
    $email = trim($email);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return null;
    }

    if ($requiredRole === null) {
        $stmt = $conn->prepare(
            'SELECT UserID, username, full_name, email, role, password FROM users WHERE email = ? LIMIT 1'
        );
        $stmt->bind_param('s', $email);
    } else {
        $stmt = $conn->prepare(
            'SELECT UserID, username, full_name, email, role, password FROM users WHERE email = ? AND role = ? LIMIT 1'
        );
        $stmt->bind_param('ss', $email, $requiredRole);
    }

    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user) {
        return null;
    }

    if (!is_string($user['password']) || $user['password'] === '') {
        return null;
    }

    $passwordInfo = password_get_info($user['password']);
    $isHashedPassword = $passwordInfo['algo'] !== null;
    $passwordMatches = $isHashedPassword
        ? password_verify($password, $user['password'])
        : hash_equals($user['password'], $password);

    if (!$passwordMatches) {
        return null;
    }

    $role = strtolower($user['role']);
    $roleTables = [
        'admin' => ['admin', 'AdminID', '../views/admin/dashboard.php'],
        'doctor' => ['doctor', 'DoctorID', '../views/doctors/dashboard.php'],
        'nurse' => ['nurse', 'NurseID', '../views/nurse/dashboard.php'],
        'pharmacist' => ['pharmacist', 'PharmacistID', '../views/pharmacist/dashboard.php'],
        'cashier' => ['cashier', 'CashierID', '../views/cashier/dashboard.php'],
    ];

    if (!isset($roleTables[$role])) {
        return null;
    }

    [$table, $idColumn, $redirect] = $roleTables[$role];
    $roleStmt = $conn->prepare("SELECT `$idColumn` AS role_id FROM `$table` WHERE UserID = ? LIMIT 1");
    $roleStmt->bind_param('i', $user['UserID']);
    $roleStmt->execute();
    $roleData = $roleStmt->get_result()->fetch_assoc();
    $roleStmt->close();

    if (!$roleData) {
        return null;
    }

    if (!$isHashedPassword) {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $upgrade = $conn->prepare('UPDATE users SET password = ? WHERE UserID = ?');
        $upgrade->bind_param('si', $passwordHash, $user['UserID']);
        $upgrade->execute();
        $upgrade->close();
    }

    session_regenerate_id(true);
    $_SESSION['UserID'] = (int) $user['UserID'];
    $_SESSION['user_id'] = (int) $user['UserID'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['role_id'] = (int) $roleData['role_id'];

    return ['redirect' => $redirect];
}
