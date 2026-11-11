<?php

namespace App\Support;

final class ExternalParticipantIdentity
{
    public static function teacherIdColumn(): string
    {
        return 'teacherID';
    }

    public static function teacherNameColumn(): string
    {
        return 'teacherName';
    }

    public static function newTeacherNameColumn(): string
    {
        return 'gn_name';
    }

    public static function teacherId(string $qualifier = 'teacher'): string
    {
        return $qualifier.'.'.self::teacherIdColumn();
    }

    public static function teacherName(string $qualifier = 'teacher'): string
    {
        return $qualifier.'.'.self::teacherNameColumn();
    }

    public static function newTeacherId(string $qualifier = 'guru_new'): string
    {
        return $qualifier.'.gn_id';
    }

    public static function newTeacherName(string $qualifier = 'guru_new'): string
    {
        return $qualifier.'.'.self::newTeacherNameColumn();
    }
}
