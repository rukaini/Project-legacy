<?php

namespace App\Support;

final class ExternalTrainingSchema
{
    public static function courseIdColumn(): string
    {
        return 'courseID';
    }

    public static function courseNameColumn(): string
    {
        return 'courseName';
    }

    public static function courseTypeColumn(): string
    {
        return 'courseType';
    }

    public static function targetAudienceColumn(): string
    {
        return 'targetAudience';
    }

    public static function sessionIdColumn(): string
    {
        return 'sessionID';
    }

    public static function sessionCourseIdColumn(): string
    {
        return 'courseID';
    }

    public static function sessionDateColumn(): string
    {
        return 'sessionDate';
    }

    public static function sessionNameColumn(): string
    {
        return 'sessionName';
    }

    public static function sessionStartColumn(): string
    {
        return 'startTime';
    }

    public static function sessionEndColumn(): string
    {
        return 'endTime';
    }

    public static function assignmentSessionIdColumn(): string
    {
        return 'sessionID';
    }

    public static function assignmentTrainerIdColumn(): string
    {
        return 'trainerID';
    }

    public static function trainerIdColumn(): string
    {
        return 'trainerID';
    }

    public static function trainerNameColumn(): string
    {
        return 'trainerName';
    }

    public static function staffTable(): string
    {
        return 'staff_edu';
    }

    public static function staffIdColumn(): string
    {
        return 'staffID';
    }

    public static function staffNameColumn(): string
    {
        return 'staffName';
    }
}
