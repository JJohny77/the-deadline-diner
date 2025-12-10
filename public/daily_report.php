<?php
// daily_report.php
include "includes/auth.php";
include "includes/db.php";

require __DIR__ . "/lib/fpdf/fpdf.php";

/**
 * Helper για σωστή εμφάνιση ποσών με € σε FPDF (cp1252).
 */
function pdf_amount_with_euro(float $amount): string
{
    // Απλό, καθαρό, χωρίς special characters
    return number_format($amount, 2) . ' EUR';
}

// -----------------------------------------------------
// Βρίσκουμε ΗΜΕΡΟΜΗΝΙΑ ΑΝΑΦΟΡΑΣ ΜΕ ΒΑΣΗ ΤΗ MySQL
// -----------------------------------------------------
$dateFilterOrders  = "";
$dateFilterRefunds = "";

// Αν ο χρήστης έδωσε date=YYYY-MM-DD και είναι σωστό format, το χρησιμοποιούμε
if (isset($_GET['date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['date'])) {
    $reportDate = $_GET['date'];
    $safeDate   = mysqli_real_escape_string($conn, $reportDate);

    $dateFilterOrders  = "DATE(o.created_at) = '$safeDate'";
    $dateFilterRefunds = "DATE(created_at) = '$safeDate'";
} else {
    // Παίρνουμε την τρέχουσα ημερομηνία από τη MySQL για να είμαστε 100% sync
    $row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT CURDATE() AS today"));
    $reportDate = $row ? $row['today'] : date('Y-m-d');

    $dateFilterOrders  = "DATE(o.created_at) = CURDATE()";
    $dateFilterRefunds = "DATE(created_at) = CURDATE()";
}

// -----------------------------------------------------
// ΦΕΡΝΟΥΜΕ ΟΛΕΣ ΤΙΣ SERVED / REFUNDED ΠΑΡΑΓΓΕΛΙΕΣ ΤΗΣ ΗΜΕΡΑΣ
// -----------------------------------------------------
$sqlOrders = "
    SELECT 
        o.id,
        o.table_id,
        t.name AS table_name,
        o.created_at,
        o.status
    FROM orders o
    JOIN tables t ON t.id = o.table_id
    WHERE $dateFilterOrders
      AND o.status IN ('served', 'refunded')
    ORDER BY o.created_at ASC, o.id ASC
";

$res = mysqli_query($conn, $sqlOrders);
$orders = [];
while ($row = mysqli_fetch_assoc($res)) {
    $orders[] = $row;
}

// -----------------------------------------------------
// ΣΥΝΟΛΟ REFUNDS ΓΙΑ ΤΗ ΜΕΡΑ
// -----------------------------------------------------
$sqlRefunds = "
    SELECT COALESCE(SUM(amount), 0) AS total_refunds
    FROM refund_logs
    WHERE $dateFilterRefunds
";
$r = mysqli_query($conn, $sqlRefunds);
$refundRow = mysqli_fetch_assoc($r);
$totalRefunds = (float)$refundRow['total_refunds'];

// -----------------------------------------------------
// FPDF CLASS ΜΕ DejaVu FONT
// -----------------------------------------------------
class PDF extends FPDF
{
    function Header()
    {
        global $reportDate;
        $this->SetFont('DejaVu', '', 14);
        $this->Cell(0, 10, "Daily Orders Report - $reportDate", 0, 1, 'C');
        $this->Ln(3);
    }

    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('DejaVu', '', 8);
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }
}

// -----------------------------------------------------
// PDF SETUP
// -----------------------------------------------------
$pdf = new PDF();

// DejaVu font (έχεις ήδη φτιάξει DejaVuSans.php με MakeFont)
$pdf->AddFont('DejaVu', '', 'DejaVuSans.php');

$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('DejaVu', '', 10);

// -----------------------------------------------------
// ΑΝ ΔΕΝ ΥΠΑΡΧΟΥΝ ΠΑΡΑΓΓΕΛΙΕΣ
// -----------------------------------------------------
if (count($orders) === 0) {
    $pdf->Cell(0, 10, "No served/refunded orders for this day.", 0, 1);
} else {

    $grandTotalServed = 0.0;

    foreach ($orders as $order) {
        $orderId   = (int)$order['id'];
        $tableName = $order['table_name'];
        $createdAt = $order['created_at'];
        $status    = ucfirst($order['status']);

        // --------------------------
        // ΤΙΤΛΟΣ ΠΑΡΑΓΓΕΛΙΑΣ
        // --------------------------
        $pdf->SetFont('DejaVu', '', 11);
        $pdf->Cell(0, 8, "Order #$orderId - Table: $tableName - Status: $status", 0, 1);
        $pdf->SetFont('DejaVu', '', 10);
        $pdf->Cell(0, 6, "Created: $createdAt", 0, 1);

        // --------------------------
        // ΠΙΝΑΚΑΣ ITEMS HEADER
        // --------------------------
        $pdf->SetFont('DejaVu', '', 9);
        $pdf->Cell(80, 6, 'Item', 1);
        $pdf->Cell(20, 6, 'Qty', 1, 0, 'C');
        $pdf->Cell(30, 6, 'Price', 1, 0, 'R');
        $pdf->Cell(30, 6, 'Total', 1, 1, 'R');

        // --------------------------
        // ΦΕΡΝΟΥΜΕ ΤΑ ITEMS
        // --------------------------
        $itemsRes = mysqli_query($conn, "
            SELECT oi.quantity,
                   m.name  AS item_name,
                   m.price AS price
            FROM order_items oi
            JOIN menu m ON m.id = oi.menu_id
            WHERE oi.order_id = $orderId
        ");

        $orderTotal = 0.0;
        $pdf->SetFont('DejaVu', '', 9);

        while ($it = mysqli_fetch_assoc($itemsRes)) {
            $qty   = (int)$it['quantity'];
            $name  = $it['item_name'];
            $price = (float)$it['price'];
            $line  = $price * $qty;
            $orderTotal += $line;

            $pdf->Cell(80, 6, $name, 1);
            $pdf->Cell(20, 6, $qty, 1, 0, 'C');
            $pdf->Cell(30, 6, pdf_amount_with_euro($price), 1, 0, 'R');
            $pdf->Cell(30, 6, pdf_amount_with_euro($line), 1, 1, 'R');
        }

        // --------------------------
        // ΣΥΝΟΛΟ ΠΑΡΑΓΓΕΛΙΑΣ
        // --------------------------
        $pdf->SetFont('DejaVu', '', 9);
        $pdf->Cell(130, 7, 'Order Total', 1);
        $pdf->Cell(30, 7, pdf_amount_with_euro($orderTotal), 1, 1, 'R');

        $pdf->Ln(4);

        if ($order['status'] === 'served') {
            $grandTotalServed += $orderTotal;
        }
    }

    // -------------------------------------------------
    // DAILY SUMMARY
    // -------------------------------------------------
    $netRevenue = $grandTotalServed - $totalRefunds;

    $pdf->Ln(5);
    $pdf->SetFont('DejaVu', '', 11);
    $pdf->Cell(0, 8, 'Daily Summary', 0, 1);

    $pdf->SetFont('DejaVu', '', 10);
    $pdf->Cell(60, 6, 'Total Served Revenue:', 0);
    $pdf->Cell(0, 6, pdf_amount_with_euro($grandTotalServed), 0, 1);

    $pdf->Cell(60, 6, 'Total Refunds:', 0);
    $pdf->Cell(0, 6, pdf_amount_with_euro($totalRefunds), 0, 1);

    $pdf->Cell(60, 6, 'Net Revenue:', 0);
    $pdf->Cell(0, 6, pdf_amount_with_euro($netRevenue), 0, 1);
}

// -----------------------------------------------------
// OUTPUT PDF
// -----------------------------------------------------
$filename = "daily_report_" . $reportDate . ".pdf";
$pdf->Output('I', $filename);
exit;
