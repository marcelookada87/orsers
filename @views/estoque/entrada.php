<?php $pageTitle = 'Entrada de material'; ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Entrada de material</h1>
        <p class="page-subtitle">Aumente seu saldo a partir do catálogo global</p>
    </div>
    <a href="<?= BASE_URL ?>/estoque" class="btn btn-ghost"><i class="fas fa-arrow-left"></i> Voltar</a>
</div>

<div class="form-grid narrow">
    <div class="card">
        <div class="card-body">
            <?php if (empty($itens)): ?>
            <p class="text-muted">Não há itens ativos no catálogo.<?php if (Auth::isPerfilTecnico()): ?> Cadastre em <strong>Catálogo e cadastros</strong>.<?php else: ?> Solicite a um técnico com estoque ativo para cadastrar itens no catálogo.<?php endif; ?></p>
            <?php else: ?>
            <form method="post" action="<?= BASE_URL ?>/estoque/entrada" novalidate>
                <div class="form-group">
                    <label class="form-label required">Item do catálogo</label>
                    <select name="item_id" class="form-control" required>
                        <option value="">Selecione…</option>
                        <?php foreach ($itens as $it): ?>
                        <option value="<?= (int)$it['id'] ?>"><?= htmlspecialchars($it['codigo'] . ' — ' . $it['nome']) ?> (<?= htmlspecialchars($it['unidade']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label required">Quantidade</label>
                    <input type="text" name="quantidade" class="form-control" required placeholder="ex.: 1,5" inputmode="decimal">
                </div>
                <div class="form-group">
                    <label class="form-label">Observação</label>
                    <input type="text" name="observacao" class="form-control" maxlength="500" placeholder="Opcional (fornecedor, NF, etc.)">
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Confirmar entrada</button>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>
