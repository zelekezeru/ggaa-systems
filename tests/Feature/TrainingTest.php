<?php

namespace Tests\Feature;

use Tests\TestCase;

class TrainingTest extends TestCase
{
    /**
     * Test that the training landing page is accessible.
     */
    public function test_training_landing_page_can_be_rendered(): void
    {
        $response = $this->get('/training');

        $response->assertStatus(200);
    }

    /**
     * Test that the training employees page is accessible.
     */
    public function test_training_employees_page_can_be_rendered(): void
    {
        $response = $this->get('/training/employees');

        $response->assertStatus(200);
    }

    /**
     * Test that the training admins page is accessible.
     */
    public function test_training_admins_page_can_be_rendered(): void
    {
        $response = $this->get('/training/admins');

        $response->assertStatus(200);
    }

    /**
     * Test that the training finances page is accessible.
     */
    public function test_training_finances_page_can_be_rendered(): void
    {
        $response = $this->get('/training/finances');

        $response->assertStatus(200);
    }
}
