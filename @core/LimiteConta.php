<?php

/**
 * Limites efetivos por usuário (plano + overrides). Administrador (perfil) ignora teto de plano.
 */
class LimiteConta
{
    public static function ignoraLimites(array $userRow): bool
    {
        if (($userRow['perfil'] ?? '') === 'admin') {
            return true;
        }
        return ($userRow['plano_codigo'] ?? '') === 'ilimitado';
    }

    /**
     * Teto global de imagens (config admin) e, para conta ilimitada, o próprio teto.
     */
    public static function maxImagensPorOs(int $userId): int
    {
        $user = (new User())->findWithPlano($userId);
        $tetoGlobal = SistemaConfig::maxImagensPorOs();
        if (!$user) {
            return max(1, $tetoGlobal);
        }
        if (self::ignoraLimites($user)) {
            return max(1, $tetoGlobal);
        }
        $base = $user['max_imagens_por_os_override'];
        if ($base === null || $base === '') {
            $base = $user['plano_max_imagens_por_os'] ?? null;
        }
        if ($base === null || $base === '') {
            return max(1, $tetoGlobal);
        }
        return max(1, min((int)$base, $tetoGlobal));
    }

    /** null = ilimitado */
    public static function maxClientes(int $userId): ?int
    {
        $user = (new User())->findWithPlano($userId);
        if (!$user || self::ignoraLimites($user)) {
            return null;
        }
        $v = $user['max_clientes_override'];
        if ($v === null || $v === '') {
            $v = $user['plano_max_clientes'] ?? null;
        }
        if ($v === null || $v === '') {
            return null;
        }
        return max(0, (int)$v);
    }

    /** null = ilimitado */
    public static function maxOsMes(int $userId): ?int
    {
        $user = (new User())->findWithPlano($userId);
        if (!$user || self::ignoraLimites($user)) {
            return null;
        }
        $v = $user['max_os_mes_override'];
        if ($v === null || $v === '') {
            $v = $user['plano_max_os_mes'] ?? null;
        }
        if ($v === null || $v === '') {
            return null;
        }
        return max(0, (int)$v);
    }

    public static function podeCadastrarCliente(int $userId): bool
    {
        $max = self::maxClientes($userId);
        if ($max === null) {
            return true;
        }
        $n = (new Cliente())->contarAtivosPorUsuarioCadastro($userId);
        return $n < $max;
    }

    public static function podeCriarOsNoMes(int $userId): bool
    {
        $max = self::maxOsMes($userId);
        if ($max === null) {
            return true;
        }
        $n = (new Ordem())->contarCriadasNoMesPorUsuario($userId);
        return $n < $max;
    }

    public static function mensagemLimiteClientes(int $userId): string
    {
        $max = self::maxClientes($userId);
        if ($max === null) {
            return '';
        }
        return 'Limite de clientes do seu plano atingido (' . $max . '). Solicite upgrade ao administrador.';
    }

    public static function mensagemLimiteOsMes(int $userId): string
    {
        $max = self::maxOsMes($userId);
        if ($max === null) {
            return '';
        }
        return 'Limite de ordens de serviço neste mês atingido (' . $max . '). Solicite upgrade ao administrador.';
    }

    public static function estoqueAtivo(int $userId): bool
    {
        $u = (new User())->find($userId);
        if (!$u) {
            return false;
        }

        return (int)($u['estoque_ativo'] ?? 0) === 1;
    }

    /**
     * Pode incluir mais um tipo de item no estoque (linha com quantidade > 0), respeitando estoque_limite_itens.
     */
    public static function estoquePodeAdicionarNovoTipo(int $userId, int $itemId): bool
    {
        $u = (new User())->find($userId);
        if (!$u || (int)($u['estoque_ativo'] ?? 0) !== 1) {
            return false;
        }
        if (($u['perfil'] ?? '') === 'admin' || (int)($u['admin'] ?? 0) === 1) {
            return true;
        }
        $limite = $u['estoque_limite_itens'] ?? null;
        if ($limite === null || $limite === '') {
            return true;
        }
        $limite = (int)$limite;
        if ($limite <= 0) {
            return false;
        }

        $saldo = new EstoqueSaldo();
        $qAtual = $saldo->getQuantidade($userId, $itemId);
        if ($qAtual > 0) {
            return true;
        }

        $nTipos = $saldo->contarTiposComEstoquePositivo($userId);

        return $nTipos < $limite;
    }
}
