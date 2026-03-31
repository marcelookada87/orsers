<?php
$pageTitle = 'Editar usuário';
$extraJs   = ['user-plano-toggle.js'];
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Editar usuário</h1>
        <p class="page-subtitle"><?= htmlspecialchars($user['email']) ?></p>
    </div>
    <a href="<?= BASE_URL ?>/usuarios" class="btn btn-ghost">
        <i class="fas fa-arrow-left"></i> Voltar
    </a>
</div>

<div class="form-grid narrow">
    <div class="form-main">
        <div class="card">
            <div class="card-body">
                <form method="POST" action="<?= BASE_URL ?>/usuarios/<?= (int)$user['id'] ?>/atualizar" novalidate id="formEditUser">
                    <div class="form-group">
                        <label class="form-label required">Nome</label>
                        <input type="text" name="nome" class="form-control" required value="<?= htmlspecialchars($user['nome']) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label required">E-mail</label>
                        <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($user['email']) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nova senha (opcional)</label>
                        <input type="password" name="senha" class="form-control" minlength="6" autocomplete="new-password" placeholder="Deixe em branco para manter">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Perfil</label>
                        <select name="perfil" class="form-control" id="perfilSelect">
                            <option value="cliente" <?= ($user['perfil'] === 'cliente') ? 'selected' : '' ?>>Cliente</option>
                            <option value="tecnico" <?= ($user['perfil'] === 'tecnico') ? 'selected' : '' ?>>Técnico</option>
                            <option value="admin" <?= ($user['perfil'] === 'admin') ? 'selected' : '' ?>>Administrador</option>
                        </select>
                    </div>
                    <div class="form-group" id="grupoPlanoOver">
                        <label class="form-label">Plano</label>
                        <select name="plano_id" class="form-control" id="selectPlanoUsuario" required>
                            <?php foreach ($planos as $p):
                                $d = trim((string)($p['descricao'] ?? ''));
                            ?>
                            <option value="<?= (int)$p['id'] ?>" <?= ((int)$user['plano_id'] === (int)$p['id']) ? 'selected' : '' ?>
                                    data-desc="<?= htmlspecialchars($d, ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($p['nome']) ?>
                                <?php
                                $fi = $p['max_imagens_por_os'];
                                $fc = $p['max_clientes'];
                                $fo = $p['max_os_mes'];
                                $fmt = static fn($v) => $v === null ? '∞' : (string)(int)$v;
                                ?>
                                (<?= htmlspecialchars($p['codigo']) ?> — <?= $fmt($fi) ?> img/OS · <?= $fmt($fc) ?> cli · <?= $fmt($fo) ?> OS/mês)
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="form-hint text-muted" id="planoDescricaoPreview" style="margin-top:.5rem"></p>
                    </div>
                    <div class="form-group limites-over" id="grupoOverrides">
                        <label class="form-label">Overrides (opcional)</label>
                        <div class="form-row-grid">
                            <div>
                                <label class="form-label" for="o_img">Máx. imagens / OS</label>
                                <input type="number" name="max_imagens_por_os_override" id="o_img" class="form-control" min="0" placeholder="—"
                                       value="<?= $user['max_imagens_por_os_override'] !== null && $user['max_imagens_por_os_override'] !== '' ? (int)$user['max_imagens_por_os_override'] : '' ?>">
                            </div>
                            <div>
                                <label class="form-label" for="o_cli">Máx. clientes</label>
                                <input type="number" name="max_clientes_override" id="o_cli" class="form-control" min="0" placeholder="—"
                                       value="<?= $user['max_clientes_override'] !== null && $user['max_clientes_override'] !== '' ? (int)$user['max_clientes_override'] : '' ?>">
                            </div>
                            <div>
                                <label class="form-label" for="o_os">Máx. OS / mês</label>
                                <input type="number" name="max_os_mes_override" id="o_os" class="form-control" min="0" placeholder="—"
                                       value="<?= $user['max_os_mes_override'] !== null && $user['max_os_mes_override'] !== '' ? (int)$user['max_os_mes_override'] : '' ?>">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Estoque</label>
                        <label class="check-label" style="display:flex;align-items:center;gap:.5rem">
                            <input type="checkbox" name="estoque_ativo" value="1" <?= !empty($user['estoque_ativo'] ?? null) ? 'checked' : '' ?>>
                            Módulo de estoque ativo para este usuário
                        </label>
                        <div class="form-group" style="margin-top:.5rem">
                            <label class="form-label" for="estoque_lim">Limite de tipos de item (vazio = ilimitado)</label>
                            <input type="number" name="estoque_limite_itens" id="estoque_lim" class="form-control" min="0" placeholder="Ex.: 10"
                                   value="<?= isset($user['estoque_limite_itens']) && $user['estoque_limite_itens'] !== null && $user['estoque_limite_itens'] !== '' ? (int)$user['estoque_limite_itens'] : '' ?>">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Salvar
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
