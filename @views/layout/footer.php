    </main>
</div>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<script src="<?= BASE_URL ?>/assets/js/vendor/jquery-3.7.1.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/vendor/jquery.dataTables.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
<?php if (!empty($extraJs)): foreach ($extraJs as $js): ?>
<script src="<?= BASE_URL ?>/assets/js/<?= $js ?>"></script>
<?php endforeach; endif; ?>
<script src="<?= BASE_URL ?>/assets/js/datatables-init.js"></script>
</body>
</html>
