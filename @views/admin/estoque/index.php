<?php $pageTitle = 'Estoque — Administração'; ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Estoque</h1>
        <p class="page-subtitle">Catálogo, permissões e consumo por OS</p>
    </div>
    <a href="<?= BASE_URL ?>/admin" class="btn btn-ghost"><i class="fas fa-arrow-left"></i> Painel admin</a>
</div>

<div class="kpi-grid">
    <a href="<?= BASE_URL ?>/admin/estoque/catalogo" class="kpi-card kpi-blue kpi-card-link">
        <div class="kpi-icon"><i class="fas fa-barcode"></i></div>
        <div class="kpi-body">
            <div class="kpi-value"><?= (int)$nItens ?></div>
            <div class="kpi-label">Itens no catálogo</div>
        </div>
    </a>
    <a href="<?= BASE_URL ?>/admin/estoque/usuarios" class="kpi-card kpi-green kpi-card-link">
        <div class="kpi-icon"><i class="fas fa-user-check"></i></div>
        <div class="kpi-body">
            <div class="kpi-value"><?= (int)$nAtivos ?></div>
            <div class="kpi-label">Usuários com estoque ativo</div>
        </div>
    </a>
    <a href="<?= BASE_URL ?>/admin/estoque/relatorio" class="kpi-card kpi-orange kpi-card-link">
        <div class="kpi-icon"><i class="fas fa-chart-line"></i></div>
        <div class="kpi-body">
            <div class="kpi-value"><i class="fas fa-arrow-right"></i></div>
            <div class="kpi-label">Relatório de consumo</div>
        </div>
    </a>
</div>

<?php if (!empty($ranking)): ?>
<div class="card" style="margin-top:1.25rem">
    <div class="card-header"><h3 class="card-title">Itens mais consumidos em OS (todos os tempos)</h3></div>
    <div class="card-body">
        <ul class="list-ranking">
            <?php foreach ($ranking as $r): ?>
            <li><strong><?= htmlspecialchars($r['codigo']) ?></strong> — <?= htmlspecialchars($r['nome']) ?>
                <span class="text-muted">· <?= rtrim(rtrim(number_format((float)$r['consumido'], 3, ',', '.'), '0'), ',') ?></span>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
<?php endif; ?>
