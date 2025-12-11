<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/header.php';

$labRoot = __DIR__ . '/lab';

function lab_build_link(array $segments): string
{
    return 'lab/' . implode('/', array_map('rawurlencode', $segments));
}

function lab_scan(string $dir, array $segments = []): array
{
    $nodes = [];
    $entries = @scandir($dir);
    if ($entries === false) {
        return $nodes;
    }

    foreach ($entries as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }
        $full = $dir . '/' . $entry;
        $seg  = array_merge($segments, [$entry]);

        if (is_dir($full)) {
            $nodes[] = [
                'type'     => 'dir',
                'name'     => $entry,
                'segments' => $seg,
                'children' => lab_scan($full, $seg),
            ];
        } elseif (is_file($full)) {
            $nodes[] = [
                'type'     => 'file',
                'name'     => $entry,
                'segments' => $seg,
            ];
        }
    }

    usort($nodes, function ($a, $b) {
        if ($a['type'] !== $b['type']) {
            return $a['type'] === 'dir' ? -1 : 1;
        }
        return strnatcasecmp($a['name'], $b['name']);
    });

    return $nodes;
}

function lab_normalize_segments(string $param): array
{
    $parts = array_filter(array_map('trim', explode('/', $param)), 'strlen');
    $safe  = [];
    foreach ($parts as $p) {
        // loại bỏ .. và ký tự lạ
        $p = str_replace(['..', '\\'], '', $p);
        if ($p !== '') {
            $safe[] = $p;
        }
    }
    return $safe;
}

$labExists = is_dir($labRoot);
$labTree   = $labExists ? lab_scan($labRoot) : [];

$dirParam   = $_GET['dir'] ?? '';
$segments   = lab_normalize_segments($dirParam);
$isDetail   = !empty($segments);
$currentDir = null;
$childNodes = [];
$errorMsg   = '';

if ($isDetail && $labExists) {
    $targetPath = $labRoot . '/' . implode('/', $segments);
    $real = realpath($targetPath);
    if ($real && strpos($real, realpath($labRoot)) === 0 && is_dir($real)) {
        $currentDir = $segments;
        $childNodes = lab_scan($real, $segments);
    } else {
        $errorMsg = 'Thư mục không hợp lệ hoặc không tồn tại.';
        $isDetail = false;
    }
}

// Top-level directories (buổi)
$topDirs = array_values(array_filter($labTree, fn($n) => $n['type'] === 'dir'));
?>

<style>
.lab-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 12px;
}
.lab-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 12px;
    box-shadow: 0 6px 16px rgba(15,23,42,0.05);
    display: flex;
    gap: 10px;
    align-items: center;
    transition: box-shadow 0.15s ease, transform 0.12s ease;
}
.lab-card:hover {
    box-shadow: 0 10px 24px rgba(15,23,42,0.08);
    transform: translateY(-1px);
}
.lab-card__index {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    background: #f3f4f6;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    color: #1f2937;
    flex-shrink: 0;
}
.lab-card__body h4 {
    margin: 0;
    font-size: 1rem;
    font-weight: 700;
    color: #111827;
}
.lab-card__body .lab-sub {
    font-size: 0.85rem;
    color: #6b7280;
}
.lab-list {
    list-style: none;
    margin: 8px 0;
    padding: 0;
}
.lab-list li {
    margin-bottom: 6px;
    font-size: 0.95rem;
    display: flex;
    align-items: center;
    gap: 6px;
}
.lab-list i {
    color: #6b7280;
}
.breadcrumb {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    font-size: 0.9rem;
    margin-bottom: 10px;
}
.breadcrumb a {
    color: #2563eb;
}
.lab-empty {
    font-size: 0.9rem;
    color: #6b7280;
}
</style>

<section class="section">
    <div class="section-header">
        <h2>Lab</h2>
        <span style="color:#6b7280;font-size:0.9rem;">Bài tập theo buổi</span>
    </div>

    <?php if (!$labExists): ?>
        <div class="alert alert--warning">Thư mục <code>lab/</code> chưa tồn tại.</div>
    <?php elseif ($isDetail): ?>
        <div class="breadcrumb">
            <a href="lab.php">Lab</a>
            <?php $path = []; foreach ($currentDir as $seg): $path[] = $seg; ?>
                <span>/</span>
                <a href="lab.php?dir=<?= htmlspecialchars(implode('/', $path)) ?>"><?= htmlspecialchars($seg) ?></a>
            <?php endforeach; ?>
        </div>
        <div style="margin-bottom:8px;">
            <a href="lab.php" class="btn btn--ghost btn-sm">
                &larr; Quay về danh sách buổi
            </a>
        </div>
        <h3 style="margin-bottom:8px;">Nội dung <?= htmlspecialchars(end($currentDir)) ?></h3>
        <?php if (empty($childNodes)): ?>
            <div class="lab-empty">Không có file/thư mục con.</div>
        <?php else: ?>
            <ul class="lab-list">
                <?php foreach ($childNodes as $node): ?>
                    <?php if ($node['type'] === 'dir'): ?>
                        <li>
                            <i class="fa-solid fa-folder"></i>
                            <a href="lab.php?dir=<?= htmlspecialchars(implode('/', $node['segments'])) ?>">
                                <?= htmlspecialchars($node['name']) ?>
                            </a>
                        </li>
                    <?php else: ?>
                        <li>
                            <i class="fa-regular fa-file"></i>
                            <a href="<?= htmlspecialchars(lab_build_link($node['segments'])) ?>" target="_blank">
                                <?= htmlspecialchars($node['name']) ?>
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    <?php else: ?>
        <?php if (empty($topDirs)): ?>
            <div class="lab-empty">Không tìm thấy thư mục buổi nào.</div>
        <?php else: ?>
            <div class="lab-grid">
                <?php foreach ($topDirs as $idx => $dir): ?>
                    <a class="lab-card" href="lab.php?dir=<?= htmlspecialchars(implode('/', $dir['segments'])) ?>">
                        <div class="lab-card__index"><?= $idx + 1 ?></div>
                        <div class="lab-card__body">
                            <h4>Bài tập <?= htmlspecialchars($dir['name']) ?></h4>
                            <div class="lab-sub">Xem chi tiết →</div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</section>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
