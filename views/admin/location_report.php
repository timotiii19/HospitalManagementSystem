<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'Admin') {
    header("Location: ../../auth/admin_login.php");
    exit();
}

include('../../config/db.php');
$typeFilter = isset($_GET['type']) ? $_GET['type'] : '';
$capacityFilter = isset($_GET['capacity']) ? $_GET['capacity'] : '';

$where = [];
if (!empty($typeFilter)) {
    $where[] = "l.RoomType = '" . $conn->real_escape_string($typeFilter) . "'";
}
if (!empty($capacityFilter)) {
    $where[] = "l.RoomCapacity = '" . $conn->real_escape_string($capacityFilter) . "'";
}

$whereSQL = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';
$sql = "
    SELECT l.*, 
           COUNT(i.InpatientID) AS Occupied
    FROM locations l
    LEFT JOIN inpatients i 
        ON l.LocationID = i.LocationID AND i.DischargeDate IS NULL
    $whereSQL
    GROUP BY l.LocationID
    ORDER BY l.Building, l.Floor, l.RoomNumber
";

// Require FPDF
require_once __DIR__ . '/../../dompdf/vendor/fpdf186/fpdf.php';

class PDF extends FPDF {
    function Header() {
        $this->SetFont('Arial','B',60);
        $this->SetTextColor(230, 230, 230);
        $this->Rotate(45, 70, 190);
        $this->Text(-50, 300, 'CHART MEMORIAL');
        $this->Text(-10, 330, 'HOSPITAL');
        $this->Rotate(0);

        $this->SetTextColor(0);
        $logoPath = __DIR__ . '/../../images/hosplogo-hp.png';
        if (file_exists($logoPath)) {
            $this->Image($logoPath, 100, 10, 100);
        }
        $this->Ln(24);
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 10, 'Location Report', 0, 1, 'C');
    }

    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(0,10,'Page '.$this->PageNo(),0,0,'C');
    }

    protected $angle = 0;
    function Rotate($angle, $x = -1, $y = -1) {
        if ($x == -1) $x = $this->x;
        if ($y == -1) $y = $this->y;
        if ($this->angle != 0) $this->_out('Q');
        $this->angle = $angle;
        if ($angle != 0) {
            $angle *= M_PI / 180;
            $c = cos($angle); $s = sin($angle);
            $cx = $x * $this->k; $cy = ($this->h - $y) * $this->k;
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

    function TableHeader($headers, $widths) {
        $this->SetFont('Arial','B',8);
        $this->SetFillColor(230,230,230);
        foreach ($headers as $i => $col) {
            $this->Cell($widths[$i], 8, $col, 1, 0, 'C', true);
        }
        $this->Ln();
    }
}

$result = $conn->query($sql);

$pdf = new PDF('L', 'mm', 'A4'); // Landscape
$pdf->AddPage();
$pdf->Ln(5);

// Headers & widths
$headers = ['ID','Name','Condition','Room Type','Capacity','Occupied','Building','Floor','Room #','Fee'];
$widths  = [15, 55, 45, 35, 20, 20, 20, 20, 20, 25];
$pdf->TableHeader($headers, $widths);

// Table Row
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        if ($pdf->GetY() > 180) {
            $pdf->AddPage();
            $pdf->TableHeader($headers, $widths);
        }

        $data = [
            $row['LocationID'],
            $row['LocationName'],
            $row['ConditionType'],
            $row['RoomType'],
            $row['RoomCapacity'],
            $row['Occupied'], // New Occupied column
            $row['Building'],
            $row['Floor'],
            $row['RoomNumber'],
            '$' . number_format($row['RoomFee'], 2)
        ];
        foreach ($data as $i => $val) {
            $pdf->Cell($widths[$i], 8, $val, 1, 0, 'C');
        }
        $pdf->Ln();
    }
} else {
    $pdf->Cell(array_sum($widths), 10, 'No location records found.', 1, 1, 'C');
}

// Footer
$pdf->SetY(-50);
$adminName = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : $_SESSION['username'];
$printDateTime = date('Y-m-d H:i:s');

$pdf->SetFont('Arial', 'I', 10);
$pdf->Cell(0, 8, "Printed by: $adminName", 0, 1, 'R');
$pdf->Cell(0, 8, "Printed on: $printDateTime", 0, 1, 'R');

$pdf->Output('D', 'Location_Report_' . date('Ymd') . '.pdf');
exit();
