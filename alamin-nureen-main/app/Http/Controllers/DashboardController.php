<?php

namespace App\Http\Controllers;

use App\Support\LegacyPageRunner;
use App\Services\NewTeacherLmsRequirementService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DashboardController extends Controller
{
    public function __construct(private readonly LegacyPageRunner $runner) {}

    public function index(Request $request): Response
    {
        $response = $this->runner->renderBlade('dashboard', $request, 'legacy.dashboard');
        // Backfill unsnapshotted appointments when Staff EDU visits TrainHub.
        NewTeacherLmsRequirementService::snapshotMissingFromCurrentDefault();
        return $response;
    }
}
