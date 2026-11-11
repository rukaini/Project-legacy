<?php

namespace App\Integration\Repositories;

use App\Integration\Contracts\TeacherRepositoryInterface;
use App\Integration\DTOs\TeacherDTO;
use App\Models\Teacher;
use Illuminate\Support\Collection;

class LocalTeacherRepository implements TeacherRepositoryInterface
{
    public function findById(int|string $id): ?TeacherDTO
    {
        $teacher = Teacher::with('school')->find($id);

        return $teacher ? $this->toDTO($teacher) : null;
    }

    public function findByEmail(string $email): ?TeacherDTO
    {
        $teacher = Teacher::with('school')->where('email', $email)->first();

        return $teacher ? $this->toDTO($teacher) : null;
    }

    public function all(): Collection
    {
        return Teacher::with('school')->get()->map(fn ($t) => $this->toDTO($t));
    }

    public function allForDropdown(): Collection
    {
        return Teacher::select('teacherID', 'teacherName', 'email')->get()->map(fn ($t) => [
            'id'    => $t->teacherID,
            'name'  => $t->teacherName,
            'email' => $t->email,
        ]);
    }

    public function updateProfile(string $email, array $data): void
    {
        Teacher::where('email', $email)->update($data);
    }

    private function toDTO(Teacher $teacher): TeacherDTO
    {
        return new TeacherDTO(
            id:            $teacher->teacherID,
            name:          $teacher->teacherName,
            email:         $teacher->email,
            phoneNumber:   $teacher->phoneNumber,
            ICNumber:      $teacher->ICNumber,
            gender:        $teacher->gender,
            address:       $teacher->address,
            race:          $teacher->race,
            martialStatus: $teacher->martialStatus,
            appointedDate: $teacher->appointedDate,
            serviceDate:   $teacher->serviceDate,
            pensionDate:   $teacher->pensionDate,
            latestAge:     $teacher->latestAge,
            schoolID:      $teacher->schoolID,
            schoolName:    $teacher->school?->schoolName,
        );
    }
}
