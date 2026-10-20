@props(['name' => 'pin', 'size' => 18])
<svg viewBox="0 0 24 24" width="{{ $size }}" height="{{ $size }}" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" {{ $attributes }}>
    @switch($name)
        @case('bed')
            <path d="M3 18v-6.5A1.5 1.5 0 0 1 4.5 10h15a1.5 1.5 0 0 1 1.5 1.5V18M3 15h18M3 18v2M21 18v2M6 10V7.5A1.5 1.5 0 0 1 7.5 6h3A1.5 1.5 0 0 1 12 7.5V10"/>
            @break
        @case('bath')
            <path d="M4 12h16v2.5a4.5 4.5 0 0 1-4.5 4.5h-7A4.5 4.5 0 0 1 4 14.5V12ZM6 12V6a2 2 0 0 1 3.7-1M7 19l-1 2M17 19l1 2"/>
            @break
        @case('building')
            <path d="M4 20V5.5A1.5 1.5 0 0 1 5.5 4h7A1.5 1.5 0 0 1 14 5.5V20M14 10h4.5a1.5 1.5 0 0 1 1.5 1.5V20M3 20h18M8 8h2M8 12h2M8 16h2M17 14h.01M17 17h.01"/>
            @break
        @case('land')
            <path d="M3 7.5 9 4l6 3.5L21 4v12.5L15 20l-6-3.5L3 20V7.5ZM9 4v12.5M15 7.5V20"/>
            @break
        @case('car')
            <path d="M5 16v2.5M19 16v2.5M3.5 16h17v-3.5l-1.8-4.6A1.5 1.5 0 0 0 17.3 7H6.7a1.5 1.5 0 0 0-1.4.9L3.5 12.5V16ZM3.5 12.5h17"/><circle cx="7.5" cy="14.2" r=".6" fill="currentColor"/><circle cx="16.5" cy="14.2" r=".6" fill="currentColor"/>
            @break
        @case('floors')
            <path d="m12 3 9 4.5-9 4.5-9-4.5L12 3ZM3 12l9 4.5 9-4.5M3 16.5 12 21l9-4.5"/>
            @break
        @case('certificate')
            <path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8l-5-5ZM14 3v5h5M9 13h6M9 17h4"/>
            @break
        @case('sofa')
            <path d="M5 11V8a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v3M3 13a2 2 0 0 1 4 0v2h10v-2a2 2 0 0 1 4 0v5H3v-5ZM5 18v2M19 18v2"/>
            @break
        @case('calendar')
            <rect x="3.5" y="5" width="17" height="15.5" rx="2"/><path d="M3.5 10h17M8 3v4M16 3v4"/>
            @break
        @case('school')
            <path d="m2.5 9 9.5-5 9.5 5-9.5 5-9.5-5ZM6 11v5c0 1.5 2.7 3 6 3s6-1.5 6-3v-5M21.5 9v6"/>
            @break
        @case('health')
            <rect x="3.5" y="3.5" width="17" height="17" rx="4"/><path d="M12 8v8M8 12h8"/>
            @break
        @case('mall')
            <path d="M5 8h14l-1 12H6L5 8ZM9 8V6.5a3 3 0 0 1 6 0V8"/>
            @break
        @case('road')
            <path d="M8 3 4 21M16 3l4 18M12 4v3M12 11v3M12 18v2"/>
            @break
        @case('train')
            <rect x="5" y="3" width="14" height="14" rx="3"/><path d="M5 11h14M9 17l-2 4M15 17l2 4"/><circle cx="9" cy="14" r=".7" fill="currentColor"/><circle cx="15" cy="14" r=".7" fill="currentColor"/>
            @break
        @case('park')
            <path d="M12 21v-6M7 15h10l-5-11-5 11ZM9 10.5h6"/>
            @break
        @case('airport')
            <path d="M10.5 13.5 3 11l1-2 8 1 4-5a1.5 1.5 0 0 1 2 2l-5 4 1 8-2 1-2.5-7.5L6 15.5V18l-1.5 1-1-3-3-1 1-1.5h2.5Z"/>
            @break
        @case('phone')
            <path d="M4.5 5.5c0-1 .8-1.5 1.7-1.5h1.6c.6 0 1.1.4 1.3 1l1 2.6c.2.5 0 1.1-.4 1.5l-1.3 1.2a13 13 0 0 0 5.8 5.8l1.2-1.3c.4-.4 1-.5 1.5-.4l2.6 1c.6.2 1 .7 1 1.3v1.6c0 .9-.5 1.7-1.5 1.7C11.5 20 4 12.5 4.5 5.5Z"/>
            @break
        @case('whatsapp')
            <path d="M4 20l1.2-3.9A8 8 0 1 1 8 19L4 20Z"/><path d="M9 9.5c.2 2.4 2.6 4.8 5 5l1-1.3-1.8-1-1 .8c-.8-.3-1.6-1.1-1.9-1.9l.8-1-1-1.8L9 9.5Z"/>
            @break
        @case('mail')
            <rect x="3" y="5" width="18" height="14" rx="2"/><path d="m4 7 8 6 8-6"/>
            @break
        @case('clock')
            <circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/>
            @break
        @case('check')
            <path d="m5 12.5 4.5 4.5L19 7.5"/>
            @break
        @case('arrow')
            <path d="M5 12h14M13 6l6 6-6 6"/>
            @break
        @case('arrow-left')
            <path d="M19 12H5M11 6l-6 6 6 6"/>
            @break
        @case('spark')
            <path d="M12 3c.6 4.2 2.8 6.4 7 7-4.2.6-6.4 2.8-7 7-.6-4.2-2.8-6.4-7-7 4.2-.6 6.4-2.8 7-7ZM19 16c.3 1.6 1 2.3 2.5 2.5-1.5.3-2.2 1-2.5 2.5-.3-1.5-1-2.2-2.5-2.5 1.5-.2 2.2-.9 2.5-2.5Z"/>
            @break
        @case('ruler')
            <path d="M3 16.5 16.5 3 21 7.5 7.5 21 3 16.5ZM7 12.5l2 2M10 9.5l2 2M13 6.5l2 2"/>
            @break
        @default
            <path d="M12 21s-6.5-5.4-6.5-11a6.5 6.5 0 0 1 13 0c0 5.6-6.5 11-6.5 11Z"/><circle cx="12" cy="10" r="2.3"/>
    @endswitch
</svg>
