<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body {
    font-family: Arial, Helvetica, sans-serif;
    background: #f8f9fa;
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 100vh;
    padding: 60px 40px;
  }
  .card {
    background: #ffffff;
    border-radius: 12px;
    padding: 56px 48px;
    max-width: 520px;
    width: 100%;
    text-align: center;
    border: 1px solid #e5e7eb;
  }
  .icon-wrap {
    width: 88px;
    height: 88px;
    border-radius: 16px;
    background: {{ $iconColor }}18;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 28px;
  }
  .ext-badge {
    font-size: 22px;
    font-weight: 900;
    color: {{ $iconColor }};
    letter-spacing: 1px;
  }
  .filename {
    font-size: 18px;
    font-weight: 700;
    color: #111827;
    word-break: break-all;
    margin-bottom: 8px;
    line-height: 1.4;
  }
  .type-label {
    font-size: 13px;
    color: #6b7280;
    margin-bottom: 28px;
  }
  .meta {
    background: #f3f4f6;
    border-radius: 8px;
    padding: 16px 20px;
    text-align: left;
    margin-bottom: 32px;
  }
  .meta-row {
    font-size: 12px;
    color: #374151;
    margin-bottom: 6px;
    line-height: 1.5;
  }
  .meta-row:last-child { margin-bottom: 0; }
  .meta-label { color: #9ca3af; font-weight: 600; text-transform: uppercase; font-size: 10px; letter-spacing: 0.5px; }
  .divider {
    border: none;
    border-top: 1px solid #e5e7eb;
    margin: 0 0 28px;
  }
  .download-note {
    font-size: 13px;
    color: #6b7280;
    line-height: 1.6;
  }
  .download-note strong {
    color: #374151;
  }
  .pill {
    display: inline-block;
    background: {{ $iconColor }}18;
    color: {{ $iconColor }};
    font-size: 11px;
    font-weight: 700;
    padding: 3px 10px;
    border-radius: 20px;
    margin-bottom: 20px;
    letter-spacing: 0.5px;
  }
</style>
</head>
<body>
<div class="card">
  <div class="icon-wrap">
    <span class="ext-badge">{{ strtoupper($ext) }}</span>
  </div>

  <div class="pill">{{ $typeLabel }}</div>

  <div class="filename">{{ $fileName }}</div>
  <div class="type-label">This file requires Microsoft Office or a compatible app to open.</div>

  @if($courseName || $trainerName)
  <div class="meta">
    @if($courseName)
    <div class="meta-row">
      <div class="meta-label">Course</div>
      {{ $courseName }}
    </div>
    @endif
    @if($trainerName)
    <div class="meta-row">
      <div class="meta-label">Trainer</div>
      {{ $trainerName }}
    </div>
    @endif
  </div>
  @endif

  <hr class="divider">

  <div class="download-note">
    Click <strong>Download</strong> in the toolbar above to save this file<br>
    and open it in <strong>Microsoft {{ $typeLabel }}</strong>.
  </div>
</div>
</body>
</html>
