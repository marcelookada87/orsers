<?php $pageTitle = 'Movimentações de estoque'; ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Movimentações</h1>
        <p class="page-subtitle">Histórico de entradas e saídas do seu estoque</p>
    </div>
    <a href="<?= BASE_URL ?>/estoque" class="btn btn-ghost"><i class="fas fa-arrow-left"></i> Estoque</a>
</div>

<div class="card filter-card">
    <form method="get" action="<?= BASE_URL ?>/estoque/historico" class="filter-form">
        <div class="filter-row">
            <div class="filter-group">
                <label>Tipo</label>
                <select name="tipo" class="form-control">
                    <option value="">Todos</option>
                    <option value="entrada" <?= ($filtros['tipo'] ?? '') === 'entrada' ? 'selected' : '' ?>>Entrada</option>
                    <option value="saida" <?= ($filtros['tipo'] ?? '') === 'saida' ? 'selected' : '' ?>>Saída</option>
                    <option value="ajuste" <?= ($filtros['tipo'] ?? '') === 'ajuste' ? 'selected' : '' ?>>Ajuste</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Item</label>
                <select name="item_id" class="form-control">
                    <option value="0">Todos</option>
                    <?php foreach ($itens as $it): ?>
                    <option value="<?= (int)$it['id'] ?>" <?= (int)($filtros['item_id'] ?? 0) === (int)$it['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($it['codigo'] . ' — ' . $it['nome']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label>De</label>
                <input type="date" name="data_ini" class="form-control" value="<?= htmlspecialchars($filtros['data_ini'] ?? '') ?>">
            </div>
            <div class="filter-group">
                <label>Até</label>
                <input type="date" name="data_fim" class="form-control" value="<?= htmlspecialchars($filtros['data_fim'] ?? '') ?>">
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter"></i> Filtrar</button>
            </div>
        </div>
    </form>
</div>

<div class="card">
    <div class="card-body">
        <?php if (empty($movs)): ?>
        <div class="empty-state compact"><p>Nenhuma movimentação neste filtro.</p></div>
        <?php else: ?>
        <div class="table-wrap">
            <table class="table table-datatable" data-dt-order="[]" data-dt-page-length="25">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Tipo</th>
                        <th>Item</th>
                        <th>Qtd</th>
                        <th>Saldo antes</th>
                        <th>Saldo depois</th>
                        <th>Ref.</th>
                        <th>Obs.</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($movs as $m): ?>
                    <tr>
                        <td data-order="<?= strtotime($m['created_at']) ?>"><?= date('d/m/Y H:i', strtotime($m['created_at'])) ?></td>
                        <td><span class="badge"><?= htmlspecialchars($m['tipo']) ?></span></td>
                        <td><?= htmlspecialchars($m['item_codigo']) ?> — <?= htmlspecialchars($m['item_nome']) ?></td>
                        <td><?= htmlspecialchars((string)$m['quantidade']) ?> <?= htmlspecialchars($m['item_unidade']) ?></td>
                        <td><?= htmlspecialchars((string)$m['saldo_anterior']) ?></td>
                        <td><?= htmlspecialchars((string)$m['saldo_posterior']) ?></td>
                        <td>
                            <?php if (($m['referencia_tipo'] ?? '') === 'os' && !empty($m['referencia_id'])): ?>
                            <a href="<?= BASE_URL ?>/ordens/<?= (int)$m['referencia_id'] ?>">OS #<?= (int)$m['referencia_id'] ?></a>
                            <?php else: ?>
                            <?= htmlspecialchars($m['referencia_tipo'] ?? '') ?>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars((string)($m['observacao'] ?? '')) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
