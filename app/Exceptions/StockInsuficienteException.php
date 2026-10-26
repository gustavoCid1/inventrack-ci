<?php

namespace App\Exceptions;

use RuntimeException;

class StockInsuficienteException extends RuntimeException
{
    public static function para(string $producto, int $disponible, int $solicitado): self
    {
        return new self("Stock insuficiente para {$producto}: disponible {$disponible}, solicitado {$solicitado}.");
    }
}
