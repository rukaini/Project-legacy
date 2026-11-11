@extends('layouts.app')

@section('title', 'All School Records')

@section('styles')
<style>
    :root {
        --primary-green: #059669;
        --primary-dark: #047857;
        --primary-light: #10b981;
        --primary-soft: #d1fae5;
        --primary-bg: #ecfdf5;
        --gray-50: #f9fafb;
        --gray-100: #f3f4f6;
        --gray-200: #e5e7eb;
        --gray-300: #d1d5db;
        --gray-600: #4b5563;
        --gray-700: #374151;
        --gray-800: #1f2937;
        --gray-900: #111827;
    }

    body {
        background: linear-gradient(135deg, #eef9f2 0%, #e0f2e9 100%);
        font-family: 'Inter', 'Poppins', system-ui, -apple-system, sans-serif;
        min-height: 100vh;
    }

    .page-title {
        font-size: 2rem;
        font-weight: 700;
        background: linear-gradient(135deg, var(--primary-green), var(--primary-dark));
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
        margin-bottom: 1.5rem;
        letter-spacing: -0.02em;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .page-title::before {
        content: "🏫";
        font-size: 2rem;
    }

    /* Stats Summary */
    .stats-summary {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 1.25rem;
        margin-bottom: 2rem;
    }

    .stat-card {
        background: white;
        padding: 1.25rem;
        border-radius: 20px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        transition: all 0.3s ease;
        border: 1px solid var(--gray-100);
        position: relative;
        overflow: hidden;
    }

    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, var(--primary-green), var(--primary-light));
    }

    .stat-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 24px rgba(0,0,0,0.1);
    }

    .stat-icon {
        font-size: 1.5rem;
        margin-bottom: 0.5rem;
    }

    .stat-info h4 {
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--gray-600);
        margin-bottom: 0.5rem;
    }

    .stat-info p {
        font-size: 1.5rem;
        font-weight: 700;
        margin: 0;
        color: var(--gray-800);
    }

    .stat-info small {
        font-size: 0.65rem;
        color: var(--gray-500);
        display: block;
        margin-top: 0.25rem;
    }

    /* Search Bar */
    .search-section {
        margin-bottom: 1.5rem;
        display: flex;
        justify-content: flex-end;
    }

    .search-box {
        display: flex;
        gap: 0.75rem;
        align-items: center;
    }

    .search-input {
        width: 320px;
        border: 1px solid var(--gray-200);
        border-radius: 14px;
        padding: 0.75rem 1rem;
        background: white;
        outline: none;
        font-size: 0.875rem;
        transition: all 0.2s ease;
    }

    .search-input:focus {
        border-color: var(--primary-green);
        box-shadow: 0 0 0 3px rgba(5,150,105,0.1);
    }

    .btn-search {
        background: linear-gradient(135deg, var(--primary-green), var(--primary-dark));
        color: white;
        border: none;
        padding: 0.75rem 1.5rem;
        border-radius: 14px;
        font-weight: 600;
        font-size: 0.875rem;
        cursor: pointer;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .btn-search:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(5,150,105,0.3);
    }

    /* Table Container */
    .table-container {
        background: white;
        border-radius: 24px;
        overflow: hidden;
        border: 1px solid var(--gray-100);
        box-shadow: 0 8px 25px rgba(0,0,0,0.06);
    }

    .table-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid var(--gray-100);
        background: var(--gray-50);
        flex-wrap: wrap;
        gap: 1rem;
    }

    .header-title {
        font-size: 1rem;
        font-weight: 700;
        color: var(--gray-800);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .header-title::before {
        content: "📋";
        font-size: 1rem;
    }

    .btn-outline-custom {
        background: linear-gradient(135deg, var(--primary-green), var(--primary-dark));
        color: white;
        padding: 0.625rem 1.5rem;
        border-radius: 12px;
        text-decoration: none;
        font-size: 0.875rem;
        font-weight: 600;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        border: none;
        cursor: pointer;
    }

    .btn-outline-custom:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(5,150,105,0.3);
        color: white;
    }

    /* Table */
    .table {
        margin: 0;
    }

    .table thead {
        background: var(--gray-50);
    }

    .table thead th {
        border: none;
        padding: 1rem;
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--gray-600);
        font-weight: 600;
        border-bottom: 2px solid var(--gray-200);
    }

    .table tbody td {
        padding: 1rem;
        vertical-align: middle;
        border-bottom: 1px solid var(--gray-100);
        color: var(--gray-700);
    }

    .table tbody tr {
        transition: background 0.2s ease;
    }

    .table tbody tr:hover {
        background: var(--primary-bg);
    }

    /* Badges */
    .badge-code {
        background: var(--primary-soft);
        color: var(--primary-dark);
        padding: 0.375rem 0.875rem;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 700;
        display: inline-block;
    }

    .school-name {
        font-weight: 700;
        color: var(--gray-800);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .school-name::before {
        content: "🏛️";
        font-size: 0.875rem;
    }

    .teacher-count {
        font-weight: 700;
        color: var(--primary-green);
        font-size: 1rem;
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
    }

    .teacher-count::before {
        content: "👨‍🏫";
        font-size: 0.875rem;
    }

    .capacity-count {
        font-weight: 700;
        color: var(--primary-dark);
        font-size: 1rem;
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
    }

    .capacity-count::before {
        content: "📊";
        font-size: 0.875rem;
    }

    .vacancy-count {
        font-weight: 700;
        color: #dc2626;
        font-size: 1rem;
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
    }

    .vacancy-count::before {
        content: "🎯";
        font-size: 0.875rem;
    }

    /* Edit Button */
    .btn-edit {
        border: none;
        background: var(--gray-100);
        color: var(--gray-700);
        padding: 0.5rem 1rem;
        border-radius: 10px;
        font-size: 0.75rem;
        font-weight: 600;
        transition: all 0.2s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
    }

    .btn-edit:hover {
        background: var(--primary-green);
        color: white;
        transform: translateY(-1px);
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 3rem;
        color: var(--gray-500);
    }

    /* Modal Styles */
    .modal-content {
        border-radius: 24px !important;
        border: none !important;
        overflow: hidden !important;
    }

    .modal-header {
        background: linear-gradient(135deg, var(--primary-green), var(--primary-dark)) !important;
        color: white !important;
        border: none !important;
        padding: 1.25rem 1.5rem !important;
    }

    .modal-title {
        font-weight: 700 !important;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .modal-body {
        padding: 1.5rem !important;
    }

    .modal-footer {
        border: none !important;
        padding: 1rem 1.5rem 1.5rem !important;
        gap: 0.75rem;
    }

    .form-label {
        font-weight: 600;
        font-size: 0.8rem;
        color: var(--gray-700);
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        gap: 0.375rem;
    }

    .form-control {
        border: 1px solid var(--gray-200);
        border-radius: 12px;
        padding: 0.75rem 1rem;
        font-size: 0.875rem;
        transition: all 0.2s ease;
    }

    .form-control:focus {
        border-color: var(--primary-green);
        box-shadow: 0 0 0 3px rgba(5,150,105,0.1);
    }

    /* Responsive */
    @media (max-width: 768px) {
        .page-title {
            font-size: 1.5rem;
        }

        .stats-summary {
            grid-template-columns: 1fr;
        }

        .search-section {
            justify-content: center;
        }

        .search-box {
            width: 100%;
        }

        .search-input {
            flex: 1;
            width: auto;
        }

        .table-header {
            flex-direction: column;
            text-align: center;
        }

        .table-container {
            overflow-x: auto;
        }

        .table {
            min-width: 600px;
        }
    }
</style>
@endsection

@section('content')

<div class="container py-4">

    <h2 class="page-title">All School Records</h2>

    {{-- Statistics Summary --}}
    @php
        $totalTeachers = $schools->sum('totalTeacher');
        $totalCapacity = $schools->sum(function($school) {
            return ($school->totalTeacher ?? 0) + ($school->vacancy ?? 0);
        });
        $totalVacancy = $schools->sum('vacancy');
        $totalSchools = $schools->count();
    @endphp

    <div class="stats-summary">
        <div class="stat-card">
            <div class="stat-icon">🏫</div>
            <div class="stat-info">
                <h4>Total Schools</h4>
                <p>{{ $totalSchools }}</p>
                <small>Registered institutions</small>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">👨‍🏫</div>
            <div class="stat-info">
                <h4>Total Teachers</h4>
                <p>{{ $totalTeachers }}</p>
                <small>Across all schools</small>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">📊</div>
            <div class="stat-info">
                <h4>Total Capacity</h4>
                <p>{{ $totalCapacity }}</p>
                <small>Maximum teacher slots</small>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">🎯</div>
            <div class="stat-info">
                <h4>Total Vacancy</h4>
                <p>{{ $totalVacancy }}</p>
                <small>Open positions</small>
            </div>
        </div>
    </div>

    {{-- Search Section --}}
    <div class="search-section">
        <form method="GET" action="{{ route('school.list') }}" class="search-box">
            <input type="text"
                   name="search"
                   class="search-input"
                   placeholder="🔍 Search school name, code or address..."
                   value="{{ request('search') }}">
            <button type="submit" class="btn-search">
                🔍 Search
            </button>
            @if(request('search'))
                <a href="{{ route('school.list') }}" class="btn-search" style="background: var(--gray-200); color: var(--gray-700);">
                    ✖ Clear
                </a>
            @endif
        </form>
    </div>

    <div class="table-container">
        <div class="table-header">
            <div class="header-title">
                School Management Records
            </div>
            <button type="button" class="btn-outline-custom" data-bs-toggle="modal" data-bs-target="#registerSchoolModal">
                ➕ Register New School
            </button>
        </div>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>School Code</th>
                        <th>School Name</th>
                        <th class="text-center">Teacher</th>
                        <th class="text-center">Capacity</th>
                        <th class="text-center">Vacancy</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($schools as $school)
                    <tr>
                        <td>
                            <span class="badge-code">
                                {{ $school->schoolID }}
                            </span>
                        </td>
                        <td>
                            <div class="school-name">
                                {{ $school->schoolName }}
                            </div>
                            <small style="color: var(--gray-500); font-size: 0.7rem;">
                                {{ $school->schoolAddress ? Str::limit($school->schoolAddress, 40) : 'No address' }}
                            </small>
                        </td>
                        <td class="text-center">
                            <span class="teacher-count">
                                {{ $school->totalTeacher ?? 0 }}
                            </span>
                        </td>
                        <td class="text-center">
                            <span class="capacity-count">
                                {{ ($school->totalTeacher ?? 0) + ($school->vacancy ?? 0) }}
                            </span>
                        </td>
                        <td class="text-center">
                            <span class="vacancy-count">
                                {{ $school->vacancy ?? 0 }}
                            </span>
                        </td>
                        <td class="text-center">
                            <a href="{{ route('school.edit', $school->schoolID) }}" class="btn-edit">
                                ✏️ Edit
                            </a>
                        </td>
                    </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="empty-state">
                                No schools found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- REGISTER SCHOOL MODAL -->
<div class="modal fade" id="registerSchoolModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">🏫 Register New School</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <form action="{{ route('school.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                🆔 School ID
                            </label>
                            <input type="text" name="schoolID" class="form-control" placeholder="Enter school ID" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                📛 School Name
                            </label>
                            <input type="text" name="schoolName" class="form-control" placeholder="Enter school name" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                            📍 School Address
                        </label>
                        <textarea name="schoolAddress" class="form-control" rows="3" placeholder="Enter complete address" required></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                📅 Register Date
                            </label>
                            <input type="date" name="registerDate" class="form-control">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                📞 Phone Number
                            </label>
                            <input type="text" name="phoneNumber" class="form-control" placeholder="e.g., +60 12 345 6789">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                👨‍🏫 Total Teacher
                            </label>
                            <input type="number" name="totalTeacher" class="form-control" placeholder="0" min="0" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                🎯 Vacancy
                            </label>
                            <input type="number" name="vacancy" class="form-control" placeholder="0" min="0" required>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-secondary" data-bs-dismiss="modal" style="background: white; border: 1px solid var(--gray-200); color: var(--gray-700); padding: 0.625rem 1.5rem; border-radius: 12px; font-weight: 600;">
                        ❌ Cancel
                    </button>
                    <button type="submit" class="btn-success" style="background: linear-gradient(135deg, var(--primary-green), var(--primary-dark)); border: none; padding: 0.625rem 1.5rem; border-radius: 12px; font-weight: 600; color: white;">
                        💾 Register School
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection