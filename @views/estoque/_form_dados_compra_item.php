<?php
/**
 * Trecho reutilizável: dados de NF e fornecedor (catálogo).
 * Define $itemEstoqueItem como array do item ou null no cadastro novo.
 */
$itemEstoqueItem = $itemEstoqueItem ?? null;
$__val = static function (?array $row, string $key): string {
    if (!$row || !array_key_exists($key, $row) || $row[$key] === null || $row[$key] === '') {
        return '';
    }

    return htmlspecialchars((string)$row[$key], ENT_QUOTES, 'UTF-8');
};
$__valDate = static function (?array $row): string {
    if (!$row || empty($row['nf_emissao'])) {
        return '';
    }
    $s = (string)$row['nf_emissao'];

    return htmlspecialchars(strlen($s) >= 10 ? substr($s, 0, 10) : $s, ENT_QUOTES, 'UTF-8');
};
?>
<div class="catalogo-bloco-compra" style="margin-top:1rem;padding-top:1rem;border-top:1px solid var(--border-subtle, #e2e8f0)">
    <h4 class="card-title" style="font-size:1rem;margin:0 0 .65rem">Nota fiscal e fornecedor <span class="text-muted" style="font-weight:400">(opcional)</span></h4>
    <p class="form-hint" style="margin-bottom:1rem">Úteis para rastreio de compra, garantia e auditoria — como em sistemas de estoque.</p>
    <div class="form-group">
        <label class="form-label" for="nf_numero">Nº da nota fiscal (NF-e)</label>
        <input id="nf_numero" type="text" name="nf_numero" class="form-control" maxlength="64"
               value="<?= $__val($itemEstoqueItem, 'nf_numero') ?>"
               placeholder="Ex.: 123456 ou série/número">
    </div>
    <div class="form-group">
        <label class="form-label" for="nf_emissao">Data de emissão da NF</label>
        <input id="nf_emissao" type="date" name="nf_emissao" class="form-control"
               value="<?= $__valDate($itemEstoqueItem) ?>">
    </div>
    <div class="form-group">
        <label class="form-label" for="fornecedor">Fornecedor / empresa</label>
        <input id="fornecedor" type="text" name="fornecedor" class="form-control" maxlength="200"
               value="<?= $__val($itemEstoqueItem, 'fornecedor') ?>"
               placeholder="Razão social ou nome fantasia">
    </div>
    <div class="form-group">
        <label class="form-label" for="fornecedor_cnpj">CNPJ do fornecedor</label>
        <input id="fornecedor_cnpj" type="text" name="fornecedor_cnpj" class="form-control" maxlength="18"
               value="<?= $__val($itemEstoqueItem, 'fornecedor_cnpj') ?>"
               placeholder="Opcional">
    </div>
    <div class="form-group">
        <label class="form-label" for="compra_observacoes">Outras informações</label>
        <textarea id="compra_observacoes" name="compra_observacoes" class="form-control" rows="2" maxlength="600"
                  placeholder="Lote, nº do pedido, serial, observações da compra…"><?= $__val($itemEstoqueItem, 'compra_observacoes') ?></textarea>
        <small class="form-hint">Até 600 caracteres.</small>
    </div>
</div>
