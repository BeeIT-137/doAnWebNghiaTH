<?php
// admin/product-edit.php
// Thêm / Sửa sản phẩm + Biến thể (màu / dung lượng + ảnh riêng)

require_once __DIR__ . '/../includes/admin_auth.php';
// admin_auth.php đã require functions.php → có db(), make_slug(), require_admin(), upload_image()...

require_admin();
$pdo = db();

// Lấy danh mục
$stmtCat = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC");
$categories = $stmtCat->fetchAll(PDO::FETCH_ASSOC);

// Xác định đang thêm mới hay sửa
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = $id > 0;

$product  = null;
$variants = [];
$selectedCategoryIds = [];

if ($isEdit) {
    // Lấy thông tin sản phẩm
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        redirect('products.php');
    }

    // Lấy danh sách biến thể
    $stmtVar = $pdo->prepare("SELECT * FROM product_variants WHERE product_id = ? ORDER BY id ASC");
    $stmtVar->execute([$id]);
    $variants = $stmtVar->fetchAll(PDO::FETCH_ASSOC);

    // Lấy danh mục đã chọn cho sản phẩm (nếu sử dụng bảng product_categories)
    $selectedCategoryIds = [];
    $stmtProdCat = $pdo->prepare("SELECT category_id FROM product_categories WHERE product_id = ? ORDER BY category_id ASC");
    $stmtProdCat->execute([$id]);
    $selectedCategoryIds = $stmtProdCat->fetchAll(PDO::FETCH_COLUMN);
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Nhận mảng danh mục (hỗ trợ chọn nhiều)
    $category_ids = $_POST['category_ids'] ?? [];
    if (!is_array($category_ids)) $category_ids = [$category_ids];
    $category_ids = array_values(array_filter(array_map('intval', $category_ids), function ($v) {
        return $v > 0;
    }));

    $name        = trim($_POST['name'] ?? '');
    $slug        = trim($_POST['slug'] ?? '');
    $price       = (int)($_POST['price'] ?? 0);
    $old_price   = $_POST['old_price'] !== '' ? (int)$_POST['old_price'] : null;
    $description = trim($_POST['description'] ?? '');
    $specs       = trim($_POST['specs'] ?? '');
    $stock       = (int)($_POST['stock'] ?? 0);

    // Ảnh cũ (nếu đang sửa)
    $imageOld    = $_POST['image_old'] ?? ($product['image'] ?? null);

    if (empty($category_ids) || $name === '') {
        $error = 'Vui lòng chọn ít nhất một danh mục và nhập tên sản phẩm.';
    } else {
        if ($slug === '') {
            $slug = make_slug($name);
        }

        // Upload ảnh chính sản phẩm
        // - Nếu không chọn file → giữ nguyên $imageOld
        // - Nếu chọn file hợp lệ → lưu file, trả về path mới
        $image = upload_image('image', $imageOld);

        // Lấy primary category để lưu vào cột category_id của bảng products (để tương thích)
        $primary_category_id = $category_ids[0] ?? 0;

        try {
            $pdo->beginTransaction();

            if ($isEdit) {
                // UPDATE PRODUCTS
                $stmt = $pdo->prepare("
                    UPDATE products
                    SET category_id = :category_id,
                        name        = :name,
                        slug        = :slug,
                        price       = :price,
                        old_price   = :old_price,
                        image       = :image,
                        description = :description,
                        specs       = :specs,
                        stock       = :stock
                    WHERE id = :id
                ");
                $stmt->execute([
                    ':category_id' => $primary_category_id,
                    ':name'        => $name,
                    ':slug'        => $slug,
                    ':price'       => $price,
                    ':old_price'   => $old_price,
                    ':image'       => $image,
                    ':description' => $description,
                    ':specs'       => $specs,
                    ':stock'       => $stock,
                    ':id'          => $id,
                ]);

                $productId = $id;
            } else {
                // INSERT PRODUCTS
                $stmt = $pdo->prepare("
                    INSERT INTO products 
                        (category_id, name, slug, price, old_price, image, description, specs, stock, created_at)
                    VALUES
                        (:category_id, :name, :slug, :price, :old_price, :image, :description, :specs, :stock, NOW())
                ");
                $stmt->execute([
                    ':category_id' => $primary_category_id,
                    ':name'        => $name,
                    ':slug'        => $slug,
                    ':price'       => $price,
                    ':old_price'   => $old_price,
                    ':image'       => $image,
                    ':description' => $description,
                    ':specs'       => $specs,
                    ':stock'       => $stock,
                ]);
                $productId = (int)$pdo->lastInsertId();
                $isEdit    = true;
                $id        = $productId;
            }

            // ==========================
            // XỬ LÝ BIẾN THỂ + ẢNH RIÊNG
            // ==========================

            // Xóa sạch biến thể cũ, thêm lại từ form
            $stmtDelVar = $pdo->prepare("DELETE FROM product_variants WHERE product_id = ?");
            $stmtDelVar->execute([$productId]);

            $variant_colors     = $_POST['variant_color']      ?? [];
            $variant_storages   = $_POST['variant_storage']    ?? [];
            $variant_image_old  = $_POST['variant_image_old']  ?? [];
            $variant_prices     = $_POST['variant_price']      ?? [];
            $variant_old_price  = $_POST['variant_old_price']  ?? [];
            $variant_stocks     = $_POST['variant_stock']      ?? [];

            $hasVariant = false;
            $minPrice   = null;
            $totalStock = 0;

            if (!empty($variant_colors) && is_array($variant_colors)) {
                $stmtVarIns = $pdo->prepare("
                    INSERT INTO product_variants
                        (product_id, color, storage, image, price, old_price, stock)
                    VALUES
                        (:product_id, :color, :storage, :image, :price, :old_price, :stock)
                ");

                foreach ($variant_colors as $idx => $color) {
                    $color   = trim($color);
                    $storage = trim($variant_storages[$idx] ?? '');
                    $imgOld  = trim($variant_image_old[$idx] ?? '');

                    $v_price = isset($variant_prices[$idx]) ? (int)$variant_prices[$idx] : 0;
                    $v_old   = isset($variant_old_price[$idx]) && $variant_old_price[$idx] !== ''
                        ? (int)$variant_old_price[$idx]
                        : null;
                    $v_stock = isset($variant_stocks[$idx]) ? (int)$variant_stocks[$idx] : 0;

                    // Chuẩn hóa FILES cho ảnh biến thể index $idx
                    $fileFieldName = 'variant_image_' . $idx;

                    if (!empty($_FILES['variant_image']['name'][$idx])) {
                        // Map từ mảng variant_image[] sang một field "phẳng" để dùng upload_image()
                        $_FILES[$fileFieldName] = [
                            'name'     => $_FILES['variant_image']['name'][$idx],
                            'type'     => $_FILES['variant_image']['type'][$idx],
                            'tmp_name' => $_FILES['variant_image']['tmp_name'][$idx],
                            'error'    => $_FILES['variant_image']['error'][$idx],
                            'size'     => $_FILES['variant_image']['size'][$idx],
                        ];
                    }

                    // Upload ảnh biến thể: nếu không upload mới → dùng lại $imgOld
                    $v_image = upload_image($fileFieldName, $imgOld);

                    // Bỏ qua dòng rỗng & giá không hợp lệ
                    if ($color === '' && $storage === '' && $v_image === '') continue;
                    if ($v_price <= 0) continue;

                    $stmtVarIns->execute([
                        ':product_id' => $productId,
                        ':color'      => $color,
                        ':storage'    => $storage,
                        ':image'      => $v_image,
                        ':price'      => $v_price,
                        ':old_price'  => $v_old,
                        ':stock'      => $v_stock,
                    ]);

                    $hasVariant = true;

                    if ($minPrice === null || $v_price < $minPrice) {
                        $minPrice = $v_price;
                    }
                    $totalStock += max(0, $v_stock);
                }
            }

            // Nếu có biến thể → cập nhật lại price & stock của products
            if ($hasVariant) {
                $stmtUpdate = $pdo->prepare("UPDATE products SET price = ?, stock = ? WHERE id = ?");
                $stmtUpdate->execute([
                    $minPrice !== null ? $minPrice : $price,
                    $totalStock,
                    $productId
                ]);
            }

            // Lưu các danh mục cho sản phẩm (bảng product_categories)
            // Xóa các liên kết cũ rồi thêm các liên kết mới từ form
            $stmtDelProdCat = $pdo->prepare("DELETE FROM product_categories WHERE product_id = ?");
            $stmtDelProdCat->execute([$productId]);

            if (!empty($category_ids)) {
                $stmtInsProdCat = $pdo->prepare("INSERT INTO product_categories (product_id, category_id) VALUES (?, ?)");
                foreach ($category_ids as $cat_id) {
                    $stmtInsProdCat->execute([$productId, $cat_id]);
                }
            }

            $pdo->commit();

            $success = $isEdit ? 'Cập nhật sản phẩm thành công.' : 'Thêm sản phẩm thành công.';

            // Reload lại dữ liệu để hiển thị chính xác
            $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? LIMIT 1");
            $stmt->execute([$productId]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmtVar = $pdo->prepare("SELECT * FROM product_variants WHERE product_id = ? ORDER BY id ASC");
            $stmtVar->execute([$productId]);
            $variants = $stmtVar->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Có lỗi xảy ra khi lưu sản phẩm. Vui lòng thử lại.';
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="admin-main__inner">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;gap:8px;flex-wrap:wrap;">
        <h2 style="margin:0;font-size:1.1rem;">
            <?= $isEdit ? 'Chỉnh sửa sản phẩm' : 'Thêm sản phẩm mới' ?>
        </h2>
        <a href="products.php" class="btn btn--ghost">
            &larr; Quay lại danh sách
        </a>
    </div>

    <?php if ($error): ?>
        <div style="background:#fee2e2;border:1px solid #fecaca;color:#b91c1c;padding:8px 10px;border-radius:10px;font-size:0.9rem;margin-bottom:10px;">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div style="background:#ecfdf5;border:1px solid #bbf7d0;color:#166534;padding:8px 10px;border-radius:10px;font-size:0.9rem;margin-bottom:10px;">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <div class="admin-form">
        <!-- form nhớ thêm enctype để upload file -->
        <form method="post" enctype="multipart/form-data">
            <!-- GRID 2 CỘT -->
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:16px;margin-bottom:16px;">
                <div>
                    <div class="form-group">
                        <label>Danh mục (chọn 1 hoặc nhiều)</label>
                        <div class="category-checkbox-list" style="border:1px solid #e5e7eb;border-radius:8px;padding:8px;max-height:200px;overflow:auto;">
                            <?php foreach ($categories as $cat): ?>
                                <label class="category-checkbox-item">
                                    <input type="checkbox" name="category_ids[]" value="<?= (int)$cat['id'] ?>"
                                        <?= in_array($cat['id'], $selectedCategoryIds ?? []) ? 'checked' : '' ?> />
                                    <span><?= htmlspecialchars($cat['name']) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <small style="color:#6b7280;">Bạn có thể chọn nhiều danh mục, ví dụ: &quot;Điện thoại&quot; và &quot;iPhone&quot;.</small>
                    </div>

                    <div class="form-group">
                        <label>Tên sản phẩm</label>
                        <input type="text" name="name"
                            value="<?= $product ? htmlspecialchars($product['name']) : '' ?>"
                            placeholder="Ví dụ: iPhone 15 Pro Max 256GB" required>
                    </div>

                    <div class="form-group">
                        <label>Slug (đường dẫn SEO)</label>
                        <input type="text" name="slug"
                            value="<?= $product ? htmlspecialchars($product['slug']) : '' ?>"
                            placeholder="Tự tạo từ tên nếu để trống">
                        <small>Ví dụ: iphone-15-pro-max-256gb</small>
                    </div>

                    <div class="form-group">
                        <label>Mô tả ngắn</label>
                        <textarea name="description" rows="3"
                            placeholder="Mô tả tổng quan về sản phẩm..."><?= $product ? htmlspecialchars($product['description']) : '' ?></textarea>
                    </div>
                </div>

                <div>
                    <div class="form-group">
                        <label>Ảnh chính sản phẩm</label>

                        <?php if (!empty($product['image'])): ?>
                            <div style="margin-bottom:6px;">
                                <img src="../<?= htmlspecialchars($product['image']) ?>"
                                    alt="<?= htmlspecialchars($product['name'] ?? '') ?>"
                                    style="max-width:140px;border-radius:10px;background:#020617;padding:4px;">
                            </div>
                        <?php endif; ?>

                        <input type="file" name="image" accept="image/*">
                        <input type="hidden" name="image_old" value="<?= htmlspecialchars($product['image'] ?? '') ?>">
                        <small>Chọn file JPG/PNG/GIF/WebP &lt;= 3MB. Nếu không chọn, hệ thống sẽ giữ nguyên ảnh cũ.</small>
                    </div>

                    <div class="form-group">
                        <label>Giá cơ bản (nếu không dùng biến thể)</label>
                        <input type="number" name="price" min="0" step="1000"
                            value="<?= $product ? (int)$product['price'] : 0 ?>"
                            placeholder="Ví dụ: 25990000">
                        <small>Nếu có biến thể, giá sẽ tự chọn theo biến thể thấp nhất.</small>
                    </div>

                    <div class="form-group">
                        <label>Giá cũ (nếu có)</label>
                        <input type="number" name="old_price" min="0" step="1000"
                            value="<?= $product && $product['old_price'] !== null ? (int)$product['old_price'] : '' ?>"
                            placeholder="Ví dụ: 28990000">
                    </div>

                    <div class="form-group">
                        <label>Tồn kho (nếu không dùng biến thể)</label>
                        <input type="number" name="stock" min="0" step="1"
                            value="<?= $product ? (int)$product['stock'] : 0 ?>"
                            placeholder="Ví dụ: 10">
                    </div>
                </div>
            </div>

            <!-- THÔNG SỐ KỸ THUẬT -->
            <div class="form-group">
                <label>Thông số kỹ thuật</label>
                <textarea name="specs" rows="4" placeholder="VD: Màn hình, chip, RAM, ROM, camera, pin..."><?= $product ? htmlspecialchars($product['specs']) : '' ?></textarea>
            </div>

            <!-- BIẾN THỂ -->
            <div class="admin-card" style="margin-top:12px;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;gap:8px;flex-wrap:wrap;">
                    <div>
                        <h3 style="font-size:0.95rem;margin:0;">Biến thể màu, dung lượng & ảnh riêng</h3>
                        <p style="font-size:0.8rem;color:#6b7280;margin-top:2px;">
                            Mỗi biến thể tương ứng với một tổ hợp màu + dung lượng + ảnh, có giá và tồn kho riêng.
                        </p>
                    </div>
                    <button type="button" class="btn btn--ghost" id="add-variant-btn">
                        <i class="fa-solid fa-plus" style="margin-right:4px;"></i> Thêm biến thể
                    </button>
                </div>

                <div id="variant-rows">
                    <?php if (!empty($variants)): ?>
                        <?php foreach ($variants as $v): ?>
                            <div class="variant-row"
                                style="display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:8px;align-items:flex-end;margin-bottom:8px;">
                                <div class="form-group">
                                    <label>Màu sắc</label>
                                    <input type="text" name="variant_color[]"
                                        value="<?= htmlspecialchars($v['color']) ?>"
                                        placeholder="Ví dụ: Xanh, Đen, Trắng...">
                                </div>
                                <div class="form-group">
                                    <label>Dung lượng</label>
                                    <input type="text" name="variant_storage[]"
                                        value="<?= htmlspecialchars($v['storage']) ?>"
                                        placeholder="Ví dụ: 128GB, 256GB...">
                                </div>
                                <div class="form-group">
                                    <label>Ảnh biến thể</label>

                                    <?php if (!empty($v['image'])): ?>
                                        <div style="margin-bottom:4px;">
                                            <img src="../<?= htmlspecialchars($v['image']) ?>"
                                                alt=""
                                                style="width:48px;height:48px;object-fit:contain;border-radius:8px;background:#020617;padding:2px;">
                                        </div>
                                    <?php endif; ?>

                                    <input type="file" name="variant_image[]" accept="image/*">
                                    <input type="hidden" name="variant_image_old[]" value="<?= htmlspecialchars($v['image'] ?? '') ?>">
                                    <small style="font-size:0.7rem;color:#9ca3af;">Không chọn file nếu muốn giữ ảnh cũ.</small>
                                </div>
                                <div class="form-group">
                                    <label>Giá</label>
                                    <input type="number" name="variant_price[]" min="0" step="1000"
                                        value="<?= (int)$v['price'] ?>" placeholder="Giá bán">
                                </div>
                                <div class="form-group">
                                    <label>Giá cũ</label>
                                    <input type="number" name="variant_old_price[]" min="0" step="1000"
                                        value="<?= $v['old_price'] !== null ? (int)$v['old_price'] : '' ?>"
                                        placeholder="Giá gốc (nếu có)">
                                </div>
                                <div class="form-group">
                                    <label>Tồn kho</label>
                                    <div style="display:flex;gap:6px;align-items:center;">
                                        <input type="number" name="variant_stock[]" min="0" step="1"
                                            value="<?= (int)$v['stock'] ?>" placeholder="SL">
                                        <button type="button" class="btn btn-sm btn--ghost variant-remove-btn">
                                            <i class="fa-regular fa-trash-can"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <!-- 1 dòng trống mặc định khi chưa có biến thể -->
                        <div class="variant-row"
                            style="display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:8px;align-items:flex-end;margin-bottom:8px;">
                            <div class="form-group">
                                <label>Màu sắc</label>
                                <input type="text" name="variant_color[]" placeholder="Ví dụ: Xanh, Đen, Trắng...">
                            </div>
                            <div class="form-group">
                                <label>Dung lượng</label>
                                <input type="text" name="variant_storage[]" placeholder="Ví dụ: 128GB, 256GB...">
                            </div>
                            <div class="form-group">
                                <label>Ảnh biến thể</label>
                                <input type="file" name="variant_image[]" accept="image/*">
                                <input type="hidden" name="variant_image_old[]" value="">
                            </div>
                            <div class="form-group">
                                <label>Giá</label>
                                <input type="number" name="variant_price[]" min="0" step="1000" placeholder="Giá bán">
                            </div>
                            <div class="form-group">
                                <label>Giá cũ</label>
                                <input type="number" name="variant_old_price[]" min="0" step="1000" placeholder="Giá gốc (nếu có)">
                            </div>
                            <div class="form-group">
                                <label>Tồn kho</label>
                                <div style="display:flex;gap:6px;align-items:center;">
                                    <input type="number" name="variant_stock[]" min="0" step="1" placeholder="SL">
                                    <button type="button" class="btn btn-sm btn--ghost variant-remove-btn">
                                        <i class="fa-regular fa-trash-can"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <p style="font-size:0.78rem;color:#9ca3af;margin-top:4px;">
                    Nếu không sử dụng biến thể, bạn có thể để trống phần này và dùng giá / tồn kho ở phần trên.
                    Ảnh biến thể sẽ dùng cho trang chi tiết để đổi màu máy theo màu khách chọn.
                </p>
            </div>

            <div style="margin-top:14px;display:flex;gap:8px;flex-wrap:wrap;">
                <button type="submit" class="btn btn--primary">
                    <i class="fa-solid fa-floppy-disk" style="margin-right:4px;"></i> Lưu sản phẩm
                </button>
                <a href="products.php" class="btn btn--ghost">Hủy</a>
            </div>
        </form>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>

<script>
    // JS thêm / xóa dòng biến thể
    document.addEventListener('DOMContentLoaded', function() {
        const variantContainer = document.getElementById('variant-rows');
        const addBtn = document.getElementById('add-variant-btn');

        if (!variantContainer || !addBtn) return;

        function bindRemoveButtons() {
            const removeButtons = variantContainer.querySelectorAll('.variant-remove-btn');
            removeButtons.forEach(btn => {
                btn.onclick = function() {
                    const row = btn.closest('.variant-row');
                    if (!row) return;
                    // Ít nhất giữ lại 1 dòng
                    const totalRows = variantContainer.querySelectorAll('.variant-row').length;
                    if (totalRows > 1) {
                        row.remove();
                    } else {
                        row.querySelectorAll('input').forEach(i => {
                            // Reset giá trị cho dòng trống
                            if (i.type === 'file' || i.type === 'text' || i.type === 'number' || i.type === 'hidden') {
                                i.value = '';
                            }
                        });
                    }
                };
            });
        }

        addBtn.addEventListener('click', function() {
            const rows = variantContainer.querySelectorAll('.variant-row');
            const lastRow = rows[rows.length - 1];
            const newRow = lastRow.cloneNode(true);

            // Xóa giá trị inputs trong dòng mới
            newRow.querySelectorAll('input').forEach(i => {
                if (i.type === 'file' || i.type === 'text' || i.type === 'number' || i.type === 'hidden') {
                    i.value = '';
                }
            });

            variantContainer.appendChild(newRow);
            bindRemoveButtons();
        });

        bindRemoveButtons();
    });
</script>