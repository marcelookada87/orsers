<?php
$pageTitle = 'Categorias de estoque';
$fmtDt = static function ($v): string {
    if ($v === null || $v === '') {
        return '—';
    }
    $t = strtotime((string)$v);

    return $t ? date('d/m/Y H:i', $t) : '—';
};
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Categorias</h1>
        <p class="page-subtitle">Grupos para organizar itens do catálogo e das entradas de material</p>
    </div>
    <div class="page-header-actions">
        <a href="<?= BASE_URL ?>/estoque/catalogo" class="btn btn-ghost"><i class="fas fa-barcode"></i> Catálogo de itens</a>
        <a href="<?= BASE_URL ?>/estoque" class="btn btn-ghost"><i class="fas fa-boxes"></i> Meu estoque</a>
    </div>
</div>

<?php if (!empty($flash)): ?>
<div class="alert alert-<?= htmlspecialchars($flash['type'] ?? 'info') ?> flash-msg"><?= htmlspecialchars($flash['message'] ?? '') ?></div>
<?php endif; ?>

<div class="categorias-page-grid">
    <div class="card categorias-card-form">
        <div class="card-header"><h3 class="card-title">Nova categoria</h3></div>
        <div class="card-body">
            <form method="post" action="<?= BASE_URL ?>/estoque/categorias/criar" class="categorias-form-inline">
                <div class="form-group categorias-form-inline__field">
                    <label class="form-label required" for="catNovaNome">Nome</label>
                    <input id="catNovaNome" type="text" name="nome" class="form-control form-control-sm" required maxlength="120" placeholder="Ex.: Elétrica">
                </div>
                <div class="form-group categorias-form-inline__field categorias-form-inline__field--grow">
                    <label class="form-label" for="catNovaDesc">Descrição</label>
                    <input id="catNovaDesc" type="text" name="descricao" class="form-control form-control-sm" maxlength="500" placeholder="Opcional">
                </div>
                <div class="form-group categorias-form-inline__btn">
                    <label class="form-label categorias-form-inline__label-sp">&nbsp;</label>
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Adicionar</button>
                </div>
            </form>
        </div>
    </div>
    <div class="card categorias-card-table">
        <div class="card-header"><h3 class="card-title">Categorias cadastradas</h3></div>
        <div class="card-body">
            <?php if (empty($categorias)): ?>
            <p class="text-muted compact catalogo-cat-empty">Nenhuma categoria cadastrada.</p>
            <?php else: ?>
            <div class="table-wrap">
                <table class="table table-datatable" data-dt-order="[]" data-dt-page-length="15" data-dt-no-sort-last="1">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Descrição</th>
                            <th>Itens</th>
                            <th>Status</th>
                            <th>Criado em</th>
                            <th>Última alteração</th>
                            <th class="table-actions-th"><span class="sr-only">Ações</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categorias as $c):
                            $criadoTs = strtotime((string)($c['created_at'] ?? '')) ?: 0;
                            $altRaw   = $c['updated_at'] ?? $c['created_at'] ?? '';
                            $altTs    = strtotime((string)$altRaw) ?: 0;
                        ?>
                        <tr>
                            <td><?= htmlspecialchars((string)$c['nome']) ?></td>
                            <td><?php
                                $d = trim((string)($c['descricao'] ?? ''));
                                echo $d !== '' ? htmlspecialchars($d) : '—';
                            ?></td>
                            <td data-order="<?= (int)($c['itens_qtd'] ?? 0) ?>"><?= (int)($c['itens_qtd'] ?? 0) ?></td>
                            <td><?= (int)($c['ativo'] ?? 0)
                                ? '<span class="badge badge-success">Ativo</span>'
                                : '<span class="badge">Inativo</span>' ?></td>
                            <td data-order="<?= $criadoTs ?>"><?= $fmtDt($c['created_at'] ?? null) ?></td>
                            <td data-order="<?= $altTs ?>"><?= $fmtDt($altRaw !== '' ? $altRaw : null) ?></td>
                            <td class="table-actions">
                                <a href="<?= BASE_URL ?>/estoque/categorias/<?= (int)$c['id'] ?>/editar" class="btn btn-ghost btn-sm" title="Editar"><i class="fas fa-edit"></i></a>
                                <form method="post" action="<?= BASE_URL ?>/estoque/categorias/<?= (int)$c['id'] ?>/excluir" class="form-inline-cat-del" onsubmit="return confirm('Excluir esta categoria? Só é permitido se nenhum item do catálogo estiver vinculado a ela.');">
                                    <button type="submit" class="btn btn-ghost btn-sm btn-icon-danger" title="Excluir"><i class="fas fa-trash-alt"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
