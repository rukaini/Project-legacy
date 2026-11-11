<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AppToastTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::get('/__tests/app-toasts', fn () => view('components.app-toasts'));
    }

    public function test_toast_container_is_not_rendered_without_a_message(): void
    {
        $this->get('/__tests/app-toasts')
            ->assertOk()
            ->assertDontSee('data-app-toast-region', false);
    }

    public function test_success_flash_renders_the_shared_status_toast(): void
    {
        $this->withSession(['success' => 'Material uploaded successfully.'])
            ->get('/__tests/app-toasts')
            ->assertOk()
            ->assertSee('class="app-toast success"', false)
            ->assertSee('role="status"', false)
            ->assertSee('bi-check-circle-fill', false)
            ->assertSeeText('Material uploaded successfully.');
    }

    public function test_error_flash_renders_the_shared_alert_toast(): void
    {
        $this->withSession(['error' => 'The material could not be uploaded.'])
            ->get('/__tests/app-toasts')
            ->assertOk()
            ->assertSee('class="app-toast error"', false)
            ->assertSee('role="alert"', false)
            ->assertSee('bi-exclamation-triangle-fill', false)
            ->assertSeeText('The material could not be uploaded.');
    }

    public function test_multiple_flash_messages_stack_without_duplicate_ids(): void
    {
        $response = $this->withSession([
            'success' => 'First action completed.',
            'error' => 'Second action failed.',
        ])->get('/__tests/app-toasts')->assertOk();

        $response->assertSeeTextInOrder(['First action completed.', 'Second action failed.']);
        $this->assertSame(2, preg_match_all('/\sdata-app-toast(?:\s|>)/', $response->getContent()));
        $response->assertDontSee('id=', false);
    }

    public function test_status_flash_uses_the_success_status_contract(): void
    {
        $this->withSession(['status' => 'Your settings were saved.'])
            ->get('/__tests/app-toasts')
            ->assertOk()
            ->assertSee('class="app-toast success"', false)
            ->assertSee('role="status"', false)
            ->assertSeeText('Your settings were saved.');
    }
}
