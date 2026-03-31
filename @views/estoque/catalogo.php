<?php $pageTitle = 'Catálogo e itens'; ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Catálogo de itens</h1>
        <p class="page-subtitle">Cadastre peças usadas nas entradas de estoque e nas OS · <a href="<?= BASE_URL ?>/estoque/categorias">Categorias</a> · <a href="<?= BASE_URL ?>/conta/configuracao/catalogo-codigo">Tag do código</a></p>
    </div>
    <div class="page-header-actions">
        <a href="<?= BASE_URL ?>/estoque/categorias" class="btn btn-ghost"><i class="fas fa-folder"></i> Categorias</a>
        <a href="<?= BASE_URL ?>/estoque" class="btn btn-ghost"><i class="fas fa-arrow-left"></i> Meu estoque</a>
        <a href="<?= BASE_URL ?>/estoque/relatorio" class="btn btn-ghost"><i class="fas fa-chart-line"></i> Meu consumo em OS</a>
    </div>
</div>

<?php if (!empty($flash)): ?>
<div class="alert alert-<?= htmlspecialchars($flash['type'] ?? 'info') ?> flash-msg"><?= htmlspecialchars($flash['message'] ?? '') ?></div>
<?php endif; ?>

<div class="card catalogo-card-novo-item-solo">
    <div class="card-header"><h3 class="card-title">Novo item</h3></div>
    <div class="card-body">
        <form method="post" action="<?= BASE_URL ?>/estoque/catalogo/criar">
            <div class="form-group">
                <label class="form-label required">Código</label>
                <input type="text" name="codigo" class="form-control" required maxlength="64"
                       value="<?= htmlspecialchars((string)($sugerido ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                       pattern="[A-Z0-9._\-]{1,64}" title="Maiúsculas, números, . _ -">
            </div>
            <div class="form-group">
                <label class="form-label required">Nome</label>
                <input type="text" name="nome" class="form-control" required maxlength="200">
            </div>
            <div class="form-group">
                <label class="form-label">Categoria</label>
                <select name="categoria_id" class="form-control">
                    <option value="0">—</option>
                    <?php foreach ($categorias as $c): ?>
                    <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Unidade</label>
                <input type="text" name="unidade" class="form-control" value="un" maxlength="16" placeholder="un, kg, m, cx…">
            </div>
            <div class="form-group">
                <label class="form-label">Descrição</label>
                <textarea name="descricao" class="form-control" rows="2" maxlength="500"></textarea>
            </div>
            <?php $itemEstoqueItem = null;
            require __DIR__ . '/_form_dados_compra_item.php'; ?>
            <div class="form-group">
                <label class="form-label">Quantidade inicial no meu estoque</label>
                <input type="text" name="quantidade_inicial" class="form-control" inputmode="decimal" placeholder="Opcional — ex.: 5 ou 1,5">
                <small class="form-hint">Se preenchido, gera entrada automática no seu estoque (respeita limite de tipos de item, se houver).</small>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Cadastrar</button>
        </form>
    </div>
</div>

<div class="card" style="margin-top:1.25rem">
    <div class="card-header"><h3 class="card-title">Itens cadastrados</h3></div>
    <div class="card-body">
        <div class="table-wrap">
            <table class="table table-datatable" data-dt-order="[]" data-dt-page-length="25" data-dt-no-sort-last="1">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Nome</th>
                        <th>Categoria</th>
                        <th>NF</th>
                        <th>Fornecedor</th>
                        <th>Un.</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($itens as $it):
                        $nfT = trim((string)($it['nf_numero'] ?? ''));
                        $nfD = $nfT !== '' ? (mb_strlen($nfT) > 20 ? mb_substr($nfT, 0, 20) . '…' : $nfT) : '—';
                        $foT = trim((string)($it['fornecedor'] ?? ''));
                        $foD = $foT !== '' ? (mb_strlen($foT) > 28 ? mb_substr($foT, 0, 28) . '…' : $foT) : '—';
                        ?>
                    <tr>
                        <td><code><?= htmlspecialchars((string)$it['codigo']) ?></code></td>
                        <td><?= htmlspecialchars((string)$it['nome']) ?></td>
                        <td><?= htmlspecialchars((string)($it['categoria_nome'] ?? '—')) ?></td>
                        <td class="text-muted" title="<?= $nfT !== '' ? htmlspecialchars($nfT) : '' ?>"><?= $nfT !== '' ? htmlspecialchars($nfD) : '—' ?></td>
                        <td class="text-muted" title="<?= $foT !== '' ? htmlspecialchars($foT) : '' ?>"><?= $foT !== '' ? htmlspecialchars($foD) : '—' ?></td>
                        <td><?= htmlspecialchars((string)$it['unidade']) ?></td>
                        <td><?= (int)($it['ativo'] ?? 0) ? '<span class="badge badge-success">Ativo</span>' : '<span class="badge">Inativo</span>' ?></td>
                        <td class="table-actions">
                            <a href="<?= BASE_URL ?>/estoque/catalogo/<?= (int)$it['id'] ?>/editar" class="btn btn-ghost btn-sm"><i class="fas fa-edit"></i></a>
                            <form method="post" action="<?= BASE_URL ?>/estoque/catalogo/<?= (int)$it['id'] ?>/toggle" style="display:inline" onsubmit="return confirm('Alterar status deste item?');">
                                <button type="submit" class="btn btn-ghost btn-sm" title="Ativar/desativar"><i class="fas fa-power-off"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
