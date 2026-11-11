<?php

namespace Tests\Feature;

use App\Http\Controllers\Trainer\ProfileController;
use ReflectionMethod;
use Tests\TestCase;

class CertificateRemovalRouteContractTest extends TestCase
{
    public function test_certificate_removal_route_matches_controller_parameter_contract(): void
    {
        $route = app('router')
            ->getRoutes()
            ->getByName('trainer.profile.certificate.remove');

        $this->assertNotNull(
            $route,
            'The named certificate-removal route must exist.'
        );

        $this->assertSame(
            ['DELETE'],
            $route->methods(),
            'The certificate-removal route must accept only DELETE.'
        );

        $this->assertSame(
            ProfileController::class.'@certificateRemove',
            $route->getActionName(),
            'The route must target ProfileController::certificateRemove.'
        );

        $controllerMethod = new ReflectionMethod(
            ProfileController::class,
            'certificateRemove'
        );

        $requiredControllerParameters = array_values(array_map(
            static fn ($parameter): string => $parameter->getName(),
            array_filter(
                $controllerMethod->getParameters(),
                static fn ($parameter): bool => ! $parameter->isOptional()
            )
        ));

        $this->assertSame(
            'trainer/profile/certificate/{certId}',
            $route->uri(),
            'The route URI must declare the required certificate identifier.'
        );

        $this->assertSame(
            ['certId'],
            $route->parameterNames(),
            'The route must expose certId as a route parameter.'
        );

        $this->assertSame(
            $requiredControllerParameters,
            $route->parameterNames(),
            'Route parameters must match the controller method’s required parameters.'
        );
    }
}
