<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\Teacher;
use App\Models\TeacherAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TeacherController extends Controller
{
    /**
     * Display a listing of teachers grouped by school
     */
    public function index(Request $request)
    {
        $query = DB::table('teacher')
            ->leftJoin('assign', 'teacher.teacherID', '=', 'assign.teacherID')
            ->leftJoin('school', 'assign.schoolID', '=', 'school.schoolID')
            ->select(
                'teacher.*',
                'assign.status as status',
                'school.schoolName'
            )
            ->where('assign.status', '=', 'Aktif')
            ->where(function($q) {
                $q->whereNotNull('teacher.teacherID');
            });

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('teacher.teacherName', 'like', "%$search%")
                  ->orWhere('teacher.teacherID', 'like', "%$search%")
                  ->orWhere('assign.schoolID', 'like', "%$search%")
                  ->orWhere('school.schoolName', 'like', "%$search%");
            });
        }

        $teachers = $query->orderBy('assign.schoolID')
                      ->orderBy('teacher.teacherName')
                      ->get()
                      ->groupBy('schoolName');

        $schools = School::orderBy('schoolName')->get();

        return view('teacher.teacher', compact('teachers', 'schools'));
    }

    /**
     * Store a newly created teacher
     */
    public function store(Request $request)
    {
        $plainPassword = !empty($request->password) ? $request->password : 'password';
        $hashedPassword = bcrypt($plainPassword);
        
        Log::info('Creating teacher', [
            'teacherID' => $request->teacherID,
            'email' => $request->email,
            'using_default' => empty($request->password)
        ]);

        // serviceDate is set to null - trigger will calculate it
        DB::statement('CALL add_teacher(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [
            $request->teacherID,
            $request->teacherName,
            $request->ICNumber,
            $request->phoneNumber,
            $request->email,
            $request->maritalStatus,
            $request->gender,
            $request->address,
            $request->race,
            $request->appointedDate,
            null, // serviceDate - let trigger calculate
            $request->pensionDate,
            $request->latestAge,
            $request->schoolID,
            $hashedPassword
        ]);

        // Ensure role and password_change_required are set
        DB::table('teacher')
            ->where('teacherID', $request->teacherID)
            ->update([
                'role' => 'teacher',
                'password_change_required' => 1
            ]);

        // Verify the password was stored correctly
        $teacher = DB::table('teacher')->where('teacherID', $request->teacherID)->first();
        if ($teacher) {
            Log::info('Teacher created, verifying password', [
                'teacherID' => $teacher->teacherID,
                'hash_check' => Hash::check($plainPassword, $teacher->password)
            ]);
            
            if (!Hash::check($plainPassword, $teacher->password)) {
                DB::table('teacher')
                    ->where('teacherID', $request->teacherID)
                    ->update(['password' => bcrypt($plainPassword)]);
                
                Log::warning('Password forced update for teacher', ['teacherID' => $request->teacherID]);
            }
        }

        return back()->with('success', 'Teacher added successfully. Default password: ' . $plainPassword);
    }

    /**
     * Display the specified teacher
     */
    public function show(string $teacherID)
    {
        $teacher = DB::table('teacher')
            ->leftJoin('assign', 'teacher.teacherID', '=', 'assign.teacherID')
            ->leftJoin('school', 'assign.schoolID', '=', 'school.schoolID')
            ->select(
                'teacher.*',
                'school.schoolName',
                'school.schoolID',
                'assign.status as status',
                'assign.assignDate'
            )
            ->where('teacher.teacherID', $teacherID)
            ->first();

        if (!$teacher) {
            abort(404, 'Teacher not found');
        }

        return view('teacher.show', compact('teacher'));
    }
    
    /**
     * Display teacher details grouped by school
     */
    public function details(Request $request)
    {
        $query = DB::table('teacher')
            ->leftJoin('assign', 'teacher.teacherID', '=', 'assign.teacherID')
            ->leftJoin('school', 'assign.schoolID', '=', 'school.schoolID')
            ->select(
                'teacher.*', 
                'school.schoolName',
                'assign.status as status'
            );

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('teacher.teacherName', 'like', "%$search%")
                  ->orWhere('teacher.teacherID', 'like', "%$search%")
                  ->orWhere('assign.schoolID', 'like', "%$search%")
                  ->orWhere('school.schoolName', 'like', "%$search%");
            });
        }

        $teachers = $query->orderBy('school.schoolName')
                          ->orderBy('teacher.teacherName')
                          ->get()
                          ->groupBy('schoolName');

        return view('teacher.teacher-details', compact('teachers'));
    }

    /**
     * Terminate/stop a teacher
     */
    public function terminate(string $teacherID)
    {
        $oldData = DB::table('assign')->where('teacherID', $teacherID)->first();

        DB::table('assign')
            ->where('teacherID', $teacherID)
            ->update(['status' => 'Berhenti']);

        TeacherAudit::create([
            'teacherID'  => $teacherID,
            'action'     => 'Stop',
            'oldData'    => json_encode($oldData),
            'newData'    => json_encode(['status' => 'Berhenti']),
            'actionDate' => now(), 
        ]);

        return back()->with('status', 'Teacher stopped successfully.');
    }

    /**
     * Permanently delete a teacher record.
     */
    public function destroy(string $teacherID)
    {
        try {
            $teacher = Teacher::where('teacherID', $teacherID)->firstOrFail();
            $oldData = $teacher->toArray();

            // Remove related assignment records first to avoid orphaned rows
            DB::table('assign')->where('teacherID', $teacherID)->delete();

            $teacher->delete();

            TeacherAudit::create([
                'teacherID'  => $teacherID,
                'action'     => 'Delete',
                'oldData'    => json_encode($oldData),
                'newData'    => null,
                'actionDate' => now(),
            ]);

            return redirect()->route('teachers.index')->with('status', 'Teacher deleted successfully.');

        } catch (\Exception $e) {
            Log::error('Error deleting teacher: ' . $e->getMessage());
            return back()->with('error', 'Failed to delete teacher: ' . $e->getMessage());
        }
    }

    /**
     * Download CSV template for teacher import
     */
    public function template()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename=teacher_template.csv',
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, [
                'teacherID',
                'teacherName',
                'ICNumber',
                'phoneNumber',
                'email',
                'maritalStatus',
                'gender',
                'address',
                'race',
                'appointedDate',
                'serviceDate',
                'pensionDate',
                'latestAge',
                'schoolID'
            ]);
            
            fputcsv($file, [
                'T001',
                'Ahmad Bin Abdullah',
                '900101-01-1234',
                '0123456789',
                'ahmad@school.edu',
                'Single',
                'Male',
                '123 Jalan Example',
                'Malay',
                '2024-01-01',
                '', // Leave empty - will be auto-calculated
                '2044-01-01',
                '34',
                'SCH001'
            ]);
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Import teachers from CSV
     */
    public function import(Request $request)
    {
        $request->validate(['csvfile' => 'required|file|mimes:csv,txt']);
        
        try {
            DB::beginTransaction();
            $handle = fopen($request->file('csvfile')->getRealPath(), 'r');
            fgetcsv($handle); // Skip header

            while (($data = fgetcsv($handle)) !== false) {
                if (empty(array_filter($data))) continue;

                $teacherID = trim($data[0]);
                $schoolID = trim($data[13]); 
                $appointedDate = !empty($data[9]) ? date('Y-m-d', strtotime($data[9])) : null;
                
                $defaultPassword = bcrypt('password');

                // Insert/Update into 'teacher' table - serviceDate will be auto-calculated by trigger
                DB::table('teacher')->updateOrInsert(
                    ['teacherID' => $teacherID],
                    [
                        'teacherName'   => $data[1],
                        'ICNumber'      => $data[2],
                        'phoneNumber'   => $data[3],
                        'email'         => $data[4],
                        'maritalStatus' => $data[5],
                        'gender'        => $data[6],
                        'address'       => $data[7],
                        'race'          => $data[8],
                        'appointedDate' => $appointedDate,
                        'serviceDate'   => null, // Let trigger calculate this
                        'pensionDate'   => !empty($data[11]) ? date('Y-m-d', strtotime($data[11])) : null,
                        'latestAge'     => !empty($data[12]) ? intval($data[12]) : null,
                        'password'      => $defaultPassword,
                        'role'          => 'teacher',
                        'password_change_required' => 1
                    ]
                );

                // Insert into 'assign' table
                DB::table('assign')->updateOrInsert(
                    ['teacherID' => $teacherID],
                    [
                        'schoolID'   => $schoolID,
                        'assignDate' => $appointedDate,
                        'status'     => 'Aktif'
                    ]
                );
            }
            fclose($handle);
            DB::commit();
            return back()->with('status', 'Import successful. Default password is: password');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for editing a teacher
     */
    public function edit(string $teacherID)
    {
        // Use DB facade to get all teacher data including appointedDate
        $teacher = DB::table('teacher')
            ->leftJoin('assign', 'teacher.teacherID', '=', 'assign.teacherID')
            ->leftJoin('school', 'assign.schoolID', '=', 'school.schoolID')
            ->select(
                'teacher.*',
                'school.schoolName',
                'school.schoolID', // Keep as schoolID - no alias
                'assign.status as status'
            )
            ->where('teacher.teacherID', $teacherID)
            ->first();

        if (!$teacher) {
            abort(404, 'Teacher not found');
        }

        // Format appointedDate for the date input field (Y-m-d format)
        if ($teacher->appointedDate) {
            $teacher->appointedDate = Carbon::parse($teacher->appointedDate)->format('Y-m-d');
        }

        // Format pensionDate for the date input field (Y-m-d format)
        if ($teacher->pensionDate) {
            $teacher->pensionDate = Carbon::parse($teacher->pensionDate)->format('Y-m-d');
        }

        // Calculate service years if not set
        if (!$teacher->serviceDate && $teacher->appointedDate) {
            $teacher->serviceDate = Carbon::parse($teacher->appointedDate)->diffInYears(now());
        }

        $schools = School::orderBy('schoolName')->get();

        return view('teacher.edit', compact('teacher', 'schools'));
    }

    /**
     * Update the specified teacher
     */
    public function update(Request $request, string $teacherID)
    {
        // Get old data using DB facade
        $oldData = DB::table('teacher')
            ->where('teacherID', $teacherID)
            ->first();

        if (!$oldData) {
            abort(404, 'Teacher not found');
        }

        // Log the update
        Log::info('Updating teacher', [
            'teacherID' => $teacherID,
            'appointedDate' => $request->appointedDate,
            'serviceDate' => 'auto-calculated by trigger'
        ]);

        // Update using stored procedure
        // serviceDate is set to null - trigger will calculate it from appointedDate
        DB::statement("CALL update_teacher(?,?,?,?,?,?,?,?,?,?,?,?,?,?)", [
            $teacherID,
            $request->teacherName,
            $request->ICNumber,
            $request->phoneNumber,
            $request->email,
            $request->maritalStatus,
            $request->gender,
            $request->address,
            $request->race,
            $request->appointedDate,
            null, // serviceDate - let trigger calculate
            $request->pensionDate,
            $request->latestAge,
            $request->schoolID
        ]);

        // Get updated data
        $teacher = DB::table('teacher')
            ->where('teacherID', $teacherID)
            ->first();
        
        $updatedAssign = DB::table('assign')
            ->where('teacherID', $teacherID)
            ->first();
        
        $newData = (array) $teacher;
        $newData['status'] = $updatedAssign ? $updatedAssign->status : 'N/A';

        // Create audit log
        TeacherAudit::create([
            'teacherID'  => $teacherID,
            'action'     => 'Update Teacher Info',
            'oldData'    => json_encode($oldData),
            'newData'    => json_encode($newData),
            'actionDate' => now(), 
        ]);

        return redirect()->route('teachers.show', $teacherID)
                         ->with('status', 'Teacher updated successfully.');
    }

    /**
     * Display stopped teachers
     */
    public function stopped(Request $request)
    {
        $query = DB::table('teacher')
            ->join('assign', 'teacher.teacherID', '=', 'assign.teacherID')
            ->leftJoin('school', 'assign.schoolID', '=', 'school.schoolID')
            ->select('teacher.*', 'assign.status as status', 'school.schoolName')
            ->where('assign.status', '=', 'Berhenti');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('teacher.teacherName', 'like', "%$search%")
                  ->orWhere('teacher.teacherID', 'like', "%$search%");
            });
        }

        $teachers = $query->orderBy('school.schoolName')
                          ->orderBy('teacher.teacherName')
                          ->get()
                          ->groupBy('schoolName');

        return view('teacher.stopped', compact('teachers'));
    }

    /**
     * Fix inconsistent statuses
     */
    public function fixStatuses()
    {
        try {
            DB::table('assign')->where('status', 'Active')->update(['status' => 'Aktif']);
            DB::table('assign')->where('status', 'Inactive')->update(['status' => 'Berhenti']);
            DB::table('assign')->whereNull('status')->update(['status' => 'Aktif']);
            
            return response()->json(['message' => 'Statuses fixed successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Fix service years for all teachers (one-time fix)
     */
    public function fixServiceYears()
    {
        try {
            DB::statement("
                UPDATE teacher 
                SET serviceDate = TIMESTAMPDIFF(YEAR, appointedDate, CURDATE())
                WHERE appointedDate IS NOT NULL
            ");
            
            return response()->json([
                'message' => 'Service years updated successfully',
                'updated' => DB::table('teacher')->whereNotNull('appointedDate')->count()
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}