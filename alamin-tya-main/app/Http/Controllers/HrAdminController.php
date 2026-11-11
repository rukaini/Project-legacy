<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreHrAdminRequest;
use App\Models\HrAdministrator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class HrAdminController extends Controller
{
    /**
     * Guard: only HR role can access this module.
     */
    private function guard(): void
    {
        abort_unless(in_array(session('role'), ['hr', 'HR'], true), 403);
    }

    /**
     * List all HR administrators.
     */
    public function index(Request $request)
    {
        $this->guard();

        $query = HrAdministrator::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('hrid', 'like', "%{$search}%");
            });
        }

        if ($role = $request->input('role')) {
            $query->where('role', $role);
        }

        // No timestamps on this model, so order by hrid
        $hrAdmins = $query->orderBy('hrid')->paginate(15)->withQueryString();

        return view('hr-admin.index', compact('hrAdmins'));
    }

    /**
     * Show registration form.
     */
    public function create()
    {
        $this->guard();

        return view('hr-admin.create');
    }

    /**
     * Store new HR administrator.
     */
  public function store(StoreHrAdminRequest $request)
{
    $this->guard();

    $data = $request->validated();

    // Auto-generate primary key
    $data['hrid'] = $this->generateHrid();

    // Hash password
    $data['password'] = Hash::make($data['password']);

    // Defaults
    $data['role'] = $data['role'] ?? 'hr';

    // Optional column — safe default
    if (Schema::hasColumn('hr_administrator', 'password_change_required')) {
        $data['password_change_required'] = 0;
    }

    HrAdministrator::create($data);

    return redirect()
        ->route('hr-admins.index')
        ->with('success', 'HR administrator registered successfully.');
}

    /**
     * Show edit form.
     */
    public function edit(HrAdministrator $hrAdmin)
    {
        $this->guard();

        return view('hr-admin.edit', ['hrAdmin' => $hrAdmin]);
    }

    /**
     * Update HR administrator.
     */
    public function update(StoreHrAdminRequest $request, HrAdministrator $hrAdmin)
    {
        $this->guard();

        $data = $request->validated();

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $hrAdmin->update($data);

        return redirect()
            ->route('hr-admins.index')
            ->with('success', 'HR administrator updated successfully.');
    }

    /**
     * Delete HR administrator.
     */
    public function destroy(HrAdministrator $hrAdmin)
    {
        $this->guard();

        // Prevent self-deletion
        if ($hrAdmin->hrid === session('userID')) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $hrAdmin->delete();

        return redirect()
            ->route('hr-admins.index')
            ->with('success', 'HR administrator removed.');
    }

    /**
     * Toggle active/inactive status (only if the table has a `status` column).
     */
    public function toggleStatus(HrAdministrator $hrAdmin)
    {
        $this->guard();

        if (!Schema::hasColumn('hr_administrator', 'status')) {
            return back()->with('error', 'Status column is not present in the table.');
        }

        $hrAdmin->update([
            'status' => $hrAdmin->status === 'active' ? 'inactive' : 'active',
        ]);

        return back()->with('success', 'Status updated.');
    }

    /**
     * Generate a unique string HRID like HRA-2025-0001.
     */
    private function generateHrid(): string
    {
        $year   = date('Y');
        $prefix = "HRA-{$year}-";

        // Find the highest existing suffix for this year
        $last = HrAdministrator::where('hrid', 'like', $prefix . '%')
            ->orderByDesc('hrid')
            ->value('hrid');

        $next = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}