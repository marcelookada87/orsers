<?php
$pageTitle = 'Configurações do sistema';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Configurações</h1>
        <p class="page-subtitle">Parâmetros globais (apenas administrador)</p>
    </div>
    <a href="<?= BASE_URL ?>/admin" class="btn btn-ghost">
        <i class="fas fa-arrow-left"></i> Painel admin
    </a>
</div>

<div class="form-grid narrow">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-images"></i> Imagens nas ordens de serviço</h3>
        </div>
        <div class="card-body">
            <form method="post" action="<?= BASE_URL ?>/admin/configuracoes">
                <div class="form-group">
                    <label class="form-label required" for="max_imagens_por_os">Máximo de imagens por OS</label>
                    <input type="number" name="max_imagens_por_os" id="max_imagens_por_os" class="form-control"
                           min="1" max="50" required value="<?= (int)$maxImagens ?>">
                    <small class="form-hint">Valor entre 1 e 50. É o <strong>teto global</strong>: cada plano ou override de usuário não pode ultrapassar este valor para imagens por OS.</small>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Salvar
                </button>
            </form>
        </div>
    </div>
</div>
