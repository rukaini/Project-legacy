@once
    <style data-lms-page-heading-styles>
        .lw-page-heading { margin: 0 0 16px; }
        .lw-page-heading.is-embedded { margin: 0; }
        .lw-page-heading h2 { margin: 0; color: #0f172a; font-family: inherit; font-size: 20px; font-weight: 850; line-height: 1.25; letter-spacing: -.02em; }
        .lw-page-heading p { margin: 3px 0 0; color: #64748b; font-family: inherit; font-size: 12.5px; font-weight: 600; line-height: 1.45; }
        @media (max-width: 760px) { .lw-page-heading { margin-bottom: 14px; } }
    </style>
@endonce

<header @class(['lw-page-heading', 'is-embedded' => $embedded ?? false])>
    <h2>{{ $title }}</h2>
    <p>{{ $description }}</p>
</header>
