<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $compiledViewsPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'payment-myads-test-views';

        if (! is_dir($compiledViewsPath)) {
            mkdir($compiledViewsPath, 0777, true);
        }

        config(['view.compiled' => $compiledViewsPath]);
    }
}
