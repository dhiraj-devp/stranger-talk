<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertOk();
    }

    public function test_unexpected_errors_hide_internal_details(): void
    {
        $this->get('/__exception')
            ->assertStatus(500)
            ->assertSee('Something went wrong')
            ->assertDontSee('database password hidden')
            ->assertDontSee('SQLSTATE')
            ->assertDontSee('vendor\\laravel');

        $this->getJson('/__exception')
            ->assertStatus(500)
            ->assertExactJson(['message' => 'Something went wrong. Please try again.']);
    }
}
