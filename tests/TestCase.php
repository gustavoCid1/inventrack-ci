<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Las pruebas validan el backend; la compilación de assets (Vite)
        // se verifica en su propia etapa del pipeline (npm run build).
        $this->withoutVite();
    }
}
