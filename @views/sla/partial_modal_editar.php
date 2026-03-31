<?php
/** Fragmento — formulário edição OS no modal do painel SLA (sem layout). */
$isTec = Auth::isTecnico();
?>
<form method="post" action="<?= BASE_URL ?>/ordens/<?= (int)$ordem['id'] ?>/atualizar" class="sla-modal-edit-form" id="formSlaModalEditar">
    <input type="hidden" name="retorno_sla" value="1">

    <div class="sla-modal-form-group">
        <label class="form-label required">Título</label>
        <input type="text" name="titulo" class="form-control" required maxlength="200" value="<?= htmlspecialchars($ordem['titulo']) ?>">
    </div>
    <div class="sla-modal-form-group">
        <label class="form-label required">Descrição</label>
        <textarea name="descricao" class="form-control textarea-modal" required rows="4"><?= htmlspecialchars($ordem['descricao']) ?></textarea>
    </div>
    <div class="sla-modal-form-group">
        <label class="form-label">Observações</label>
        <textarea name="observacoes" class="form-control" rows="2"><?= htmlspecialchars($ordem['observacoes'] ?? '') ?></textarea>
    </div>

    <div class="sla-modal-form-row">
        <div class="sla-modal-form-group">
            <label class="form-label required">Cliente</label>
            <select name="cliente_id" class="form-control" required>
                <option value="" disabled <?= empty($ordem['cliente_id']) ? 'selected' : '' ?>>Selecione…</option>
                <?php foreach ($clientes as $cl): ?>
                <option value="<?= (int)$cl['id'] ?>" <?= (int)($ordem['cliente_id'] ?? 0) === (int)$cl['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars(Cliente::rotuloExibicao($cl)) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="sla-modal-form-group">
            <label class="form-label required">Categoria</label>
            <select name="categoria_id" class="form-control" required>
                <?php foreach ($categorias as $c): ?>
                <option value="<?= (int)$c['id'] ?>" <?= (int)$ordem['categoria_id'] === (int)$c['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($c['nome']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="sla-modal-form-group">
        <label class="form-label required">Prioridade</label>
        <select name="prioridade_id" class="form-control" required>
            <?php foreach ($prioridades as $p): ?>
            <option value="<?= (int)$p['id'] ?>" <?= (int)$ordem['prioridade_id'] === (int)$p['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($p['nome']) ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>

    <?php if ($isTec): ?>
    <div class="sla-modal-form-group">
        <label class="form-label">Status</label>
        <select name="status" class="form-control">
            <?php foreach (['aberta','em_andamento','aguardando','finalizada','cancelada'] as $s): ?>
            <option value="<?= $s ?>" <?= $ordem['status'] === $s ? 'selected' : '' ?>><?= ucfirst(str_replace('_', ' ', $s)) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="sla-modal-finance-block">
        <div class="sla-modal-finance-title"><i class="fas fa-coins"></i> Financeiro (técnico)</div>
        <div class="sla-modal-form-group">
            <label class="form-label">Motivo da finalização</label>
            <textarea name="motivo_finalizacao" class="form-control" rows="2" maxlength="500"><?= htmlspecialchars($ordem['motivo_finalizacao'] ?? '') ?></textarea>
        </div>
        <div class="sla-modal-form-row">
            <div class="sla-modal-form-group">
                <label class="form-label">Valor serviço (R$)</label>
                <input type="text" name="valor_servico" class="form-control" inputmode="decimal" value="<?= htmlspecialchars($vfServ) ?>" autocomplete="off">
            </div>
            <div class="sla-modal-form-group">
                <label class="form-label">Valor pago (R$)</label>
                <input type="text" name="valor_pago" class="form-control" inputmode="decimal" value="<?= htmlspecialchars($vfPago) ?>" autocomplete="off">
            </div>
        </div>
        <div class="sla-modal-form-group">
            <label class="form-label">Forma de pagamento</label>
            <select name="forma_pagamento" class="form-control">
                <?php foreach ($formasPgEdit as $k => $label): ?>
                <option value="<?= htmlspecialchars((string)$k) ?>" <?= (($ordem['forma_pagamento'] ?? '') === (string)$k) ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="sla-modal-form-group">
            <label class="form-label">Data/hora finalização</label>
            <input type="datetime-local" name="data_finalizacao" class="form-control" value="<?= htmlspecialchars($dtFinLocal) ?>">
        </div>
        <div class="sla-modal-form-group">
            <label class="form-label">Materiais / uso</label>
            <textarea name="detalhe_financeiro" class="form-control" rows="2"><?= htmlspecialchars($ordem['detalhe_financeiro'] ?? '') ?></textarea>
        </div>
    </div>
    <?php else: ?>
    <input type="hidden" name="status" value="<?= htmlspecialchars($ordem['status']) ?>">
    <?php endif; ?>

    <div class="sla-modal-form-actions">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Salvar</button>
        <a href="<?= BASE_URL ?>/ordens/<?= (int)$ordem['id'] ?>/editar" class="btn btn-ghost btn-sm" target="_blank" rel="noopener">
            <i class="fas fa-external-link-alt"></i> Edição completa
        </a>
    </div>
</form>
