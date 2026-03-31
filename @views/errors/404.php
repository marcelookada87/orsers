<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>404 — Página não encontrada</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #0f172a; color: #e2e8f0; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
        .box { text-align: center; }
        .code { font-size: 8rem; font-weight: 700; color: #3b82f6; line-height: 1; }
        h1 { font-size: 1.5rem; margin: .5rem 0 1rem; }
        a { color: #3b82f6; text-decoration: none; padding: .5rem 1.5rem; border: 1px solid #3b82f6; border-radius: .5rem; display: inline-block; }
        a:hover { background: #3b82f6; color: #fff; }
    </style>
</head>
<body>
    <div class="box">
        <div class="code">404</div>
        <h1>Página não encontrada</h1>
        <a href="<?= defined('BASE_URL') ? BASE_URL : '/' ?>/dashboard">Voltar ao início</a>
    </div>
</body>
</html>
