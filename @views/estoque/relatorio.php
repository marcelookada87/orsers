<?php $pageTitle = 'Meu consumo em OS'; ?>

<?php if (!empty($flash)): ?>
<div class="alert alert-<?= htmlspecialchars($flash['type'] ?? 'info') ?> flash-msg"><?= htmlspecialchars($flash['message'] ?? '') ?></div>
<?php endif; ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Consumo nas minhas OS</h1>
        <p class="page-subtitle">Materiais que você lançou nas ordens de serviço</p>
    </div>
    <a href="<?= BASE_URL ?>/estoque" class="btn btn-ghost"><i class="fas fa-arrow-left"></i> Meu estoque</a>
</div>

<div class="card filter-card">
    <form method="get" action="<?= BASE_URL ?>/estoque/relatorio" class="filter-form">
        <div class="filter-row">
            <div class="filter-group">
                <label>De</label>
                <input type="date" name="data_ini" class="form-control" value="<?= htmlspecialchars($ini) ?>">
            </div>
            <div class="filter-group">
                <label>Até</label>
                <input type="date" name="data_fim" class="form-control" value="<?= htmlspecialchars($fim) ?>">
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter"></i> Atualizar</button>
            </div>
        </div>
    </form>
</div>

<div class="form-grid" style="grid-template-columns:1fr 1fr; gap:1.25rem; align-items:start">
    <div class="card">
        <div class="card-header"><h3 class="card-title">Ranking no período</h3></div>
        <div class="card-body">
            <?php if (empty($ranking)): ?>
            <p class="text-muted">Nenhum consumo no período.</p>
            <?php else: ?>
            <ol>
                <?php foreach ($ranking as $r): ?>
                <li><strong><?= htmlspecialchars((string)$r['codigo']) ?></strong> <?= htmlspecialchars((string)$r['nome']) ?>
                    — <?= rtrim(rtrim(number_format((float)$r['total_q'], 3, ',', '.'), '0'), ',') ?>
                </li>
                <?php endforeach; ?>
            </ol>
            <?php endif; ?>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><h3 class="card-title">Detalhe</h3></div>
        <div class="card-body">
            <?php if (empty($consumo)): ?>
            <p class="text-muted">Sem lançamentos.</p>
            <?php else: ?>
            <div class="table-wrap">
                <table class="table table-datatable" data-dt-order="[[0,&quot;desc&quot;]]" data-dt-page-length="25">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>OS</th>
                            <th>Item</th>
                            <th>Qtd</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($consumo as $c): ?>
                        <tr>
                            <td data-order="<?= strtotime((string)$c['created_at']) ?>"><?= date('d/m/Y H:i', strtotime((string)$c['created_at'])) ?></td>
                            <td><a href="<?= BASE_URL ?>/ordens/<?= (int)$c['ordem_id'] ?>"><?= htmlspecialchars((string)$c['numero']) ?></a></td>
                            <td><?= htmlspecialchars((string)$c['codigo']) ?> — <?= htmlspecialchars((string)$c['item_nome']) ?></td>
                            <td><?= htmlspecialchars((string)$c['quantidade']) ?> <?= htmlspecialchars((string)$c['unidade']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
