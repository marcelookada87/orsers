<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — OS Manager</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/app.css">
</head>
<body class="login-body">

<div class="login-wrapper">
    <div class="login-illustration">
        <div class="illustration-content">
            <div class="ill-icon"><i class="fas fa-tools"></i></div>
            <h2>OS Manager</h2>
            <p>Sistema de Ordens de Serviço com SLA, imagens e integração Telegram.</p>
            <ul class="ill-features">
                <li><i class="fas fa-check"></i> Gestão completa de OS</li>
                <li><i class="fas fa-check"></i> Controle de SLA em tempo real</li>
                <li><i class="fas fa-check"></i> Upload de imagens comprimidas</li>
                <li><i class="fas fa-check"></i> Integração com Telegram</li>
                <li><i class="fas fa-check"></i> Multi-usuário SAAS</li>
            </ul>
        </div>
    </div>

    <div class="login-form-side">
        <div class="login-card">
            <div class="login-logo">
                <i class="fas fa-tools"></i>
            </div>
            <h1 class="login-title">Bem-vindo</h1>
            <p class="login-subtitle">Acesse sua conta para continuar</p>

            <?php if (!empty($flash)): ?>
            <div class="alert alert-<?= $flash['type'] ?>">
                <i class="fas fa-<?= $flash['type'] === 'error' ? 'exclamation-circle' : 'check-circle' ?>"></i>
                <?= htmlspecialchars($flash['message']) ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="<?= BASE_URL ?>/login" class="login-form" novalidate>
                <div class="form-group">
                    <label for="email" class="form-label">E-mail</label>
                    <div class="input-icon-wrapper">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" id="email" name="email" class="form-control"
                               placeholder="seu@email.com" autocomplete="email" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="senha" class="form-label">Senha</label>
                    <div class="input-icon-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" id="senha" name="senha" class="form-control"
                               placeholder="••••••••" autocomplete="current-password" required>
                        <button type="button" class="toggle-senha" id="toggleSenha" tabindex="-1">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group login-remember-row">
                    <label class="login-remember-label">
                        <input type="checkbox" name="remember" value="1">
                        <span>Manter conectado neste dispositivo (até 1 ano)</span>
                    </label>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-login">
                    <i class="fas fa-sign-in-alt"></i>
                    Entrar
                </button>
            </form>

            <p class="login-footer-text">OS Manager &copy; <?= date('Y') ?></p>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
$(function () {
    $('#toggleSenha').on('click', function () {
        const input = $('#senha');
        const icon  = $(this).find('i');
        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            input.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });
});
</script>
</body>
</html>
