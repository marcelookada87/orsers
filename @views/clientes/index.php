<?php
$pageTitle = 'Clientes';
$totalPaginas = $limit > 0 ? (int)ceil($total / $limit) : 1;
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Clientes</h1>
        <p class="page-subtitle">Cadastro de clientes para vincular às ordens de serviço</p>
    </div>
    <a href="<?= BASE_URL ?>/clientes/criar" class="btn btn-primary">
        <i class="fas fa-plus"></i> Novo cliente
    </a>
</div>

<div class="card filter-card">
    <form method="GET" action="<?= BASE_URL ?>/clientes" class="filter-form">
        <div class="filter-row">
            <div class="filter-group" style="flex:2">
                <label>Buscar</label>
                <div class="input-icon-wrapper">
                    <i class="fas fa-search input-icon"></i>
                    <input type="text" name="busca" class="form-control"
                           placeholder="Nome, fantasia, documento ou e-mail..." value="<?= htmlspecialchars($filtros['busca'] ?? '') ?>">
                </div>
            </div>
            <div class="filter-group">
                <label>Status</label>
                <select name="ativo" class="form-control">
                    <option value="">Todos</option>
                    <option value="1" <?= ($filtros['ativo'] ?? '') === '1' ? 'selected' : '' ?>>Ativos</option>
                    <option value="0" <?= ($filtros['ativo'] ?? '') === '0' ? 'selected' : '' ?>>Inativos</option>
                </select>
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter"></i> Filtrar</button>
                <a href="<?= BASE_URL ?>/clientes" class="btn btn-ghost btn-sm">Limpar</a>
            </div>
        </div>
    </form>
</div>

<div class="card">
    <div class="card-body p0">
        <div class="table-responsive">
            <table class="table table-hover table-datatable" data-dt-no-sort-last="1">
                <thead>
                    <tr>
                        <th>Nome / Razão social</th>
                        <th>Fantasia</th>
                        <th>Documento</th>
                        <th>Cidade / UF</th>
                        <th>Contato</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clientes as $c): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($c['nome_razao_social']) ?></strong></td>
                        <td><?= htmlspecialchars($c['nome_fantasia'] ?? '') ?></td>
                        <td><?= htmlspecialchars($c['documento'] ?? '—') ?></td>
                        <td><?= htmlspecialchars(trim(($c['cidade'] ?? '') . ' / ' . ($c['uf'] ?? ''), ' /')) ?: '—' ?></td>
                        <td class="td-date"><?= htmlspecialchars($c['email'] ?? $c['telefone'] ?? $c['celular'] ?? '—') ?></td>
                        <td>
                            <?php if ($c['ativo']): ?>
                            <span class="badge badge-success">Ativo</span>
                            <?php else: ?>
                            <span class="badge badge-muted">Inativo</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?= BASE_URL ?>/clientes/<?= $c['id'] ?>/editar" class="btn-icon" title="Editar">
                                <i class="fas fa-edit"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($clientes)): ?>
                    <tr>
                        <td colspan="7" class="empty-td">
                            <div class="empty-state">
                                <i class="fas fa-address-book"></i>
                                <p>Nenhum cliente encontrado</p>
                                <a href="<?= BASE_URL ?>/clientes/criar" class="btn btn-primary btn-sm">Cadastrar primeiro cliente</a>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if ($totalPaginas > 1): ?>
<div class="pagination-bar">
    <span class="pagination-info">Página <?= (int)$page ?> de <?= $totalPaginas ?></span>
    <div class="pagination-links">
        <?php if ($page > 1): ?>
        <a href="<?= BASE_URL ?>/clientes?pagina=<?= $page - 1 ?>&<?= http_build_query(array_filter($filtros)) ?>" class="btn btn-ghost btn-sm">Anterior</a>
        <?php endif; ?>
        <?php if ($page < $totalPaginas): ?>
        <a href="<?= BASE_URL ?>/clientes?pagina=<?= $page + 1 ?>&<?= http_build_query(array_filter($filtros)) ?>" class="btn btn-ghost btn-sm">Próxima</a>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>
