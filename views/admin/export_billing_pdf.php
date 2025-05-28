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

// Default values
$isInpatient = false;
$roomFee = 0;
$daysStayed = 0;
$roomTotal = 0;

$inpatient_sql = "
    SELECT i.AdmissionDate, i.DischargeDate, l.RoomFee
    FROM inpatients i
    INNER JOIN locations l ON i.LocationID = l.LocationID
    WHERE i.PatientID = {$row['PatientID']}
    LIMIT 1
";

$inpatient_result = $conn->query($inpatient_sql);
if ($inpatient_result && $inpatient_result->num_rows > 0) {
    $in_row = $inpatient_result->fetch_assoc();
    $isInpatient = true;
    
    $admissionDate = new DateTime($in_row['AdmissionDate']);
    $dischargeDate = new DateTime($in_row['DischargeDate']);
    $interval = $admissionDate->diff($dischargeDate);
    $daysStayed = max(1, $interval->days); // At least 1 day
    $roomFee = $in_row['RoomFee'];
    $roomTotal = $daysStayed * $roomFee;
}


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
        // Watermark
        $this->SetFont('Arial','B',60);
        $this->SetTextColor(230, 230, 230); // Light gray
        $this->Rotate(45, 70, 190); // Rotate text
        $this->Text(-50, 300, 'CHART MEMORIAL');
        $this->Text(-10, 330, 'HOSPITAL');
        $this->Rotate(0); // Reset rotation

        // Logo and Title
        $this->SetTextColor(0); // Reset text color
        $logoPath = __DIR__ . '/../../images/hosplogo-hp.png';
        if (file_exists($logoPath)) {
            $this->Image($logoPath, 55, 10, 100); // Centered logo
        }
        $this->Ln(24);
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 10, 'Billing Report', 0, 1, 'C');
    }

    // Page footer
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(0,10,'Page '.$this->PageNo(),0,0,'C');
    }

    // Rotation helper
    protected $angle = 0;
    function Rotate($angle, $x = -1, $y = -1) {
        if ($x == -1)
            $x = $this->x;
        if ($y == -1)
            $y = $this->y;
        if ($this->angle != 0)
            $this->_out('Q');
        $this->angle = $angle;
        if ($angle != 0) {
            $angle *= M_PI / 180;
            $c = cos($angle);
            $s = sin($angle);
            $cx = $x * $this->k;
            $cy = ($this->h - $y) * $this->k;
            $this->_out(sprintf('q %.5F %.5F %.5F %.5F %.2F %.2F cm', 
                $c, $s, -$s, $c, $cx, $cy));
        }
    }
    function _endpage() {
        if ($this->angle != 0) {
            $this->angle = 0;
            $this->_out('Q');
        }
        parent::_endpage();
    }
}

$pdf = new PDF();
$pdf->AddPage();
$pdf->SetFont('Arial','',8);

// Receipt & Cashier info
$pdf->Cell(100, 8, 'Patient Type: ' . ($isInpatient ? 'Inpatient' : 'Outpatient'), 0, 0, 'L');
$pdf->Cell(90, 8, 'Receipt #: ' . $row['Receipt'], 0, 1, 'R');
$pdf->Ln(2);


// Billing Details Header Row
$pdf->SetFont('Arial','B', 9);
$pdf->SetFillColor(230,230,230);
$pdf->Cell(45, 10, 'Patient', 1, 0, 'C', true);
$pdf->Cell(45, 10, 'Doctor', 1, 0, 'C', true);
$pdf->Cell(35, 10, 'Doctor Fee', 1, 0, 'C', true);
$pdf->Cell(40, 10, 'Payment Method', 1, 0, 'C', true);
$pdf->Cell(25, 10, 'Payment Date', 1, 1, 'C', true);

// Billing Details Data
$pdf->SetFont('Arial','',12);
$pdf->Cell(45, 10, $row['PatientName'], 1, 0, 'C');
$pdf->Cell(45, 10, $row['DoctorName'], 1, 0, 'C');
$pdf->Cell(35, 10, '$' . number_format($row['DoctorFee'], 2), 1, 0, 'C');
$pdf->Cell(40, 10, $row['PaymentMethod'], 1, 0, 'C');
$pdf->Cell(25, 10, date('Y-m-d', strtotime($row['PaymentDate'])), 1, 0, 'C');
$pdf->Ln(15);

// Medicines Header Row
$pdf->SetFont('Arial','B', 9);
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

$grandTotal = $row['TotalAmount'] + $roomTotal;

$pdf->Ln(3);
$pdf->SetFont('Arial','B',10);

// Left column: Room Details
$startY = $pdf->GetY(); // Remember starting Y position

if ($isInpatient) {
    $pdf->Cell(100, 8, 'Room Fee per Day: $' . number_format($roomFee, 2), 0, 0);
    $pdf->SetXY(110, $startY);
    $pdf->Cell(90, 8, 'Total Medicine Cost: $' . number_format($medicineTotal, 2), 0, 1, 'R');

    $pdf->Cell(100, 8, 'Days Stayed: ' . $daysStayed, 0, 0);
    $pdf->SetX(110);
    $pdf->Cell(90, 8, 'Doctor Fee: $' . number_format($row['DoctorFee'], 2), 0, 1, 'R');

    $pdf->Cell(100, 8, 'Total Room Fee: $' . number_format($roomTotal, 2), 0, 1); // now ends line fully

    // Add spacing between room and totals
    $pdf->Ln(4); // small vertical gap

    // Optional: Horizontal divider line
    $currentY = $pdf->GetY();
    $pdf->SetDrawColor(180, 180, 180);
    $pdf->Line(10, $currentY, 200, $currentY);
    $pdf->Ln(4);
} else {
    // If not inpatient, just align billing totals to right
    $pdf->SetX(110);
    $pdf->Cell(90, 8, 'Total Medicine Cost: $' . number_format($medicineTotal, 2), 0, 1, 'R');
    $pdf->SetX(110);
    $pdf->Cell(90, 8, 'Doctor Fee: $' . number_format($row['DoctorFee'], 2), 0, 1, 'R');

    $pdf->Ln(4);
    $currentY = $pdf->GetY();
    $pdf->SetDrawColor(180, 180, 180);
    $pdf->Line(10, $currentY, 200, $currentY);
    $pdf->Ln(4);
}

// Billing Totals
$pdf->SetX(110);
$pdf->Cell(90, 8, 'Overall Amount Paid: $' . number_format($row['TotalAmount'], 2), 0, 1, 'R');
$pdf->SetX(110);
$pdf->Cell(90, 8, 'Grand Total (inc. room): $' . number_format($grandTotal, 2), 0, 1, 'R');

$adminName = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : $_SESSION['username'];
$printDateTime = date('Y-m-d H:i:s');

// Move the cursor near the bottom of the page
$pdf->SetY(-50); // 30mm from the bottom

$pdf->SetFont('Arial', 'I', 10);
$pdf->Cell(0, 8, "Printed by: $adminName", 0, 1, 'R');
$pdf->Cell(0, 8, "Printed on: $printDateTime", 0, 1, 'R');

$pdf->Output('D', 'Billing_Report_' . $row['BillingID'] . '_' . date('Ymd') . '.pdf');
exit();
