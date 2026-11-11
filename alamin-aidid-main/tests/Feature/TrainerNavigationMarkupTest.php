<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class TrainerNavigationMarkupTest extends TestCase
{
    public function test_trainer_navigation_renders_matching_desktop_and_accessible_mobile_links(): void
    {
        Route::middleware('web')->get('/trainer/proposals/_test/navigation', function () {
            return view('trainer.partials.navbar');
        });

        $response = $this
            ->withSession(['user_name' => 'Test Trainer'])
            ->get('/trainer/proposals/_test/navigation');

        $response->assertOk();

        $html = $response->getContent();
        $toggle = $this->extractElement(
            $html,
            'button',
            'data-bs-target="#trainerMobileNavigation"',
        );
        $desktopNavigation = $this->extractNavigation($html, 'desktop');
        $mobileNavigation = $this->extractNavigation($html, 'mobile');

        $this->assertStringContainsString('class="trainer-topbar-shell app-header-shell container-fluid"', $html);
        $this->assertStringContainsString('class="topbar app-header"', $html);
        $this->assertStringContainsString('Al Amin Edu Oasis', $html);
        $this->assertStringContainsString('/images/branding/ai-amin-edu-oasis-logo.jpg', $html);
        $this->assertStringContainsString('class="app-header-logo"', $html);
        $this->assertStringNotContainsString('class="brand-mark"', $html);
        $this->assertStringNotContainsString('brand-subtitle', $html);
        $this->assertStringNotContainsString('Lead Trainer', $html);
        $this->assertStringNotContainsString('Trainer Management System</span>', $html);

        $this->assertStringContainsString('type="button"', $toggle);
        $this->assertStringContainsString('data-bs-toggle="collapse"', $toggle);
        $this->assertStringContainsString('aria-controls="trainerMobileNavigation"', $toggle);
        $this->assertStringContainsString('aria-expanded="false"', $toggle);
        $this->assertStringContainsString('aria-label="Toggle trainer navigation"', $toggle);
        $this->assertStringContainsString('d-lg-none', $toggle);
        $this->assertStringContainsString('Menu', strip_tags($toggle));

        $this->assertStringContainsString('id="trainerMobileNavigation"', $mobileNavigation);
        $this->assertStringContainsString('collapse', $mobileNavigation);
        $this->assertStringContainsString('d-lg-none', $mobileNavigation);
        $this->assertStringContainsString('aria-label="Trainer mobile navigation"', $mobileNavigation);
        $this->assertStringContainsString('d-none d-lg-flex', $desktopNavigation);
        $this->assertStringContainsString('aria-label="Trainer primary navigation"', $desktopNavigation);
        $this->assertStringContainsString('nav-link-top active', $desktopNavigation);
        $this->assertStringContainsString('trainer-mobile-link active', $mobileNavigation);

        $expectedLinks = [
            'Dashboard' => route('trainer.dashboard'),
            'My Training' => route('trainer.courses'),
            'Analytics' => route('trainer.analytics'),
            'Feedback' => route('trainer.feedback'),
            'Proposal' => url('/trainer/proposals'),
        ];

        $this->assertSame($expectedLinks, $this->extractLinks($desktopNavigation));
        $this->assertSame($expectedLinks, $this->extractLinks($mobileNavigation));
        $this->assertStringNotContainsString('Switch System', $response->getContent());
        $this->assertStringNotContainsString('Back to Portal', $response->getContent());
        $this->assertStringNotContainsString('Training Proposal', $desktopNavigation);
        $this->assertStringNotContainsString('Training Proposal', $mobileNavigation);

        $this->assertStringContainsString(
            'href="'.route('trainer.profile').'"',
            $html,
        );
        $this->assertStringContainsString('aria-label="View trainer profile"', $html);
        $this->assertStringContainsString('Trainer', $html);

        $logoutForm = $this->extractElement(
            $html,
            'form',
            'action="'.route('logout').'"',
        );

        $this->assertStringContainsString('method="POST"', $logoutForm);
        $this->assertStringContainsString('name="_token"', $logoutForm);
        $this->assertStringContainsString('id="aididLogoutForm"', $logoutForm);
        $this->assertStringContainsString('type="button"', $logoutForm);
        $this->assertStringContainsString('data-logout-confirm-open', $logoutForm);
        $this->assertStringContainsString('aria-controls="logoutConfirmationDialog"', $logoutForm);
        $this->assertStringContainsString('aria-label="Logout"', $logoutForm);
        $this->assertStringContainsString('<dialog id="logoutConfirmationDialog"', $html);
        $this->assertStringContainsString('Are you sure you want to log out?', $html);
        $this->assertMatchesRegularExpression('/<button[^>]*type="button"[^>]*data-logout-confirm-cancel[^>]*>Cancel<\/button>/', $html);
        $this->assertMatchesRegularExpression('/<button[^>]*type="submit"[^>]*form="aididLogoutForm"[^>]*>Logout<\/button>/', $html);
    }

    private function extractNavigation(string $html, string $surface): string
    {
        return $this->extractElement(
            $html,
            'nav',
            'data-navigation-surface="'.$surface.'"',
        );
    }

    private function extractElement(string $html, string $tag, string $attribute): string
    {
        $pattern = sprintf(
            '~<%1$s\b(?=[^>]*%2$s)[^>]*>.*?</%1$s>~s',
            preg_quote($tag, '~'),
            preg_quote($attribute, '~'),
        );

        $matched = preg_match($pattern, $html, $matches);

        $this->assertSame(
            1,
            $matched,
            sprintf('Unable to find <%s> containing %s.', $tag, $attribute),
        );

        return $matches[0];
    }

    /**
     * @return array<string, string>
     */
    private function extractLinks(string $navigation): array
    {
        preg_match_all(
            '~<a\b[^>]*href="([^"]+)"[^>]*>(.*?)</a>~s',
            $navigation,
            $matches,
            PREG_SET_ORDER,
        );

        $links = [];

        foreach ($matches as $match) {
            $label = preg_replace('/\s+/', ' ', strip_tags($match[2]));
            $links[trim((string) $label)] = html_entity_decode($match[1]);
        }

        return $links;
    }
}
