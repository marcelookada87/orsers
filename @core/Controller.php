<?php
/**
 * Controller — base para todos os controllers
 */
abstract class Controller
{
    protected Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    protected function render(string $view, array $data = [], ?string $layout = 'default'): void
    {
        extract($data);
        $viewFile = ROOT_PATH . '/@views/' . ltrim($view, '/') . '.php';
        if (!file_exists($viewFile)) {
            http_response_code(500);
            echo "View não encontrada: {$view}";
            return;
        }

        if ($layout === null) {
            require $viewFile;
            return;
        }

        $headerFile = ROOT_PATH . '/@views/layout/header.php';
        $footerFile = ROOT_PATH . '/@views/layout/footer.php';

        if (file_exists($headerFile)) require $headerFile;
        require $viewFile;
        if (file_exists($footerFile)) require $footerFile;
    }

    protected function redirect(string $path): never
    {
        header('Location: ' . BASE_URL . $path);
        exit;
    }

    protected function json(mixed $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    protected function abort(int $code = 404, string $message = 'Não encontrado'): never
    {
        http_response_code($code);
        if ($this->isAjaxRequest()) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => $message], JSON_UNESCAPED_UNICODE);
            exit;
        }
        echo $message;
        exit;
    }

    protected function setFlash(string $type, string $message): void
    {
        Auth::start();
        $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    }

    protected function getFlash(): ?array
    {
        Auth::start();
        if (!empty($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
            return $flash;
        }
        return null;
    }

    protected function isPost(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    protected function isGet(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'GET';
    }

    protected function input(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    protected function post(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $default;
    }

    protected function get(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    /** Requisição feita via fetch / XMLHttpRequest (modais, APIs). */
    protected function isAjaxRequest(): bool
    {
        return strcasecmp($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '', 'XMLHttpRequest') === 0;
    }
}
