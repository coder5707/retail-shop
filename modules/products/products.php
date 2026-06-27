<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../partials/header.php';

$message = "";
$error   = "";

/* ─── ADD / UPDATE ─────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id       = (int)($_POST['id'] ?? 0);
    $name     = trim($_POST['name']     ?? '');
    $category = trim($_POST['category'] ?? '');
    $brand    = trim($_POST['brand']    ?? '');
    $size     = trim($_POST['size']     ?? '');
    $color    = trim($_POST['color']    ?? '');
    $price    = (float)($_POST['price'] ?? 0);
    $stock    = (int)($_POST['stock']   ?? 0);

    if ($name === '') {
        $error = "Product name is required.";
    } elseif ($price < 0) {
        $error = "Price cannot be negative.";
    } elseif ($stock < 0) {
        $error = "Stock cannot be negative.";
    } else {
        if ($id > 0) {
            $stmt = $conn->prepare("
                UPDATE shop_products
                SET prod_name=?, prod_category=?, prod_brand=?,
                    prod_size=?, prod_color=?, price=?, stock=?
                WHERE prod_id=?
            ");
            $stmt->bind_param("sssssdii", $name, $category, $brand, $size, $color, $price, $stock, $id);
        } else {
            $stmt = $conn->prepare("
                INSERT INTO shop_products
                    (prod_name, prod_category, prod_brand, prod_size, prod_color, price, stock)
                VALUES (?,?,?,?,?,?,?)
            ");
            $stmt->bind_param("sssssdi", $name, $category, $brand, $size, $color, $price, $stock);
        }
        if ($stmt->execute()) {
            $message = $id ? "✅ Product updated." : "✅ Product added.";
        } else {
            $error = "DB error: " . $stmt->error;
        }
        $stmt->close();
    }
}

/* ─── SOFT DELETE ──────────────────────────────── */
if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    $stmt   = $conn->prepare("UPDATE shop_products SET prod_status=0 WHERE prod_id=?");
    $stmt->bind_param("i", $del_id);
    $stmt->execute() ? $message = "🗑 Product deleted." : $error = "Delete failed.";
    $stmt->close();
}

/* ─── FETCH FOR EDIT ───────────────────────────── */
$editProduct = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt    = $conn->prepare("SELECT * FROM shop_products WHERE prod_id=? AND prod_status=1");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $editProduct = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

/* ─── LIST ACTIVE PRODUCTS ─────────────────────── */
$result = $conn->query("
    SELECT * FROM shop_products
    WHERE prod_status = 1
    ORDER BY created_at DESC
");
?>

<div class="card">
    <h2>👕 Products</h2>
    <?php if ($error):   ?><div class="alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($message): ?><div class="alert success"><?= $message ?></div><?php endif; ?>
</div>

<div class="card">
    <h3><?= $editProduct ? "✏ Edit Product" : "➕ Add New Product" ?></h3>
    <form method="post">
        <input type="hidden" name="id" value="<?= $editProduct['prod_id'] ?? '' ?>">
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;">
            <div>
                <label>Name *</label>
                <input type="text" name="name" required value="<?= htmlspecialchars($editProduct['prod_name'] ?? '') ?>">
            </div>
            <div>
                <label>Category</label>
                <input type="text" name="category" value="<?= htmlspecialchars($editProduct['prod_category'] ?? '') ?>">
            </div>
            <div>
                <label>Brand</label>
                <input type="text" name="brand" value="<?= htmlspecialchars($editProduct['prod_brand'] ?? '') ?>">
            </div>
            <div>
                <label>Size</label>
                <input type="text" name="size" value="<?= htmlspecialchars($editProduct['prod_size'] ?? '') ?>">
            </div>
            <div>
                <label>Color</label>
                <input type="text" name="color" value="<?= htmlspecialchars($editProduct['prod_color'] ?? '') ?>">
            </div>
            <div>
                <label>Price (₹) *</label>
                <input type="number" step="0.01" min="0" name="price" required value="<?= $editProduct['price'] ?? '' ?>">
            </div>
            <div>
                <label>Stock *</label>
                <input type="number" min="0" name="stock" required value="<?= $editProduct['stock'] ?? 0 ?>">
            </div>
        </div>
        <br>
        <button class="btn" type="submit"><?= $editProduct ? "Update" : "Add" ?> Product</button>
        <?php if ($editProduct): ?>
            <a href="products.php" class="btn secondary">Cancel</a>
        <?php endif; ?>
    </form>
</div>

<div class="card">
    <h3>Product List</h3>
    <?php if ($result->num_rows === 0): ?>
        <p style="color:#777;">No products found. Add one above.</p>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th>#</th><th>Name</th><th>Category</th><th>Brand</th>
                <th>Size</th><th>Color</th><th>Price</th><th>Stock</th><th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr <?= $row['stock'] == 0 ? 'style="background:#fff5f5;"' : '' ?>>
                <td><?= $row['prod_id'] ?></td>
                <td><?= htmlspecialchars($row['prod_name']) ?></td>
                <td><?= htmlspecialchars($row['prod_category']) ?></td>
                <td><?= htmlspecialchars($row['prod_brand']) ?></td>
                <td><?= htmlspecialchars($row['prod_size']) ?></td>
                <td><?= htmlspecialchars($row['prod_color']) ?></td>
                <td>₹ <?= number_format($row['price'], 2) ?></td>
                <td><?= $row['stock'] == 0 ? '<span style="color:red;font-weight:bold;">Out</span>' : $row['stock'] ?></td>
                <td>
                    <a class="btn small secondary" href="products.php?edit=<?= $row['prod_id'] ?>">Edit</a>
                    <a class="btn small danger"
                       href="products.php?delete=<?= $row['prod_id'] ?>"
                       onclick="return confirm('Delete this product?')">Delete</a>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
