<?php
/**
 * Chave/valor de configurações persistidas (admin).
 */
class SistemaConfig
{
    private const CHAVE_MAX_IMAGENS = 'max_imagens_por_os';

    /** @var array<string, string>|null */
    private static ?array $cache = null;

    public static function flushCache(): void
    {
        self::$cache = null;
    }

    private static function loadCache(): void
    {
        if (self::$cache !== null) {
            return;
        }
        self::$cache = [];
        try {
            $db = Database::getInstance();
            $rows = $db->fetchAll('SELECT chave, valor FROM `sistema_config`');
            foreach ($rows as $r) {
                self::$cache[(string)$r['chave']] = (string)$r['valor'];
            }
        } catch (Throwable) {
            self::$cache = [];
        }
    }

    public static function get(string $chave, string $default = ''): string
    {
        self::loadCache();
        return self::$cache[$chave] ?? $default;
    }

    public static function set(string $chave, string $valor): void
    {
        $db = Database::getInstance();
        $db->execute(
            'INSERT INTO `sistema_config` (`chave`, `valor`) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE `valor` = VALUES(`valor`)',
            [$chave, $valor]
        );
        self::flushCache();
    }

    public static function maxImagensPorOs(): int
    {
        $fallback = defined('MAX_IMAGES_PER_OS_FALLBACK')
            ? (int)MAX_IMAGES_PER_OS_FALLBACK
            : 5;
        $v = (int)self::get(self::CHAVE_MAX_IMAGENS, (string)$fallback);
        if ($v < 1) {
            $v = 1;
        }
        if ($v > 50) {
            $v = 50;
        }
        return $v;
    }

    public static function setMaxImagensPorOs(int $n): void
    {
        if ($n < 1) {
            $n = 1;
        }
        if ($n > 50) {
            $n = 50;
        }
        self::set(self::CHAVE_MAX_IMAGENS, (string)$n);
    }
}
