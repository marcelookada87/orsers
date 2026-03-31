<?php
class SLAController extends Controller
{
    private Ordem $ordemModel;
    private OrdemHistorico $historicoModel;
    private CategoriaOS $categoriaModel;
    private Prioridade $prioridadeModel;
    private User $userModel;
    private Cliente $clienteModel;

    public function __construct()
    {
        parent::__construct();
        $this->ordemModel      = new Ordem();
        $this->historicoModel  = new OrdemHistorico();
        $this->categoriaModel  = new CategoriaOS();
        $this->prioridadeModel = new Prioridade();
        $this->userModel       = new User();
        $this->clienteModel    = new Cliente();
    }

    public function painel(): void
    {
        Auth::requireLogin();
        $ordens = $this->ordemModel->paineISla();

        $dados = array_map(function ($o) {
            $pct   = SLAHelper::percentual($o['data_abertura'], $o['sla_prazo']);
            $classe = SLAHelper::cssClass($pct, $o['status']);
            $label  = SLAHelper::label($pct, $o['status']);
            $restante = SLAHelper::tempoRestante($o['sla_prazo'], $o['status']);
            $aberto   = SLAHelper::tempoAberto($o['data_abertura']);
            return array_merge($o, [
                'sla_percent'  => $pct,
                'sla_class'    => $classe,
                'sla_label'    => $label,
                'tempo_restante' => $restante,
                'tempo_aberto'   => $aberto,
            ]);
        }, $ordens);

        $user  = Auth::user();
        $flash = $this->getFlash();
        $this->render('sla/painel', compact('user', 'dados', 'flash'));
    }

    public function apiSla(): void
    {
        Auth::requireLogin();
        $ordens = $this->ordemModel->paineISla();
        $result = [];
        foreach ($ordens as $o) {
            $pct = SLAHelper::percentual($o['data_abertura'], $o['sla_prazo']);
            $result[] = [
                'id'         => $o['id'],
                'numero'     => $o['numero'],
                'titulo'     => $o['titulo'],
                'status'     => $o['status'],
                'percentual' => $pct,
                'classe'     => SLAHelper::cssClass($pct, $o['status']),
                'label'      => SLAHelper::label($pct, $o['status']),
                'restante'   => SLAHelper::tempoRestante($o['sla_prazo'], $o['status']),
            ];
        }
        $this->json($result);
    }

    /** POST — recalcula SLA a partir de agora (categoria × prioridade). */
    public function resetarSla(string $id): void
    {
        Auth::requireTecnico();
        $id = (int)$id;
        if ($id <= 0) {
            $this->setFlash('error', 'Ordem inválida.');
            if ($this->isAjaxRequest()) {
                $this->json(['ok' => false, 'error' => 'Ordem inválida.'], 400);
            }
            $this->redirect('/sla');
        }
        if ($this->ordemModel->recalcularSlaApartirDeAgora($id)) {
            $this->historicoModel->registrar(
                $id,
                Auth::id(),
                'sla',
                'SLA recalculado a partir da data e hora atuais (prazo renovado).'
            );
            $this->setFlash('success', 'SLA atualizado a partir de agora.');
            if ($this->isAjaxRequest()) {
                $this->json(['ok' => true]);
            }
        } else {
            $this->setFlash('error', 'Não foi possível resetar o SLA desta ordem.');
            if ($this->isAjaxRequest()) {
                $this->json(['ok' => false, 'error' => 'Não foi possível resetar o SLA.'], 400);
            }
        }
        $this->redirect('/sla');
    }

    /** POST — finaliza a OS pelo painel. */
    public function concluirOrdem(string $id): void
    {
        Auth::requireTecnico();
        $id = (int)$id;
        if ($id <= 0) {
            $this->setFlash('error', 'Ordem inválida.');
            if ($this->isAjaxRequest()) {
                $this->json(['ok' => false, 'error' => 'Ordem inválida.'], 400);
            }
            $this->redirect('/sla');
        }
        $ordem = $this->ordemModel->findComDetalhes($id);
        if (!$ordem) {
            $this->setFlash('error', 'Ordem não encontrada.');
            if ($this->isAjaxRequest()) {
                $this->json(['ok' => false, 'error' => 'Ordem não encontrada.'], 404);
            }
            $this->redirect('/sla');
        }
        if (in_array($ordem['status'], ['finalizada', 'cancelada'], true)) {
            $this->setFlash('error', 'Esta ordem já está encerrada.');
            if ($this->isAjaxRequest()) {
                $this->json(['ok' => false, 'error' => 'Ordem já encerrada.'], 400);
            }
            $this->redirect('/sla');
        }
        $statusAntigo = $ordem['status'];
        $update       = [
            'status'             => 'finalizada',
            'data_finalizacao'   => date('Y-m-d H:i:s'),
        ];
        if (empty($ordem['data_inicio'])) {
            $update['data_inicio'] = date('Y-m-d H:i:s');
        }
        $this->ordemModel->update($id, $update);
        $this->historicoModel->registrar(
            $id,
            Auth::id(),
            'finalizacao',
            "OS {$ordem['numero']} concluída pelo painel SLA.",
            ['de' => $statusAntigo, 'para' => 'finalizada']
        );
        $this->setFlash('success', "Ordem {$ordem['numero']} concluída.");
        if ($this->isAjaxRequest()) {
            $this->json(['ok' => true, 'numero' => $ordem['numero']]);
        }
        $this->redirect('/sla');
    }

    /** GET — JSON para modal “Ver” no painel SLA. */
    public function apiOrdemVer(string $id): void
    {
        Auth::requireLogin();
        $oid = (int)$id;
        if ($oid <= 0) {
            $this->json(['ok' => false, 'error' => 'Inválido'], 400);
        }
        $ordem = $this->ordemModel->findComDetalhes($oid);
        if (!$ordem) {
            $this->json(['ok' => false, 'error' => 'Não encontrada'], 404);
        }
        if (!Auth::isTecnico()
            && (int)$ordem['usuario_criador_id'] !== (int)Auth::id()
            && (int)($ordem['usuario_responsavel_id'] ?? 0) !== (int)Auth::id()) {
            $this->json(['ok' => false, 'error' => 'Sem permissão'], 403);
        }

        $pct = SLAHelper::percentual($ordem['data_abertura'], $ordem['sla_prazo']);
        $this->json([
            'ok'    => true,
            'ordem' => [
                'id'                 => (int)$ordem['id'],
                'numero'             => $ordem['numero'],
                'titulo'             => $ordem['titulo'],
                'descricao'          => $ordem['descricao'],
                'observacoes'        => $ordem['observacoes'] ?? '',
                'status'             => $ordem['status'],
                'categoria_nome'     => $ordem['categoria_nome'] ?? '',
                'prioridade_nome'    => $ordem['prioridade_nome'] ?? '',
                'prioridade_cor'     => $ordem['prioridade_cor'] ?? '#888',
                'cliente_nome'       => $ordem['cliente_nome'] ?? '',
                'cliente_fantasia'   => $ordem['cliente_fantasia'] ?? '',
                'responsavel_nome'   => $ordem['responsavel_nome'] ?? '',
                'criador_nome'       => $ordem['criador_nome'] ?? '',
                'data_abertura_fmt'  => date('d/m/Y H:i', strtotime($ordem['data_abertura'])),
                'sla_prazo_fmt'      => $ordem['sla_prazo'] ? date('d/m/Y H:i', strtotime($ordem['sla_prazo'])) : '—',
            ],
            'sla'   => [
                'percent'   => $pct,
                'class'     => SLAHelper::cssClass($pct, $ordem['status']),
                'label'     => SLAHelper::label($pct, $ordem['status']),
                'restante'  => SLAHelper::tempoRestante($ordem['sla_prazo'], $ordem['status']),
                'aberto_ha' => SLAHelper::tempoAberto($ordem['data_abertura']),
            ],
        ]);
    }

    /** GET — comentários para modal no painel SLA. */
    public function apiOrdemComentarios(string $id): void
    {
        Auth::requireLogin();
        $oid = (int)$id;
        if ($oid <= 0) {
            $this->json(['ok' => false], 400);
        }
        $ordem = $this->ordemModel->findComDetalhes($oid);
        if (!$ordem) {
            $this->json(['ok' => false, 'error' => 'Não encontrada'], 404);
        }
        if (!Auth::isTecnico()
            && (int)$ordem['usuario_criador_id'] !== (int)Auth::id()
            && (int)($ordem['usuario_responsavel_id'] ?? 0) !== (int)Auth::id()) {
            $this->json(['ok' => false, 'error' => 'Sem permissão'], 403);
        }

        $rows = $this->db->fetchAll(
            "SELECT c.id, c.comentario, c.created_at, u.nome AS usuario_nome
             FROM ordens_comentarios c
             LEFT JOIN usuarios u ON u.id = c.usuario_id
             WHERE c.ordem_id = ?
             ORDER BY c.created_at DESC
             LIMIT 40",
            [$oid]
        );
        foreach ($rows as &$r) {
            $r['created_fmt'] = date('d/m/Y H:i', strtotime($r['created_at']));
        }
        unset($r);

        $this->json(['ok' => true, 'comentarios' => $rows, 'numero' => $ordem['numero']]);
    }

    /** GET — fragmento HTML do formulário de edição (modal). */
    public function modalEditarForm(string $id): void
    {
        Auth::requireLogin();
        $oid = (int)$id;
        if ($oid <= 0) {
            http_response_code(400);
            echo '<p class="sla-modal-err">Ordem inválida.</p>';
            return;
        }
        $ordem = $this->ordemModel->findComDetalhes($oid);
        if (!$ordem) {
            http_response_code(404);
            echo '<p class="sla-modal-err">Ordem não encontrada.</p>';
            return;
        }
        $pode = Auth::isTecnico() || (int)$ordem['usuario_criador_id'] === (int)Auth::id();
        if (!$pode) {
            http_response_code(403);
            echo '<p class="sla-modal-err">Sem permissão para editar.</p>';
            return;
        }
        if (in_array($ordem['status'], ['finalizada', 'cancelada'], true)) {
            http_response_code(400);
            echo '<p class="sla-modal-err">OS encerrada — use a página da ordem.</p>';
            return;
        }

        $categorias  = $this->categoriaModel->allAtivas();
        $prioridades = $this->prioridadeModel->listarParaEdicaoOrdem(
            isset($ordem['prioridade_id']) ? (int)$ordem['prioridade_id'] : null
        );
        $clientes    = $this->clienteModel->listarParaSelect();
        if (!empty($ordem['cliente_id'])) {
            $cid = (int)$ordem['cliente_id'];
            $ids = array_column($clientes, 'id');
            if (!in_array($cid, $ids, true)) {
                $extra = $this->clienteModel->find($cid);
                if ($extra) {
                    array_unshift($clientes, [
                        'id'                => $extra['id'],
                        'nome_razao_social' => $extra['nome_razao_social'],
                        'nome_fantasia'     => $extra['nome_fantasia'],
                        'documento'         => $extra['documento'],
                    ]);
                }
            }
        }

        $vfServ = isset($ordem['valor_servico']) && $ordem['valor_servico'] !== null && $ordem['valor_servico'] !== ''
            ? number_format((float)$ordem['valor_servico'], 2, ',', '.') : '';
        $vfPago = isset($ordem['valor_pago']) && $ordem['valor_pago'] !== null && $ordem['valor_pago'] !== ''
            ? number_format((float)$ordem['valor_pago'], 2, ',', '.') : '';
        $dtFinLocal = !empty($ordem['data_finalizacao'])
            ? date('Y-m-d\TH:i', strtotime($ordem['data_finalizacao'])) : '';
        $formasPgEdit = Ordem::formasPagamentoOpcoes();

        $this->render('sla/partial_modal_editar', compact(
            'ordem', 'categorias', 'prioridades', 'clientes',
            'vfServ', 'vfPago', 'dtFinLocal', 'formasPgEdit'
        ), null);
    }
}
