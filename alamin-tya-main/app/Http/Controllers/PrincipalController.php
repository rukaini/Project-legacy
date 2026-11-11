<?php

namespace App\Http\Controllers;

use App\Models\Principal;
use App\Models\School;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PrincipalController extends Controller
{
 public function index(Request $request): View
{
    $query = DB::table('principal')
        ->leftJoin('school', 'principal.schoolID', '=', 'school.schoolID')
        ->select('principal.*', 'school.schoolName')
        ->where(function ($q) {
            $q->where('principal.status', 'Aktif')
              ->orWhereNull('principal.status');
        });

    if ($request->filled('search')) {
        $search = $request->search;
        $query->where(function ($q) use ($search) {
            $q->where('principal.principalName', 'like', "%$search%")
              ->orWhere('principal.principalID', 'like', "%$search%")
              ->orWhere('school.schoolName', 'like', "%$search%");
        });
    }

    $principals = $query->orderBy('principal.principalName')
                        ->get()
                        ->groupBy('schoolID');

    // Get schools without principals for the "no principal" display
    $schoolsWithPrincipals = DB::table('principal')
        ->where('status', 'Aktif')
        ->orWhereNull('status')
        ->pluck('schoolID')
        ->unique()
        ->toArray();
    
    $schoolsWithoutPrincipal = School::whereNotIn('schoolID', $schoolsWithPrincipals)
        ->orderBy('schoolName')
        ->get();

    // Get available schools for registration (same as schools without principals)
    $availableSchools = $schoolsWithoutPrincipal;

    $schools = School::orderBy('schoolName')->get();

    return view('principal.principal-list', compact('principals', 'schools', 'schoolsWithoutPrincipal', 'availableSchools'));
}

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'principalID'   => 'required|unique:principal,principalID',
            'principalName' => 'required|string|max:255',
            'ICNumber'      => 'required|string|max:20',
            'phoneNumber'   => 'nullable|string|max:20',
            'email'         => 'nullable|email|max:100|unique:principal,email',
            'maritalStatus' => 'nullable|in:Single,Married,Divorced,Widowed',
            'gender'        => 'nullable|in:Male,Female',
            'address'       => 'nullable|string',
            'race'          => 'nullable|string|max:50',
            'appointedDate' => 'nullable|date',
            'serviceDate'   => 'nullable|integer|min:0',  // Changed to integer
            'pensionDate'   => 'nullable|date',
            'latestAge'     => 'nullable|integer|min:0|max:120',
            'schoolID'      => 'required|exists:school,schoolID',
        ]);

        try {
            DB::beginTransaction();

            $defaultPassword = Hash::make('password');
            
            DB::table('principal')->insert([
                'principalID' => $request->principalID,
                'principalName' => $request->principalName,
                'ICNumber' => $request->ICNumber,
                'phoneNumber' => $request->phoneNumber,
                'email' => $request->email,
                'maritalStatus' => $request->maritalStatus,
                'gender' => $request->gender,
                'address' => $request->address,
                'race' => $request->race,
                'appointedDate' => $request->appointedDate,
                'serviceDate' => $request->serviceDate,  // Now storing as integer
                'pensionDate' => $request->pensionDate,
                'latestAge' => $request->latestAge,
                'schoolID' => $request->schoolID,
                'password' => $defaultPassword,
                'password_change_required' => 1,
                'role' => 'principal',
                'status' => 'Aktif'
            ]);

            DB::commit();

            return redirect()->route('principals.list')
                           ->with('status', 'Principal registered successfully! Default password: password');

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Principal registration failed: ' . $e->getMessage());
            
            return back()->withInput()
                         ->with('error', 'Failed to register principal: ' . $e->getMessage());
        }
    }

    /**
     * ALTERNATIVE: Direct Insert without Stored Procedure
     */
    public function storeDirect(Request $request): RedirectResponse
    {
        $request->validate([
            'principalID'   => 'required|unique:principal,principalID',
            'principalName' => 'required|string|max:255',
            'ICNumber'      => 'required|string|max:20',
            'phoneNumber'   => 'nullable|string|max:20',
            'email'         => 'nullable|email|max:100|unique:principal,email',
            'maritalStatus' => 'nullable|in:Single,Married,Divorced,Widowed',
            'gender'        => 'nullable|in:Male,Female',
            'address'       => 'nullable|string',
            'race'          => 'nullable|string|max:50',
            'appointedDate' => 'nullable|date',
            'serviceDate'   => 'nullable|integer|min:0',  // Changed to integer
            'pensionDate'   => 'nullable|date',
            'latestAge'     => 'nullable|integer|min:0|max:120',
            'schoolID'      => 'required|exists:school,schoolID',
        ]);

        try {
            DB::beginTransaction();

            DB::table('principal')->insert([
                'principalID' => $request->principalID,
                'principalName' => $request->principalName,
                'ICNumber' => $request->ICNumber,
                'phoneNumber' => $request->phoneNumber,
                'email' => $request->email,
                'maritalStatus' => $request->maritalStatus,
                'gender' => $request->gender,
                'address' => $request->address,
                'race' => $request->race,
                'appointedDate' => $request->appointedDate,
                'serviceDate' => $request->serviceDate,  // Now storing as integer
                'pensionDate' => $request->pensionDate,
                'latestAge' => $request->latestAge,
                'schoolID' => $request->schoolID,
                'password' => Hash::make('password'),
                'password_change_required' => 1,
                'role' => 'principal',
                'status' => 'Aktif'
            ]);

            DB::commit();

            return redirect()->route('principals.index')
                           ->with('status', 'Principal registered successfully! Default password: password');

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Principal registration failed: ' . $e->getMessage());
            
            return back()->withInput()
                         ->with('error', 'Failed to register principal: ' . $e->getMessage());
        }
    }

    /**
     * SHOW PRINCIPAL DETAILS
     */
    public function show(string $principalID): View
    {
        $principal = DB::table('principal')
            ->leftJoin('school', 'principal.schoolID', '=', 'school.schoolID')
            ->select('principal.*', 'school.schoolName')
            ->where('principal.principalID', $principalID)
            ->first();

        if (!$principal) {
            abort(404, 'Principal not found');
        }

        return view('principal.principal-details', compact('principal'));
    }

    /**
     * EDIT PRINCIPAL
     */
    public function edit(string $principalID): View
    {
        $principal = DB::table('principal')->where('principalID', $principalID)->first();
        
        if (!$principal) {
            abort(404, 'Principal not found');
        }
        
        $schools = School::all();
        return view('principal.edit', compact('principal', 'schools'));
    }

    /**
     * UPDATE PRINCIPAL
     */
    public function update(Request $request, string $principalID): RedirectResponse
    {
        $request->validate([
            'principalName' => 'required|string|max:255',
            'ICNumber'      => 'required|string|max:20',
            'phoneNumber'   => 'nullable|string|max:20',
            'email'         => 'nullable|email|max:100|unique:principal,email,' . $principalID . ',principalID',
            'maritalStatus' => 'nullable|in:Single,Married,Divorced,Widowed',
            'gender'        => 'nullable|in:Male,Female',
            'address'       => 'nullable|string',
            'race'          => 'nullable|string|max:50',
            'appointedDate' => 'nullable|date',
            'serviceDate'   => 'nullable|integer|min:0',  // Changed to integer
            'pensionDate'   => 'nullable|date',
            'latestAge'     => 'nullable|integer|min:0|max:120',
            'schoolID'      => 'required|exists:school,schoolID',
            'status'        => 'nullable|in:Aktif,Berhenti',
            'password_change_required' => 'nullable|boolean',
        ]);

        try {
            DB::table('principal')
                ->where('principalID', $principalID)
                ->update([
                    'principalName' => $request->principalName,
                    'ICNumber' => $request->ICNumber,
                    'phoneNumber' => $request->phoneNumber,
                    'email' => $request->email,
                    'maritalStatus' => $request->maritalStatus,
                    'gender' => $request->gender,
                    'address' => $request->address,
                    'race' => $request->race,
                    'appointedDate' => $request->appointedDate,
                    'serviceDate' => $request->serviceDate,  // Now storing as integer
                    'pensionDate' => $request->pensionDate,
                    'latestAge' => $request->latestAge,
                    'schoolID' => $request->schoolID,
                    'status' => $request->status ?? 'Aktif',
                    'password_change_required' => $request->has('password_change_required') ? 1 : 0,
                ]);

            return redirect()->route('principals.show', $principalID)
                           ->with('status', 'Principal profile updated successfully!');

        } catch (\Exception $e) {
            return back()->withInput()
                         ->with('error', 'Failed to update principal: ' . $e->getMessage());
        }
    }

    /**
     * TERMINATE PRINCIPAL
     */
    public function terminate(string $principalID): RedirectResponse
    {
        try {
            DB::table('principal')
                ->where('principalID', $principalID)
                ->update(['status' => 'Berhenti']);

            return back()->with('status', 'Principal terminated successfully.');

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to terminate principal: ' . $e->getMessage());
        }
    }

    /**
     * DELETE PRINCIPAL (permanent)
     */
    public function destroy(string $principalID): RedirectResponse
    {
        try {
            $principal = Principal::where('principalID', $principalID)->firstOrFail();
            $principal->delete();

            return redirect()->route('principal.index')->with('status', 'Principal deleted successfully.');

        } catch (\Exception $e) {
            Log::error('Error deleting principal: ' . $e->getMessage());
            return back()->with('error', 'Failed to delete principal: ' . $e->getMessage());
        }
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'csvfile' => 'required|file|mimes:csv,txt,xlsx|max:2048'
        ]);

        $file = fopen($request->file('csvfile')->getRealPath(), 'r');
        
        // Skip header row
        $header = fgetcsv($file);
        
        $successCount = 0;
        $errorCount = 0;
        $errors = [];

        try {
            $rowNumber = 1;
            
            while (($row = fgetcsv($file, 1000, ",")) !== false) {
                $rowNumber++;
                
                // Skip completely empty rows
                if (empty(array_filter($row))) {
                    continue;
                }
                
                // Extract data with proper trimming
                $principalID = trim($row[0] ?? '');
                $principalName = trim($row[1] ?? '');
                $ICNumber = trim($row[2] ?? '');
                $phoneNumber = trim($row[3] ?? null);
                $email = trim($row[4] ?? null);
                $maritalStatus = trim($row[5] ?? null);
                $gender = trim($row[6] ?? null);
                $address = trim($row[7] ?? null);
                $race = trim($row[8] ?? null);
                $appointedDate = !empty($row[9]) ? date('Y-m-d', strtotime($row[9])) : null;
                $serviceDate = !empty($row[10]) ? (int)$row[10] : null;  // Changed to integer
                $pensionDate = !empty($row[11]) ? date('Y-m-d', strtotime($row[11])) : null;
                $latestAge = !empty($row[12]) ? (int)$row[12] : null;
                $schoolID = trim($row[13] ?? '');
                
                // Validate required fields
                if (empty($principalID)) {
                    $errorCount++;
                    $errors[] = "Row $rowNumber: Principal ID is required";
                    continue;
                }
                
                if (empty($principalName)) {
                    $errorCount++;
                    $errors[] = "Row $rowNumber: Principal Name is required";
                    continue;
                }
                
                if (empty($ICNumber)) {
                    $errorCount++;
                    $errors[] = "Row $rowNumber: IC Number is required";
                    continue;
                }
                
                if (empty($schoolID)) {
                    $errorCount++;
                    $errors[] = "Row $rowNumber: School ID is required";
                    continue;
                }
                
                // Check if principal already exists
                $exists = DB::table('principal')->where('principalID', $principalID)->exists();
                if ($exists) {
                    $errorCount++;
                    $errors[] = "Row $rowNumber: Principal ID '$principalID' already exists";
                    continue;
                }
                
                // Check if school exists
                $schoolExists = DB::table('school')->where('schoolID', $schoolID)->exists();
                if (!$schoolExists) {
                    $errorCount++;
                    $errors[] = "Row $rowNumber: School ID '$schoolID' does not exist in the system";
                    continue;
                }
                
                // Validate date formats
                if (!empty($row[9]) && $appointedDate === false) {
                    $errorCount++;
                    $errors[] = "Row $rowNumber: Invalid Appointed Date format (use YYYY-MM-DD)";
                    continue;
                }
                
                if (!empty($row[11]) && $pensionDate === false) {
                    $errorCount++;
                    $errors[] = "Row $rowNumber: Invalid Pension Date format (use YYYY-MM-DD)";
                    continue;
                }
                
                try {
                    DB::table('principal')->insert([
                        'principalID' => $principalID,
                        'principalName' => $principalName,
                        'ICNumber' => $ICNumber,
                        'phoneNumber' => $phoneNumber ?: null,
                        'email' => $email ?: null,
                        'maritalStatus' => $maritalStatus ?: null,
                        'gender' => $gender ?: null,
                        'address' => $address ?: null,
                        'race' => $race ?: null,
                        'appointedDate' => $appointedDate,
                        'serviceDate' => $serviceDate,  // Now storing as integer
                        'pensionDate' => $pensionDate,
                        'latestAge' => $latestAge,
                        'schoolID' => $schoolID,
                        'password' => Hash::make('password'),
                        'password_change_required' => 1,
                        'role' => 'principal',
                        'status' => 'Aktif'
                    ]);
                    $successCount++;
                    
                } catch (\Exception $e) {
                    $errorCount++;
                    $errors[] = "Row $rowNumber: " . $e->getMessage();
                    Log::warning("Failed to import principal row $rowNumber: " . $e->getMessage());
                }
            }

            fclose($file);
            
            if ($errorCount > 0) {
                session()->flash('import_errors', $errors);
                
                $message = "Import completed! Success: $successCount, Failed: $errorCount";
                if ($successCount > 0) {
                    return back()->with('status', $message)->with('import_errors', $errors);
                } else {
                    return back()->with('error', $message . ' No records were imported.')->with('import_errors', $errors);
                }
            }
            
            return back()->with('status', "Import completed successfully! $successCount records added.");

        } catch (\Exception $e) {
            fclose($file);
            Log::error('CSV Import failed: ' . $e->getMessage());
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    /**
     * DOWNLOAD TEMPLATE CSV - Updated with serviceDate as integer
     */
    public function template(): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename=principal_template.csv',
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');
            
            // Add UTF-8 BOM for Excel compatibility
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Headers matching your table structure - mark required fields with *
            fputcsv($file, [
                'principalID*',
                'principalName*', 
                'ICNumber*',
                'phoneNumber',
                'email',
                'maritalStatus',
                'gender',
                'address',
                'race',
                'appointedDate (YYYY-MM-DD)',
                'serviceDate (Years - Number)',  // Changed to indicate years
                'pensionDate (YYYY-MM-DD)',
                'latestAge',
                'schoolID*'
            ]);

            // Add example row with real data
            fputcsv($file, [
                'PR001',
                'Ahmad Bin Abdullah',
                '750101101234',
                '0123456789',
                'ahmad@school.edu',
                'Married',
                'Male',
                'No 1, Jalan School, Kuala Lumpur',
                'Malay',
                '2020-01-15',
                '30',  // Service years as integer
                '2035-01-15',
                '50',
                'SCH001'
            ]);
            
            // Add a second example
            fputcsv($file, [
                'PR002',
                'Siti Binti Hassan',
                '800505085678',
                '0112345678',
                'siti@school.edu',
                'Single',
                'Female',
                'No 2, Jalan Pendidikan, Selangor',
                'Malay',
                '2021-06-01',
                '25',  // Service years as integer
                '2036-06-01',
                '45',
                'SCH002'
            ]);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * STOPPED LIST - Show terminated/resigned principals
     */
    public function stopped(Request $request): View
    {
        $query = DB::table('principal')
            ->leftJoin('school', 'principal.schoolID', '=', 'school.schoolID')
            ->select('principal.*', 'school.schoolName')
            ->where('principal.status', 'Berhenti');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('principal.principalName', 'like', "%$search%")
                  ->orWhere('principal.principalID', 'like', "%$search%")
                  ->orWhere('school.schoolName', 'like', "%$search%");
            });
        }

        $principals = $query->orderBy('principal.principalName')
                            ->get()
                            ->groupBy('schoolName');

        return view('principal.stopped', compact('principals'));
    }
    
    public function showTeacher(string $teacherID): View|RedirectResponse
    {
        if (!Session::has('user')) {
            return redirect()->route('login')->with('error', 'Session expired. Please login again.');
        }
        
        $principal = Session::get('user');
        
        if (!isset($principal->role) || strtolower($principal->role) !== 'principal') {
            abort(403, 'Access denied. Only principals can view teacher details.');
        }
        
        if (empty($principal->schoolID)) {
            abort(404, 'School not found for this principal account.');
        }
        
        $teacher = Teacher::join('ASSIGN', 'Teacher.teacherID', '=', 'ASSIGN.teacherID')
                      ->where('Teacher.teacherID', $teacherID)
                      ->where('ASSIGN.schoolID', $principal->schoolID)
                      ->select('Teacher.*', 'ASSIGN.status', 'ASSIGN.assignDate')
                      ->first();
        
        if (!$teacher) {
            abort(404, 'Teacher not found or does not belong to your school.');
        }
        
        return view('principal.teacher-details', compact('teacher', 'principal'));
    }

    public function exportTeachers(): StreamedResponse|RedirectResponse
    {
        if (!Session::has('user')) {
            return redirect()->route('login')->with('error', 'Please login first.');
        }
        
        $principal = Session::get('user');
        
        if (!isset($principal->role) || strtolower($principal->role) !== 'principal') {
            abort(403, 'Unauthorized access.');
        }
        
        $teachers = Teacher::where('schoolID', $principal->schoolID)
                          ->orderBy('teacherName')
                          ->get();
        
        $school = School::find($principal->schoolID);
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename=teachers_' . preg_replace('/[^a-zA-Z0-9]/', '_', $school->schoolName) . '.csv',
        ];
        
        $callback = function () use ($teachers) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            fputcsv($file, [
                'Teacher ID', 'Teacher Name', 'IC Number', 'Phone Number', 'Email',
                'Gender', 'Race', 'Marital Status', 'Address', 'Status',
                'Appointed Date', 'Service Years', 'Pension Date'  // Changed to Service Years
            ]);
            
            foreach ($teachers as $teacher) {
                fputcsv($file, [
                    $teacher->teacherID, 
                    $teacher->teacherName, 
                    $teacher->ICNumber ?? '',
                    $teacher->phoneNumber ?? '', 
                    $teacher->email ?? '', 
                    $teacher->gender ?? '',
                    $teacher->race ?? '', 
                    $teacher->maritalStatus ?? '', 
                    $teacher->address ?? '',
                    $teacher->status ?? 'Active', 
                    $teacher->appointedDate ?? '',
                    $teacher->serviceDate ?? '',  // Now shows years as integer
                    $teacher->pensionDate ?? ''
                ]);
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }

    public function getTeacherStats(): JsonResponse|RedirectResponse
    {
        if (!Session::has('user')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        $principal = Session::get('user');
        
        if (!isset($principal->role) || strtolower($principal->role) !== 'principal') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        $stats = [
            'total' => Teacher::where('schoolID', $principal->schoolID)->count(),
            'active' => Teacher::where('schoolID', $principal->schoolID)
                              ->where(function($q) {
                                  $q->where('status', 'Aktif')
                                    ->orWhereNull('status');
                              })->count(),
            'inactive' => Teacher::where('schoolID', $principal->schoolID)
                                ->where('status', 'Berhenti')
                                ->count(),
            'male' => Teacher::where('schoolID', $principal->schoolID)
                            ->where('gender', 'Lelaki')
                            ->count(),
            'female' => Teacher::where('schoolID', $principal->schoolID)
                              ->where('gender', 'Perempuan')
                              ->count(),
        ];
        
        return response()->json($stats);
    }

    public function calendar(): View
    {
        return view('principal.calendar');
    }

   public function teacherList(Request $request): View|RedirectResponse
{
    if (!Session::has('user')) {
        return redirect()->route('login')->with('error', 'Please login first.');
    }
    
    $principal = Session::get('user');
    
    if (!isset($principal->role) || strtolower($principal->role) !== 'principal') {
        abort(403, 'Unauthorized access.');
    }
    
    if (!$principal->schoolID) {
        return back()->with('error', 'Your account is not assigned to any school.');
    }

    // Get ALL teachers for statistics (including resigned if needed)
    $allTeachersQuery = Teacher::join('ASSIGN', 'Teacher.teacherID', '=', 'ASSIGN.teacherID')
                        ->where('ASSIGN.schoolID', $principal->schoolID)
                        ->select('Teacher.*', 'ASSIGN.status', 'ASSIGN.assignDate');
    
    // Get all teachers for statistics
    $allTeachers = $allTeachersQuery->get();
    
    // Calculate statistics from ALL teachers
    $totalTeachers = $allTeachers->count();
    $activeTeachers = $allTeachers->filter(function($teacher) {
        $status = strtolower($teacher->status ?? '');
        return empty($status) || $status == 'aktif' || $status == 'active';
    })->count();
    
    $inactiveTeachers = $allTeachers->filter(function($teacher) {
        $status = strtolower($teacher->status ?? '');
        return $status == 'berhenti' || $status == 'inactive' || $status == 'stopped';
    })->count();
    
    $maleTeachers = $allTeachers->filter(function($teacher) {
        $gender = strtolower($teacher->gender ?? '');
        return in_array($gender, ['male', 'lelaki', 'm']);
    })->count();
    
    $femaleTeachers = $allTeachers->filter(function($teacher) {
        $gender = strtolower($teacher->gender ?? '');
        return in_array($gender, ['female', 'perempuan', 'f']);
    })->count();
    
    // Build the paginated query for display
    $query = Teacher::join('ASSIGN', 'Teacher.teacherID', '=', 'ASSIGN.teacherID')
                ->where('ASSIGN.schoolID', $principal->schoolID)
                ->select('Teacher.*', 'ASSIGN.status', 'ASSIGN.assignDate');
    
    // Apply status filter for display
    if ($request->filled('status')) {
        $query->where('ASSIGN.status', $request->status);
    } else {
        // Default: Show active teachers only
        $query->where(function($q) {
            $q->where('ASSIGN.status', 'Aktif')
              ->orWhereNull('ASSIGN.status');
        });
    }
    
    if ($request->filled('search')) {
        $search = $request->search;
        $query->where(function ($q) use ($search) {
            $q->where('Teacher.teacherName', 'like', "%{$search}%")
              ->orWhere('Teacher.teacherID', 'like', "%{$search}%")
              ->orWhere('Teacher.ICNumber', 'like', "%{$search}%")
              ->orWhere('Teacher.email', 'like', "%{$search}%");
        });
    }
    
    $school = School::find($principal->schoolID);
    $teachers = $query->orderBy('Teacher.teacherName')->paginate(10)->withQueryString();
    
    // Pass statistics to the view
    return view('principal.teacher-list', compact(
        'teachers', 
        'school', 
        'principal',
        'totalTeachers',
        'activeTeachers',
        'inactiveTeachers',
        'maleTeachers',
        'femaleTeachers'
    ));
}

/**
 * Get schools without a principal
 */
private function getAvailableSchools()
{
    $schoolsWithPrincipals = DB::table('principal')
        ->where('status', 'Aktif')
        ->orWhereNull('status')
        ->pluck('schoolID')
        ->unique()
        ->toArray();
    
    return School::whereNotIn('schoolID', $schoolsWithPrincipals)
        ->orderBy('schoolName')
        ->get();
}

    public function resignList(Request $request): View|RedirectResponse
    {
        if (!Session::has('user')) {
            return redirect()->route('login')->with('error', 'Please login first.');
        }
        
        $principal = Session::get('user');
        
        if (!isset($principal->role) || strtolower($principal->role) !== 'principal') {
            abort(403, 'Unauthorized access.');
        }
        
        $query = Teacher::join('ASSIGN', 'Teacher.teacherID', '=', 'ASSIGN.teacherID')
                    ->where('ASSIGN.schoolID', $principal->schoolID)
                    ->where('ASSIGN.status', 'Berhenti')
                    ->select('Teacher.*', 'ASSIGN.status', 'ASSIGN.assignDate');
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('Teacher.teacherName', 'like', "%{$search}%")
                  ->orWhere('Teacher.teacherID', 'like', "%{$search}%")
                  ->orWhere('Teacher.ICNumber', 'like', "%{$search}%");
            });
        }
        
        $school = School::find($principal->schoolID);
        $resignedTeachers = $query->orderBy('Teacher.teacherName')->paginate(10)->withQueryString();
        
        $stats = [
            'total_resigned' => Teacher::join('ASSIGN', 'Teacher.teacherID', '=', 'ASSIGN.teacherID')
                                   ->where('ASSIGN.schoolID', $principal->schoolID)
                                   ->where('ASSIGN.status', 'Berhenti')
                                   ->count(),
        ];
        
        return view('principal.resign', compact('resignedTeachers', 'school', 'principal', 'stats'));
    }

    public function showResignedTeacher(string $teacherID): View|RedirectResponse
    {
        if (!Session::has('user')) {
            return redirect()->route('login')->with('error', 'Please login first.');
        }
        
        $principal = Session::get('user');
        
        if (!isset($principal->role) || strtolower($principal->role) !== 'principal') {
            abort(403, 'Unauthorized access. Only principals can view resigned teacher details.');
        }
        
        if (empty($principal->schoolID)) {
            abort(404, 'School not found for this principal account.');
        }
        
        $teacher = Teacher::join('ASSIGN', 'Teacher.teacherID', '=', 'ASSIGN.teacherID')
                      ->where('Teacher.teacherID', $teacherID)
                      ->where('ASSIGN.schoolID', $principal->schoolID)
                      ->where('ASSIGN.status', 'Berhenti')
                      ->select('Teacher.*', 'ASSIGN.status as assign_status', 'ASSIGN.assignDate')
                      ->first();
        
        if (!$teacher) {
            abort(404, 'Resigned teacher not found or does not belong to your school.');
        }
        
        return view('principal.resigned-teacher-details', compact('teacher', 'principal'));
    }

    public function exportResignedTeachers(): StreamedResponse|RedirectResponse
    {
        if (!Session::has('user')) {
            return redirect()->route('login')->with('error', 'Please login first.');
        }
        
        $principal = Session::get('user');
        
        if (!isset($principal->role) || strtolower($principal->role) !== 'principal') {
            abort(403, 'Unauthorized access.');
        }
        
        $teachers = Teacher::join('ASSIGN', 'Teacher.teacherID', '=', 'ASSIGN.teacherID')
                      ->where('ASSIGN.schoolID', $principal->schoolID)
                      ->where('ASSIGN.status', 'Berhenti')
                      ->orderBy('Teacher.teacherName')
                      ->select('Teacher.*', 'ASSIGN.status', 'ASSIGN.assignDate')
                      ->get();
        
        $school = School::find($principal->schoolID);
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename=resigned_teachers_' . preg_replace('/[^a-zA-Z0-9]/', '_', $school->schoolName) . '.csv',
        ];
        
        $callback = function () use ($teachers) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            fputcsv($file, ['Teacher ID', 'Teacher Name', 'IC Number', 'Phone Number', 'Email', 'Gender', 'Status', 'Assign Date']);
            
            foreach ($teachers as $teacher) {
                fputcsv($file, [
                    $teacher->teacherID, 
                    $teacher->teacherName, 
                    $teacher->ICNumber ?? '',
                    $teacher->phoneNumber ?? '', 
                    $teacher->email ?? '', 
                    $teacher->gender ?? '',
                    $teacher->status ?? 'Berhenti', 
                    $teacher->assignDate ?? ''
                ]);
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
}