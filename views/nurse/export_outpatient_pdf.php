<?php
require '../../dompdf/autoload.inc.php';
use Dompdf\Dompdf;

include('../../config/db.php');

// Check database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT o.OutpatientID, p.Name AS patient_name, p.DateOfBirth, p.Sex, p.Address, o.VisitDate, o.Reason, p.Contact
        FROM outpatients o
        JOIN patients p ON o.PatientID = p.PatientID";


$result = $conn->query($sql);

if (!$result) {
    die("Query failed: " . $conn->error);
}

// Build HTML
$html = '
<h2>Outpatient Report</h2>
<table border="1" width="100%" cellspacing="0" cellpadding="5">
    <tr>
        <th>ID</th>
        <th>Patient Name</th>
        <th>Sex</th>
        <th>Date of Birth</th>
        <th>Address</th>
        <th>Visit Date</th>
        <th>Reason</th>
        <th>Contact</th>
    </tr>';

while ($row = $result->fetch_assoc()) {
    $html .= '
    <tr>
        <td>' . htmlspecialchars($row['OutpatientID']) . '</td>
        <td>' . htmlspecialchars($row['patient_name']) . '</td>
        <td>' . htmlspecialchars($row['Sex']) . '</td>
        <td>' . htmlspecialchars($row['DateOfBirth']) . '</td>
        <td>' . htmlspecialchars($row['Address']) . '</td>
        <td>' . htmlspecialchars($row['VisitDate']) . '</td>
        <td>' . htmlspecialchars($row['Reason']) . '</td>
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
