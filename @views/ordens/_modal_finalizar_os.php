<?php
/** Modal finalizar OS pela listagem — apenas técnicos */
$formasPg = Ordem::formasPagamentoOpcoes();
$dtPadrao = date('Y-m-d\TH:i');
?>
<div class="modal-backdrop" id="modalFinalizarOs" aria-hidden="true">
    <div class="modal-dialog modal-finalizar-os" role="dialog" aria-labelledby="modalFinalizarOsTitulo">
        <div class="modal-header">
            <h2 id="modalFinalizarOsTitulo" class="modal-title">
                <i class="fas fa-flag-checkered"></i> Finalizar <span data-modal-os-num></span>
            </h2>
            <button type="button" class="modal-close" data-modal-close aria-label="Fechar">&times;</button>
        </div>
        <form method="post" action="">
            <div class="modal-body">
                <p class="modal-lead">Registre o motivo, valores e forma de pagamento. A OS será marcada como <strong>finalizada</strong>.</p>
                <div class="form-group">
                    <label class="form-label required">Motivo da finalização</label>
                    <textarea name="motivo_finalizacao" class="form-control" rows="3" required maxlength="500" placeholder="Ex.: Reparo concluído, testes OK, entregue ao cliente."></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Data / hora da finalização</label>
                    <input type="datetime-local" name="data_finalizacao" class="form-control" value="<?= htmlspecialchars($dtPadrao) ?>">
                </div>
                <div class="form-row-2">
                    <div class="form-group">
                        <label class="form-label">Valor do serviço (R$)</label>
                        <input type="text" name="valor_servico" class="form-control" inputmode="decimal" placeholder="0,00" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Valor pago (R$)</label>
                        <input type="text" name="valor_pago" class="form-control" inputmode="decimal" placeholder="0,00" autocomplete="off">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Forma de pagamento</label>
                    <select name="forma_pagamento" class="form-control">
                        <?php foreach ($formasPg as $k => $label): ?>
                        <option value="<?= htmlspecialchars((string)$k) ?>"><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Materiais / peças utilizados (opcional)</label>
                    <textarea name="detalhe_financeiro" class="form-control" rows="2" maxlength="65535" placeholder="Ex.: Tela original R$ 120; cola; cabo USB."></textarea>
                </div>
                <div class="form-group form-check-finalizar">
                    <label class="form-check-label">
                        <input type="checkbox" name="confirmar_encerramento" value="1" required>
                        Confirmo o encerramento desta OS e os dados financeiros informados.
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" data-modal-close>Cancelar</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-check"></i> Finalizar OS
                </button>
            </div>
        </form>
    </div>
</div>
