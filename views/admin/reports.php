<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'Admin') {
    header("Location: ../../auth/admin_login.php");
    exit();
}

include('../../config/db.php');

if (isset($_GET['download']) && $_GET['download'] === 'pdf' && isset($_GET['billing_id'])) {
    require_once __DIR__ . '/../../dompdf/vendor/fpdf186/fpdf.php';

    $billing_id = intval($_GET['billing_id']);

    $sql = "
        SELECT 
            b.BillingID,
            p.PatientID,
            p.Name AS PatientName,
            d.DoctorID,
            d.DoctorName,
            b.DoctorFee,
            b.TotalAmount,
            b.PaymentDate,
            b.Receipt,
            b.PaymentMethod,
            b.CashierID,
            u.full_name AS CashierName
        FROM patientbilling b
        LEFT JOIN patients p ON b.PatientID = p.PatientID
        LEFT JOIN doctor d ON b.DoctorID = d.DoctorID
        LEFT JOIN users u ON b.CashierID = u.UserID
        WHERE b.BillingID = $billing_id
        LIMIT 1
    ";

    $result = $conn->query($sql);
    if (!$result || $result->num_rows == 0) {
        die('Billing record not found.');
    }

    $row = $result->fetch_assoc();

    $med_sql = "
        SELECT m.MedicineName, pm.QuantityUsed, m.Price
        FROM patientmedication pm
        INNER JOIN pharmacy m ON pm.MedicineID = m.MedicineID
        WHERE pm.PatientID = " . intval($row['PatientID']) . "
    ";
    $med_result = $conn->query($med_sql);

    class PDF extends FPDF {
        function Header() {
            $this->SetFont('Arial','B',16);
            $this->Cell(0,10,'Billing Report',0,1,'C');
            $this->Ln(5);
        }

        function Footer() {
            $this->SetY(-15);
            $this->SetFont('Arial','I',8);
            $this->Cell(0,10,'Page '.$this->PageNo(),0,0,'C');
        }
    }

    $pdf = new PDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial','',10);

    $pdf->SetFont('Arial','B',11);
    $pdf->Cell(100, 10, 'Receipt #: ' . $row['Receipt'], 0, 0);
    $pdf->Cell(90, 10, 'Cashier: ' . $row['CashierName'], 0, 1);
    $pdf->Ln(3);

    $pdf->SetFont('Arial','B',10);
    $pdf->SetFillColor(230,230,230);
    $pdf->Cell(40, 8, 'Patient', 1, 0, 'C', true);
    $pdf->Cell(40, 8, 'Doctor', 1, 0, 'C', true);
    $pdf->Cell(30, 8, 'Doctor Fee', 1, 0, 'C', true);
    $pdf->Cell(40, 8, 'Payment Method', 1, 0, 'C', true);
    $pdf->Cell(40, 8, 'Payment Date', 1, 1, 'C', true);

    $pdf->SetFont('Arial','',10);
    $pdf->Cell(40, 8, $row['PatientName'], 1);
    $pdf->Cell(40, 8, $row['DoctorName'], 1);
    $pdf->Cell(30, 8, '$' . number_format($row['DoctorFee'], 2), 1);
    $pdf->Cell(40, 8, $row['PaymentMethod'], 1);
    $pdf->Cell(40, 8, $row['PaymentDate'], 1);
    $pdf->Ln(12);

    $pdf->SetFont('Arial','B',10);
    $pdf->Cell(70, 8, 'Medicine Name', 1, 0, 'C', true);
    $pdf->Cell(30, 8, 'Quantity Used', 1, 0, 'C', true);
    $pdf->Cell(40, 8, 'Price/Unit', 1, 0, 'C', true);
    $pdf->Cell(40, 8, 'Subtotal', 1, 1, 'C', true);

    $pdf->SetFont('Arial','',10);
    $medicineTotal = 0;

    if ($med_result && $med_result->num_rows > 0) {
        while ($med = $med_result->fetch_assoc()) {
            $name = $med['MedicineName'];
            $qty = $med['QuantityUsed'];
            $price = $med['Price'];
            $subtotal = $qty * $price;
            $medicineTotal += $subtotal;

            $pdf->Cell(70, 8, $name, 1);
            $pdf->Cell(30, 8, $qty, 1);
            $pdf->Cell(40, 8, '$' . number_format($price, 2), 1);
            $pdf->Cell(40, 8, '$' . number_format($subtotal, 2), 1);
            $pdf->Ln();
        }
    } else {
        $pdf->Cell(180, 8, 'No medicine data found.', 1, 1);
    }

    $pdf->Ln(3);
    $pdf->SetFont('Arial','B',10);
    $pdf->Cell(100, 8, 'Total Medicine Cost: $' . number_format($medicineTotal, 2), 0, 1);
    $pdf->Cell(100, 8, 'Overall Amount Paid: $' . number_format($row['TotalAmount'], 2), 0, 1);

    $pdf->Output('D', 'Billing_Report_' . $row['BillingID'] . '_' . date('Ymd') . '.pdf');
    exit();
}

// No output before here
include('../../includes/admin_header.php');
include('../../includes/admin_sidebar.php');

$sql = "
SELECT 
    b.BillingID,
    b.PatientID,
    p.Name AS PatientName,
    b.DoctorID,
    d.DoctorName,
    b.DoctorFee,
    b.MedicineCost,
    b.TotalAmount,
    b.PaymentDate,
    b.Receipt,
    b.PaymentMethod,
    b.CreatedAt,
    b.CashierID,
    u.full_name AS CashierName
FROM patientbilling b
LEFT JOIN patients p ON b.PatientID = p.PatientID
LEFT JOIN doctor d ON b.DoctorID = d.DoctorID
LEFT JOIN users u ON b.CashierID = u.UserID
ORDER BY b.BillingID DESC
";

$result = $conn->query($sql);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Billing Management & Reports</title>
    <link rel="stylesheet" href="../../css/style.css" />
    <style>
         body {
            font-family: Arial, sans-serif;
            background-color: #ffffff;
        }
        .content {
            padding: 40px;
        }
        h2 {
            margin-bottom: 10px;
        }
        table {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 12px;
            text-align: left;
            vertical-align: top;
        }
        th {
            background-color: #f2f2f2;
        }
        .btn-download {
            display: inline-block;          /* Make it inline-block so padding and width apply nicely */
            padding: 6px 12px;
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 4px;
            text-decoration: none;
            font-size: 14px;
            white-space: nowrap;            /* Prevent the text from wrapping */
            text-align: center;
            vertical-align: middle;
            line-height: normal;            /* Reset line height */
            width: auto;                    /* Let button width adjust to content */
        }

        td > .btn-download {
            width: 100%;                   /* Make button fill the table cell width */
            box-sizing: border-box;        /* Include padding in width */
        }

        .btn-download:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>

<div class="content">
    <h2>Billing Management & Reports</h2>

    <table>
        <thead>
            <tr>
                <th>BillingID</th>
                <th>Patient</th>
                <th>Doctor</th>
                <th>Doctor Fee</th>
                <th>Medicine Cost</th>
                <th>Total Amount</th>
                <th>Payment Date</th>
                <th>Receipt</th>
                <th>Payment Method</th>
                <th>Created At</th>
             <!--   <th>Cashier</th> -->
                <th>Download PDF</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['BillingID']) ?></td>
                        <td><?= htmlspecialchars($row['PatientName']) ?></td>
                        <td><?= htmlspecialchars($row['DoctorName']) ?></td>
                        <td><?= number_format($row['DoctorFee'], 2) ?></td>
                        <td><?= nl2br(htmlspecialchars($row['MedicineCost'])) ?></td>
                        <td><?= number_format($row['TotalAmount'], 2) ?></td>
                        <td><?= htmlspecialchars($row['PaymentDate']) ?></td>
                        <td><?= htmlspecialchars($row['Receipt']) ?></td>
                        <td><?= htmlspecialchars($row['PaymentMethod']) ?></td>
                        <td><?= htmlspecialchars($row['CreatedAt']) ?></td>
                <!--    <td><?= htmlspecialchars($row['CashierName']) ?></td> -->
                        <td>
                           <a href="export_billing_pdf.php?billing_id=<?= $row['BillingID'] ?>" target="_blank" class="btn-download">Download PDF</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="12" style="text-align:center;">No billing records found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>
