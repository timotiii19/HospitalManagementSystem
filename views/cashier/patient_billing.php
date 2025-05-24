<?php
ob_start();
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'Cashier') {
    header("Location: ../../auth/cashier_login.php");
    exit();
}
include('../../includes/cashier_header.php');
include('../../includes/cashier_sidebar.php');
include('../../config/db.php');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch Medicine
$medicines_result = $conn->query("SELECT MedicineID, MedicineName, Price, StockQuantity FROM Pharmacy");

// Fetch Doctor
$doctors_result = $conn->query("
    SELECT d.DoctorID, d.DoctorName, d.DoctorFee, dept.DepartmentName 
    FROM doctor d
    LEFT JOIN department dept ON d.DepartmentID = dept.DepartmentID
");

// Fetch patients
$patients_result = $conn->query("SELECT PatientID, Name FROM patients");

// Generate receipt number
$result = $conn->query("SELECT MAX(CAST(Receipt AS UNSIGNED)) AS last_receipt FROM patientbilling");
$row = $result->fetch_assoc();
$last_receipt = $row['last_receipt'] ?? 0;
$new_receipt_number = str_pad($last_receipt + 1, 6, '0', STR_PAD_LEFT);

// Add bill
if (isset($_POST['add_bill'])) {
    $conn->begin_transaction();
    
    try {
        $patient_parts = explode(' - ', $_POST['patient_id']);
        $patient_id = (int)$patient_parts[0];
        $doctor_parts = explode(' - ', $_POST['doctor_id']);
        $doctor_id = (int)$doctor_parts[0];
        $doctor_fee = (float)$_POST['doctor_fee'];
        $medicine_total = (float)$_POST['medicine_total'];
        $total = $doctor_fee + $medicine_total;
        $payment_date = $_POST['payment_date'];
        $receipt = $_POST['receipt'];

        // Insert billing record
        $stmt = $conn->prepare("INSERT INTO patientbilling (PatientID, DoctorID, DoctorFee, MedicineCost, TotalAmount, PaymentDate, Receipt) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iidddss", $patient_id, $doctor_id, $doctor_fee, $medicine_total, $total, $payment_date, $receipt);
        $stmt->execute();

        // Handle medicine stock updates and record in patientmedication
        if (!empty($_POST['medicine_ids']) && !empty($_POST['medicine_quantities'])) {
            $medicine_ids = is_array($_POST['medicine_ids']) ? 
                $_POST['medicine_ids'] : 
                explode(',', $_POST['medicine_ids']);
            
            $quantities = is_array($_POST['medicine_quantities']) ? 
                $_POST['medicine_quantities'] : 
                explode(',', $_POST['medicine_quantities']);

            // Verify counts match
            if (count($medicine_ids) !== count($quantities)) {
                throw new Exception("Medicine IDs and quantities count mismatch");
            }

            for ($i = 0; $i < count($medicine_ids); $i++) {
                $med_id = (int)$medicine_ids[$i];
                $qty = (int)$quantities[$i];
                
                if ($med_id <= 0 || $qty <= 0) continue;
                
                // Update stock
                $update_stmt = $conn->prepare("UPDATE Pharmacy SET StockQuantity = StockQuantity - ? WHERE MedicineID = ?");
                $update_stmt->bind_param("ii", $qty, $med_id);
                $update_stmt->execute();
                
                // Record in patientmedication (using PatientID only)
                $med_stmt = $conn->prepare("INSERT INTO patientmedication (PatientID, MedicineID, QuantityUsed) VALUES (?, ?, ?)");
                $med_stmt->bind_param("iii", $patient_id, $med_id, $qty);
                $med_stmt->execute();
            }
        }

        $conn->commit();
        $_SESSION['success_message'] = "Bill added successfully!";
        header("Location: patient_billing.php?patient_id=".$patient_id);
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
        header("Location: patient_billing.php");
        exit();
    }
}

// Delete bill
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->begin_transaction();
    
    try {
        // First get the patient ID
        $stmt = $conn->prepare("SELECT PatientID FROM patientbilling WHERE BillingID = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $patientID = $row['PatientID'];

            // Delete from patientbilling first
            $del_bill_stmt = $conn->prepare("DELETE FROM patientbilling WHERE BillingID = ?");
            $del_bill_stmt->bind_param("i", $id);
            $del_bill_stmt->execute();

            $conn->commit();
            header("Location: patient_billing.php?patient_id=$patientID");
            exit();
        } else {
            throw new Exception("Billing entry not found.");
        }
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
        header("Location: patient_billing.php");
        exit();
    }
}

// Fetch bills with department info (simple query without medicine details)
$bills_result = $conn->query("
    SELECT 
        b.*, 
        p.PatientID, 
        p.Name AS PatientName, 
        d.DoctorID, 
        d.DoctorName, 
        dept.DepartmentName
    FROM patientbilling b
    JOIN patients p ON b.PatientID = p.PatientID
    JOIN doctor d ON b.DoctorID = d.DoctorID
    LEFT JOIN department dept ON d.DepartmentID = dept.DepartmentID
    ORDER BY b.PaymentDate DESC
");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Billing Management</title>
    <style>
    /* Main Content Styles */
    .content {
        padding: 40px;
        margin-left: 210px; /* space for sidebar */
        margin-top: 10px;
        background-color: #f8f9fa;
        min-height: calc(100vh - 60px);
    }

    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        padding-bottom: 15px;
        border-bottom: 1px solid #e0e0e0;
    }

    .page-title {
        color: #6f42c1;
        font-size: 24px;
        font-weight: 600;
    }

    /* Form Styles */
    .billing-form {
        background-color: white;
        padding: 25px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        margin-bottom: 30px;
        width: 1195px;
    }

    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
    }

    .form-group {
        margin-bottom: 15px;
        margin-left:
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 500;
        color: #555;
        font-size: 17px;
    }

    .form-control {
        width: 100%;
        padding: 10px 0px;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 14px;
        transition: border-color 0.3s;
    }

    .form-control:focus {
        border-color: #6f42c1;
        outline: none;
        box-shadow: 0 0 0 3px rgba(111, 66, 193, 0.1);
    }

    .form-control[readonly] {
        background-color: #f5f5f5;
    }

    /* Medicine Section */
    .medicine-section {
        grid-column: 1 / -1;
        background-color: #f9f9f9;
        padding: 15px;
        border-radius: 6px;
        margin-top: 10px;
    }

    .medicine-search-row {
        display: flex;
        gap: 10px;
    }

    .medicine-search-row .form-group {
        flex: 1;
    }

    .stock-info {
        font-size: 14px;
        color: #666;
        margin-top: 5px;
    }

    .btn-add {
        background-color: #28a745;
        color: white;
        margin-top: 24px;
        border: none;
        height: 40px;
        padding: 10px 25px;
        border-radius: 8px;
        cursor: pointer;
        font-size: 14px;
        transition: background-color 0.3s;
    }

    .btn-add:hover {
        background-color: #218838;
    }

    #selectedMedicines {
        list-style: none;
        padding: 0;
        margin-top: 15px;
    }

    #selectedMedicines li {
        background-color: white;
        border: 1px solid #eee;
        padding: 10px;
        margin-bottom: 8px;
        border-radius: 5px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .medicine-item {
        flex: 1;
    }

    .remove-medicine {
        background-color: #dc3545;
        color: white;
        border: none;
        width: 25px;
        height: 25px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        margin-left: 10px;
    }

    /* Button Styles */
    .btn-calculate {
        background-color: #17a2b8;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 14px;
        margin-top: 15px;
        transition: background-color 0.3s;
    }

    .btn-calculate:hover {
        background-color: #138496;
    }

    .btn-submit {
        background-color: #eb6d9b;
        color: white;
        border: none;
        padding: 10px 25px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 16px;
        margin-top: 20px;
        transition: background-color 0.3s;
        display: block;
        width: 100%;
    }

    .btn-submit:hover {
        background-color: #d45d8b;
    }

    /* Table Styles */
    .billing-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
        background-color: white;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        border-radius: 8px;
        overflow: hidden;
    }

    .billing-table th {
        background-color: #6f42c1;
        color: white;
        padding: 12px 15px;
        text-align: left;
        font-weight: 500;
    }

    .billing-table td {
        padding: 12px 15px;
        border-bottom: 1px solid #eee;
        color: #555;
    }

    .billing-table tr:last-child td {
        border-bottom: none;
    }

    .billing-table tr:hover td {
        background-color: #f8f5ff;
    }

    .action-buttons {
    margin-top: 10px; /* Space above the buttons */
    display: flex;
    flex-direction: column; /* Stacks them vertically */
    gap: 6px; /* Space between the buttons */
    }

    .btn-receipt {
        background-color: #17a2b8;
        color: white;
        border: none;
        padding: 6px 12px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 14px;
        transition: background-color 0.3s;
        text-decoration: none;
        display: inline-block;
        margin-left: 5px;
    }
    .btn-receipt:hover {
        background-color: #138496;
    }

    .btn-delete {
        background-color: #dc3545;
        color: white;
        border: none;
        padding: 6px 12px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 14px;
        transition: background-color 0.3s;
        text-decoration: none;
        margin-left: 5px;
        text-align: center;
    }

    .btn-delete:hover {
        background-color: #c82333;
    }

    /* Responsive Adjustments */
    @media (max-width: 768px) {
        .content {
            margin-left: 0;
            padding: 20px 15px;
        }
        
        .form-grid {
            grid-template-columns: 1fr;
        }
    }

    /* Currency styling */
    .currency {
        color: #28a745;
        font-weight: 500;
    }
    </style>
</head>
<body>

<script>
// Your existing JavaScript remains unchanged
let selectedMedicinePrices = [];

function setDoctorFee() {
    const doctorInput = document.getElementById("doctorSearch");
    const datalist = document.getElementById("doctorList").options;
    const value = doctorInput.value.trim();

    for (let option of datalist) {
        if (option.value === value) {
            const fee = option.getAttribute("data-fee");
            const dept = option.getAttribute("data-dept");
            
            document.getElementById("doctorFeeDisplay").value = "₱" + parseFloat(fee).toFixed(2);
            document.getElementById("doctorFee").value = parseFloat(fee).toFixed(2);
            
            // Add department display
            document.getElementById("doctorDeptDisplay").value = dept || "N/A";
            return;
        }
    }

    document.getElementById("doctorFeeDisplay").value = "";
    document.getElementById("doctorFee").value = "";
    document.getElementById("doctorDeptDisplay").value = "";
}

function handleEnter(event) {
    if (event.key === 'Enter') {
        event.preventDefault();
        addMedicine();
    }
}

function showStock() {
    const input = document.getElementById("medicineSearch");
    const value = input.value.trim();
    const stockDisplay = document.getElementById("stockDisplay");
    const options = document.getElementById("medicineList").options;
    
    if (!value) {
        stockDisplay.textContent = "Stock: -";
        return;
    }
    
    for (let opt of options) {
        if (opt.value === value) {
            const stock = opt.getAttribute("data-stock");
            stockDisplay.textContent = `Stock: ${stock}`;
            return;
        }
    }
    
    stockDisplay.textContent = "Stock: -";
}

let selectedMedicines = [];

function addMedicine() {
    try {
        const input = document.getElementById("medicineSearch");
        const quantityInput = document.getElementById("medicineQuantity");
        
        if (!input || !quantityInput) {
            console.error("Could not find input elements");
            return;
        }

        const value = input.value.trim();
        const quantity = parseInt(quantityInput.value) || 1;
        
        if (!value) {
            alert("Please select a medicine first");
            return;
        }

        const options = document.getElementById("medicineList").options;
        if (!options || options.length === 0) {
            console.error("No medicine options found");
            return;
        }

        let medicineData = null;
        
        for (let opt of options) {
            if (opt.value === value) {
                medicineData = {
                    id: opt.value.split(' - ')[0],
                    name: opt.value,
                    price: parseFloat(opt.getAttribute("data-price")) || 0,
                    stock: parseInt(opt.getAttribute("data-stock")) || 0
                };
                break;
            }
        }

        if (!medicineData) {
            alert("Medicine not found!");
            return;
        }

        if (medicineData.stock < quantity) {
            alert(`Not enough stock! Only ${medicineData.stock} available.`);
            return;
        }

        const existingIndex = selectedMedicines.findIndex(m => m.id === medicineData.id);
        
        if (existingIndex >= 0) {
            selectedMedicines[existingIndex].quantity += quantity;
        } else {
            selectedMedicines.push({
                id: medicineData.id,
                name: medicineData.name,
                price: medicineData.price,
                quantity: quantity
            });
        }

        updateSelectedMedicinesList();
        input.value = "";
        quantityInput.value = "1";
        
    } catch (error) {
        console.error("Error in addMedicine:", error);
        alert("An error occurred while adding medicine");
    }
}

function updateSelectedMedicinesList() {
    try {
        const list = document.getElementById("selectedMedicines");
        if (!list) {
            console.error("Could not find selected medicines list");
            return;
        }

        list.innerHTML = "";
        
        selectedMedicines.forEach((med, index) => {
            const li = document.createElement("li");
            
            li.innerHTML = `
                <div class="medicine-item">
                    ${med.name} - 
                    Qty: <strong>${med.quantity}</strong>
                    × <span class="currency">₱${med.price.toFixed(2)}</span> = 
                    <span class="currency">₱${(med.price * med.quantity).toFixed(2)}</span>
                </div>
                <button type="button" class="remove-medicine" onclick="removeMedicine(${index})">×</button>
            `;
            list.appendChild(li);
        });
    } catch (error) {
        console.error("Error updating medicines list:", error);
    }
}

function removeMedicine(index) {
    if (index >= 0 && index < selectedMedicines.length) {
        selectedMedicines.splice(index, 1);
        updateSelectedMedicinesList();
    }
}

function calculateTotal() {
    let total = 0;
    const medicineIds = [];
    const quantities = [];
    
    selectedMedicines.forEach(med => {
        total += med.price * med.quantity;
        medicineIds.push(med.id);
        quantities.push(med.quantity);
    });

    document.getElementById("medicineTotalDisplay").value = "₱" + total.toFixed(2);
    document.getElementById("medicineTotal").value = total.toFixed(2);
    
    document.getElementById("hidden_medicine_ids").value = medicineIds.join(',');
    document.getElementById("hidden_medicine_quantities").value = quantities.join(',');
}
</script>


<div class="content">
    <div class="page-header">
        <h1 class="page-title">Billing Management</h1>
    </div>

    <form method="post" action="" class="billing-form">
        <div class="form-grid">
            <!-- Patient Information -->
            <div class="form-group">
                <label for="patient_id_input">Patient Name</label>
                <input list="patientList" class="form-control" name="patient_id" id="patient_id_input" placeholder="Select Patient" required>
                <datalist id="patientList">
                    <?php
                    $patients_result->data_seek(0);
                    while ($p = $patients_result->fetch_assoc()) {
                        echo "<option value='{$p['PatientID']} - " . htmlspecialchars($p['Name']) . "'>";
                    }
                    ?>
                </datalist>
            </div>

            <!-- Doctor Information -->
            <div class="form-group">
                <label for="doctorSearch">Doctor Name</label>
                <input list="doctorList" class="form-control" id="doctorSearch" name="doctor_id" 
                    placeholder="Select Doctor" onchange="setDoctorFee()" required>
            </div>

            <div class="form-group">
                <label>Department</label>
                <input type="text" class="form-control" id="doctorDeptDisplay" readonly>
            </div>

            <div class="form-group">
                <label>Doctor Fee</label>
                <input type="text" class="form-control" id="doctorFeeDisplay" readonly>
                <input type="hidden" name="doctor_fee" id="doctorFee" required>
            </div>

            <datalist id="doctorList">
                <?php
                $doctors_result->data_seek(0);
                while ($d = $doctors_result->fetch_assoc()) {
                    echo "<option value='{$d['DoctorID']} - " . htmlspecialchars($d['DoctorName']) . "' 
                        data-fee='{$d['DoctorFee']}'
                        data-dept='" . htmlspecialchars($d['DepartmentName']) . "'>";
                }
                ?>
            </datalist>

            <!-- Medicine Section -->
            <div class="medicine-section">
                <h3 style="margin-top: 0; margin-bottom: 15px; color: #6f42c1;">Medicines</h3>
                
                <div class="medicine-search-row">
                    <div class="form-group">
                        <label for="medicineSearch">Search Medicine</label>
                        <input list="medicineList" class="form-control" id="medicineSearch" 
                            placeholder="Type to search..." oninput="showStock()" onchange="showStock()">
                        <div id="stockDisplay" class="stock-info">Stock: -</div>
                    </div>
                    
                    <div class="form-group" style="width: 100px;">
                        <label for="medicineQuantity">Quantity</label>
                        <input type="number" class="form-control" id="medicineQuantity" min="1" value="1">
                    </div>
                    
                    <button type="button" class="btn-add" onclick="addMedicine()">Add</button>
                </div>
                
                <ul id="selectedMedicines"></ul>
                
                <button type="button" class="btn-calculate" onclick="calculateTotal()">Calculate Total</button>
                
                <div class="form-group" style="margin-top: 15px;">
                    <label>Medicine Total</label>
                    <input type="text" class="form-control" id="medicineTotalDisplay" readonly>
                    <input type="hidden" name="medicine_total" id="medicineTotal" required>
                </div>
            </div>

            <datalist id="medicineList">
                <?php
                $medicines_result->data_seek(0);
                while ($med = $medicines_result->fetch_assoc()) {
                    echo "<option value='{$med['MedicineID']} - " . htmlspecialchars($med['MedicineName']) . "' 
                        data-price='{$med['Price']}' 
                        data-stock='{$med['StockQuantity']}'></option>";
                }
                ?>
            </datalist>

            <!-- Payment Information -->
            <div class="form-group">
                <label>Payment Date</label>
                <input type="date" class="form-control" name="payment_date" value="<?php echo date('Y-m-d'); ?>" readonly required>
            </div>

            <div class="form-group">
                <label>Receipt Number</label>
                <input type="text" class="form-control" name="receipt" value="<?php echo $new_receipt_number; ?>" readonly>
            </div>

            <input type="hidden" name="medicine_ids" id="hidden_medicine_ids">
            <input type="hidden" name="medicine_quantities" id="hidden_medicine_quantities">
        </div>

        <button type="submit" name="add_bill" class="btn-submit">
            Add Bill
        </button>
    </form>

    <!-- Billing Table -->
    <table class="billing-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Patient</th>
                <th>Doctor</th>
                <th>Department</th>
                <th>Doctor Fee</th>
                <th>Medicine Cost</th>
                <th>Total Amount</th>
                <th>Payment Date</th>
                <th>Receipt</th>
                <th>Action</th>
            </tr>
        </thead>
<tbody>
    <?php 
    $bills_result->data_seek(0); // Reset pointer if needed
    while ($row = $bills_result->fetch_assoc()): 
        // Fetch medicines for this patient
        $med_stmt = $conn->prepare("
            SELECT ph.MedicineName, pm.QuantityUsed, ph.Price 
            FROM patientmedication pm
            JOIN Pharmacy ph ON pm.MedicineID = ph.MedicineID
            WHERE pm.PatientID = ? AND pm.QuantityUsed > 0
        ");
        $med_stmt->bind_param("i", $row['PatientID']);
        $med_stmt->execute();
        $med_result = $med_stmt->get_result();
        $medicines = $med_result->fetch_all(MYSQLI_ASSOC);
    ?>
        <tr>
            <td><?= $row['BillingID'] ?></td>
            <td><?= htmlspecialchars($row['PatientName']) ?></td>
            <td><?= htmlspecialchars($row['DoctorName']) ?></td>
            <td><?= htmlspecialchars($row['DepartmentName'] ?? 'N/A') ?></td>
            <td class="currency">₱<?= number_format($row['DoctorFee'], 2) ?></td>
            <td class="currency">₱<?= number_format($row['MedicineCost'], 2) ?></td>
            <td class="currency">₱<?= number_format($row['TotalAmount'], 2) ?></td>
            <td><?= htmlspecialchars($row['PaymentDate']) ?></td>
            <td><?= htmlspecialchars($row['Receipt']) ?></td>
            <td>
            <div class="action-buttons">
                <a class="btn-delete" href="?delete=<?= $row['BillingID'] ?>" onclick="return confirm('Are you sure you want to delete this bill?')">Delete</a>
                <a class="btn-receipt" href="generate_receipt.php?billing_id=<?= $row['BillingID'] ?>">Receipt</a>
            </div>
            </td>
        </tr>
        <?php if (!empty($medicines)): ?>
        <tr>
                </div>
            </td>
        </tr>
        <?php endif; ?>
    <?php endwhile; ?>
</tbody>
    </table>
</div>

</body>
</html>