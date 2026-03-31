<?php
$pageTitle = 'Novo Usuário';
$extraJs   = ['user-plano-toggle.js'];
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Novo Usuário</h1>
    </div>
    <a href="<?= BASE_URL ?>/usuarios" class="btn btn-ghost">
        <i class="fas fa-arrow-left"></i> Voltar
    </a>
</div>

<div class="form-grid narrow">
    <div class="form-main">
        <div class="card">
            <div class="card-body">
                <form method="POST" action="<?= BASE_URL ?>/usuarios" novalidate id="formNovoUser">
                    <div class="form-group">
                        <label class="form-label required">Nome</label>
                        <input type="text" name="nome" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label required">E-mail</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label required">Senha</label>
                        <input type="password" name="senha" class="form-control" required minlength="6">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Perfil</label>
                        <select name="perfil" class="form-control" id="perfilSelect">
                            <option value="cliente">Cliente</option>
                            <option value="tecnico">Técnico</option>
                            <option value="admin">Administrador</option>
                        </select>
                    </div>
                    <div class="form-group" id="grupoPlanoOver">
                        <label class="form-label">Plano</label>
                        <select name="plano_id" class="form-control" id="selectPlanoUsuario" required>
                            <?php foreach ($planos as $p):
                                $d = trim((string)($p['descricao'] ?? ''));
                                $fi = $p['max_imagens_por_os'];
                                $fc = $p['max_clientes'];
                                $fo = $p['max_os_mes'];
                                $fmt = static fn($v) => $v === null || $v === '' ? '∞' : (string)(int)$v;
                            ?>
                            <option value="<?= (int)$p['id'] ?>"
                                    data-desc="<?= htmlspecialchars($d, ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($p['nome']) ?>
                                (<?= htmlspecialchars($p['codigo']) ?> —
                                <?= $fmt($fi) ?> img/OS · <?= $fmt($fc) ?> cli · <?= $fmt($fo) ?> OS/mês)
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="form-hint text-muted" id="planoDescricaoPreview" style="margin-top:.5rem"></p>
                        <small class="form-hint">Administradores usam sempre o plano Ilimitado.</small>
                    </div>
                    <div class="form-group limites-over" id="grupoOverrides">
                        <label class="form-label">Overrides (opcional)</label>
                        <p class="form-hint text-muted">Deixe em branco para usar só o plano. Útil para vender mais fotos ou vagas sem novo plano.</p>
                        <div class="form-row-grid">
                            <div>
                                <label class="form-label" for="o_img">Máx. imagens / OS</label>
                                <input type="number" name="max_imagens_por_os_override" id="o_img" class="form-control" min="0" placeholder="—">
                            </div>
                            <div>
                                <label class="form-label" for="o_cli">Máx. clientes</label>
                                <input type="number" name="max_clientes_override" id="o_cli" class="form-control" min="0" placeholder="—">
                            </div>
                            <div>
                                <label class="form-label" for="o_os">Máx. OS / mês</label>
                                <input type="number" name="max_os_mes_override" id="o_os" class="form-control" min="0" placeholder="—">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Estoque</label>
                        <label class="check-label" style="display:flex;align-items:center;gap:.5rem">
                            <input type="checkbox" name="estoque_ativo" value="1">
                            Módulo de estoque ativo para este usuário
                        </label>
                        <div class="form-group" style="margin-top:.5rem">
                            <label class="form-label" for="estoque_lim_n">Limite de tipos de item (vazio = ilimitado)</label>
                            <input type="number" name="estoque_limite_itens" id="estoque_lim_n" class="form-control" min="0" placeholder="Ex.: 10">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Criar Usuário
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
