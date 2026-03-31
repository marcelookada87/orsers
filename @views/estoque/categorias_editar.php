<?php $pageTitle = 'Editar categoria'; ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Editar categoria</h1>
        <p class="page-subtitle"><?= htmlspecialchars((string)$cat['nome']) ?></p>
    </div>
    <a href="<?= BASE_URL ?>/estoque/categorias" class="btn btn-ghost"><i class="fas fa-arrow-left"></i> Categorias</a>
</div>

<?php if (!empty($flash)): ?>
<div class="alert alert-<?= htmlspecialchars($flash['type'] ?? 'info') ?> flash-msg"><?= htmlspecialchars($flash['message'] ?? '') ?></div>
<?php endif; ?>

<div class="form-grid narrow">
    <div class="card">
        <div class="card-body">
            <form method="post" action="<?= BASE_URL ?>/estoque/categorias/<?= (int)$cat['id'] ?>/atualizar">
                <div class="form-group">
                    <label class="form-label required">Nome</label>
                    <input type="text" name="nome" class="form-control" required maxlength="120" value="<?= htmlspecialchars((string)$cat['nome']) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Descrição</label>
                    <input type="text" name="descricao" class="form-control" maxlength="500" value="<?= htmlspecialchars((string)($cat['descricao'] ?? '')) ?>">
                </div>
                <div class="form-group">
                    <label class="checkbox-inline">
                        <input type="checkbox" name="ativo" value="1" <?= (int)($cat['ativo'] ?? 0) ? 'checked' : '' ?>>
                        Categoria ativa (visível nas listas de seleção)
                    </label>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Salvar</button>
            </form>
        </div>
    </div>
</div>
