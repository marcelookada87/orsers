<?php
class ClienteController extends Controller
{
    private Cliente $clienteModel;

    public function __construct()
    {
        parent::__construct();
        $this->clienteModel = new Cliente();
    }

    public function index(): void
    {
        Auth::requirePerfilTecnico();

        $filtros = [];
        if (isset($_GET['ativo']) && $_GET['ativo'] !== '') {
            $filtros['ativo'] = $_GET['ativo'];
        }
        if (!empty($_GET['busca'])) {
            $filtros['busca'] = trim($_GET['busca']);
        }

        $page   = max(1, (int)$this->get('pagina', 1));
        $limit  = 20;
        $offset = ($page - 1) * $limit;

        $clientes = $this->clienteModel->listarComFiltros($filtros, $limit, $offset);
        $total    = $this->clienteModel->contarComFiltros($filtros);
        $flash    = $this->getFlash();

        $this->render('clientes/index', compact('clientes', 'filtros', 'flash', 'page', 'total', 'limit'));
    }

    public function create(): void
    {
        Auth::requirePerfilTecnico();
        $flash = $this->getFlash();
        $this->render('clientes/create', compact('flash'));
    }

    public function store(): void
    {
        Auth::requirePerfilTecnico();

        $data = $this->normalizeInput();
        if (empty($data['nome_razao_social'])) {
            $this->setFlash('error', 'Nome ou razão social é obrigatório.');
            $this->redirect('/clientes/criar');
        }

        if (!LimiteConta::podeCadastrarCliente((int)Auth::id())) {
            $this->setFlash('error', LimiteConta::mensagemLimiteClientes((int)Auth::id()));
            $this->redirect('/clientes/criar');
        }

        $data['usuario_cadastro_id'] = (int)Auth::id();

        $this->clienteModel->create($data);
        $this->setFlash('success', 'Cliente cadastrado com sucesso!');
        $this->redirect('/clientes');
    }

    public function edit(string $id): void
    {
        Auth::requirePerfilTecnico();
        $cliente = $this->clienteModel->find((int)$id);
        if (!$cliente) {
            $this->abort(404, 'Cliente não encontrado.');
        }
        $flash = $this->getFlash();
        $this->render('clientes/edit', compact('cliente', 'flash'));
    }

    public function update(string $id): void
    {
        Auth::requirePerfilTecnico();
        $cliente = $this->clienteModel->find((int)$id);
        if (!$cliente) {
            $this->abort(404);
        }

        $data = $this->normalizeInput();
        if (empty($data['nome_razao_social'])) {
            $this->setFlash('error', 'Nome ou razão social é obrigatório.');
            $this->redirect('/clientes/' . $id . '/editar');
        }

        $this->clienteModel->update((int)$id, $data);
        $this->setFlash('success', 'Cliente atualizado com sucesso!');
        $this->redirect('/clientes');
    }

    /**
     * Cadastro rápido a partir da tela de OS (JSON).
     */
    public function criarRapido(): void
    {
        Auth::requirePerfilTecnico();
        if (!$this->isPost()) {
            $this->json(['ok' => false, 'error' => 'Método inválido'], 405);
        }

        $data = $this->dadosClienteRapido();
        if ($data['nome_razao_social'] === '') {
            $this->json(['ok' => false, 'error' => 'Informe o nome ou razão social.'], 422);
        }

        if (!LimiteConta::podeCadastrarCliente((int)Auth::id())) {
            $this->json(['ok' => false, 'error' => LimiteConta::mensagemLimiteClientes((int)Auth::id())], 422);
        }

        $data['usuario_cadastro_id'] = (int)Auth::id();

        $id = (int)$this->clienteModel->create($data);
        $row = $this->clienteModel->find($id);
        if (!$row) {
            $this->json(['ok' => false, 'error' => 'Não foi possível salvar o cliente.'], 500);
        }

        $this->json([
            'ok'     => true,
            'cliente'=> [
                'id'    => $id,
                'label' => Cliente::rotuloExibicao($row),
            ],
        ]);
    }

    private function dadosClienteRapido(): array
    {
        $empty = fn(?string $v) => ($v === null || trim($v) === '') ? null : trim($v);

        return [
            'nome_razao_social' => trim($this->post('nome_razao_social', '')),
            'nome_fantasia'     => $empty($this->post('nome_fantasia', '')),
            'tipo_pessoa'       => in_array($this->post('tipo_pessoa', 'juridica'), ['fisica', 'juridica'], true)
                ? $this->post('tipo_pessoa', 'juridica') : 'juridica',
            'documento'         => $empty($this->post('documento', '')),
            'email'             => $empty($this->post('email', '')),
            'telefone'          => $empty($this->post('telefone', '')),
            'celular'           => $empty($this->post('celular', '')),
            'cep'               => null,
            'logradouro'        => null,
            'numero'            => null,
            'complemento'       => null,
            'bairro'            => null,
            'cidade'            => null,
            'uf'                => null,
            'observacoes'       => null,
            'ativo'             => 1,
        ];
    }

    private function normalizeInput(): array
    {
        $empty = fn(string $v) => trim($v) === '' ? null : trim($v);

        return [
            'nome_razao_social' => trim($this->post('nome_razao_social', '')),
            'nome_fantasia'     => $empty($this->post('nome_fantasia', '')),
            'tipo_pessoa'       => in_array($this->post('tipo_pessoa', 'juridica'), ['fisica', 'juridica'], true)
                ? $this->post('tipo_pessoa', 'juridica') : 'juridica',
            'documento'         => $empty($this->post('documento', '')),
            'email'             => $empty($this->post('email', '')),
            'telefone'          => $empty($this->post('telefone', '')),
            'celular'           => $empty($this->post('celular', '')),
            'cep'               => $empty($this->post('cep', '')),
            'logradouro'        => $empty($this->post('logradouro', '')),
            'numero'            => $empty($this->post('numero', '')),
            'complemento'       => $empty($this->post('complemento', '')),
            'bairro'            => $empty($this->post('bairro', '')),
            'cidade'            => $empty($this->post('cidade', '')),
            'uf'                => $empty($this->post('uf', '')),
            'observacoes'       => $empty($this->post('observacoes', '')),
            'ativo'             => isset($_POST['ativo']) ? 1 : 0,
        ];
    }
}
