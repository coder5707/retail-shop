<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../partials/header.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    echo "<div class='card'><div class='alert error'>Invalid sale ID.</div></div>";
    require_once __DIR__ . '/../../partials/footer.php';
    exit;
}

$stmt = $conn->prepare("
    SELECT s.*, c.cust_name, c.cust_phone, c.cust_email
    FROM shop_sales s
    LEFT JOIN shop_customers c ON s.customer_id = c.cust_id
    WHERE s.sale_id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$sale = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$sale) {
    echo "<div class='card'><div class='alert error'>Sale #$id not found.</div></div>";
    require_once __DIR__ . '/../../partials/footer.php';
    exit;
}

$itemsStmt = $conn->prepare("
    SELECT si.qty, si.unit_price, p.prod_name
    FROM shop_sale_items si
    JOIN shop_products p ON si.product_id = p.prod_id
    WHERE si.sale_id = ?
");
$itemsStmt->bind_param("i", $id);
$itemsStmt->execute();
$items = $itemsStmt->get_result();
$itemsStmt->close();
?>

<div class="card">
    <h2>🧾 Bill #<?= $sale['sale_id'] ?></h2>
    <p><strong>Date:</strong> <?= $sale['sale_date'] ?></p>
    <p><strong>Customer:</strong> <?= htmlspecialchars($sale['cust_name'] ?? 'Walk-in') ?></p>
    <?php if ($sale['cust_phone']): ?>
        <p><strong>Phone:</strong> <?= htmlspecialchars($sale['cust_phone']) ?></p>
    <?php endif; ?>
    <?php if ($sale['cust_email']): ?>
        <p><strong>Email:</strong> <?= htmlspecialchars($sale['cust_email']) ?></p>
    <?php endif; ?>
    <div style="margin-top:10px;">
        <a class="btn small secondary" href="print_bill.php?id=<?= $sale['sale_id'] ?>" target="_blank">🖨 Print Bill</a>
        <a class="btn small" href="sales_list.php">← Back to Sales</a>
    </div>
</div>

<div class="card">
    <table>
        <thead>
            <tr>
                <th>#</th><th>Product</th><th>Qty</th><th>Unit Price (₹)</th><th>Line Total (₹)</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $i = 1;
        while ($it = $items->fetch_assoc()):
            $lineTotal = $it['qty'] * $it['unit_price'];
        ?>
            <tr>
                <td><?= $i++ ?></td>
                <td><?= htmlspecialchars($it['prod_name']) ?></td>
                <td><?= $it['qty'] ?></td>
                <td><?= number_format($it['unit_price'], 2) ?></td>
                <td><?= number_format($lineTotal, 2) ?></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="4" style="text-align:right;">Grand Total</th>
                <th>₹ <?= number_format($sale['sale_total'], 2) ?></th>
            </tr>
        </tfoot>
    </table>
</div>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
