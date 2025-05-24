<?php
require '../../dompdf/autoload.inc.php';
use Dompdf\Dompdf;

include('../../config/db.php');

// Check database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT i.InpatientID, p.Name AS patient_name, p.DateOfBirth, p.Sex, p.Address, i.AdmissionDate, i.DischargeDate, p.Contact
        FROM inpatients i
        JOIN patients p ON i.PatientID = p.PatientID";


$result = $conn->query($sql);

if (!$result) {
    die("Query failed: " . $conn->error);
}

// Build HTML
$html = '
<h2>Inpatient Report</h2>
<table border="1" width="100%" cellspacing="0" cellpadding="5">
    <tr>
        <th>ID</th>
        <th>Patient Name</th>
        <th>Sex</th>
        <th>Date of Birth</th>
        <th>Address</th>
        <th>Admission Date</th>
        <th>Discharge Date</th>
        <th>Contact</th>
    </tr>';

while ($row = $result->fetch_assoc()) {
    $html .= '
    <tr>
        <td>' . htmlspecialchars($row['InpatientID']) . '</td>
        <td>' . htmlspecialchars($row['patient_name']) . '</td>
        <td>' . htmlspecialchars($row['Sex']) . '</td>
        <td>' . htmlspecialchars($row['DateOfBirth']) . '</td>
        <td>' . htmlspecialchars($row['Address']) . '</td>
        <td>' . htmlspecialchars($row['AdmissionDate']) . '</td>
        <td>' . htmlspecialchars($row['DischargeDate']) . '</td>
        <td>' . htmlspecialchars($row['Contact']) . '</td>
    </tr>';
}

$html .= '</table>';

$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

// Stream the PDF to the browser (view in browser, not download)
$dompdf->stream("inpatient_report.pdf", ["Attachment" => 0]);

exit();
?>
