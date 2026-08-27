<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Health endpoint smoke test — safe on any DB state.
     */
    public function test_health_endpoint(): void
    {
        $response = $this->get('/up');

        $response->assertStatus(200);
    }
}
