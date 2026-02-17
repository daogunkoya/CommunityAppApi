<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Passport\ClientRepository;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('passport:keys', ['--force' => true]);

        $clientRepository = new ClientRepository();
        $clientRepository->createPersonalAccessGrantClient(
            'Test Personal Access Client',
            'users'
        );
    }
}
