<?php

namespace Tests\Feature;

use Tests\TestCase;

class TrainHubRoutesTest extends TestCase
{
    public function test_root_redirects_to_login(): void
    {
        $this->get('/')->assertRedirect('/login.php');
    }

    public function test_old_login_redirects_to_alamin_login(): void
    {
        config(['alamin_login.url' => 'http://gateway.test']);

        $this->get('/login.php')->assertRedirect('http://gateway.test');
        $this->get('/login')->assertRedirect('http://gateway.test');
        $this->post('/login.php')->assertRedirect('http://gateway.test');
    }
}
