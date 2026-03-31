<?php $pageTitle = 'Catálogo de estoque'; ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Catálogo de itens</h1>
        <p class="page-subtitle">Itens globais disponíveis para entrada de estoque dos técnicos</p>
    </div>
    <a href="<?= BASE_URL ?>/admin/estoque" class="btn btn-ghost"><i class="fas fa-arrow-left"></i> Estoque</a>
</div>

<div class="form-grid" style="grid-template-columns: 1fr 1fr; gap: 1.25rem; align-items:start;">
    <div class="card">
        <div class="card-header"><h3 class="card-title">Novo item</h3></div>
        <div class="card-body">
            <form method="post" action="<?= BASE_URL ?>/admin/estoque/catalogo/criar">
                <div class="form-group">
                    <label class="form-label required">Código</label>
                    <input type="text" name="codigo" class="form-control" required maxlength="64"
                           value="<?= htmlspecialchars($sugerido) ?>"
                           pattern="[A-Z0-9._\-]{1,64}" title="Maiúsculas, números, . _ -">
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
                    <label class="form-label">Descrição</label>
                    <textarea name="descricao" class="form-control" rows="2" maxlength="500"></textarea>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Cadastrar</button>
            </form>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><h3 class="card-title">Nova categoria</h3></div>
        <div class="card-body">
            <form method="post" action="<?= BASE_URL ?>/admin/estoque/categoria/criar">
                <div class="form-group">
                    <label class="form-label required">Nome</label>
                    <input type="text" name="nome" class="form-control" required maxlength="120">
                </div>
                <div class="form-group">
                    <label class="form-label">Descrição</label>
                    <input type="text" name="descricao" class="form-control" maxlength="500">
                </div>
                <button type="submit" class="btn btn-secondary btn-sm">Adicionar categoria</button>
            </form>
        </div>
    </div>
</div>

<div class="card" style="margin-top:1.25rem">
    <div class="card-header"><h3 class="card-title">Itens cadastrados</h3></div>
    <div class="card-body">
        <div class="table-wrap">
            <table class="table table-datatable" data-dt-order="[]" data-dt-page-length="25">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Nome</th>
                        <th>Categoria</th>
                        <th>Un.</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($itens as $it): ?>
                    <tr>
                        <td><code><?= htmlspecialchars($it['codigo']) ?></code></td>
                        <td><?= htmlspecialchars($it['nome']) ?></td>
                        <td><?= htmlspecialchars((string)($it['categoria_nome'] ?? '—')) ?></td>
                        <td><?= htmlspecialchars($it['unidade']) ?></td>
                        <td><?= (int)($it['ativo'] ?? 0) ? '<span class="badge badge-success">Ativo</span>' : '<span class="badge">Inativo</span>' ?></td>
                        <td class="table-actions">
                            <a href="<?= BASE_URL ?>/admin/estoque/catalogo/<?= (int)$it['id'] ?>/editar" class="btn btn-ghost btn-sm"><i class="fas fa-edit"></i></a>
                            <form method="post" action="<?= BASE_URL ?>/admin/estoque/catalogo/<?= (int)$it['id'] ?>/toggle" style="display:inline" onsubmit="return confirm('Alterar status deste item?');">
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
