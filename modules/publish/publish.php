<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../partials/header.php';

if ($_SESSION['role'] !== 'admin') {
    echo "<div class='card'><div class='alert error'>Access Denied — Admin only.</div></div>";
    require_once __DIR__ . '/../../partials/footer.php';
    exit;
}

$message = "";
$error   = "";

/* ── UNPUBLISH ─────────────────── */
if (isset($_GET['unpublish'])) {
    $uid = (int)$_GET['unpublish'];
    $conn->prepare("UPDATE shop_products SET publish_status=0, publish_image=NULL WHERE prod_id=?")
         ->execute([$uid]) ;
    // Use object form:
    $stmt = $conn->prepare("UPDATE shop_products SET publish_status=0, publish_image=NULL WHERE prod_id=?");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $stmt->close();
    $message = "Product unpublished.";
}

/* ── PUBLISH ───────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = (int)($_POST['product_id'] ?? 0);

    if (!$product_id) {
        $error = "Please select a product.";
    } elseif (empty($_FILES['publish_image']['name'])) {
        $error = "Please upload an image.";
    } else {
        $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
        $mime    = mime_content_type($_FILES['publish_image']['tmp_name']);

        if (!in_array($mime, $allowed)) {
            $error = "Only JPG, PNG, GIF, WEBP images are allowed.";
        } elseif ($_FILES['publish_image']['size'] > 2 * 1024 * 1024) {
            $error = "Image must be under 2MB.";
        } else {
            $ext       = pathinfo($_FILES['publish_image']['name'], PATHINFO_EXTENSION);
            $imageName = time() . '_' . $product_id . '.' . $ext;
            $destDir   = __DIR__ . '/../../uploads/products/';
            if (!is_dir($destDir)) mkdir($destDir, 0755, true);

            if (move_uploaded_file($_FILES['publish_image']['tmp_name'], $destDir . $imageName)) {
                $stmt = $conn->prepare(
                    "UPDATE shop_products SET publish_status=1, publish_image=? WHERE prod_id=?"
                );
                $stmt->bind_param("si", $imageName, $product_id);
                $stmt->execute();
                $stmt->close();
                $message = "✅ Product published successfully!";
            } else {
                $error = "Failed to upload image. Check folder permissions.";
            }
        }
    }
}

/* ── FETCH UNPUBLISHED ─────────── */
$unpublished = $conn->query("
    SELECT prod_id, prod_name FROM shop_products
    WHERE prod_status = 1 AND publish_status = 0
    ORDER BY prod_name
");

/* ── FETCH PUBLISHED ───────────── */
$published = $conn->query("
    SELECT prod_id, prod_name, publish_image FROM shop_products
    WHERE prod_status = 1 AND publish_status = 1
    ORDER BY prod_name
");
?>
<div class="card">
    <h2>🌐 Publish Products (Admin)</h2>
    <?php if ($error):   ?><div class="alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($message): ?><div class="alert success"><?= $message ?></div><?php endif; ?>
</div>

<div class="card">
    <h3>Publish a Product</h3>
    <?php if ($unpublished->num_rows === 0): ?>
        <p style="color:#777;">All products are already published.</p>
    <?php else: ?>
    <form method="post" enctype="multipart/form-data">
        <label>Select Product *</label>
        <select name="product_id" required style="margin-bottom:10px;">
            <option value="">-- Select Product --</option>
            <?php while ($row = $unpublished->fetch_assoc()): ?>
                <option value="<?= $row['prod_id'] ?>"><?= htmlspecialchars($row['prod_name']) ?></option>
            <?php endwhile; ?>
        </select>
        <label>Upload Photo * (max 2MB, JPG/PNG/GIF/WEBP)</label>
        <input type="file" name="publish_image" accept="image/*" required style="margin-bottom:12px;">
        <br>
        <button class="btn">🌐 Publish Product</button>
    </form>
    <?php endif; ?>
</div>

<div class="card">
    <h3>Published Products</h3>
    <?php if ($published->num_rows === 0): ?>
        <p style="color:#777;">No products published yet.</p>
    <?php else: ?>
    <table>
        <thead><tr><th>#</th><th>Name</th><th>Image</th><th>Action</th></tr></thead>
        <tbody>
        <?php while ($row = $published->fetch_assoc()): ?>
            <tr>
                <td><?= $row['prod_id'] ?></td>
                <td><?= htmlspecialchars($row['prod_name']) ?></td>
                <td>
                    <?php if ($row['publish_image']): ?>
                        <img src="<?= $basePath ?>/uploads/products/<?= htmlspecialchars($row['publish_image']) ?>"
                             style="height:40px;border-radius:4px;" alt="Product">
                    <?php else: ?>—<?php endif; ?>
                </td>
                <td>
                    <a class="btn small danger"
                       href="publish.php?unpublish=<?= $row['prod_id'] ?>"
                       onclick="return confirm('Unpublish this product?')">Unpublish</a>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
