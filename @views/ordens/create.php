<?php
$pageTitle = 'Nova Ordem de Serviço';
$extraJs   = ['ordens.js', 'image-upload.js'];
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Nova Ordem de Serviço</h1>
        <p class="page-subtitle">Preencha todos os campos obrigatórios</p>
    </div>
    <a href="<?= BASE_URL ?>/ordens" class="btn btn-ghost">
        <i class="fas fa-arrow-left"></i> Voltar
    </a>
</div>

<form method="POST" action="<?= BASE_URL ?>/ordens" enctype="multipart/form-data" id="formOS" novalidate>

<div class="form-grid">
    <!-- Coluna principal -->
    <div class="form-main">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Dados da OS</h3></div>
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label required">Título</label>
                    <input type="text" name="titulo" id="inputTituloOs" class="form-control" required maxlength="200"
                           placeholder="Descreva brevemente o problema ou serviço" autocomplete="off">
                    <?php require ROOT_PATH . '/@views/ordens/partials/titulo_sugestoes.php'; ?>
                </div>
                <div class="form-group">
                    <label class="form-label required">Descrição</label>
                    <textarea name="descricao" class="form-control textarea-lg" required rows="6"
                              placeholder="Descreva detalhadamente o que precisa ser feito..."></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Observações</label>
                    <textarea name="observacoes" class="form-control" rows="3"
                              placeholder="Informações adicionais, localização, etc."></textarea>
                </div>
            </div>
        </div>

        <!-- Upload de imagens -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-images"></i> Imagens</h3>
                <span class="card-badge"><?= (int)$limiteImagensOs ?> máx. · <?= MAX_IMAGE_SIZE_KB ?>KB</span>
            </div>
            <div class="card-body">
                <div class="upload-area" id="uploadArea" data-max-files="<?= (int)$limiteImagensOs ?>">
                    <input type="file" name="imagens[]" id="fileInput" class="upload-file-input" multiple
                           accept="image/jpeg,image/png,image/webp,image/gif">
                    <div class="upload-placeholder" id="uploadPlaceholder">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p>Arraste ou <span class="upload-link">selecione</span> · JPG/PNG/WebP/GIF</p>
                        <small>Até <?= MAX_IMAGE_SIZE_KB ?>KB após compressão</small>
                    </div>
                    <div class="image-preview-grid" id="imagePreviewGrid"></div>
                </div>
                <div class="upload-counter">
                    <span id="imageCount">0</span>/<?= (int)$limiteImagensOs ?> · selecionadas
                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="form-sidebar">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Classificação</h3></div>
            <div class="card-body">
                <div class="form-group">
                    <div class="label-row">
                        <label class="form-label required">Cliente</label>
                        <?php if (Auth::isPerfilTecnico()): ?>
                        <button type="button" class="btn btn-ghost btn-xs btn-cliente-rapido" id="btnAbrirClienteRapido" title="Cadastro rápido sem sair da página">
                            <i class="fas fa-bolt"></i> Novo cliente rápido
                        </button>
                        <?php endif; ?>
                    </div>
                    <select name="cliente_id" id="selectClienteOs" class="form-control" required>
                        <option value="" disabled selected>Selecione o cliente…</option>
                        <?php foreach ($clientes as $cl): ?>
                        <option value="<?= (int)$cl['id'] ?>">
                            <?= htmlspecialchars(Cliente::rotuloExibicao($cl)) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="form-hint">Obrigatório.<?php if (Auth::isPerfilTecnico()): ?> Cadastro em <a href="<?= BASE_URL ?>/clientes">Clientes</a>.<?php elseif (Auth::isAdmin()): ?> Clientes são cadastrados por usuários com perfil técnico.<?php else: ?> Lista definida pela equipe.<?php endif; ?><?php if (empty($clientes)): ?><?php if (Auth::isPerfilTecnico()): ?> Cadastre pelo menos um cliente antes de abrir a OS.<?php elseif (Auth::isAdmin()): ?> Nenhum cliente disponível; solicite o cadastro a um técnico.<?php else: ?> Aguarde o cadastro de clientes pela equipe.<?php endif; ?><?php endif; ?></small>
                    <div class="inline-toast ok" id="toastClienteOsOk" style="display:none" role="status"></div>
                </div>
                <div class="form-group">
                    <label class="form-label required">Categoria</label>
                    <select name="categoria_id" class="form-control" required id="categoriaSelect">
                        <option value="">Selecione...</option>
                        <?php foreach ($categorias as $c): ?>
                        <option value="<?= $c['id'] ?>" data-sla="<?= $c['sla_horas'] ?>">
                            <?= htmlspecialchars($c['nome']) ?>
                            (SLA: <?= $c['sla_horas'] ?>h)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label required">Prioridade</label>
                    <div class="priority-grid">
                        <?php foreach ($prioridades as $p): ?>
                        <label class="priority-option">
                            <input type="radio" name="prioridade_id" value="<?= $p['id'] ?>"
                                   <?= $p['nivel'] == 3 ? 'checked' : '' ?>
                                   data-mult="<?= $p['sla_multiplicador'] ?>">
                            <span class="priority-badge" style="--prio-color: <?= htmlspecialchars($p['cor']) ?>; border-color: <?= htmlspecialchars($p['cor']) ?>; color: <?= htmlspecialchars($p['cor']) ?>">
                                <?= htmlspecialchars($p['nome']) ?>
                            </span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="sla-preview" id="slaPreview" style="display:none">
                    <div class="sla-preview-label">SLA estimado</div>
                    <div class="sla-preview-value" id="slaPreviewValue">—</div>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary btn-block btn-lg">
            <i class="fas fa-save"></i> Criar Ordem de Serviço
        </button>
    </div>
</div>
</form>

<?php if (Auth::isPerfilTecnico()): ?>
<?php require __DIR__ . '/_modal_cliente_rapido.php'; ?>
<?php endif; ?>
