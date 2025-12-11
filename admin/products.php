<?php
// admin/products.php
// Quản lý sản phẩm + hiển thị thông tin biến thể

require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../includes/functions.php';


$pdo = db();

// Xử lý xóa sản phẩm
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$id]);
    }
    redirect('products.php');
}

// Tìm kiếm theo tên / ID
$search = trim($_GET['q'] ?? '');

// Lấy sản phẩm + danh mục + thông tin biến thể (đếm, min/max giá, tổng stock)
$sql = "
    SELECT 
        p.*,
        c.name AS category_name,
        COALESCE(v.variant_count, 0)  AS variant_count,
        COALESCE(v.min_price,  NULL) AS variant_min_price,
        COALESCE(v.max_price,  NULL) AS variant_max_price,
        COALESCE(v.total_stock, NULL) AS variant_total_stock
    FROM products p
    JOIN categories c ON p.category_id = c.id
    LEFT JOIN (
        SELECT 
            product_id,
            COUNT(*)        AS variant_count,
            MIN(price)      AS min_price,
            MAX(price)      AS max_price,
            SUM(stock)      AS total_stock
        FROM product_variants
        GROUP BY product_id
    ) v ON v.product_id = p.id
";

$params = [];
if ($search !== '') {
    $sql .= " WHERE p.id = :idExact OR p.name LIKE :kw";
    $params[':idExact'] = (int)$search;
    $params[':kw']      = '%' . $search . '%';
}

$sql .= " ORDER BY p.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/includes/header.php';
?>

<div class="admin-main__inner">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;gap:8px;flex-wrap:wrap;">
        <h2 style="margin:0;font-size:1.1rem;">Quản lý sản phẩm</h2>

        <!-- Thêm mới: dùng luôn product-edit.php (không có id) -->
        <a href="product-edit.php" class="btn btn--primary">
            <i class="fa-solid fa-plus" style="margin-right:4px;"></i> Thêm sản phẩm
        </a>
    </div>

    <!-- Thống kê nhanh -->
    <div class="admin-cards" style="margin-bottom:10px;">
        <div class="admin-card">
            <div class="admin-card__label">Tổng sản phẩm</div>
            <div class="admin-card__value"><?= count($products) ?></div>
            <div class="admin-card__icon"><i class="fa-solid fa-mobile-screen-button"></i></div>
        </div>
        <div class="admin-card">
            <div class="admin-card__label">Có biến thể</div>
            <div class="admin-card__value">
                <?php
                $hasVariants = 0;
                foreach ($products as $p) {
                    if ((int)$p['variant_count'] > 0) {
                        $hasVariants++;
                    }
                }
                echo $hasVariants;
                ?>
            </div>
            <div class="admin-card__icon"><i class="fa-solid fa-layer-group"></i></div>
        </div>
        <div class="admin-card">
            <div class="admin-card__label">Không biến thể</div>
            <div class="admin-card__value">
                <?= max(0, count($products) - $hasVariants) ?>
            </div>
            <div class="admin-card__icon"><i class="fa-regular fa-square"></i></div>
        </div>
    </div>

    <!-- Tìm kiếm sản phẩm -->
    <div class="admin-form" style="margin-bottom:10px;">
        <form method="get" style="display:flex;flex-wrap:wrap;gap:8px;align-items:flex-end;">
            <div class="form-group" style="flex:1;min-width:220px;">
                <label>Tìm kiếm sản phẩm</label>
                <input
                    type="text"
                    name="q"
                    value="<?= htmlspecialchars($search) ?>"
                    placeholder="Nhập ID hoặc tên sản phẩm...">
                <small>Ví dụ: 15, &quot;iPhone&quot;, &quot;Samsung&quot;...</small>
            </div>
            <div>
                <button type="submit" class="btn btn--primary">
                    <i class="fa-solid fa-magnifying-glass"></i> Tìm kiếm
                </button>
            </div>
        </form>
    </div>

    <!-- Bảng sản phẩm -->
    <div class="admin-table-wrap">
        <table class="admin-table admin-table--products">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Sản phẩm</th>
                    <th>Danh mục</th>
                    <th>Giá / biến thể</th>
                    <th>Kho</th>
                    <th>Ngày tạo</th>
                    <th style="text-align:right;">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="7" style="text-align:center;font-size:0.9rem;color:#9ca3af;">
                            Chưa có sản phẩm nào.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($products as $p): ?>
                        <?php
                        $variantCount = (int)$p['variant_count'];
                        $minPrice     = $p['variant_min_price'] !== null ? (int)$p['variant_min_price'] : null;
                        $maxPrice     = $p['variant_max_price'] !== null ? (int)$p['variant_max_price'] : null;

                        // Giá hiển thị
                        if ($variantCount > 0 && $minPrice !== null) {
                            $priceDisplay = 'Từ ' . number_format($minPrice, 0, ',', '.') . '₫';
                            if ($maxPrice !== null && $maxPrice !== $minPrice) {
                                $priceDisplay .= ' - ' . number_format($maxPrice, 0, ',', '.') . '₫';
                            }
                        } else {
                            $priceDisplay = number_format($p['price'], 0, ',', '.') . '₫';
                        }

                        // Kho hiển thị
                        if ($variantCount > 0 && $p['variant_total_stock'] !== null) {
                            $stockDisplay = (int)$p['variant_total_stock'] . ' (biến thể)';
                        } else {
                            $stockDisplay = (int)$p['stock'];
                        }
                        ?>
                        <tr>
                            <td>
                                <span class="badge badge--id">#<?= (int)$p['id'] ?></span>
                            </td>
                            <td>
                                <div style="font-size:0.9rem;font-weight:500;">
                                    <?= htmlspecialchars($p['name']) ?>
                                </div>
                                <?php if ($variantCount > 0): ?>
                                    <div style="font-size:0.8rem;color:#6b7280;margin-top:2px;">
                                        <?= $variantCount ?> biến thể (màu / dung lượng)
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($p['category_name']) ?></td>
                            <td><?= $priceDisplay ?></td>
                            <td><?= $stockDisplay ?></td>
                            <td style="font-size:0.8rem;color:#9ca3af;">
                                <?= htmlspecialchars($p['created_at']) ?>
                            </td>
                            <td style="text-align:right;">
                                <a href="product-edit.php?id=<?= (int)$p['id'] ?>" class="btn btn--ghost btn-sm" style="margin-right:4px;">
                                    <i class="fa-regular fa-pen-to-square"></i> Sửa
                                </a>

                                <form method="post" style="display:inline;"
                                    onsubmit="return confirm('Bạn chắc chắn muốn xóa sản phẩm này?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                                    <button type="submit" class="btn btn-sm"
                                        style="border-radius:999px;border:1px solid #fecaca;background:#fef2f2;color:#b91c1c;">
                                        <i class="fa-regular fa-trash-can"></i> Xóa
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
