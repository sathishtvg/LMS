<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    body { font-family: DejaVu Sans, sans-serif; }
    .frame { border: 8px solid {{ $primaryColor ?? '#2563eb' }}; padding: 34px; height: 540px; }
    .brand { font-weight: 900; font-size: 16px; color: {{ $primaryColor ?? '#2563eb' }}; letter-spacing: 1px; }
    .brandRow { display:flex; justify-content: space-between; align-items: center; }
    .logo { width: 56px; height: 56px; object-fit: contain; }
    .title { margin-top: 18px; font-weight: 900; font-size: 44px; text-align:center; color:#0f172a; }
    .subtitle { margin-top: 10px; text-align:center; color:#334155; font-size: 16px; }
    .name { margin-top: 26px; text-align:center; font-weight: 900; font-size: 34px; color:#0f172a; }
    .course { margin-top: 10px; text-align:center; font-size: 22px; color:#0f172a; }
    .meta { margin-top: 44px; display:flex; justify-content: space-between; color:#475569; font-size: 12px; }
    .small { font-size: 12px; color:#64748b; text-align:center; margin-top: 18px; }
    .verify { margin-top: 10px; text-align:center; font-size: 11px; color:#64748b; }
  </style>
</head>
<body>
  <div class="frame">
    <div class="brandRow">
      <div class="brand">{{ $companyName }}</div>
      @if(!empty($logoPath))
        @php
          $local = public_path(str_replace('/storage/','storage/',$logoPath));
        @endphp
        <img class="logo" src="{{ $local }}" alt="logo" />
      @endif
    </div>

    <div class="title">Certificate of Completion</div>
    <div class="subtitle">This certificate is proudly awarded to</div>

    <div class="name">{{ $user->name }}</div>

    <div class="subtitle">for successfully completing</div>
    <div class="course"><strong>{{ $course->title ?? $course->code }}</strong></div>

    <div class="small">
      Issued on {{ \Carbon\Carbon::parse($cert->issued_at)->format('d/m/Y') }}
    </div>

    <div class="meta">
      <div>
        Certificate No: <strong>{{ $cert->certificate_no }}</strong><br>
        Verification Token: <strong>{{ $cert->verification_token }}</strong>
      </div>
      <div style="text-align:right;">
        Status: <strong>{{ strtoupper($cert->status) }}</strong>
      </div>
    </div>

    <div class="verify">
      Verify: {{ $verifyUrl }}
    </div>
  </div>
</body>
</html>
