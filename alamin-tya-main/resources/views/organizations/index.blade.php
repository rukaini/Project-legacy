@extends('layouts.app')

@section('title', 'Organizations')

@section('content')
<div class="ui-page-shell">
    {{-- Page Heading --}}
    <div class="ui-page-heading">
        <div class="ui-page-heading__copy">
            <h1 style="display:flex;align-items:center;gap:10px;">
                <i class="fas fa-sitemap" style="color:var(--app-primary);font-size:24px;"></i> Organizations
            </h1>
            <p>Manage all organizations in the system</p>
        </div>
        <div class="ui-page-heading__actions">
            <span class="ui-badge ui-badge--info">
                <i class="fas fa-building"></i> {{ $totalOrganizations ?? 0 }} Organizations
            </span>
            <button type="button" class="ui-button ui-button--primary" onclick="openModal('registerModal')" style="min-height:36px;padding:0 16px;display:inline-flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;">
                <i class="fas fa-plus"></i> Register
            </button>
            <button type="button" class="ui-button ui-button--outline" onclick="openModal('csvModal')" style="min-height:36px;padding:0 16px;display:inline-flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;">
                <i class="fas fa-file-import"></i> Import CSV
            </button>
            <a href="{{ route('orgs.download-template') }}" class="ui-button ui-button--compact" style="min-height:36px;padding:0 16px;display:inline-flex;align-items:center;gap:6px;font-size:13px;">
                <i class="fas fa-download"></i> Template
            </a>
        </div>
    </div>

    {{-- Alert Messages --}}
    @if(session('success'))
        <div class="ui-message" style="border-color:#a7f3d0;background:#ecfdf5;color:#065f46;margin-bottom:16px;display:flex;justify-content:space-between;align-items:center;">
            <div style="display:flex;align-items:center;gap:8px;">
                <i class="fas fa-check-circle"></i>
                <span>{{ session('success') }}</span>
            </div>
            <button type="button" class="ui-button ui-button--icon" style="width:28px;height:28px;font-size:14px;" onclick="this.closest('.ui-message').remove();">
                <i class="fas fa-times"></i>
            </button>
        </div>
    @endif

    @if(session('status'))
        <div class="ui-message" style="border-color:#a7f3d0;background:#ecfdf5;color:#065f46;margin-bottom:16px;display:flex;justify-content:space-between;align-items:center;">
            <div style="display:flex;align-items:center;gap:8px;">
                <i class="fas fa-check-circle"></i>
                <span>{{ session('status') }}</span>
            </div>
            <button type="button" class="ui-button ui-button--icon" style="width:28px;height:28px;font-size:14px;" onclick="this.closest('.ui-message').remove();">
                <i class="fas fa-times"></i>
            </button>
        </div>
    @endif

    @if(session('error'))
        <div class="ui-message ui-message--error" style="margin-bottom:16px;">
            <i class="fas fa-exclamation-triangle"></i>
            <div>{{ session('error') }}</div>
        </div>
    @endif

    @if(session('import_errors') && count(session('import_errors')) > 0)
        <div class="ui-message" style="border-color:#fde68a;background:#fffbeb;color:#92400e;margin-bottom:16px;">
            <i class="fas fa-exclamation-triangle" style="color:var(--app-warning);"></i>
            <div>
                <strong>Import completed with errors:</strong>
                <ul style="margin:8px 0 0 20px;padding:0;max-height:300px;overflow-y:auto;">
                    @foreach(session('import_errors') as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    {{-- Statistics --}}
    <div class="row g-3 mb-4">
        <div class="col-md-12">
            <div class="ui-card ui-card--stat-admin text-center">
                <div class="ui-card__label">Total Organizations</div>
                <div class="ui-card__value">{{ $totalOrganizations ?? 0 }}</div>
                <div style="font-size:11px;color:var(--app-text-subtle);margin-top:4px;">
                    <i class="fas fa-building"></i> All Organizations
                </div>
            </div>
        </div>
    </div>

    {{-- Search --}}
    <div style="display:flex;flex-wrap:wrap;gap:12px;margin-bottom:20px;align-items:center;justify-content:space-between;">
        <form method="GET" action="{{ route('orgs.index') }}" style="display:flex;gap:10px;flex-wrap:wrap;flex:1;">
            <div style="position:relative;flex:1;min-width:200px;">
                <i class="fas fa-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--app-text-subtle);font-size:14px;"></i>
                <input type="text" name="search" class="ui-input" placeholder="Search by name, ID, address..." value="{{ request('search') }}" style="padding-left:36px;height:40px;">
            </div>
            <button type="submit" class="ui-button ui-button--primary" style="height:40px;padding:0 20px;">
                <i class="fas fa-search"></i> Search
            </button>
            @if(request('search'))
                <a href="{{ route('orgs.index') }}" class="ui-button ui-button--compact" style="height:40px;padding:0 16px;">
                    <i class="fas fa-times"></i> Clear
                </a>
            @endif
        </form>
    </div>

    {{-- Organizations Table --}}
    <div class="ui-card ui-card--section">
        <div class="ui-card__header">
            <h3 style="margin:0;font-size:14px;font-weight:800;color:var(--app-text);display:flex;align-items:center;gap:8px;">
                <i class="fas fa-list" style="color:var(--app-primary);"></i> Organization List
            </h3>
            <span class="ui-badge ui-badge--info">
                <i class="fas fa-building"></i> {{ $organizations->total() ?? 0 }} Organizations
            </span>
        </div>
        <div class="ui-card__body" style="padding:0;">
            <div class="ui-table-wrap">
                <table class="ui-table ui-table--dashboard">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Address</th>
                            <th>Phone</th>
                            <th>Register Date</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($organizations as $org)
                        <tr>
                            <td>
                                <span class="ui-badge ui-badge--info" style="font-size:11px;">
                                    {{ $org->OrganizationID }}
                                </span>
                            </td>
                            <td style="font-weight:700;color:var(--app-text);">
                                <i class="fas fa-building" style="color:var(--app-primary);font-size:12px;"></i>
                                {{ $org->OrganizationName }}
                            </td>
                            <td style="font-size:13px;color:var(--app-text-secondary);">
                                {{ Str::limit($org->OrganizationAddress ?? '-', 50) }}
                            </td>
                            <td style="font-size:13px;color:var(--app-text-secondary);">
                                {{ $org->PhoneNumber ?? '-' }}
                            </td>
                            <td style="font-size:13px;color:var(--app-text-secondary);">
                                {{ $org->RegisterDate ? \Carbon\Carbon::parse($org->RegisterDate)->format('d/m/Y') : '-' }}
                            </td>
                            <td class="text-center">
                                <div style="display:flex;gap:6px;justify-content:center;flex-wrap:wrap;">
                                    <a href="{{ route('orgs.show', $org->OrganizationID) }}"
                                       class="ui-button ui-button--compact"
                                       style="font-size:11px;padding:4px 14px;min-height:30px;border-color:var(--app-primary-border);color:var(--app-primary);">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <a href="{{ route('orgs.edit', $org->OrganizationID) }}"
                                       class="ui-button ui-button--compact"
                                       style="font-size:11px;padding:4px 14px;min-height:30px;">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <form action="{{ route('orgs.destroy', $org->OrganizationID) }}" method="POST" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to delete this organization?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="ui-button" style="font-size:11px;padding:4px 14px;min-height:30px;background:var(--app-danger);color:#fff;border:1px solid var(--app-danger);border-radius:var(--app-radius-control);cursor:pointer;">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6">
                                <div class="ui-state ui-state--compact">
                                    <div style="font-size:3rem;margin-bottom:8px;">🏢</div>
                                    <div class="ui-state__title">No Organizations Found</div>
                                    <div class="ui-state__copy">
                                        @if(request('search'))
                                            No organizations match your search criteria.
                                        @else
                                            No organizations have been registered yet.
                                        @endif
                                    </div>
                                    @if(request('search'))
                                        <a href="{{ route('orgs.index') }}" class="ui-button ui-button--primary" style="margin-top:16px;">
                                            <i class="fas fa-times"></i> Clear Search
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($organizations->hasPages())
            <div style="padding:12px 20px;border-top:1px solid var(--app-divider);">
                {{ $organizations->links() }}
            </div>
        @endif
        <div style="padding:8px 20px;border-top:1px solid var(--app-divider);display:flex;justify-content:space-between;flex-wrap:wrap;gap:8px;font-size:12px;color:var(--app-text-subtle);">
            <span>Showing {{ $organizations->firstItem() ?? 0 }} to {{ $organizations->lastItem() ?? 0 }} of {{ $organizations->total() }} results</span>
            <span>Last updated: {{ now()->format('d/m/Y H:i:s') }}</span>
        </div>
    </div>
</div>

{{-- ============================================================ --}}
{{-- REGISTER MODAL --}}
{{-- ============================================================ --}}
<div id="registerModal" class="modal" style="display:none;">
    <div class="modal-box">
        <div class="modal-header">
            <h3 style="font-size:1.25rem;font-weight:700;margin:0;display:flex;align-items:center;gap:10px;color:#fff;">
                <i class="fas fa-plus-circle"></i> Register New Organization
            </h3>
            <button type="button" class="modal-close" onclick="closeModal('registerModal')" style="background:rgba(255,255,255,0.2);border:none;border-radius:50%;width:36px;height:36px;display:flex;align-items:center;justify-content:center;cursor:pointer;color:#fff;font-size:1.4rem;transition:all 0.2s;">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form method="POST" action="{{ route('orgs.store') }}">
            @csrf
            <div class="modal-body">
                {{-- Validation errors --}}
                @if($errors->any())
                    <div style="background:#fee2e2;color:#991b1b;padding:12px 16px;border-radius:12px;margin-bottom:16px;font-size:0.8rem;">
                        <div style="font-weight:700;margin-bottom:6px;">
                            <i class="fas fa-exclamation-triangle"></i> Please fix these errors:
                        </div>
                        <ul style="margin:0;padding-left:20px;">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Section: Identity --}}
                <div style="background:var(--app-primary-soft);padding:0.4rem 1rem;border-radius:40px;font-size:0.75rem;font-weight:600;color:var(--app-primary-dark);margin-bottom:1.2rem;display:inline-block;">
                    🏢 Organization Information
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <div class="form-label"><i class="fas fa-id-card"></i> Organization ID <span style="color:#e11d48;">*</span></div>
                        <input name="OrganizationID" class="form-control-custom" placeholder="e.g. ORG001" value="{{ old('OrganizationID') }}" required>
                    </div>
                    <div class="form-group">
                        <div class="form-label"><i class="fas fa-signature"></i> Organization Name <span style="color:#e11d48;">*</span></div>
                        <input name="OrganizationName" class="form-control-custom" placeholder="Ministry of Education" value="{{ old('OrganizationName') }}" required>
                    </div>
                </div>

                <div class="form-group">
                    <div class="form-label"><i class="fas fa-map-marker-alt"></i> Organization Address</div>
                    <textarea name="OrganizationAddress" class="form-control-custom" rows="3" placeholder="Level 5, Block E, Complex Government, Putrajaya">{{ old('OrganizationAddress') }}</textarea>
                </div>

                <div class="divider-light"></div>

                {{-- Section: Contact --}}
                <div style="background:var(--app-primary-soft);padding:0.4rem 1rem;border-radius:40px;font-size:0.75rem;font-weight:600;color:var(--app-primary-dark);margin-bottom:1.2rem;display:inline-block;">
                    📞 Contact Information
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <div class="form-label"><i class="fas fa-calendar-alt"></i> Register Date</div>
                        <input type="date" name="RegisterDate" class="form-control-custom" value="{{ old('RegisterDate') }}">
                    </div>
                    <div class="form-group">
                        <div class="form-label"><i class="fas fa-phone"></i> Phone Number</div>
                        <input name="PhoneNumber" class="form-control-custom" placeholder="03-8000-0000" value="{{ old('PhoneNumber') }}">
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="submit" class="btn-custom btn-save">
                        <i class="fas fa-save"></i> Save Organization
                    </button>
                    <button type="button" class="btn-light-custom btn-cancel-modal" onclick="closeModal('registerModal')">
                        Cancel
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- ============================================================ --}}
{{-- CSV MODAL --}}
{{-- ============================================================ --}}
<div id="csvModal" class="modal" style="display:none;">
    <div class="modal-box">
        <div class="modal-header">
            <h3 style="font-size:1.25rem;font-weight:700;margin:0;display:flex;align-items:center;gap:10px;color:#fff;">
                <i class="fas fa-file-import"></i> Import from CSV
            </h3>
            <button type="button" class="modal-close" onclick="closeModal('csvModal')" style="background:rgba(255,255,255,0.2);border:none;border-radius:50%;width:36px;height:36px;display:flex;align-items:center;justify-content:center;cursor:pointer;color:#fff;font-size:1.4rem;transition:all 0.2s;">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <form method="POST" action="{{ route('orgs.import') }}" enctype="multipart/form-data">
                @csrf
                <div class="form-group">
                    <div class="form-label"><i class="fas fa-file-csv"></i> Choose File</div>
                    <input type="file" name="csvfile" class="form-control-custom" accept=".csv,.txt" required>
                    <p style="font-size: 0.7rem; color: #64748b; margin-top: 8px;">✓ Supported formats: .csv, .txt</p>
                    <p style="font-size: 0.7rem; color: #64748b; margin-top: 4px;">✓ Required columns: OrganizationID*, OrganizationName*, OrganizationAddress, RegisterDate, PhoneNumber</p>
                    <p style="font-size: 0.7rem; color: #d97706; margin-top: 4px;">⚠️ Duplicates will be skipped.</p>
                    <p style="font-size: 0.7rem; margin-top: 8px;">
                        <a href="{{ route('orgs.download-template') }}" style="color:var(--app-primary);text-decoration:none;font-weight:600;">
                            <i class="fas fa-download"></i> Download Template
                        </a>
                    </p>
                </div>
                <div class="modal-actions" style="margin-top: 0.5rem;">
                    <button type="submit" class="btn-custom btn-save"><i class="fas fa-cloud-upload-alt"></i> Upload & Import</button>
                    <button type="button" class="btn-light-custom btn-cancel-modal" onclick="closeModal('csvModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    /* Modal Styles */
    .modal {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.6);
        backdrop-filter: blur(6px);
        justify-content: center;
        align-items: center;
        z-index: 99999;
        padding: 20px;
    }

    .modal.show {
        display: flex !important;
    }

    .modal-box {
        background: white;
        width: 660px;
        max-width: 95%;
        max-height: 90vh;
        overflow-y: auto;
        border-radius: 24px;
        animation: modalSlideIn 0.35s cubic-bezier(0.21, 1.11, 0.35, 1);
        box-shadow: 0 30px 60px -20px rgba(0,0,0,0.35);
    }

    @keyframes modalSlideIn {
        from { opacity: 0; transform: scale(0.96) translateY(-12px); }
        to   { opacity: 1; transform: scale(1) translateY(0); }
    }

    .modal-header {
        background: linear-gradient(135deg, var(--app-primary), var(--app-primary-dark));
        padding: 1.25rem 1.5rem;
        border-radius: 24px 24px 0 0;
        color: white;
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: sticky;
        top: 0;
        z-index: 10;
    }

    .modal-body {
        padding: 1.5rem;
    }

    .form-row {
        display: flex;
        gap: 1rem;
        margin-bottom: 1rem;
        flex-wrap: wrap;
    }

    .form-group {
        flex: 1;
        min-width: 160px;
    }

    .form-label {
        display: block;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: var(--app-text-secondary);
        margin-bottom: 0.4rem;
    }

    .form-label i {
        margin-right: 4px;
        color: var(--app-primary);
    }

    .form-control-custom {
        width: 100%;
        border: 1px solid var(--app-border);
        border-radius: var(--app-radius-control);
        padding: 0.75rem 1rem;
        font-size: 0.9rem;
        transition: all 0.2s ease;
        outline: none;
        background-color: var(--app-surface);
        font-family: inherit;
        box-sizing: border-box;
        color: var(--app-text);
    }

    .form-control-custom:focus {
        border-color: var(--app-primary);
        box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.15);
        background-color: white;
    }

    .form-control-custom:disabled {
        background-color: var(--app-background);
        cursor: not-allowed;
        opacity: 0.7;
    }

    textarea.form-control-custom {
        resize: vertical;
        min-height: 80px;
    }

    .divider-light {
        height: 1px;
        background: linear-gradient(to right, var(--app-border), transparent);
        margin: 1.5rem 0 1rem;
    }

    .modal-actions {
        display: flex;
        gap: 1rem;
        margin-top: 2rem;
        justify-content: flex-end;
        border-top: 1px solid var(--app-divider);
        padding-top: 1.5rem;
        flex-wrap: wrap;
    }

    .btn-custom {
        background: linear-gradient(135deg, var(--app-primary), var(--app-primary-dark));
        color: white;
        border: none;
        padding: 0.625rem 1.5rem;
        border-radius: var(--app-radius-control);
        font-size: 0.875rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .btn-custom:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
    }

    .btn-custom:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
    }

    .btn-save {
        padding: 0.8rem 2rem;
        font-weight: 700;
    }

    .btn-light-custom {
        background: white;
        border: 1px solid var(--app-border);
        color: var(--app-text-secondary);
        padding: 0.625rem 1.5rem;
        border-radius: var(--app-radius-control);
        font-size: 0.875rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .btn-light-custom:hover {
        background: var(--app-primary-soft);
        border-color: var(--app-primary-border);
        color: var(--app-primary-dark);
    }

    .btn-cancel-modal {
        padding: 0.8rem 2rem;
    }

    @media (max-width: 768px) {
        .form-row {
            flex-direction: column;
            gap: 0;
        }
        .modal-box {
            width: 100%;
            max-width: 100%;
        }
        .modal-body {
            padding: 1rem;
        }
    }
</style>

<script>
    function openModal(id) {
        var modal = document.getElementById(id);
        if (modal) {
            modal.style.display = 'flex';
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
        }
    }

    function closeModal(id) {
        var modal = document.getElementById(id);
        if (modal) {
            modal.style.display = 'none';
            modal.classList.remove('show');
            document.body.style.overflow = '';
        }
    }

    // Close modal when clicking outside
    window.onclick = function(e) {
        var registerModal = document.getElementById('registerModal');
        var csvModal = document.getElementById('csvModal');
        if (registerModal && e.target == registerModal) {
            closeModal('registerModal');
        }
        if (csvModal && e.target == csvModal) {
            closeModal('csvModal');
        }
    }

    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeModal('registerModal');
            closeModal('csvModal');
        }
    });

    // Auto-hide success messages after 5 seconds
    setTimeout(function() {
        document.querySelectorAll('.ui-message').forEach(function(alert) {
            if (!alert.classList.contains('ui-message--error')) {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(function() {
                    if (alert.parentElement) alert.remove();
                }, 500);
            }
        });
    }, 5000);

    // Re-open register modal if there were validation errors
    @if($errors->any() && old('OrganizationID'))
        document.addEventListener('DOMContentLoaded', function () {
            openModal('registerModal');
        });
    @endif
</script>
@endsection