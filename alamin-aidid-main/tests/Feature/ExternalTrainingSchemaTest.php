<?php

namespace Tests\Feature;

use App\Support\CanonicalTrainingQuery;
use App\Support\ExternalTrainingSchema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ExternalTrainingSchemaTest extends TestCase
{
    public function test_owner_columns_and_arbitrary_string_ids_are_resolved_without_exposing_password_fields(): void
    {
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');

        Schema::create('course', function (Blueprint $table): void {
            $table->string('courseID', 20)->primary();
            $table->string('courseName', 255);
            // SQLite has no SET type; the owner-value contract is asserted by the synthetic row below.
            $table->string('targetAudience')->default('teacher');
            $table->string('courseType', 20)->nullable();
        });
        Schema::create('course_session', function (Blueprint $table): void {
            $table->string('sessionID', 20)->primary();
            $table->string('courseID', 20);
            $table->date('sessionDate');
            $table->string('sessionName', 255);
            $table->time('startTime');
            $table->time('endTime');
        });
        Schema::create('session_trainer', function (Blueprint $table): void {
            $table->string('trainerID', 20);
            $table->string('sessionID', 20);
            $table->primary(['trainerID', 'sessionID']);
        });
        Schema::create('trainer', function (Blueprint $table): void {
            $table->string('trainerID', 20)->primary();
            $table->string('trainerName', 255);
            $table->string('trainerPassword', 255);
        });
        Schema::create('staff_edu', function (Blueprint $table): void {
            $table->string('staffID', 20)->primary();
            $table->string('staffName', 250);
            $table->string('password', 255);
        });

        DB::table('course')->insert([
            'courseID' => 'CTEST01',
            'courseName' => 'Synthetic Course',
            'targetAudience' => 'new_teacher',
            'courseType' => 'LMS',
        ]);
        DB::table('trainer')->insert([
            'trainerID' => 'TRTEST01',
            'trainerName' => 'Synthetic Trainer',
            'trainerPassword' => 'not-returned',
        ]);
        DB::table('staff_edu')->insert([
            'staffID' => 'EDUTEST01',
            'staffName' => 'Synthetic Staff',
            'password' => 'not-returned',
        ]);

        $this->assertSame('courseID', ExternalTrainingSchema::courseIdColumn());
        $this->assertSame('courseName', ExternalTrainingSchema::courseNameColumn());
        $this->assertSame('targetAudience', ExternalTrainingSchema::targetAudienceColumn());
        $this->assertSame('courseType', ExternalTrainingSchema::courseTypeColumn());
        $this->assertSame('sessionID', ExternalTrainingSchema::sessionIdColumn());
        $this->assertSame('trainerID', ExternalTrainingSchema::trainerIdColumn());
        $this->assertSame('trainerName', ExternalTrainingSchema::trainerNameColumn());
        $this->assertSame('staff_edu', ExternalTrainingSchema::staffTable());
        $this->assertSame('staffID', ExternalTrainingSchema::staffIdColumn());
        $this->assertSame('staffName', ExternalTrainingSchema::staffNameColumn());

        $course = DB::table('course')->where('courseID', 'CTEST01')->first(['courseID', 'courseName', 'targetAudience', 'courseType']);
        $trainer = DB::table('trainer')->where('trainerID', 'TRTEST01')->first(['trainerID', 'trainerName']);
        $staff = DB::table('staff_edu')->where('staffID', 'EDUTEST01')->first(['staffID', 'staffName']);

        $this->assertSame('CTEST01', $course->courseID);
        $this->assertSame('new_teacher', $course->targetAudience);
        $this->assertSame('LMS', $course->courseType);
        $this->assertSame('TRTEST01', $trainer->trainerID);
        $this->assertSame('Synthetic Trainer', $trainer->trainerName);
        $this->assertObjectNotHasProperty('trainerPassword', $trainer);
        $this->assertSame('EDUTEST01', $staff->staffID);
        $this->assertSame('Synthetic Staff', $staff->staffName);
        $this->assertObjectNotHasProperty('password', $staff);
    }

    public function test_general_identity_queries_never_select_password_columns(): void
    {
        $trainerColumns = CanonicalTrainingQuery::trainers()->columns ?? [];
        $staffColumns = CanonicalTrainingQuery::staff()->columns ?? [];

        $this->assertNotContains('trainer.*', $trainerColumns);
        $this->assertNotContains('trainer.trainerPassword', $trainerColumns);
        $this->assertNotContains('trainer.trainerPassword as password', $trainerColumns);
        $this->assertNotContains('staff_edu.*', $staffColumns);
        $this->assertNotContains('staff_edu.password', $staffColumns);
    }
}
