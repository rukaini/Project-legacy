<?php

namespace Tests\Feature;

use Tests\TestCase;

class RootRouteTest extends TestCase
{
    public function test_the_root_route_redirects_to_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/login');
    }
}
