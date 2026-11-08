<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit("Not found.\n");
}

if (getenv('HMS_SEED_DEMO') !== '1') {
    fwrite(STDERR, "Seeding is disabled. Set HMS_SEED_DEMO=1 to create local demo accounts.\n");
    exit(1);
}

$dbHost = strtolower(trim(getenv('HMS_DB_HOST') ?: 'localhost'));
if (!in_array($dbHost, ['localhost', '127.0.0.1', '::1'], true)) {
    fwrite(STDERR, "Refusing to seed a non-local database host: {$dbHost}\n");
    exit(1);
}

require_once __DIR__ . '/../config/db.php';

$databaseResult = $conn->query('SELECT DATABASE()');
$databaseName = (string) $databaseResult->fetch_row()[0];
$databaseResult->free();

if (strtolower($databaseName) === 'charle_hms') {
    fwrite(STDERR, "Refusing to seed the known damaged database 'charle_hms'. Select the intended local database first.\n");
    exit(1);
}

$demoPassword = 'HMS-Demo-Only!2026';
$accounts = [
    [
        'username' => 'demo_admin',
        'name' => 'Demo Administrator',
        'email' => 'admin.demo@hms.local',
        'role' => 'Admin',
        'table' => 'admin',
        'profileSql' => 'INSERT INTO admin (UserID) VALUES (?)',
        'profileTypes' => 'i',
    ],
    [
        'username' => 'demo_doctor',
        'name' => 'Demo Doctor',
        'email' => 'doctor.demo@hms.local',
        'role' => 'Doctor',
        'table' => 'doctor',
        'profileSql' => 'INSERT INTO doctor (UserID, DoctorName, Email) VALUES (?, ?, ?)',
        'profileTypes' => 'iss',
    ],
    [
        'username' => 'demo_nurse',
        'name' => 'Demo Nurse',
        'email' => 'nurse.demo@hms.local',
        'role' => 'Nurse',
        'table' => 'nurse',
        'profileSql' => 'INSERT INTO nurse (UserID, Name, Email) VALUES (?, ?, ?)',
        'profileTypes' => 'iss',
    ],
    [
        'username' => 'demo_pharmacist',
        'name' => 'Demo Pharmacist',
        'email' => 'pharmacist.demo@hms.local',
        'role' => 'Pharmacist',
        'table' => 'pharmacist',
        'profileSql' => 'INSERT INTO pharmacist (UserID, Name, Email) VALUES (?, ?, ?)',
        'profileTypes' => 'iss',
    ],
    [
        'username' => 'demo_cashier',
        'name' => 'Demo Cashier',
        'email' => 'cashier.demo@hms.local',
        'role' => 'Cashier',
        'table' => 'cashier',
        'profileSql' => 'INSERT INTO cashier (UserID, Name) VALUES (?, ?)',
        'profileTypes' => 'is',
    ],
];

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$created = [];
$existing = [];

try {
    $conn->begin_transaction();

    foreach ($accounts as $account) {
        $lookup = $conn->prepare(
            'SELECT UserID, username, email, role FROM users WHERE email = ? OR username = ? LIMIT 2'
        );
        $lookup->bind_param('ss', $account['email'], $account['username']);
        $lookup->execute();
        $matches = $lookup->get_result()->fetch_all(MYSQLI_ASSOC);
        $lookup->close();

        if (count($matches) > 1) {
            throw new RuntimeException("Username/email conflict for {$account['username']}.");
        }

        if ($matches) {
            $user = $matches[0];
            if (
                $user['username'] !== $account['username']
                || $user['email'] !== $account['email']
                || $user['role'] !== $account['role']
            ) {
                throw new RuntimeException("Existing account conflicts with demo identity {$account['username']}.");
            }
            $userId = (int) $user['UserID'];
            $existing[] = $account;
        } else {
            $passwordHash = password_hash($demoPassword, PASSWORD_DEFAULT);
            $insertUser = $conn->prepare(
                'INSERT INTO users (username, full_name, email, password, role) VALUES (?, ?, ?, ?, ?)'
            );
            $insertUser->bind_param(
                'sssss',
                $account['username'],
                $account['name'],
                $account['email'],
                $passwordHash,
                $account['role']
            );
            $insertUser->execute();
            $userId = (int) $conn->insert_id;
            $insertUser->close();
            $created[] = $account;
        }

        $profileCheck = $conn->prepare(
            'SELECT UserID FROM `' . $account['table'] . '` WHERE UserID = ? LIMIT 1'
        );
        $profileCheck->bind_param('i', $userId);
        $profileCheck->execute();
        $hasProfile = (bool) $profileCheck->get_result()->fetch_assoc();
        $profileCheck->close();

        if (!$hasProfile) {
            $profile = $conn->prepare($account['profileSql']);
            if ($account['profileTypes'] === 'i') {
                $profile->bind_param('i', $userId);
            } elseif ($account['profileTypes'] === 'is') {
                $profile->bind_param('is', $userId, $account['name']);
            } else {
                $profile->bind_param('iss', $userId, $account['name'], $account['email']);
            }
            $profile->execute();
            $profile->close();
        }
    }

    $conn->commit();
} catch (Throwable $error) {
    $conn->rollback();
    fwrite(STDERR, "Demo seeding failed; changes were rolled back: {$error->getMessage()}\n");
    exit(1);
}

echo "Demo accounts are stored in database '{$databaseName}'.\n";
if ($created) {
    echo "New accounts (password: {$demoPassword}):\n";
    foreach ($created as $account) {
        echo "  {$account['role']}: {$account['email']}\n";
    }
}
if ($existing) {
    echo "Already-existing accounts were left unchanged (their current passwords cannot be reported):\n";
    foreach ($existing as $account) {
        echo "  {$account['role']}: {$account['email']}\n";
    }
}
