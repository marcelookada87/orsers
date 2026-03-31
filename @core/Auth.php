<?php
/**
 * Auth — autenticação e controle de sessão
 */
class Auth
{
    private static bool $started = false;

    public static function start(): void
    {
        if (self::$started) return;
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.cookie_httponly', '1');
            ini_set('session.use_strict_mode', '1');
            // Permite sessão longa no servidor quando "manter conectado" (cookie ajustado no login)
            ini_set('session.gc_maxlifetime', (string)SESSION_REMEMBER_LIFETIME);
            session_name(SESSION_NAME);
            $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
            session_set_cookie_params([
                'lifetime' => 0,
                'path'     => '/',
                'secure'   => $secure,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_start();
        }
        self::$started = true;
    }

    /**
     * Renova o cookie de sessão com tempo de vida explícito (segundos a partir de agora).
     */
    private static function setSessionCookieLifetime(int $seconds): void
    {
        $params = session_get_cookie_params();
        $opts = [
            'expires'  => time() + $seconds,
            'path'     => $params['path'] ?: '/',
            'secure'   => !empty($params['secure']),
            'httponly' => true,
            'samesite' => $params['samesite'] ?? 'Lax',
        ];
        if (!empty($params['domain'])) {
            $opts['domain'] = $params['domain'];
        }
        setcookie(session_name(), session_id(), $opts);
    }

    public static function login(array $user, bool $manterConectado = false): void
    {
        self::start();
        session_regenerate_id(true);
        $_SESSION['user_id']     = $user['id'];
        $_SESSION['user_nome']   = $user['nome'];
        $_SESSION['user_email']  = $user['email'];
        $_SESSION['user_perfil'] = $user['perfil'];
        // Só administrador de verdade = perfil `admin` (coluna `admin` no BD não abre o painel sozinha)
        $_SESSION['user_admin']       = (($user['perfil'] ?? '') === 'admin') ? 1 : 0;
        $_SESSION['user_plano_id']    = (int)($user['plano_id'] ?? 1);
        $_SESSION['user_plano_codigo']= (string)($user['plano_codigo'] ?? '');
        $_SESSION['logged_at']        = time();
        $_SESSION['remember_me'] = $manterConectado;

        $duracao = $manterConectado ? SESSION_REMEMBER_LIFETIME : SESSION_LIFETIME;
        $_SESSION['auth_expires_at'] = time() + $duracao;

        self::setSessionCookieLifetime($duracao);
    }

    public static function logout(): void
    {
        self::start();
        $params = session_get_cookie_params();
        $opts = [
            'expires'  => time() - 3600,
            'path'     => $params['path'] ?: '/',
            'secure'   => !empty($params['secure']),
            'httponly' => true,
            'samesite' => $params['samesite'] ?? 'Lax',
        ];
        if (!empty($params['domain'])) {
            $opts['domain'] = $params['domain'];
        }
        setcookie(session_name(), '', $opts);
        $_SESSION = [];
        session_destroy();
        self::$started = false;
    }

    public static function check(): bool
    {
        self::start();
        if (empty($_SESSION['user_id'])) {
            return false;
        }
        $expires = $_SESSION['auth_expires_at'] ?? null;
        if ($expires === null) {
            $expires = ($_SESSION['logged_at'] ?? 0) + SESSION_LIFETIME;
        }
        if (time() > (int)$expires) {
            self::logout();
            return false;
        }
        return true;
    }

    public static function user(): array
    {
        self::start();
        return [
            'id'            => $_SESSION['user_id'] ?? null,
            'nome'          => $_SESSION['user_nome'] ?? '',
            'email'         => $_SESSION['user_email'] ?? '',
            'perfil'        => $_SESSION['user_perfil'] ?? '',
            'admin'         => (int)($_SESSION['user_admin'] ?? 0),
            'plano_id'      => (int)($_SESSION['user_plano_id'] ?? 1),
            'plano_codigo'  => (string)($_SESSION['user_plano_codigo'] ?? ''),
        ];
    }

    /** Atualiza plano na sessão após `findWithPlano()` ou equivalente (chaves `plano_id`, `plano_codigo`). */
    public static function refreshPlanoFromUser(array $user): void
    {
        self::start();
        $_SESSION['user_plano_id'] = (int)($user['plano_id'] ?? 1);
        $_SESSION['user_plano_codigo'] = (string)($user['plano_codigo'] ?? '');
    }

    public static function id(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    public static function perfil(): string
    {
        return $_SESSION['user_perfil'] ?? '';
    }

    /**
     * Administrador da plataforma (painel admin, usuários, planos, relatórios).
     * Baseado exclusivamente em sessão — exige perfil literal "admin".
     */
    public static function isAdmin(): bool
    {
        $p = $_SESSION['user_perfil'] ?? '';
        return is_string($p) && $p === 'admin';
    }

    /** Perfil técnico: cadastro de clientes, políticas de SLA (Gestão), operações de campo. */
    public static function isPerfilTecnico(): bool
    {
        $p = self::perfil();
        return is_string($p) && $p === 'tecnico';
    }

    /** Admin ou técnico: equipe operacional (OS, painel SLA, campos extras em formulários). */
    public static function isTecnico(): bool
    {
        return in_array(self::perfil(), ['admin', 'tecnico'], true);
    }

    public static function requireLogin(string $redirect = '/login'): void
    {
        if (!self::check()) {
            if (strcasecmp($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '', 'XMLHttpRequest') === 0
                && defined('BASE_URL')) {
                header('Content-Type: application/json; charset=utf-8');
                http_response_code(401);
                echo json_encode([
                    'ok'        => false,
                    'error'     => 'Sessão expirada ou não autenticado. Entre de novo.',
                    'redirect'  => BASE_URL . $redirect,
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
            header('Location: ' . BASE_URL . $redirect);
            exit;
        }
    }

    public static function requireAdmin(string $redirect = '/dashboard'): void
    {
        self::requireLogin();
        if (!self::isAdmin()) {
            header('Location: ' . BASE_URL . $redirect);
            exit;
        }
    }

    public static function requireTecnico(string $redirect = '/dashboard'): void
    {
        self::requireLogin();
        if (!self::isTecnico()) {
            header('Location: ' . BASE_URL . $redirect);
            exit;
        }
    }

    /**
     * Rotas de gestão operacional (clientes, cadastro SLA) — somente perfil técnico, não admin.
     */
    public static function requirePerfilTecnico(string $redirect = '/dashboard'): void
    {
        self::requireLogin();
        if (!self::isPerfilTecnico()) {
            header('Location: ' . BASE_URL . $redirect);
            exit;
        }
    }
}
