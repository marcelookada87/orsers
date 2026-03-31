<?php $pageTitle = 'Ordens de Serviço'; ?>

<div class="page-ordens">
<div class="page-header">
    <div>
        <h1 class="page-title">Ordens de Serviço</h1>
        <p class="page-subtitle">Gerencie todas as ordens do sistema</p>
    </div>
    <a href="<?= BASE_URL ?>/ordens/criar" class="btn btn-primary">
        <i class="fas fa-plus"></i> Nova OS
    </a>
</div>

<!-- Filtros -->
<div class="card filter-card">
    <form method="GET" action="<?= BASE_URL ?>/ordens" class="filter-form">
        <div class="filter-row">
            <div class="filter-group">
                <label>Buscar</label>
                <div class="input-icon-wrapper">
                    <i class="fas fa-search input-icon"></i>
                    <input type="text" name="busca" class="form-control"
                           placeholder="Número, título ou cliente..." value="<?= htmlspecialchars($filtros['busca'] ?? '') ?>">
                </div>
            </div>
            <div class="filter-group">
                <label>Status</label>
                <select name="status" class="form-control">
                    <option value="">Todos</option>
                    <?php foreach (['aberta','em_andamento','aguardando','finalizada','cancelada'] as $s): ?>
                    <option value="<?= $s ?>" <?= ($filtros['status'] ?? '') === $s ? 'selected' : '' ?>>
                        <?= ucfirst(str_replace('_', ' ', $s)) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label>Categoria</label>
                <select name="categoria_id" class="form-control">
                    <option value="">Todas</option>
                    <?php foreach ($categorias as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= ($filtros['categoria_id'] ?? 0) == $c['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['nome']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label>Prioridade</label>
                <select name="prioridade_id" class="form-control">
                    <option value="">Todas</option>
                    <?php foreach ($prioridades as $p): ?>
                    <option value="<?= $p['id'] ?>" <?= ($filtros['prioridade_id'] ?? 0) == $p['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($p['nome']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label>Cliente</label>
                <select name="cliente_id" class="form-control">
                    <option value="">Todos</option>
                    <?php foreach ($clientesFiltro as $cl): ?>
                    <option value="<?= (int)$cl['id'] ?>" <?= (int)($filtros['cliente_id'] ?? 0) === (int)$cl['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cl['nome_razao_social']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="fas fa-filter"></i> Filtrar
                </button>
                <a href="<?= BASE_URL ?>/ordens" class="btn btn-ghost btn-sm">Limpar</a>
            </div>
        </div>
    </form>
</div>

<!-- Tabela -->
<div class="card">
    <div class="card-body p0">
        <div class="table-responsive">
            <table class="table table-hover table-datatable table-os-list" data-dt-no-sort-last="1" data-dt-order-col="7" data-dt-order-dir="desc">
                <thead>
                    <tr>
                        <th>Número</th>
                        <th>Título</th>
                        <th>Cliente</th>
                        <th>Categoria</th>
                        <th>Status</th>
                        <th>Prioridade</th>
                        <th>SLA</th>
                        <th>Abertura</th>
                        <th>Vl. serviço</th>
                        <th>Vl. pago</th>
                        <th>Pagamento</th>
                        <th>Encerramento</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ordens as $o):
                        $pct    = SLAHelper::percentual($o['data_abertura'], $o['sla_prazo']);
                        $classe = SLAHelper::cssClass($pct, $o['status']);
                        $motivoTxt = trim((string)($o['motivo_finalizacao'] ?? ''));
                        $motivoRes = '';
                        if ($motivoTxt !== '') {
                            $motivoRes = (function_exists('mb_strlen') && mb_strlen($motivoTxt) > 40)
                                ? mb_substr($motivoTxt, 0, 40) . '…'
                                : ((strlen($motivoTxt) > 40) ? substr($motivoTxt, 0, 40) . '…' : $motivoTxt);
                        }
                        $dtFimAttr = date('Y-m-d\TH:i');
                    ?>
                    <tr>
                        <td>
                            <a href="<?= BASE_URL ?>/ordens/<?= $o['id'] ?>" class="os-num-link">
                                <?= htmlspecialchars($o['numero']) ?>
                            </a>
                        </td>
                        <td class="td-titulo">
                            <a href="<?= BASE_URL ?>/ordens/<?= $o['id'] ?>" class="td-link">
                                <?= htmlspecialchars($o['titulo']) ?>
                            </a>
                        </td>
                        <td class="td-cliente">
                            <?php if (!empty($o['cliente_nome'])): ?>
                            <span title="<?= htmlspecialchars($o['cliente_fantasia'] ?? '') ?>"><?= htmlspecialchars($o['cliente_nome']) ?></span>
                            <?php else: ?>
                            <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="cat-badge" style="background:<?= htmlspecialchars($o['categoria_cor']) ?>20;color:<?= htmlspecialchars($o['categoria_cor']) ?>">
                                <?= htmlspecialchars($o['categoria_nome']) ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge badge-status badge-<?= $o['status'] ?>">
                                <?= ucfirst(str_replace('_', ' ', $o['status'])) ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge-prioridade" style="background:<?= htmlspecialchars($o['prioridade_cor']) ?>20;color:<?= htmlspecialchars($o['prioridade_cor']) ?>">
                                <?= htmlspecialchars($o['prioridade_nome']) ?>
                            </span>
                        </td>
                        <td>
                            <div class="sla-cell">
                                <div class="sla-bar-mini">
                                    <div class="sla-fill <?= $classe ?>" style="width:<?= min($pct, 100) ?>%"></div>
                                </div>
                                <span class="sla-pct <?= $classe ?>"><?= $pct ?>%</span>
                            </div>
                        </td>
                        <td class="td-date"><?= date('d/m/Y', strtotime($o['data_abertura'])) ?></td>
                        <td class="td-moeda"><?= Ordem::formatarMoeda(isset($o['valor_servico']) && $o['valor_servico'] !== '' ? (float)$o['valor_servico'] : null) ?></td>
                        <td class="td-moeda"><?= Ordem::formatarMoeda(isset($o['valor_pago']) && $o['valor_pago'] !== '' ? (float)$o['valor_pago'] : null) ?></td>
                        <td class="td-pgto"><?= htmlspecialchars(Ordem::labelFormaPagamento($o['forma_pagamento'] ?? null)) ?></td>
                        <td class="td-encerramento">
                            <?php if (!empty($o['data_finalizacao'])): ?>
                            <div class="enc-data"><?= date('d/m/Y H:i', strtotime($o['data_finalizacao'])) ?></div>
                            <?php if ($motivoRes !== ''): ?>
                            <div class="enc-motivo text-muted" title="<?= htmlspecialchars($motivoTxt) ?>"><?= htmlspecialchars($motivoRes) ?></div>
                            <?php endif; ?>
                            <?php else: ?>
                            <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="td-actions">
                            <a href="<?= BASE_URL ?>/ordens/<?= $o['id'] ?>" class="btn-icon" title="Ver">
                                <i class="fas fa-eye"></i>
                            </a>
                            <?php if (!in_array($o['status'], ['finalizada','cancelada'])): ?>
                            <a href="<?= BASE_URL ?>/ordens/<?= $o['id'] ?>/editar" class="btn-icon" title="Editar">
                                <i class="fas fa-edit"></i>
                            </a>
                            <?php endif; ?>
                            <?php if (Auth::isTecnico() && !in_array($o['status'], ['finalizada','cancelada'])): ?>
                            <button type="button" class="btn-icon btn-finalizar-os" title="Finalizar OS"
                                    data-action="finalizar-os"
                                    data-os-id="<?= (int)$o['id'] ?>"
                                    data-os-num="<?= htmlspecialchars($o['numero'], ENT_QUOTES, 'UTF-8') ?>"
                                    data-default-dt="<?= htmlspecialchars($dtFimAttr, ENT_QUOTES, 'UTF-8') ?>">
                                <i class="fas fa-flag-checkered"></i>
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($ordens)): ?>
                    <tr>
                        <td colspan="13" class="empty-td">
                            <div class="empty-state">
                                <i class="fas fa-clipboard-list"></i>
                                <p>Nenhuma ordem encontrada</p>
                                <a href="<?= BASE_URL ?>/ordens/criar" class="btn btn-primary btn-sm">Criar primeira OS</a>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if (Auth::isTecnico()): ?>
<?php require __DIR__ . '/_modal_finalizar_os.php'; ?>
<?php endif; ?>
</div>
