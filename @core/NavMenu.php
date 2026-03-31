<?php

/**
 * Menu lateral: itens vindos do banco (`nav_menu_itens` + `nav_menu_item_perfis`).
 */
class NavMenu
{
    public const SECTION_LABELS = [
        'principal'      => 'Principal',
        'gestao'           => 'Gestão',
        'administracao'    => 'Administração',
        'conta'            => 'Conta',
    ];

    /**
     * Garante o item "Categorias" no menu quando o banco ainda não foi atualizado pelo patch (INSERT IGNORE não recria linhas antigas).
     */
    private static function ensureEstoqueCategoriasMenuItem(): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;
        try {
            $db = Database::getInstance();
            $row = $db->fetch(
                'SELECT `id` FROM `nav_menu_itens` WHERE `url_path` = ? LIMIT 1',
                ['/estoque/categorias']
            );
            if ($row) {
                $mid = (int)$row['id'];
                $db->execute(
                    'INSERT IGNORE INTO `nav_menu_item_perfis` (`menu_item_id`, `perfil`) VALUES (?, ?)',
                    [$mid, 'tecnico']
                );
                $db->execute(
                    "UPDATE `nav_menu_itens` SET `sort_order` = 24 WHERE `url_path` = '/estoque/categorias'"
                );
                $db->execute(
                    "UPDATE `nav_menu_itens` SET `sort_order` = 25 WHERE `url_path` = '/estoque/catalogo'"
                );

                return;
            }
            $db->execute(
                "INSERT INTO `nav_menu_itens` (`section_code`,`label`,`icon_class`,`url_path`,`sort_order`,`ativo`,`requer_estoque_ativo`,`item_class`,`active_rule`)
                 VALUES ('gestao','Categorias','fas fa-folder','/estoque/categorias',24,1,1,NULL,'estoque_categorias')"
            );
            $mid = (int)$db->lastInsertId();
            if ($mid > 0) {
                $db->execute(
                    'INSERT IGNORE INTO `nav_menu_item_perfis` (`menu_item_id`, `perfil`) VALUES (?, ?)',
                    [$mid, 'tecnico']
                );
            }
            $db->execute(
                "UPDATE `nav_menu_itens` SET `sort_order` = 25 WHERE `url_path` = '/estoque/catalogo'"
            );
        } catch (Throwable $e) {
            error_log('NavMenu::ensureEstoqueCategoriasMenuItem: ' . $e->getMessage());
        }
    }

    /**
     * Garante o item "Configuração" (Conta) quando o patch de menu ainda não foi aplicado.
     */
    private static function ensureContaConfiguracaoMenuItem(): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;
        try {
            $db = Database::getInstance();
            $row = $db->fetch(
                'SELECT `id` FROM `nav_menu_itens` WHERE `url_path` = ? LIMIT 1',
                ['/conta/configuracao']
            );
            if ($row) {
                $mid = (int)$row['id'];
                foreach (['tecnico', 'admin', 'cliente'] as $perfil) {
                    $db->execute(
                        'INSERT IGNORE INTO `nav_menu_item_perfis` (`menu_item_id`, `perfil`) VALUES (?, ?)',
                        [$mid, $perfil]
                    );
                }

                return;
            }
            $db->execute(
                "INSERT INTO `nav_menu_itens` (`section_code`,`label`,`icon_class`,`url_path`,`sort_order`,`ativo`,`requer_estoque_ativo`,`item_class`,`active_rule`)
                 VALUES ('conta','Configuração','fas fa-sliders-h','/conta/configuracao',20,1,0,NULL,'conta_configuracao')"
            );
            $mid = (int)$db->lastInsertId();
            if ($mid > 0) {
                foreach (['tecnico', 'admin', 'cliente'] as $perfil) {
                    $db->execute(
                        'INSERT IGNORE INTO `nav_menu_item_perfis` (`menu_item_id`, `perfil`) VALUES (?, ?)',
                        [$mid, $perfil]
                    );
                }
            }
        } catch (Throwable $e) {
            error_log('NavMenu::ensureContaConfiguracaoMenuItem: ' . $e->getMessage());
        }
    }

    /** @return array<string, list<array<string,mixed>>> */
    public static function itensAgrupados(int $userId, string $perfil): array
    {
        self::ensureEstoqueCategoriasMenuItem();
        self::ensureContaConfiguracaoMenuItem();
        $db = Database::getInstance();
        $row = $db->fetch('SELECT `estoque_ativo` FROM `usuarios` WHERE `id` = ? LIMIT 1', [$userId]);
        $estoqueOk = $row && (int)($row['estoque_ativo'] ?? 0) === 1;

        $rows = $db->fetchAll(
            "SELECT m.`id`, m.`section_code`, m.`label`, m.`icon_class`, m.`url_path`, m.`sort_order`,
                    m.`requer_estoque_ativo`, m.`item_class`, m.`active_rule`
             FROM `nav_menu_itens` m
             INNER JOIN `nav_menu_item_perfis` p ON p.`menu_item_id` = m.`id`
             WHERE m.`ativo` = 1 AND p.`perfil` = ?
               AND (m.`requer_estoque_ativo` = 0 OR ? = 1)
             ORDER BY FIELD(m.`section_code`, 'principal', 'gestao', 'administracao', 'conta'),
                      m.`sort_order` ASC, m.`id` ASC",
            [$perfil, $estoqueOk ? 1 : 0]
        );

        $out = [];
        foreach ($rows as $r) {
            $sec = (string)$r['section_code'];
            if (!isset($out[$sec])) {
                $out[$sec] = [];
            }
            $out[$sec][] = $r;
        }

        return $out;
    }

    public static function itemEstaAtivo(array $item, string $uriNav, string $pathOnly): bool
    {
        $rule = (string)($item['active_rule'] ?? '');
        $pathOnly = rtrim(str_replace('\\', '/', $pathOnly), '/');
        $scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');

        return match ($rule) {
            'sla_cadastro' => str_contains($uriNav, '/sla/cadastros')
                || str_contains($uriNav, '/sla/categorias')
                || str_contains($uriNav, '/sla/prioridades'),
            'sla_painel' => str_contains($uriNav, '/sla')
                && !str_contains($uriNav, '/sla/cadastros')
                && !str_contains($uriNav, '/sla/categorias')
                && !str_contains($uriNav, '/sla/prioridades'),
            'ordens_index' => str_contains($uriNav, '/ordens') && !str_contains($uriNav, '/ordens/criar'),
            'ordens_criar' => str_contains($uriNav, '/ordens/criar'),
            'admin_hub' => str_ends_with($pathOnly, '/admin'),
            'estoque_catalogo' => str_contains($uriNav, '/estoque/catalogo'),
            'estoque_categorias' => str_contains($uriNav, '/estoque/categorias'),
            'conta_configuracao' => str_contains($uriNav, '/conta/configuracao'),
            'estoque_tecnico' => str_contains($uriNav, '/estoque')
                && !str_contains($uriNav, '/admin/estoque')
                && !str_contains($uriNav, '/estoque/catalogo')
                && !str_contains($uriNav, '/estoque/categorias'),
            'dashboard' => str_contains($uriNav, '/dashboard')
                || str_ends_with($pathOnly, '/dashboard')
                || ($scriptDir !== '' && ($pathOnly === $scriptDir || $pathOnly === $scriptDir . '/')),
            default => self::defaultAtivo((string)$item['url_path'], $uriNav),
        };
    }

    private static function defaultAtivo(string $urlPath, string $uriNav): bool
    {
        if ($urlPath === '' || $urlPath === '/') {
            return false;
        }
        if ($urlPath === '/logout') {
            return false;
        }

        return str_contains($uriNav, $urlPath);
    }
}
