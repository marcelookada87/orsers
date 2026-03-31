<?php
$c = $categoria ?? [];
$isEdit = !empty($c['id']);
$pageTitle = $pageTitle ?? ($isEdit ? 'Editar categoria' : 'Nova categoria');
?>

<div class="page-header">
    <div>
        <h1 class="page-title"><?= htmlspecialchars($pageTitle) ?></h1>
        <p class="page-subtitle">Tempo base (horas) usado no cálculo do SLA desta categoria</p>
    </div>
    <a href="<?= BASE_URL ?>/sla/cadastros" class="btn btn-ghost"><i class="fas fa-arrow-left"></i> Voltar</a>
</div>

<form method="POST" action="<?= BASE_URL ?><?= $isEdit ? '/sla/categorias/' . (int)$c['id'] . '/atualizar' : '/sla/categorias' ?>" novalidate>
    <div class="form-grid narrow">
        <div class="form-main">
            <div class="card">
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label required">Nome</label>
                        <input type="text" name="nome" class="form-control" required maxlength="100"
                               value="<?= htmlspecialchars($c['nome'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Descrição</label>
                        <input type="text" name="descricao" class="form-control" maxlength="255"
                               value="<?= htmlspecialchars($c['descricao'] ?? '') ?>">
                    </div>
                    <div class="form-row-2">
                        <div class="form-group">
                            <label class="form-label required">SLA base (horas)</label>
                            <input type="number" name="sla_horas" class="form-control" required min="0.25" max="8760" step="0.25"
                                   value="<?= htmlspecialchars($c['sla_horas'] ?? '24') ?>">
                            <small class="form-hint">Ex.: 8 para 8 horas, 24 para um dia útil</small>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Cor (interface)</label>
                            <input type="color" name="cor" class="form-control form-control-color"
                                   value="<?= htmlspecialchars($c['cor'] ?? '#3B82F6') ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="checkbox-inline">
                            <input type="checkbox" name="ativo" value="1" <?= !isset($c['ativo']) || !empty($c['ativo']) ? 'checked' : '' ?>>
                            Categoria ativa (disponível em novas OS)
                        </label>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Salvar</button>
                </div>
            </div>
        </div>
    </div>
</form>
