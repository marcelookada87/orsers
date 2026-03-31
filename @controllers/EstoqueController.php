<?php

class EstoqueController extends Controller
{
    private EstoqueSaldo $saldoModel;
    private EstoqueItem $itemModel;
    private EstoqueOsItem $osItemModel;
    private EstoqueMovimentacao $movModel;

    public function __construct()
    {
        parent::__construct();
        $this->saldoModel  = new EstoqueSaldo();
        $this->itemModel   = new EstoqueItem();
        $this->osItemModel = new EstoqueOsItem();
        $this->movModel    = new EstoqueMovimentacao();
    }

    private function requireEstoqueAtivoUsuario(): void
    {
        Auth::requireLogin();
        if (!LimiteConta::estoqueAtivo((int)Auth::id())) {
            $this->setFlash('error', 'O módulo de estoque não está ativo para sua conta.');
            $this->redirect('/dashboard');
        }
    }

    /** Catálogo, categorias e relatório: só perfil técnico (não admin) com estoque ativo. */
    private function requireGestaoCatalogoTecnico(): void
    {
        $this->requireEstoqueAtivoUsuario();
        Auth::requirePerfilTecnico();
    }

    /** @return array<string,mixed> */
    private function assertOrdemVisivel(int $ordemId): array
    {
        $ordem = (new Ordem())->findComDetalhes($ordemId);
        if (!$ordem) {
            $this->abort(404, 'Ordem não encontrada.');
        }
        if (!Auth::isAdmin() && (int)$ordem['usuario_criador_id'] !== (int)Auth::id()
            && (int)($ordem['usuario_responsavel_id'] ?? 0) !== (int)Auth::id()) {
            $this->abort(403, 'Sem permissão.');
        }

        return $ordem;
    }

    private function assertOrdemEditavelMateriais(array $ordem): void
    {
        if (in_array($ordem['status'], ['finalizada', 'cancelada'], true)) {
            throw new RuntimeException('OS encerrada: materiais não podem ser alterados.');
        }
    }

    /** @return array<string,mixed> */
    private function carregarOrdemParaMateriais(int $ordemId): array
    {
        $ordem = $this->assertOrdemVisivel($ordemId);
        $this->assertOrdemEditavelMateriais($ordem);

        return $ordem;
    }

    /**
     * @param list<array<string,mixed>> $linhas
     * @return list<array<string,mixed>>
     */
    private function filtrarLinhasEstoqueIndex(array $linhas, string $filtro, string $busca, string $categoria): array
    {
        $buscaNorm = $busca !== '' ? mb_strtolower(trim($busca), 'UTF-8') : '';
        $out       = [];
        foreach ($linhas as $r) {
            if ($categoria !== '' && trim((string)($r['categoria_nome'] ?? '')) !== $categoria) {
                continue;
            }
            $q = (float)$r['quantidade'];
            $m = (float)$r['quantidade_minima'];
            $inativo = (int)($r['item_ativo'] ?? 0) === 0;
            if ($buscaNorm !== '') {
                $hay = mb_strtolower(
                    trim((string)($r['item_codigo'] ?? '')) . ' ' . trim((string)($r['item_nome'] ?? '')),
                    'UTF-8'
                );
                if (!str_contains($hay, $buscaNorm)) {
                    continue;
                }
            }
            $ok = match ($filtro) {
                'alerta' => $m > 0 && $q <= $m,
                'zerado' => $q <= 0,
                'inativo' => $inativo,
                'ok' => $q > 0 && ($m <= 0.0 || $q > $m),
                default => true,
            };
            if ($ok) {
                $out[] = $r;
            }
        }

        return $out;
    }

    public function index(): void
    {
        $this->requireEstoqueAtivoUsuario();
        $uid   = (int)Auth::id();
        $flash = $this->getFlash();
        $linhasTodas = $this->saldoModel->getSaldoUsuario($uid);
        $baixo  = 0;
        $zerado  = 0;
        foreach ($linhasTodas as $r) {
            $q = (float)$r['quantidade'];
            $m = (float)$r['quantidade_minima'];
            if ($q <= 0) {
                ++$zerado;
            } elseif ($m > 0 && $q <= $m) {
                ++$baixo;
            }
        }
        $filtro = (string)$this->get('f', 'todos');
        $busca  = trim((string)$this->get('q', ''));
        $catFiltro = trim((string)$this->get('cat', ''));
        $permitidos = ['todos', 'alerta', 'zerado', 'inativo', 'ok'];
        if (!in_array($filtro, $permitidos, true)) {
            $filtro = 'todos';
        }
        $categoriasOpts = [];
        foreach ($linhasTodas as $r) {
            $cn = trim((string)($r['categoria_nome'] ?? ''));
            if ($cn !== '') {
                $categoriasOpts[$cn] = true;
            }
        }
        $categoriasOpts = array_keys($categoriasOpts);
        sort($categoriasOpts, SORT_NATURAL | SORT_FLAG_CASE);
        if ($catFiltro !== '' && !in_array($catFiltro, $categoriasOpts, true)) {
            $catFiltro = '';
        }
        $linhas = $this->filtrarLinhasEstoqueIndex($linhasTodas, $filtro, $busca, $catFiltro);
        $tiposOk = $this->saldoModel->contarTiposComEstoquePositivo($uid);
        $user    = (new User())->find($uid);
        $limite  = $user['estoque_limite_itens'] ?? null;
        $totalLinhas = count($linhasTodas);
        $filtroEstoqueAtivo = $filtro !== 'todos' || $busca !== '' || $catFiltro !== '';

        $this->render('estoque/index', compact(
            'flash',
            'linhas',
            'linhasTodas',
            'baixo',
            'zerado',
            'tiposOk',
            'limite',
            'filtro',
            'busca',
            'catFiltro',
            'categoriasOpts',
            'totalLinhas',
            'filtroEstoqueAtivo'
        ));
    }

    public function entradaForm(): void
    {
        $this->requireEstoqueAtivoUsuario();
        $flash      = $this->getFlash();
        $itens      = $this->itemModel->listarAtivos();
        $sugerido   = $this->itemModel->proximoCodigoSugerido((int)Auth::id());

        $this->render('estoque/entrada', compact('flash', 'itens', 'sugerido'));
    }

    public function entradaSalvar(): void
    {
        $this->requireEstoqueAtivoUsuario();
        $itemId = (int)$this->post('item_id', 0);
        try {
            $q = EstoqueSaldo::parseQuantidade($this->post('quantidade', ''));
        } catch (InvalidArgumentException $e) {
            $this->setFlash('error', $e->getMessage());
            $this->redirect('/estoque/entrada');
        }
        $obs = trim($this->post('observacao', ''));

        if ($itemId <= 0) {
            $this->setFlash('error', 'Selecione o item do catálogo.');
            $this->redirect('/estoque/entrada');
        }
        $item = $this->itemModel->find($itemId);
        if (!$item || !(int)($item['ativo'] ?? 0)) {
            $this->setFlash('error', 'Item inválido ou inativo.');
            $this->redirect('/estoque/entrada');
        }

        try {
            $this->saldoModel->executarEntrada(
                (int)Auth::id(),
                $itemId,
                $q,
                'manual',
                null,
                $obs !== '' ? $obs : null
            );
        } catch (RuntimeException $e) {
            $this->setFlash('error', $e->getMessage());
            $this->redirect('/estoque/entrada');
        }

        $this->setFlash('success', 'Estoque atualizado com sucesso.');
        $this->redirect('/estoque');
    }

    public function atualizarMinimo(): void
    {
        $this->requireEstoqueAtivoUsuario();
        $itemId = (int)$this->post('item_id', 0);
        $minRaw = $this->post('quantidade_minima', '0');
        $min    = max(0, round((float)str_replace(',', '.', (string)$minRaw), 3));
        if ($itemId <= 0) {
            $this->setFlash('error', 'Item inválido.');
            $this->redirect('/estoque');
        }
        $this->db->execute(
            'UPDATE `estoque_saldo` SET `quantidade_minima` = ? WHERE `usuario_id` = ? AND `item_id` = ?',
            [$min, (int)Auth::id(), $itemId]
        );
        $this->setFlash('success', 'Alerta de estoque mínimo atualizado.');
        $this->redirect('/estoque');
    }

    /** Catálogo global: CRUD (técnico com estoque ativo). */
    public function catalogoGerir(): void
    {
        $this->requireGestaoCatalogoTecnico();
        $flash       = $this->getFlash();
        $itens       = $this->itemModel->listarTodosComCategoria();
        $categorias  = (new EstoqueCategoria())->listarTodas();
        $sugerido    = $this->itemModel->proximoCodigoSugerido((int)Auth::id());

        $this->render('estoque/catalogo', compact('flash', 'itens', 'categorias', 'sugerido'));
    }

    /** @deprecated Rota antiga — redireciona para Conta › Configuração. */
    public function redirectConfigTecnicoLegado(): never
    {
        header('Location: ' . BASE_URL . '/conta/configuracao/catalogo-codigo', true, 302);
        exit;
    }

    public function catalogoCriar(): void
    {
        $this->requireGestaoCatalogoTecnico();
        $codigo = strtoupper(trim($this->post('codigo', '')));
        $nome   = trim($this->post('nome', ''));
        if ($codigo === '' || !preg_match('/^[A-Z0-9._-]{1,64}$/', $codigo)) {
            $this->setFlash('error', 'Código inválido (1–64 caracteres: letras maiúsculas, números, . _ -).');
            $this->redirect('/estoque/catalogo');
        }
        if ($nome === '') {
            $this->setFlash('error', 'Informe o nome do item.');
            $this->redirect('/estoque/catalogo');
        }
        if ($this->itemModel->findByCodigo($codigo)) {
            $this->setFlash('error', 'Já existe item com este código.');
            $this->redirect('/estoque/catalogo');
        }

        $catId = (int)$this->post('categoria_id', 0);
        $catId = $catId > 0 ? $catId : null;
        if ($catId !== null && !(new EstoqueCategoria())->find($catId)) {
            $catId = null;
        }

        $unidade = trim($this->post('unidade', 'un'));
        if (mb_strlen($unidade) > 16) {
            $unidade = mb_substr($unidade, 0, 16);
        }
        $desc = trim($this->post('descricao', ''));
        if (mb_strlen($desc) > 500) {
            $desc = mb_substr($desc, 0, 500);
        }

        $qIniRaw = trim($this->post('quantidade_inicial', ''));
        $qIni    = null;
        if ($qIniRaw !== '') {
            try {
                $qIni = EstoqueSaldo::parseQuantidade($qIniRaw);
            } catch (InvalidArgumentException $e) {
                $this->setFlash('error', 'Quantidade inicial inválida: ' . $e->getMessage());
                $this->redirect('/estoque/catalogo');
            }
        }

        $newId = (int)$this->itemModel->create([
            'categoria_id' => $catId,
            'codigo'       => $codigo,
            'nome'         => mb_substr($nome, 0, 200),
            'descricao'    => $desc !== '' ? $desc : null,
            'unidade'      => $unidade !== '' ? $unidade : 'un',
            'ativo'        => 1,
            'criado_por'   => (int)Auth::id(),
        ]);

        if ($qIni !== null && $qIni > 0) {
            try {
                $this->saldoModel->executarEntrada(
                    (int)Auth::id(),
                    $newId,
                    $qIni,
                    'manual',
                    null,
                    'Entrada automática na criação do item'
                );
                $this->setFlash('success', 'Item cadastrado e quantidade inicial lançada no seu estoque.');
            } catch (Throwable $e) {
                $this->setFlash(
                    'error',
                    'Item cadastrado no catálogo, mas a quantidade inicial não foi aplicada: ' . $e->getMessage()
                );
            }
        } else {
            $this->setFlash('success', 'Item cadastrado no catálogo.');
        }

        $this->redirect('/estoque/catalogo');
    }

    public function catalogoEditar(string $id): void
    {
        $this->requireGestaoCatalogoTecnico();
        $item = $this->itemModel->find((int)$id);
        if (!$item) {
            $this->abort(404);
        }
        $flash      = $this->getFlash();
        $categorias = (new EstoqueCategoria())->listarTodas();

        $this->render('estoque/catalogo_editar', compact('flash', 'item', 'categorias'));
    }

    public function catalogoAtualizar(string $id): void
    {
        $this->requireGestaoCatalogoTecnico();
        $iid = (int)$id;
        $ex  = $this->itemModel->find($iid);
        if (!$ex) {
            $this->abort(404);
        }

        $nome = trim($this->post('nome', ''));
        if ($nome === '') {
            $this->setFlash('error', 'Nome obrigatório.');
            $this->redirect('/estoque/catalogo/' . $iid . '/editar');
        }

        $catId = (int)$this->post('categoria_id', 0);
        $catId = $catId > 0 ? $catId : null;
        if ($catId !== null && !(new EstoqueCategoria())->find($catId)) {
            $catId = null;
        }

        $unidade = trim($this->post('unidade', 'un'));
        if (mb_strlen($unidade) > 16) {
            $unidade = mb_substr($unidade, 0, 16);
        }
        $desc = trim($this->post('descricao', ''));
        if (mb_strlen($desc) > 500) {
            $desc = mb_substr($desc, 0, 500);
        }

        $this->itemModel->update($iid, [
            'categoria_id' => $catId,
            'nome'         => mb_substr($nome, 0, 200),
            'descricao'    => $desc !== '' ? $desc : null,
            'unidade'      => $unidade !== '' ? $unidade : 'un',
        ]);

        $this->setFlash('success', 'Item atualizado.');
        $this->redirect('/estoque/catalogo');
    }

    public function catalogoToggle(string $id): void
    {
        $this->requireGestaoCatalogoTecnico();
        $iid = (int)$id;
        $ex  = $this->itemModel->find($iid);
        if (!$ex) {
            $this->abort(404);
        }
        $novo = (int)!((int)($ex['ativo'] ?? 0));
        $this->itemModel->update($iid, ['ativo' => $novo]);
        $this->setFlash('success', $novo ? 'Item ativado.' : 'Item desativado.');
        $this->redirect('/estoque/catalogo');
    }

    public function categoriasIndex(): void
    {
        $this->requireGestaoCatalogoTecnico();
        $flash      = $this->getFlash();
        $categorias = (new EstoqueCategoria())->listarTodasComContagem();
        $this->render('estoque/categorias', compact('flash', 'categorias'));
    }

    public function categoriasCriar(): void
    {
        $this->requireGestaoCatalogoTecnico();
        $nome = trim($this->post('nome', ''));
        if ($nome === '') {
            $this->setFlash('error', 'Nome da categoria obrigatório.');
            $this->redirect('/estoque/categorias');
        }
        $desc = trim($this->post('descricao', ''));
        (new EstoqueCategoria())->create([
            'nome'      => mb_substr($nome, 0, 120),
            'descricao' => $desc !== '' ? mb_substr($desc, 0, 500) : null,
            'ativo'     => 1,
        ]);
        $this->setFlash('success', 'Categoria criada.');
        $this->redirect('/estoque/categorias');
    }

    public function categoriasEditar(string $id): void
    {
        $this->requireGestaoCatalogoTecnico();
        $cid = (int)$id;
        $cat = (new EstoqueCategoria())->find($cid);
        if (!$cat) {
            $this->abort(404);
        }
        $flash = $this->getFlash();
        $this->render('estoque/categorias_editar', compact('flash', 'cat'));
    }

    public function categoriasAtualizar(string $id): void
    {
        $this->requireGestaoCatalogoTecnico();
        $cid = (int)$id;
        $catModel = new EstoqueCategoria();
        $ex       = $catModel->find($cid);
        if (!$ex) {
            $this->abort(404);
        }
        $nome = trim($this->post('nome', ''));
        if ($nome === '') {
            $this->setFlash('error', 'Nome da categoria obrigatório.');
            $this->redirect('/estoque/categorias/' . $cid . '/editar');
        }
        $desc = trim($this->post('descricao', ''));
        $ativo = isset($_POST['ativo']) ? 1 : 0;
        $catModel->update($cid, [
            'nome'      => mb_substr($nome, 0, 120),
            'descricao' => $desc !== '' ? mb_substr($desc, 0, 500) : null,
            'ativo'     => $ativo,
        ]);
        $this->setFlash('success', 'Categoria atualizada.');
        $this->redirect('/estoque/categorias');
    }

    public function categoriasExcluir(string $id): void
    {
        $this->requireGestaoCatalogoTecnico();
        $cid = (int)$id;
        $catModel = new EstoqueCategoria();
        $ex       = $catModel->find($cid);
        if (!$ex) {
            $this->abort(404);
        }
        $n = $catModel->contarItensVinculados($cid);
        if ($n > 0) {
            $this->setFlash(
                'error',
                'Não é possível excluir: existem ' . $n . ' item(ns) do catálogo vinculado(s) a esta categoria. Altere ou remova o vínculo nos itens antes de excluir.'
            );
            $this->redirect('/estoque/categorias');
        }
        $catModel->delete($cid);
        $this->setFlash('success', 'Categoria excluída.');
        $this->redirect('/estoque/categorias');
    }

    /** Consumo em OS apenas do técnico logado. */
    public function relatorioConsumo(): void
    {
        $this->requireGestaoCatalogoTecnico();
        $flash = $this->getFlash();
        $uid   = (int)Auth::id();
        $ini   = trim($this->get('data_ini', date('Y-m-01')));
        $fim   = trim($this->get('data_fim', date('Y-m-t')));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $ini)) {
            $ini = date('Y-m-01');
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fim)) {
            $fim = date('Y-m-t');
        }

        $consumo = $this->db->fetchAll(
            "SELECT o.`numero`, o.`id` AS ordem_id, o.`titulo`,
                    i.`codigo`, i.`nome` AS item_nome, oi.`quantidade`, i.`unidade`, oi.`created_at`
             FROM `estoque_os_itens` oi
             INNER JOIN `ordens_servico` o ON o.`id` = oi.`ordem_id`
             INNER JOIN `estoque_itens` i ON i.`id` = oi.`item_id`
             WHERE oi.`usuario_id` = ? AND oi.`created_at` >= ? AND oi.`created_at` <= ?
             ORDER BY oi.`created_at` DESC",
            [$uid, $ini . ' 00:00:00', $fim . ' 23:59:59']
        );

        $ranking = $this->db->fetchAll(
            "SELECT i.`nome`, i.`codigo`, SUM(oi.`quantidade`) AS total_q
             FROM `estoque_os_itens` oi
             INNER JOIN `estoque_itens` i ON i.`id` = oi.`item_id`
             WHERE oi.`usuario_id` = ? AND oi.`created_at` >= ? AND oi.`created_at` <= ?
             GROUP BY i.`id`, i.`nome`, i.`codigo`
             ORDER BY total_q DESC
             LIMIT 30",
            [$uid, $ini . ' 00:00:00', $fim . ' 23:59:59']
        );

        $this->render('estoque/relatorio', compact('flash', 'consumo', 'ranking', 'ini', 'fim'));
    }

    public function historico(): void
    {
        $this->requireEstoqueAtivoUsuario();
        $filtros = [
            'tipo'     => trim($this->get('tipo', '')),
            'item_id'  => (int)$this->get('item_id', 0),
            'data_ini' => trim($this->get('data_ini', '')),
            'data_fim' => trim($this->get('data_fim', '')),
        ];
        if ($filtros['tipo'] !== '' && !in_array($filtros['tipo'], ['entrada', 'saida', 'ajuste'], true)) {
            $filtros['tipo'] = '';
        }
        $effective = array_filter(
            [
                'tipo'     => $filtros['tipo'] !== '' ? $filtros['tipo'] : null,
                'item_id'  => $filtros['item_id'] > 0 ? $filtros['item_id'] : null,
                'data_ini' => $filtros['data_ini'] !== '' ? $filtros['data_ini'] : null,
                'data_fim' => $filtros['data_fim'] !== '' ? $filtros['data_fim'] : null,
            ],
            static fn ($v) => $v !== null && $v !== ''
        );

        $movs  = $this->movModel->listarPorUsuario((int)Auth::id(), $effective);
        $itens = $this->itemModel->listarAtivos();
        $flash = $this->getFlash();

        $this->render('estoque/historico', compact('movs', 'itens', 'filtros', 'flash'));
    }

    public function apiSaldo(): void
    {
        Auth::requireLogin();
        if (!LimiteConta::estoqueAtivo((int)Auth::id())) {
            $this->json([
                'ok'    => false,
                'error' => 'Estoque inativo. O administrador pode ativar em Administração → Permissões de estoque.',
            ], 403);
        }
        $q = trim($this->get('q', ''));
        $lista = $this->saldoModel->listarComSaldoParaUsuario((int)Auth::id());
        if ($q !== '') {
            $ql = mb_strtolower($q);
            $lista = array_values(array_filter(
                $lista,
                static function ($row) use ($ql) {
                    return str_contains(mb_strtolower((string)$row['nome']), $ql)
                        || str_contains(mb_strtolower((string)$row['codigo']), $ql);
                }
            ));
        }

        $this->json(['ok' => true, 'itens' => $lista]);
    }

    public function apiItensOs(string $id): void
    {
        Auth::requireLogin();
        $oid   = (int)$id;
        $ordem = $this->assertOrdemVisivel($oid);
        $itens = $this->osItemModel->listarPorOrdem($oid);
        $estoqueOk = LimiteConta::estoqueAtivo((int)Auth::id());
        $osAberta  = !in_array($ordem['status'], ['finalizada', 'cancelada'], true);
        $pode      = $estoqueOk && $osAberta;

        $motivoBloqueio = null;
        if (!$pode) {
            if (!$estoqueOk) {
                $motivoBloqueio = 'O estoque não está ativo para o seu usuário. O administrador pode ativar em Administração → Permissões de estoque.';
            } elseif (!$osAberta) {
                $motivoBloqueio = 'Esta OS está finalizada ou cancelada; não é possível lançar ou remover materiais.';
            }
        }

        $this->json([
            'ok'              => true,
            'itens'           => $itens,
            'pode_editar'     => $pode,
            'motivo_bloqueio' => $motivoBloqueio,
        ]);
    }

    public function adicionarItemOs(string $id): void
    {
        Auth::requireLogin();
        if (!LimiteConta::estoqueAtivo((int)Auth::id())) {
            $this->json([
                'ok'    => false,
                'error' => 'Estoque inativo para sua conta. Peça ao administrador para ativar em Administração → Permissões de estoque.',
            ], 403);
        }
        $oid = (int)$id;
        try {
            $this->carregarOrdemParaMateriais($oid);
        } catch (RuntimeException $e) {
            $this->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }

        $itemId = (int)$this->post('item_id', 0);
        try {
            $q = EstoqueSaldo::parseQuantidade($this->post('quantidade', ''));
        } catch (InvalidArgumentException $e) {
            $this->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }
        $obs = trim($this->post('observacao', ''));

        if ($itemId <= 0) {
            $this->json(['ok' => false, 'error' => 'Informe o item.'], 422);
        }

        $confirmarNegativo = isset($_POST['confirmar_saldo_negativo'])
            && (string)$this->post('confirmar_saldo_negativo', '') === '1';

        try {
            $ret = $this->saldoModel->executarSaidaParaOs(
                (int)Auth::id(),
                $oid,
                $itemId,
                $q,
                $obs !== '' ? $obs : null,
                $confirmarNegativo
            );
        } catch (EstoqueSaldoInsuficienteException $e) {
            $this->json([
                'ok'          => false,
                'code'        => 'saldo_insuficiente',
                'disponivel'  => $e->disponivel,
                'solicitado'  => $e->solicitado,
                'message'     => sprintf(
                    'Saldo insuficiente: disponível %.3f, informado %.3f.',
                    $e->disponivel,
                    $e->solicitado
                ),
            ], 422);
        } catch (RuntimeException $e) {
            $this->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }

        $row = $this->osItemModel->find($ret['os_item_id']);
        $this->json(['ok' => true, 'os_item' => $row]);
    }

    public function removerItemOs(string $id, string $osItemId): void
    {
        Auth::requireLogin();
        if (!LimiteConta::estoqueAtivo((int)Auth::id())) {
            $this->json([
                'ok'    => false,
                'error' => 'Estoque inativo para sua conta. Peça ao administrador para ativar em Administração → Permissões de estoque.',
            ], 403);
        }
        $oid = (int)$id;
        $iid = (int)$osItemId;
        try {
            $this->carregarOrdemParaMateriais($oid);
        } catch (RuntimeException $e) {
            $this->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }

        try {
            $this->saldoModel->executarDevolucaoOs($iid, $oid, (int)Auth::id(), Auth::isAdmin());
        } catch (RuntimeException $e) {
            $this->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }

        $this->json(['ok' => true]);
    }
}
