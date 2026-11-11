<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrganizationController extends Controller
{
    /**
     * Display a listing of organizations.
     */
    public function index(Request $request): View
    {
        $query = DB::table('organizations');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('OrganizationID', 'LIKE', "%{$search}%")
                  ->orWhere('OrganizationName', 'LIKE', "%{$search}%")
                  ->orWhere('OrganizationAddress', 'LIKE', "%{$search}%")
                  ->orWhere('PhoneNumber', 'LIKE', "%{$search}%");
            });
        }

        $organizations = $query->orderBy('OrganizationName')->paginate(10)->withQueryString();
        $totalOrganizations = DB::table('organizations')->count();

        return view('organizations.index', compact('organizations', 'totalOrganizations'));
    }

    /**
     * Show the form for creating a new organization.
     */
    public function create(): View
    {
        return view('organizations.create');
    }

    /**
     * Store a newly created organization.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'OrganizationID'      => 'required|string|max:20|unique:organizations,OrganizationID',
            'OrganizationName'    => 'required|string|max:255',
            'OrganizationAddress' => 'nullable|string',
            'RegisterDate'        => 'nullable|date',
            'PhoneNumber'         => 'nullable|string|max:20',
        ], [
            'OrganizationID.unique' => 'An organization with this ID already exists. Please use a unique Organization ID.',
            'OrganizationID.required' => 'Organization ID is required.',
            'OrganizationName.required' => 'Organization name is required.',
        ]);

        try {
            DB::beginTransaction();

            DB::table('organizations')->insert([
                'OrganizationID'      => $request->OrganizationID,
                'OrganizationName'    => $request->OrganizationName,
                'OrganizationAddress' => $request->OrganizationAddress,
                'RegisterDate'        => $request->RegisterDate,
                'PhoneNumber'         => $request->PhoneNumber,
            ]);

            DB::commit();

            return redirect()->route('orgs.index')
                ->with('success', "Organization '{$request->OrganizationName}' created successfully!");

        } catch (QueryException $e) {
            DB::rollBack();
            Log::error('Error creating organization: ' . $e->getMessage(), ['OrganizationID' => $request->OrganizationID]);

            if ($this->isDuplicateKeyError($e)) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', "❌ Duplicate Organization ID: '{$request->OrganizationID}' is already registered.");
            }

            return redirect()->back()
                ->withInput()
                ->with('error', '❌ Database error: ' . $this->friendlyDbMessage($e));

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating organization: ' . $e->getMessage());

            return redirect()->back()
                ->withInput()
                ->with('error', '❌ Failed to create organization: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified organization.
     */
    public function show($id): View
    {
        $organization = DB::table('organizations')->where('OrganizationID', $id)->first();

        if (!$organization) {
            abort(404, 'Organization not found');
        }

        $schools = DB::table('school')->where('OrganizationID', $id)->get();

        return view('organizations.show', compact('organization', 'schools'));
    }

    /**
     * Show the form for editing the specified organization.
     */
    public function edit($id): View
    {
        $organization = DB::table('organizations')->where('OrganizationID', $id)->first();

        if (!$organization) {
            abort(404, 'Organization not found');
        }

        return view('organizations.edit', compact('organization'));
    }

    /**
     * Update the specified organization.
     */
    public function update(Request $request, $id): RedirectResponse
    {
        $request->validate([
            'OrganizationName'    => 'required|string|max:255',
            'OrganizationAddress' => 'nullable|string',
            'RegisterDate'        => 'nullable|date',
            'PhoneNumber'         => 'nullable|string|max:20',
        ], [
            'OrganizationName.required' => 'Organization name is required.',
        ]);

        try {
            DB::beginTransaction();

            $affected = DB::table('organizations')
                ->where('OrganizationID', $id)
                ->update([
                    'OrganizationName'    => $request->OrganizationName,
                    'OrganizationAddress' => $request->OrganizationAddress,
                    'RegisterDate'        => $request->RegisterDate,
                    'PhoneNumber'         => $request->PhoneNumber,
                ]);

            if ($affected === 0) {
                DB::rollBack();
                return redirect()->route('orgs.index')
                    ->with('error', '❌ Organization not found or no changes made.');
            }

            DB::commit();

            return redirect()->route('orgs.index')
                ->with('success', "Organization '{$request->OrganizationName}' updated successfully!");

        } catch (QueryException $e) {
            DB::rollBack();
            Log::error('Error updating organization: ' . $e->getMessage(), ['OrganizationID' => $id]);

            if ($this->isDuplicateKeyError($e)) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', '❌ Duplicate entry detected. Please check your data.');
            }

            return redirect()->back()
                ->withInput()
                ->with('error', '❌ Database error: ' . $this->friendlyDbMessage($e));

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating organization: ' . $e->getMessage(), ['OrganizationID' => $id]);

            return redirect()->back()
                ->withInput()
                ->with('error', '❌ Failed to update organization: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified organization.
     */
    public function destroy($id): RedirectResponse
    {
        try {
            $organization = DB::table('organizations')->where('OrganizationID', $id)->first();

            if (!$organization) {
                return redirect()->route('orgs.index')
                    ->with('error', '❌ Organization not found.');
            }

            // Check if organization has schools assigned
            $schoolCount = DB::table('school')->where('OrganizationID', $id)->count();

            if ($schoolCount > 0) {
                return redirect()->route('orgs.index')
                    ->with('error', "❌ Cannot delete '{$organization->OrganizationName}': {$schoolCount} school(s) still assigned. Please reassign them first.");
            }

            DB::beginTransaction();

            DB::table('organizations')->where('OrganizationID', $id)->delete();

            DB::commit();

            return redirect()->route('orgs.index')
                ->with('success', "Organization '{$organization->OrganizationName}' deleted successfully!");

        } catch (QueryException $e) {
            DB::rollBack();
            Log::error('Error deleting organization: ' . $e->getMessage(), ['OrganizationID' => $id]);

            if ($this->isForeignKeyError($e)) {
                return redirect()->route('orgs.index')
                    ->with('error', '❌ Cannot delete organization — it is referenced by other records (schools, etc.).');
            }

            return redirect()->route('orgs.index')
                ->with('error', '❌ Database error: ' . $this->friendlyDbMessage($e));

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting organization: ' . $e->getMessage(), ['OrganizationID' => $id]);

            return redirect()->route('orgs.index')
                ->with('error', '❌ Failed to delete organization: ' . $e->getMessage());
        }
    }

    /**
     * Import organizations from CSV (strict skip mode).
     */
    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'csvfile' => 'required|file|mimes:csv,txt|max:5120'
        ]);

        $file = $request->file('csvfile');
        $handle = null;

        try {
            $handle = fopen($file->getRealPath(), 'r');
            if (!$handle) {
                throw new \Exception('Cannot open the uploaded file.');
            }

            // Detect delimiter
            $firstLine = fgets($handle);
            rewind($handle);
            $delimiter = strpos($firstLine, "\t") !== false ? "\t" : ",";

            $header = fgetcsv($handle, 0, $delimiter);
            if (!$header) {
                throw new \Exception('CSV file is empty or invalid.');
            }

            // Strip BOM from first column
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);

            $importedCount  = 0;
            $duplicateCount = 0;
            $invalidCount   = 0;
            $errors         = [];
            $rowNumber      = 1;

            DB::beginTransaction();

            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                $rowNumber++;

                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }

                $row = array_pad($row, 5, '');
                $data = array_map('trim', $row);

                $orgID    = $data[0] ?? '';
                $orgName  = $data[1] ?? '';
                $orgAddr  = $data[2] ?? null;
                $regDate  = !empty($data[3]) ? date('Y-m-d', strtotime($data[3])) : null;
                $phone    = $data[4] ?? null;

                if (empty($orgID)) {
                    $errors[] = "Row {$rowNumber}: Missing OrganizationID — skipped.";
                    $invalidCount++;
                    continue;
                }

                if (empty($orgName)) {
                    $errors[] = "Row {$rowNumber} (ID: {$orgID}): Missing OrganizationName — skipped.";
                    $invalidCount++;
                    continue;
                }

                // Duplicate check — SKIP
                $exists = DB::table('organizations')->where('OrganizationID', $orgID)->exists();
                if ($exists) {
                    $duplicateCount++;
                    $errors[] = "Row {$rowNumber} (ID: {$orgID}): Already exists — skipped.";
                    continue;
                }

                try {
                    DB::table('organizations')->insert([
                        'OrganizationID'      => $orgID,
                        'OrganizationName'    => $orgName,
                        'OrganizationAddress' => $orgAddr,
                        'RegisterDate'        => $regDate,
                        'PhoneNumber'         => $phone,
                    ]);
                    $importedCount++;

                } catch (QueryException $e) {
                    if ($this->isDuplicateKeyError($e)) {
                        $duplicateCount++;
                        $errors[] = "Row {$rowNumber} (ID: {$orgID}): Duplicate — skipped.";
                    } else {
                        $errors[] = "Row {$rowNumber} (ID: {$orgID}): Database error — " . $this->friendlyDbMessage($e);
                        $invalidCount++;
                    }
                    Log::error("CSV import DB error at row {$rowNumber}: " . $e->getMessage(), ['OrganizationID' => $orgID]);

                } catch (\Exception $e) {
                    $errors[] = "Row {$rowNumber} (ID: {$orgID}): " . $e->getMessage();
                    $invalidCount++;
                    Log::error("CSV import error at row {$rowNumber}: " . $e->getMessage(), ['OrganizationID' => $orgID]);
                }
            }

            fclose($handle);
            DB::commit();

            // Build summary message
            $message = "📥 Import completed — New organizations: {$importedCount}";

            if ($duplicateCount > 0) {
                $message .= ", Duplicates skipped: {$duplicateCount}";
            }
            if ($invalidCount > 0) {
                $message .= ", Invalid rows: {$invalidCount}";
            }

            if ($importedCount === 0 && ($duplicateCount > 0 || $invalidCount > 0)) {
                $flashKey = 'error';
                $message = "⚠️ No new organizations imported. " . $message;
            } elseif ($importedCount > 0 && ($duplicateCount > 0 || $invalidCount > 0)) {
                $flashKey = 'success';
                $message = "✅ Partial import. " . $message;
            } else {
                $flashKey = 'success';
                $message = "✅ " . $message;
            }

            $redirect = redirect()->route('orgs.index')->with($flashKey, $message);

            if (!empty($errors)) {
                Log::warning("CSV import warnings: " . implode("; ", array_slice($errors, 0, 20)));
                $redirect->with('import_errors', $errors);
            }

            return $redirect;

        } catch (\Exception $e) {
            DB::rollBack();

            if ($handle && is_resource($handle)) {
                fclose($handle);
            }

            Log::error('CSV import error: ' . $e->getMessage());

            return redirect()->route('orgs.index')
                ->withInput()
                ->with('error', '❌ Import failed: ' . $e->getMessage());
        }
    }

    /**
     * Download CSV template for organizations import.
     */
    public function downloadTemplate(): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="organization_template.csv"',
        ];

        $columns = [
            'OrganizationID*',
            'OrganizationName*',
            'OrganizationAddress',
            'RegisterDate (YYYY-MM-DD)',
            'PhoneNumber'
        ];

        $callback = function () use ($columns) {
            $file = fopen('php://output', 'w');

            // Add UTF-8 BOM for Excel compatibility
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($file, $columns);

            fputcsv($file, [
                'ORG001',
                'Ministry of Education',
                'Level 5, Block E, Complex Government, Putrajaya',
                '2024-01-01',
                '03-8000-0000'
            ]);

            fputcsv($file, [
                'ORG002',
                'Selangor Education Department',
                'Jalan SS 7/2, Petaling Jaya, Selangor',
                '2024-01-15',
                '03-7000-0000'
            ]);

            fputcsv($file, [
                'ORG003',
                'Kuala Lumpur Education Department',
                'Jalan Tuanku Abdul Rahman, Kuala Lumpur',
                '2024-02-01',
                '03-6000-0000'
            ]);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
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