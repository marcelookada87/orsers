<?php
$pageTitle = 'Painel SLA';
$extraJs   = ['sla-painel.js'];

$totalOrdens  = count($dados);
$vencidas     = count(array_filter($dados, fn($o) => $o['sla_percent'] > 100));
$criticas     = count(array_filter($dados, fn($o) => $o['sla_percent'] > 85 && $o['sla_percent'] <= 100));
$atencao      = count(array_filter($dados, fn($o) => $o['sla_percent'] > 60 && $o['sla_percent'] <= 85));
$noPrazo      = count(array_filter($dados, fn($o) => $o['sla_percent'] <= 60));
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Painel SLA</h1>
        <p class="page-subtitle">Monitoramento de acordos de nível de serviço em tempo real</p>
    </div>
    <button class="btn btn-ghost" id="btnRefreshSla" title="Atualizar">
        <i class="fas fa-sync-alt"></i> Atualizar
    </button>
</div>

<!-- SLA KPIs -->
<div class="sla-kpi-row">
    <div class="sla-kpi-card sla-overdue">
        <div class="sla-kpi-icon"><i class="fas fa-fire"></i></div>
        <div class="sla-kpi-count"><?= $vencidas ?></div>
        <div class="sla-kpi-label">Vencidas</div>
    </div>
    <div class="sla-kpi-card sla-danger">
        <div class="sla-kpi-icon"><i class="fas fa-exclamation-triangle"></i></div>
        <div class="sla-kpi-count"><?= $criticas ?></div>
        <div class="sla-kpi-label">Críticas (85-100%)</div>
    </div>
    <div class="sla-kpi-card sla-warning">
        <div class="sla-kpi-icon"><i class="fas fa-clock"></i></div>
        <div class="sla-kpi-count"><?= $atencao ?></div>
        <div class="sla-kpi-label">Atenção (60-85%)</div>
    </div>
    <div class="sla-kpi-card sla-ok">
        <div class="sla-kpi-icon"><i class="fas fa-check-circle"></i></div>
        <div class="sla-kpi-count"><?= $noPrazo ?></div>
        <div class="sla-kpi-label">No prazo</div>
    </div>
</div>

<!-- Cards SLA -->
<?php if (empty($dados)): ?>
<div class="card">
    <div class="card-body">
        <div class="empty-state">
            <i class="fas fa-check-double"></i>
            <p>Nenhuma ordem aberta no momento</p>
            <a href="<?= BASE_URL ?>/ordens/criar" class="btn btn-primary btn-sm">Criar OS</a>
        </div>
    </div>
</div>
<?php else: ?>

<!-- Legenda -->
<div class="sla-legend-row">
    <div class="sla-legend-item"><span class="sla-dot sla-ok"></span> No prazo (0–60%)</div>
    <div class="sla-legend-item"><span class="sla-dot sla-warning"></span> Atenção (60–85%)</div>
    <div class="sla-legend-item"><span class="sla-dot sla-danger"></span> Crítico (85–100%)</div>
    <div class="sla-legend-item"><span class="sla-dot sla-overdue"></span> Vencido (>100%)</div>
</div>

<div class="sla-cards-grid" id="slaCardsGrid">
    <?php foreach ($dados as $o): ?>
    <div class="sla-card <?= $o['sla_class'] ?>" data-id="<?= $o['id'] ?>">
        <div class="sla-card-header">
            <div class="sla-card-meta">
                <a href="<?= BASE_URL ?>/ordens/<?= $o['id'] ?>" class="sla-card-num">
                    <?= htmlspecialchars($o['numero']) ?>
                </a>
                <span class="badge-prioridade" style="background:<?= htmlspecialchars($o['prioridade_cor']) ?>20;color:<?= htmlspecialchars($o['prioridade_cor']) ?>">
                    <?= htmlspecialchars($o['prioridade_nome']) ?>
                </span>
            </div>
            <span class="sla-status-tag <?= $o['sla_class'] ?>"><?= $o['sla_label'] ?></span>
        </div>

        <div class="sla-card-titulo">
            <?= htmlspecialchars(mb_substr($o['titulo'], 0, 60)) ?><?= mb_strlen($o['titulo']) > 60 ? '…' : '' ?>
        </div>

        <div class="sla-card-info">
            <span><i class="fas fa-tag"></i> <?= htmlspecialchars($o['categoria_nome']) ?></span>
            <?php if (!empty($o['cliente_nome'])): ?>
            <span><i class="fas fa-building"></i> <?= htmlspecialchars($o['cliente_nome']) ?></span>
            <?php endif; ?>
            <span><i class="fas fa-user"></i> <?= htmlspecialchars($o['responsavel_nome'] ?? 'Sem responsável') ?></span>
        </div>

        <!-- Barra de progresso SLA -->
        <div class="sla-progress-wrapper">
            <div class="sla-progress-track">
                <div class="sla-progress-fill <?= $o['sla_class'] ?>"
                     style="width:<?= min($o['sla_percent'], 100) ?>%"
                     data-percent="<?= $o['sla_percent'] ?>">
                </div>
                <?php if ($o['sla_percent'] > 100): ?>
                <div class="sla-overflow-indicator">
                    <i class="fas fa-arrow-right"></i>
                </div>
                <?php endif; ?>
            </div>
            <div class="sla-progress-labels">
                <span class="sla-percent-label"><?= $o['sla_percent'] ?>%</span>
                <span class="sla-time-label <?= $o['sla_class'] ?>">
                    <?= $o['sla_percent'] > 100 ? '⚠️ ' : '' ?><?= $o['tempo_restante'] ?>
                </span>
            </div>
        </div>

        <?php
        $podeEditarPainel = Auth::isTecnico()
            || (int)($o['usuario_criador_id'] ?? 0) === (int)Auth::id();
        ?>
        <div class="sla-card-actions">
            <button type="button" class="btn btn-ghost btn-xs sla-card-act js-sla-modal-ver" data-ordem-id="<?= (int)$o['id'] ?>" title="Ver detalhes">
                <i class="fas fa-eye"></i><span class="sla-card-act-label">Ver</span>
            </button>
            <?php if ($podeEditarPainel): ?>
            <button type="button" class="btn btn-ghost btn-xs sla-card-act js-sla-modal-editar" data-ordem-id="<?= (int)$o['id'] ?>" title="Editar OS">
                <i class="fas fa-pen"></i><span class="sla-card-act-label">Editar</span>
            </button>
            <?php endif; ?>
            <button type="button" class="btn btn-ghost btn-xs sla-card-act js-sla-modal-comentar" data-ordem-id="<?= (int)$o['id'] ?>" title="Comentários">
                <i class="fas fa-comments"></i><span class="sla-card-act-label">Comentar</span>
            </button>
            <?php if (Auth::isTecnico()): ?>
            <button type="button" class="btn btn-ghost btn-xs sla-card-act sla-card-act-warn js-sla-modal-reset" data-ordem-id="<?= (int)$o['id'] ?>" title="Renovar prazo de SLA">
                <i class="fas fa-redo-alt"></i><span class="sla-card-act-label">Reset SLA</span>
            </button>
            <button type="button" class="btn btn-primary btn-xs sla-card-act js-sla-modal-concluir" data-ordem-id="<?= (int)$o['id'] ?>" title="Finalizar OS">
                <i class="fas fa-check"></i><span class="sla-card-act-label">Concluir</span>
            </button>
            <?php endif; ?>
        </div>

        <div class="sla-card-footer">
            <span><i class="fas fa-hourglass-half"></i> Aberta há <?= $o['tempo_aberto'] ?></span>
            <span class="badge badge-status badge-<?= $o['status'] ?>"><?= ucfirst(str_replace('_', ' ', $o['status'])) ?></span>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="sla-modal-host" id="slaModalHost" aria-hidden="true">
    <div class="sla-modal-backdrop js-sla-modal-close"></div>
    <div class="sla-modal-shell" role="dialog" aria-modal="true" aria-labelledby="slaModalTitle">
        <div class="sla-modal-glow"></div>
        <div class="sla-modal-inner">
            <header class="sla-modal-head">
                <h2 id="slaModalTitle" class="sla-modal-title"><i class="fas fa-layer-group"></i> <span id="slaModalTitleText">Painel SLA</span></h2>
                <button type="button" class="sla-modal-close js-sla-modal-close" aria-label="Fechar">&times;</button>
            </header>
            <div id="slaModalBody" class="sla-modal-body">
                <div class="sla-modal-loading" id="slaModalLoading">
                    <span class="sla-modal-spinner"></span>
                    <span>Carregando…</span>
                </div>
                <div id="slaModalContent" class="sla-modal-content" hidden></div>
            </div>
        </div>
    </div>
</div>
