<?php
$pageTitle = 'Relatórios';
$fmtLim = static fn ($v) => ($v === null || $v === '') ? '∞' : (string)(int)$v;
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Relatórios</h1>
        <p class="page-subtitle">Uso por usuário e resumo financeiro (mês atual)</p>
    </div>
    <a href="<?= BASE_URL ?>/admin" class="btn btn-ghost">
        <i class="fas fa-arrow-left"></i> Painel admin
    </a>
</div>

<div class="kpi-grid">
    <div class="kpi-card kpi-blue">
        <div class="kpi-icon"><i class="fas fa-folder-open"></i></div>
        <div class="kpi-body">
            <div class="kpi-value"><?= (int)($contadores['aberta'] ?? 0) ?></div>
            <div class="kpi-label">OS abertas (todas)</div>
        </div>
    </div>
    <div class="kpi-card kpi-green">
        <div class="kpi-icon"><i class="fas fa-check-circle"></i></div>
        <div class="kpi-body">
            <div class="kpi-value"><?= (int)($contadores['finalizada'] ?? 0) ?></div>
            <div class="kpi-label">Finalizadas (todas)</div>
        </div>
    </div>
    <div class="kpi-card kpi-orange">
        <div class="kpi-icon"><i class="fas fa-file-invoice-dollar"></i></div>
        <div class="kpi-body">
            <div class="kpi-value">R$ <?= number_format($fin['valor_servico'] ?? 0, 2, ',', '.') ?></div>
            <div class="kpi-label">Soma valor serviço (<?= htmlspecialchars($ini) ?> — <?= htmlspecialchars($fim) ?>)</div>
        </div>
    </div>
    <div class="kpi-card kpi-gray">
        <div class="kpi-icon"><i class="fas fa-money-bill-wave"></i></div>
        <div class="kpi-body">
            <div class="kpi-value">R$ <?= number_format($fin['valor_pago'] ?? 0, 2, ',', '.') ?></div>
            <div class="kpi-label">Soma valor pago (período)</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-user-clock"></i> Uso por usuário e plano</h3>
    </div>
    <div class="card-body p0">
        <div class="table-responsive">
            <table class="table table-hover table-datatable">
                <thead>
                    <tr>
                        <th>Usuário</th>
                        <th>Perfil</th>
                        <th>Plano</th>
                        <th>Clientes</th>
                        <th>OS (mês)</th>
                        <th>Limites (plano)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($linhas as $row): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($row['nome']) ?></strong><br>
                            <small class="text-muted"><?= htmlspecialchars($row['email']) ?></small>
                        </td>
                        <td><span class="badge badge-perfil badge-<?= htmlspecialchars($row['perfil']) ?>"><?= ucfirst($row['perfil']) ?></span></td>
                        <td><?= htmlspecialchars($row['plano_nome']) ?> <small class="text-muted">(<?= htmlspecialchars($row['plano_codigo']) ?>)</small></td>
                        <td><?= (int)$row['qtd_clientes'] ?></td>
                        <td><?= (int)$row['os_mes_criadas'] ?></td>
                        <td>
                            Img <?= $fmtLim($row['plano_max_imagens_por_os'] ?? null) ?> ·
                            Cli <?= $fmtLim($row['plano_max_clientes'] ?? null) ?> ·
                            OS <?= $fmtLim($row['plano_max_os_mes'] ?? null) ?>
                            <?php
                            $temOv = ($row['max_imagens_por_os_override'] ?? null) !== null
                                || ($row['max_clientes_override'] ?? null) !== null
                                || ($row['max_os_mes_override'] ?? null) !== null;
                            if ($temOv):
                            ?>
                            <br><small class="text-warning">Overrides ativos</small>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
