<?php $pageTitle = 'Painel administrativo'; ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Painel administrativo</h1>
        <p class="page-subtitle">Planos, usuários e relatórios do sistema</p>
    </div>
    <a href="<?= BASE_URL ?>/dashboard" class="btn btn-ghost">
        <i class="fas fa-arrow-left"></i> Dashboard
    </a>
</div>

<div class="kpi-grid">
    <a href="<?= BASE_URL ?>/usuarios" class="kpi-card kpi-blue kpi-card-link">
        <div class="kpi-icon"><i class="fas fa-users"></i></div>
        <div class="kpi-body">
            <div class="kpi-value"><i class="fas fa-arrow-right"></i></div>
            <div class="kpi-label">Usuários e limites</div>
        </div>
    </a>
    <a href="<?= BASE_URL ?>/admin/planos" class="kpi-card kpi-orange kpi-card-link">
        <div class="kpi-icon"><i class="fas fa-layer-group"></i></div>
        <div class="kpi-body">
            <div class="kpi-value"><i class="fas fa-arrow-right"></i></div>
            <div class="kpi-label">Planos</div>
        </div>
    </a>
    <a href="<?= BASE_URL ?>/admin/relatorios" class="kpi-card kpi-green kpi-card-link">
        <div class="kpi-icon"><i class="fas fa-chart-bar"></i></div>
        <div class="kpi-body">
            <div class="kpi-value"><i class="fas fa-arrow-right"></i></div>
            <div class="kpi-label">Relatórios de uso</div>
        </div>
    </a>
    <a href="<?= BASE_URL ?>/admin/configuracoes" class="kpi-card kpi-gray kpi-card-link">
        <div class="kpi-icon"><i class="fas fa-cog"></i></div>
        <div class="kpi-body">
            <div class="kpi-value"><i class="fas fa-arrow-right"></i></div>
            <div class="kpi-label">Configurações globais</div>
        </div>
    </a>
    <a href="<?= BASE_URL ?>/admin/estoque/usuarios" class="kpi-card kpi-blue kpi-card-link">
        <div class="kpi-icon"><i class="fas fa-warehouse"></i></div>
        <div class="kpi-body">
            <div class="kpi-value"><i class="fas fa-arrow-right"></i></div>
            <div class="kpi-label">Estoque — quem pode usar</div>
        </div>
    </a>
</div>
