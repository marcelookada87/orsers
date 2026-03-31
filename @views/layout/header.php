<!DOCTYPE html>
<html lang="pt-BR" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'OS Manager') ?> — OS Manager</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/app.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/vendor/jquery.dataTables.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/datatables-custom.css">
    <?php if (!empty($extraCss)): foreach ($extraCss as $css): ?>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/<?= $css ?>">
    <?php endforeach; endif; ?>
</head>
<body class="app-body">
<script>window.BASE_URL=<?= json_encode(BASE_URL, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>

<div class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="sidebar-logo">
            <i class="fas fa-tools"></i>
        </div>
        <span class="sidebar-brand-name">OS Manager</span>
    </div>

    <nav class="sidebar-nav">
        <?php
        $_uriNav  = $_SERVER['REQUEST_URI'] ?? '';
        $_pathNav = parse_url($_uriNav, PHP_URL_PATH) ?: '';
        $_pathNav = rtrim(str_replace('\\', '/', $_pathNav), '/');

        $_navGrouped = [];
        if (Auth::check()) {
            try {
                $_navGrouped = NavMenu::itensAgrupados((int)Auth::id(), Auth::perfil());
            } catch (Throwable $e) {
                error_log('NavMenu: ' . $e->getMessage());
            }
        }

        foreach (NavMenu::SECTION_LABELS as $_secCode => $_secLabel) {
            if (empty($_navGrouped[$_secCode])) {
                continue;
            }
            ?>
        <div class="nav-section-label"><?= htmlspecialchars($_secLabel) ?></div>
            <?php foreach ($_navGrouped[$_secCode] as $_it):
                $_active = NavMenu::itemEstaAtivo($_it, $_uriNav, $_pathNav) ? ' active' : '';
                $_cls    = trim('nav-item ' . (string)($_it['item_class'] ?? '') . $_active);
                $_href   = BASE_URL . (string)$_it['url_path'];
                $_icon   = trim((string)($_it['icon_class'] ?? ''));
                ?>
        <a href="<?= htmlspecialchars($_href) ?>" class="<?= htmlspecialchars($_cls) ?>">
                <?php if ($_icon !== ''): ?>
            <i class="<?= htmlspecialchars($_icon) ?>"></i>
                <?php endif; ?>
            <span><?= htmlspecialchars((string)$_it['label']) ?></span>
        </a>
            <?php endforeach; ?>
        <?php } ?>
    </nav>
</div>

<div class="main-wrapper">
    <header class="topbar">
        <button class="sidebar-toggle" id="sidebarToggle" aria-label="Menu">
            <i class="fas fa-bars"></i>
        </button>
        <div class="topbar-breadcrumb">
            <?= htmlspecialchars($pageTitle ?? 'Dashboard') ?>
        </div>
        <?php
        $_topbarEstoqueItens   = [];
        $_topbarEstoqueAlertas = 0;
        $_topbarEstoqueAtivo   = false;
        if (Auth::check()) {
            try {
                $_uidTop = (int)Auth::id();
                $_topbarEstoqueAtivo = LimiteConta::estoqueAtivo($_uidTop);
                if ($_topbarEstoqueAtivo) {
                    $_topbarEstoqueItens = (new EstoqueSaldo())->listarAlertasEstoqueMinimo($_uidTop);
                    $_topbarEstoqueAlertas = count($_topbarEstoqueItens);
                }
            } catch (Throwable $e) {
                error_log('Topbar alertas estoque: ' . $e->getMessage());
            }
        }
        $_topbarBadgeEstoque = $_topbarEstoqueAlertas > 10 ? '+9' : (string)$_topbarEstoqueAlertas;
        ?>
        <div class="topbar-right">
            <div class="topbar-user">
                <?php if ($_topbarEstoqueAtivo && $_topbarEstoqueAlertas > 0): ?>
                <div class="topbar-estoque-dropdown-wrap">
                    <button type="button"
                            class="topbar-estoque-bell js-topbar-estoque-bell"
                            aria-expanded="false"
                            aria-haspopup="true"
                            aria-controls="topbarEstoqueDropdownPanel"
                            title="Itens no ou abaixo do estoque mínimo">
                        <i class="fas fa-bell" aria-hidden="true"></i>
                        <span class="topbar-estoque-bell__badge"><?= htmlspecialchars($_topbarBadgeEstoque) ?></span>
                    </button>
                    <div id="topbarEstoqueDropdownPanel" class="topbar-estoque-dropdown" role="menu" hidden>
                        <div class="topbar-estoque-dropdown__head">Estoque mínimo</div>
                        <ul class="topbar-estoque-dropdown__list">
                            <?php foreach ($_topbarEstoqueItens as $_r):
                                $_cod = trim((string)$_r['item_codigo']);
                                $_nom = trim((string)$_r['item_nome']);
                            ?>
                            <li role="none">
                                <a href="<?= BASE_URL ?>/estoque" class="topbar-estoque-dropdown__item" role="menuitem">
                                    <span class="topbar-estoque-dropdown__code"><?= htmlspecialchars($_cod) ?></span>
                                    <span class="topbar-estoque-dropdown__nome"><?= htmlspecialchars($_nom) ?></span>
                                </a>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <a href="<?= BASE_URL ?>/estoque" class="topbar-estoque-dropdown__foot">Meu estoque completo</a>
                    </div>
                </div>
                <?php elseif ($_topbarEstoqueAtivo): ?>
                <a href="<?= BASE_URL ?>/estoque"
                   class="topbar-estoque-bell topbar-estoque-bell--quiet"
                   title="Meu estoque"
                   aria-label="Abrir meu estoque">
                    <i class="fas fa-bell" aria-hidden="true"></i>
                </a>
                <?php endif; ?>
                <div class="user-avatar<?= $_topbarEstoqueAlertas > 0 ? ' user-avatar--estoque-alerta' : '' ?>"<?= $_topbarEstoqueAlertas > 0 ? ' title="Estoque: itens no ou abaixo do mínimo configurado"' : '' ?>>
                    <?= strtoupper(substr(Auth::user()['nome'], 0, 1)) ?>
                </div>
                <div class="user-info">
                    <span class="user-name-wrap">
                        <?php if ($_topbarEstoqueAlertas > 0): ?>
                        <span class="user-name-alerta" title="Estoque mínimo"><i class="fas fa-exclamation-circle" aria-hidden="true"></i></span>
                        <?php endif; ?>
                        <span class="user-name"><?= htmlspecialchars(Auth::user()['nome']) ?></span>
                    </span>
                    <span class="user-role"><?= ucfirst(Auth::user()['perfil']) ?></span>
                </div>
            </div>
        </div>
    </header>

    <?php if (!empty($flash)): ?>
    <div class="flash-message flash-<?= $flash['type'] ?>" id="flashMessage">
        <i class="fas fa-<?= $flash['type'] === 'success' ? 'check-circle' : ($flash['type'] === 'error' ? 'exclamation-circle' : 'info-circle') ?>"></i>
        <?= htmlspecialchars($flash['message']) ?>
        <button class="flash-close" onclick="document.getElementById('flashMessage').remove()">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <?php endif; ?>

    <main class="main-content">
