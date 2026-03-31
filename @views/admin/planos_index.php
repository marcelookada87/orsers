<?php $pageTitle = 'Planos'; ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Planos</h1>
        <p class="page-subtitle">Limites por conta (imagens/OS, clientes, OS por mês)</p>
    </div>
    <div class="page-header-actions">
        <a href="<?= BASE_URL ?>/admin/planos/criar" class="btn btn-primary">
            <i class="fas fa-plus"></i> Novo plano
        </a>
        <a href="<?= BASE_URL ?>/admin" class="btn btn-ghost">
            <i class="fas fa-arrow-left"></i> Painel admin
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body p0">
        <div class="table-responsive">
            <table class="table table-hover table-datatable">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Código</th>
                        <th>Img/OS</th>
                        <th>Clientes</th>
                        <th>OS/mês</th>
                        <th>Usuários</th>
                        <th>Ordem</th>
                        <th>Ativo</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($planos as $p):
                        $pid = (int)$p['id'];
                        $nu = $cnt[$pid] ?? 0;
                        $fmt = static fn ($v) => ($v === null || $v === '') ? '∞' : (string)(int)$v;
                        ?>
                    <tr>
                        <td><?= htmlspecialchars($p['nome']) ?></td>
                        <td><code><?= htmlspecialchars($p['codigo']) ?></code></td>
                        <td><?= $fmt($p['max_imagens_por_os'] ?? null) ?></td>
                        <td><?= $fmt($p['max_clientes'] ?? null) ?></td>
                        <td><?= $fmt($p['max_os_mes'] ?? null) ?></td>
                        <td><?= (int)$nu ?></td>
                        <td><?= (int)($p['ordem'] ?? 0) ?></td>
                        <td><?= !empty($p['ativo']) ? 'Sim' : 'Não' ?></td>
                        <td class="td-actions">
                            <a href="<?= BASE_URL ?>/admin/planos/<?= $pid ?>/editar" class="btn btn-ghost btn-sm"><i class="fas fa-edit"></i></a>
                            <?php if (!in_array($p['codigo'], ['free', 'ilimitado'], true) && $nu === 0): ?>
                            <form method="post" action="<?= BASE_URL ?>/admin/planos/<?= $pid ?>/excluir" class="form-inline"
                                  onsubmit="return confirm('Excluir este plano?');">
                                <button type="submit" class="btn btn-ghost btn-sm text-danger" title="Excluir"><i class="fas fa-trash"></i></button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
