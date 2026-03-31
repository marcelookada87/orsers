<?php
/**
 * SLAHelper — cálculos de SLA por ordem de serviço
 */
class SLAHelper
{
    public static function calcPrazo(string $dataAbertura, float $slaHoras, float $multiplicador = 1.0): string
    {
        $segundos  = (int)round($slaHoras * $multiplicador * 3600);
        $timestamp = strtotime($dataAbertura) + $segundos;
        return date('Y-m-d H:i:s', $timestamp);
    }

    public static function percentual(?string $dataAbertura, ?string $slaPrazo): float
    {
        if (!$dataAbertura || !$slaPrazo) return 0.0;

        $inicio    = strtotime($dataAbertura);
        $prazo     = strtotime($slaPrazo);
        $agora     = time();
        $total     = $prazo - $inicio;

        if ($total <= 0) return 100.0;

        $decorrido = $agora - $inicio;
        $pct       = ($decorrido / $total) * 100;
        return round(min($pct, 150), 1);
    }

    public static function cssClass(float $percentual, string $status): string
    {
        if (in_array($status, ['finalizada', 'cancelada'], true)) return 'sla-done';
        if ($percentual > 100) return 'sla-overdue';
        if ($percentual > 85)  return 'sla-danger';
        if ($percentual > 60)  return 'sla-warning';
        return 'sla-ok';
    }

    public static function label(float $percentual, string $status): string
    {
        if (in_array($status, ['finalizada', 'cancelada'], true)) return 'Concluído';
        if ($percentual > 100) return 'Vencido';
        if ($percentual > 85)  return 'Crítico';
        if ($percentual > 60)  return 'Atenção';
        return 'No prazo';
    }

    public static function tempoRestante(?string $slaPrazo, string $status): string
    {
        if (in_array($status, ['finalizada', 'cancelada'], true)) return '—';
        if (!$slaPrazo) return 'Sem SLA';

        $diff = strtotime($slaPrazo) - time();
        if ($diff <= 0) {
            $diff = abs($diff);
            return '-' . self::formatarTempo($diff) . ' (vencido)';
        }
        return self::formatarTempo($diff);
    }

    private static function formatarTempo(int $segundos): string
    {
        $dias  = floor($segundos / 86400);
        $horas = floor(($segundos % 86400) / 3600);
        $min   = floor(($segundos % 3600) / 60);
        if ($dias > 0) return "{$dias}d {$horas}h";
        if ($horas > 0) return "{$horas}h {$min}m";
        return "{$min}m";
    }

    public static function tempoAberto(?string $dataAbertura): string
    {
        if (!$dataAbertura) return '—';
        return self::formatarTempo(time() - strtotime($dataAbertura));
    }
}
