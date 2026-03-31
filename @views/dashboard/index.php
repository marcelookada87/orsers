<?php
$pageTitle = 'Dashboard';
$extraJs   = ['dashboard.js'];
?>
<?php $totalAtivos = ($contadores['aberta'] + $contadores['em_andamento'] + $contadores['aguardando']); ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Dashboard</h1>
        <p class="page-subtitle">Bem-vindo, <?= htmlspecialchars($user['nome']) ?>!</p>
    </div>
    <a href="<?= BASE_URL ?>/ordens/criar" class="btn btn-primary">
        <i class="fas fa-plus"></i> Nova OS
    </a>
</div>

<!-- KPI Cards -->
<div class="kpi-grid">
    <div class="kpi-card kpi-blue">
        <div class="kpi-icon"><i class="fas fa-folder-open"></i></div>
        <div class="kpi-body">
            <div class="kpi-value"><?= $contadores['aberta'] ?></div>
            <div class="kpi-label">Abertas</div>
        </div>
        <div class="kpi-trend">
            <a href="<?= BASE_URL ?>/ordens?status=aberta" class="kpi-link">Ver todas <i class="fas fa-arrow-right"></i></a>
        </div>
    </div>
    <div class="kpi-card kpi-orange">
        <div class="kpi-icon"><i class="fas fa-wrench"></i></div>
        <div class="kpi-body">
            <div class="kpi-value"><?= $contadores['em_andamento'] ?></div>
            <div class="kpi-label">Em Andamento</div>
        </div>
        <div class="kpi-trend">
            <a href="<?= BASE_URL ?>/ordens?status=em_andamento" class="kpi-link">Ver todas <i class="fas fa-arrow-right"></i></a>
        </div>
    </div>
    <div class="kpi-card kpi-green">
        <div class="kpi-icon"><i class="fas fa-check-circle"></i></div>
        <div class="kpi-body">
            <div class="kpi-value"><?= $finalizadasHoje ?></div>
            <div class="kpi-label">Finalizadas Hoje</div>
        </div>
    </div>
    <div class="kpi-card <?= $slaVencidas > 0 ? 'kpi-red' : 'kpi-gray' ?>">
        <div class="kpi-icon"><i class="fas fa-exclamation-triangle"></i></div>
        <div class="kpi-body">
            <div class="kpi-value"><?= $slaVencidas ?></div>
            <div class="kpi-label">SLA Vencido</div>
        </div>
        <div class="kpi-trend">
            <a href="<?= BASE_URL ?>/sla" class="kpi-link">Ver painel <i class="fas fa-arrow-right"></i></a>
        </div>
    </div>
</div>

<!-- Charts + Urgentes -->
<div class="dashboard-row">
    <div class="card chart-card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-chart-donut"></i> Distribuição de OS</h3>
        </div>
        <div class="card-body chart-body">
            <canvas id="chartStatus" width="260" height="260"></canvas>
            <div class="chart-legend" id="chartLegend"></div>
        </div>
    </div>

    <div class="card urgentes-card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-fire"></i> Mais urgentes</h3>
            <a href="<?= BASE_URL ?>/sla" class="card-link">Painel SLA</a>
        </div>
        <div class="card-body p0">
            <?php if (empty($abertas)): ?>
            <div class="empty-state">
                <i class="fas fa-check-double"></i>
                <p>Nenhuma OS urgente no momento</p>
            </div>
            <?php else: ?>
            <ul class="urgentes-list">
                <?php foreach ($abertas as $o):
                    $pct    = SLAHelper::percentual($o['data_abertura'], $o['sla_prazo']);
                    $classe = SLAHelper::cssClass($pct, $o['status']);
                    $label  = SLAHelper::label($pct, $o['status']);
                ?>
                <li class="urgente-item">
                    <a href="<?= BASE_URL ?>/ordens/<?= $o['id'] ?>" class="urgente-link">
                        <div class="urgente-info">
                            <span class="urgente-num"><?= htmlspecialchars($o['numero']) ?></span>
                            <span class="urgente-titulo"><?= htmlspecialchars($o['titulo']) ?></span>
                        </div>
                        <div class="sla-mini">
                            <div class="sla-bar-mini">
                                <div class="sla-fill <?= $classe ?>" style="width:<?= min($pct, 100) ?>%"></div>
                            </div>
                            <span class="sla-badge <?= $classe ?>"><?= $label ?></span>
                        </div>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Tabela de ordens recentes -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-list"></i> Ordens Recentes</h3>
        <a href="<?= BASE_URL ?>/ordens" class="card-link">Ver todas</a>
    </div>
    <div class="card-body p0">
        <div class="table-responsive">
            <table class="table table-datatable" data-dt-no-sort-last="1" data-dt-order-col="6" data-dt-order-dir="desc">
                <thead>
                    <tr>
                        <th>Número</th>
                        <th>Título</th>
                        <th>Cliente</th>
                        <th>Categoria</th>
                        <th>Status</th>
                        <th>Prioridade</th>
                        <th>Abertura</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentes as $o): ?>
                    <tr>
                        <td><code class="os-num"><?= htmlspecialchars($o['numero']) ?></code></td>
                        <td class="td-titulo"><?= htmlspecialchars($o['titulo']) ?></td>
                        <td><?= !empty($o['cliente_nome']) ? htmlspecialchars($o['cliente_nome']) : '—' ?></td>
                        <td><?= htmlspecialchars($o['categoria_nome']) ?></td>
                        <td><span class="badge badge-status badge-<?= $o['status'] ?>"><?= ucfirst(str_replace('_', ' ', $o['status'])) ?></span></td>
                        <td>
                            <span class="badge-prioridade" style="background:<?= htmlspecialchars($o['prioridade_cor']) ?>20;color:<?= htmlspecialchars($o['prioridade_cor']) ?>">
                                <?= htmlspecialchars($o['prioridade_nome']) ?>
                            </span>
                        </td>
                        <td class="td-date"><?= date('d/m/Y H:i', strtotime($o['data_abertura'])) ?></td>
                        <td>
                            <a href="<?= BASE_URL ?>/ordens/<?= $o['id'] ?>" class="btn-icon" title="Ver">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($recentes)): ?>
                    <tr><td colspan="8" class="text-center empty-td">Nenhuma OS cadastrada ainda.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
const chartData = {
    aberta:       <?= $contadores['aberta'] ?>,
    em_andamento: <?= $contadores['em_andamento'] ?>,
    aguardando:   <?= $contadores['aguardando'] ?>,
    finalizada:   <?= $contadores['finalizada'] ?>,
    cancelada:    <?= $contadores['cancelada'] ?>
};
</script>
