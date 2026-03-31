<?php
$pageTitle = 'Meu estoque';
$fmtQ = static fn ($v) => rtrim(rtrim(number_format((float)$v, 3, ',', '.'), '0'), ',') ?: '0';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Meu estoque</h1>
        <p class="page-subtitle">
            Tipos com saldo: <strong><?= (int)$tiposOk ?></strong>
            <?php if ($limite !== null && $limite !== ''): ?>
                · Limite: <strong><?= (int)$limite ?></strong> tipos distintos
            <?php else: ?>
                · Limite de tipos: <strong>ilimitado</strong>
            <?php endif; ?>
        </p>
    </div>
    <div class="page-header-actions">
        <?php if (Auth::isPerfilTecnico()): ?>
        <a href="<?= BASE_URL ?>/estoque/catalogo" class="btn btn-ghost"><i class="fas fa-barcode"></i> Catálogo e cadastros</a>
        <a href="<?= BASE_URL ?>/estoque/relatorio" class="btn btn-ghost"><i class="fas fa-chart-line"></i> Consumo em OS</a>
        <?php endif; ?>
        <a href="<?= BASE_URL ?>/estoque/historico" class="btn btn-ghost"><i class="fas fa-history"></i> Movimentações</a>
        <a href="<?= BASE_URL ?>/estoque/entrada" class="btn btn-primary"><i class="fas fa-plus"></i> Entrada de material</a>
    </div>
</div>

<div class="kpi-grid" style="margin-bottom:1.25rem">
    <div class="kpi-card kpi-blue">
        <div class="kpi-icon"><i class="fas fa-boxes"></i></div>
        <div class="kpi-body">
            <div class="kpi-value"><?= (int)$totalLinhas ?></div>
            <div class="kpi-label">Linhas de estoque</div>
        </div>
    </div>
    <div class="kpi-card kpi-orange">
        <div class="kpi-icon"><i class="fas fa-exclamation-triangle"></i></div>
        <div class="kpi-body">
            <div class="kpi-value"><?= (int)$baixo ?></div>
            <div class="kpi-label">Abaixo do mínimo</div>
        </div>
    </div>
    <div class="kpi-card kpi-gray">
        <div class="kpi-icon"><i class="fas fa-ban"></i></div>
        <div class="kpi-body">
            <div class="kpi-value"><?= (int)$zerado ?></div>
            <div class="kpi-label">Sem saldo (0)</div>
        </div>
    </div>
</div>

<form method="get" action="<?= BASE_URL ?>/estoque" class="estoque-filtros-toolbar" id="formEstoqueFiltros" autocomplete="off">
    <div class="estoque-filtros-toolbar__row">
        <label class="sr-only" for="estoqueFiltroSit">Situação</label>
        <select id="estoqueFiltroSit" name="f" class="form-control form-control-sm estoque-filtro-sel" title="Itens no mínimo = saldo no ou abaixo do mínimo (em risco de acabar)" onchange="document.getElementById('formEstoqueFiltros').submit()">
            <option value="todos"<?= ($filtro ?? 'todos') === 'todos' ? ' selected' : '' ?>>Todos</option>
            <option value="alerta"<?= ($filtro ?? '') === 'alerta' ? ' selected' : '' ?>>No mínimo (a vencer)</option>
            <option value="zerado"<?= ($filtro ?? '') === 'zerado' ? ' selected' : '' ?>>Sem saldo (0)</option>
            <option value="ok"<?= ($filtro ?? '') === 'ok' ? ' selected' : '' ?>>Acima do mínimo</option>
            <option value="inativo"<?= ($filtro ?? '') === 'inativo' ? ' selected' : '' ?>>Inativo no catálogo</option>
        </select>
        <?php if (!empty($categoriasOpts)): ?>
        <label class="sr-only" for="estoqueFiltroCat">Categoria</label>
        <select id="estoqueFiltroCat" name="cat" class="form-control form-control-sm estoque-filtro-sel" onchange="document.getElementById('formEstoqueFiltros').submit()">
            <option value=""<?= ($catFiltro ?? '') === '' ? ' selected' : '' ?>>Todas as categorias</option>
            <?php foreach ($categoriasOpts as $cOpt): ?>
            <option value="<?= htmlspecialchars($cOpt) ?>"<?= ($catFiltro ?? '') === $cOpt ? ' selected' : '' ?>><?= htmlspecialchars($cOpt) ?></option>
            <?php endforeach; ?>
        </select>
        <?php endif; ?>
        <label class="sr-only" for="estoqueFiltroQ">Buscar código ou nome</label>
        <input type="search" id="estoqueFiltroQ" name="q" value="<?= htmlspecialchars($busca ?? '') ?>" class="form-control form-control-sm estoque-filtro-q" placeholder="Código ou nome">
        <button type="submit" class="btn btn-primary btn-sm estoque-filtro-btn" title="Buscar"><i class="fas fa-search"></i></button>
        <?php if (!empty($filtroEstoqueAtivo)): ?>
        <a href="<?= BASE_URL ?>/estoque" class="btn btn-ghost btn-sm">Limpar</a>
        <?php endif; ?>
    </div>
</form>
<?php if (!empty($filtroEstoqueAtivo) && (int)($totalLinhas ?? 0) > 0): ?>
<p class="text-muted compact estoque-filtros-meta">Exibindo <strong><?= count($linhas) ?></strong> de <?= (int)$totalLinhas ?> linhas.</p>
<?php endif; ?>

<?php $mostrarColCat = !empty($categoriasOpts); ?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Saldos</h3>
    </div>
    <div class="card-body">
        <?php if (empty($linhasTodas ?? [])): ?>
        <div class="empty-state">
            <i class="fas fa-warehouse"></i>
            <p>Nenhum item no estoque. Use <strong>Entrada de material</strong> para dar entrada nos itens do catálogo.</p>
        </div>
        <?php elseif (empty($linhas)): ?>
        <div class="empty-state">
            <i class="fas fa-filter"></i>
            <p>Nenhuma linha com os filtros atuais. <a href="<?= BASE_URL ?>/estoque">Limpar filtros</a> ou ajuste a situação / busca.</p>
        </div>
        <?php else: ?>
        <div class="table-wrap">
            <table class="table table-datatable" data-dt-order="[]" data-dt-page-length="25" data-dt-no-sort-last="1">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Item</th>
                        <?php if ($mostrarColCat): ?><th class="th-cat">Cat.</th><?php endif; ?>
                        <th>Un.</th>
                        <th>Quantidade</th>
                        <th>Mínimo (alerta)</th>
                        <th class="table-actions-th" aria-label="Ações"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($linhas as $r):
                        $q = (float)$r['quantidade'];
                        $m = (float)$r['quantidade_minima'];
                        $cls = ($q <= 0) ? 'text-danger' : (($m > 0 && $q <= $m) ? 'text-warning' : '');
                    ?>
                    <tr class="<?= $cls ?>">
                        <td><code><?= htmlspecialchars($r['item_codigo']) ?></code></td>
                        <td><?= htmlspecialchars($r['item_nome']) ?></td>
                        <?php if ($mostrarColCat): ?>
                        <td class="td-cat text-muted"><?php
                            $cn = trim((string)($r['categoria_nome'] ?? ''));
                            echo $cn !== '' ? htmlspecialchars($cn) : '—';
                        ?></td>
                        <?php endif; ?>
                        <td><?= htmlspecialchars($r['item_unidade']) ?></td>
                        <td data-order="<?= htmlspecialchars((string)$q) ?>"><strong><?= $fmtQ($q) ?></strong></td>
                        <td>
                            <form method="post" action="<?= BASE_URL ?>/estoque/minimo" class="form-inline-minimo" style="display:flex;gap:.35rem;align-items:center">
                                <input type="hidden" name="item_id" value="<?= (int)$r['item_id'] ?>">
                                <input type="text" name="quantidade_minima" class="form-control form-control-sm" style="max-width:88px"
                                       value="<?= $fmtQ($m) ?>" inputmode="decimal">
                                <button type="submit" class="btn btn-ghost btn-sm" title="Salvar mínimo"><i class="fas fa-save"></i></button>
                            </form>
                        </td>
                        <td class="table-actions">
                            <?php if (Auth::isPerfilTecnico()): ?>
                            <a href="<?= BASE_URL ?>/estoque/catalogo/<?= (int)$r['item_id'] ?>/editar" class="btn btn-ghost btn-sm" title="Editar item no catálogo"><i class="fas fa-edit"></i></a>
                            <?php endif; ?>
                            <?php if ((int)($r['item_ativo'] ?? 0) === 0): ?>
                            <span class="badge badge-muted">Inativo</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
