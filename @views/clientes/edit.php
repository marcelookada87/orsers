<?php
$pageTitle = 'Editar cliente';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Editar cliente</h1>
        <p class="page-subtitle"><?= htmlspecialchars($cliente['nome_razao_social']) ?></p>
    </div>
    <a href="<?= BASE_URL ?>/clientes" class="btn btn-ghost">
        <i class="fas fa-arrow-left"></i> Voltar
    </a>
</div>

<form method="POST" action="<?= BASE_URL ?>/clientes/<?= (int)$cliente['id'] ?>/atualizar" novalidate>
    <?php require __DIR__ . '/_form_fields.php'; ?>
</form>
