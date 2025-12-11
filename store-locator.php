<?php
// store-locator.php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/header.php';

// Danh sách cửa hàng demo + mã tỉnh/thành để lọc
$stores = [
    [
        'name'    => 'TechPhone Quận 1',
        'address' => '123 Đường Công Nghệ, Quận 1, TP. HCM',
        'phone'   => '028 1234 5678',
        'hours'   => '8:00 - 22:00',
        'city'    => 'hcm',
    ],
    [
        'name'    => 'TechPhone Quận 7',
        'address' => '456 Đường Số 7, Quận 7, TP. HCM',
        'phone'   => '028 2345 6789',
        'hours'   => '8:00 - 22:00',
        'city'    => 'hcm',
    ],
    [
        'name'    => 'TechPhone Hà Nội',
        'address' => '789 Phố Công Nghệ, Quận Cầu Giấy, Hà Nội',
        'phone'   => '024 3456 7890',
        'hours'   => '8:30 - 22:00',
        'city'    => 'hn',
    ],
    [
        'name'    => 'TechPhone Đà Nẵng',
        'address' => '99 Đường Biển, Sơn Trà, Đà Nẵng',
        'phone'   => '0236 111 222',
        'hours'   => '8:30 - 22:00',
        'city'    => 'dn',
    ],
];
?>

<section class="section">
    <div class="section-header">
        <h2>Cửa hàng gần bạn</h2>
    </div>

    <div class="form-card store-filter">
        <h3>Tìm cửa hàng</h3>
        <p class="store-filter__note">
            Chọn tỉnh/thành phố để xem cửa hàng TechPhone gần bạn
        </p>
        <div class="store-filter__controls">
            <label for="store-city" class="sr-only">Chọn tỉnh/thành</label>
            <select id="store-city" class="store-filter__select">
                <option value="all">Tất cả</option>
                <option value="hcm">TP. Hồ Chí Minh</option>
                <option value="hn">Hà Nội</option>
                <option value="dn">Đà Nẵng</option>
            </select>
        </div>
    </div>

    <div class="store-grid" id="store-list">
        <?php foreach ($stores as $store): ?>
            <article class="store-card" data-city="<?= htmlspecialchars($store['city']) ?>">
                <h3><?= htmlspecialchars($store['name']) ?></h3>
                <p><i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars($store['address']) ?></p>
                <p><i class="fa-solid fa-phone"></i> <?= htmlspecialchars($store['phone']) ?></p>
                <p><i class="fa-solid fa-clock"></i> Giờ mở cửa: <?= htmlspecialchars($store['hours']) ?></p>
            </article>
        <?php endforeach; ?>
    </div>

    <div class="store-empty" id="store-empty" hidden>
        <i class="fa-solid fa-store-slash"></i> Không tìm thấy cửa hàng phù hợp.
    </div>
</section>

<script>
    // JS lọc cửa hàng theo tỉnh/thành
    document.addEventListener('DOMContentLoaded', function() {
        const selectCity = document.getElementById('store-city');
        const cards = document.querySelectorAll('.store-card');
        const emptyBox = document.getElementById('store-empty');

        if (!selectCity || !cards.length) return;

        function filterStores() {
            const value = selectCity.value;
            let visibleCount = 0;

            cards.forEach(card => {
                const city = card.getAttribute('data-city');
                if (value === 'all' || city === value) {
                    card.style.display = '';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            if (emptyBox) {
                emptyBox.hidden = visibleCount > 0;
            }
        }

        // Lọc luôn khi đổi select (cho tiện)
        selectCity.addEventListener('change', filterStores);

        // Gọi lần đầu để áp dụng (ví dụ mặc định là "all")
        filterStores();
    });
</script>

<?php
require_once __DIR__ . '/includes/footer.php';
