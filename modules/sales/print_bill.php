<?php
require_once __DIR__ . '/../../config/db.php';
// No header.php — standalone print page

session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../auth/login.php");
    exit;
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { echo "Invalid ID."; exit; }

$stmt = $conn->prepare("
    SELECT s.*, c.cust_name, c.cust_phone
    FROM shop_sales s
    LEFT JOIN shop_customers c ON s.customer_id = c.cust_id
    WHERE s.sale_id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$sale = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$sale) { echo "Sale not found."; exit; }

$iStmt = $conn->prepare("
    SELECT si.qty, si.unit_price, p.prod_name
    FROM shop_sale_items si
    JOIN shop_products p ON si.product_id = p.prod_id
    WHERE si.sale_id = ?
");
$iStmt->bind_param("i", $id);
$iStmt->execute();
$items = $iStmt->get_result();
$iStmt->close();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Bill #<?= $sale['sale_id'] ?></title>
    <style>
        body{font-family:Arial,sans-serif;font-size:13px;padding:20px;max-width:500px;margin:0 auto;}
        h2{text-align:center;margin-bottom:4px;}
        .center{text-align:center;}
        table{width:100%;border-collapse:collapse;margin-top:10px;}
        th,td{border:1px solid #ccc;padding:5px 7px;}
        th{background:#f5f5f5;}
        .total-row th{background:#34495e;color:#fff;}
        @media print{.no-print{display:none;}}
    </style>
</head>
<body>
<h2>🛍️ Retail Clothes Shop</h2>
<p class="center">Bill #<?= $sale['sale_id'] ?> | <?= $sale['sale_date'] ?></p>
<p><strong>Customer:</strong> <?= htmlspecialchars($sale['cust_name'] ?? 'Walk-in') ?>
   <?php if ($sale['cust_phone']): ?> | <?= htmlspecialchars($sale['cust_phone']) ?><?php endif; ?>
</p>
<table>
    <thead><tr><th>Product</th><th>Qty</th><th>Price</th><th>Total</th></tr></thead>
    <tbody>
    <?php while ($it = $items->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($it['prod_name']) ?></td>
            <td><?= $it['qty'] ?></td>
            <td>₹<?= number_format($it['unit_price'],2) ?></td>
            <td>₹<?= number_format($it['qty']*$it['unit_price'],2) ?></td>
        </tr>
    <?php endwhile; ?>
    </tbody>
    <tfoot>
        <tr class="total-row"><th colspan="3">Grand Total</th><th>₹<?= number_format($sale['sale_total'],2) ?></th></tr>
    </tfoot>
</table>
<br>
<p class="center">Thank you for shopping with us!</p>
<div class="no-print" style="text-align:center;margin-top:16px;">
    <button onclick="window.print()" style="padding:8px 20px;background:#1abc9c;color:#fff;border:none;border-radius:4px;cursor:pointer;font-size:14px;">🖨 Print</button>
</div>
</body>
</html>
