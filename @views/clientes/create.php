<?php
$pageTitle = 'Novo cliente';
$cliente = [];
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Novo cliente</h1>
        <p class="page-subtitle">Dados cadastrais para uso nas ordens de serviço</p>
    </div>
    <a href="<?= BASE_URL ?>/clientes" class="btn btn-ghost">
        <i class="fas fa-arrow-left"></i> Voltar
    </a>
</div>

<form method="POST" action="<?= BASE_URL ?>/clientes" novalidate>
    <?php require __DIR__ . '/_form_fields.php'; ?>
</form>
