<?php
session_start();
require_once __DIR__ . '/../config/db.php';

$result = $conn->query("
    SELECT prod_id, prod_name, prod_category, prod_brand,
           prod_size, prod_color, price, publish_image
    FROM shop_products
    WHERE prod_status = 1 AND publish_status = 1
    ORDER BY prod_name
");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Our Collection</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body{background:#f4f6f9;font-family:Arial,sans-serif;}
        .shop-header{background:#2c3e50;color:#fff;padding:16px 24px;display:flex;justify-content:space-between;align-items:center;}
        .shop-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:18px;padding:24px;}
        .shop-card{background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.1);}
        .shop-card img{width:100%;height:200px;object-fit:cover;}
        .shop-card-body{padding:12px;}
        .shop-card-body h4{margin:0 0 6px;font-size:15px;}
        .shop-card-body p{margin:2px 0;font-size:12px;color:#666;}
        .shop-card-body .price{font-size:16px;font-weight:bold;color:#1abc9c;margin-top:6px;}
        .no-img{width:100%;height:200px;background:#ecf0f1;display:flex;align-items:center;justify-content:center;font-size:40px;}
    </style>
</head>
<body>
<div class="shop-header">
    <span>🛍️ Our Collection</span>
    <a href="../auth/login.php" class="btn small">Staff Login</a>
</div>

<?php if ($result->num_rows === 0): ?>
    <div style="text-align:center;padding:60px;color:#777;">
        <p style="font-size:40px;">👕</p>
        <p>No products available yet.</p>
    </div>
<?php else: ?>
<div class="shop-grid">
    <?php while ($row = $result->fetch_assoc()): ?>
    <div class="shop-card">
        <?php if ($row['publish_image']): ?>
            <img src="../uploads/products/<?= htmlspecialchars($row['publish_image']) ?>"
                 alt="<?= htmlspecialchars($row['prod_name']) ?>">
        <?php else: ?>
            <div class="no-img">👕</div>
        <?php endif; ?>
        <div class="shop-card-body">
            <h4><?= htmlspecialchars($row['prod_name']) ?></h4>
            <?php if ($row['prod_brand']):    ?><p>Brand: <?= htmlspecialchars($row['prod_brand']) ?></p><?php endif; ?>
            <?php if ($row['prod_category']): ?><p>Type: <?= htmlspecialchars($row['prod_category']) ?></p><?php endif; ?>
            <?php if ($row['prod_size']):     ?><p>Size: <?= htmlspecialchars($row['prod_size']) ?></p><?php endif; ?>
            <?php if ($row['prod_color']):    ?><p>Color: <?= htmlspecialchars($row['prod_color']) ?></p><?php endif; ?>
            <div class="price">₹ <?= number_format($row['price'], 2) ?></div>
        </div>
    </div>
    <?php endwhile; ?>
</div>
<?php endif; ?>
</body>
</html>
