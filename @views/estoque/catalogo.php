<?php
$pageTitle = 'Catálogo e itens';
$extraJs   = ['catalogo-item-modal.js'];
?>

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
        <form method="post" action="<?= BASE_URL ?>/estoque/catalogo/criar" id="catalogoNovoForm">
            <div class="form-group">
                <label class="form-label required">Código</label>
                <div style="display:flex;gap:.4rem;align-items:center">
                    <input type="text" name="codigo" class="form-control" required maxlength="64"
                           value="<?= htmlspecialchars((string)($sugerido ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                           pattern="[A-Z0-9._\-]{1,64}" title="Maiúsculas, números, . _ -">
                    <button type="button" class="btn btn-ghost btn-sm" id="btnCatalogoScan" title="Escanear código/QR">
                        <i class="fas fa-qrcode"></i>
                    </button>
                </div>
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
                <label class="form-label">Quantidade inicial no meu estoque</label>
                <input type="text" name="quantidade_inicial" class="form-control" inputmode="decimal" placeholder="Opcional — ex.: 5 ou 1,5">
                <small class="form-hint">Se preenchido, gera entrada automática no seu estoque (respeita limite de tipos de item, se houver).</small>
            </div>
            <div class="form-group">
                <label class="form-label">Descrição</label>
                <textarea name="descricao" class="form-control" rows="2" maxlength="500"></textarea>
            </div>
            <?php $itemEstoqueItem = null;
            require __DIR__ . '/_form_dados_compra_item.php'; ?>
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
                            <button type="button"
                                    class="btn btn-ghost btn-sm js-item-view"
                                    title="Visualizar item"
                                    data-codigo="<?= htmlspecialchars((string)$it['codigo']) ?>"
                                    data-nome="<?= htmlspecialchars((string)$it['nome']) ?>"
                                    data-categoria="<?= htmlspecialchars((string)($it['categoria_nome'] ?? '—')) ?>"
                                    data-unidade="<?= htmlspecialchars((string)$it['unidade']) ?>"
                                    data-status="<?= (int)($it['ativo'] ?? 0) ? 'Ativo' : 'Inativo' ?>"
                                    data-descricao="<?= htmlspecialchars((string)($it['descricao'] ?? '')) ?>"
                                    data-nf-numero="<?= htmlspecialchars((string)($it['nf_numero'] ?? '')) ?>"
                                    data-nf-emissao="<?= htmlspecialchars((string)($it['nf_emissao'] ?? '')) ?>"
                                    data-nf-valor-total="<?= htmlspecialchars((string)($it['nf_valor_total'] ?? '')) ?>"
                                    data-fornecedor="<?= htmlspecialchars((string)($it['fornecedor'] ?? '')) ?>"
                                    data-fornecedor-cnpj="<?= htmlspecialchars((string)($it['fornecedor_cnpj'] ?? '')) ?>"
                                    data-compra-observacoes="<?= htmlspecialchars((string)($it['compra_observacoes'] ?? '')) ?>">
                                <i class="fas fa-eye"></i>
                            </button>
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

<div class="modal-backdrop" id="itemViewModal" aria-hidden="true">
    <div class="modal-dialog" role="dialog" aria-modal="true" aria-labelledby="itemViewTitle" style="max-width:640px">
        <div class="modal-header">
            <h3 class="modal-title" id="itemViewTitle"><i class="fas fa-box-open"></i> Dados do item</h3>
            <button type="button" class="modal-close" data-modal-close>&times;</button>
        </div>
        <div class="modal-body" style="padding:12px 18px 12px">
            <dl class="item-view-grid" style="display:grid;grid-template-columns:160px 1fr;gap:.55rem .9rem;margin:0">
                <dt>Código</dt><dd id="ivCodigo"></dd>
                <dt>Nome</dt><dd id="ivNome"></dd>
                <dt>Categoria</dt><dd id="ivCategoria"></dd>
                <dt>Unidade</dt><dd id="ivUnidade"></dd>
                <dt>Status</dt><dd id="ivStatus"></dd>
                <dt>Descrição</dt><dd id="ivDescricao"></dd>
                <dt>Nº NF</dt><dd id="ivNfNumero"></dd>
                <dt>Emissão NF</dt><dd id="ivNfEmissao"></dd>
                <dt>Valor total NF</dt><dd id="ivNfValor"></dd>
                <dt>Fornecedor</dt><dd id="ivFornecedor"></dd>
                <dt>CNPJ</dt><dd id="ivFornecedorCnpj"></dd>
                <dt>Obs. compra</dt><dd id="ivCompraObs"></dd>
            </dl>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-ghost" data-modal-close>Fechar</button>
        </div>
    </div>
</div>

<div class="modal-backdrop" id="catalogoScanModal" aria-hidden="true">
    <div class="modal-dialog" role="dialog" aria-modal="true" aria-labelledby="catalogoScanTitle" style="max-width:700px">
        <div class="modal-header">
            <h3 class="modal-title" id="catalogoScanTitle"><i class="fas fa-qrcode"></i> Scanner de item</h3>
            <button type="button" class="modal-close" data-catalogo-scan-close>&times;</button>
        </div>
        <div class="modal-body" style="padding:0 18px 12px">
            <p class="modal-lead">Bipe com a pistola ou use a câmera. Se já existir item, faça entrada rápida só com quantidade.</p>
            <div class="form-group">
                <label class="form-label">Leitura da pistola</label>
                <input type="text" id="catalogoScanInput" class="form-control" placeholder="Clique e bip o código">
            </div>
            <div class="form-group" style="margin-top:.6rem">
                <button type="button" class="btn btn-ghost btn-sm" id="btnCatalogoCamStart"><i class="fas fa-camera"></i> Câmera</button>
                <button type="button" class="btn btn-ghost btn-sm" id="btnCatalogoCamStop" style="display:none"><i class="fas fa-stop"></i> Parar</button>
            </div>
            <video id="catalogoScanVideo" style="display:none;width:100%;border-radius:8px;border:1px solid var(--border)" autoplay muted playsinline></video>
            <p class="form-hint" id="catalogoScanHint" style="margin-top:.5rem;min-height:1.25rem"></p>
            <hr style="border-color:var(--border);opacity:.45;margin:12px 0">
            <h4 class="card-title" style="font-size:1rem;margin:0 0 .55rem">Dados do item encontrado</h4>
            <dl style="display:grid;grid-template-columns:135px 1fr;gap:.35rem .8rem;margin:0 0 .7rem">
                <dt>Código</dt><dd id="csCodigo">—</dd>
                <dt>Nome</dt><dd id="csNome">—</dd>
                <dt>Categoria</dt><dd id="csCategoria">—</dd>
                <dt>Unidade</dt><dd id="csUnidade">—</dd>
                <dt>Status</dt><dd id="csStatus">—</dd>
                <dt>Descrição</dt><dd id="csDescricao">—</dd>
                <dt>NF</dt><dd id="csNf">—</dd>
                <dt>Fornecedor</dt><dd id="csFornecedor">—</dd>
            </dl>
            <input type="hidden" id="csItemId" value="">
            <div class="form-group">
                <label class="form-label required" for="csQtd">Quantidade (obrigatório)</label>
                <input id="csQtd" type="text" class="form-control" inputmode="decimal" placeholder="Ex.: 1 ou 1,5">
            </div>
            <div class="form-group">
                <label class="form-label" for="csNfNumeroEntrada">Nº NF (opcional)</label>
                <input id="csNfNumeroEntrada" type="text" class="form-control" maxlength="64" placeholder="Ex.: 123456">
            </div>
            <div class="form-group">
                <label class="form-label" for="csDataCompra">Data da compra (opcional)</label>
                <input id="csDataCompra" type="date" class="form-control">
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-ghost" data-catalogo-scan-close>Fechar</button>
            <button type="button" class="btn btn-ghost" id="btnCatalogoScanNovo"><i class="fas fa-plus-circle"></i> Cadastrar novo com código lido</button>
            <button type="button" class="btn btn-primary" id="btnCatalogoEntradaRapida"><i class="fas fa-check"></i> Lançar entrada rápida</button>
        </div>
    </div>
</div>
