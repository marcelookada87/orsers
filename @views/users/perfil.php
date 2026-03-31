<?php
$pageTitle = 'Meu Perfil';
$extraJs   = ['perfil.js'];
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Meu Perfil</h1>
        <p class="page-subtitle">Gerencie seus dados e vinculação com Telegram</p>
    </div>
</div>

<div class="form-grid">
    <div class="form-main">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Dados Pessoais</h3></div>
            <div class="card-body">
                <form method="POST" action="<?= BASE_URL ?>/perfil/atualizar" novalidate>
                    <div class="form-group">
                        <label class="form-label">Nome</label>
                        <input type="text" name="nome" class="form-control" value="<?= htmlspecialchars($user['nome']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">E-mail</label>
                        <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Telefone</label>
                        <input type="text" name="telefone" class="form-control" value="<?= htmlspecialchars($user['telefone'] ?? '') ?>" placeholder="(11) 99999-9999">
                    </div>
                    <hr>
                    <h4 class="section-subtitle">Alterar Senha</h4>
                    <div class="form-group">
                        <label class="form-label">Senha Atual</label>
                        <input type="password" name="senha_atual" class="form-control" autocomplete="current-password">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nova Senha</label>
                        <input type="password" name="nova_senha" class="form-control" autocomplete="new-password">
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Salvar alterações
                    </button>
                </form>
            </div>
        </div>

        <?php if (Auth::isPerfilTecnico() && !empty($planos)): ?>
        <div class="card">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-layer-group"></i> Alterar plano</h3></div>
            <div class="card-body">
                <p class="text-muted" style="margin-top:0">
                    Plano atual: <strong><?= htmlspecialchars($user['plano_nome'] ?? '—') ?></strong>
                </p>
                <?php if (
                    ($user['max_imagens_por_os_override'] ?? null) !== null && $user['max_imagens_por_os_override'] !== ''
                    || ($user['max_clientes_override'] ?? null) !== null && $user['max_clientes_override'] !== ''
                    || ($user['max_os_mes_override'] ?? null) !== null && $user['max_os_mes_override'] !== ''
                ): ?>
                <p class="form-hint text-muted">
                    <i class="fas fa-info-circle"></i> Há limites personalizados definidos pelo administrador; eles prevalecem sobre o plano.
                </p>
                <?php endif; ?>
                <form method="POST" action="<?= BASE_URL ?>/perfil/plano" novalidate>
                    <div class="form-group">
                        <label class="form-label" for="selectPlanoPerfil">Selecione o plano</label>
                        <p class="form-hint text-muted" style="margin-top:0;margin-bottom:8px">
                            Ao trocar a opção, aparece abaixo o que há de especial em cada plano (texto cadastrado na descrição).
                        </p>
                        <select name="plano_id" class="form-control" id="selectPlanoPerfil" required>
                            <?php foreach ($planos as $p):
                                $d = trim((string)($p['descricao'] ?? ''));
                                $sel = ((int)$user['plano_id'] === (int)$p['id']) ? ' selected' : '';
                                $ativo = (int)($p['ativo'] ?? 1);
                                $ehAtual = ((int)$user['plano_id'] === (int)$p['id']);
                                $optDisabled = !$ativo && !$ehAtual;
                                $nomePlano = (string)($p['nome'] ?? '');
                            ?>
                            <option value="<?= (int)$p['id'] ?>"<?= $sel ?>
                                    data-nome="<?= htmlspecialchars($nomePlano, ENT_QUOTES, 'UTF-8') ?>"
                                    data-desc="<?= htmlspecialchars($d, ENT_QUOTES, 'UTF-8') ?>"
                                    <?= $optDisabled ? ' disabled' : '' ?>>
                                <?= htmlspecialchars($p['nome']) ?><?= $ativo ? '' : ' — indisponível' ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="plano-escolha-info" id="planoEscolhaInfo" role="region" aria-live="polite">
                            <div class="plano-escolha-info-head">
                                <i class="fas fa-info-circle" aria-hidden="true"></i>
                                <span id="planoEscolhaTitulo"></span>
                            </div>
                            <p class="plano-escolha-info-texto" id="planoEscolhaTexto"></p>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-secondary">
                        <i class="fas fa-check"></i> Salvar plano
                    </button>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="form-sidebar">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fab fa-telegram"></i> Telegram</h3>
            </div>
            <div class="card-body">
                <?php if ($user['telegram_ativo'] && $user['telegram_chat_id']): ?>
                <div class="telegram-status connected">
                    <i class="fas fa-check-circle"></i>
                    <div>
                        <strong>Conta vinculada</strong>
                        <p>Chat ID: <?= htmlspecialchars($user['telegram_chat_id']) ?></p>
                    </div>
                </div>
                <?php else: ?>
                <div class="telegram-status disconnected">
                    <i class="fas fa-unlink"></i>
                    <div>
                        <strong>Não vinculado</strong>
                        <p>Vincule sua conta para receber notificações e criar OS pelo Telegram.</p>
                    </div>
                </div>
                <button class="btn btn-telegram btn-block" id="btnGerarToken">
                    <i class="fab fa-telegram"></i> Vincular Telegram
                </button>
                <div class="telegram-token-box" id="telegramTokenBox" style="display:none">
                    <p>Abra o bot e envie o comando:</p>
                    <code id="telegramCmd" class="token-cmd"></code>
                    <small>Token válido por 10 minutos.</small>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3 class="card-title">Informações da Conta</h3></div>
            <div class="card-body">
                <div class="info-row">
                    <span class="info-label">Perfil</span>
                    <span class="info-value"><?= ucfirst($user['perfil']) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Membro desde</span>
                    <span class="info-value"><?= date('d/m/Y', strtotime($user['created_at'])) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Último acesso</span>
                    <span class="info-value"><?= $user['ultimo_acesso'] ? date('d/m/Y H:i', strtotime($user['ultimo_acesso'])) : '—' ?></span>
                </div>
            </div>
        </div>
    </div>
</div>
