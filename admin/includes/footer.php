        </main> <!-- .admin-main__inner -->
    </div> <!-- .admin-main -->
</div> <!-- .admin-layout -->

<!-- Chart.js cho trang thống kê -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Toggle sidebar admin trên mobile -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const layout   = document.querySelector('.admin-layout');
    const btnMenu  = document.getElementById('adminMenuToggle');
    const backdrop = document.getElementById('adminSidebarBackdrop');

    if (!layout || !btnMenu) return;

    function toggleMenu() {
        layout.classList.toggle('admin-layout--menu-open');
    }

    btnMenu.addEventListener('click', toggleMenu);

    if (backdrop) {
        backdrop.addEventListener('click', function () {
            layout.classList.remove('admin-layout--menu-open');
        });
    }
});
</script>

</body>
</html>
