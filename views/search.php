<?php
session_start();
require_once __DIR__ . '/../config/db.php';

$roleLayouts = [
    'Admin' => ['admin_header.php', 'admin_sidebar.php'],
    'Doctor' => ['doctor_header.php', 'doctor_sidebar.php'],
    'Nurse' => ['nurse_header.php', 'nurse_sidebar.php'],
    'Pharmacist' => ['pharmacist_header.php', 'pharmacist_sidebar.php'],
    'Cashier' => ['cashier_header.php', 'cashier_sidebar.php'],
];
$role = $_SESSION['role'] ?? '';

if (!isset($_SESSION['UserID']) || !isset($roleLayouts[$role])) {
    header('Location: ' . APP_BASE_URL . '/auth/role_selection.php');
    exit();
}

$query = trim($_GET['query'] ?? '');
$query = mb_substr($query, 0, 100);
$patients = [];
$users = [];

if ($query !== '') {
    $like = '%' . $query . '%';
    $patientStmt = $conn->prepare(
        'SELECT PatientID, Name, DateOfBirth, Sex, PatientType
         FROM patients
         WHERE Name LIKE ? OR CAST(PatientID AS CHAR) = ?
         ORDER BY Name
         LIMIT 50'
    );
    $patientStmt->bind_param('ss', $like, $query);
    $patientStmt->execute();
    $patients = $patientStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $patientStmt->close();

    if ($role === 'Admin') {
        $userStmt = $conn->prepare(
            'SELECT UserID, username, full_name, email, role
             FROM users
             WHERE full_name LIKE ? OR username LIKE ? OR email LIKE ?
                OR CAST(UserID AS CHAR) = ?
             ORDER BY full_name
             LIMIT 50'
        );
        $userStmt->bind_param('ssss', $like, $like, $like, $query);
        $userStmt->execute();
        $users = $userStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $userStmt->close();
    }
}

[$header, $sidebar] = $roleLayouts[$role];
include __DIR__ . '/../includes/' . $header;
include __DIR__ . '/../includes/' . $sidebar;
?>

<main class="content">
    <h1>Search</h1>
    <?php if ($query === ''): ?>
        <p>Use the search field above to find patients<?= $role === 'Admin' ? ' and staff accounts' : '' ?>.</p>
    <?php else: ?>
        <p>Results for <strong><?= htmlspecialchars($query, ENT_QUOTES, 'UTF-8') ?></strong></p>

        <?php if ($role === 'Admin'): ?>
            <h2>Staff accounts</h2>
            <?php if ($users): ?>
                <table>
                    <thead><tr><th>ID</th><th>Name</th><th>Username</th><th>Email</th><th>Role</th></tr></thead>
                    <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= (int) $user['UserID'] ?></td>
                            <td><?= htmlspecialchars($user['full_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($user['username'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($user['email'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($user['role'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No staff accounts matched.</p>
            <?php endif; ?>
        <?php endif; ?>

        <h2>Patients</h2>
        <?php if ($patients): ?>
            <table>
                <thead><tr><th>ID</th><th>Name</th><th>Date of birth</th><th>Sex</th><th>Patient type</th></tr></thead>
                <tbody>
                <?php foreach ($patients as $patient): ?>
                    <tr>
                        <td><?= (int) $patient['PatientID'] ?></td>
                        <td><?= htmlspecialchars($patient['Name'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($patient['DateOfBirth'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($patient['Sex'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($patient['PatientType'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No patients matched.</p>
        <?php endif; ?>
    <?php endif; ?>
</main>
