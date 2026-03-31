<?php

class EstoqueAdminController extends Controller
{
    public function index(): void
    {
        Auth::requireAdmin();
        $this->redirect('/admin/estoque/usuarios');
    }

    public function usuarios(): void
    {
        Auth::requireAdmin();
        $flash = $this->getFlash();
        $users = (new User())->allAtivos();

        $this->render('admin/estoque/usuarios', compact('flash', 'users'));
    }

    public function usuariosAcesso(string $id): void
    {
        Auth::requireAdmin();
        $uid = (int)$id;
        $u   = (new User())->find($uid);
        if (!$u) {
            $this->abort(404);
        }

        $ativo  = isset($_POST['estoque_ativo']) ? 1 : 0;
        $limRaw = trim($this->post('estoque_limite_itens', ''));
        $limite = $limRaw === '' ? null : max(0, (int)$limRaw);

        (new User())->update($uid, [
            'estoque_ativo'        => $ativo,
            'estoque_limite_itens' => $limite,
        ]);

        $this->setFlash('success', 'Permissões de estoque atualizadas.');
        $this->redirect('/admin/estoque/usuarios');
    }
}
