<?php

class ContaConfigController extends Controller
{
    private EstoqueItem $itemModel;

    public function __construct()
    {
        parent::__construct();
        $this->itemModel = new EstoqueItem();
    }

    private function requireLoginConta(): void
    {
        Auth::requireLogin();
    }

    /** Hub: demais telas de preferências ficam ligadas a partir daqui. */
    public function index(): void
    {
        $this->requireLoginConta();
        $flash = $this->getFlash();
        $podeCatalogoCodigo = LimiteConta::estoqueAtivo((int)Auth::id()) && Auth::isPerfilTecnico();

        $this->render('conta/configuracao', compact('flash', 'podeCatalogoCodigo'));
    }

    public function catalogoCodigoForm(): void
    {
        $this->requireLoginConta();
        if (!LimiteConta::estoqueAtivo((int)Auth::id())) {
            $this->setFlash('error', 'O módulo de estoque não está ativo para sua conta.');
            $this->redirect('/conta/configuracao');
        }
        Auth::requirePerfilTecnico('/conta/configuracao');

        $flash    = $this->getFlash();
        $cfg      = new UsuarioConfiguracao();
        $uid      = (int)Auth::id();
        $salva    = $cfg->getValorCodigoItemTagOuLegado($uid);
        $tagAtual = ($salva !== null && $salva !== '') ? $salva : 'ITEM';
        $preview  = $this->itemModel->proximoCodigoSugerido($uid);

        $this->render('conta/configuracao_catalogo_codigo', compact('flash', 'tagAtual', 'preview'));
    }

    public function catalogoCodigoSalvar(): void
    {
        $this->requireLoginConta();
        if (!LimiteConta::estoqueAtivo((int)Auth::id())) {
            $this->setFlash('error', 'O módulo de estoque não está ativo para sua conta.');
            $this->redirect('/conta/configuracao');
        }
        Auth::requirePerfilTecnico('/conta/configuracao');

        if (!$this->isPost()) {
            $this->redirect('/conta/configuracao/catalogo-codigo');
        }
        $raw = trim((string)$this->post('catalogo_codigo_item_tag', ''));
        $soValidos = preg_replace('/[^A-Za-z0-9._-]/', '', $raw) ?? '';
        $soValidos = trim($soValidos, '._-');
        $tag = $raw === '' ? 'ITEM' : EstoqueItem::normalizarTagCodigoItem($raw);
        if ($raw !== '' && $soValidos === '') {
            $this->setFlash('error', 'Use letras, números e opcionalmente . _ - na tag. Ex.: PEC, ITEM_, TV-01.');
            $this->redirect('/conta/configuracao/catalogo-codigo');
        }
        (new UsuarioConfiguracao())->setValor((int)Auth::id(), UsuarioConfigChave::CATALOGO_CODIGO_ITEM_TAG, $tag);

        $this->setFlash('success', 'Configuração salva. Novos itens do catálogo usarão esse prefixo na sugestão.');
        $this->redirect('/conta/configuracao/catalogo-codigo');
    }
}
