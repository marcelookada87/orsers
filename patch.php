<?php
/**
 * patch.php — Executor de patches do banco de dados
 * Acesso protegido por senha. Nunca expor em produção sem restrição de IP.
 */

session_start();

define('PATCH_PASSWORD', 'OSManager@Patch2026');
define('PATCH_VERSION',  '01');
define('PATCH_DIR',      __DIR__ . '/@config/patch/version_' . PATCH_VERSION);
define('CONFIG_FILE',    __DIR__ . '/@config/config.php');

$isAuthenticated = !empty($_SESSION['patch_authenticated']);
$message         = null;
$results         = [];

// Login / Logout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isAuthenticated) {
    if (($_POST['password'] ?? '') === PATCH_PASSWORD) {
        $_SESSION['patch_authenticated'] = true;
        header('Location: patch.php');
        exit;
    }
    $message = ['type' => 'error', 'text' => 'Senha incorreta.'];
}

if (isset($_GET['logout'])) {
    unset($_SESSION['patch_authenticated']);
    header('Location: patch.php');
    exit;
}

if (!$isAuthenticated) {
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <title>Patch Runner — OS Manager</title>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
        <style>
            body { font-family: 'Inter', sans-serif; background: #0f172a; color: #e2e8f0; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
            .box { background: #1e293b; border: 1px solid #334155; border-radius: 10px; padding: 32px 36px; width: 360px; }
            h1 { font-size: 1.3rem; font-weight: 700; margin-bottom: 20px; }
            label { font-size: .8rem; color: #94a3b8; display: block; margin-bottom: 5px; }
            input { width: 100%; padding: 9px 12px; background: #0f172a; border: 1px solid #334155; color: #f1f5f9; border-radius: 6px; font-size: .87rem; margin-bottom: 14px; box-sizing: border-box; }
            input:focus { outline: none; border-color: #3b82f6; }
            button { width: 100%; padding: 10px; background: #3b82f6; color: #fff; border: none; border-radius: 6px; font-size: .9rem; font-weight: 600; cursor: pointer; }
            .err { background: rgba(239,68,68,.15); border: 1px solid rgba(239,68,68,.3); color: #ef4444; padding: 8px 12px; border-radius: 6px; font-size: .82rem; margin-bottom: 12px; }
        </style>
    </head>
    <body>
        <div class="box">
            <h1>🔧 Patch Runner</h1>
            <?php if ($message): ?>
            <div class="err"><?= htmlspecialchars($message['text']) ?></div>
            <?php endif; ?>
            <form method="POST">
                <label>Senha de acesso</label>
                <input type="password" name="password" autofocus>
                <button type="submit">Entrar</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Carregar banco
if (file_exists(CONFIG_FILE)) {
    define('PATCH_CHECK_MODE', false);
    require_once CONFIG_FILE;
}

// Listar patches
function getPatchFiles(): array
{
    $dir   = PATCH_DIR;
    $files = glob($dir . '/patch_*.php') ?: [];
    sort($files);
    return $files;
}

function getAppliedPatches(): array
{
    try {
        $db   = Database::getInstance();
        $rows = $db->fetchAll("SELECT patch_file, status FROM patches_applied");
        $map  = [];
        foreach ($rows as $r) { $map[$r['patch_file']] = $r['status']; }
        return $map;
    } catch (Exception $e) {
        return [];
    }
}

function applyPatch(string $filePath): array
{
    $filename = basename($filePath);
    ob_start();
    try {
        require $filePath;
        $output = ob_get_clean();
        Database::getInstance()->execute(
            "INSERT INTO patches_applied (patch_file, patch_number, status, output)
             VALUES (?, ?, 'success', ?)
             ON DUPLICATE KEY UPDATE status='success', output=?, applied_at=NOW()",
            [$filename, extractNumber($filename), $output, $output]
        );
        return ['status' => 'success', 'output' => $output];
    } catch (Exception $e) {
        $output = ob_get_clean() . "\nERRO: " . $e->getMessage();
        try {
            Database::getInstance()->execute(
                "INSERT INTO patches_applied (patch_file, patch_number, status, output)
                 VALUES (?, ?, 'error', ?)
                 ON DUPLICATE KEY UPDATE status='error', output=?, applied_at=NOW()",
                [$filename, extractNumber($filename), $output, $output]
            );
        } catch (Exception $ex) {}
        return ['status' => 'error', 'output' => $output];
    }
}

function extractNumber(string $filename): string
{
    preg_match('/(\d+)/', $filename, $m);
    return $m[1] ?? '';
}

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isAuthenticated) {
    $action = $_POST['action'] ?? '';

    if ($action === 'apply_one') {
        $file    = PATCH_DIR . '/' . basename($_POST['patch_file'] ?? '');
        $results = [basename($file) => applyPatch($file)];
    }

    if ($action === 'apply_all') {
        $applied = getAppliedPatches();
        foreach (getPatchFiles() as $file) {
            $name = basename($file);
            if (isset($applied[$name]) && $applied[$name] === 'success') continue;
            $results[$name] = applyPatch($file);
        }
    }
}

$patches = getPatchFiles();
$applied = getAppliedPatches();

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Patch Runner — OS Manager</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,wght@0,400;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background: #0f172a; color: #e2e8f0; }
        .topbar { background: #1e293b; border-bottom: 1px solid #334155; padding: 14px 24px; display: flex; align-items: center; justify-content: space-between; }
        .topbar h1 { font-size: 1rem; font-weight: 700; }
        .topbar a { font-size: .8rem; color: #94a3b8; }
        .container { max-width: 900px; margin: 0 auto; padding: 28px 20px; }
        .btn { display: inline-flex; align-items: center; gap: 6px; padding: 7px 14px; border-radius: 6px; font-size: .82rem; font-weight: 600; cursor: pointer; border: none; }
        .btn-primary { background: #3b82f6; color: #fff; }
        .btn-success { background: #10b981; color: #fff; }
        .btn-sm { padding: 4px 10px; font-size: .76rem; }
        .table { width: 100%; border-collapse: collapse; font-size: .83rem; }
        .table th { background: #1e293b; padding: 10px 14px; text-align: left; font-size: .72rem; text-transform: uppercase; letter-spacing: .06em; color: #64748b; }
        .table td { padding: 10px 14px; border-bottom: 1px solid #1e293b; }
        .table tr:last-child td { border-bottom: none; }
        .badge { padding: 2px 8px; border-radius: 20px; font-size: .7rem; font-weight: 700; }
        .badge-success { background: rgba(16,185,129,.2); color: #10b981; }
        .badge-error   { background: rgba(239,68,68,.2); color: #ef4444; }
        .badge-pending { background: rgba(245,158,11,.15); color: #f59e0b; }
        .result-block { background: #1e293b; border: 1px solid #334155; border-radius: 8px; padding: 16px; margin-bottom: 14px; }
        .result-block h3 { font-size: .85rem; margin-bottom: 8px; }
        .result-block pre { font-family: monospace; font-size: .78rem; color: #94a3b8; white-space: pre-wrap; word-break: break-all; }
        .result-block.success h3 { color: #10b981; }
        .result-block.error   h3 { color: #ef4444; }
        .actions { display: flex; gap: 10px; margin-bottom: 22px; }
        .card { background: #1e293b; border: 1px solid #334155; border-radius: 10px; overflow: hidden; margin-bottom: 22px; }
        .card-header { padding: 12px 16px; border-bottom: 1px solid #334155; font-weight: 600; font-size: .9rem; }
    </style>
</head>
<body>
<div class="topbar">
    <h1>🔧 Patch Runner — OS Manager</h1>
    <a href="?logout=1">Sair</a>
</div>
<div class="container">

    <?php if (!empty($results)): ?>
    <div class="card">
        <div class="card-header">Resultados</div>
        <div style="padding:16px">
            <?php foreach ($results as $name => $result): ?>
            <div class="result-block <?= $result['status'] ?>">
                <h3><?= htmlspecialchars($name) ?> — <?= strtoupper($result['status']) ?></h3>
                <pre><?= htmlspecialchars($result['output']) ?></pre>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="actions">
        <form method="POST">
            <input type="hidden" name="action" value="apply_all">
            <button type="submit" class="btn btn-success"
                    onclick="return confirm('Aplicar todos os patches pendentes?')">
                ✅ Aplicar Pendentes
            </button>
        </form>
    </div>

    <div class="card">
        <div class="card-header">Patches — version_<?= PATCH_VERSION ?></div>
        <table class="table">
            <thead>
                <tr>
                    <th>Arquivo</th>
                    <th>Status</th>
                    <th>Ação</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($patches as $file):
                    $name   = basename($file);
                    $status = $applied[$name] ?? 'pendente';
                ?>
                <tr>
                    <td><code><?= htmlspecialchars($name) ?></code></td>
                    <td>
                        <span class="badge badge-<?= $status === 'success' ? 'success' : ($status === 'error' ? 'error' : 'pending') ?>">
                            <?= ucfirst($status) ?>
                        </span>
                    </td>
                    <td>
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="action" value="apply_one">
                            <input type="hidden" name="patch_file" value="<?= htmlspecialchars($name) ?>">
                            <button type="submit" class="btn btn-primary btn-sm">Aplicar</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($patches)): ?>
                <tr><td colspan="3" style="text-align:center;color:#64748b;padding:30px">Nenhum patch encontrado em version_<?= PATCH_VERSION ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
