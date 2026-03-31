<?php
class TelegramWebhookController extends Controller
{
    private TelegramSender $telegram;
    private User $userModel;
    private Ordem $ordemModel;
    private OrdemHistorico $historicoModel;
    private CategoriaOS $categoriaModel;
    private Prioridade $prioridadeModel;

    public function __construct()
    {
        parent::__construct();
        $this->telegram       = new TelegramSender();
        $this->userModel      = new User();
        $this->ordemModel     = new Ordem();
        $this->historicoModel = new OrdemHistorico();
        $this->categoriaModel = new CategoriaOS();
        $this->prioridadeModel = new Prioridade();
    }

    public function webhook(): void
    {
        $body   = file_get_contents('php://input');
        $update = json_decode($body, true);

        if (empty($update)) {
            http_response_code(200);
            exit;
        }

        $updateId = $update['update_id'] ?? 0;

        // Idempotência
        $existe = $this->db->fetch(
            "SELECT id FROM telegram_updates WHERE update_id = ?",
            [$updateId]
        );
        if ($existe) {
            http_response_code(200);
            exit;
        }

        $message = $update['message'] ?? $update['edited_message'] ?? null;
        if (!$message) {
            http_response_code(200);
            exit;
        }

        $chatId  = (string)($message['chat']['id'] ?? '');
        $texto   = trim($message['text'] ?? '');

        $this->db->execute(
            "INSERT INTO telegram_updates (update_id, chat_id, mensagem) VALUES (?,?,?)",
            [$updateId, $chatId, $texto]
        );

        $this->processarMensagem($chatId, $texto);

        $this->db->execute(
            "UPDATE telegram_updates SET processado=1 WHERE update_id=?",
            [$updateId]
        );

        http_response_code(200);
        echo 'ok';
    }

    private function processarMensagem(string $chatId, string $texto): void
    {
        $partes   = explode(' ', $texto, 2);
        $comando  = strtolower($partes[0] ?? '');
        $args     = trim($partes[1] ?? '');

        if (str_starts_with($comando, '/start')) {
            $this->cmdStart($chatId, $args);
            return;
        }

        $user = $this->userModel->findByTelegramChatId($chatId);
        if (!$user) {
            $this->telegram->enviar($chatId,
                "⚠️ <b>Conta não vinculada.</b>\n\nAcesse o sistema web, vá em <b>Perfil</b> e clique em <i>Vincular Telegram</i> para obter seu token de vinculação.\n\nDepois envie: <code>/start SEU_TOKEN</code>");
            return;
        }

        match ($comando) {
            '/ordens'    => $this->cmdOrdens($chatId, $user),
            '/ver'       => $this->cmdVer($chatId, $user, $args),
            '/status'    => $this->cmdVer($chatId, $user, $args),
            '/nova'      => $this->cmdNova($chatId, $user, $args),
            '/finalizar' => $this->cmdFinalizar($chatId, $user, $args),
            '/ajuda'     => $this->cmdAjuda($chatId),
            default      => $this->telegram->enviar($chatId, "❓ Comando não reconhecido. Use /ajuda para ver os comandos disponíveis."),
        };
    }

    private function cmdStart(string $chatId, string $token): void
    {
        if (empty($token)) {
            $this->telegram->enviar($chatId,
                "👋 <b>OS Manager</b>\n\nPara vincular sua conta, acesse o sistema web, vá em Perfil e clique em <i>Vincular Telegram</i>.");
            return;
        }

        $user = $this->userModel->findByTelegramToken(trim($token));
        if (!$user) {
            $this->telegram->enviar($chatId, "❌ Token inválido ou expirado. Gere um novo token no sistema.");
            return;
        }

        $this->userModel->vincularTelegram($user['id'], $chatId);
        $this->telegram->enviar($chatId,
            "✅ <b>Conta vinculada com sucesso!</b>\n\nOlá, <b>{$user['nome']}</b>!\n\nUse /ajuda para ver os comandos disponíveis.");
    }

    private function cmdOrdens(string $chatId, array $user): void
    {
        $ordens = $this->ordemModel->abertas($user['id']);
        if (empty($ordens)) {
            $this->telegram->enviar($chatId, "✅ Você não tem ordens abertas no momento.");
            return;
        }

        $msg = "📋 <b>Suas ordens abertas:</b>\n\n";
        foreach ($ordens as $o) {
            $pct    = SLAHelper::percentual($o['data_abertura'], $o['sla_prazo']);
            $label  = SLAHelper::label($pct, $o['status']);
            $msg   .= "• <b>{$o['numero']}</b> — {$o['titulo']}\n";
            $msg   .= "  Status: {$o['status']} | SLA: {$label} ({$pct}%)\n";
            $msg   .= "  Tempo aberto: " . SLAHelper::tempoAberto($o['data_abertura']) . "\n\n";
        }
        $msg .= "\nUse /ver OS-YYYY-NNNN para detalhes.";
        $this->telegram->enviar($chatId, $msg);
    }

    private function cmdVer(string $chatId, array $user, string $numero): void
    {
        if (empty($numero)) {
            $this->telegram->enviar($chatId, "ℹ️ Informe o número: /ver OS-2026-0001");
            return;
        }

        $ordem = $this->ordemModel->findByNumero(strtoupper($numero));
        if (!$ordem) {
            $this->telegram->enviar($chatId, "❌ OS <b>{$numero}</b> não encontrada.");
            return;
        }

        $pct = SLAHelper::percentual($ordem['data_abertura'], $ordem['sla_prazo']);
        $msg = "📄 <b>{$ordem['numero']}</b>\n";
        $msg .= "<b>Título:</b> {$ordem['titulo']}\n";
        $msg .= "<b>Status:</b> {$ordem['status']}\n";
        $msg .= "<b>Prioridade:</b> {$ordem['prioridade_nome']}\n";
        $msg .= "<b>Categoria:</b> {$ordem['categoria_nome']}\n";
        $msg .= "<b>SLA:</b> " . SLAHelper::label($pct, $ordem['status']) . " ({$pct}%)\n";
        $msg .= "<b>Tempo restante:</b> " . SLAHelper::tempoRestante($ordem['sla_prazo'], $ordem['status']) . "\n";
        $msg .= "<b>Aberta há:</b> " . SLAHelper::tempoAberto($ordem['data_abertura']);

        $this->telegram->enviar($chatId, $msg);
    }

    private function cmdNova(string $chatId, array $user, string $args): void
    {
        if (empty($args)) {
            $cats    = $this->categoriaModel->allAtivas();
            $catList = implode(', ', array_column($cats, 'nome'));
            $this->telegram->enviar($chatId,
                "➕ <b>Criar nova OS</b>\n\nFormato (cliente obrigatório):\n<code>/nova Título | Descrição | Categoria | Cliente</code>\n\n<i>Cliente:</i> trecho do nome, fantasia ou documento cadastrado no sistema.\n\nCategorias: {$catList}\n\nExemplo:\n<code>/nova PC não liga | Computador da recepção não liga | Suporte | Marcelo</code>");
            return;
        }

        $partes = array_map('trim', explode('|', $args, 4));
        if (count($partes) < 4) {
            $this->telegram->enviar($chatId, "❌ Formato incompleto. Use:\n<code>/nova Título | Descrição | Categoria | Cliente</code>");
            return;
        }

        $titulo      = $partes[0];
        $descricao   = $partes[1];
        $catNome     = $partes[2];
        $clienteNome = $partes[3];

        if ($titulo === '' || $descricao === '' || $catNome === '' || $clienteNome === '') {
            $this->telegram->enviar($chatId, "❌ Título, descrição, categoria e cliente não podem ficar vazios.");
            return;
        }

        $categoria = $this->db->fetch(
            "SELECT * FROM categorias_os WHERE nome LIKE ? AND ativo = 1 LIMIT 1",
            ["%{$catNome}%"]
        );
        $categoriaId = $categoria ? (int)$categoria['id'] : 1;

        $likeCliente = '%' . $clienteNome . '%';
        $ehAdmin     = ($user['perfil'] ?? '') === 'admin';
        if ($ehAdmin) {
            $clienteRow = $this->db->fetch(
                "SELECT id FROM clientes WHERE ativo = 1 AND (nome_razao_social LIKE ? OR nome_fantasia LIKE ? OR documento LIKE ?) ORDER BY id ASC LIMIT 1",
                [$likeCliente, $likeCliente, $likeCliente]
            );
        } else {
            $clienteRow = $this->db->fetch(
                "SELECT id FROM clientes WHERE ativo = 1 AND usuario_cadastro_id = ?
                 AND (nome_razao_social LIKE ? OR nome_fantasia LIKE ? OR documento LIKE ?)
                 ORDER BY id ASC LIMIT 1",
                [(int)$user['id'], $likeCliente, $likeCliente, $likeCliente]
            );
        }
        if (!$clienteRow) {
            $this->telegram->enviar($chatId, "❌ Cliente não encontrado. Cadastre em Clientes no sistema e use um trecho do nome, fantasia ou documento.");
            return;
        }
        $clienteId = (int)$clienteRow['id'];

        if (!$ehAdmin && !LimiteConta::podeCriarOsNoMes((int)$user['id'])) {
            $this->telegram->enviar($chatId, '❌ ' . LimiteConta::mensagemLimiteOsMes((int)$user['id']));
            return;
        }

        $prioridadeId = $this->prioridadeModel->idPadraoTelegram();

        $cat      = $this->categoriaModel->find($categoriaId);
        $prio     = $this->prioridadeModel->find($prioridadeId);
        $slaHoras = $cat['sla_horas'] ?? 24;
        $mult     = $prio['sla_multiplicador'] ?? 1.0;
        $numero   = $this->ordemModel->gerarNumero();
        $slaPrazo = SLAHelper::calcPrazo(date('Y-m-d H:i:s'), $slaHoras, $mult);

        $ordemId = (int)$this->ordemModel->create([
            'numero'               => $numero,
            'titulo'               => $titulo,
            'descricao'            => $descricao,
            'status'               => 'aberta',
            'prioridade_id'        => $prioridadeId,
            'categoria_id'         => $categoriaId,
            'usuario_criador_id'   => $user['id'],
            'cliente_id'           => $clienteId,
            'sla_prazo'            => $slaPrazo,
            'sla_horas_previstas'  => $slaHoras * $mult,
            'origem'               => 'telegram',
        ]);

        $this->historicoModel->registrar($ordemId, $user['id'], 'criacao', "OS {$numero} criada via Telegram.");
        $this->telegram->enviar($chatId,
            "✅ <b>OS criada: {$numero}</b>\n{$titulo}\nSLA: " . SLAHelper::tempoRestante($slaPrazo, 'aberta'));
    }

    private function cmdFinalizar(string $chatId, array $user, string $numero): void
    {
        if (empty($numero)) {
            $this->telegram->enviar($chatId, "ℹ️ Informe o número: /finalizar OS-2026-0001");
            return;
        }

        $ordem = $this->ordemModel->findByNumero(strtoupper($numero));
        if (!$ordem) {
            $this->telegram->enviar($chatId, "❌ OS <b>{$numero}</b> não encontrada.");
            return;
        }

        if (!in_array($user['perfil'], ['admin', 'tecnico'])) {
            if ($ordem['usuario_criador_id'] != $user['id']) {
                $this->telegram->enviar($chatId, "🚫 Sem permissão para finalizar esta OS.");
                return;
            }
        }

        if (in_array($ordem['status'], ['finalizada', 'cancelada'])) {
            $this->telegram->enviar($chatId, "ℹ️ OS {$numero} já está {$ordem['status']}.");
            return;
        }

        $this->ordemModel->update($ordem['id'], [
            'status'           => 'finalizada',
            'data_finalizacao' => date('Y-m-d H:i:s'),
        ]);
        $this->historicoModel->registrar($ordem['id'], $user['id'], 'finalizacao',
            "OS {$numero} finalizada via Telegram.");
        $this->telegram->enviar($chatId, "✅ <b>OS {$numero} finalizada com sucesso!</b>");
    }

    private function cmdAjuda(string $chatId): void
    {
        $this->telegram->enviar($chatId,
            "📖 <b>Comandos disponíveis:</b>\n\n" .
            "/ordens — Lista suas ordens abertas\n" .
            "/ver <i>OS-YYYY-NNNN</i> — Detalhes de uma OS\n" .
            "/nova <i>Título | Desc | Categoria</i> — Cria nova OS\n" .
            "/finalizar <i>OS-YYYY-NNNN</i> — Finaliza uma OS\n" .
            "/ajuda — Este menu\n\n" .
            "💡 <i>Dica: Vincule sua conta em Perfil no sistema web.</i>"
        );
    }
}
