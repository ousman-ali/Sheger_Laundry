<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\RefreshMysql;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, RefreshMysql;

    protected function setUp(): void
    {
        parent::setUp();
        $this->refreshMysql();
    }
}
