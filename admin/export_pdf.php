<?php
$required_role = ['admin'];
include '../includes/auth.php';
include '../config/db.php';

require_once('../includes/tcpdf/tcpdf.php');

// Create PDF object
$pdf = new TCPDF();
$pdf->SetCreator('Blood Donation Management');
$pdf->SetAuthor('Admin');
$pdf->SetTitle('System Export Report');
$pdf->SetMargins(15, 15, 15);
$pdf->AddPage();

// Title
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, 'Blood Donation Management - Export Report', 0, 1, 'C');
$pdf->Ln(8);

// Function to generate tables
function addTable($pdf, $title, $headers, $data)
{
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, $title, 0, 1, 'L');
    $pdf->Ln(2);

    $pdf->SetFont('helvetica', '', 10);

    // Table header
    $tbl = '<table border="1" cellpadding="4"><tr>';
    foreach ($headers as $header) {
        $tbl .= '<th><b>' . htmlspecialchars($header) . '</b></th>';
    }
    $tbl .= '</tr>';

    // Table rows
    if (!empty($data)) {
        foreach ($data as $row) {
            $tbl .= '<tr>';
            foreach ($row as $cell) {
                $tbl .= '<td>' . htmlspecialchars($cell) . '</td>';
            }
            $tbl .= '</tr>';
        }
    } else {
        $tbl .= '<tr><td colspan="' . count($headers) . '">No records found</td></tr>';
    }

    $tbl .= '</table>';
    $pdf->writeHTML($tbl, true, false, false, false, '');
    $pdf->Ln(5);
}

// Fetch Colleges
$colleges = $conn->query("SELECT name, email, status FROM institutions WHERE type='college'");
$college_data = $colleges->fetch_all(MYSQLI_ASSOC);
addTable($pdf, 'Colleges', ['Name', 'Email', 'Status'], $college_data);

// Fetch Hospitals
$hospitals = $conn->query("SELECT name, email, status FROM institutions WHERE type='hospital'");
$hospital_data = $hospitals->fetch_all(MYSQLI_ASSOC);
addTable($pdf, 'Hospitals', ['Name', 'Email', 'Status'], $hospital_data);

// Fetch Users / Students
$users = $conn->query("SELECT name, email, role FROM users");
$user_data = $users->fetch_all(MYSQLI_ASSOC);
addTable($pdf, 'Users / Students', ['Name', 'Email', 'Role'], $user_data);

// Output PDF
$pdf->Output('export_report.pdf', 'D'); // Force download
exit;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Export PDF - Admin</title>
    <?php include '../includes/header.php'; ?>

</head>

<body>
    <?php include 'admin_layout_start.php' ?>
    <div class="container text-center py-5">
        <h2 class="mb-4">Export Data</h2>
        <a href="export_pdf.php" class="btn btn-danger btn-lg">
            <i class="bi bi-file-earmark-pdf"></i> Download PDF Report
        </a>
    </div>

    <?php include 'admin_layout_end.php'; ?>

</body>

</html>