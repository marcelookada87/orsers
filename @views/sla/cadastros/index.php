<?php
$pageTitle = 'Cadastro de SLA';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Cadastro de SLA</h1>
        <p class="page-subtitle">
            O prazo de cada OS é calculado assim:
            <strong>SLA (horas) = horas da categoria × multiplicador da prioridade</strong>,
            a partir da data de abertura.
        </p>
    </div>
    <a href="<?= BASE_URL ?>/sla" class="btn btn-ghost">
        <i class="fas fa-stopwatch"></i> Painel SLA
    </a>
</div>

<div class="sla-cadastro-grid">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-layer-group"></i> Categorias</h3>
            <a href="<?= BASE_URL ?>/sla/categorias/criar" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Nova categoria
            </a>
        </div>
        <div class="card-body p0">
            <div class="table-responsive">
                <table class="table table-hover table-datatable" id="dtSlaCategorias" data-dt-no-sort-last="1">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>SLA base (h)</th>
                            <th>Cor</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categorias as $c): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($c['nome']) ?></strong>
                                <?php if (!empty($c['descricao'])): ?>
                                <br><small class="text-muted"><?= htmlspecialchars($c['descricao']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars((string)$c['sla_horas']) ?></td>
                            <td><span class="sla-color-dot" style="background:<?= htmlspecialchars($c['cor']) ?>"></span> <?= htmlspecialchars($c['cor']) ?></td>
                            <td><?= !empty($c['ativo']) ? '<span class="badge badge-success">Ativa</span>' : '<span class="badge badge-muted">Inativa</span>' ?></td>
                            <td>
                                <a href="<?= BASE_URL ?>/sla/categorias/<?= (int)$c['id'] ?>/editar" class="btn-icon" title="Editar"><i class="fas fa-edit"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($categorias)): ?>
                        <tr><td colspan="5" class="empty-td text-center">Nenhuma categoria.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-flag"></i> Prioridades</h3>
            <a href="<?= BASE_URL ?>/sla/prioridades/criar" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Nova prioridade
            </a>
        </div>
        <div class="card-body p0">
            <div class="table-responsive">
                <table class="table table-hover table-datatable" id="dtSlaPrioridades" data-dt-no-sort-last="1">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Nível</th>
                            <th>Multiplicador</th>
                            <th>Cor</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($prioridades as $p): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($p['nome']) ?></strong></td>
                            <td><?= (int)$p['nivel'] ?></td>
                            <td><code><?= htmlspecialchars((string)$p['sla_multiplicador']) ?>×</code></td>
                            <td><span class="sla-color-dot" style="background:<?= htmlspecialchars($p['cor']) ?>"></span></td>
                            <td><?= !empty($p['ativo']) ? '<span class="badge badge-success">Ativa</span>' : '<span class="badge badge-muted">Inativa</span>' ?></td>
                            <td>
                                <a href="<?= BASE_URL ?>/sla/prioridades/<?= (int)$p['id'] ?>/editar" class="btn-icon" title="Editar"><i class="fas fa-edit"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($prioridades)): ?>
                        <tr><td colspan="6" class="empty-td text-center">Nenhuma prioridade.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="card sla-help-card">
    <div class="card-body">
        <h4 class="sla-help-title"><i class="fas fa-info-circle"></i> Como usar</h4>
        <ul class="sla-help-list">
            <li><strong>Categoria</strong> define o tempo base em horas (ex.: Suporte = 8h).</li>
            <li><strong>Prioridade</strong> multiplica esse tempo (ex.: Urgente = 0,5× → metade do prazo).</li>
            <li>Prioridades <strong>inativas</strong> não aparecem em novas OS, mas continuam em OS antigas.</li>
        </ul>
    </div>
</div>
