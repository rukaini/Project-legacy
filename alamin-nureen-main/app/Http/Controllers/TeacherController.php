<?php

namespace App\Http\Controllers;

use App\Support\LegacyPageRunner;
use App\Services\NewTeacherLmsRequirementService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TeacherController extends Controller
{
    public function __construct(private readonly LegacyPageRunner $runner) {}

    public function index(Request $request): Response
    {
        $response = $this->runner->renderBlade('teacher', $request, 'legacy.teacher');
        // Pick up new teachers created in other group systems without requiring a trigger.
        NewTeacherLmsRequirementService::snapshotMissingFromCurrentDefault();
        return $response;
    }
}
