<?php
$pageTitle = 'Usuários';
$fmtLim = static fn (?int $x): string => $x === null ? '∞' : (string)$x;
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Usuários</h1>
        <p class="page-subtitle">Gerencie os usuários do sistema</p>
    </div>
    <a href="<?= BASE_URL ?>/usuarios/criar" class="btn btn-primary">
        <i class="fas fa-plus"></i> Novo Usuário
    </a>
</div>

<div class="card">
    <div class="card-body p0">
        <div class="table-responsive">
            <table class="table table-hover table-datatable">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>E-mail</th>
                        <th>Perfil</th>
                        <th>Plano</th>
                        <th>Clientes</th>
                        <th>OS (mês)</th>
                        <th>Img/OS</th>
                        <th>Telegram</th>
                        <th>Último acesso</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u):
                        $rid = (int)$u['id'];
                        $r = $resumo[$rid] ?? [
                            'clientes' => 0, 'os_mes' => 0, 'max_cli' => null, 'max_os' => null, 'max_img' => 1,
                        ];
                        ?>
                    <tr>
                        <td>
                            <div class="user-row">
                                <div class="user-avatar-sm"><?= strtoupper(substr($u['nome'], 0, 1)) ?></div>
                                <?= htmlspecialchars($u['nome']) ?>
                            </div>
                        </td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td><span class="badge badge-perfil badge-<?= $u['perfil'] ?>"><?= ucfirst($u['perfil']) ?></span></td>
                        <td><?= htmlspecialchars($u['plano_nome'] ?? '') ?></td>
                        <td><?= (int)$r['clientes'] ?> / <?= $fmtLim($r['max_cli']) ?></td>
                        <td><?= (int)$r['os_mes'] ?> / <?= $fmtLim($r['max_os']) ?></td>
                        <td><?= (int)$r['max_img'] ?></td>
                        <td>
                            <?php if ($u['telegram_ativo']): ?>
                            <span class="badge badge-success"><i class="fab fa-telegram"></i> Vinculado</span>
                            <?php else: ?>
                            <span class="badge badge-muted">Não vinculado</span>
                            <?php endif; ?>
                        </td>
                        <td class="td-date"><?= $u['ultimo_acesso'] ? date('d/m/Y H:i', strtotime($u['ultimo_acesso'])) : '—' ?></td>
                        <td class="td-actions">
                            <a href="<?= BASE_URL ?>/usuarios/<?= $rid ?>/editar" class="btn btn-ghost btn-sm" title="Editar">
                                <i class="fas fa-edit"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
