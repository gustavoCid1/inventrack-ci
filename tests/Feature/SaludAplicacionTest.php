<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SaludAplicacionTest extends TestCase
{
    #[Test]
    public function el_endpoint_de_salud_responde(): void
    {
        $this->get('/up')->assertOk();
    }
}
