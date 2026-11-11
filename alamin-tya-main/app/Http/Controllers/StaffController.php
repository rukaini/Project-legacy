<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\QueryException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Carbon\Carbon;

class StaffController extends Controller
{
    /**
     * LIST STAFF - Group by department
     */
    public function index(Request $request): View
    {
        $query = DB::table('staff')
            ->where(function ($q) {
                $q->where('status', 'active')
                  ->orWhere('status', 'Aktif')
                  ->orWhereNull('status');
            });

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('staffName', 'like', "%$search%")
                  ->orWhere('staffID', 'like', "%$search%")
                  ->orWhere('ICNumber', 'like', "%$search%")
                  ->orWhere('email', 'like', "%$search%");
            });
        }

        $staffs = $query->orderBy('staffName')
                        ->get()
                        ->groupBy('department');

        return view('staff.staff-list', compact('staffs'));
    }

    /**
     * STORE STAFF
     */
 public function store(Request $request): RedirectResponse
{
    $request->validate([
        'staffID'       => 'required|string|max:50|unique:staff,staffID',
        'staffName'     => 'required|string|max:255',
        'ICNumber'      => 'required|string|max:20',
        'phoneNumber'   => 'nullable|string|max:20',
        'email'         => 'nullable|email|max:100|unique:staff,email',
        'maritalStatus' => 'nullable|in:Single,Married,Divorced,Widowed',
        'gender'        => 'nullable|in:Male,Female',
        'address'       => 'nullable|string',
        'race'          => 'nullable|string|max:50',
        'appointedDate' => 'nullable|date',
        'serviceDate'   => 'nullable|integer|min:0',
        'pensionDate'   => 'nullable|date',
        'latestAge'     => 'nullable|integer|min:0|max:120',
        'department'    => 'required|string|max:100',
        'otherRace'     => 'nullable|string|max:50',
    ], [
        'staffID.unique'        => 'A staff member with this ID already exists. Please use a unique Staff ID.',
        'staffID.required'      => 'Staff ID is required.',
        'staffName.required'    => 'Staff name is required.',
        'ICNumber.required'     => 'IC Number is required.',
        'email.unique'          => 'This email is already registered to another staff member.',
        'email.email'           => 'Please enter a valid email address.',
        'department.required'   => 'Please select a department.',
    ]);

    try {
        DB::beginTransaction();

        $race = $request->race;
        if ($request->race == 'Other' && $request->filled('otherRace')) {
            $race = $request->otherRace;
        }

        $defaultPassword = Hash::make('password');

        $serviceYears = null;
        if ($request->appointedDate) {
            $serviceYears = Carbon::parse($request->appointedDate)->diffInYears(now());
        }

        DB::table('staff')->insert([
            'staffID' => $request->staffID,
            'staffName' => $request->staffName,
            'ICNumber' => $request->ICNumber,
            'phoneNumber' => $request->phoneNumber,
            'email' => $request->email,
            'maritalStatus' => $request->maritalStatus,
            'gender' => $request->gender,
            'address' => $request->address,
            'race' => $race,
            'appointedDate' => $request->appointedDate,
            'serviceDate' => $serviceYears,
            'pensionDate' => $request->pensionDate,
            'latestAge' => $request->latestAge,
            'department' => $request->department,
            'password' => $defaultPassword,
            'password_change_required' => 1,
            'role' => 'staff',
            'status' => 'active',
            'credit_hour' => $request->credit_hour ?? 0,
        ]);

        DB::commit();

        return redirect()->route('staff.list')
                       ->with('status', 'Staff registered successfully! Default password: password');

    } catch (QueryException $e) {
        DB::rollBack();
        Log::error('Staff registration DB error: ' . $e->getMessage(), ['staffID' => $request->staffID]);

        if ($this->isDuplicateKeyError($e)) {
            return back()
                ->withInput()
                ->with('error', "❌ Duplicate Staff ID: '{$request->staffID}' is already registered. Please use a unique Staff ID.");
        }

        return back()
            ->withInput()
            ->with('error', '❌ Database error: ' . $this->friendlyDbMessage($e));

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Staff registration failed: ' . $e->getMessage());

        return back()
            ->withInput()
            ->with('error', '❌ Failed to register staff: ' . $e->getMessage());
    }
}

    /**
     * SHOW STAFF DETAILS
     */
    public function show($staffID): View|RedirectResponse
    {
        $staff = DB::table('staff')->where('staffID', $staffID)->first();

        if (!$staff) {
            return redirect()->route('staff.list')->with('error', '❌ Staff not found.');
        }

        return view('staff.show', compact('staff'));
    }

    /**
     * EDIT STAFF
     */
    public function edit($staffID): View|RedirectResponse
    {
        $staff = DB::table('staff')->where('staffID', $staffID)->first();

        if (!$staff) {
            return redirect()->route('staff.list')->with('error', '❌ Staff not found.');
        }

        if ($staff->appointedDate) {
            $staff->appointedDate = Carbon::parse($staff->appointedDate)->format('Y-m-d');
        }

        if ($staff->pensionDate) {
            $staff->pensionDate = Carbon::parse($staff->pensionDate)->format('Y-m-d');
        }

        if (!$staff->serviceDate && $staff->appointedDate) {
            $staff->serviceDate = Carbon::parse($staff->appointedDate)->diffInYears(now());
        }

        return view('staff.edit', compact('staff'));
    }

    /**
     * UPDATE STAFF
     */
    public function update(Request $request, $staffID): RedirectResponse
    {
        Log::info('Updating staff: ' . $staffID, $request->all());

        $request->validate([
            'staffName'     => 'required|string|max:255',
            'ICNumber'      => 'required|string|max:20',
            'phoneNumber'   => 'nullable|string|max:20',
            'email'         => 'nullable|email|max:100|unique:staff,email,' . $staffID . ',staffID',
            'maritalStatus' => 'nullable|in:Single,Married,Divorced,Widowed',
            'gender'        => 'nullable|in:Male,Female',
            'address'       => 'nullable|string',
            'race'          => 'nullable|string|max:50',
            'appointedDate' => 'nullable|date',
            'serviceDate'   => 'nullable|integer|min:0',
            'pensionDate'   => 'nullable|date',
            'latestAge'     => 'nullable|integer|min:0|max:120',
            'department'    => 'required|string|max:100',
            'status'        => 'nullable|string',
            'credit_hour'   => 'nullable|integer|min:0',
            'otherRace'     => 'nullable|string|max:50',
        ], [
            'staffName.required' => 'Staff name is required.',
            'ICNumber.required' => 'IC Number is required.',
            'email.unique' => 'This email is already used by another staff member.',
            'email.email' => 'Please enter a valid email address.',
            'department.required' => 'Please select a department.',
        ]);

        try {
            DB::beginTransaction();

            $race = $request->race;
            if ($request->race == 'Other' && $request->filled('otherRace')) {
                $race = $request->otherRace;
            }

            $serviceYears = $request->serviceDate;
            if ($request->appointedDate && !$request->serviceDate) {
                $serviceYears = Carbon::parse($request->appointedDate)->diffInYears(now());
            }

            $updateData = [
                'staffName' => $request->staffName,
                'ICNumber' => $request->ICNumber,
                'phoneNumber' => $request->phoneNumber,
                'email' => $request->email,
                'maritalStatus' => $request->maritalStatus,
                'gender' => $request->gender,
                'address' => $request->address,
                'race' => $race,
                'appointedDate' => $request->appointedDate,
                'serviceDate' => $serviceYears,
                'pensionDate' => $request->pensionDate,
                'latestAge' => $request->latestAge,
                'department' => $request->department,
                'credit_hour' => $request->credit_hour ?? 0,
            ];

            if ($request->has('status')) {
                $status = $request->status;
                if (in_array($status, ['active', 'Active', 'AKTIF', 'Aktif'])) {
                    $updateData['status'] = 'active';
                } elseif (in_array($status, ['Berhenti', 'berhenti', 'inactive', 'Inactive'])) {
                    $updateData['status'] = 'Berhenti';
                } else {
                    $updateData['status'] = $status;
                }
            }

            $affected = DB::table('staff')
                ->where('staffID', $staffID)
                ->update($updateData);

            Log::info('Staff update affected rows: ' . $affected);

            DB::commit();

            return redirect()->route('staff.show', $staffID)
                           ->with('status', 'Staff profile updated successfully!');

        } catch (QueryException $e) {
            DB::rollBack();
            Log::error('Staff update DB error: ' . $e->getMessage(), ['staffID' => $staffID]);

            if ($this->isDuplicateKeyError($e)) {
                return back()
                    ->withInput()
                    ->with('error', '❌ Duplicate entry — Email or Staff ID already exists.');
            }

            return back()
                ->withInput()
                ->with('error', '❌ Database error: ' . $this->friendlyDbMessage($e));

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Staff update failed: ' . $e->getMessage());

            return back()
                ->withInput()
                ->with('error', '❌ Failed to update staff: ' . $e->getMessage());
        }
    }

    /**
     * TERMINATE STAFF
     */
    public function terminate($staffID): RedirectResponse
    {
        try {
            $staff = DB::table('staff')->where('staffID', $staffID)->first();

            if (!$staff) {
                return back()->with('error', '❌ Staff not found.');
            }

            DB::table('staff')
                ->where('staffID', $staffID)
                ->update(['status' => 'Berhenti']);

            return back()->with('status', "Staff '{$staff->staffName}' terminated successfully.");

        } catch (QueryException $e) {
            Log::error('Staff terminate DB error: ' . $e->getMessage(), ['staffID' => $staffID]);
            return back()->with('error', '❌ Database error: ' . $this->friendlyDbMessage($e));

        } catch (\Exception $e) {
            Log::error('Staff terminate error: ' . $e->getMessage(), ['staffID' => $staffID]);
            return back()->with('error', '❌ Failed to terminate staff: ' . $e->getMessage());
        }
    }

    /**
     * DELETE STAFF (permanent)
     */
    public function destroy($staffID): RedirectResponse
    {
        try {
            $staff = DB::table('staff')->where('staffID', $staffID)->first();

            if (!$staff) {
                return back()->with('error', '❌ Staff not found.');
            }

            DB::beginTransaction();

            DB::table('staff')->where('staffID', $staffID)->delete();

            DB::commit();

            return redirect()->route('staff.list')->with('status', "Staff '{$staff->staffName}' deleted successfully.");

        } catch (QueryException $e) {
            DB::rollBack();
            Log::error('Staff delete DB error: ' . $e->getMessage(), ['staffID' => $staffID]);

            if ($this->isForeignKeyError($e)) {
                return back()->with('error', '❌ Cannot delete staff — referenced by other records.');
            }

            return back()->with('error', '❌ Database error: ' . $this->friendlyDbMessage($e));

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting staff: ' . $e->getMessage(), ['staffID' => $staffID]);
            return back()->with('error', '❌ Failed to delete staff: ' . $e->getMessage());
        }
    }

    /**
     * DOWNLOAD TEMPLATE CSV
     */
    public function template(): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename=staff_template.csv',
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');

            // Add UTF-8 BOM for Excel compatibility
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($file, [
                'staffID*',
                'staffName*',
                'ICNumber*',
                'phoneNumber',
                'email',
                'maritalStatus',
                'gender',
                'address',
                'race',
                'appointedDate (YYYY-MM-DD)',
                'serviceDate (Auto)',
                'pensionDate (YYYY-MM-DD)',
                'latestAge',
                'department*',
                'credit_hour'
            ]);

            fputcsv($file, [
                'STF001',
                'Ahmad Bin Abdullah',
                '750101101234',
                '0123456789',
                'ahmad@school.edu',
                'Married',
                'Male',
                'No 1, Jalan School, Kuala Lumpur',
                'Malay',
                '2020-01-15',
                '', // Leave empty - will be auto-calculated
                '2035-01-15',
                '45',
                'Administration',
                '40'
            ]);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * IMPORT CSV — strict skip on duplicates
     */
    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'csvfile' => 'required|file|mimes:csv,txt|max:5120'
        ]);

        $file = $request->file('csvfile');
        $handle = null;
        $tempFile = null;

        try {
            // Read and clean file content (strip BOM)
            $content = file_get_contents($file->getRealPath());
            $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);

            // Save to temp file
            $tempFile = tempnam(sys_get_temp_dir(), 'staff_clean_');
            file_put_contents($tempFile, $content);

            $handle = fopen($tempFile, 'r');
            if (!$handle) {
                throw new \Exception('Cannot open the uploaded file.');
            }

            // Detect delimiter
            $firstLine = fgets($handle);
            rewind($handle);
            $delimiter = strpos($firstLine, "\t") !== false ? "\t" : ",";

            // Read and skip header
            $header = fgetcsv($handle, 0, $delimiter);
            if (!$header) {
                throw new \Exception('CSV file is empty or invalid.');
            }

            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);

            $importedCount  = 0;
            $duplicateCount = 0;
            $invalidCount   = 0;
            $warnings       = [];
            $errors         = [];
            $rowNumber      = 1;

            $defaultPassword = Hash::make('password');

            DB::beginTransaction();

            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                $rowNumber++;

                // Skip empty rows
                if (empty(trim($row[0] ?? ''))) {
                    continue;
                }

                // Pad row to ensure we have at least 15 columns
                $row = array_pad($row, 15, '');

                $staffID       = trim($row[0]);
                $staffName     = trim($row[1]);
                $ICNumber      = trim($row[2]);
                $phoneNumber   = !empty(trim($row[3])) ? trim($row[3]) : null;
                $email         = !empty(trim($row[4])) ? trim($row[4]) : null;
                $maritalStatus = !empty(trim($row[5])) ? trim($row[5]) : null;
                $gender        = !empty(trim($row[6])) ? trim($row[6]) : null;
                $address       = !empty(trim($row[7])) ? trim($row[7]) : null;
                $race          = !empty(trim($row[8])) ? trim($row[8]) : null;
                $department    = trim($row[13]);
                $creditHour    = !empty($row[14]) && is_numeric($row[14]) ? (int)$row[14] : 0;

                // Validate required fields
                if (empty($staffID)) {
                    $errors[] = "Row {$rowNumber}: Missing staffID — skipped.";
                    $invalidCount++;
                    continue;
                }

                if (empty($staffName)) {
                    $errors[] = "Row {$rowNumber} (ID: {$staffID}): Missing staffName — skipped.";
                    $invalidCount++;
                    continue;
                }

                if (empty($department)) {
                    $errors[] = "Row {$rowNumber} (ID: {$staffID}): Missing department — skipped.";
                    $invalidCount++;
                    continue;
                }

                // Auto-generate temp IC if missing
                if (empty($ICNumber)) {
                    $ICNumber = 'TEMP_' . preg_replace('/[^A-Za-z0-9]/', '', $staffID) . '_' . date('Ymd');
                    $warnings[] = "Row {$rowNumber}: Generated temporary IC for {$staffID}";
                }

                // Duplicate check — SKIP
                $existingStaff = DB::table('staff')->where('staffID', $staffID)->first();
                if ($existingStaff) {
                    $duplicateCount++;
                    $errors[] = "Row {$rowNumber} (ID: {$staffID}): Already exists — skipped.";
                    continue;
                }

                // Parse dates
                $appointedDate = !empty($row[9]) && trim($row[9]) != '?' ? $this->parseDate($row[9]) : null;
                $pensionDate   = !empty($row[11]) && trim($row[11]) != '?' ? $this->parseDate($row[11]) : null;
                $latestAge     = !empty($row[12]) && is_numeric($row[12]) ? (int)$row[12] : null;

                // Calculate service years from appointed date
                $serviceYears = null;
                if ($appointedDate) {
                    $serviceYears = Carbon::parse($appointedDate)->diffInYears(now());
                }

                try {
                    DB::table('staff')->insert([
                        'staffID' => $staffID,
                        'staffName' => $staffName,
                        'ICNumber' => $ICNumber,
                        'phoneNumber' => $phoneNumber,
                        'email' => $email,
                        'maritalStatus' => $maritalStatus,
                        'gender' => $gender,
                        'address' => $address,
                        'race' => $race,
                        'appointedDate' => $appointedDate,
                        'serviceDate' => $serviceYears,
                        'pensionDate' => $pensionDate,
                        'latestAge' => $latestAge,
                        'department' => $department,
                        'credit_hour' => $creditHour,
                        'password' => $defaultPassword,
                        'password_change_required' => 1,
                        'role' => 'staff',
                        'status' => 'active'
                    ]);
                    $importedCount++;

                } catch (QueryException $e) {
                    if ($this->isDuplicateKeyError($e)) {
                        $duplicateCount++;
                        $errors[] = "Row {$rowNumber} (ID: {$staffID}): Duplicate — skipped.";
                    } else {
                        $errors[] = "Row {$rowNumber} (ID: {$staffID}): Database error — " . $this->friendlyDbMessage($e);
                        $invalidCount++;
                    }
                    Log::error("Staff CSV import DB error at row {$rowNumber}: " . $e->getMessage(), ['staffID' => $staffID]);

                } catch (\Exception $e) {
                    $errors[] = "Row {$rowNumber} (ID: {$staffID}): " . $e->getMessage();
                    $invalidCount++;
                    Log::error("Staff CSV import error at row {$rowNumber}: " . $e->getMessage(), ['staffID' => $staffID]);
                }
            }

            fclose($handle);
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
            DB::commit();

            // Build summary message
            $message = "📥 Import completed — New staff: {$importedCount}";

            if ($duplicateCount > 0) {
                $message .= ", Duplicates skipped: {$duplicateCount}";
            }
            if ($invalidCount > 0) {
                $message .= ", Invalid rows: {$invalidCount}";
            }
            if (!empty($warnings)) {
                $message .= ", Warnings: " . count($warnings);
            }

            if ($importedCount === 0 && ($duplicateCount > 0 || $invalidCount > 0)) {
                $flashKey = 'error';
                $message = "⚠️ No new staff imported. " . $message;
            } elseif ($importedCount > 0 && ($duplicateCount > 0 || $invalidCount > 0)) {
                $flashKey = 'status';
                $message = "✅ Partial import. " . $message;
            } else {
                $flashKey = 'status';
                $message = "✅ " . $message . ". Default password is: password";
            }

            $redirect = back()->with($flashKey, $message);

            if (!empty($errors)) {
                Log::warning("Staff CSV import warnings: " . implode("; ", array_slice($errors, 0, 20)));
                $redirect->with('import_errors', $errors);
            }

            return $redirect;

        } catch (\Exception $e) {
            DB::rollBack();

            if ($handle && is_resource($handle)) {
                fclose($handle);
            }
            if ($tempFile && file_exists($tempFile)) {
                unlink($tempFile);
            }

            Log::error("Staff CSV Import Exception: " . $e->getMessage());

            return back()
                ->withInput()
                ->with('error', '❌ Import failed: ' . $e->getMessage());
        }
    }

    /**
     * Helper function to parse dates
     */
    private function parseDate(?string $dateString): ?string
    {
        if (empty($dateString)) return null;

        $dateString = trim($dateString);
        $dateString = str_replace('/', '-', $dateString);

        $timestamp = strtotime($dateString);
        if ($timestamp === false || $timestamp <= 0) {
            return null;
        }

        return date('Y-m-d', $timestamp);
    }

    /**
     * DISPLAY STOPPED STAFF
     */
    public function stopped(Request $request): View
    {
        $query = DB::table('staff')
            ->where('status', 'Berhenti');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('staffName', 'like', "%$search%")
                  ->orWhere('staffID', 'like', "%$search%")
                  ->orWhere('ICNumber', 'like', "%$search%")
                  ->orWhere('email', 'like', "%$search%")
                  ->orWhere('department', 'like', "%$search%");
            });
        }

        $resignedStaff = $query->orderBy('staffName')
                               ->get()
                               ->groupBy('department');

        $stats = [
            'total_resigned' => DB::table('staff')->where('status', 'Berhenti')->count(),
            'by_department' => DB::table('staff')
                ->select('department', DB::raw('count(*) as count'))
                ->where('status', 'Berhenti')
                ->groupBy('department')
                ->get(),
            'this_year_resigned' => DB::table('staff')
                ->where('status', 'Berhenti')
                ->whereYear('pensionDate', now()->year)
                ->count(),
        ];

        return view('staff.stopped', compact('resignedStaff', 'stats'));
    }

    /**
     * FIX SERVICE YEARS - One-time fix for all staff
     */
    public function fixServiceYears(): JsonResponse
    {
        try {
            DB::statement("
                UPDATE staff
                SET serviceDate = TIMESTAMPDIFF(YEAR, appointedDate, CURDATE())
                WHERE appointedDate IS NOT NULL
            ");

            return response()->json([
                'message' => 'Service years updated successfully',
                'updated' => DB::table('staff')->whereNotNull('appointedDate')->count()
            ]);

        } catch (QueryException $e) {
            Log::error('fixServiceYears DB error: ' . $e->getMessage());
            return response()->json(['error' => $this->friendlyDbMessage($e)], 500);

        } catch (\Exception $e) {
            Log::error('fixServiceYears error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
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