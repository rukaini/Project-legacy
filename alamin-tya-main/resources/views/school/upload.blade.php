@extends('layouts.app')

@section('title', 'Import School Data')

@section('content')
<div class="ui-page-shell" style="max-width:1000px;margin:0 auto;">
    {{-- Page Heading --}}
    <div class="ui-page-heading">
        <div class="ui-page-heading__copy">
            <h1 style="display:flex;align-items:center;gap:10px;">
                <i class="fas fa-file-import" style="color:var(--app-primary);font-size:24px;"></i> Import School Data
            </h1>
            <p>Upload a CSV file containing school information</p>
        </div>
        <div class="ui-page-heading__actions">
            <a href="{{ route('school.list') }}" class="ui-button ui-button--compact" style="min-height:36px;padding:0 16px;display:inline-flex;align-items:center;gap:6px;font-size:13px;">
                <i class="fas fa-arrow-left"></i> Back to List
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

    @if($errors->any())
        <div class="ui-message ui-message--error" style="margin-bottom:16px;">
            <i class="fas fa-exclamation-triangle"></i>
            <div>
                <ul style="margin:0;padding-left:20px;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    {{-- Main Card --}}
    <div class="ui-card ui-card--section" style="position:relative;overflow:hidden;">
        {{-- Background Decoration --}}
        <div style="position:absolute;right:-30px;top:-30px;font-size:160px;opacity:0.04;pointer-events:none;z-index:0;">
            📂
        </div>

        {{-- Card Header --}}
        <div class="ui-card__header" style="background:linear-gradient(135deg, var(--app-primary), var(--app-primary-dark));border-radius:18px 18px 0 0;margin:-1px -1px 0 -1px;padding:20px 28px;position:relative;z-index:1;">
            <div>
                <h4 style="margin:0;font-size:20px;font-weight:800;color:#fff;display:flex;align-items:center;gap:10px;">
                    <i class="fas fa-upload"></i> Upload School Data
                </h4>
                <p style="margin:6px 0 0;color:rgba(255,255,255,0.85);font-size:14px;">
                    Please choose a CSV file containing the latest school information
                </p>
            </div>
            <span class="ui-badge" style="background:rgba(255,255,255,0.2);border-color:rgba(255,255,255,0.3);color:#fff;font-size:12px;padding:4px 14px;">
                <i class="fas fa-file-csv"></i> CSV Import
            </span>
        </div>

        {{-- Card Body --}}
        <div class="ui-card__body" style="padding:28px;position:relative;z-index:1;">
            <form action="{{ route('school.import') }}" method="POST" enctype="multipart/form-data">
                @csrf

                {{-- File Drop Zone --}}
                <div class="file-drop-zone" onclick="document.getElementById('fileInput').click()" style="
                    border: 2px dashed var(--app-border);
                    background: var(--app-background);
                    border-radius: var(--app-radius-panel);
                    padding: 40px 24px;
                    text-align: center;
                    transition: all 0.3s ease;
                    cursor: pointer;
                    position: relative;
                ">
                    <div style="font-size:48px;color:var(--app-primary);margin-bottom:12px;">
                        <i class="fas fa-file-spreadsheet"></i>
                    </div>
                    <div style="display:flex;align-items:center;justify-content:center;gap:12px;flex-wrap:wrap;">
                        <input type="file" name="file" id="fileInput" class="ui-input" accept=".csv" required style="display:inline-block;width:auto;max-width:300px;cursor:pointer;padding:8px 12px;">
                        <span style="color:var(--app-text-subtle);font-size:13px;">or click to browse</span>
                    </div>
                    <div class="file-hint" style="font-size:12px;color:var(--app-text-subtle);margin-top:12px;">
                        <i class="fas fa-info-circle"></i> Allowed format: <strong>.csv</strong> only
                    </div>
                </div>

                {{-- Info Strip --}}
                <div style="display:flex;align-items:center;gap:10px;padding:12px 16px;background:var(--app-primary-soft);border-radius:var(--app-radius-card);margin:16px 0;font-size:13px;color:var(--app-text-secondary);">
                    <i class="fas fa-info-circle" style="color:var(--app-primary);font-size:16px;"></i>
                    <span>Make sure the file header follows the specified format below</span>
                </div>

                {{-- Action Buttons --}}
                <div style="display:flex;gap:12px;flex-wrap:wrap;">
                    <a href="{{ route('school.download-template') }}" class="ui-button ui-button--outline" style="min-height:44px;padding:0 24px;display:inline-flex;align-items:center;gap:8px;">
                        <i class="fas fa-download"></i> Download Template
                    </a>
                    <button type="submit" class="ui-button ui-button--primary" style="min-height:44px;padding:0 28px;display:inline-flex;align-items:center;gap:8px;">
                        <i class="fas fa-cloud-upload-alt"></i> Start Importing
                    </button>
                </div>

                {{-- CSV Format Example --}}
                <div style="margin-top:28px;padding-top:24px;border-top:1px solid var(--app-divider);">
                    <div style="font-weight:700;font-size:13px;color:var(--app-text);margin-bottom:12px;display:flex;align-items:center;gap:8px;">
                        <i class="fas fa-table" style="color:var(--app-primary);"></i> CSV File Format Example
                    </div>
                    
                    <div style="background:linear-gradient(135deg, #1e293b, #0f172a);color:#e2e8f0;padding:16px 20px;border-radius:var(--app-radius-card);font-family:'SFMono-Regular',Consolas,'Liberation Mono',monospace;font-size:13px;overflow-x:auto;line-height:1.8;">
                        <div>
                            <span style="color:#81ecec;font-weight:bold;">schoolID</span>,<span style="color:#81ecec;font-weight:bold;">schoolName</span>,<span style="color:#81ecec;font-weight:bold;">schoolAddress</span>,<span style="color:#81ecec;font-weight:bold;">registerDate</span>,<span style="color:#81ecec;font-weight:bold;">phoneNumber</span>,<span style="color:#81ecec;font-weight:bold;">totalTeacher</span>,<span style="color:#81ecec;font-weight:bold;">vacancy</span>
                        </div>
                        <div>
                            <span style="color:#fbbf24;">S001</span>,<span style="color:#fbbf24;">SMK Ayer Keroh</span>,<span style="color:#fbbf24;">Melaka</span>,<span style="color:#fbbf24;">2024-01-01</span>,<span style="color:#fbbf24;">0123456789</span>,<span style="color:#fbbf24;">50</span>,<span style="color:#fbbf24;">5</span>
                        </div>
                        <div>
                            <span style="color:#fbbf24;">S002</span>,<span style="color:#fbbf24;">SK Bukit Baru</span>,<span style="color:#fbbf24;">Melaka</span>,<span style="color:#fbbf24;">2024-01-02</span>,<span style="color:#fbbf24;">0199999999</span>,<span style="color:#fbbf24;">40</span>,<span style="color:#fbbf24;">3</span>
                        </div>
                    </div>

                    <div style="display:flex;align-items:center;gap:10px;padding:10px 0;margin-top:8px;font-size:12px;color:var(--app-text-subtle);">
                        <i class="fas fa-lightbulb" style="color:var(--app-warning);font-size:14px;"></i>
                        <span>Tip: Date format should be <strong>YYYY-MM-DD</strong>. All text fields should not contain commas.</span>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // File input change handler to show filename
    document.addEventListener('DOMContentLoaded', function() {
        const fileInput = document.getElementById('fileInput');
        const fileHint = document.querySelector('.file-hint');
        
        if (fileInput && fileHint) {
            fileInput.addEventListener('change', function(e) {
                const fileName = e.target.files[0]?.name;
                if (fileName) {
                    fileHint.innerHTML = `<i class="fas fa-check-circle" style="color:var(--app-success);"></i> Selected: <strong>${fileName}</strong>`;
                    fileHint.style.color = 'var(--app-success)';
                }
            });
        }

        // Auto-hide success message after 5 seconds
        setTimeout(function() {
            const alert = document.querySelector('.ui-message[style*="border-color:#a7f3d0"]');
            if (alert) {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(function() {
                    if (alert.parentNode) alert.remove();
                }, 500);
            }
        }, 5000);
    });

    // File drop zone hover effect
    document.addEventListener('DOMContentLoaded', function() {
        const dropZone = document.querySelector('.file-drop-zone');
        if (dropZone) {
            dropZone.addEventListener('dragover', function(e) {
                e.preventDefault();
                this.style.borderColor = 'var(--app-primary)';
                this.style.background = 'var(--app-primary-soft)';
            });
            
            dropZone.addEventListener('dragleave', function(e) {
                e.preventDefault();
                this.style.borderColor = 'var(--app-border)';
                this.style.background = 'var(--app-background)';
            });
            
            dropZone.addEventListener('drop', function(e) {
                e.preventDefault();
                this.style.borderColor = 'var(--app-border)';
                this.style.background = 'var(--app-background)';
                
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    const fileInput = document.getElementById('fileInput');
                    if (fileInput) {
                        fileInput.files = files;
                        // Trigger change event
                        const event = new Event('change', { bubbles: true });
                        fileInput.dispatchEvent(event);
                    }
                }
            });
        }
    });
</script>

<style>
    /* File drop zone hover effect */
    .file-drop-zone:hover {
        border-color: var(--app-primary) !important;
        background: var(--app-primary-soft) !important;
        transform: scale(1.01);
    }
    
    /* File input styling */
    #fileInput {
        padding: 8px 12px;
        border: 1px solid var(--app-border);
        border-radius: var(--app-radius-control);
        font-size: 13px;
        background: var(--app-surface);
        cursor: pointer;
        transition: border-color 0.2s ease;
    }
    
    #fileInput:hover {
        border-color: var(--app-primary);
    }
    
    #fileInput:focus {
        outline: 2px solid var(--app-focus-ring);
        outline-offset: 2px;
    }
</style>
@endsection