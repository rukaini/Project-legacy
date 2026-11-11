<?php

namespace App\Http\Controllers;

use App\Models\TeacherAudit;
use App\Models\Teacher;
use App\Models\School;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SchoolController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->route('school.list');
    }

    // SCHOOL LIST + SEARCH
    public function list(Request $request): View
    {
        // Sync every school's totalTeacher before rendering the list
        // so the numbers on the cards are always current.
        $this->resyncAllSchoolCounts();

        $query = School::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('schoolName', 'like', "%$search%")
                  ->orWhere('schoolID', 'like', "%$search%")
                  ->orWhere('schoolAddress', 'like', "%$search%");
            });
        }

        $schools = $query->orderBy('schoolName')->get();

        return view('school.school-list', compact('schools'));
    }

    public function records(Request $request): View
    {
        $this->resyncAllSchoolCounts();

        $query = School::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('schoolName', 'like', "%$search%")
                  ->orWhere('schoolID', 'like', "%$search%")
                  ->orWhere('schoolAddress', 'like', "%$search%");
            });
        }

        $schools = $query->orderBy('schoolName')->get();

        return view('school.school-records', compact('schools'));
    }

    public function organizations(): RedirectResponse
    {
        return redirect()->route('orgs.index');
    }

    public function uploadForm(): View
    {
        return view('school.upload');
    }

    public function importCSV(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:5120'
        ]);

        $file = $request->file('file');

        try {
            $handle = fopen($file->getRealPath(), 'r');

            if (!$handle) {
                throw new \Exception('Cannot open the file');
            }

            $firstLine = fgets($handle);
            rewind($handle);
            $delimiter = strpos($firstLine, "\t") !== false ? "\t" : ",";
            $header = fgetcsv($handle, 0, $delimiter);

            if (!$header) {
                throw new \Exception('File is empty or invalid format');
            }

            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);

            $importedCount  = 0;
            $duplicateCount = 0;
            $invalidCount   = 0;
            $errors         = [];

            DB::beginTransaction();

            $rowNumber = 1;
            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                $rowNumber++;

                if (empty(trim($row[0] ?? '')) && empty(trim($row[1] ?? ''))) {
                    continue;
                }

                $schoolID      = !empty(trim($row[0] ?? '')) ? trim($row[0]) : null;
                $schoolName    = trim($row[1] ?? '');
                $schoolAddress = trim($row[2] ?? '');
                $registerDate  = !empty($row[3]) ? date('Y-m-d', strtotime($row[3])) : null;
                $phoneNumber   = !empty(trim($row[4] ?? '')) ? trim($row[4]) : null;
                // NOTE: totalTeacher from CSV is IGNORED — the real value is
                // derived from the `assign` table by updateSchoolTeacherCount().
                $capacity      = !empty($row[7]) && is_numeric($row[7]) ? (int)$row[7] : 15;

                if (empty($schoolID)) {
                    $schoolID = 'SCH' . strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $schoolName), 0, 8)) . rand(100, 999);
                }

                if (empty($schoolName)) {
                    $errors[] = "Row {$rowNumber}: Missing school name — skipped.";
                    $invalidCount++;
                    continue;
                }

                if (empty($schoolAddress)) {
                    $errors[] = "Row {$rowNumber}: Missing address for '{$schoolName}' — skipped.";
                    $invalidCount++;
                    continue;
                }

                // Duplicate check — SKIP if school exists
                $existingSchool = School::where('schoolID', $schoolID)->first();
                if ($existingSchool) {
                    $duplicateCount++;
                    $errors[] = "Row {$rowNumber} (ID: {$schoolID}): Already exists — skipped.";
                    continue;
                }

                try {
                    // Store 0 initially — updateSchoolTeacherCount() below will
                    // compute the real values from the assign table.
                    School::create([
                        'schoolID' => $schoolID,
                        'schoolName' => $schoolName,
                        'schoolAddress' => $schoolAddress,
                        'registerDate' => $registerDate,
                        'phoneNumber' => $phoneNumber,
                        'totalTeacher' => 0,
                        'vacancy' => $capacity,
                        'capacity' => $capacity,
                    ]);
                    $importedCount++;

                } catch (QueryException $e) {
                    if ($this->isDuplicateKeyError($e)) {
                        $duplicateCount++;
                        $errors[] = "Row {$rowNumber} (ID: {$schoolID}): Duplicate — skipped.";
                    } else {
                        $errors[] = "Row {$rowNumber} (ID: {$schoolID}): Database error — " . $this->friendlyDbMessage($e);
                        $invalidCount++;
                    }
                    Log::error("CSV Import DB Error at row {$rowNumber}: " . $e->getMessage(), ['schoolID' => $schoolID]);

                } catch (\Exception $e) {
                    $errors[] = "Row {$rowNumber} (ID: {$schoolID}): " . $e->getMessage();
                    $invalidCount++;
                    Log::error("CSV Import Error at row {$rowNumber}: " . $e->getMessage(), ['schoolID' => $schoolID]);
                }
            }

            DB::commit();
            fclose($handle);

            // Recompute totalTeacher + vacancy for every school based on actual assignments
            $this->resyncAllSchoolCounts();

            $message = "📥 Import completed — New schools: {$importedCount}";

            if ($duplicateCount > 0) {
                $message .= ", Duplicates skipped: {$duplicateCount}";
            }
            if ($invalidCount > 0) {
                $message .= ", Invalid rows: {$invalidCount}";
            }

            if ($importedCount === 0 && ($duplicateCount > 0 || $invalidCount > 0)) {
                $flashKey = 'error';
                $message = "⚠️ No new schools imported. " . $message;
            } elseif ($importedCount > 0 && ($duplicateCount > 0 || $invalidCount > 0)) {
                $flashKey = 'success';
                $message = "✅ Partial import. " . $message;
            } else {
                $flashKey = 'success';
                $message = "✅ " . $message;
            }

            $redirect = redirect()->route('school.list')->with($flashKey, $message);

            if (!empty($errors)) {
                Log::warning("CSV Import Warnings: " . implode("; ", array_slice($errors, 0, 20)));
                $redirect->with('import_errors', $errors);
            }

            return $redirect;

        } catch (\Exception $e) {
            DB::rollBack();

            if (isset($handle) && is_resource($handle)) {
                fclose($handle);
            }

            Log::error("CSV Import Exception: " . $e->getMessage());

            return redirect()
                ->back()
                ->withInput()
                ->with('error', '❌ Failed to import CSV: ' . $e->getMessage());
        }
    }

    public function downloadTemplate(): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="template_sekolah.csv"',
        ];

        $columns = [
            'schoolID',
            'schoolName',
            'schoolAddress',
            'registerDate',
            'phoneNumber',
            'totalTeacher (Auto)',
            'vacancy (Auto)',
            'capacity'
        ];

        $callback = function() use ($columns) {
            $file = fopen('php://output', 'w');

            // Add UTF-8 BOM for Excel compatibility
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($file, $columns);
            fputcsv($file, ['SCH001', 'Sekolah Kebangsaan Damai', 'Jalan Damai, Kuala Lumpur', '2024-01-01', '03-42567890', '10', '5', '15']);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'schoolID'      => 'required|string|max:50|unique:school,schoolID',
            'schoolName'    => 'required|string|max:255',
            'schoolAddress' => 'required|string',
            'registerDate'  => 'nullable|date',
            'phoneNumber'   => 'nullable|string|max:20',
            'capacity'      => 'required|integer|min:1',
        ], [
            'schoolID.unique' => 'A school with this ID already exists. Please use a different School ID.',
            'schoolID.required' => 'School ID is required.',
            'schoolName.required' => 'School name is required.',
            'schoolAddress.required' => 'School address is required.',
        ]);

        $capacity = (int) $request->capacity;

        try {
            DB::beginTransaction();

            // Insert with totalTeacher = 0 and vacancy = capacity.
            // updateSchoolTeacherCount() right after will set the real values
            // based on the actual teacher assignments.
            School::create([
                'schoolID' => $request->schoolID,
                'schoolName' => $request->schoolName,
                'schoolAddress' => $request->schoolAddress,
                'registerDate' => $request->registerDate,
                'phoneNumber' => $request->phoneNumber ?: null,
                'totalTeacher' => 0,
                'vacancy' => $capacity,
                'capacity' => $capacity,
            ]);

            DB::commit();

            // Compute real totalTeacher + vacancy from assignments
            $this->updateSchoolTeacherCount($request->schoolID);

            return redirect()
                ->route('school.list')
                ->with('success', "School '{$request->schoolName}' registered successfully!");

        } catch (QueryException $e) {
            DB::rollBack();
            Log::error('School store DB error: ' . $e->getMessage(), ['schoolID' => $request->schoolID]);

            if ($this->isDuplicateKeyError($e)) {
                return back()
                    ->withInput()
                    ->with('error', "❌ Duplicate School ID: '{$request->schoolID}' is already registered. Please use a unique School ID.");
            }

            return back()
                ->withInput()
                ->with('error', '❌ Database error: ' . $this->friendlyDbMessage($e));

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('School store error: ' . $e->getMessage(), ['schoolID' => $request->schoolID]);

            return back()
                ->withInput()
                ->with('error', '❌ Failed to register school: ' . $e->getMessage());
        }
    }

    public function edit($id): View
    {
        $school = School::where('schoolID', $id)->firstOrFail();
        return view('school.edit', compact('school'));
    }

    public function update(Request $request, $id): RedirectResponse
    {
        $id = urldecode($id);

        $school = School::where('schoolID', $id)->first();

        if (!$school) {
            return redirect()
                ->route('school.list')
                ->with('error', '❌ School not found.');
        }

        $request->validate([
            'schoolName'    => 'required|string|max:255',
            'schoolAddress' => 'required|string',
            'registerDate'  => 'nullable|date',
            'phoneNumber'   => 'nullable|string|max:20',
            'capacity'      => 'required|integer|min:1',
        ], [
            'schoolName.required' => 'School name is required.',
            'schoolAddress.required' => 'School address is required.',
        ]);

        try {
            DB::beginTransaction();

            $capacity = (int) $request->capacity;

            // Only update the fields the user can actually control.
            // totalTeacher and vacancy are derived from actual assignments.
            $data = [
                'schoolName'    => $request->schoolName,
                'schoolAddress' => $request->schoolAddress,
                'registerDate'  => $request->registerDate,
                'capacity'      => $capacity,
            ];

            $data['phoneNumber'] = $request->filled('phoneNumber') ? $request->phoneNumber : null;

            $school->update($data);

            DB::commit();

            // Recompute derived fields
            $this->updateSchoolTeacherCount($school->schoolID);

            $school->refresh();

            return redirect()
                ->route('school.show', $school->schoolID)
                ->with('success', "School updated successfully! Capacity: {$school->capacity}, Teachers: {$school->totalTeacher}, Vacancy: {$school->vacancy}");

        } catch (QueryException $e) {
            DB::rollBack();
            Log::error('School update DB error: ' . $e->getMessage(), ['schoolID' => $id]);

            if ($this->isDuplicateKeyError($e)) {
                return back()
                    ->withInput()
                    ->with('error', '❌ Duplicate entry detected. Please check your data.');
            }

            return back()
                ->withInput()
                ->with('error', '❌ Database error: ' . $this->friendlyDbMessage($e));

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('School update error: ' . $e->getMessage(), ['schoolID' => $id]);

            return back()
                ->withInput()
                ->with('error', '❌ Failed to update school: ' . $e->getMessage());
        }
    }

    public function destroy($id): RedirectResponse
    {
        try {
            $school = School::where('schoolID', $id)->firstOrFail();
            $name = $school->schoolName;

            // Check if school has active teachers assigned
            $teacherCount = DB::table('assign')
                ->where('schoolID', $id)
                ->where('status', 'Aktif')
                ->count();

            if ($teacherCount > 0) {
                return redirect()
                    ->route('school.records')
                    ->with('error', "❌ Cannot delete '{$name}': {$teacherCount} active teacher(s) still assigned. Please reassign or remove them first.");
            }

            $school->delete();

            return redirect()
                ->route('school.records')
                ->with('success', "School '{$name}' deleted successfully.");

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return redirect()
                ->route('school.records')
                ->with('error', '❌ School not found.');

        } catch (QueryException $e) {
            Log::error('School delete DB error: ' . $e->getMessage(), ['schoolID' => $id]);

            if ($this->isForeignKeyError($e)) {
                return redirect()
                    ->route('school.records')
                    ->with('error', '❌ Cannot delete school — it is referenced by other records (teachers, assignments, etc.).');
            }

            return redirect()
                ->route('school.records')
                ->with('error', '❌ Database error: ' . $this->friendlyDbMessage($e));

        } catch (\Exception $e) {
            Log::error('School delete error: ' . $e->getMessage(), ['schoolID' => $id]);

            return redirect()
                ->route('school.records')
                ->with('error', '❌ Failed to delete school: ' . $e->getMessage());
        }
    }

    public function show($id): View
    {
        $id = urldecode($id);

        $school = School::where('schoolID', $id)->first();

        if (!$school) {
            abort(404, 'School not found with ID: ' . $id);
        }

        $teachers = DB::table('teacher')
            ->join('assign', 'teacher.teacherID', '=', 'assign.teacherID')
            ->select('teacher.*', 'assign.status')
            ->where('assign.schoolID', $id)
            ->where('assign.status', '=', 'Aktif')
            ->orderBy('teacher.teacherName')
            ->get();

        $allTeachers = DB::table('teacher')
            ->join('assign', 'teacher.teacherID', '=', 'assign.teacherID')
            ->select('teacher.*', 'assign.status')
            ->where('assign.schoolID', $id)
            ->orderBy('teacher.teacherName')
            ->get();

        $totalTeachers    = $allTeachers->count();
        $activeTeachers   = $allTeachers->where('status', 'Aktif')->count();
        $inactiveTeachers = $allTeachers->where('status', 'Berhenti')->count();

        // Recompute cached columns
        $this->updateSchoolTeacherCount($id);

        $school = School::where('schoolID', $id)->first();

        return view('school.show', compact('school', 'teachers', 'totalTeachers', 'activeTeachers', 'inactiveTeachers'));
    }

    /**
     * Recalculate and update a single school's totalTeacher + vacancy columns
     * based on the current count of ACTIVE teachers assigned to it.
     */
    private function updateSchoolTeacherCount(string $schoolId): bool
    {
        try {
            $activeCount = DB::table('teacher')
                ->join('assign', 'teacher.teacherID', '=', 'assign.teacherID')
                ->where('assign.schoolID', $schoolId)
                ->where('assign.status', 'Aktif')
                ->count();

            $school = School::where('schoolID', $schoolId)->first();
            if (!$school) {
                return false;
            }

            $capacity = (int) ($school->capacity ?? 15);
            $vacancy  = max(0, $capacity - $activeCount);

            School::where('schoolID', $schoolId)
                ->update([
                    'totalTeacher' => $activeCount,
                    'vacancy'      => $vacancy,
                ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to update school teacher count for {$schoolId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Recompute the totalTeacher + vacancy columns for EVERY school.
     * Useful after bulk operations (import, teacher CSV import, etc.).
     */
    private function resyncAllSchoolCounts(): void
    {
        try {
            School::select('schoolID')->chunk(100, function ($schools) {
                foreach ($schools as $school) {
                    $this->updateSchoolTeacherCount($school->schoolID);
                }
            });
        } catch (\Exception $e) {
            Log::error('resyncAllSchoolCounts failed: ' . $e->getMessage());
        }
    }

    // ============================================================
    // HELPERS
    // ============================================================

    /**
     * Detect duplicate primary key / unique constraint errors
     */
    private function isDuplicateKeyError(QueryException $e): bool
    {
        $code = $e->getCode();
        $message = strtolower($e->getMessage());

        if ($code === '23000' || $code === 23000) {
            return true;
        }
        if (isset($e->errorInfo[1]) && (int) $e->errorInfo[1] === 1062) {
            return true;
        }
        if (isset($e->errorInfo[0]) && $e->errorInfo[0] === '23505') {
            return true;
        }

        foreach (['duplicate', 'unique constraint', 'unique violation', 'already exists'] as $kw) {
            if (str_contains($message, $kw)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Detect foreign key constraint violations
     */
    private function isForeignKeyError(QueryException $e): bool
    {
        $message = strtolower($e->getMessage());

        if (isset($e->errorInfo[1]) && (int) $e->errorInfo[1] === 1451) {
            return true;
        }

        foreach (['foreign key constraint', 'cannot delete or update a parent row'] as $kw) {
            if (str_contains($message, $kw)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Convert a raw QueryException into a friendly message
     */
    private function friendlyDbMessage(QueryException $e): string
    {
        $message = $e->getMessage();

        if (preg_match("/Duplicate entry '(.+?)' for key '(.+?)'/", $message, $matches)) {
            return "Duplicate entry '{$matches[1]}' — value already exists.";
        }

        $short = preg_replace('/\s+\(Connection:.*$/', '', $message);
        $short = preg_replace('/\s+\(SQL:.*$/', '', $short);

        return strlen($short) > 200 ? substr($short, 0, 200) . '...' : $short;
    }
}