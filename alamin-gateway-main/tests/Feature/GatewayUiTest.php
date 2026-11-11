<?php

namespace Tests\Feature;

use Tests\TestCase;

class GatewayUiTest extends TestCase
{
    public function test_portal_stylesheet_is_versioned_and_render_blocking_in_the_document_head(): void
    {
        config(['app.url' => 'http://localhost']);

        $html = $this->get('http://10.10.0.219:8086/login')
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression('/<head\b[^>]*>(.*?)<\/head>/is', $html);
        preg_match('/<head\b[^>]*>(.*?)<\/head>/is', $html, $headMatches);
        $head = $headMatches[1];

        $this->assertSame(1, preg_match_all('/<link\b[^>]*portal\.css[^>]*>/i', $html, $stylesheetMatches));
        $stylesheet = $stylesheetMatches[0][0];

        $this->assertStringContainsString($stylesheet, $head);
        $this->assertStringContainsString('rel="stylesheet"', $stylesheet);
        $this->assertMatchesRegularExpression(
            '#href="http://10\.10\.0\.219:8086/css/portal\.css\?v=\d+"#',
            $stylesheet,
        );
        $this->assertDoesNotMatchRegularExpression('/\b(?:async|defer|media|onload)\s*=/i', $stylesheet);
        $this->assertStringNotContainsString('rel="preload"', $stylesheet);
    }

    public function test_login_page_uses_elit_portal_branding_real_logo_and_functional_controls_only(): void
    {
        $response = $this->get('/login')
            ->assertOk()
            ->assertSee('ELIT Portal')
            ->assertSee('EduOasis Learning &amp; Insight Tracking', false)
            ->assertSee('Login')
            ->assertSee('Sign in to continue to your available systems.')
            ->assertSee('Email')
            ->assertSee('Password')
            ->assertDontSee('Account access')
            ->assertDontSee('Outsider')
            ->assertSee('images/al-amin-edu-oasis-logo.jpg', false)
            ->assertSee('alt="Al Amin Edu Oasis logo"', false)
            ->assertSee('data-password-toggle="password"', false)
            ->assertSee('data-loading', false)
            ->assertSee('Forgot password?')
            ->assertSee('href="'.route('password.request').'"', false)
            ->assertDontSee('Change Password')
            ->assertSee('name="_token"', false);

        foreach ([
            'One account',
            'Integrated Systems Portal',
            'Connected systems',
            'Canonical identity verification',
            'Secure one-time system handoff',
            'Sign in securely',
            'Reset password',
            'system administrator',
            'Gateway',
            'SSO',
            'Contact Us',
            'Privacy Policy',
            'Terms',
        ] as $unusedCopy) {
            $response->assertDontSee($unusedCopy);
        }

        $this->assertStringNotContainsString('password/reset', $response->getContent());
        foreach ([
            'Sistem'.' ELIT',
            'Staff EDU /'.' Admin',
            'Guru'.' Baru',
            'Guru'.' Besar',
        ] as $retiredLabel) {
            $this->assertStringNotContainsString($retiredLabel, $response->getContent());
        }
    }

    public function test_portal_header_is_sticky_and_system_directory_is_compact(): void
    {
        $response = $this->get('/login')
            ->assertOk()
            ->assertSee('class="portal-header"', false)
            ->assertSee('aria-label="ELIT Portal home"', false);

        $this->assertMatchesRegularExpression(
            '/<header class="portal-header">.*ELIT Portal.*EduOasis Learning &amp; Insight Tracking.*<\/header>/s',
            $response->getContent(),
        );
        $css = file_get_contents(public_path('css/portal.css'));
        $this->assertMatchesRegularExpression(
            '/\.portal-header\s*\{[^}]*position:\s*sticky;[^}]*top:\s*0;[^}]*z-index:\s*50;[^}]*background:\s*var\(--surface\);/s',
            $css,
        );
        preg_match('/\.portal-header\s*\{([^}]*)\}/s', $css, $headerRules);
        $this->assertStringNotContainsString('border-top', $headerRules[1]);
        $this->assertMatchesRegularExpression(
            '/\.system-grid\s*\{[^}]*grid-template-columns:\s*repeat\(3,\s*minmax\(0,\s*1fr\)\);/s',
            $css,
        );
        $this->assertMatchesRegularExpression(
            '/\.system-grid\.is-single\s*\{[^}]*grid-template-columns:\s*minmax\(0,\s*300px\);/s',
            $css,
        );
    }

    public function test_single_role_page_uses_compact_role_presentation_and_one_real_system_card(): void
    {
        $this->withSession(['gateway.identity' => $this->identity('trainer')])
            ->get('/systems')
            ->assertOk()
            ->assertSee('class="role-badge"', false)
            ->assertSee('Role:')
            ->assertSee('Trainer')
            ->assertSee('class="system-grid is-single"', false)
            ->assertSee('Trainer Management System')
            ->assertDontSee('Current Role')
            ->assertDontSee('class="role-section"', false)
            ->assertDontSee('TrainHub');
    }

    public function test_multi_role_page_keeps_selector_and_selected_role_systems_together(): void
    {
        $staff = $this->role('staff_edu');
        $trainer = $this->role('trainer');
        $identity = [
            'email' => $staff['email'],
            'name' => $staff['name'],
            'roles' => ['staff_edu' => $staff, 'trainer' => $trainer],
            'selected_role' => 'staff_edu',
        ];

        $response = $this->withSession(['gateway.identity' => $identity])
            ->get('/systems')
            ->assertOk()
            ->assertSee('Select Role')
            ->assertSee('Available systems are based on your selected role.')
            ->assertSee('name="role"', false)
            ->assertSee('data-role-select', false)
            ->assertSee('<option value="staff_edu" selected', false)
            ->assertSee('TrainHub')
            ->assertSee('Trainer Management System')
            ->assertSee('Teacher Employment System')
            ->assertDontSee('EduTrain');

        $this->assertStringNotContainsString('Staff EDU /'.' Admin', $response->getContent());

        $visibleText = html_entity_decode(strip_tags($response->getContent()));
        $this->assertSame(1, substr_count($visibleText, 'TrainHub'));
        $this->assertSame(1, substr_count($visibleText, 'Trainer Management System'));
        $this->assertSame(1, substr_count($visibleText, 'Teacher Employment System'));
        $this->assertStringNotContainsString('aidid', strtolower($visibleText));
        foreach (['Aidid', 'Nureen', 'Syiqin', 'Izz', 'TYA'] as $internalName) {
            $this->assertStringNotContainsString($internalName, $visibleText);
        }
        foreach (['LMS', 'Feedback', 'Materials', 'Assessments', 'Sessions'] as $inventedCard) {
            $response->assertDontSee($inventedCard);
        }
    }

    public function test_navbar_uses_one_accessible_account_menu_with_derived_initials(): void
    {
        $identity = $this->identity('principal');
        $identity['name'] = 'Presentation Principal';
        $identity['roles']['principal']['name'] = 'Presentation Principal';

        $response = $this->withSession(['gateway.identity' => $identity])
            ->get('/systems')
            ->assertOk()
            ->assertSee('data-account-menu', false)
            ->assertSee('class="account-menu-trigger"', false)
            ->assertSee('aria-haspopup="menu"', false)
            ->assertSee('aria-expanded="false"', false)
            ->assertSee('aria-controls="account-menu-dropdown"', false)
            ->assertSee('role="menu"', false)
            ->assertSee('role="menuitem"', false)
            ->assertSee('class="header-avatar" aria-hidden="true"', false)
            ->assertSee('>PP</span>', false)
            ->assertSee('class="header-name">Presentation Principal</span>', false)
            ->assertSee('href="'.route('password.change').'"', false)
            ->assertSee('Change Password')
            ->assertSee('Logout')
            ->assertSee('Welcome, Presentation Principal')
            ->assertSee('class="role-badge"', false)
            ->assertSee('Principal');

        $html = $response->getContent();
        $this->assertSame(1, preg_match_all('/<button[^>]*data-account-menu-trigger/s', $html));
        $this->assertDoesNotMatchRegularExpression('/<header[^>]*>.*class="header-link/s', $html);
        $this->assertStringContainsString("event.key === 'Escape'", $html);
        $this->assertStringContainsString("event.key === 'ArrowDown'", $html);
        $this->assertStringContainsString('if (! menu.contains(event.target)) setOpen(false);', $html);
        $this->assertMatchesRegularExpression(
            '/<form method="POST" action="'.preg_quote(route('logout'), '/').'"[^>]*>.*name="_token".*<button[^>]*type="submit"[^>]*role="menuitem"/s',
            $html,
        );

        $css = (string) file_get_contents(public_path('css/portal.css'));
        $this->assertMatchesRegularExpression(
            '/@media \(max-width: 480px\).*\.account-menu-trigger\s*\{[^}]*max-width:\s*165px;/s',
            $css,
        );

        session()->flush();
        $this->get('/forgot-password')->assertOk()->assertDontSee('Change Password');
        $this->get('/register')->assertOk()->assertDontSee('Change Password');
    }

    public function test_administrator_account_and_role_badges_are_separate_neutral_badges(): void
    {
        $role = $this->role('staff_edu');
        $role['name'] = 'System Administrator';
        $identity = [
            'email' => $role['email'],
            'name' => $role['name'],
            'roles' => ['staff_edu' => $role],
            'selected_role' => 'staff_edu',
            'is_administrator' => true,
        ];

        $this->withSession(['gateway.identity' => $identity])
            ->get('/systems')
            ->assertOk()
            ->assertSee('Welcome, System Administrator')
            ->assertSee('class="account-badge"', false)
            ->assertSee('Account:')
            ->assertSee('Administrator')
            ->assertSee('class="role-badge"', false)
            ->assertSee('Role:')
            ->assertSee('Staff EDU')
            ->assertSee('>SA</span>', false)
            ->assertSee('<span>Administrator</span>', false)
            ->assertSee('<span>Role: Staff EDU</span>', false)
            ->assertSee('images/al-amin-edu-oasis-logo.jpg', false)
            ->assertSee('ELIT Portal');

        $css = (string) file_get_contents(public_path('css/portal.css'));
        $this->assertStringNotContainsString('#fff8df', strtolower($css));
        $this->assertStringNotContainsString('#d7c48b', strtolower($css));
        $this->assertMatchesRegularExpression(
            '/\.account-badge\s*\{[^}]*border-color:\s*#c3cfda;[^}]*background:\s*#f2f5f8;/s',
            $css,
        );

        $normal = $this->withSession(['gateway.identity' => $this->identity('teacher')])->get('/systems');
        $normal->assertSee('Role:')->assertSee('Teacher')->assertDontSee('Account:');
    }

    public function test_canonical_outsider_role_is_presented_as_public_with_edutrain_only(): void
    {
        $response = $this->withSession(['gateway.identity' => $this->identity('outsider')])
            ->get('/systems')
            ->assertOk()
            ->assertSee('class="systems-body"', false)
            ->assertSee('Role:')
            ->assertSee('Public')
            ->assertSee('EduTrain')
            ->assertDontSee('Outsider');

        foreach (['Trainer Management System', 'TrainHub', 'Teacher Employment System', 'Human Resources System'] as $unavailableSystem) {
            $response->assertDontSee($unavailableSystem);
        }

        $this->assertSame('outsider', $this->identity('outsider')['selected_role']);
    }

    private function identity(string $selectedRole): array
    {
        $role = $this->role($selectedRole);

        return [
            'email' => $role['email'],
            'name' => $role['name'],
            'roles' => [$selectedRole => $role],
            'selected_role' => $selectedRole,
        ];
    }

    private function role(string $role): array
    {
        return [
            'role' => $role,
            'source' => $role,
            'id' => $role === 'trainer' ? 'TR001' : 'ST001',
            'name' => 'Portal User',
            'email' => 'portal@example.test',
        ];
    }
}
