<?php
class UserController extends Controller
{
    private User $userModel;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new User();
    }

    public function index(): void
    {
        Auth::requireAdmin();
        $users = $this->userModel->allAtivos();
        $resumo = [];
        foreach ($users as $u) {
            $uid = (int)$u['id'];
            $resumo[$uid] = [
                'clientes' => (new Cliente())->contarAtivosPorUsuarioCadastro($uid),
                'os_mes'   => (new Ordem())->contarCriadasNoMesPorUsuario($uid),
                'max_cli'  => LimiteConta::maxClientes($uid),
                'max_os'   => LimiteConta::maxOsMes($uid),
                'max_img'  => LimiteConta::maxImagensPorOs($uid),
            ];
        }
        $flash = $this->getFlash();
        $this->render('users/index', compact('users', 'resumo', 'flash'));
    }

    public function create(): void
    {
        Auth::requireAdmin();
        $flash  = $this->getFlash();
        $planos = (new Plano())->listarParaSelecaoUsuario();
        $this->render('users/create', compact('flash', 'planos'));
    }

    public function store(): void
    {
        Auth::requireAdmin();

        $nome   = trim($this->post('nome', ''));
        $email  = trim($this->post('email', ''));
        $senha  = $this->post('senha', '');
        $perfil = $this->post('perfil', 'cliente');

        if (!$nome || !$email || !$senha) {
            $this->setFlash('error', 'Preencha todos os campos.');
            $this->redirect('/usuarios/criar');
        }

        if ($this->userModel->findByEmail($email)) {
            $this->setFlash('error', 'E-mail já cadastrado.');
            $this->redirect('/usuarios/criar');
        }

        $planoModel = new Plano();
        $admin      = ($perfil === 'admin') ? 1 : 0;
        $planoId    = (int)$this->post('plano_id', 0);
        if ($admin) {
            $pIl = $planoModel->findByCodigo('ilimitado');
            $planoId = $pIl ? (int)$pIl['id'] : 2;
        } else {
            $pRow = $planoId > 0 ? $planoModel->find($planoId) : false;
            if (!$pRow || (string)($pRow['codigo'] ?? '') === 'ilimitado') {
                $planoId = $planoModel->idPadraoFree();
            }
        }

        $parseOvr = static function (string $raw): ?int {
            $raw = trim($raw);
            if ($raw === '') {
                return null;
            }
            $n = (int)$raw;
            return $n >= 0 ? $n : null;
        };

        $oImg = $admin ? null : $parseOvr($this->post('max_imagens_por_os_override', ''));
        $oCli = $admin ? null : $parseOvr($this->post('max_clientes_override', ''));
        $oOs  = $admin ? null : $parseOvr($this->post('max_os_mes_override', ''));

        $eLimRaw = trim($this->post('estoque_limite_itens', ''));
        $eLim    = $eLimRaw === '' ? null : max(0, (int)$eLimRaw);

        $this->userModel->create([
            'nome'                         => $nome,
            'email'                        => $email,
            'senha'                        => $this->userModel->hashSenha($senha),
            'perfil'                       => $perfil,
            'admin'                        => $admin,
            'plano_id'                     => $planoId,
            'max_imagens_por_os_override'  => $oImg,
            'max_clientes_override'        => $oCli,
            'max_os_mes_override'          => $oOs,
            'estoque_ativo'                => isset($_POST['estoque_ativo']) ? 1 : 0,
            'estoque_limite_itens'         => $eLim,
        ]);

        $this->setFlash('success', 'Usuário criado com sucesso!');
        $this->redirect('/usuarios');
    }

    public function edit(string $id): void
    {
        Auth::requireAdmin();
        $user = $this->userModel->findWithPlano((int)$id);
        if (!$user) {
            $this->abort(404, 'Usuário não encontrado.');
        }
        $planos = (new Plano())->listarParaSelecaoUsuario();
        if ((int)($user['plano_id'] ?? 0) > 0) {
            $plIl = (new Plano())->find((int)$user['plano_id']);
            if ($plIl && (string)($plIl['codigo'] ?? '') === 'ilimitado') {
                $planos[] = $plIl;
            }
        }
        $flash  = $this->getFlash();
        $this->render('users/edit', compact('user', 'planos', 'flash'));
    }

    public function update(string $id): void
    {
        Auth::requireAdmin();
        $uid = (int)$id;
        $ex  = $this->userModel->find($uid);
        if (!$ex) {
            $this->abort(404);
        }

        $nome   = trim($this->post('nome', ''));
        $email  = trim($this->post('email', ''));
        $perfil = $this->post('perfil', 'cliente');
        $senha  = $this->post('senha', '');

        if (!$nome || !$email) {
            $this->setFlash('error', 'Nome e e-mail são obrigatórios.');
            $this->redirect('/usuarios/' . $uid . '/editar');
        }

        $outro = $this->userModel->findByEmail($email);
        if ($outro && (int)$outro['id'] !== $uid) {
            $this->setFlash('error', 'E-mail já cadastrado.');
            $this->redirect('/usuarios/' . $uid . '/editar');
        }

        $planoModel = new Plano();
        $admin      = ($perfil === 'admin') ? 1 : 0;
        $planoId    = (int)$this->post('plano_id', 0);
        if ($admin) {
            $pIl = $planoModel->findByCodigo('ilimitado');
            $planoId = $pIl ? (int)$pIl['id'] : 2;
        } else {
            $pRow = $planoId > 0 ? $planoModel->find($planoId) : false;
            if (!$pRow || (string)($pRow['codigo'] ?? '') === 'ilimitado') {
                $planoId = $planoModel->idPadraoFree();
            }
        }

        $parseOvr = static function (string $raw): ?int {
            $raw = trim($raw);
            if ($raw === '') {
                return null;
            }
            $n = (int)$raw;
            return $n >= 0 ? $n : null;
        };

        $oImg = $admin ? null : $parseOvr($this->post('max_imagens_por_os_override', ''));
        $oCli = $admin ? null : $parseOvr($this->post('max_clientes_override', ''));
        $oOs  = $admin ? null : $parseOvr($this->post('max_os_mes_override', ''));

        $eLimRaw = trim($this->post('estoque_limite_itens', ''));
        $eLim    = $eLimRaw === '' ? null : max(0, (int)$eLimRaw);

        $data = [
            'nome'                        => $nome,
            'email'                       => $email,
            'perfil'                      => $perfil,
            'admin'                       => $admin,
            'plano_id'                    => $planoId,
            'max_imagens_por_os_override' => $oImg,
            'max_clientes_override'       => $oCli,
            'max_os_mes_override'         => $oOs,
            'estoque_ativo'               => isset($_POST['estoque_ativo']) ? 1 : 0,
            'estoque_limite_itens'        => $eLim,
        ];
        if ($senha !== '') {
            $data['senha'] = $this->userModel->hashSenha($senha);
        }

        $this->userModel->update($uid, $data);
        $this->setFlash('success', 'Usuário atualizado.');
        $this->redirect('/usuarios');
    }

    public function perfil(): void
    {
        Auth::requireLogin();
        $uid = Auth::id();
        $user = $this->userModel->findWithPlano((int)$uid) ?: $this->userModel->find((int)$uid);
        $flash  = $this->getFlash();
        $planos = [];
        if (Auth::isPerfilTecnico()) {
            $planoModel = new Plano();
            $planos = $planoModel->listarParaSelecaoUsuario();
            $idsPlanos = array_map(static fn ($p) => (int)$p['id'], $planos);
            if ($user && (int)($user['plano_id'] ?? 0) > 0) {
                $plAtual = $planoModel->find((int)$user['plano_id']);
                if ($plAtual && !in_array((int)$plAtual['id'], $idsPlanos, true)) {
                    $planos[] = $plAtual;
                }
            }
        }
        $this->render('users/perfil', compact('user', 'flash', 'planos'));
    }

    /**
     * Técnico altera o próprio plano (planos comerciais; não permite escolher ilimitado salvo já possuir).
     */
    public function atualizarPlanoPerfil(): void
    {
        Auth::requireLogin();
        if (!Auth::isPerfilTecnico()) {
            $this->redirect('/perfil');
        }
        $uid = (int)Auth::id();
        $planoId = (int)$this->post('plano_id', 0);
        $planoModel = new Plano();
        $pRow = $planoId > 0 ? $planoModel->find($planoId) : false;
        if (!$pRow || !(int)($pRow['ativo'] ?? 0)) {
            $this->setFlash('error', 'Plano inválido ou indisponível.');
            $this->redirect('/perfil');
        }
        $codigo = (string)($pRow['codigo'] ?? '');
        if ($codigo === 'ilimitado') {
            $atual = $this->userModel->findWithPlano($uid);
            if (!$atual || (string)($atual['plano_codigo'] ?? '') !== 'ilimitado') {
                $this->setFlash('error', 'Este plano não pode ser selecionado.');
                $this->redirect('/perfil');
            }
        }
        $this->userModel->update($uid, ['plano_id' => $planoId]);
        $fresh = $this->userModel->findWithPlano($uid);
        if ($fresh) {
            Auth::refreshPlanoFromUser($fresh);
        }
        $this->setFlash('success', 'Plano atualizado com sucesso.');
        $this->redirect('/perfil');
    }

    public function atualizarPerfil(): void
    {
        Auth::requireLogin();
        $nome     = trim($this->post('nome', ''));
        $telefone = trim($this->post('telefone', ''));
        $senhaAtual = $this->post('senha_atual', '');
        $novaSenha  = $this->post('nova_senha', '');

        $user = $this->userModel->find(Auth::id());
        $data = ['nome' => $nome, 'telefone' => $telefone ?: null];

        if ($senhaAtual && $novaSenha) {
            if (!$this->userModel->verificarSenha($senhaAtual, $user['senha'])) {
                $this->setFlash('error', 'Senha atual incorreta.');
                $this->redirect('/perfil');
            }
            $data['senha'] = $this->userModel->hashSenha($novaSenha);
        }

        $this->userModel->update(Auth::id(), $data);
        $this->setFlash('success', 'Perfil atualizado!');
        $this->redirect('/perfil');
    }

    public function gerarTelegramToken(): void
    {
        Auth::requireLogin();
        $token = $this->userModel->gerarTelegramToken(Auth::id());
        $this->json(['token' => $token, 'comando' => "/start {$token}"]);
    }
}
