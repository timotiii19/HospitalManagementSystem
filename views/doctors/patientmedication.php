<?php
session_start();

include('../../includes/doctor_header.php');
include('../../includes/doctor_sidebar.php');
include('../../config/db.php');


$medications = [];

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch medications
$doctor_id = $_SESSION['doctor_id'];
$query = "SELECT * FROM patientmedication WHERE DoctorID = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$medications_result = $stmt->get_result();

while ($row = $medications_result->fetch_assoc()) {
    $medications[] = (object) $row;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Patient Medications</title>
    <link rel="stylesheet" href="/css/bootstrap.min.css">
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <style>
    .content {
        margin-top: 60px;  /* space below the header */
        margin-left: 250px; /* space beside the sidebar */
        padding: 20px;
        background-color: #ffffff;
        min-height: calc(100vh - 60px);
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
        border-radius: 8px;
    }

    .content h1 {
        font-size: 24px;
        margin-bottom: 20px;
    }

    .content form {
        background: #f1f1f1;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 20px;
    }

    .content table {
        width: 100%;
        border-collapse: collapse;
        background: #fff;
        border-radius: 8px;
        overflow: hidden;
    }

    .content table th,
    .content table td {
        padding: 10px;
        border: 1px solid #ddd;
    }

    .content table th {
        background-color: #eb6d9b;
        color: white;
    }

    .content table tr:nth-child(even) {
        background-color: #f9f9f9;
    }
    </style>
</head>
<body>
<div class="content">
    <h2 class="mb-4 text-center">Patient Medications Management</h2>

    <!-- Add Medication Form -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <i class="fas fa-plus-circle"></i> Add New Medication
        </div>
        <div class="card-body">
            <form method="POST" action="patientmedication.php">
                <div class="row g-2">
                    <div class="col-md-2">
                        <input type="number" name="PatientID" class="form-control" placeholder="Patient ID" required>
                    </div>
                    <div class="col-md-2">
                        <input type="number" name="MedicineID" class="form-control" placeholder="Medicine ID" required>
                    </div>
                    <div class="col-md-2">
                        <input type="number" name="DoctorID" class="form-control" value="<?= $_SESSION['doctor_id'] ?>" readonly>
                    </div>
                    <div class="col-md-2">
                        <input type="text" name="Dosage" class="form-control" placeholder="Dosage" required>
                    </div>
                    <div class="col-md-2">
                        <input type="text" name="Frequency" class="form-control" placeholder="Frequency" required>
                    </div>
                    <div class="col-md-2">
                        <input type="date" name="StartDate" class="form-control" required>
                    </div>
                    <div class="col-md-2 mt-2">
                        <input type="date" name="EndDate" class="form-control" required>
                    </div>
                    <div class="col-md-2 mt-2">
                        <button class="btn btn-success w-100" type="submit">
                            <i class="fas fa-plus"></i> Add
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Medication List Table -->
    <div class="card">
        <div class="card-header bg-secondary text-white">
            <i class="fas fa-pills"></i> Medication Records
        </div>
        <div class="card-body">
            <?php if (!empty($medications)): ?>
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Patient ID</th>
                            <th>Medicine ID</th>
                            <th>Doctor ID</th>
                            <th>Dosage</th>
                            <th>Frequency</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($medications as $m): ?>
                            <tr>
                                <td><?= $m->PatientMedicationID ?></td>
                                <td><?= $m->PatientID ?></td>
                                <td><?= $m->MedicineID ?></td>
                                <td><?= $m->DoctorID ?></td>
                                <td><?= $m->Dosage ?></td>
                                <td><?= $m->Frequency ?></td>
                                <td><?= $m->StartDate ?></td>
                                <td><?= $m->EndDate ?></td>
                                <td>
                                    <a href="/patientmedication/edit/<?= $m->PatientMedicationID ?>" class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="/patientmedication/delete/<?= $m->PatientMedicationID ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this medication?');">
                                        <i class="fas fa-trash-alt"></i> Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
                <div class="alert alert-info">
                    No medications found for this doctor.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Optional: Edit Medication Form -->
    <?php if (isset($editMedication)): ?>
    <div class="card mt-4">
        <div class="card-header bg-warning text-dark">
            <i class="fas fa-edit"></i> Edit Medication
        </div>
        <div class="card-body">
            <form method="POST" action="/patientmedication/update/<?= $editMedication->PatientMedicationID ?>">
                <div class="row g-2">
                    <div class="col-md-2">
                        <input type="number" name="PatientID" class="form-control" value="<?= $editMedication->PatientID ?>">
                    </div>
                    <div class="col-md-2">
                        <input type="number" name="MedicineID" class="form-control" value="<?= $editMedication->MedicineID ?>">
                    </div>
                    <div class="col-md-2">
                        <input type="number" name="DoctorID" class="form-control" value="<?= $editMedication->DoctorID ?>">
                    </div>
                    <div class="col-md-2">
                        <input type="text" name="Dosage" class="form-control" value="<?= $editMedication->Dosage ?>">
                    </div>
                    <div class="col-md-2">
                        <input type="text" name="Frequency" class="form-control" value="<?= $editMedication->Frequency ?>">
                    </div>
                    <div class="col-md-2">
                        <input type="date" name="StartDate" class="form-control" value="<?= $editMedication->StartDate ?>">
                    </div>
                    <div class="col-md-2 mt-2">
                        <input type="date" name="EndDate" class="form-control" value="<?= $editMedication->EndDate ?>">
                    </div>
                    <div class="col-md-2 mt-2">
                        <button class="btn btn-success w-100" type="submit">
                            <i class="fas fa-save"></i> Update
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>