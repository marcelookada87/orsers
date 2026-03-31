<?php
/**
 * Configuração principal do sistema OS SAAS
 */

// Versão do banco — ajuste manual conforme patches aplicados
define('DB_VERSION', 1);

// Banco de dados
define('DB_HOST',     'localhost');
define('DB_PORT',     '3306');
define('DB_NAME',     'orsers');
define('DB_USER',     'root');
define('DB_PASS',     '');
define('DB_CHARSET',  'utf8mb4');

// Aplicação
define('APP_NAME',    'OS Manager');
define('APP_VERSION', '1.0.0');
define('APP_ENV',     'development'); // development | production
define('APP_DEBUG',   true);

// Timezone
date_default_timezone_set('America/Sao_Paulo');

// Base URL — detectada automaticamente
$_protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$_host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_script   = dirname($_SERVER['SCRIPT_NAME'] ?? '');
$_base     = rtrim(str_replace('\\', '/', $_script), '/');
define('BASE_URL',  $_protocol . '://' . $_host . $_base);
define('ROOT_PATH', dirname(__DIR__));

// Upload
define('UPLOAD_PATH',    ROOT_PATH . '/uploads/ordens');
define('UPLOAD_URL',     BASE_URL  . '/uploads/ordens');
/** Padrão se a tabela sistema_config não existir ou a chave estiver ausente */
define('MAX_IMAGES_PER_OS_FALLBACK', 5);
define('MAX_IMAGE_SIZE_KB',   400);
define('IMAGE_MAX_DIMENSION', 1280);

// Session
define('SESSION_NAME',     'orsers_sess');
define('SESSION_LIFETIME', 7200); // 2 horas (sem "manter conectado")
/** Duração máxima quando o usuário marca "Manter conectado" (1 ano, em segundos) */
define('SESSION_REMEMBER_LIFETIME', 365 * 24 * 3600);

// Telegram — carregado a seguir
if (file_exists(__DIR__ . '/telegram.php')) {
    require_once __DIR__ . '/telegram.php';
}

// Core
require_once ROOT_PATH . '/@core/Database.php';
require_once ROOT_PATH . '/@core/NavMenu.php';
require_once ROOT_PATH . '/@core/Auth.php';
require_once ROOT_PATH . '/@core/Controller.php';
require_once ROOT_PATH . '/@core/Model.php';
require_once ROOT_PATH . '/@core/Router.php';
require_once ROOT_PATH . '/@core/SLAHelper.php';
require_once ROOT_PATH . '/@core/ImageCompressor.php';
require_once ROOT_PATH . '/@core/TelegramSender.php';

// Models
require_once ROOT_PATH . '/@models/User.php';
require_once ROOT_PATH . '/@models/Cliente.php';
require_once ROOT_PATH . '/@models/CategoriaOS.php';
require_once ROOT_PATH . '/@models/Prioridade.php';
require_once ROOT_PATH . '/@models/Ordem.php';
require_once ROOT_PATH . '/@models/OrdemImagem.php';
require_once ROOT_PATH . '/@models/OrdemHistorico.php';
require_once ROOT_PATH . '/@models/SistemaConfig.php';
require_once ROOT_PATH . '/@models/Plano.php';
require_once ROOT_PATH . '/@models/NavMenuItem.php';
require_once ROOT_PATH . '/@models/EstoqueCategoria.php';
require_once ROOT_PATH . '/@models/EstoqueItem.php';
require_once ROOT_PATH . '/@models/EstoqueMovimentacao.php';
require_once ROOT_PATH . '/@models/EstoqueOsItem.php';
require_once ROOT_PATH . '/@models/EstoqueSaldo.php';
require_once ROOT_PATH . '/@models/UsuarioConfiguracao.php';
require_once ROOT_PATH . '/@core/LimiteConta.php';

/**
 * Limite de imagens por OS (valor configurável no admin; fallback em MAX_IMAGES_PER_OS_FALLBACK).
 */
function max_imagens_por_os(): int
{
    return SistemaConfig::maxImagensPorOs();
}

/** Limite efetivo por criador da OS (plano + overrides, respeitando teto global). */
function max_imagens_por_os_usuario(int $userId): int
{
    return LimiteConta::maxImagensPorOs($userId);
}

// Controllers
require_once ROOT_PATH . '/@controllers/AuthController.php';
require_once ROOT_PATH . '/@controllers/DashboardController.php';
require_once ROOT_PATH . '/@controllers/ClienteController.php';
require_once ROOT_PATH . '/@controllers/OrdemController.php';
require_once ROOT_PATH . '/@controllers/SLAController.php';
require_once ROOT_PATH . '/@controllers/SlaCadastroController.php';
require_once ROOT_PATH . '/@controllers/UserController.php';
require_once ROOT_PATH . '/@controllers/AdminController.php';
require_once ROOT_PATH . '/@controllers/EstoqueController.php';
require_once ROOT_PATH . '/@controllers/EstoqueAdminController.php';
require_once ROOT_PATH . '/@controllers/ContaConfigController.php';
require_once ROOT_PATH . '/@controllers/TelegramWebhookController.php';
require_once ROOT_PATH . '/@controllers/TelegramCronController.php';
