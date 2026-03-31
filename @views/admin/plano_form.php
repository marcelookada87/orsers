<?php
$edit = $plano !== null;
$pageTitle = $edit ? 'Editar plano' : 'Novo plano';
?>

<div class="page-header">
    <div>
        <h1 class="page-title"><?= htmlspecialchars($pageTitle) ?></h1>
    </div>
    <a href="<?= BASE_URL ?>/admin/planos" class="btn btn-ghost">
        <i class="fas fa-arrow-left"></i> Voltar
    </a>
</div>

<div class="form-grid narrow">
    <div class="card">
        <div class="card-body">
            <form method="post" action="<?= $edit
                ? BASE_URL . '/admin/planos/' . (int)$plano['id'] . '/atualizar'
                : BASE_URL . '/admin/planos' ?>" novalidate>
                <?php if (!$edit): ?>
                <div class="form-group">
                    <label class="form-label required" for="codigo">Código</label>
                    <input type="text" name="codigo" id="codigo" class="form-control" required maxlength="32"
                           pattern="[a-z0-9_]{2,32}" placeholder="ex.: pro, empresa_x"
                           title="Letras minúsculas, números e underscore">
                    <small class="form-hint">Único, sem espaços (use _). Não pode ser alterado depois.</small>
                </div>
                <?php else: ?>
                <div class="form-group">
                    <label class="form-label">Código</label>
                    <input type="text" class="form-control" readonly value="<?= htmlspecialchars($plano['codigo']) ?>">
                </div>
                <?php endif; ?>

                <div class="form-group">
                    <label class="form-label required" for="nome">Nome</label>
                    <input type="text" name="nome" id="nome" class="form-control" required maxlength="100"
                           value="<?= $edit ? htmlspecialchars($plano['nome']) : '' ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="descricao">Descrição (cadastro de usuário)</label>
                    <textarea name="descricao" id="descricao" class="form-control" rows="3" maxlength="500"
                              placeholder="Texto exibido ao escolher o plano ao criar/editar usuário"><?= $edit ? htmlspecialchars((string)($plano['descricao'] ?? '')) : '' ?></textarea>
                    <small class="form-hint">Opcional · até 500 caracteres</small>
                </div>

                <div class="form-row-grid">
                    <div class="form-group">
                        <label class="form-label" for="max_imagens_por_os">Máx. imagens / OS</label>
                        <input type="number" name="max_imagens_por_os" id="max_imagens_por_os" class="form-control" min="0" placeholder="∞ ilimitado"
                               value="<?= $edit && $plano['max_imagens_por_os'] !== null && $plano['max_imagens_por_os'] !== '' ? (int)$plano['max_imagens_por_os'] : '' ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="max_clientes">Máx. clientes</label>
                        <input type="number" name="max_clientes" id="max_clientes" class="form-control" min="0" placeholder="∞"
                               value="<?= $edit && $plano['max_clientes'] !== null && $plano['max_clientes'] !== '' ? (int)$plano['max_clientes'] : '' ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="max_os_mes">Máx. OS / mês</label>
                        <input type="number" name="max_os_mes" id="max_os_mes" class="form-control" min="0" placeholder="∞"
                               value="<?= $edit && $plano['max_os_mes'] !== null && $plano['max_os_mes'] !== '' ? (int)$plano['max_os_mes'] : '' ?>">
                    </div>
                </div>
                <p class="form-hint text-muted">Deixe em branco para ilimitado em cada campo (planos internos ou premium).</p>

                <div class="form-row-2">
                    <div class="form-group">
                        <label class="form-label" for="ordem">Ordem na lista</label>
                        <input type="number" name="ordem" id="ordem" class="form-control" min="0"
                               value="<?= $edit ? (int)($plano['ordem'] ?? 0) : '0' ?>">
                    </div>
                    <div class="form-group" style="align-self: end;">
                        <label class="form-label">
                            <input type="checkbox" name="ativo" value="1" <?= (!$edit || !empty($plano['ativo'])) ? 'checked' : '' ?>>
                            Plano ativo
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Salvar
                </button>
            </form>
        </div>
    </div>
</div>
