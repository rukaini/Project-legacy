<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\CanonicalEnrollment;
use App\Support\CanonicalTrainingQuery;
use App\Support\ExternalParticipantIdentity;
use App\Support\ParticipantTypeLabel;
use App\Support\SharedTrainingSchema;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DirectoryController extends Controller
{
    public function directory(Request $request)
    {
        if (session('user_role') !== 'admin') {
            return redirect()->route('login')->with('error', 'Please login as admin first.');
        }

        $label = function ($column) {
            $map = [
                'teacher_email' => 'Email', 'email' => 'Email', 'trainer_email' => 'Email',
                'trainer_phoneNo' => 'Phone', 'phone' => 'Phone', 'phone_no' => 'Phone',
                'phoneNo' => 'Phone', 'phoneNumber' => 'Phone', 'phone_number' => 'Phone', 'contact' => 'Phone',
                'gender' => 'Gender', 'current_status' => 'Status', 'status' => 'Status',
                'join_date' => 'Joined', 'appointed_date' => 'Appointed', 'appointedDate' => 'Appointed',
                'serviceDate' => 'Service Date', 'pensionDate' => 'Pension Date', 'latestAge' => 'Latest Age',
                'marital_status' => 'Marital Status', 'maritalStatus' => 'Marital Status', 'expertise' => 'Expertise',
                'school' => 'School', 'school_name' => 'School', 'sekolah' => 'School',
                'schoolID' => 'School ID', 'hrID' => 'HR Administrator ID',
                'organization' => 'Organisation', 'organisation' => 'Organisation', 'company' => 'Company',
                'ic' => 'IC / MyKad', 'ic_no' => 'IC / MyKad', 'ic_number' => 'IC / MyKad',
                'ICNumber' => 'IC / MyKad', 'nric' => 'NRIC', 'mykad' => 'IC / MyKad',
                'subject' => 'Subject', 'position' => 'Position', 'department' => 'Department', 'address' => 'Address',
                'resignation_request_date' => 'Resignation Request Date',
                'resignation_request_reason' => 'Resignation Request Reason',
                'resignation_request_status' => 'Resignation Request Status',
            ];

            return $map[$column] ?? ucwords(str_replace('_', ' ', $column));
        };
        $hidden = ['password', 'trainerPassword', 'password_change_required', 'password_changed_required',
            'trainer_changed_password', 'remember_token', 'created_at', 'updated_at',
            'trainer_pic', 'pic', 'photo', 'avatar', 'image'];

        $courseSessionRows = DB::table('course as course');
        SharedTrainingSchema::joinCourseSessions($courseSessionRows);
        $courseSessionRows = $courseSessionRows
            ->selectRaw(SharedTrainingSchema::courseInternalExpression().' AS course_id')
            ->selectRaw(SharedTrainingSchema::sessionInternalExpression().' AS session_id')
            ->get();
        $courseSessionsMap = $courseSessionRows
            ->groupBy('course_id')->map(function ($group) {
                return $group->pluck('session_id')->all();
            });

        $sessionTrainerRows = DB::table('course as course');
        SharedTrainingSchema::joinCourseSessions($sessionTrainerRows);
        SharedTrainingSchema::joinSessionAssignments($sessionTrainerRows);
        SharedTrainingSchema::joinAssignmentTrainers($sessionTrainerRows);
        $sessionTrainerRows = $sessionTrainerRows
            ->selectRaw(SharedTrainingSchema::sessionInternalExpression().' AS session_id')
            ->selectRaw(SharedTrainingSchema::trainerInternalExpression().' AS trainer_id')
            ->selectRaw(SharedTrainingSchema::courseInternalExpression().' AS course_id')
            ->get();
        $sessionTrainersMap = $sessionTrainerRows->groupBy('session_id')->map(function ($group) {
            return $group->pluck('trainer_id')->unique()->values()->all();
        });
        $courseTrainersMap = $sessionTrainerRows->groupBy('course_id')->map(function ($group) {
            return $group->pluck('trainer_id')->unique()->values()->all();
        });
        $trainerCoursesMap = $sessionTrainerRows->groupBy('trainer_id')->map(function ($group) {
            return $group->pluck('course_id')->unique()->values()->all();
        });
        $trainerSessionsMap = $sessionTrainerRows->groupBy('trainer_id')->map(function ($group) {
            return $group->pluck('session_id')->unique()->values()->all();
        });

        $trainerNames = CanonicalTrainingQuery::trainers()
            ->get()
            ->pluck('trainer_name', 'trainer_id');

        $trainerCertificates = collect();
        if (Schema::hasTable('trainer_certificate')) {
            $trainerCertificates = DB::table('trainer_certificate')
                ->select('cert_id', 'trainer_id', 'title', 'file_path', 'uploaded_at')
                ->orderByDesc('uploaded_at')
                ->orderByDesc('cert_id')
                ->get()
                ->groupBy('trainer_id');
        }

        $participantCourses = [];
        foreach (CanonicalEnrollment::query()->get() as $enrollment) {
            if ($enrollment->teacher_id !== null && $enrollment->teacher_id !== '') {
                $key = 'Teacher:'.$enrollment->teacher_id;
            } elseif ($enrollment->applicant_id !== null && $enrollment->applicant_id !== '') {
                $key = 'Guru New:'.$enrollment->applicant_id;
            } elseif ($enrollment->outsider_id !== null && $enrollment->outsider_id !== '') {
                $key = 'Outsider:'.$enrollment->outsider_id;
            } elseif ($enrollment->staff_id !== null && $enrollment->staff_id !== '') {
                $key = 'Staff:'.$enrollment->staff_id;
            } else {
                continue;
            }
            $participantCourses[$key][] = $enrollment->course_id;
        }

        $participants = collect();
        $sources = [
            ['teacher', ExternalParticipantIdentity::teacherIdColumn(), ExternalParticipantIdentity::teacherNameColumn(), 'Teacher', 'Teacher'],
            ['guru_new', 'gn_id', ExternalParticipantIdentity::newTeacherNameColumn(), 'Guru Baru', 'Guru New'],
            ['outsider', 'outsider_id', 'name', 'Outsider', 'Outsider'],
            ['staff_edu', 'staffID', 'staffName', 'Staff', 'Staff'],
        ];
        foreach ($sources as $source) {
            [$table, $idColumn, $nameColumn, $type, $keyType] = $source;
            if (! Schema::hasTable($table)) {
                continue;
            }
            $columns = Schema::getColumnListing($table);
            $safeColumns = array_values(array_diff($columns, $hidden));
            foreach (DB::table($table)->select($safeColumns)->get() as $record) {
                $id = $record->$idColumn ?? null;
                $name = $record->$nameColumn ?? 'Participant';
                $courseIds = array_values(array_unique($participantCourses[$keyType.':'.$id] ?? []));
                $sessionIds = [];
                foreach ($courseIds as $courseId) {
                    if (isset($courseSessionsMap[$courseId])) {
                        $sessionIds = array_merge($sessionIds, $courseSessionsMap[$courseId]);
                    }
                }

                $details = [];
                foreach ($columns as $column) {
                    if ($column === $idColumn || $column === $nameColumn || in_array($column, $hidden)) {
                        continue;
                    }
                    $value = $record->$column ?? null;
                    if ($value === null || $value === '') {
                        continue;
                    }
                    $details[$label($column)] = $value;
                }

                $participants->push((object) [
                    'name' => $name,
                    'type' => ParticipantTypeLabel::display($type),
                    'type_value' => $type,
                    'course_ids' => $courseIds,
                    'session_ids' => array_values(array_unique($sessionIds)),
                    'courses' => count($courseIds),
                    'details' => $details,
                ]);
            }
        }
        $participants = $participants->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)->values();

        $trainers = CanonicalTrainingQuery::trainers()->orderBy('trainer_name')->get()
            ->map(function ($trainer) use ($trainerCoursesMap, $trainerSessionsMap, $trainerCertificates, $label, $hidden) {
                $trainer->course_ids = $trainerCoursesMap[$trainer->trainer_id] ?? [];
                $trainer->session_ids = $trainerSessionsMap[$trainer->trainer_id] ?? [];
                $trainer->course_count = count($trainer->course_ids);
                $skip = ['trainer_id', 'trainer_name', 'course_ids', 'session_ids', 'course_count'];
                $details = [];
                foreach (get_object_vars($trainer) as $column => $value) {
                    if (in_array($column, $skip) || in_array($column, $hidden)) {
                        continue;
                    }
                    if ($value === null || $value === '' || is_array($value)) {
                        continue;
                    }
                    $details[$label($column)] = $value;
                }
                $trainer->details = $details;
                $trainer->certificates = $trainerCertificates
                    ->get($trainer->trainer_id, collect())
                    ->map(function ($certificate) use ($trainer) {
                        $path = $this->certificateStoragePath($certificate->file_path ?? null);
                        $available = $path !== null && Storage::disk('public')->exists($path);
                        $uploadedAt = null;

                        if (! empty($certificate->uploaded_at)) {
                            try {
                                $uploadedAt = Carbon::parse($certificate->uploaded_at)->format('d M Y');
                            } catch (\Throwable) {
                                $uploadedAt = null;
                            }
                        }

                        $extension = $path ? strtoupper((string) pathinfo($path, PATHINFO_EXTENSION)) : '';

                        return [
                            'id' => (int) $certificate->cert_id,
                            'title' => trim((string) ($certificate->title ?? '')) ?: 'Certificate',
                            'file_type' => $extension ?: 'File',
                            'uploaded_at' => $uploadedAt,
                            'available' => $available,
                            'view_url' => $available
                                ? route('admin.directory.trainer.certificate.view', [
                                    'trainerId' => $trainer->trainer_id,
                                    'certId' => $certificate->cert_id,
                                ])
                                : null,
                        ];
                    })
                    ->values();

                return $trainer;
            });

        $courses = CanonicalTrainingQuery::courses()
            ->orderBy('course_name')
            ->get()
            ->map(function ($course) use ($courseSessionsMap, $courseTrainersMap, $trainerNames) {
                $course->total_sessions = count($courseSessionsMap[$course->course_id] ?? []);
                $course->held_sessions = SharedTrainingSchema::constrainSessionsToCourse(
                    CanonicalTrainingQuery::sessions(),
                    $course
                )->whereRaw(SharedTrainingSchema::sessionDateExpression().' <= CURRENT_DATE')->count();
                $course->enrolled = SharedTrainingSchema::constrainEnrollmentToCourse(
                    CanonicalEnrollment::query(),
                    $course
                )->count();
                $course->is_lms = strtolower((string) ($course->training_mode ?? '')) === 'lms';
                $course->trainer_ids = $courseTrainersMap[$course->course_id] ?? [];
                $course->trainer_names = collect($course->trainer_ids)
                    ->map(fn ($trainerId) => $trainerNames[$trainerId] ?? null)
                    ->filter()
                    ->values()
                    ->all();

                return $course;
            });

        $sessions = CanonicalTrainingQuery::sessions();
        SharedTrainingSchema::joinSessionCourse($sessions, type: 'left');
        $sessions = $sessions
            ->addSelect(DB::raw(SharedTrainingSchema::courseInternalExpression().' AS course_id'))
            ->addSelect(DB::raw(SharedTrainingSchema::courseNameExpression().' AS course_name'))
            ->addSelect(DB::raw(SharedTrainingSchema::courseModeExpression().' AS delivery_type'))
            ->orderByDesc('date')
            ->get()
            ->map(function ($session) use ($sessionTrainersMap, $trainerNames) {
                $session->session_type = ucfirst(strtolower(trim((string) $session->delivery_type)));
                $trainerIds = $sessionTrainersMap[$session->session_id] ?? [];
                $session->trainer_ids = $trainerIds;
                $names = [];
                foreach ($trainerIds as $trainerId) {
                    if (isset($trainerNames[$trainerId])) {
                        $names[] = $trainerNames[$trainerId];
                    }
                }
                $session->trainer_names = $names;

                return $session;
            });

        $courseOptions = $courses->pluck('course_name', 'course_id');
        $trainerOptions = $trainerNames;
        $sessionOptions = $sessions->mapWithKeys(function ($session) {
            $label = ($session->session_title ?: 'Session')
                .($session->course_name ? ' — '.$session->course_name : '');

            return [$session->session_id => $label];
        });
        $courseCategories = $courses->pluck('course_category')->filter()->unique()->sort()->values();
        $sessionTypes = $sessions->pluck('session_type')->filter()->unique()->sort()->values();
        $trainerStatuses = $trainers->pluck('status')->filter()->unique()->sort()->values();

        return view('admin.directory', compact(
            'participants',
            'trainers',
            'courses',
            'sessions',
            'courseOptions',
            'trainerOptions',
            'sessionOptions',
            'courseCategories',
            'sessionTypes',
            'trainerStatuses'
        ));
    }

    public function viewTrainerCertificate(string $trainerId, int $certId)
    {
        if (session('user_role') !== 'admin') {
            return redirect()->route('login')->with('error', 'Please login as admin first.');
        }

        abort_unless(Schema::hasTable('trainer_certificate'), 404);
        $trainer = SharedTrainingSchema::trainer($trainerId);
        abort_unless($trainer, 404);

        $certificate = DB::table('trainer_certificate')
            ->where('cert_id', $certId)
            ->where('trainer_id', $trainer->trainer_id)
            ->first();

        abort_unless($certificate, 404);

        $path = $this->certificateStoragePath($certificate->file_path ?? null);
        abort_unless($path && Storage::disk('public')->exists($path), 404);

        return Storage::disk('public')->response($path, basename($path));
    }

    private function certificateStoragePath(mixed $path): ?string
    {
        $normalized = str_replace('\\', '/', trim((string) $path));

        if ($normalized === ''
            || Str::startsWith($normalized, ['http://', 'https://', '/'])
            || str_contains($normalized, '..')
            || ! Str::startsWith($normalized, 'trainer_certs/')) {
            return null;
        }

        return $normalized;
    }
}
