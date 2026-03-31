<?php
$rec = $titulosRecentes ?? [];
$pop = $titulosPopulares ?? [];
$pad = $titulosPadrao ?? [];

$seen = [];
foreach ($rec as $t) {
    $k = strtolower(trim((string)$t));
    if ($k !== '') {
        $seen[$k] = true;
    }
}
$popF = [];
foreach ($pop as $t) {
    $k = strtolower(trim((string)$t));
    if ($k === '' || isset($seen[$k])) {
        continue;
    }
    $seen[$k] = true;
    $popF[] = $t;
}
$padF = [];
foreach ($pad as $t) {
    $k = strtolower(trim((string)$t));
    if ($k === '' || isset($seen[$k])) {
        continue;
    }
    $seen[$k] = true;
    $padF[] = $t;
}

$mostrar = count($rec) > 0 || count($popF) > 0 || count($padF) > 0;
if (!$mostrar) {
    return;
}
?>
<div class="titulo-sugestoes-wrap" aria-label="Sugestões de título">
    <?php if (count($rec) > 0): ?>
    <div class="titulo-sugestoes-block">
        <span class="titulo-sugestoes-label">Seus últimos títulos</span>
        <div class="titulo-sugestoes-row">
            <?php foreach ($rec as $t): ?>
            <button type="button" class="titulo-chip js-titulo-sugestao"
                    data-titulo="<?= htmlspecialchars($t, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($t) ?></button>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (count($popF) > 0): ?>
    <div class="titulo-sugestoes-block">
        <span class="titulo-sugestoes-label">Mais usados no sistema</span>
        <div class="titulo-sugestoes-row">
            <?php foreach ($popF as $t): ?>
            <button type="button" class="titulo-chip js-titulo-sugestao"
                    data-titulo="<?= htmlspecialchars($t, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($t) ?></button>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (count($padF) > 0): ?>
    <div class="titulo-sugestoes-block">
        <span class="titulo-sugestoes-label">Sugestões rápidas</span>
        <div class="titulo-sugestoes-row titulo-sugestoes-row--scroll">
            <?php foreach ($padF as $t): ?>
            <button type="button" class="titulo-chip js-titulo-sugestao"
                    data-titulo="<?= htmlspecialchars($t, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($t) ?></button>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>
