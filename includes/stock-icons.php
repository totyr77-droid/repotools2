<?php
function stock_icon(string $name): string {
    $paths = [
        'pallet'=>'<path d="M3 21h18M4 17h16V4H4zM4 10h16M12 4v13"/>',
        'search'=>'<circle cx="10" cy="10" r="7"/><path d="m15 15 6 6"/>',
        'qr'=>'<path d="M3 3h6v6H3zM15 3h6v6h-6zM3 15h6v6H3zM15 15h3v3h3v3h-6zM21 12v3M12 3v3M12 12h3M12 18v3"/>',
        'chart'=>'<path d="M4 3v18h17M8 16v-4M13 16V8M18 16V5"/>',
        'book'=>'<path d="M3 4h7l2 2 2-2h7v15h-7l-2 2-2-2H3zM12 6v15"/>',
        'report'=>'<path d="M4 3h16v18H4zM8 7h8M8 11h8M8 15h4"/>',
        'barcode'=>'<path d="M3 4v16M6 4v16M10 4v16M14 4v16M17 4v16M21 4v16"/>',
        'keyboard'=>'<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M6 9h.01M10 9h.01M14 9h.01M18 9h.01M6 13h.01M10 13h.01M14 13h.01M18 13h.01M7 16h10"/>',
        'print'=>'<path d="M6 9V3h12v6M6 17H3V9h18v8h-3M6 14h12v7H6zM17 12h.01"/>',
        'share'=>'<path d="M12 16V3m-5 5 5-5 5 5M5 13v8h14v-8"/>',
        'settings'=>'<path d="M9.7 2h4.6l.6 2.8 2.1 1.2 2.7-.8 2.3 4-2.1 2v2.4l2.1 2-2.3 4-2.7-.8-2.1 1.2-.6 2.8H9.7l-.6-2.8L7 18.8l-2.7.8-2.3-4 2.1-2v-2.4L2 9.2l2.3-4 2.7.8 2.1-1.2z"/><circle cx="12" cy="12" r="3"/>',
        'trash'=>'<path d="M3 6h18M9 6V3h6v3M5 6l1 15h12l1-15M10 10v7M14 10v7"/>',
        'user'=>'<circle cx="12" cy="7" r="4"/><path d="M4 21v-3a8 8 0 0 1 16 0v3"/>',
        'clock'=>'<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'plus'=>'<path d="M12 4v16M4 12h16"/>',
        'check'=>'<path d="m5 12 4 4 10-10"/>',
        'close'=>'<path d="m6 6 12 12M18 6 6 18"/>',
        'chevron'=>'<path d="m6 9 6 6 6-6"/>',
        'exit'=>'<path d="M9 4H4v16h5m5-12 4 4-4 4m-6-4h13"/>',
    ];
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.($paths[$name]??$paths['qr']).'</svg>';
}
