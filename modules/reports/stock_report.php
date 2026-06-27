<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../partials/header.php';

$result = $conn->query("
    SELECT prod_id, prod_name, prod_category, prod_brand,
           prod_size, prod_color, price, stock
    FROM shop_products
    WHERE prod_status = 1
    ORDER BY prod_name
");
?>
<div class="card">
    <h2>📦 Stock Report</h2>
    <p style="color:#555;">Current inventory of all active products.</p>
</div>
<div class="card">
    <?php if ($result->num_rows === 0): ?>
        <p style="color:#777;">No products found.</p>
    <?php else: ?>
    <table>
        <thead>
            <tr><th>#</th><th>Name</th><th>Category</th><th>Brand</th><th>Size</th><th>Color</th><th>Price (₹)</th><th>Stock</th><th>Status</th></tr>
        </thead>
        <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr <?= $row['stock'] == 0 ? 'style="background:#fff5f5;"' : ($row['stock'] < 5 ? 'style="background:#fffbea;"' : '') ?>>
                <td><?= $row['prod_id'] ?></td>
                <td><?= htmlspecialchars($row['prod_name']) ?></td>
                <td><?= htmlspecialchars($row['prod_category']) ?></td>
                <td><?= htmlspecialchars($row['prod_brand']) ?></td>
                <td><?= htmlspecialchars($row['prod_size']) ?></td>
                <td><?= htmlspecialchars($row['prod_color']) ?></td>
                <td>₹ <?= number_format($row['price'], 2) ?></td>
                <td><?= $row['stock'] ?></td>
                <td>
                    <?php if ($row['stock'] == 0): ?>
                        <span style="color:red;font-weight:bold;">Out of Stock</span>
                    <?php elseif ($row['stock'] < 5): ?>
                        <span style="color:#e67e22;font-weight:bold;">Low Stock</span>
                    <?php else: ?>
                        <span style="color:green;">In Stock</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
