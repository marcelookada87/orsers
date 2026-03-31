<?php
/**
 * Front Controller — OS Manager
 */

require_once __DIR__ . '/@config/config.php';

Auth::start();

$router = new Router();

// ----------------------------------------------------------------
// Auth
// ----------------------------------------------------------------
$router->get('/login',  [AuthController::class, 'loginForm']);
$router->post('/login', [AuthController::class, 'loginPost']);
$router->any('/logout', [AuthController::class, 'logout']);

// ----------------------------------------------------------------
// Dashboard
// ----------------------------------------------------------------
$router->get('/',          [DashboardController::class, 'index']);
$router->get('/dashboard', [DashboardController::class, 'index']);

// ----------------------------------------------------------------
// Clientes
// ----------------------------------------------------------------
$router->get('/clientes', [ClienteController::class, 'index']);
$router->get('/clientes/criar', [ClienteController::class, 'create']);
$router->post('/clientes', [ClienteController::class, 'store']);
$router->post('/clientes/rapido', [ClienteController::class, 'criarRapido']);
$router->get('/clientes/{id}/editar', [ClienteController::class, 'edit']);
$router->post('/clientes/{id}/atualizar', [ClienteController::class, 'update']);

// ----------------------------------------------------------------
// Ordens de Serviço
// ----------------------------------------------------------------
$router->get('/ordens',                                    [OrdemController::class, 'index']);
$router->get('/ordens/criar',                              [OrdemController::class, 'create']);
$router->post('/ordens',                                   [OrdemController::class, 'store']);
$router->get('/ordens/{id}',                               [OrdemController::class, 'view']);
$router->get('/ordens/{id}/editar',                        [OrdemController::class, 'edit']);
$router->post('/ordens/{id}/atualizar',                    [OrdemController::class, 'update']);
$router->post('/ordens/{id}/finalizar-rapido',             [OrdemController::class, 'finalizarRapido']);
$router->post('/ordens/{id}/reabrir',                      [OrdemController::class, 'reabrir']);
$router->post('/ordens/{id}/comentar',                     [OrdemController::class, 'comentar']);
$router->get('/ordens/{id}/imagens',                       [OrdemController::class, 'imagensViaGet']);
$router->post('/ordens/{id}/imagens',                      [OrdemController::class, 'uploadImagens']);
$router->post('/ordens/{ordemId}/imagem/{imagemId}/deletar', [OrdemController::class, 'deletarImagem']);

// ----------------------------------------------------------------
// SLA — cadastros (rotas específicas antes do painel /sla)
// ----------------------------------------------------------------
$router->get('/sla/cadastros', [SlaCadastroController::class, 'index']);
$router->get('/sla/categorias/criar', [SlaCadastroController::class, 'categoriasCriar']);
$router->post('/sla/categorias', [SlaCadastroController::class, 'categoriasSalvar']);
$router->get('/sla/categorias/{id}/editar', [SlaCadastroController::class, 'categoriasEditar']);
$router->post('/sla/categorias/{id}/atualizar', [SlaCadastroController::class, 'categoriasAtualizar']);
$router->get('/sla/prioridades/criar', [SlaCadastroController::class, 'prioridadesCriar']);
$router->post('/sla/prioridades', [SlaCadastroController::class, 'prioridadesSalvar']);
$router->get('/sla/prioridades/{id}/editar', [SlaCadastroController::class, 'prioridadesEditar']);
$router->post('/sla/prioridades/{id}/atualizar', [SlaCadastroController::class, 'prioridadesAtualizar']);

$router->post('/sla/ordem/{id}/resetar-sla', [SLAController::class, 'resetarSla']);
$router->post('/sla/ordem/{id}/concluir',    [SLAController::class, 'concluirOrdem']);
$router->get('/api/sla/ordem/{id}/ver',       [SLAController::class, 'apiOrdemVer']);
$router->get('/api/sla/ordem/{id}/comentarios', [SLAController::class, 'apiOrdemComentarios']);
$router->get('/sla/ordem/{id}/modal-editar',  [SLAController::class, 'modalEditarForm']);

$router->get('/sla',     [SLAController::class, 'painel']);
$router->get('/api/sla', [SLAController::class, 'apiSla']);

// ----------------------------------------------------------------
// Estoque (técnico + APIs — rotas específicas primeiro)
// ----------------------------------------------------------------
$router->get('/api/estoque/os/{id}/itens', [EstoqueController::class, 'apiItensOs']);
$router->get('/api/estoque/saldo', [EstoqueController::class, 'apiSaldo']);
$router->post('/estoque/os/{id}/item/{osItemId}/remover', [EstoqueController::class, 'removerItemOs']);
$router->post('/estoque/os/{id}/item', [EstoqueController::class, 'adicionarItemOs']);
$router->post('/estoque/minimo', [EstoqueController::class, 'atualizarMinimo']);
$router->get('/estoque/categorias', [EstoqueController::class, 'categoriasIndex']);
$router->post('/estoque/categorias/criar', [EstoqueController::class, 'categoriasCriar']);
$router->post('/estoque/categorias/{id}/excluir', [EstoqueController::class, 'categoriasExcluir']);
$router->post('/estoque/categorias/{id}/atualizar', [EstoqueController::class, 'categoriasAtualizar']);
$router->get('/estoque/categorias/{id}/editar', [EstoqueController::class, 'categoriasEditar']);
$router->post('/estoque/catalogo/criar', [EstoqueController::class, 'catalogoCriar']);
$router->post('/estoque/catalogo/{id}/toggle', [EstoqueController::class, 'catalogoToggle']);
$router->post('/estoque/catalogo/{id}/atualizar', [EstoqueController::class, 'catalogoAtualizar']);
$router->get('/estoque/catalogo/{id}/editar', [EstoqueController::class, 'catalogoEditar']);
$router->get('/estoque/configuracao-tecnico', [EstoqueController::class, 'redirectConfigTecnicoLegado']);
$router->post('/estoque/configuracao-tecnico', [ContaConfigController::class, 'catalogoCodigoSalvar']);
$router->get('/conta/configuracao', [ContaConfigController::class, 'index']);
$router->get('/conta/configuracao/catalogo-codigo', [ContaConfigController::class, 'catalogoCodigoForm']);
$router->post('/conta/configuracao/catalogo-codigo', [ContaConfigController::class, 'catalogoCodigoSalvar']);
$router->get('/estoque/catalogo', [EstoqueController::class, 'catalogoGerir']);
$router->get('/estoque/relatorio', [EstoqueController::class, 'relatorioConsumo']);
$router->get('/estoque/historico', [EstoqueController::class, 'historico']);
$router->get('/estoque/entrada', [EstoqueController::class, 'entradaForm']);
$router->post('/estoque/entrada', [EstoqueController::class, 'entradaSalvar']);
$router->get('/estoque', [EstoqueController::class, 'index']);

// ----------------------------------------------------------------
// Admin — estoque: só permissões por usuário (catálogo e OS ficam com o técnico)
// ----------------------------------------------------------------
$router->post('/admin/estoque/usuarios/{id}/acesso', [EstoqueAdminController::class, 'usuariosAcesso']);
$router->get('/admin/estoque/usuarios', [EstoqueAdminController::class, 'usuarios']);
$router->get('/admin/estoque', [EstoqueAdminController::class, 'index']);

// ----------------------------------------------------------------
// Usuários
// ----------------------------------------------------------------
$router->get('/usuarios',        [UserController::class, 'index']);
$router->get('/usuarios/criar',  [UserController::class, 'create']);
$router->post('/usuarios',       [UserController::class, 'store']);
$router->get('/usuarios/{id}/editar', [UserController::class, 'edit']);
$router->post('/usuarios/{id}/atualizar', [UserController::class, 'update']);

// ----------------------------------------------------------------
// Administração
// ----------------------------------------------------------------
$router->get('/admin',                    [AdminController::class, 'hub']);
$router->get('/admin/relatorios',         [AdminController::class, 'relatorios']);
$router->get('/admin/planos',            [AdminController::class, 'planosIndex']);
$router->get('/admin/planos/criar',      [AdminController::class, 'planoNovo']);
$router->post('/admin/planos',           [AdminController::class, 'planoCriarPost']);
$router->get('/admin/planos/{id}/editar', [AdminController::class, 'planoEditar']);
$router->post('/admin/planos/{id}/atualizar', [AdminController::class, 'planoAtualizar']);
$router->post('/admin/planos/{id}/excluir',   [AdminController::class, 'planoExcluir']);
$router->get('/admin/configuracoes',  [AdminController::class, 'configuracoes']);
$router->post('/admin/configuracoes', [AdminController::class, 'configuracoesSalvar']);
$router->get('/perfil',          [UserController::class, 'perfil']);
$router->post('/perfil/atualizar', [UserController::class, 'atualizarPerfil']);
$router->post('/perfil/plano',     [UserController::class, 'atualizarPlanoPerfil']);
$router->get('/perfil/telegram-token', [UserController::class, 'gerarTelegramToken']);

// ----------------------------------------------------------------
// Telegram
// ----------------------------------------------------------------
$router->any('/telegram/webhook',    [TelegramWebhookController::class, 'webhook']);
$router->post('/api/cron/telegram',  [TelegramCronController::class, 'processar']);
$router->get('/api/cron/telegram/ping', [TelegramCronController::class, 'ping']);

// ----------------------------------------------------------------
// Dispatch
// ----------------------------------------------------------------
$router->dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);
