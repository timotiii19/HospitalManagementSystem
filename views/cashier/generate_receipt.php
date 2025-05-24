<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'Cashier') {
    header("Location: ../../auth/cashier_login.php");
    exit();
}

include('../../config/db.php');

if (!isset($_GET['billing_id'])) {
    die('Missing billing ID.');
}

$billing_id = intval($_GET['billing_id']);

// Query billing information
$sql = "
    SELECT 
        b.*, 
        p.Name AS PatientName,
        d.DoctorName,
        dept.DepartmentName,
        u.full_name AS CashierName
    FROM patientbilling b
    JOIN patients p ON b.PatientID = p.PatientID
    JOIN doctor d ON b.DoctorID = d.DoctorID
    LEFT JOIN department dept ON d.DepartmentID = dept.DepartmentID
    LEFT JOIN users u ON b.CashierID = u.UserID
    WHERE b.BillingID = $billing_id
    LIMIT 1
";

$result = $conn->query($sql);
if (!$result || $result->num_rows == 0) {
    die('Billing record not found.');
}

$bill = $result->fetch_assoc();

// Query medicines for this patient
$med_sql = "
    SELECT 
        ph.MedicineName, 
        pm.QuantityUsed, 
        ph.Price
    FROM patientmedication pm
    JOIN Pharmacy ph ON pm.MedicineID = ph.MedicineID
    WHERE pm.PatientID = {$bill['PatientID']}
";
$med_result = $conn->query($med_sql);

require_once('../../dompdf/vendor/autoload.php');
use Dompdf\Dompdf;

// Convert the logo image to a data URI
$logoPath = 'C:/xampp/htdocs/HMS-main/images/hosplogo.png';
$logoData = base64_encode(file_get_contents($logoPath));
$logoMime = mime_content_type($logoPath);
$logoURI = 'data:'.$logoMime.';base64,'.$logoData;

// Get current date and time
$currentDate = date('F j, Y h:i A');
// Get cashier name from session
$cashierName = $_SESSION['full_name'] ?? $_SESSION['username'];

$html = '
<!DOCTYPE html>
<html>
<head>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
        }
        .header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        .logo {
            height: 80px;
            margin-right: 20px;
        }
        .header-text {
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .hospital-name {
            font-size: 24px;
            font-weight: bold;
            margin: 0;
        }
        .receipt-title {
            font-size: 18px;
            margin: 5px 0 0 0;
        }
        .details {
            margin-bottom: 20px;
            font-size: 15px;
        }
        .detail-row {
            display: flex;
            margin-bottom: 5px;
            font-size: 13px;
        }
        .detail-label {
            width: 150px;
            font-weight: bold;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .total-row {
            font-weight: bold;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-style: italic;
        }
        .print-info {
            margin-top: 10px;
            font-size: 8px;
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="header">
        <img class="logo" src="'.$logoURI.'" alt="Hospital Logo">
        <div class="header-text">
            <div class="receipt-title">OFFICIAL RECEIPT</div>
        </div>
    </div>
    
    <div class="print-info">
        Printed on: '.$currentDate.'<br>
        Printed by: '.$cashierName.'
    </div>

    <div class="details">
        <div class="detail-row">
            <div class="detail-label">Receipt Number:</div>
            <div>'.$bill['Receipt'].'</div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Date:</div>
            <div>'.date('F j, Y', strtotime($bill['PaymentDate'])).'</div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Patient Name:</div>
            <div>'.$bill['PatientName'].'</div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Doctor:</div>
            <div>'.$bill['DoctorName'].' ('.$bill['DepartmentName'].')</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Medicine</th>
                <th>Quantity</th>
                <th>Unit Price</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>';

$total = 0;
if ($med_result && $med_result->num_rows > 0) {
    while ($med = $med_result->fetch_assoc()) {
        $subtotal = $med['QuantityUsed'] * $med['Price'];
        $total += $subtotal;
        $html .= '
            <tr>
                <td>'.$med['MedicineName'].'</td>
                <td>'.$med['QuantityUsed'].'</td>
                <td>PHP '.number_format($med['Price'], 2).'</td>
                <td>PHP '.number_format($subtotal, 2).'</td>
            </tr>';
    }
}

$html .= '
            <tr class="total-row">
                <td colspan="3">Doctor Fee</td>
                <td>PHP '.number_format($bill['DoctorFee'], 2).'</td>
            </tr>
            <tr class="total-row">
                <td colspan="3">Total Amount</td>
                <td>PHP '.number_format($bill['TotalAmount'], 2).'</td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        Thank you for choosing Chart Memorial Hospital<br>
        This is an official receipt
    </div>
</body>
</html>';

$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A5', 'portrait');

// Enable remote image loading
$dompdf->set_option('isRemoteEnabled', true);

$dompdf->render();

// Output the generated PDF
$dompdf->stream('receipt_'.$bill['Receipt'].'.pdf', [
    'Attachment' => 0,  // 0 to display in browser, 1 to download
    'compress' => 1
]);
