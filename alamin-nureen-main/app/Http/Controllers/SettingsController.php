<?php

namespace App\Http\Controllers;

use App\Models\StaffEdu;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class SettingsController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $staffID = (string) ($_SESSION['staffID'] ?? $_SESSION['staff_id'] ?? '');

        if ($staffID === '') {
            return redirect()->route('login');
        }

        if ($request->isMethod('post')) {
            return $this->handlePost($request, $staffID);
        }

        $staff = DB::table('staff_edu')
            ->selectRaw('staffID, staffName, ICNumber, phoneNumber, email, maritalStatus, gender, address, race, appointedDate, serviceDate, pensionDate, latestAge, password_changed_required, role, credit_hour, department, status, fn_age_from_ic(ICNumber) AS calculatedAge, fn_service_duration(appointedDate) AS calculatedServiceDuration')
            ->where('staffID', $staffID)
            ->first();

        if (!$staff) {
            $this->destroyNativeSession();
            return redirect()->route('login');
        }

        // Keep the legacy-native session values expected by the shared topbar.
        $_SESSION['staffID'] = (string) $staff->staffID;
        $_SESSION['staff_id'] = (string) $staff->staffID;
        $_SESSION['staffName'] = (string) $staff->staffName;
        $_SESSION['staff_name'] = (string) $staff->staffName;

        $staffArray = (array) $staff;

        // One source of truth for staff credit, including verified management orders.
        try {
            $creditYear = (int) date('Y');
            $totals = \App\Services\StaffCreditHourService::totals($staffID, $creditYear);
            $staffArray['credit_hour'] = min(40.0, $totals['total']);
            $staffArray['credit_year'] = $creditYear;
            $creditSettings = \App\Support\TrainHubSettings::all();
            $staffArray['credit_target'] = $totals['target'];
            $staffArray['training_target'] = $creditSettings['training_target_hours'];
            $staffArray['tarbiah_target'] = $creditSettings['tarbiah_target_hours'];
            $staffArray['tarbiah_credit_hour'] = $totals['tarbiah'];
            $staffArray['training_credit_hour'] = $totals['training'];
        } catch (Throwable $e) {
            // The original staff_edu.credit_hour remains available while the database owner applies the patch.
            report($e);
        }

        $displayAge = !empty($staffArray['calculatedAge'])
            ? $staffArray['calculatedAge']
            : ($staffArray['latestAge'] ?? '-');

        $calculatedServiceDuration = trim((string) ($staffArray['calculatedServiceDuration'] ?? ''));
        $storedServiceDate = trim((string) ($staffArray['serviceDate'] ?? ''));
        $displayServiceDuration = $calculatedServiceDuration !== ''
            ? $calculatedServiceDuration
            : ($storedServiceDate !== '' ? $storedServiceDate : '-');

        $statusClass = strtolower((string) ($staffArray['status'] ?? 'inactive'));
        if (!in_array($statusClass, ['active', 'inactive'], true)) {
            $statusClass = 'inactive';
        }

        $name = (string) ($staffArray['staffName'] ?? 'A');
        $avatarCharacter = function_exists('mb_substr')
            ? mb_substr($name, 0, 1, 'UTF-8')
            : substr($name, 0, 1);

        $flash = session('settings_flash', []);

        $staffAccounts = DB::table('staff_edu')
            ->select('staffID', 'staffName', 'email', 'department', 'status')
            ->orderBy('staffName')
            ->get();

        return view('legacy.settings', [
            'staff' => $staffArray,
            'displayAge' => $displayAge,
            'displayServiceDuration' => $displayServiceDuration,
            'statusClass' => $statusClass,
            'avatarCharacter' => $avatarCharacter,
            'message' => (string) ($flash['message'] ?? ''),
            'messageType' => (string) ($flash['type'] ?? 'success'),
            'staffAccounts' => $staffAccounts,
            'canAddStaffEdu' => in_array(str_replace([' ', '-'], '_', strtoupper(trim((string)($staffArray['role'] ?? '')))), ['STAFF_EDU', 'ADMIN'], true) && strtolower((string)($staffArray['status'] ?? 'inactive')) === 'active',
        ]);
    }

    private function handlePost(Request $request, string $staffID): RedirectResponse
    {
        return match ((string) $request->input('action')) {
            'update_profile' => $this->updateProfile($request, $staffID),
            'change_password' => $this->changePassword($request, $staffID),
            'add_staff_edu' => $this->addStaffEdu($request, $staffID),
            default => $this->backWithMessage('error', 'Invalid settings action.'),
        };
    }

    private function updateProfile(Request $request, string $staffID): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'phoneNumber' => ['nullable', 'string', 'max:20'],
            'email' => [
                'nullable',
                'email',
                'max:100',
                Rule::unique('staff_edu', 'email')->ignore($staffID, 'staffID'),
            ],
            'maritalStatus' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:255'],
        ], [
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'That email address is already used by another staff account.',
        ]);

        if ($validator->fails()) {
            return redirect()->route('settings.index')
                ->withErrors($validator)
                ->withInput()
                ->with('settings_flash', [
                    'type' => 'error',
                    'message' => $validator->errors()->first(),
                ]);
        }

        $data = $validator->validated();
        foreach (['phoneNumber', 'email', 'maritalStatus', 'address'] as $field) {
            $value = isset($data[$field]) ? trim((string) $data[$field]) : '';
            $data[$field] = $value === '' ? null : $value;
        }

        try {
            $changed = DB::transaction(function () use ($staffID, $data): bool {
                $old = DB::table('staff_edu')
                    ->where('staffID', $staffID)
                    ->lockForUpdate()
                    ->first(['staffName', 'phoneNumber', 'email', 'maritalStatus', 'address']);

                if (!$old) {
                    throw new \RuntimeException('The staff account could not be found.');
                }

                $newValues = [
                    'phoneNumber' => $data['phoneNumber'],
                    'email' => $data['email'],
                    'maritalStatus' => $data['maritalStatus'],
                    'address' => $data['address'],
                ];

                $oldValues = (array) $old;
                $normalize = static fn ($value) => $value === null ? null : (string) $value;
                $hasChanges = false;
                foreach ($newValues as $key => $value) {
                    if ($normalize($oldValues[$key] ?? null) !== $normalize($value)) {
                        $hasChanges = true;
                        break;
                    }
                }

                if (!$hasChanges) {
                    return false;
                }

                // Canonical V6's staff_edu UPDATE trigger records this in audit_log.
                DB::statement('SET @current_staff_id = ?, @current_staff_name = ?', [
                    $staffID,
                    (string) ($old->staffName ?? $staffID),
                ]);
                DB::table('staff_edu')->where('staffID', $staffID)->update($newValues);

                return true;
            });

            if (isset($_SESSION['unified_user']) && is_array($_SESSION['unified_user'])) {
                $_SESSION['unified_user']['email'] = (string) ($data['email'] ?? '');
            }

            return $this->backWithMessage('success', $changed ? 'Profile updated successfully.' : 'No profile changes to save.');
        } catch (QueryException $e) {
            report($e);
            $message = ((int) ($e->errorInfo[1] ?? 0) === 1062)
                ? 'The email address is already used by another staff account.'
                : 'Unable to save the settings at this time. Please try again.';
            return $this->backWithMessage('error', $message);
        } catch (Throwable $e) {
            report($e);
            return $this->backWithMessage('error', 'Unable to save the settings at this time. Please try again.');
        }
    }

    private function addStaffEdu(Request $request, string $currentStaffID): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'newStaffID' => ['required', 'string', 'max:20', Rule::unique('staff_edu', 'staffID')],
            'newStaffName' => ['required', 'string', 'max:250'],
            'newStaffEmail' => ['required', 'email', 'max:100', Rule::unique('staff_edu', 'email')],
            'newStaffIC' => ['nullable', 'string', 'max:20', Rule::unique('staff_edu', 'ICNumber')],
            'newStaffPhone' => ['nullable', 'string', 'max:20'],
            'newStaffDepartment' => ['nullable', 'string', 'max:50'],
            'newStaffGender' => ['nullable', Rule::in(['Male', 'Female'])],
            'newStaffAppointedDate' => ['nullable', 'date'],
        ], [
            'newStaffID.required' => 'Staff ID is required.',
            'newStaffID.unique' => 'That Staff ID already exists.',
            'newStaffName.required' => 'Staff name is required.',
            'newStaffEmail.required' => 'Staff email is required.',
            'newStaffEmail.email' => 'Please enter a valid staff email.',
            'newStaffEmail.unique' => 'That staff email already exists.',
            'newStaffIC.unique' => 'That IC number already exists.',
        ]);

        if ($validator->fails()) {
            return redirect()->route('settings.index')
                ->withErrors($validator)
                ->withInput()
                ->with('settings_flash', [
                    'type' => 'error',
                    'message' => $validator->errors()->first(),
                ]);
        }

        $data = $validator->validated();
        $nullable = static fn ($value) => trim((string)($value ?? '')) === '' ? null : trim((string)$value);

        try {
            DB::transaction(function () use ($currentStaffID, $data, $nullable): void {
                $current = DB::table('staff_edu')->where('staffID', $currentStaffID)->lockForUpdate()->first();
                if (!$current) {
                    throw new \RuntimeException('The current Staff EDU account could not be found.');
                }
                $currentRole = str_replace([' ', '-'], '_', strtoupper(trim((string)($current->role ?? ''))));
                if (strtolower((string)($current->status ?? 'inactive')) !== 'active' || !in_array($currentRole, ['STAFF_EDU', 'ADMIN'], true)) {
                    throw new \RuntimeException('Only an active Staff EDU account can add another Staff EDU account.');
                }

                DB::statement('SET @current_staff_id = ?, @current_staff_name = ?', [
                    $currentStaffID,
                    (string)($current->staffName ?? $currentStaffID),
                ]);

                DB::table('staff_edu')->insert([
                    'staffID' => trim((string)$data['newStaffID']),
                    'staffName' => trim((string)$data['newStaffName']),
                    'ICNumber' => $nullable($data['newStaffIC'] ?? null),
                    'phoneNumber' => $nullable($data['newStaffPhone'] ?? null),
                    'email' => strtolower(trim((string)$data['newStaffEmail'])),
                    'gender' => $nullable($data['newStaffGender'] ?? null),
                    'appointedDate' => $nullable($data['newStaffAppointedDate'] ?? null),
                    'password' => Hash::make(bin2hex(random_bytes(24))),
                    'password_changed_required' => 1,
                    'role' => 'STAFF_EDU',
                    'credit_hour' => 0,
                    'department' => $nullable($data['newStaffDepartment'] ?? null),
                    'status' => 'active',
                ]);
            });

            return $this->backWithMessage('success', 'New Staff EDU account added successfully.');
        } catch (QueryException $e) {
            report($e);
            return $this->backWithMessage('error', 'Unable to add the Staff EDU account. Please check the Staff ID, IC number and email.');
        } catch (Throwable $e) {
            report($e);
            return $this->backWithMessage('error', $e->getMessage() ?: 'Unable to add the Staff EDU account.');
        }
    }

    private function changePassword(Request $request, string $staffID): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'currentPassword' => ['required', 'string'],
            'newPassword' => ['required', 'string', 'min:8', 'max:72', 'same:confirmPassword'],
            'confirmPassword' => ['required', 'string', 'min:8', 'max:72'],
        ], [
            'currentPassword.required' => 'Current password is required.',
            'newPassword.required' => 'New password is required.',
            'newPassword.min' => 'New password must be at least 8 characters.',
            'newPassword.max' => 'New password cannot exceed 72 characters.',
            'newPassword.same' => 'New password and confirmation password do not match.',
            'confirmPassword.required' => 'Confirmation password is required.',
        ]);

        if ($validator->fails()) {
            return $this->backWithMessage('error', $validator->errors()->first());
        }

        $current = (string) $request->input('currentPassword');
        $new = (string) $request->input('newPassword');

        try {
            DB::transaction(function () use ($staffID, $current, $new): void {
                $staff = StaffEdu::query()->whereKey($staffID)->lockForUpdate()->first();
                if (!$staff) {
                    throw new \RuntimeException('The staff account could not be found.');
                }

                $stored = (string) $staff->password;
                $isHash = str_starts_with($stored, '$2y$')
                    || str_starts_with($stored, '$2a$')
                    || str_starts_with($stored, '$2b$')
                    || str_starts_with($stored, '$argon2');

                $currentValid = $isHash
                    ? Hash::check($current, $stored)
                    : hash_equals($stored, $current);

                if (!$currentValid) {
                    throw new \InvalidArgumentException('Current password is incorrect.');
                }

                $newMatchesCurrent = $isHash
                    ? Hash::check($new, $stored)
                    : hash_equals($stored, $new);
                if ($newMatchesCurrent) {
                    throw new \InvalidArgumentException('The new password must be different from the current password.');
                }

                DB::statement('SET @current_staff_id = ?, @current_staff_name = ?', [
                    $staffID,
                    (string) ($staff->staffName ?? $staffID),
                ]);
                $staff->password = Hash::make($new);
                $staff->password_changed_required = 0;
                $staff->save();
            });

            $_SESSION['password_change_required'] = 0;
            return $this->backWithMessage('success', 'Password changed successfully.');
        } catch (\InvalidArgumentException $e) {
            return $this->backWithMessage('error', $e->getMessage());
        } catch (Throwable $e) {
            report($e);
            return $this->backWithMessage('error', 'Unable to save the settings at this time. Please try again.');
        }
    }

    private function backWithMessage(string $type, string $message): RedirectResponse
    {
        return redirect()->route('settings.index')->with('settings_flash', [
            'type' => $type,
            'message' => $message,
        ]);
    }

    private function destroyNativeSession(): void
    {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();
            session_destroy();
        }
    }
}
