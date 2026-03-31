<?php $pageTitle = 'Permissões de estoque'; ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Estoque por usuário</h1>
        <p class="page-subtitle">Somente isto fica com o administrador: quem pode usar estoque. Cadastro de peças, entradas e lançamentos nas OS são feitos pelo <strong>perfil técnico</strong> em Meu estoque / Catálogo.</p>
    </div>
    <a href="<?= BASE_URL ?>/admin" class="btn btn-ghost"><i class="fas fa-arrow-left"></i> Painel admin</a>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-wrap">
            <table class="table table-datatable" data-dt-order="[]" data-dt-page-length="25">
                <thead>
                    <tr>
                        <th>Usuário</th>
                        <th>Perfil</th>
                        <th>Controle de acesso</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?= htmlspecialchars($u['nome']) ?><br><small class="text-muted"><?= htmlspecialchars($u['email']) ?></small></td>
                        <td><?= htmlspecialchars($u['perfil']) ?></td>
                        <td>
                            <form method="post" action="<?= BASE_URL ?>/admin/estoque/usuarios/<?= (int)$u['id'] ?>/acesso" class="form-estoque-acesso" style="display:flex;flex-wrap:wrap;gap:.5rem;align-items:center">
                                <label class="check-label" style="display:flex;align-items:center;gap:.35rem;margin:0">
                                    <input type="checkbox" name="estoque_ativo" value="1" <?= !empty($u['estoque_ativo']) ? 'checked' : '' ?>>
                                    Ativo
                                </label>
                                <input type="number" name="estoque_limite_itens" class="form-control form-control-sm" style="max-width:100px" min="0"
                                       placeholder="∞ vazio"
                                       value="<?= isset($u['estoque_limite_itens']) && $u['estoque_limite_itens'] !== null && $u['estoque_limite_itens'] !== '' ? (int)$u['estoque_limite_itens'] : '' ?>">
                                <small class="text-muted">Limite tipos vazio = ∞</small>
                                <button type="submit" class="btn btn-primary btn-sm">Salvar</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
