<?php
$pageTitle = 'Editar item do catálogo';
$fmtQ = static fn ($v) => rtrim(rtrim(number_format((float)$v, 3, ',', '.'), '0'), ',') ?: '0';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Editar item</h1>
        <p class="page-subtitle"><code><?= htmlspecialchars((string)$item['codigo']) ?></code> — código não pode ser alterado</p>
    </div>
    <div class="page-header-actions">
        <a href="<?= BASE_URL ?>/estoque" class="btn btn-ghost"><i class="fas fa-boxes"></i> Meu estoque</a>
        <a href="<?= BASE_URL ?>/estoque/catalogo" class="btn btn-ghost"><i class="fas fa-arrow-left"></i> Catálogo</a>
    </div>
</div>

<div class="form-grid narrow">
    <div class="card">
        <div class="card-header"><h3 class="card-title">Dados do catálogo (global)</h3></div>
        <div class="card-body">
            <form method="post" action="<?= BASE_URL ?>/estoque/catalogo/<?= (int)$item['id'] ?>/atualizar" id="formCatalogoEditar">
                <div class="form-group">
                    <label class="form-label">Código</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars((string)$item['codigo']) ?>" disabled>
                </div>
                <div class="form-group">
                    <label class="form-label required">Nome</label>
                    <input type="text" name="nome" class="form-control" required maxlength="200" value="<?= htmlspecialchars((string)$item['nome']) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Categoria</label>
                    <select name="categoria_id" class="form-control">
                        <option value="0">—</option>
                        <?php foreach ($categorias as $c): ?>
                        <option value="<?= (int)$c['id'] ?>" <?= (int)($item['categoria_id'] ?? 0) === (int)$c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Unidade</label>
                    <input type="text" name="unidade" class="form-control" maxlength="16" value="<?= htmlspecialchars((string)$item['unidade']) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Descrição</label>
                    <textarea name="descricao" class="form-control" rows="3" maxlength="500"><?= htmlspecialchars((string)($item['descricao'] ?? '')) ?></textarea>
                </div>
                <?php $itemEstoqueItem = $item;
                require __DIR__ . '/_form_dados_compra_item.php'; ?>

                <div class="catalogo-editar-bloco-estoque" style="margin-top:1.35rem;padding-top:1.25rem;border-top:1px solid var(--border-subtle, #e2e8f0)">
                    <h4 class="card-title" style="font-size:1rem;margin:0 0 .5rem">No seu estoque</h4>
                    <p class="form-hint" style="margin-bottom:1rem">Quantidade e alerta valem só para a sua conta. Alterações na quantidade geram um <strong>ajuste</strong> nas movimentações.</p>
                    <div class="form-group">
                        <label class="form-label required" for="estoqueQ">Quantidade atual</label>
                        <input id="estoqueQ" type="text" name="estoque_quantidade" class="form-control" required inputmode="decimal"
                               value="<?= htmlspecialchars($fmtQ($estoqueQ ?? 0)) ?>"
                               placeholder="0">
                        <small class="form-hint">Unidade: <strong><?= htmlspecialchars((string)$item['unidade']) ?></strong> — use vírgula ou ponto (ex.: 1,5).</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="estoqueM">Estoque mínimo (alerta)</label>
                        <input id="estoqueM" type="text" name="estoque_quantidade_minima" class="form-control" inputmode="decimal"
                               value="<?= htmlspecialchars($fmtQ($estoqueMin ?? 0)) ?>"
                               placeholder="0">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="margin-top:1rem"><i class="fas fa-save"></i> Salvar</button>
            </form>
        </div>
    </div>
</div>
