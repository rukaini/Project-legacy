<?php

namespace App\Models;

use App\Services\NewTeacherLmsRequirementService;

class GuruNew extends TrainHubModel
{
    protected $table = 'guru_new';
    protected $primaryKey = 'gn_id';

    protected static function booted(): void
    {
        static::created(static function (self $teacher): void {
            // Runs for GuruNew::create()/save() in this Laravel app.
            // Raw SQL and another Laravel app need their own integration.
            NewTeacherLmsRequirementService::snapshotForTeacher((string) $teacher->getKey());
        });
    }
}
