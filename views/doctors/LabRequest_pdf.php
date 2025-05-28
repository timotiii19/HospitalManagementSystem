<?php
session_start(); // Start session to access session variables

date_default_timezone_set('Asia/Manila');


require '../../dompdf/autoload.inc.php';
use Dompdf\Dompdf;

include('../../config/db.php');

// Ensure the user is a logged-in doctor
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Doctor') {
    die("Access denied.");
}

if (!isset($_GET['id'])) {
    die("Missing ID");
}

$id = intval($_GET['id']);
$query = "
    SELECT lp.*, p.Name AS PatientName, d.DoctorName
    FROM labprocedure lp
    JOIN patients p ON lp.PatientID = p.PatientID
    JOIN doctor d ON lp.DoctorID = d.DoctorID
    WHERE LabReqID = $id
";
$result = mysqli_query($conn, $query);
$data = mysqli_fetch_assoc($result);

if (!$data) {
    die("No data found.");
}

$notes = $data['Notes'] ?? "";

// Doctor's name from session
$doctorName = $_SESSION['full_name'] ?? $_SESSION['username'];
$printDateTime = date("F j, Y - g:i A");

$dompdf = new Dompdf();

// Convert logo to base64
$hospital_logo = __DIR__ . '/../../images/hosplogo-hp.png';
$imgData = base64_encode(file_get_contents($hospital_logo));
$src = 'data: '.mime_content_type($hospital_logo).';base64,'.$imgData;

// HTML content
$html = '
<style>
    body { font-family: Arial, sans-serif; font-size: 14px; }
    h1 { text-align: center; color: #b22222; }
    .section { margin-bottom: 20px; }
    .label { font-weight: bold; color: #333; }
    .box { border: 1px solid #999; padding: 12px; border-radius: 6px; background: #f9f9f9; }
    .header { text-align: center; margin-bottom: 20px; }
    .logo { text-align: center; margin-bottom: 10px; }
    .signature-box { margin-top: 50px; }
    .signature-line { border-top: 1px solid #000; width: 250px; margin-top: 40px; }
</style>

<div class="logo">
    <img src="' . $src . '" alt="Hospital Logo" height="60">
</div>
<div class="header">
    <h1>Lab Request Form</h1>
    <p><strong>Chart Memorial Hospital</strong></p>
</div>

<div class="section">
    <div class="box">
        <p><span class="label">Request ID:</span> ' . $data['LabReqID'] . '</p>
        <p><span class="label">Patient Name:</span> ' . htmlspecialchars($data['PatientName']) . '</p>
        <p><span class="label">Doctor:</span> Dr. ' . htmlspecialchars($data['DoctorName']) . '</p>
        <p><span class="label">Procedure:</span> ' . htmlspecialchars($data['ProcedureName']) . '</p>
        <p><span class="label">Test Date:</span> ' . date("F j, Y - g:i A", strtotime($data['TestDate'])) . '</p>
        <p><span class="label">Notes:</span><br>' . nl2br(htmlspecialchars($notes)) . '</p>
    </div>
</div>

<div class="section signature-box">
    <p class="label">Doctor\'s Signature:</p>
    <div class="signature-line"></div>
    <p>Date: _________________________</p>
</div>

<div class="section">
    <p><em>This document is a formal lab test request submitted by a licensed medical professional. Please perform the procedure in accordance with hospital protocols.</em></p>
    <p style="text-align:right;">Request submitted on: <strong>' . date("F j, Y - g:i A", strtotime($data['CreatedAt'])) . '</strong></p>
</div>

<div class="section">
    <p><strong>Printed by:</strong> Dr. ' . htmlspecialchars($doctorName) . '</p>
    <p><strong>Printed on:</strong> ' . $printDateTime . '</p>
</div>
';

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("LabRequest_{$data['LabReqID']}.pdf", array("Attachment" => 0));
exit();
