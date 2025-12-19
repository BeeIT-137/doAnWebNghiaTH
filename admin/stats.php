<?php
// admin/stats.php
require_once __DIR__ . '/../includes/admin_auth.php'; // protect admin stats page
$pdo = db(); // db connection

// Doanh thu theo ngày 7 ngày gần nhất
$revenueStmt = $pdo->query("
    SELECT DATE(created_at) AS order_date, SUM(total) AS total_revenue
    FROM orders
    WHERE status = 'completed'
      AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY DATE(created_at)
    ORDER BY order_date ASC
"); // revenue of last 7 days for completed orders
$revenueRows = $revenueStmt->fetchAll();

$labels = [];
$dataRevenue = [];
foreach ($revenueRows as $row) {
    $labels[]      = $row['order_date'];
    $dataRevenue[] = (float)$row['total_revenue'];
}

// Số đơn theo trạng thái (cho biểu đồ tròn)
$statusStmt = $pdo->query("
    SELECT status, COUNT(*) AS cnt
    FROM orders
    GROUP BY status
"); // count orders grouped by status
$statusRows = $statusStmt->fetchAll();

$statusLabels = [];
$statusData   = [];
foreach ($statusRows as $row) {
    $statusLabels[] = $row['status'];
    $statusData[]   = (int)$row['cnt'];
}

require_once __DIR__ . '/includes/header.php';
?>

<h2 style="margin-bottom:10px;">Thống kê</h2>

<div class="admin-charts">
    <div class="admin-chart-card">
        <h3 style="margin-bottom:6px;">Doanh thu theo ngày (7 ngày gần nhất)</h3>
        <canvas id="revenueChart" height="200"></canvas>
    </div>
    <div class="admin-chart-card">
        <h3 style="margin-bottom:6px;">Số đơn hàng theo trạng thái</h3>
        <canvas id="statusChart" height="200"></canvas>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const revenueCtx = document.getElementById('revenueChart');
        const statusCtx = document.getElementById('statusChart');

        if (revenueCtx && window.Chart) {
            new Chart(revenueCtx, {
                type: 'bar',
                data: {
                    labels: <?= json_encode($labels, JSON_UNESCAPED_UNICODE) ?>,
                    datasets: [{
                        label: 'Doanh thu (₫)',
                        data: <?= json_encode($dataRevenue) ?>,
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false
                        },
                    },
                    scales: {
                        x: {
                            ticks: {
                                color: '#e5e7eb'
                            }
                        },
                        y: {
                            ticks: {
                                color: '#e5e7eb',
                                callback: function(value) {
                                    return value.toLocaleString('vi-VN') + '₫';
                                }
                            }
                        }
                    }
                }
            });
        }

        if (statusCtx && window.Chart) {
            new Chart(statusCtx, {
                type: 'pie',
                data: {
                    labels: <?= json_encode($statusLabels, JSON_UNESCAPED_UNICODE) ?>,
                    datasets: [{
                        data: <?= json_encode($statusData) ?>,
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                color: '#e5e7eb'
                            }
                        }
                    }
                }
            });
        }
    });
</script>

<?php
require_once __DIR__ . '/includes/footer.php';
