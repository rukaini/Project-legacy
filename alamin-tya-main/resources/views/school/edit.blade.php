@extends('layouts.app')
@section('title', 'Edit School')

@section('content')
<div class="ui-page-shell" style="max-width:900px;margin:0 auto;">
    {{-- Page Header --}}
    <div class="ui-page-heading">
        <div class="ui-page-heading__copy">
            <h1 style="display:flex;align-items:center;gap:10px;">
                <i class="fas fa-edit" style="color:var(--app-primary);font-size:24px;"></i> Edit School
            </h1>
            <p>School ID: <strong>{{ $school->schoolID }}</strong></p>
        </div>
        <div class="ui-page-heading__actions">
            <span class="ui-badge ui-badge--info">
                <i class="fas fa-building"></i> {{ $school->schoolID }}
            </span>
        </div>
    </div>

    {{-- Alert Messages --}}
    @if(session('error'))
        <div class="ui-message ui-message--error" style="margin-bottom:16px;">
            <i class="fas fa-exclamation-triangle"></i>
            <div>{{ session('error') }}</div>
        </div>
    @endif

    @if(session('info'))
        <div class="ui-message" style="border-color:#bfdbfe;background:#eff6ff;color:#1d4ed8;margin-bottom:16px;">
            <i class="fas fa-info-circle"></i>
            <div>{{ session('info') }}</div>
        </div>
    @endif

    @if(session('success'))
        <div class="ui-message" style="border-color:#a7f3d0;background:#ecfdf5;color:#065f46;margin-bottom:16px;">
            <i class="fas fa-check-circle"></i>
            <div>{{ session('success') }}</div>
        </div>
    @endif

    {{-- Edit Form --}}
    <div class="ui-card ui-card--section">
        <div class="ui-card__header">
            <h3 style="margin:0;font-size:14px;font-weight:800;color:var(--app-text);display:flex;align-items:center;gap:8px;">
                <i class="fas fa-school" style="color:var(--app-primary);"></i> School Information
            </h3>
            <span class="ui-badge ui-badge--warning">
                <i class="fas fa-edit"></i> Edit Mode
            </span>
        </div>
        <div class="ui-card__body">
            <form action="{{ route('school.update', $school->schoolID) }}" method="POST" id="schoolForm">
                @csrf
                @method('PUT')

                {{-- School ID (Read Only) --}}
                <div class="ui-field" style="margin-bottom:16px;">
                    <label class="ui-label" style="display:flex;align-items:center;gap:6px;">
                        <i class="fas fa-id-card" style="color:var(--app-primary);"></i> School ID (Read Only)
                    </label>
                    <input type="text" class="ui-input" value="{{ $school->schoolID }}" readonly style="background:var(--app-divider);color:var(--app-text-secondary);cursor:not-allowed;">
                    <div class="ui-field__helper">School ID cannot be changed</div>
                </div>

                {{-- School Name --}}
                <div class="ui-field" style="margin-bottom:16px;">
                    <label class="ui-label required" style="display:flex;align-items:center;gap:6px;">
                        <i class="fas fa-signature" style="color:var(--app-primary);"></i> School Name
                    </label>
                    <input type="text" 
                           name="schoolName" 
                           class="ui-input" 
                           value="{{ old('schoolName', $school->schoolName) }}" 
                           required
                           placeholder="Enter school name">
                    @error('schoolName')
                        <div class="ui-field__error">{{ $message }}</div>
                    @enderror
                </div>

                {{-- School Address --}}
                <div class="ui-field" style="margin-bottom:16px;">
                    <label class="ui-label required" style="display:flex;align-items:center;gap:6px;">
                        <i class="fas fa-map-marker-alt" style="color:var(--app-primary);"></i> School Address
                    </label>
                    <textarea name="schoolAddress" 
                              class="ui-textarea" 
                              rows="3" 
                              required
                              placeholder="Enter complete address">{{ old('schoolAddress', $school->schoolAddress) }}</textarea>
                    @error('schoolAddress')
                        <div class="ui-field__error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row g-3">
                    {{-- Register Date --}}
                    <div class="col-md-6">
                        <div class="ui-field" style="margin-bottom:16px;">
                            <label class="ui-label" style="display:flex;align-items:center;gap:6px;">
                                <i class="fas fa-calendar-alt" style="color:var(--app-primary);"></i> Register Date
                            </label>
                            <input type="date" 
                                   name="registerDate" 
                                   class="ui-input" 
                                   value="{{ old('registerDate', $school->registerDate) }}">
                            @error('registerDate')
                                <div class="ui-field__error">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Phone Number --}}
                    <div class="col-md-6">
                        <div class="ui-field" style="margin-bottom:16px;">
                            <label class="ui-label" style="display:flex;align-items:center;gap:6px;">
                                <i class="fas fa-phone" style="color:var(--app-primary);"></i> Phone Number
                            </label>
                            <input type="text" 
                                   name="phoneNumber" 
                                   class="ui-input" 
                                   value="{{ old('phoneNumber', $school->phoneNumber ?? '') }}"
                                   placeholder="e.g., 03-42567890">
                            @error('phoneNumber')
                                <div class="ui-field__error">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row g-3">
                    {{-- School Capacity --}}
                    <div class="col-md-6">
                        <div class="ui-field" style="margin-bottom:16px;">
                            <label class="ui-label required" style="display:flex;align-items:center;gap:6px;">
                                <i class="fas fa-users" style="color:var(--app-primary);"></i> School Capacity
                            </label>
                            <input type="number" 
                                   name="capacity" 
                                   id="capacityInput"
                                   class="ui-input" 
                                   value="{{ old('capacity', $school->capacity ?? 15) }}" 
                                   min="0"
                                   required
                                   oninput="calculateVacancy()"
                                   style="border-color:var(--app-warning);background:var(--app-warning-soft);font-weight:600;">
                            <div class="ui-field__helper">Maximum number of teachers allowed</div>
                            @error('capacity')
                                <div class="ui-field__error">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Total Teacher --}}
                    <div class="col-md-6">
                        <div class="ui-field" style="margin-bottom:16px;">
                            <label class="ui-label required" style="display:flex;align-items:center;gap:6px;">
                                <i class="fas fa-chalkboard-user" style="color:var(--app-primary);"></i> Total Teacher
                            </label>
                            <input type="number" 
                                   name="totalTeacher" 
                                   id="totalTeacher"
                                   class="ui-input" 
                                   value="{{ old('totalTeacher', $school->totalTeacher) }}" 
                                   min="0"
                                   required
                                   oninput="calculateVacancy()">
                            @error('totalTeacher')
                                <div class="ui-field__error">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Vacancy (Auto-calculated) --}}
                <div class="ui-field" style="margin-bottom:16px;">
                    <label class="ui-label required" style="display:flex;align-items:center;gap:6px;">
                        <i class="fas fa-bullseye" style="color:var(--app-primary);"></i> Vacancy (Auto-calculated)
                    </label>
                    <input type="number" 
                           name="vacancy" 
                           id="vacancy"
                           class="ui-input" 
                           value="{{ old('vacancy', $school->vacancy) }}" 
                           min="0"
                           readonly
                           required
                           style="background:var(--app-primary-soft);color:var(--app-primary);font-weight:700;border-color:var(--app-primary-border);">
                    <div class="ui-field__helper">Auto-calculated: Capacity - Total Teacher</div>
                    @error('vacancy')
                        <div class="ui-field__error">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Real-time Statistics --}}
                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;padding:16px;background:var(--app-background);border-radius:var(--app-radius-card);margin:16px 0;">
                    <div style="text-align:center;" id="statTotal">
                        <div style="font-size:10px;font-weight:850;color:var(--app-text-subtle);letter-spacing:0.06em;text-transform:uppercase;">Total Teachers</div>
                        <div style="font-size:24px;font-weight:800;color:var(--app-primary);" id="totalDisplay">{{ $school->totalTeacher ?? 0 }}</div>
                    </div>
                    <div style="text-align:center;" id="statCapacity">
                        <div style="font-size:10px;font-weight:850;color:var(--app-text-subtle);letter-spacing:0.06em;text-transform:uppercase;">Capacity</div>
                        <div style="font-size:24px;font-weight:800;color:var(--app-warning);" id="capacityDisplay">{{ $school->capacity ?? 15 }}</div>
                    </div>
                    <div style="text-align:center;" id="statVacancy">
                        <div style="font-size:10px;font-weight:850;color:var(--app-text-subtle);letter-spacing:0.06em;text-transform:uppercase;">Vacancy</div>
                        <div style="font-size:24px;font-weight:800;color:var(--app-success);" id="vacancyDisplay">{{ $school->vacancy ?? 0 }}</div>
                    </div>
                </div>

                {{-- Dynamic Status Messages --}}
                <div id="statusMessages" style="margin-bottom:16px;"></div>

                {{-- Action Buttons --}}
                <div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:8px;">
                    <button type="submit" class="ui-button ui-button--primary" id="submitBtn" style="min-height:44px;padding:0 28px;">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                    <a href="{{ route('school.show', $school->schoolID) }}" class="ui-button ui-button--compact" style="min-height:44px;padding:0 20px;">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                    <a href="{{ route('school.list') }}" class="ui-button ui-button--compact" style="min-height:44px;padding:0 20px;">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function calculateVacancy() {
    const totalTeacher = parseInt(document.getElementById('totalTeacher').value) || 0;
    const capacity = parseInt(document.getElementById('capacityInput').value) || 0;
    const vacancy = capacity - totalTeacher;
    const finalVacancy = vacancy >= 0 ? vacancy : 0;
    
    // Update displays
    document.getElementById('vacancy').value = finalVacancy;
    document.getElementById('totalDisplay').textContent = totalTeacher;
    document.getElementById('capacityDisplay').textContent = capacity;
    document.getElementById('vacancyDisplay').textContent = finalVacancy;
    
    // Get stat elements
    const statVacancy = document.getElementById('statVacancy');
    const statTotal = document.getElementById('statTotal');
    const statCapacity = document.getElementById('statCapacity');
    
    // Reset colors
    statTotal.querySelector('div:last-child').style.color = 'var(--app-primary)';
    statCapacity.querySelector('div:last-child').style.color = 'var(--app-warning)';
    statVacancy.querySelector('div:last-child').style.color = 'var(--app-success)';
    
    // Update status messages
    const statusDiv = document.getElementById('statusMessages');
    const submitBtn = document.getElementById('submitBtn');
    
    if (capacity === 0) {
        statusDiv.innerHTML = `
            <div class="ui-message ui-message--error" style="margin:0;">
                <i class="fas fa-exclamation-triangle"></i>
                <div><strong>Warning:</strong> School capacity is set to 0. No teachers can be assigned.</div>
            </div>
        `;
        submitBtn.disabled = true;
        submitBtn.style.opacity = '0.5';
        submitBtn.style.cursor = 'not-allowed';
        return;
    }
    
    if (vacancy < 0) {
        // Over capacity
        statusDiv.innerHTML = `
            <div class="ui-message ui-message--error" style="margin:0;">
                <i class="fas fa-exclamation-triangle"></i>
                <div>
                    <strong>Over Capacity!</strong> Total teachers (${totalTeacher}) exceeds school capacity (${capacity}) by ${Math.abs(vacancy)} teacher(s).
                    <br>Please increase capacity or reduce teacher count.
                </div>
            </div>
        `;
        submitBtn.disabled = true;
        submitBtn.style.opacity = '0.5';
        submitBtn.style.cursor = 'not-allowed';
        
        statVacancy.querySelector('div:last-child').style.color = 'var(--app-danger)';
        statTotal.querySelector('div:last-child').style.color = 'var(--app-danger)';
        
    } else if (vacancy === 0) {
        // Full capacity
        statusDiv.innerHTML = `
            <div class="ui-message" style="margin:0;border-color:#fde68a;background:#fffbeb;color:#92400e;">
                <i class="fas fa-info-circle"></i>
                <div><strong>Full Capacity:</strong> School is at full capacity (${totalTeacher}/${capacity}). No more teachers can be added.</div>
            </div>
        `;
        submitBtn.disabled = false;
        submitBtn.style.opacity = '1';
        submitBtn.style.cursor = 'pointer';
        
        statVacancy.querySelector('div:last-child').style.color = 'var(--app-warning)';
        statTotal.querySelector('div:last-child').style.color = 'var(--app-success)';
        
    } else if (vacancy <= 3) {
        // Low vacancy
        statusDiv.innerHTML = `
            <div class="ui-message" style="margin:0;border-color:#fde68a;background:#fffbeb;color:#92400e;">
                <i class="fas fa-exclamation-triangle"></i>
                <div><strong>Low Vacancy:</strong> Only ${vacancy} slot(s) available. Consider increasing capacity.</div>
            </div>
        `;
        submitBtn.disabled = false;
        submitBtn.style.opacity = '1';
        submitBtn.style.cursor = 'pointer';
        
        statVacancy.querySelector('div:last-child').style.color = 'var(--app-warning)';
        statTotal.querySelector('div:last-child').style.color = 'var(--app-success)';
        
    } else {
        // Good capacity
        statusDiv.innerHTML = `
            <div class="ui-message" style="margin:0;border-color:#a7f3d0;background:#ecfdf5;color:#065f46;">
                <i class="fas fa-check-circle"></i>
                <div><strong>Good Standing:</strong> ${vacancy} slot(s) available for new teachers.</div>
            </div>
        `;
        submitBtn.disabled = false;
        submitBtn.style.opacity = '1';
        submitBtn.style.cursor = 'pointer';
        
        statVacancy.querySelector('div:last-child').style.color = 'var(--app-success)';
        statTotal.querySelector('div:last-child').style.color = 'var(--app-success)';
    }
}

// Calculate on page load
document.addEventListener('DOMContentLoaded', function() {
    calculateVacancy();
});
</script>

<style>
    .required::after {
        content: "*";
        color: var(--app-danger);
        margin-left: 4px;
        font-weight: 700;
    }
    
    #submitBtn:disabled {
        opacity: 0.5 !important;
        cursor: not-allowed !important;
    }
</style>
@endsection