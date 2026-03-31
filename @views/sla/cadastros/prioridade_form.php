<?php
$p = $prioridade ?? [];
$isEdit = !empty($p['id']);
$pageTitle = $pageTitle ?? ($isEdit ? 'Editar prioridade' : 'Nova prioridade');
?>

<div class="page-header">
    <div>
        <h1 class="page-title"><?= htmlspecialchars($pageTitle) ?></h1>
        <p class="page-subtitle">Multiplicador aplicado sobre as horas da categoria (ex.: 0,5 = metade do prazo)</p>
    </div>
    <a href="<?= BASE_URL ?>/sla/cadastros" class="btn btn-ghost"><i class="fas fa-arrow-left"></i> Voltar</a>
</div>

<form method="POST" action="<?= BASE_URL ?><?= $isEdit ? '/sla/prioridades/' . (int)$p['id'] . '/atualizar' : '/sla/prioridades' ?>" novalidate>
    <div class="form-grid narrow">
        <div class="form-main">
            <div class="card">
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label required">Nome</label>
                        <input type="text" name="nome" class="form-control" required maxlength="50"
                               value="<?= htmlspecialchars($p['nome'] ?? '') ?>">
                    </div>
                    <div class="form-row-2">
                        <div class="form-group">
                            <label class="form-label required">Nível (ordem)</label>
                            <input type="number" name="nivel" class="form-control" required min="1" max="9"
                                   value="<?= (int)($p['nivel'] ?? 3) ?>">
                            <small class="form-hint">1 = mais urgente na listagem</small>
                        </div>
                        <div class="form-group">
                            <label class="form-label required">Multiplicador SLA</label>
                            <input type="number" name="sla_multiplicador" class="form-control" required min="0.05" max="10" step="0.05"
                                   value="<?= htmlspecialchars($p['sla_multiplicador'] ?? '1') ?>">
                            <small class="form-hint">1,0 = neutro · &lt;1 acelera · &gt;1 alonga</small>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Cor (interface)</label>
                        <input type="color" name="cor" class="form-control form-control-color"
                               value="<?= htmlspecialchars($p['cor'] ?? '#6B7280') ?>">
                    </div>
                    <div class="form-group">
                        <label class="checkbox-inline">
                            <input type="checkbox" name="ativo" value="1" <?= !isset($p['ativo']) || !empty($p['ativo']) ? 'checked' : '' ?>>
                            Prioridade ativa (disponível em novas OS)
                        </label>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Salvar</button>
                </div>
            </div>
        </div>
    </div>
</form>
