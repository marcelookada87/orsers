<?php
$pageTitle = 'Editar OS — ' . $ordem['numero'];
$extraJs   = ['ordens.js', 'image-upload.js'];
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Editar: <?= htmlspecialchars($ordem['numero']) ?></h1>
        <p class="page-subtitle"><?= htmlspecialchars($ordem['titulo']) ?></p>
    </div>
    <div class="page-header-actions">
        <button type="submit" form="formOS" class="btn btn-primary">
            <i class="fas fa-save"></i> Salvar alterações
        </button>
        <a href="<?= BASE_URL ?>/ordens/<?= $ordem['id'] ?>" class="btn btn-ghost">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>
</div>

<form method="POST" action="<?= BASE_URL ?>/ordens/<?= $ordem['id'] ?>/atualizar" enctype="multipart/form-data" id="formOS" novalidate>

<div class="form-grid">
    <div class="form-main">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Dados da OS</h3></div>
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label required">Título</label>
                    <input type="text" name="titulo" id="inputTituloOs" class="form-control" required maxlength="200"
                           value="<?= htmlspecialchars($ordem['titulo']) ?>" autocomplete="off">
                    <?php require ROOT_PATH . '/@views/ordens/partials/titulo_sugestoes.php'; ?>
                </div>
                <div class="form-group">
                    <label class="form-label required">Descrição</label>
                    <textarea name="descricao" class="form-control textarea-lg" required rows="6"><?= htmlspecialchars($ordem['descricao']) ?></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Observações</label>
                    <textarea name="observacoes" class="form-control" rows="3"><?= htmlspecialchars($ordem['observacoes'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <!-- Imagens existentes -->
        <?php if (!empty($imagens)): ?>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-images"></i> Imagens Atuais</h3>
                <span class="card-badge"><?= $totalImagens ?>/<?= (int)$limiteImagensOs ?></span>
            </div>
            <div class="card-body">
                <div class="image-gallery-grid">
                    <?php foreach ($imagens as $img):
                        $_ep = str_replace('\\', '/', (string)$img['arquivo']);
                        $_eparts = array_values(array_filter(explode('/', $_ep)));
                        $_eurl = UPLOAD_URL . '/' . implode('/', array_map('rawurlencode', $_eparts));
                    ?>
                    <div class="gallery-item" id="img-<?= $img['id'] ?>">
                        <a href="<?= htmlspecialchars($_eurl) ?>" class="gallery-lightbox" data-img-id="<?= (int)$img['id'] ?>">
                            <img src="<?= htmlspecialchars($_eurl) ?>"
                                 alt="Imagem da OS" loading="lazy"
                                 onerror="this.style.opacity='.35'; this.alt='Falha ao carregar';">
                        </a>
                        <div class="gallery-overlay">
                            <span class="gallery-size"><?= $img['tamanho_kb'] ?>KB</span>
                            <?php if (Auth::isTecnico()): ?>
                            <button type="button" class="gallery-delete"
                                    data-ordem="<?= $ordem['id'] ?>" data-img="<?= $img['id'] ?>"
                                    title="Remover">
                                <i class="fas fa-trash"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Upload de novas imagens -->
        <?php if ($totalImagens < (int)$limiteImagensOs): ?>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-images"></i> Imagens</h3>
                <span class="card-badge"><?= (int)$limiteImagensOs - $totalImagens ?> vaga(s)</span>
            </div>
            <div class="card-body">
                <div class="upload-area" id="uploadArea" data-max-files="<?= (int)((int)$limiteImagensOs - $totalImagens) ?>">
                    <input type="file" name="imagens[]" id="fileInput" class="upload-file-input" multiple
                           accept="image/jpeg,image/png,image/webp,image/gif">
                    <div class="upload-placeholder" id="uploadPlaceholder">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p>Arraste ou <span class="upload-link">selecione</span> · JPG/PNG/WebP/GIF</p>
                    </div>
                    <div class="image-preview-grid" id="imagePreviewGrid"></div>
                </div>
                <div class="upload-counter">
                    <span id="imageCount">0</span>/<?= (int)((int)$limiteImagensOs - $totalImagens) ?> · novas
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

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
                        <option value="" disabled <?= empty($ordem['cliente_id']) ? 'selected' : '' ?>>Selecione o cliente…</option>
                        <?php foreach ($clientes as $cl): ?>
                        <option value="<?= (int)$cl['id'] ?>" <?= (int)($ordem['cliente_id'] ?? 0) === (int)$cl['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars(Cliente::rotuloExibicao($cl)) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="form-hint"><?php if (Auth::isPerfilTecnico()): ?><a href="<?= BASE_URL ?>/clientes">Gerenciar clientes</a><?php elseif (Auth::isAdmin()): ?>Cadastro de clientes é feito por usuários com perfil técnico.<?php else: ?>Alteração de cadastro pela equipe técnica.<?php endif; ?></small>
                    <div class="inline-toast ok" id="toastClienteOsOk" style="display:none" role="status"></div>
                </div>
                <?php if (Auth::isTecnico()): ?>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control">
                        <?php foreach (['aberta','em_andamento','aguardando','finalizada','cancelada'] as $s): ?>
                        <option value="<?= $s ?>" <?= $ordem['status'] === $s ? 'selected' : '' ?>>
                            <?= ucfirst(str_replace('_', ' ', $s)) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php else: ?>
                <input type="hidden" name="status" value="<?= $ordem['status'] ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label class="form-label required">Categoria</label>
                    <select name="categoria_id" class="form-control" required id="categoriaSelect">
                        <?php foreach ($categorias as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $ordem['categoria_id'] == $c['id'] ? 'selected' : '' ?> data-sla="<?= $c['sla_horas'] ?>">
                            <?= htmlspecialchars($c['nome']) ?>
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
                                   <?= $ordem['prioridade_id'] == $p['id'] ? 'checked' : '' ?>
                                   data-mult="<?= $p['sla_multiplicador'] ?>">
                            <span class="priority-badge" style="--prio-color: <?= htmlspecialchars($p['cor']) ?>; border-color: <?= htmlspecialchars($p['cor']) ?>; color: <?= htmlspecialchars($p['cor']) ?>">
                                <?= htmlspecialchars($p['nome']) ?>
                            </span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

            </div>
        </div>

        <?php if (Auth::isTecnico()):
            $vfServ = isset($ordem['valor_servico']) && $ordem['valor_servico'] !== null && $ordem['valor_servico'] !== ''
                ? number_format((float)$ordem['valor_servico'], 2, ',', '.') : '';
            $vfPago = isset($ordem['valor_pago']) && $ordem['valor_pago'] !== null && $ordem['valor_pago'] !== ''
                ? number_format((float)$ordem['valor_pago'], 2, ',', '.') : '';
            $dtFinLocal = !empty($ordem['data_finalizacao'])
                ? date('Y-m-d\TH:i', strtotime($ordem['data_finalizacao'])) : '';
            $formasPgEdit = Ordem::formasPagamentoOpcoes();
        ?>
        <div class="card">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-coins"></i> Financeiro</h3></div>
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label">Motivo da finalização</label>
                    <textarea name="motivo_finalizacao" class="form-control" rows="2" maxlength="500" placeholder="Obrigatório ao alterar o status para Finalizada."><?= htmlspecialchars($ordem['motivo_finalizacao'] ?? '') ?></textarea>
                    <small class="form-hint">Ao encerrar a OS como <strong>finalizada</strong>, este campo é obrigatório.</small>
                </div>
                <div class="form-group">
                    <label class="form-label">Data / hora da finalização</label>
                    <input type="datetime-local" name="data_finalizacao" class="form-control" value="<?= htmlspecialchars($dtFinLocal) ?>">
                    <small class="form-hint">Opcional ao finalizar; se vazio, será usada a data/hora do salvamento.</small>
                </div>
                <div class="form-row-2">
                    <div class="form-group">
                        <label class="form-label">Valor do serviço (R$)</label>
                        <input type="text" name="valor_servico" class="form-control" inputmode="decimal" placeholder="0,00" value="<?= htmlspecialchars($vfServ) ?>" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Valor pago (R$)</label>
                        <input type="text" name="valor_pago" class="form-control" inputmode="decimal" placeholder="0,00" value="<?= htmlspecialchars($vfPago) ?>" autocomplete="off">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Forma de pagamento</label>
                    <select name="forma_pagamento" class="form-control">
                        <?php foreach ($formasPgEdit as $k => $label): ?>
                        <option value="<?= htmlspecialchars((string)$k) ?>" <?= (($ordem['forma_pagamento'] ?? '') === (string)$k) ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Materiais / peças utilizados</label>
                    <textarea name="detalhe_financeiro" class="form-control" rows="3" maxlength="65535" placeholder="Descreva o que foi usado, custos de peças, etc."><?= htmlspecialchars($ordem['detalhe_financeiro'] ?? '') ?></textarea>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="os-meta-card">
            <div class="meta-item">
                <span class="meta-label">Operador</span>
                <span class="meta-value"><?= htmlspecialchars((string)(Auth::user()['nome'] ?? '—')) ?></span>
            </div>
            <div class="meta-item">
                <span class="meta-label">Data de abertura</span>
                <span class="meta-value"><?= date('d/m/Y H:i', strtotime($ordem['data_abertura'])) ?></span>
            </div>
        </div>

        <button type="submit" class="btn btn-primary btn-block btn-lg">
            <i class="fas fa-save"></i> Salvar Alterações
        </button>
    </div>
</div>
</form>

<?php if (Auth::isPerfilTecnico()): ?>
<?php require __DIR__ . '/_modal_cliente_rapido.php'; ?>
<?php endif; ?>

<?php require __DIR__ . '/partials/lightbox_galeria.php'; ?>

<script>
const UPLOAD_URL = '<?= UPLOAD_URL ?>';
const ORDEM_ID   = <?= $ordem['id'] ?>;
const BASE_URL   = '<?= BASE_URL ?>';
</script>
