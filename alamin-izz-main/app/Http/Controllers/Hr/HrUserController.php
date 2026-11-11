<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\GuruNew;
use App\Models\Outsider;
use App\Models\School;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HrUserController extends Controller
{
    public function teachers(Request $request): Response
    {
        $search   = $request->query('search', '');
        $schoolId = $request->query('school_id');

        $schools = School::with(['principals' => fn ($q) => $q->limit(1)])
            ->withCount('teachers')
            ->orderBy('schoolName')
            ->get()
            ->map(fn ($s) => [
                'id'            => $s->schoolID,
                'name'          => $s->schoolName,
                'teacher_count' => $s->teachers_count,
                'guru_besar'    => $s->principals->first()?->principalName ?? '—',
            ]);

        $teachers = $schoolId
            ? Teacher::whereHas('assigns', fn ($q) => $q->where('schoolID', $schoolId)->active())
                ->when($search, fn ($q) => $q->where('teacherName', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%"))
                ->orderBy('teacherName')
                ->get()
                ->map(fn ($t) => [
                    'id'     => $t->teacherID,
                    'name'   => $t->teacherName,
                    'email'  => $t->email,
                    'phone'  => $t->phoneNumber,
                    'gender' => $t->gender,
                ])
            : collect();

        $selectedSchool = $schoolId
            ? $schools->firstWhere('id', $schoolId)
            : null;

        return Inertia::render('hr/teachers/index', [
            'schools'         => $schools,
            'teachers'        => $teachers->values(),
            'selected_school' => $selectedSchool,
            'filters'         => compact('search', 'schoolId'),
        ]);
    }

    public function outsiders(Request $request): Response
    {
        $search = $request->query('search', '');

        $outsiders = Outsider::when($search, fn ($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('ICNumber', 'like', "%{$search}%"))
            ->orderBy('name')
            ->get()
            ->map(fn ($o) => [
                'id'     => $o->outsider_id,
                'name'   => $o->name,
                'ic'     => $o->ICNumber,
                'phone'  => $o->phoneNumber,
                'gender' => $o->gender,
            ]);

        return Inertia::render('hr/outsiders/index', [
            'outsiders' => $outsiders,
            'filters'   => compact('search'),
        ]);
    }

    public function guruBaru(Request $request): Response
    {
        $search = $request->query('search', '');

        $applicants = GuruNew::when($search, fn ($q) => $q->where('gn_name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%"))
            ->orderBy('gn_name')
            ->get()
            ->map(fn ($a) => [
                'id'    => $a->gn_id,
                'name'  => $a->gn_name,
                'email' => $a->email,
                'phone' => $a->phone_number,
            ]);

        return Inertia::render('hr/guru-baru/index', [
            'applicants' => $applicants,
            'filters'    => compact('search'),
        ]);
    }
}
