<?php
class OrdemController extends Controller
{
    private Ordem $ordemModel;
    private OrdemImagem $imagemModel;
    private OrdemHistorico $historicoModel;
    private CategoriaOS $categoriaModel;
    private Prioridade $prioridadeModel;
    private User $userModel;
    private Cliente $clienteModel;

    public function __construct()
    {
        parent::__construct();
        $this->ordemModel    = new Ordem();
        $this->imagemModel   = new OrdemImagem();
        $this->historicoModel = new OrdemHistorico();
        $this->categoriaModel = new CategoriaOS();
        $this->prioridadeModel = new Prioridade();
        $this->userModel     = new User();
        $this->clienteModel  = new Cliente();
    }

    public function index(): void
    {
        Auth::requireLogin();
        $user    = Auth::user();
        $filtros = [];

        if (!Auth::isAdmin()) {
            $filtros['usuario_criador_id'] = Auth::id();
        }

        if (!empty($_GET['status']))       $filtros['status']       = $_GET['status'];
        if (!empty($_GET['prioridade_id'])) $filtros['prioridade_id'] = (int)$_GET['prioridade_id'];
        if (!empty($_GET['categoria_id']))  $filtros['categoria_id']  = (int)$_GET['categoria_id'];
        if (!empty($_GET['busca']))         $filtros['busca']          = $_GET['busca'];
        if (!empty($_GET['cliente_id']))    $filtros['cliente_id']     = (int)$_GET['cliente_id'];

        $page   = max(1, (int)($this->get('pagina', 1)));
        $limit  = 15;
        $offset = ($page - 1) * $limit;

        $ordens    = $this->ordemModel->listarComFiltros($filtros, $limit, $offset);
        $categorias = $this->categoriaModel->allAtivas();
        $prioridades = $this->prioridadeModel->listarParaFiltroOrdens();
        $clientesFiltro = $this->clienteModel->listarParaSelect(Auth::isTecnico() ? null : Auth::id());
        $flash       = $this->getFlash();
        $extraJs     = Auth::isTecnico() ? ['ordens-list.js'] : [];

        $this->render('ordens/index', compact('user', 'ordens', 'filtros', 'categorias', 'prioridades', 'clientesFiltro', 'flash', 'page', 'extraJs'));
    }

    public function create(): void
    {
        Auth::requireLogin();
        $categorias  = $this->categoriaModel->allAtivas();
        $prioridades = $this->prioridadeModel->listarParaCriarOrdem();
        $clientes    = $this->clienteModel->listarParaSelect(Auth::isTecnico() ? null : Auth::id());
        $flash       = $this->getFlash();
        $limiteImagensOs = max_imagens_por_os_usuario((int)Auth::id());
        $titulosRecentes  = $this->ordemModel->titulosRecentesDoUsuario(Auth::id(), 10);
        $titulosPopulares = $this->ordemModel->titulosMaisUsados(12);
        $titulosPadrao    = Ordem::titulosSugestaoPadrao();

        $this->render('ordens/create', compact(
            'categorias', 'prioridades', 'clientes', 'flash',
            'titulosRecentes', 'titulosPopulares', 'titulosPadrao', 'limiteImagensOs'
        ));
    }

    public function store(): void
    {
        Auth::requireLogin();

        $titulo      = trim($this->post('titulo', ''));
        $descricao   = trim($this->post('descricao', ''));
        $categoriaId = (int)$this->post('categoria_id', 0);
        $permitidas  = $this->prioridadeModel->listarParaCriarOrdem();
        $idsPerm     = array_column($permitidas, 'id');
        $padraoId    = $this->prioridadeModel->idPadraoCriacao();
        $prioridadeId = (int)$this->post('prioridade_id', $padraoId ?? 0);
        if ($idsPerm && !in_array($prioridadeId, $idsPerm, true)) {
            $prioridadeId = (int)($padraoId ?? $idsPerm[0]);
        }
        $responsavelId = Auth::isTecnico() ? (int)Auth::id() : null;
        $observacoes = trim($this->post('observacoes', ''));
        $clienteId   = $this->validarClienteId(
            (int)$this->post('cliente_id', 0),
            Auth::isTecnico() ? null : Auth::id()
        );

        if (!Auth::isAdmin() && !LimiteConta::podeCriarOsNoMes((int)Auth::id())) {
            $this->setFlash('error', LimiteConta::mensagemLimiteOsMes((int)Auth::id()));
            $this->redirect('/ordens/criar');
        }

        if (!$titulo || !$descricao || !$categoriaId) {
            $this->setFlash('error', 'Título, descrição e categoria são obrigatórios.');
            $this->redirect('/ordens/criar');
        }
        if (!$clienteId) {
            $this->setFlash('error', 'Selecione um cliente para a OS.');
            $this->redirect('/ordens/criar');
        }

        $categoria   = $this->categoriaModel->find($categoriaId);
        $prioridade  = $this->prioridadeModel->find($prioridadeId);
        if (!$prioridade) {
            $this->setFlash('error', 'Prioridade inválida.');
            $this->redirect('/ordens/criar');
        }
        $slaHoras    = $categoria['sla_horas'] ?? 24;
        $multiplicador = $prioridade['sla_multiplicador'] ?? 1.0;
        $numero       = $this->ordemModel->gerarNumero();
        $slaPrazo     = SLAHelper::calcPrazo(date('Y-m-d H:i:s'), $slaHoras, $multiplicador);

        $data = [
            'numero'                 => $numero,
            'titulo'                 => $titulo,
            'descricao'              => $descricao,
            'status'                 => 'aberta',
            'prioridade_id'          => $prioridadeId,
            'categoria_id'           => $categoriaId,
            'usuario_criador_id'     => Auth::id(),
            'usuario_responsavel_id' => $responsavelId,
            'cliente_id'             => $clienteId,
            'sla_prazo'              => $slaPrazo,
            'sla_horas_previstas'    => $slaHoras * $multiplicador,
            'observacoes'            => $observacoes ?: null,
            'origem'                 => 'web',
        ];

        $ordemId = (int)$this->ordemModel->create($data);

        // Upload de imagens (não bloqueia criação da OS em caso de erro)
        try {
            $this->processarImagens($ordemId);
        } catch (Throwable $e) {
            error_log("Falha no upload de imagens durante criação da OS {$ordemId}: " . $e->getMessage());
        }

        $this->historicoModel->registrar($ordemId, Auth::id(), 'criacao', "OS {$numero} criada.");
        $this->notificarNova($ordemId, $numero, $titulo);

        $this->setFlash('success', "Ordem {$numero} criada com sucesso!");
        $this->redirect('/ordens/' . $ordemId);
    }

    public function view(string $id): void
    {
        Auth::requireLogin();
        $ordem = $this->ordemModel->findComDetalhes((int)$id);
        if (!$ordem) $this->abort(404, 'Ordem não encontrada.');

        if (!Auth::isAdmin() && $ordem['usuario_criador_id'] != Auth::id()
            && $ordem['usuario_responsavel_id'] != Auth::id()) {
            $this->abort(403, 'Sem permissão.');
        }

        $imagens    = $this->imagemModel->porOrdem((int)$id);
        $historico  = $this->historicoModel->porOrdem((int)$id);
        $comentarios = $this->db->fetchAll(
            "SELECT c.*, u.nome AS usuario_nome FROM ordens_comentarios c
             LEFT JOIN usuarios u ON u.id = c.usuario_id
             WHERE c.ordem_id = ? ORDER BY c.created_at ASC",
            [(int)$id]
        );
        $slaPercent  = SLAHelper::percentual($ordem['data_abertura'], $ordem['sla_prazo']);
        $slaCssClass = SLAHelper::cssClass($slaPercent, $ordem['status']);
        $slaLabel    = SLAHelper::label($slaPercent, $ordem['status']);
        $tempoRestante = SLAHelper::tempoRestante($ordem['sla_prazo'], $ordem['status']);
        $tempoAberto   = SLAHelper::tempoAberto($ordem['data_abertura']);
        $flash           = $this->getFlash();
        $totalImagens    = count($imagens);
        $limiteImagensOs = max_imagens_por_os_usuario((int)$ordem['usuario_criador_id']);

        $this->render('ordens/view', compact(
            'ordem', 'imagens', 'historico', 'comentarios',
            'slaPercent', 'slaCssClass', 'slaLabel',
            'tempoRestante', 'tempoAberto', 'flash', 'totalImagens', 'limiteImagensOs'
        ));
    }

    public function edit(string $id): void
    {
        Auth::requireLogin();
        $ordem = $this->ordemModel->findComDetalhes((int)$id);
        if (!$ordem) $this->abort(404);

        if (!Auth::isTecnico() && $ordem['usuario_criador_id'] != Auth::id()) {
            $this->abort(403);
        }

        if (in_array($ordem['status'], ['finalizada', 'cancelada'], true)) {
            $this->setFlash('error', 'Ordem finalizada ou cancelada não pode ser editada.');
            $this->redirect('/ordens/' . $id);
        }

        $categorias   = $this->categoriaModel->allAtivas();
        $prioridades  = $this->prioridadeModel->listarParaEdicaoOrdem(
            isset($ordem['prioridade_id']) ? (int)$ordem['prioridade_id'] : null
        );
        $clientes     = $this->clienteModel->listarParaSelect(Auth::isTecnico() ? null : Auth::id());
        if (!empty($ordem['cliente_id'])) {
            $cid = (int)$ordem['cliente_id'];
            $ids = array_column($clientes, 'id');
            if (!in_array($cid, $ids, true)) {
                $extra = $this->clienteModel->find($cid);
                if ($extra) {
                    array_unshift($clientes, [
                        'id'                  => $extra['id'],
                        'nome_razao_social'   => $extra['nome_razao_social'],
                        'nome_fantasia'       => $extra['nome_fantasia'],
                        'documento'           => $extra['documento'],
                    ]);
                }
            }
        }
        $imagens      = $this->imagemModel->porOrdem((int)$id);
        $totalImagens = count($imagens);
        $flash        = $this->getFlash();
        $limiteImagensOs = max_imagens_por_os_usuario((int)$ordem['usuario_criador_id']);
        $titulosRecentes  = $this->ordemModel->titulosRecentesDoUsuario(Auth::id(), 10);
        $titulosPopulares = $this->ordemModel->titulosMaisUsados(12);
        $titulosPadrao    = Ordem::titulosSugestaoPadrao();

        $this->render('ordens/edit', compact(
            'ordem', 'categorias', 'prioridades', 'clientes', 'imagens', 'totalImagens', 'flash',
            'titulosRecentes', 'titulosPopulares', 'titulosPadrao', 'limiteImagensOs'
        ));
    }

    public function update(string $id): void
    {
        Auth::requireLogin();
        $ordem = $this->ordemModel->findComDetalhes((int)$id);
        if (!$ordem) $this->abort(404);

        if (!Auth::isTecnico() && $ordem['usuario_criador_id'] != Auth::id()) {
            $this->abort(403);
        }

        $statusAntigo   = $ordem['status'];
        $novoStatus     = $this->post('status', $statusAntigo);
        $titulo         = trim($this->post('titulo', $ordem['titulo']));
        $descricao      = trim($this->post('descricao', $ordem['descricao']));
        $categoriaId    = (int)$this->post('categoria_id', $ordem['categoria_id']);
        $permitidasPrio = $this->prioridadeModel->listarParaEdicaoOrdem(
            isset($ordem['prioridade_id']) ? (int)$ordem['prioridade_id'] : null
        );
        $idsPrio        = array_column($permitidasPrio, 'id');
        $prioridadeId   = (int)$this->post('prioridade_id', $ordem['prioridade_id']);
        if ($idsPrio && !in_array($prioridadeId, $idsPrio, true)) {
            $prioridadeId = (int)$ordem['prioridade_id'];
        }
        $responsavelId  = Auth::isTecnico()
            ? (int)Auth::id()
            : ((int)($ordem['usuario_responsavel_id'] ?? 0) ?: null);
        $observacoes    = trim($this->post('observacoes', ''));
        $clienteId      = $this->validarClienteId(
            (int)$this->post('cliente_id', 0),
            Auth::isTecnico() ? null : Auth::id()
        );
        if (!$clienteId) {
            $this->setFlash('error', 'Cliente é obrigatório. Selecione um cliente cadastrado.');
            if ($this->post('retorno_sla', '') === '1') {
                $this->redirect('/sla');
            }
            $this->redirect('/ordens/' . $id . '/editar');
        }

        $updateData = [
            'titulo'                 => $titulo,
            'descricao'              => $descricao,
            'status'                 => $novoStatus,
            'prioridade_id'          => $prioridadeId,
            'categoria_id'           => $categoriaId,
            'usuario_responsavel_id' => $responsavelId,
            'cliente_id'             => $clienteId,
            'observacoes'            => $observacoes ?: null,
        ];

        if (Auth::isTecnico()) {
            $motivoFin = trim($this->post('motivo_finalizacao', ''));
            if ($novoStatus === 'finalizada' && $statusAntigo !== 'finalizada' && $motivoFin === '') {
                $this->setFlash('error', 'Informe o motivo da finalização.');
                if ($this->post('retorno_sla', '') === '1') {
                    $this->redirect('/sla');
                }
                $this->redirect('/ordens/' . $id . '/editar');
            }
            $updateData['motivo_finalizacao'] = $motivoFin !== '' ? $motivoFin : null;
            $updateData['valor_servico']      = $this->parseDecimalBr($this->post('valor_servico', ''));
            $updateData['valor_pago']         = $this->parseDecimalBr($this->post('valor_pago', ''));
            $forma                            = trim($this->post('forma_pagamento', ''));
            $updateData['forma_pagamento']    = $forma !== '' ? $forma : null;
            $detFin                           = trim($this->post('detalhe_financeiro', ''));
            $updateData['detalhe_financeiro'] = $detFin !== '' ? $detFin : null;
        }

        if ($novoStatus === 'em_andamento' && !$ordem['data_inicio']) {
            $updateData['data_inicio'] = date('Y-m-d H:i:s');
        }
        if ($novoStatus === 'finalizada') {
            $dfManual = Auth::isTecnico() ? trim($this->post('data_finalizacao', '')) : '';
            if ($dfManual !== '') {
                $ts = strtotime(str_replace('T', ' ', $dfManual));
                if ($ts) {
                    $updateData['data_finalizacao'] = date('Y-m-d H:i:s', $ts);
                }
            } elseif (!$ordem['data_finalizacao']) {
                $updateData['data_finalizacao'] = date('Y-m-d H:i:s');
            }
        }

        $this->ordemModel->update((int)$id, $updateData);
        try {
            $this->processarImagens((int)$id);
        } catch (Throwable $e) {
            error_log("Falha no upload de imagens durante edição da OS {$id}: " . $e->getMessage());
        }

        if ($novoStatus !== $statusAntigo) {
            $this->historicoModel->registrar((int)$id, Auth::id(), 'status',
                "Status alterado de '{$statusAntigo}' para '{$novoStatus}'.",
                ['de' => $statusAntigo, 'para' => $novoStatus]
            );
            $this->notificarAlteracaoStatus((int)$id, $ordem['numero'], $novoStatus);
        } else {
            $this->historicoModel->registrar((int)$id, Auth::id(), 'alteracao', 'Dados da OS atualizados.');
        }

        $this->setFlash('success', 'Ordem atualizada com sucesso!');
        if ($this->post('retorno_sla', '') === '1') {
            $this->redirect('/sla');
        }
        $this->redirect('/ordens/' . $id);
    }

    public function comentar(string $id): void
    {
        Auth::requireLogin();
        $comentario = trim($this->post('comentario', ''));
        if (!$comentario) {
            if ($this->isAjaxRequest()) {
                $this->json(['ok' => false, 'error' => 'Digite um comentário.'], 422);
            }
            if ($this->post('retorno_sla', '') === '1') {
                $this->redirect('/sla');
            }
            $this->redirect('/ordens/' . $id);
        }

        $this->db->execute(
            "INSERT INTO ordens_comentarios (ordem_id, usuario_id, comentario) VALUES (?, ?, ?)",
            [(int)$id, Auth::id(), $comentario]
        );
        $this->historicoModel->registrar((int)$id, Auth::id(), 'comentario', 'Comentário adicionado.');
        if ($this->isAjaxRequest()) {
            $this->json(['ok' => true]);
        }
        if ($this->post('retorno_sla', '') === '1') {
            $this->setFlash('success', 'Comentário adicionado.');
            $this->redirect('/sla');
        }
        $this->redirect('/ordens/' . $id . '#comentarios');
    }

    /**
     * GET /ordens/{id}/imagens — não é página; o upload é só via POST.
     * Redireciona para a OS na secção de imagens (evita 404 ao abrir o URL no browser).
     */
    public function imagensViaGet(string $id): void
    {
        Auth::requireLogin();
        $this->redirect('/ordens/' . (int)$id . '#imagens');
    }

    public function uploadImagens(string $id): void
    {
        Auth::requireLogin();
        $oid = (int)$id;
        $ordem = $this->ordemModel->findComDetalhes($oid);
        if (!$ordem) {
            $this->abort(404, 'Ordem não encontrada.');
        }
        if (!Auth::isAdmin() && !Auth::isTecnico() && (int)$ordem['usuario_criador_id'] !== (int)Auth::id()) {
            $this->abort(403, 'Sem permissão.');
        }
        if (in_array($ordem['status'], ['finalizada', 'cancelada'], true)) {
            $msg = 'OS encerrada — não é possível adicionar imagens.';
            $this->setFlash('error', $msg);
            if ($this->isAjaxRequest()) {
                $this->json(['ok' => false, 'error' => $msg]);
            }
            $this->redirect('/ordens/' . $oid);
        }
        $antes = $this->imagemModel->contarPorOrdem($oid);
        $proc  = $this->processarImagens($oid);
        $depois = $this->imagemModel->contarPorOrdem($oid);
        $okUpload = $depois > $antes;
        if ($okUpload) {
            $this->setFlash('success', 'Imagens enviadas com sucesso.');
        } else {
            $erroDet = $proc['userMessage'] ?? 'Nenhuma imagem foi enviada. Selecione arquivos JPG, PNG, WebP ou GIF.';
            $this->setFlash('error', $erroDet);
        }
        if ($this->isAjaxRequest()) {
            $payload = [
                'ok'    => $okUpload,
                'error' => $okUpload ? null : ($proc['userMessage'] ?? 'Não foi possível gravar imagens.'),
            ];
            if ($okUpload) {
                $payload['redirect'] = BASE_URL . '/ordens/' . $oid . '#imagens';
            }
            $this->json($payload);
        }
        $this->redirect('/ordens/' . $oid . '#imagens');
    }

    public function deletarImagem(string $ordemId, string $imagemId): void
    {
        Auth::requireLogin();
        $oid = (int)$ordemId;
        $iid = (int)$imagemId;
        $ordem = $this->ordemModel->findComDetalhes($oid);
        if (!$ordem) {
            $this->json(['success' => false, 'error' => 'Ordem não encontrada.'], 404);
        }
        if (!Auth::isAdmin() && !Auth::isTecnico() && (int)$ordem['usuario_criador_id'] !== (int)Auth::id()) {
            $this->json(['success' => false, 'error' => 'Sem permissão.'], 403);
        }
        $img = $this->imagemModel->find($iid);
        if (!$img || (int)$img['ordem_id'] !== $oid) {
            $this->json(['success' => false, 'error' => 'Imagem inválida.'], 400);
        }
        $this->imagemModel->deletarArquivo($iid);
        $this->historicoModel->registrar($oid, Auth::id(), 'imagem', 'Imagem removida.');
        $this->json(['success' => true]);
    }

    public function finalizarRapido(string $id): void
    {
        Auth::requireTecnico();
        $oid = (int)$id;
        $ordem = $this->ordemModel->findComDetalhes($oid);
        if (!$ordem) {
            $this->setFlash('error', 'Ordem não encontrada.');
            $this->redirect('/ordens');
        }
        if (in_array($ordem['status'], ['finalizada', 'cancelada'], true)) {
            $this->setFlash('error', 'Esta OS já está encerrada.');
            $this->redirect('/ordens');
        }

        $motivo = trim($this->post('motivo_finalizacao', ''));
        if ($motivo === '') {
            $this->setFlash('error', 'Informe o motivo da finalização.');
            $this->redirect('/ordens');
        }
        $confirmar = $this->post('confirmar_encerramento', '');
        if ($confirmar !== '1') {
            $this->setFlash('error', 'Confirme o encerramento da OS.');
            $this->redirect('/ordens');
        }

        $dataFimPost = trim($this->post('data_finalizacao', ''));
        $dataFim     = $dataFimPost !== '' ? date('Y-m-d H:i:s', strtotime($dataFimPost)) : date('Y-m-d H:i:s');

        $upd = [
            'status'               => 'finalizada',
            'data_finalizacao'     => $dataFim,
            'motivo_finalizacao'   => $motivo,
            'valor_servico'        => $this->parseDecimalBr($this->post('valor_servico', '')),
            'valor_pago'           => $this->parseDecimalBr($this->post('valor_pago', '')),
            'forma_pagamento'      => null,
            'detalhe_financeiro'   => null,
        ];
        $forma = trim($this->post('forma_pagamento', ''));
        if ($forma !== '') {
            $upd['forma_pagamento'] = $forma;
        }
        $det = trim($this->post('detalhe_financeiro', ''));
        if ($det !== '') {
            $upd['detalhe_financeiro'] = $det;
        }
        if (empty($ordem['data_inicio'])) {
            $upd['data_inicio'] = $dataFim;
        }

        $this->ordemModel->update($oid, $upd);
        $this->historicoModel->registrar(
            $oid,
            Auth::id(),
            'finalizacao',
            "OS {$ordem['numero']} finalizada pela lista (motivo registrado).",
            ['de' => $ordem['status'], 'para' => 'finalizada']
        );
        $this->notificarAlteracaoStatus($oid, $ordem['numero'], 'finalizada');
        $this->setFlash('success', "OS {$ordem['numero']} finalizada.");
        $this->redirect('/ordens');
    }

    public function reabrir(string $id): void
    {
        Auth::requireTecnico();
        $oid   = (int)$id;
        $ordem = $this->ordemModel->findComDetalhes($oid);
        if (!$ordem) {
            $this->setFlash('error', 'Ordem não encontrada.');
            $this->redirect('/ordens');
        }
        if (!in_array($ordem['status'], ['finalizada', 'cancelada'], true)) {
            $this->setFlash('error', 'Somente OS finalizada ou cancelada pode ser reaberta.');
            $this->redirect('/ordens/' . $oid);
        }
        if ($this->post('confirmar_reabertura', '') !== '1') {
            $this->setFlash('error', 'Confirme a reabertura da OS.');
            $this->redirect('/ordens/' . $oid);
        }

        $statusRestaurado = $this->inferirStatusAnteriorEncerramento($oid, $ordem['status']);

        $upd = [
            'status'               => $statusRestaurado,
            'data_finalizacao'       => null,
            'motivo_finalizacao'     => null,
            'valor_servico'          => null,
            'valor_pago'             => null,
            'forma_pagamento'        => null,
            'detalhe_financeiro'     => null,
        ];
        if ($statusRestaurado === 'aberta') {
            $upd['data_inicio'] = null;
        }

        $this->ordemModel->update($oid, $upd);
        $this->ordemModel->recalcularSlaApartirDeAgora($oid);

        $this->historicoModel->registrar(
            $oid,
            Auth::id(),
            'reabertura',
            "OS {$ordem['numero']} reaberta. Status restaurado para: {$statusRestaurado}.",
            ['de' => $ordem['status'], 'para' => $statusRestaurado]
        );
        $this->notificarAlteracaoStatus($oid, $ordem['numero'], $statusRestaurado);

        $this->setFlash('success', "OS {$ordem['numero']} reaberta como «{$statusRestaurado}».");
        $this->redirect('/ordens/' . $oid);
    }

    /**
     * Recupera o status antes do encerramento (histórico) ou "aberta".
     */
    private function inferirStatusAnteriorEncerramento(int $ordemId, string $statusAtual): string
    {
        $validos = ['aberta', 'em_andamento', 'aguardando'];
        $rows    = $this->historicoModel->porOrdem($ordemId);

        foreach ($rows as $h) {
            if ($h['acao'] === 'status') {
                $d = json_decode($h['dados_json'] ?? '{}', true);
                if (!is_array($d) || empty($d['para']) || $d['para'] !== $statusAtual) {
                    continue;
                }
                if (!empty($d['de']) && in_array($d['de'], $validos, true)) {
                    return $d['de'];
                }
            }
        }
        foreach ($rows as $h) {
            if ($h['acao'] === 'finalizacao') {
                $d = json_decode($h['dados_json'] ?? '{}', true);
                if (is_array($d) && !empty($d['de']) && in_array($d['de'], $validos, true)) {
                    return $d['de'];
                }
            }
        }

        return 'aberta';
    }

    private function parseDecimalBr(string $raw): ?float
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }
        $s = str_replace([' ', 'R$', 'r$'], '', $raw);
        if (str_contains($s, ',') && str_contains($s, '.')) {
            $s = str_replace('.', '', $s);
            $s = str_replace(',', '.', $s);
        } elseif (str_contains($s, ',')) {
            $s = str_replace(',', '.', $s);
        }
        if (!is_numeric($s)) {
            return null;
        }
        return round((float)$s, 2);
    }

    /**
     * @param int|null $usuarioDono se não-null, o cliente deve pertencer a esse usuário (cadastro).
     */
    private function validarClienteId(int $id, ?int $usuarioDono = null): ?int
    {
        if ($id <= 0) {
            return null;
        }
        $c = $this->clienteModel->find($id);
        if (!$c || empty($c['ativo'])) {
            return null;
        }
        if ($usuarioDono !== null && (int)($c['usuario_cadastro_id'] ?? 0) !== (int)$usuarioDono) {
            return null;
        }
        return $id;
    }

    /**
     * @return array{userMessage:?string}
     */
    private function processarImagens(int $ordemId): array
    {
        $out = ['userMessage' => null];
        if (!function_exists('imagecreatefromjpeg')) {
            $out['userMessage'] = 'PHP sem extensão GD. Ative extension=gd no php.ini e reinicie o Apache.';
            return $out;
        }

        $files = $this->normalizarArquivosImagensPost();
        $base64Files = $this->normalizarImagensBase64Post();
        if ($files === null && $base64Files === []) {
            $out['userMessage'] = $this->mensagemQuandoNenhumFicheiroRecebido();
            return $out;
        }
        if ($files === null) {
            $files = [
                'name' => [], 'type' => [], 'tmp_name' => [], 'error' => [], 'size' => []
            ];
        }
        if ($base64Files !== []) {
            foreach ($base64Files as $bf) {
                $files['name'][] = $bf['name'];
                $files['type'][] = $bf['type'];
                $files['tmp_name'][] = $bf['tmp_name'];
                $files['error'][] = $bf['error'];
                $files['size'][] = $bf['size'];
            }
        }

        $totalAtual = $this->imagemModel->contarPorOrdem($ordemId);
        $ordemRow   = $this->ordemModel->find($ordemId);
        $criadorId  = (int)($ordemRow['usuario_criador_id'] ?? Auth::id());
        $limiteOs   = max_imagens_por_os_usuario($criadorId);
        $maxNovos   = $limiteOs - $totalAtual;
        if ($maxNovos <= 0) {
            $out['userMessage'] = 'Limite de ' . $limiteOs . ' imagens por OS atingido.';
            return $out;
        }

        $year = date('Y');
        $month = date('m');
        $userId = (int)Auth::id();
        $relativeDir = $year . '/' . $month . '/u_' . $userId . '/os_' . $ordemId;
        $destDir = rtrim(UPLOAD_PATH, '/\\') . DIRECTORY_SEPARATOR . $relativeDir;
        if (!is_dir($destDir)) {
            if (!@mkdir($destDir, 0755, true) && !is_dir($destDir)) {
                $out['userMessage'] = 'Não foi possível criar a pasta de imagens da OS. Verifique permissões no servidor.';
                return $out;
            }
        }

        $count   = min(count($files['name']), $maxNovos);
        $added   = 0;
        $lastErr = null;
        $tmpBase64 = [];

        for ($i = 0; $i < $count; $i++) {
            $err = (int)$files['error'][$i];
            if ($err !== UPLOAD_ERR_OK) {
                $lastErr = $this->mensagemErroUploadPhp($err);
                continue;
            }
            $tmp = (string)($files['tmp_name'][$i] ?? '');
            $name = (string)($files['name'][$i] ?? 'imagem.jpg');
            $rawSize = (int)($files['size'][$i] ?? 0);
            if ($rawSize > 20 * 1024 * 1024) {
                $lastErr = 'Imagem maior que 20MB (tamanho bruto).';
                continue;
            }
            if (!$this->isMimePermitidoUpload($tmp, $name)) {
                $lastErr = 'Tipo de imagem não permitido. Use JPG, PNG, GIF ou WebP.';
                continue;
            }

            try {
                $result = ImageCompressor::process([
                    'tmp_name' => $tmp,
                    'name'     => $name,
                ], $destDir);

                $this->imagemModel->create([
                    'ordem_id'   => $ordemId,
                    'arquivo'    => $relativeDir . '/' . $result['arquivo'],
                    'tamanho_kb' => $result['tamanho_kb'],
                    'largura'    => $result['largura'],
                    'altura'     => $result['altura'],
                    'criado_por' => Auth::id(),
                ]);
                $this->historicoModel->registrar(
                    $ordemId, Auth::id(), 'imagem',
                    "Imagem adicionada: {$result['arquivo']} ({$result['tamanho_kb']}KB)"
                );
                $added++;
            } catch (Throwable $e) {
                error_log("Erro ao processar imagem da OS {$ordemId}: " . $e->getMessage());
                $lastErr = 'Falha ao processar imagem: ' . $e->getMessage();
            } finally {
                if (str_starts_with($tmp, sys_get_temp_dir())) {
                    $tmpBase64[] = $tmp;
                }
            }
        }
        foreach (array_unique($tmpBase64) as $tmpFile) {
            if (is_file($tmpFile)) {
                @unlink($tmpFile);
            }
        }

        if ($added === 0) {
            $out['userMessage'] = $lastErr ?? 'Nenhuma imagem válida. Use JPG, PNG, WebP ou GIF.';
        }
        return $out;
    }

    private function normalizarImagensBase64Post(): array
    {
        $raw = $_POST['image_base64'] ?? [];
        if (is_string($raw) && trim($raw) !== '') {
            $raw = [$raw];
        }
        if (!is_array($raw) || $raw === []) {
            return [];
        }
        $out = [];
        $seq = 0;
        foreach ($raw as $payload) {
            if (!is_string($payload) || trim($payload) === '') {
                continue;
            }
            $data = trim($payload);
            if (str_starts_with($data, 'data:')) {
                $parts = explode(',', $data, 2);
                if (count($parts) !== 2) {
                    continue;
                }
                $meta = strtolower($parts[0]);
                $bin = base64_decode($parts[1], true);
                if ($bin === false) {
                    continue;
                }
                $mime = 'image/jpeg';
                if (str_contains($meta, 'image/png')) {
                    $mime = 'image/png';
                } elseif (str_contains($meta, 'image/gif')) {
                    $mime = 'image/gif';
                } elseif (str_contains($meta, 'image/webp')) {
                    $mime = 'image/webp';
                }
            } else {
                $bin = base64_decode($data, true);
                if ($bin === false) {
                    continue;
                }
                $mime = 'image/jpeg';
            }
            if (strlen($bin) > 20 * 1024 * 1024) {
                continue;
            }
            $tmp = tempnam(sys_get_temp_dir(), 'osb64_');
            if (!$tmp) {
                continue;
            }
            if (file_put_contents($tmp, $bin) === false) {
                @unlink($tmp);
                continue;
            }
            $ext = match ($mime) {
                'image/png' => 'png',
                'image/gif' => 'gif',
                'image/webp' => 'webp',
                default => 'jpg',
            };
            $seq++;
            $out[] = [
                'name' => 'camera_' . $seq . '.' . $ext,
                'type' => $mime,
                'tmp_name' => $tmp,
                'error' => UPLOAD_ERR_OK,
                'size' => strlen($bin),
            ];
        }
        return $out;
    }

    private function isMimePermitidoUpload(string $tmpPath, string $name = ''): bool
    {
        $mime = 'application/octet-stream';
        if (is_file($tmpPath) && class_exists('finfo')) {
            $fi = new finfo(FILEINFO_MIME_TYPE);
            $m = $fi->file($tmpPath);
            if (is_string($m) && $m !== '') {
                $mime = $m;
            }
        }
        if ($mime === 'application/octet-stream' && function_exists('mime_content_type')) {
            $m = @mime_content_type($tmpPath);
            if (is_string($m) && $m !== '') {
                $mime = $m;
            }
        }
        $allow = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        if (in_array(strtolower($mime), $allow, true)) {
            return true;
        }
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        return in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
    }

    private function mensagemQuandoNenhumFicheiroRecebido(): string
    {
        if (!empty($_FILES) && empty($_FILES['imagens'])) {
            return 'O servidor não recebeu o campo de imagens. Recarregue a página e tente outra vez.';
        }
        $cl  = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
        $pms = ini_get('post_max_size');
        if ($cl > 0 && empty($_FILES)) {
            $lim = self::iniSizeToBytes((string)$pms);
            if ($lim > 0 && $cl > $lim) {
                return 'O envio excede post_max_size (' . $pms . '). Aumente post_max_size e upload_max_filesize no php.ini ou use fotos menores.';
            }
            return 'Nenhum ficheiro chegou ao PHP. Verifique upload_max_filesize (' . ini_get('upload_max_filesize')
                . ') e post_max_size (' . $pms . ') no php.ini.';
        }
        return 'Nenhum ficheiro recebido. Selecione imagens antes de enviar.';
    }

    private function mensagemErroUploadPhp(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE,
            UPLOAD_ERR_FORM_SIZE => 'Ficheiro maior que upload_max_filesize / post_max_size no PHP.',
            UPLOAD_ERR_PARTIAL   => 'Upload interrompido. Tente de novo.',
            UPLOAD_ERR_NO_FILE   => 'Ficheiro em falta.',
            UPLOAD_ERR_NO_TMP_DIR => 'PHP sem pasta temporária (upload_tmp_dir).',
            UPLOAD_ERR_CANT_WRITE => 'O servidor não gravou o ficheiro temporário.',
            UPLOAD_ERR_EXTENSION  => 'Upload bloqueado por uma extensão do PHP.',
            default               => 'Erro no upload (código ' . $code . ').',
        };
    }

    private static function iniSizeToBytes(string $val): int
    {
        $val = trim($val);
        if ($val === '' || $val === '0') {
            return 0;
        }
        if (!preg_match('/^(\d+(?:\.\d+)?)\s*([gmk]?)/i', $val, $m)) {
            return (int)$val;
        }
        $n = (float)$m[1];
        $u = strtolower($m[2] ?? '');

        return (int)match ($u) {
            'g' => $n * 1073741824,
            'm' => $n * 1048576,
            'k' => $n * 1024,
            default => $n,
        };
    }

    /**
     * Garante array de arquivos no formato esperado (um único upload pode vir como string em name/tmp_name).
     *
     * @return array{name:array,type:array,tmp_name:array,error:array,size:array}|null
     */
    private function normalizarArquivosImagensPost(): ?array
    {
        if (empty($_FILES['imagens']) || !is_array($_FILES['imagens'])) {
            return null;
        }
        $f = $_FILES['imagens'];
        if (!isset($f['name'], $f['tmp_name'], $f['error'])) {
            return null;
        }
        if (!is_array($f['name'])) {
            if (($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                return null;
            }

            return [
                'name'     => [$f['name']],
                'type'     => [$f['type'] ?? ''],
                'tmp_name' => [$f['tmp_name']],
                'error'    => [$f['error']],
                'size'     => [$f['size'] ?? 0],
            ];
        }
        $names = [];
        $types = [];
        $tmpNames = [];
        $errors = [];
        $sizes = [];
        $n = count($f['name']);
        for ($i = 0; $i < $n; $i++) {
            $err = (int)($f['error'][$i] ?? UPLOAD_ERR_NO_FILE);
            if ($err === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $nm = $f['name'][$i] ?? '';
            if ($nm === '' || $nm === null) {
                continue;
            }
            $names[]     = $nm;
            $types[]     = $f['type'][$i] ?? '';
            $tmpNames[]  = $f['tmp_name'][$i];
            $errors[]    = $err;
            $sizes[]     = (int)($f['size'][$i] ?? 0);
        }
        if ($names === []) {
            return null;
        }

        return [
            'name'     => $names,
            'type'     => $types,
            'tmp_name' => $tmpNames,
            'error'    => $errors,
            'size'     => $sizes,
        ];
    }

    private function notificarNova(int $ordemId, string $numero, string $titulo): void
    {
        // Enfileira notificação para o responsável via Telegram
        $ordem = $this->ordemModel->findComDetalhes($ordemId);
        if (!$ordem || !$ordem['usuario_responsavel_id']) return;

        $responsavel = (new User())->find($ordem['usuario_responsavel_id']);
        if (!$responsavel || !$responsavel['telegram_ativo']) return;

        $this->db->execute(
            "INSERT INTO notificacoes_fila (tipo, destinatario_id, canal, mensagem) VALUES (?,?,?,?)",
            ['nova_os', $ordem['usuario_responsavel_id'], 'telegram',
             "🆕 <b>Nova OS: {$numero}</b>\n{$titulo}\nPrioridade: {$ordem['prioridade_nome']}\nSLA: " .
             SLAHelper::tempoRestante($ordem['sla_prazo'], 'aberta')]
        );
    }

    private function notificarAlteracaoStatus(int $ordemId, string $numero, string $novoStatus): void
    {
        $ordem = $this->ordemModel->findComDetalhes($ordemId);
        if (!$ordem) return;

        $alvo = $ordem['usuario_criador_id'];
        $user = (new User())->find($alvo);
        if (!$user || !$user['telegram_ativo']) return;

        $statusLabel = match ($novoStatus) {
            'aberta'       => '📂 Aberta',
            'em_andamento' => '🔧 Em Andamento',
            'aguardando'   => '⏳ Aguardando',
            'finalizada'   => '✅ Finalizada',
            'cancelada'    => '❌ Cancelada',
            default        => $novoStatus,
        };

        $this->db->execute(
            "INSERT INTO notificacoes_fila (tipo, destinatario_id, canal, mensagem) VALUES (?,?,?,?)",
            ['status_alterado', $alvo, 'telegram',
             "🔄 <b>OS {$numero}</b> — Status: {$statusLabel}"]
        );
    }
}
