<?php

/**
 * Preferências por usuário em chave/valor (ex.: catálogo, integrações).
 * Chaves padronizadas em {@see UsuarioConfigChave}.
 */
class UsuarioConfiguracao extends Model
{
    protected string $table = 'usuario_configuracoes';

    public function getValor(int $usuarioId, string $chave): ?string
    {
        try {
            $row = $this->db->fetch(
                'SELECT `valor` FROM `usuario_configuracoes` WHERE `usuario_id` = ? AND `chave` = ? LIMIT 1',
                [$usuarioId, $chave]
            );

            return $row ? (string)$row['valor'] : null;
        } catch (Throwable $e) {
            error_log('UsuarioConfiguracao::getValor: ' . $e->getMessage());

            return null;
        }
    }

    /**
     * Valor da tag de código do catálogo: tabela nova ou coluna legada em usuarios (patch antigo).
     */
    public function getValorCodigoItemTagOuLegado(int $usuarioId): ?string
    {
        $v = $this->getValor($usuarioId, UsuarioConfigChave::CATALOGO_CODIGO_ITEM_TAG);
        if ($v !== null && $v !== '') {
            return $v;
        }
        $u = (new User())->find($usuarioId);
        if (!$u) {
            return null;
        }
        if (array_key_exists('estoque_item_codigo_tag', $u)) {
            $t = trim((string)$u['estoque_item_codigo_tag']);
            if ($t !== '') {
                return $t;
            }
        }

        return null;
    }

    public function setValor(int $usuarioId, string $chave, string $valor): void
    {
        $this->db->execute(
            'INSERT INTO `usuario_configuracoes` (`usuario_id`, `chave`, `valor`)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE `valor` = VALUES(`valor`)',
            [$usuarioId, $chave, $valor]
        );
    }
}

/**
 * Chaves de `usuario_configuracoes.chave` (namespace ponto + identificador).
 */
final class UsuarioConfigChave
{
    public const CATALOGO_CODIGO_ITEM_TAG = 'catalogo.codigo_item_tag';

    private function __construct()
    {
    }
}
