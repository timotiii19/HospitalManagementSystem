<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'Admin') {
    header("Location: ../../auth/admin_login.php");
    exit();
}

include('../../config/db.php');

if (!isset($_GET['billing_id'])) {
    die('Missing billing ID.');
}

$billing_id = intval($_GET['billing_id']);

// Query billing + patient + doctor + cashier info
$sql = "
    SELECT 
        b.BillingID,
        p.PatientID,
        p.Name AS PatientName,
        d.DoctorID,
        d.DoctorName,
        b.DoctorFee,
        b.MedicineCost,
        b.TotalAmount,
        b.PaymentDate,
        b.Receipt,
        b.PaymentMethod
    FROM patientbilling b
    LEFT JOIN patients p ON b.PatientID = p.PatientID
    LEFT JOIN doctor d ON b.DoctorID = d.DoctorID
    WHERE b.BillingID = $billing_id
    LIMIT 1
";

$result = $conn->query($sql);
if (!$result || $result->num_rows == 0) {
    die('Billing record not found.');
}

$row = $result->fetch_assoc();

// Query medicines used for this patient in this billing (if you track by BillingID, else by PatientID)
$med_sql = "
    SELECT m.MedicineName, pm.QuantityUsed, m.Price
    FROM patientmedication pm
    INNER JOIN pharmacy m ON pm.MedicineID = m.MedicineID
    WHERE pm.PatientID = " . intval($row['PatientID']) . "
";
$med_result = $conn->query($med_sql);

// Require FPDF
require_once __DIR__ . '/../../dompdf/vendor/fpdf186/fpdf.php';

class PDF extends FPDF {
    // Page header
    function Header() {
        // Logo
        $logoPath = __DIR__ . '/../../images/hosplogo-hp.png';
        if (file_exists($logoPath)) {
            // Center logo (page width = 210mm for A4)
            $this->Image($logoPath, 85, 10, 40); // X=85 centers approx, Y=10, width=40
        }
        $this->SetFont('Arial', 'B', 16);
        $this->Ln(20);
        $this->Cell(0, 10, 'Chart Memorial Hospital', 0, 1, 'C');
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 10, 'Billing Report', 0, 1, 'C');
        $this->Ln(5);
    }

    // Page footer
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(0,10,'Page '.$this->PageNo(),0,0,'C');
    }
}

$pdf = new PDF();
$pdf->AddPage();
$pdf->SetFont('Arial','',12);

// Receipt & Cashier info
$pdf->Cell(0, 10, 'Receipt #: ' . $row['Receipt'], 0, 1);
$pdf->Ln(5);

// Billing Details Header Row
$pdf->SetFont('Arial','B',12);
$pdf->SetFillColor(230,230,230);
$pdf->Cell(45, 10, 'Patient', 1, 0, 'C', true);
$pdf->Cell(45, 10, 'Doctor', 1, 0, 'C', true);
$pdf->Cell(35, 10, 'Doctor Fee', 1, 0, 'C', true);
$pdf->Cell(40, 10, 'Payment Method', 1, 0, 'C', true);
$pdf->Cell(25, 10, 'Payment Date', 1, 1, 'C', true);

// Billing Details Data
$pdf->SetFont('Arial','',12);
$pdf->Cell(45, 10, $row['PatientName'], 1);
$pdf->Cell(45, 10, $row['DoctorName'], 1);
$pdf->Cell(35, 10, '$' . number_format($row['DoctorFee'], 2), 1, 0, 'R');
$pdf->Cell(40, 10, $row['PaymentMethod'], 1);
$pdf->Cell(25, 10, date('Y-m-d', strtotime($row['PaymentDate'])), 1);
$pdf->Ln(10);

// Medicines Header Row
$pdf->SetFont('Arial','B',12);
$pdf->Cell(80, 10, 'Medicine Name', 1, 0, 'C', true);
$pdf->Cell(30, 10, 'Quantity Used', 1, 0, 'C', true);
$pdf->Cell(40, 10, 'Price/Unit', 1, 0, 'C', true);
$pdf->Cell(40, 10, 'Subtotal', 1, 1, 'C', true);

$pdf->SetFont('Arial','',12);
$medicineTotal = 0;

if ($med_result && $med_result->num_rows > 0) {
    while ($med = $med_result->fetch_assoc()) {
        $name = $med['MedicineName'];
        $qty = $med['QuantityUsed'];
        $price = $med['Price'];
        $subtotal = $qty * $price;
        $medicineTotal += $subtotal;

        $pdf->Cell(80, 10, $name, 1);
        $pdf->Cell(30, 10, $qty, 1, 0, 'C');
        $pdf->Cell(40, 10, '$' . number_format($price, 2), 1, 0, 'R');
        $pdf->Cell(40, 10, '$' . number_format($subtotal, 2), 1, 1, 'R');
    }
} else {
    $pdf->Cell(190, 10, 'No medicine data found.', 1, 1, 'C');
}

$pdf->Ln(5);
$pdf->SetFont('Arial','B',12);
$pdf->Cell(100, 10, 'Total Medicine Cost: $' . number_format($medicineTotal, 2), 0, 1);
$pdf->Cell(100, 10, 'Overall Amount Paid: $' . number_format($row['TotalAmount'], 2), 0, 1);


$pdf->Ln(10);
$pdf->SetFont('Arial','I',10);
$adminName = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : $_SESSION['username'];
$printDateTime = date('Y-m-d H:i:s');
$pdf->Cell(0, 10, "Printed by: $adminName", 0, 1, 'L');
$pdf->Cell(0, 10, "Printed on: $printDateTime", 0, 1, 'L');

$pdf->Output('D', 'Billing_Report_' . $row['BillingID'] . '_' . date('Ymd') . '.pdf');
exit();

