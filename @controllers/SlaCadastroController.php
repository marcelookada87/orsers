<?php
/**
 * Cadastro de políticas de SLA: categorias (horas base) e prioridades (multiplicador).
 */
class SlaCadastroController extends Controller
{
    private CategoriaOS $categoriaModel;
    private Prioridade $prioridadeModel;

    public function __construct()
    {
        parent::__construct();
        $this->categoriaModel  = new CategoriaOS();
        $this->prioridadeModel = new Prioridade();
    }

    public function index(): void
    {
        Auth::requirePerfilTecnico();
        $categorias  = $this->categoriaModel->listarTodas();
        $prioridades = $this->prioridadeModel->listarTodas();
        $flash       = $this->getFlash();
        $this->render('sla/cadastros/index', compact('categorias', 'prioridades', 'flash'));
    }

    // ---------- Categorias ----------
    public function categoriasCriar(): void
    {
        Auth::requirePerfilTecnico();
        $flash = $this->getFlash();
        $this->render('sla/cadastros/categoria_form', ['flash' => $flash, 'categoria' => null, 'pageTitle' => 'Nova categoria de SLA']);
    }

    public function categoriasSalvar(): void
    {
        Auth::requirePerfilTecnico();
        $data = $this->normalizeCategoria();
        if ($data['nome'] === '') {
            $this->setFlash('error', 'Informe o nome da categoria.');
            $this->redirect('/sla/categorias/criar');
        }
        $this->categoriaModel->create($data);
        $this->setFlash('success', 'Categoria cadastrada.');
        $this->redirect('/sla/cadastros');
    }

    public function categoriasEditar(string $id): void
    {
        Auth::requirePerfilTecnico();
        $categoria = $this->categoriaModel->find((int)$id);
        if (!$categoria) {
            $this->abort(404);
        }
        $flash = $this->getFlash();
        $this->render('sla/cadastros/categoria_form', [
            'flash'     => $flash,
            'categoria' => $categoria,
            'pageTitle' => 'Editar categoria',
        ]);
    }

    public function categoriasAtualizar(string $id): void
    {
        Auth::requirePerfilTecnico();
        $categoria = $this->categoriaModel->find((int)$id);
        if (!$categoria) {
            $this->abort(404);
        }
        $data = $this->normalizeCategoria();
        if ($data['nome'] === '') {
            $this->setFlash('error', 'Informe o nome da categoria.');
            $this->redirect('/sla/categorias/' . $id . '/editar');
        }
        $this->categoriaModel->update((int)$id, $data);
        $this->setFlash('success', 'Categoria atualizada.');
        $this->redirect('/sla/cadastros');
    }

    private function normalizeCategoria(): array
    {
        $cor = trim($this->post('cor', '#3B82F6'));
        if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $cor)) {
            $cor = '#3B82F6';
        }
        $sla = (float)str_replace(',', '.', $this->post('sla_horas', '24'));
        if ($sla < 0.25) {
            $sla = 0.25;
        }
        if ($sla > 8760) {
            $sla = 8760;
        }

        return [
            'nome'        => trim($this->post('nome', '')),
            'descricao'   => trim($this->post('descricao', '')) ?: null,
            'cor'         => $cor,
            'sla_horas'   => $sla,
            'ativo'       => isset($_POST['ativo']) ? 1 : 0,
        ];
    }

    // ---------- Prioridades ----------
    public function prioridadesCriar(): void
    {
        Auth::requirePerfilTecnico();
        $flash = $this->getFlash();
        $this->render('sla/cadastros/prioridade_form', ['flash' => $flash, 'prioridade' => null, 'pageTitle' => 'Nova prioridade de SLA']);
    }

    public function prioridadesSalvar(): void
    {
        Auth::requirePerfilTecnico();
        $data = $this->normalizePrioridade();
        if ($data['nome'] === '') {
            $this->setFlash('error', 'Informe o nome da prioridade.');
            $this->redirect('/sla/prioridades/criar');
        }
        $this->prioridadeModel->create($data);
        $this->setFlash('success', 'Prioridade cadastrada.');
        $this->redirect('/sla/cadastros');
    }

    public function prioridadesEditar(string $id): void
    {
        Auth::requirePerfilTecnico();
        $prioridade = $this->prioridadeModel->find((int)$id);
        if (!$prioridade) {
            $this->abort(404);
        }
        $flash = $this->getFlash();
        $this->render('sla/cadastros/prioridade_form', [
            'flash'      => $flash,
            'prioridade' => $prioridade,
            'pageTitle'  => 'Editar prioridade',
        ]);
    }

    public function prioridadesAtualizar(string $id): void
    {
        Auth::requirePerfilTecnico();
        $prioridade = $this->prioridadeModel->find((int)$id);
        if (!$prioridade) {
            $this->abort(404);
        }
        $data = $this->normalizePrioridade();
        if ($data['nome'] === '') {
            $this->setFlash('error', 'Informe o nome da prioridade.');
            $this->redirect('/sla/prioridades/' . $id . '/editar');
        }
        $this->prioridadeModel->update((int)$id, $data);
        $this->setFlash('success', 'Prioridade atualizada.');
        $this->redirect('/sla/cadastros');
    }

    private function normalizePrioridade(): array
    {
        $cor = trim($this->post('cor', '#6B7280'));
        if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $cor)) {
            $cor = '#6B7280';
        }
        $nivel = (int)$this->post('nivel', 3);
        if ($nivel < 1) {
            $nivel = 1;
        }
        if ($nivel > 9) {
            $nivel = 9;
        }
        $mult = (float)str_replace(',', '.', $this->post('sla_multiplicador', '1'));
        if ($mult < 0.05) {
            $mult = 0.05;
        }
        if ($mult > 10) {
            $mult = 10.0;
        }

        return [
            'nome'              => trim($this->post('nome', '')),
            'nivel'             => $nivel,
            'cor'               => $cor,
            'sla_multiplicador' => $mult,
            'ativo'             => isset($_POST['ativo']) ? 1 : 0,
        ];
    }
}
