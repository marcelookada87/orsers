<?php $pageTitle = 'Configuração'; ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Configuração</h1>
        <p class="page-subtitle">Preferências da sua conta · <a href="<?= BASE_URL ?>/perfil">Meu perfil</a></p>
    </div>
</div>

<?php if (!empty($flash)): ?>
<div class="alert alert-<?= htmlspecialchars($flash['type'] ?? 'info') ?> flash-msg"><?= htmlspecialchars($flash['message'] ?? '') ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header"><h3 class="card-title">Itens e catálogo</h3></div>
    <div class="card-body">
        <?php if (!empty($podeCatalogoCodigo)): ?>
        <p class="form-hint" style="margin-bottom:0.75rem">Prefixo sugerido para o código de novos itens do catálogo global.</p>
        <a href="<?= BASE_URL ?>/conta/configuracao/catalogo-codigo" class="btn btn-primary"><i class="fas fa-tag"></i> Tag do código (catálogo)</a>
        <?php else: ?>
        <p class="form-hint">Nenhuma opção disponível aqui no momento. (Requer perfil técnico com estoque ativo para editar a tag do catálogo.)</p>
        <?php endif; ?>
    </div>
</div>
