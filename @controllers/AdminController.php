<?php
class AdminController extends Controller
{
    public function hub(): void
    {
        Auth::requireAdmin();
        $flash = $this->getFlash();
        $this->render('admin/hub', compact('flash'));
    }

    public function relatorios(): void
    {
        Auth::requireAdmin();
        $linhas     = (new User())->relatorioUsoPlanos();
        $ordemModel = new Ordem();
        $ini        = date('Y-m-01');
        $fim        = date('Y-m-t');
        $fin        = $ordemModel->somaFinanceiroPeriodo($ini, $fim);
        $contadores = $ordemModel->contarPorStatus();
        $flash      = $this->getFlash();
        $this->render('admin/relatorios', compact('linhas', 'fin', 'contadores', 'flash', 'ini', 'fim'));
    }

    public function planosIndex(): void
    {
        Auth::requireAdmin();
        $planoModel = new Plano();
        $planos     = $planoModel->listarTodos();
        $cnt        = [];
        foreach ($planos as $p) {
            $cnt[(int)$p['id']] = $planoModel->countUsuariosPorPlano((int)$p['id']);
        }
        $flash = $this->getFlash();
        $this->render('admin/planos_index', compact('planos', 'cnt', 'flash'));
    }

    public function planoNovo(): void
    {
        Auth::requireAdmin();
        $flash = $this->getFlash();
        $plano = null;
        $this->render('admin/plano_form', compact('flash', 'plano'));
    }

    public function planoCriarPost(): void
    {
        Auth::requireAdmin();
        $codigo = strtolower(trim(preg_replace('/\s+/', '_', $this->post('codigo', ''))));
        $nome   = trim($this->post('nome', ''));
        if ($codigo === '' || !preg_match('/^[a-z0-9_]{2,32}$/', $codigo)) {
            $this->setFlash('error', 'Código inválido (2–32 caracteres: letras minúsculas, números, _).');
            $this->redirect('/admin/planos/criar');
        }
        if ($nome === '') {
            $this->setFlash('error', 'Informe o nome do plano.');
            $this->redirect('/admin/planos/criar');
        }
        $planoModel = new Plano();
        if ($planoModel->findByCodigo($codigo)) {
            $this->setFlash('error', 'Já existe um plano com este código.');
            $this->redirect('/admin/planos/criar');
        }

        $maxImg = $this->nullableIntPost('max_imagens_por_os');
        $maxCli = $this->nullableIntPost('max_clientes');
        $maxOs  = $this->nullableIntPost('max_os_mes');

        $desc = trim($this->post('descricao', ''));
        if (mb_strlen($desc) > 500) {
            $desc = mb_substr($desc, 0, 500);
        }

        $planoModel->create([
            'codigo'               => $codigo,
            'nome'                 => $nome,
            'descricao'            => $desc !== '' ? $desc : null,
            'max_imagens_por_os'   => $maxImg,
            'max_clientes'         => $maxCli,
            'max_os_mes'           => $maxOs,
            'ativo'                => isset($_POST['ativo']) ? 1 : 0,
            'ordem'                => max(0, (int)$this->post('ordem', 0)),
        ]);
        $this->setFlash('success', 'Plano criado.');
        $this->redirect('/admin/planos');
    }

    public function planoEditar(string $id): void
    {
        Auth::requireAdmin();
        $plano = (new Plano())->find((int)$id);
        if (!$plano) {
            $this->abort(404, 'Plano não encontrado.');
        }
        $flash = $this->getFlash();
        $this->render('admin/plano_form', compact('flash', 'plano'));
    }

    public function planoAtualizar(string $id): void
    {
        Auth::requireAdmin();
        $planoModel = new Plano();
        $plano      = $planoModel->find((int)$id);
        if (!$plano) {
            $this->abort(404);
        }
        $nome = trim($this->post('nome', ''));
        if ($nome === '') {
            $this->setFlash('error', 'Informe o nome do plano.');
            $this->redirect('/admin/planos/' . (int)$id . '/editar');
        }

        $maxImg = $this->nullableIntPost('max_imagens_por_os');
        $maxCli = $this->nullableIntPost('max_clientes');
        $maxOs  = $this->nullableIntPost('max_os_mes');

        $desc = trim($this->post('descricao', ''));
        if (mb_strlen($desc) > 500) {
            $desc = mb_substr($desc, 0, 500);
        }

        $planoModel->update((int)$id, [
            'nome'               => $nome,
            'descricao'          => $desc !== '' ? $desc : null,
            'max_imagens_por_os' => $maxImg,
            'max_clientes'       => $maxCli,
            'max_os_mes'         => $maxOs,
            'ativo'              => isset($_POST['ativo']) ? 1 : 0,
            'ordem'              => max(0, (int)$this->post('ordem', 0)),
        ]);
        $this->setFlash('success', 'Plano atualizado.');
        $this->redirect('/admin/planos');
    }

    public function planoExcluir(string $id): void
    {
        Auth::requireAdmin();
        $planoModel = new Plano();
        $plano      = $planoModel->find((int)$id);
        if (!$plano) {
            $this->abort(404);
        }
        $cod = (string)($plano['codigo'] ?? '');
        if (in_array($cod, [
            'free', 'ilimitado', 'basico', 'premium', 'premium_plus', 'ultra', 'ultra_mega',
        ], true)) {
            $this->setFlash('error', 'Planos base do sistema não podem ser excluídos.');
            $this->redirect('/admin/planos');
        }
        if ($planoModel->countUsuariosPorPlano((int)$id) > 0) {
            $this->setFlash('error', 'Não é possível excluir: existem usuários neste plano.');
            $this->redirect('/admin/planos');
        }
        $planoModel->delete((int)$id);
        $this->setFlash('success', 'Plano removido.');
        $this->redirect('/admin/planos');
    }

    public function configuracoes(): void
    {
        Auth::requireAdmin();
        $flash = $this->getFlash();
        $maxImagens = SistemaConfig::maxImagensPorOs();
        $this->render('admin/configuracoes', compact('flash', 'maxImagens'));
    }

    public function configuracoesSalvar(): void
    {
        Auth::requireAdmin();
        $n = (int)$this->post('max_imagens_por_os', 5);
        if ($n < 1 || $n > 50) {
            $this->setFlash('error', 'Limite de imagens por OS deve estar entre 1 e 50.');
            $this->redirect('/admin/configuracoes');
        }
        try {
            SistemaConfig::setMaxImagensPorOs($n);
            $this->setFlash('success', 'Configurações salvas.');
        } catch (Throwable $e) {
            error_log('Admin configuracoesSalvar: ' . $e->getMessage());
            $this->setFlash('error', 'Não foi possível salvar. Execute o patch do banco (sistema_config) se ainda não aplicou.');
        }
        $this->redirect('/admin/configuracoes');
    }

    private function nullableIntPost(string $key): ?int
    {
        $raw = trim($this->post($key, ''));
        if ($raw === '') {
            return null;
        }
        $n = (int)$raw;
        return $n >= 0 ? $n : null;
    }
}
