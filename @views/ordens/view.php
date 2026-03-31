<?php
$pageTitle = $ordem['numero'];
$extraJs   = ['ordens.js', 'image-upload.js', 'estoque.js'];
$slaBarWidth = min($slaPercent, 100);
$podeGerirImagens = (Auth::isAdmin() || Auth::isTecnico() || (int)$ordem['usuario_criador_id'] === (int)Auth::id())
    && !in_array($ordem['status'], ['finalizada', 'cancelada'], true);
$slotsImagens = max(0, (int)$limiteImagensOs - $totalImagens);
?>

<div class="page-header">
    <div class="page-header-left">
        <a href="<?= BASE_URL ?>/ordens" class="back-link">
            <i class="fas fa-arrow-left"></i> Ordens
        </a>
        <h1 class="page-title"><?= htmlspecialchars($ordem['numero']) ?></h1>
        <span class="badge badge-status badge-<?= $ordem['status'] ?> badge-lg">
            <?= ucfirst(str_replace('_', ' ', $ordem['status'])) ?>
        </span>
    </div>
    <div class="page-header-actions">
        <?php if (!in_array($ordem['status'], ['finalizada','cancelada'])): ?>
        <a href="<?= BASE_URL ?>/ordens/<?= $ordem['id'] ?>/editar" class="btn btn-secondary">
            <i class="fas fa-edit"></i> Editar
        </a>
        <?php elseif (Auth::isTecnico()): ?>
        <form method="post" action="<?= BASE_URL ?>/ordens/<?= (int)$ordem['id'] ?>/reabrir" class="form-reabrir-os"
              onsubmit="return confirm('Reabrir esta OS? Os dados de encerramento e financeiros serão limpos; o status volta conforme o histórico (ou Aberta).');">
            <input type="hidden" name="confirmar_reabertura" value="1">
            <button type="submit" class="btn btn-reabrir">
                <i class="fas fa-unlock-alt"></i> Reabrir OS
            </button>
        </form>
        <?php endif; ?>
    </div>
</div>

<div class="view-grid">
    <!-- Conteúdo principal -->
    <div class="view-main">

        <!-- SLA Banner -->
        <div class="sla-banner <?= $slaCssClass ?>">
            <div class="sla-banner-left">
                <div class="sla-banner-label">SLA — <strong><?= $slaLabel ?></strong></div>
                <div class="sla-banner-time">
                    <i class="fas fa-clock"></i> Restante: <?= $tempoRestante ?>
                    &nbsp;|&nbsp;
                    <i class="fas fa-hourglass-half"></i> Aberta há: <?= $tempoAberto ?>
                </div>
            </div>
            <div class="sla-banner-right">
                <div class="sla-percent-display"><?= $slaPercent ?>%</div>
            </div>
            <div class="sla-banner-bar">
                <div class="sla-fill <?= $slaCssClass ?>" style="width:<?= $slaBarWidth ?>%"></div>
            </div>
        </div>

        <!-- Dados da OS -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Detalhes</h3>
            </div>
            <div class="card-body">
                <h2 class="os-titulo"><?= htmlspecialchars($ordem['titulo']) ?></h2>
                <div class="os-descricao"><?= nl2br(htmlspecialchars($ordem['descricao'])) ?></div>
                <?php if ($ordem['observacoes']): ?>
                <div class="os-observacoes">
                    <strong>Observações:</strong>
                    <p><?= nl2br(htmlspecialchars($ordem['observacoes'])) ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Galeria de Imagens -->
        <div class="card" id="imagens">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-images"></i> Imagens</h3>
                <span class="card-badge"><?= $totalImagens ?>/<?= (int)$limiteImagensOs ?></span>
            </div>
            <div class="card-body">
                <?php if (empty($imagens)): ?>
                <div class="empty-state compact view-imagens-empty">
                    <i class="fas fa-image"></i>
                    <p>Nenhuma imagem anexada nesta OS.</p>
                </div>
                <?php else: ?>
                <div class="image-gallery-grid">
                    <?php foreach ($imagens as $img):
                        $_path = str_replace('\\', '/', (string)$img['arquivo']);
                        $_parts = array_values(array_filter(explode('/', $_path)));
                        $_urlImg = UPLOAD_URL . '/' . implode('/', array_map('rawurlencode', $_parts));
                    ?>
                    <div class="gallery-item" id="img-<?= (int)$img['id'] ?>">
                        <a href="<?= htmlspecialchars($_urlImg) ?>"
                           class="gallery-lightbox" data-img-id="<?= (int)$img['id'] ?>">
                            <img src="<?= htmlspecialchars($_urlImg) ?>"
                                 alt="Imagem da OS" loading="lazy"
                                 onerror="this.style.opacity='.35'; this.alt='Falha ao carregar';">
                        </a>
                        <div class="gallery-overlay">
                            <span class="gallery-info"><?= (int)$img['largura'] ?>×<?= (int)$img['altura'] ?> · <?= (int)$img['tamanho_kb'] ?>KB</span>
                            <?php if ($podeGerirImagens): ?>
                            <button type="button" class="gallery-delete"
                                    data-ordem="<?= (int)$ordem['id'] ?>" data-img="<?= (int)$img['id'] ?>"
                                    title="Remover imagem">
                                <i class="fas fa-trash"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <?php if ($podeGerirImagens && $slotsImagens > 0): ?>
                <form method="post" action="<?= BASE_URL ?>/ordens/<?= (int)$ordem['id'] ?>/imagens"
                      enctype="multipart/form-data" id="formOsImagens" class="view-os-upload-form">
                    <div class="upload-area view-upload-area" id="uploadArea" data-max-files="<?= (int)$slotsImagens ?>">
                        <input type="file" name="imagens[]" id="fileInput" class="upload-file-input" multiple
                               accept="image/jpeg,image/png,image/webp,image/gif">
                        <div class="upload-placeholder" id="uploadPlaceholder">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p>Arraste ou <span class="upload-link">selecione</span> · JPG/PNG/WebP/GIF</p>
                            <small>Até <?= (int)$limiteImagensOs ?> no total · <?= $slotsImagens ?> vaga(s)</small>
                        </div>
                        <div class="image-preview-grid" id="imagePreviewGrid"></div>
                    </div>
                    <div class="upload-counter">
                        <span id="imageCount">0</span>/<?= $slotsImagens ?> · envio
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm view-os-upload-submit">
                        <i class="fas fa-upload"></i> Enviar
                    </button>
                </form>
                <?php elseif ($podeGerirImagens && $slotsImagens <= 0 && !empty($imagens)): ?>
                <p class="view-imagens-limite text-muted"><i class="fas fa-info-circle"></i> Limite de <?= (int)$limiteImagensOs ?> imagens atingido. Remova uma para adicionar outras.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Materiais / estoque -->
        <div class="card" id="estoqueOsCard" data-ordem="<?= (int)$ordem['id'] ?>">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-toolbox"></i> Materiais utilizados</h3>
            </div>
            <div class="card-body">
                <p class="text-muted compact" style="margin-bottom:.75rem">Lançamentos descontam do <strong>seu</strong> estoque. É preciso ter <strong>estoque ativo</strong> no cadastro (admin em Permissões de estoque), item no catálogo e <strong>entrada de material</strong> com saldo &gt; 0. Só aparecem aqui itens com quantidade disponível.</p>
                <div data-estoque-msg class="form-hint" style="min-height:1.25rem;margin-bottom:.5rem"></div>
                <div data-estoque-list class="estoque-os-list"><p class="text-muted compact">Carregando…</p></div>
                <form data-estoque-form class="estoque-os-form" style="margin-top:1rem;display:none" method="post" action="#">
                    <p class="text-muted compact estoque-os-hint" data-estoque-disponivel style="margin:0 0 .5rem;line-height:1.45">Escolha o material abaixo. O saldo aparece entre parênteses e aqui em detalhe.</p>
                    <div class="estoque-os-lancar-row">
                        <div class="form-group estoque-os-field estoque-os-field-material">
                            <label class="form-label">Material</label>
                            <div class="estoque-os-material-wrap" style="display:flex;gap:.35rem;align-items:center">
                                <select name="item_id" class="form-control form-control-sm estoque-os-item-select" required title="Lista compacta: código, nome e saldo"></select>
                                <button type="button" class="btn btn-ghost btn-sm" id="btnOsScan" title="Escanear código/QR">
                                    <i class="fas fa-qrcode"></i>
                                </button>
                            </div>
                        </div>
                        <div class="form-group estoque-os-field estoque-os-field-qtd">
                            <label class="form-label">Qtd. usada</label>
                            <input type="text" name="quantidade" class="form-control form-control-sm" required inputmode="decimal" placeholder="ex. 2 ou 1,9" autocomplete="off">
                        </div>
                        <div class="form-group estoque-os-field estoque-os-field-obs">
                            <label class="form-label">Obs.</label>
                            <input type="text" name="observacao" class="form-control form-control-sm" maxlength="500" placeholder="Opcional">
                        </div>
                        <div class="form-group estoque-os-field estoque-os-field-btn">
                            <label class="form-label estoque-os-label-spacer">&nbsp;</label>
                            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Lançar</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Comentários -->
        <div class="card" id="comentarios">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-comments"></i> Comentários</h3>
                <span class="card-badge"><?= count($comentarios) ?></span>
            </div>
            <div class="card-body">
                <div class="comments-list">
                    <?php foreach ($comentarios as $c): ?>
                    <div class="comment-item">
                        <div class="comment-avatar"><?= strtoupper(substr($c['usuario_nome'], 0, 1)) ?></div>
                        <div class="comment-body">
                            <div class="comment-header">
                                <span class="comment-author"><?= htmlspecialchars($c['usuario_nome']) ?></span>
                                <span class="comment-time"><?= date('d/m/Y H:i', strtotime($c['created_at'])) ?></span>
                            </div>
                            <div class="comment-text"><?= nl2br(htmlspecialchars($c['comentario'])) ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($comentarios)): ?>
                    <div class="empty-state compact">
                        <i class="fas fa-comment-slash"></i>
                        <p>Nenhum comentário ainda.</p>
                    </div>
                    <?php endif; ?>
                </div>

                <form method="POST" action="<?= BASE_URL ?>/ordens/<?= $ordem['id'] ?>/comentar" class="comment-form">
                    <div class="comment-input-row">
                        <div class="comment-avatar">
                            <?= strtoupper(substr(Auth::user()['nome'], 0, 1)) ?>
                        </div>
                        <textarea name="comentario" class="form-control" rows="2"
                                  placeholder="Adicionar um comentário..."></textarea>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Histórico -->
        <div class="card" id="historico">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-history"></i> Histórico</h3>
            </div>
            <div class="card-body">
                <div class="timeline">
                    <?php foreach ($historico as $h): ?>
                    <div class="timeline-item">
                        <div class="timeline-dot"></div>
                        <div class="timeline-content">
                            <div class="timeline-header">
                                <span class="timeline-acao"><?= OrdemHistorico::iconeAcao($h['acao']) ?> <?= htmlspecialchars($h['descricao']) ?></span>
                                <span class="timeline-time"><?= date('d/m/Y H:i', strtotime($h['created_at'])) ?></span>
                            </div>
                            <div class="timeline-user"><?= htmlspecialchars($h['usuario_nome']) ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar de informações -->
    <div class="view-sidebar">
        <div class="card info-card">
            <div class="card-header"><h3 class="card-title">Informações</h3></div>
            <div class="card-body">
                <div class="info-row">
                    <span class="info-label">Número</span>
                    <span class="info-value os-num"><?= htmlspecialchars($ordem['numero']) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Status</span>
                    <span class="badge badge-status badge-<?= $ordem['status'] ?>">
                        <?= ucfirst(str_replace('_', ' ', $ordem['status'])) ?>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Prioridade</span>
                    <span class="badge-prioridade" style="background:<?= htmlspecialchars($ordem['prioridade_cor']) ?>20;color:<?= htmlspecialchars($ordem['prioridade_cor']) ?>">
                        <?= htmlspecialchars($ordem['prioridade_nome']) ?>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Categoria</span>
                    <span class="info-value"><?= htmlspecialchars($ordem['categoria_nome']) ?></span>
                </div>
                <?php if (!empty($ordem['cliente_nome'])): ?>
                <div class="info-row info-row-cliente">
                    <span class="info-label">Cliente</span>
                    <span class="info-value">
                        <strong><?= htmlspecialchars($ordem['cliente_nome']) ?></strong>
                        <?php if (!empty($ordem['cliente_fantasia'])): ?>
                        <br><small class="text-muted"><?= htmlspecialchars($ordem['cliente_fantasia']) ?></small>
                        <?php endif; ?>
                        <?php if (!empty($ordem['cliente_documento'])): ?>
                        <br><small><?= htmlspecialchars($ordem['cliente_documento']) ?></small>
                        <?php endif; ?>
                        <?php if (!empty($ordem['cliente_email']) || !empty($ordem['cliente_telefone']) || !empty($ordem['cliente_celular'])): ?>
                        <br><small class="text-muted">
                            <?= htmlspecialchars($ordem['cliente_email'] ?? '') ?>
                            <?php if (!empty($ordem['cliente_telefone'])): ?> · <?= htmlspecialchars($ordem['cliente_telefone']) ?><?php endif; ?>
                            <?php if (!empty($ordem['cliente_celular'])): ?> · <?= htmlspecialchars($ordem['cliente_celular']) ?><?php endif; ?>
                        </small>
                        <?php endif; ?>
                        <?php if (!empty($ordem['cliente_cidade']) || !empty($ordem['cliente_uf'])): ?>
                        <br><small><?= htmlspecialchars(trim(($ordem['cliente_cidade'] ?? '') . ' / ' . ($ordem['cliente_uf'] ?? ''), ' /')) ?></small>
                        <?php endif; ?>
                    </span>
                </div>
                <?php endif; ?>
                <div class="info-row">
                    <span class="info-label">Operador</span>
                    <span class="info-value"><?= htmlspecialchars((string)(Auth::user()['nome'] ?? '—')) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Responsável</span>
                    <span class="info-value"><?= htmlspecialchars($ordem['responsavel_nome'] ?? '—') ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Origem</span>
                    <span class="info-value origin-badge origin-<?= $ordem['origem'] ?>">
                        <i class="fab fa-<?= $ordem['origem'] === 'telegram' ? 'telegram' : 'globe' ?>"></i>
                        <?= ucfirst($ordem['origem']) ?>
                    </span>
                </div>
                <hr class="info-divider">
                <div class="info-row">
                    <span class="info-label">Abertura</span>
                    <span class="info-value"><?= date('d/m/Y H:i', strtotime($ordem['data_abertura'])) ?></span>
                </div>
                <?php if ($ordem['data_inicio']): ?>
                <div class="info-row">
                    <span class="info-label">Início</span>
                    <span class="info-value"><?= date('d/m/Y H:i', strtotime($ordem['data_inicio'])) ?></span>
                </div>
                <?php endif; ?>
                <?php if ($ordem['data_finalizacao']): ?>
                <div class="info-row">
                    <span class="info-label">Finalização</span>
                    <span class="info-value"><?= date('d/m/Y H:i', strtotime($ordem['data_finalizacao'])) ?></span>
                </div>
                <?php endif; ?>
                <?php if (!empty(trim((string)($ordem['motivo_finalizacao'] ?? '')))): ?>
                <div class="info-row info-row-block">
                    <span class="info-label">Motivo do encerramento</span>
                    <span class="info-value info-text-block"><?= nl2br(htmlspecialchars($ordem['motivo_finalizacao'])) ?></span>
                </div>
                <?php endif; ?>
                <hr class="info-divider">
                <div class="info-row">
                    <span class="info-label">Valor do serviço</span>
                    <span class="info-value"><?= Ordem::formatarMoeda(isset($ordem['valor_servico']) && $ordem['valor_servico'] !== '' ? (float)$ordem['valor_servico'] : null) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Valor pago</span>
                    <span class="info-value"><?= Ordem::formatarMoeda(isset($ordem['valor_pago']) && $ordem['valor_pago'] !== '' ? (float)$ordem['valor_pago'] : null) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Forma de pagamento</span>
                    <span class="info-value"><?= htmlspecialchars(Ordem::labelFormaPagamento($ordem['forma_pagamento'] ?? null)) ?></span>
                </div>
                <?php if (!empty(trim((string)($ordem['detalhe_financeiro'] ?? '')))): ?>
                <div class="info-row info-row-block">
                    <span class="info-label">Materiais / uso</span>
                    <span class="info-value info-text-block"><?= nl2br(htmlspecialchars($ordem['detalhe_financeiro'])) ?></span>
                </div>
                <?php endif; ?>
                <hr class="info-divider">
                <div class="info-row">
                    <span class="info-label">Prazo SLA</span>
                    <span class="info-value <?= $slaCssClass ?>"><?= $ordem['sla_prazo'] ? date('d/m/Y H:i', strtotime($ordem['sla_prazo'])) : '—' ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal-backdrop" id="osScanModal" aria-hidden="true">
    <div class="modal-dialog" role="dialog" aria-modal="true" aria-labelledby="osScanTitle" style="max-width:640px">
        <div class="modal-header">
            <h3 class="modal-title" id="osScanTitle"><i class="fas fa-barcode"></i> Scanner de item</h3>
            <button type="button" class="modal-close" data-os-scan-close>&times;</button>
        </div>
        <div class="modal-body" style="padding:0 18px 12px">
            <p class="modal-lead">Use pistola (bip no campo) ou câmera para ler código de barras/QR.</p>
            <div class="form-group">
                <label class="form-label">Leitura da pistola</label>
                <input type="text" id="osScanInput" class="form-control" placeholder="Clique aqui e bip o código">
            </div>
            <div class="form-group" style="margin-top:.6rem">
                <button type="button" class="btn btn-ghost btn-sm" id="btnOsCamStart"><i class="fas fa-camera"></i> Câmera</button>
                <button type="button" class="btn btn-ghost btn-sm" id="btnOsCamStop" style="display:none"><i class="fas fa-stop"></i> Parar</button>
            </div>
            <video id="osScanVideo" style="display:none;width:100%;border-radius:8px;border:1px solid var(--border)" autoplay muted playsinline></video>
            <p class="form-hint" id="osScanHint" style="margin-top:.5rem;min-height:1.25rem"></p>
            <hr style="border-color:var(--border);opacity:.45;margin:12px 0">
            <h4 class="card-title" style="font-size:1rem;margin:0 0 .5rem">Item encontrado</h4>
            <dl style="display:grid;grid-template-columns:130px 1fr;gap:.4rem .8rem;margin:0 0 .75rem">
                <dt>Código</dt><dd id="osItemCodigo">—</dd>
                <dt>Nome</dt><dd id="osItemNome">—</dd>
                <dt>Unidade</dt><dd id="osItemUn">—</dd>
                <dt>Saldo atual</dt><dd id="osItemSaldo">—</dd>
            </dl>
            <div class="form-group">
                <label class="form-label required" for="osItemQtdModal">Quantidade a lançar</label>
                <input id="osItemQtdModal" type="text" class="form-control" inputmode="decimal" placeholder="Ex.: 1 ou 1,5">
            </div>
            <input type="hidden" id="osItemIdModal" value="">
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-ghost" data-os-scan-close>Fechar</button>
            <button type="button" class="btn btn-primary" id="btnOsScanConfirm"><i class="fas fa-check"></i> Confirmar lançamento</button>
        </div>
    </div>
</div>

<?php require __DIR__ . '/partials/lightbox_galeria.php'; ?>

<script>
const BASE_URL  = '<?= BASE_URL ?>';
const ORDEM_ID  = <?= (int)$ordem['id'] ?>;
window.ORDEM_ID = ORDEM_ID;
const UPLOAD_URL = '<?= UPLOAD_URL ?>';
</script>
