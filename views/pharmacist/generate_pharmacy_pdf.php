<?php
session_start(); // Start session to access session variables

require '../../dompdf/autoload.inc.php';
use Dompdf\Dompdf;

include('../../config/db.php');

// Check if the user is a logged-in doctor
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Pharmacist') {
    die("Access denied.");
}

// Get logged-in doctor's name
$doctorName = $_SESSION['full_name'] ?? $_SESSION['username'];
$printDateTime = date("F j, Y - g:i A");

// Check DB connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Query pharmacy table
$result = $conn->query("SELECT MedicineID, MedicineName, Description, StockQuantity, Price FROM pharmacy");

if (!$result) {
    die("Query failed: " . $conn->error);
}

// HTML content
$html = '
<h2 style="text-align:center;">Pharmacy Inventory Report</h2>
<table border="1" width="100%" cellspacing="0" cellpadding="5">
    <tr>
        <th>Medicine ID</th>
        <th>Medicine Name</th>
        <th>Description</th>
        <th>Stock Quantity</th>
        <th>Price</th>
    </tr>';

while ($row = $result->fetch_assoc()) {
    $html .= '
    <tr>
        <td>' . htmlspecialchars($row['MedicineID']) . '</td>
        <td>' . htmlspecialchars($row['MedicineName']) . '</td>
        <td>' . htmlspecialchars($row['Description']) . '</td>
        <td>' . htmlspecialchars($row['StockQuantity']) . '</td>
        <td>' . number_format($row['Price'], 2) . '</td>
    </tr>';
}

$html .= '
</table>
<br><br>
<p><strong>Printed by:</strong> Pharmacist ' . htmlspecialchars($doctorName) . '</p>
<p><strong>Printed on:</strong> ' . $printDateTime . '</p>';

// Generate PDF
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();
$dompdf->stream("pharmacy_inventory.pdf", ["Attachment" => 0]);

exit();
