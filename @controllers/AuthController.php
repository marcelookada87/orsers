<?php
class AuthController extends Controller
{
    private User $userModel;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new User();
    }

    public function loginForm(): void
    {
        if (Auth::check()) {
            $this->redirect('/dashboard');
        }
        $flash = $this->getFlash();
        $this->render('auth/login', ['flash' => $flash], null);
    }

    public function loginPost(): void
    {
        $email = trim($this->post('email', ''));
        $senha = $this->post('senha', '');

        if (empty($email) || empty($senha)) {
            $this->setFlash('error', 'Preencha e-mail e senha.');
            $this->redirect('/login');
        }

        $user = $this->userModel->findByEmail($email);
        if (!$user || !$this->userModel->verificarSenha($senha, $user['senha'])) {
            $this->setFlash('error', 'E-mail ou senha incorretos.');
            $this->redirect('/login');
        }

        $manter = isset($_POST['remember']) && $_POST['remember'] === '1';
        $sessao = $this->userModel->findWithPlano((int)$user['id']) ?: $user;
        Auth::login($sessao, $manter);
        $this->userModel->updateUltimoAcesso($user['id']);
        $this->redirect('/dashboard');
    }

    public function logout(): void
    {
        Auth::logout();
        $this->redirect('/login');
    }
}
