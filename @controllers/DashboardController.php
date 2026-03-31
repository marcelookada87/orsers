<?php
class DashboardController extends Controller
{
    private Ordem $ordemModel;

    public function __construct()
    {
        parent::__construct();
        $this->ordemModel = new Ordem();
    }

    public function index(): void
    {
        Auth::requireLogin();
        $user = Auth::user();

        $contadores     = $this->ordemModel->contarPorStatus();
        $finalizadasHoje = $this->ordemModel->finalizadasHoje();
        $slaVencidas    = $this->ordemModel->slaVencidas();
        $recentes       = $this->ordemModel->recentesParaDashboard(10);

        // KPI: ordens abertas do usuário (clientes vêem só as suas)
        $abertas = Auth::isAdmin()
            ? $this->ordemModel->abertas()
            : $this->ordemModel->abertas(Auth::id());

        $flash = $this->getFlash();

        $this->render('dashboard/index', compact(
            'user', 'contadores', 'finalizadasHoje',
            'slaVencidas', 'recentes', 'abertas', 'flash'
        ));
    }
}
