<?php $pageTitle = 'Tag do código — catálogo'; ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Tag do código do item</h1>
        <p class="page-subtitle">
            Prefixo da sugestão de código em <a href="<?= BASE_URL ?>/estoque/catalogo">Catálogo e cadastros</a>.
        </p>
    </div>
    <div class="page-header-actions">
        <a href="<?= BASE_URL ?>/conta/configuracao" class="btn btn-ghost"><i class="fas fa-sliders-h"></i> Configuração</a>
        <a href="<?= BASE_URL ?>/estoque/catalogo" class="btn btn-ghost"><i class="fas fa-barcode"></i> Catálogo</a>
    </div>
</div>

<?php if (!empty($flash)): ?>
<div class="alert alert-<?= htmlspecialchars($flash['type'] ?? 'info') ?> flash-msg"><?= htmlspecialchars($flash['message'] ?? '') ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header"><h3 class="card-title">Prefixo sugerido</h3></div>
    <div class="card-body">
        <p class="form-hint" style="margin-bottom:1rem">
            A sugestão automática usa o formato <code>TAG</code> + <code>0001</code>, <code>0002</code>, … (sequência conforme o maior id no catálogo).
            Na tag são permitidos <strong>letras, números</strong> e opcionalmente <code>.</code> <code>_</code> <code>-</code> (o restante é ignorado).
            Exemplos: <code>ITEM</code>, <code>ITEM_</code>, <code>PEC-01</code>.
            Deixe em branco para usar o padrão <code>ITEM</code>.
        </p>
        <p class="form-hint" style="margin-bottom:1.25rem">
            Próxima sugestão com a tag atual: <code><?= htmlspecialchars((string)($preview ?? '')) ?></code>
        </p>
        <form method="post" action="<?= BASE_URL ?>/conta/configuracao/catalogo-codigo">
            <div class="form-group">
                <label class="form-label" for="fldTag">Tag (prefixo)</label>
                <input id="fldTag" type="text" name="catalogo_codigo_item_tag" class="form-control" maxlength="32"
                       value="<?= htmlspecialchars((string)($tagAtual ?? 'ITEM'), ENT_QUOTES, 'UTF-8') ?>"
                       placeholder="Ex.: PEC, CABO, ITEM"
                       autocomplete="off">
                <small class="form-hint">Até 16 caracteres (A–Z, 0–9). No cadastro do item você ainda pode usar <code>.</code> <code>_</code> <code>-</code> no código completo, se editar manualmente.</small>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Salvar</button>
            <p class="form-hint" style="margin-top:1rem;margin-bottom:0;font-size:0.8rem;color:#64748b">
                Persistido em <code>usuario_configuracoes</code> · chave <code><?= htmlspecialchars(UsuarioConfigChave::CATALOGO_CODIGO_ITEM_TAG) ?></code>
            </p>
        </form>
    </div>
</div>
