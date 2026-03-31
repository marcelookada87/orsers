<?php $pageTitle = 'Editar item do catálogo'; ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Editar item</h1>
        <p class="page-subtitle"><code><?= htmlspecialchars($item['codigo']) ?></code> — código não pode ser alterado</p>
    </div>
    <a href="<?= BASE_URL ?>/admin/estoque/catalogo" class="btn btn-ghost"><i class="fas fa-arrow-left"></i> Catálogo</a>
</div>

<div class="form-grid narrow">
    <div class="card">
        <div class="card-body">
            <form method="post" action="<?= BASE_URL ?>/admin/estoque/catalogo/<?= (int)$item['id'] ?>/atualizar">
                <div class="form-group">
                    <label class="form-label">Código</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($item['codigo']) ?>" disabled>
                </div>
                <div class="form-group">
                    <label class="form-label required">Nome</label>
                    <input type="text" name="nome" class="form-control" required maxlength="200" value="<?= htmlspecialchars($item['nome']) ?>">
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
                    <input type="text" name="unidade" class="form-control" maxlength="16" value="<?= htmlspecialchars($item['unidade']) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Descrição</label>
                    <textarea name="descricao" class="form-control" rows="3" maxlength="500"><?= htmlspecialchars((string)($item['descricao'] ?? '')) ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Salvar</button>
            </form>
        </div>
    </div>
</div>
