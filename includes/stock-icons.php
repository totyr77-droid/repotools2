<?php
function stock_icon(string $name): string {
    $paths = [
        'qr'=>'<path d="M3 3h6v6H3zM15 3h6v6h-6zM3 15h6v6H3zM15 15h3v3h3v3h-6zM21 12v3M12 3v3M12 12h3M12 18v3"/>',
        'barcode'=>'<path d="M3 4v16M6 4v16M10 4v16M14 4v16M17 4v16M21 4v16"/>',
        'keyboard'=>'<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M6 9h.01M10 9h.01M14 9h.01M18 9h.01M6 13h.01M10 13h.01M14 13h.01M18 13h.01M7 16h10"/>',
        'print'=>'<path d="M6 9V3h12v6M6 17H3V9h18v8h-3M6 14h12v7H6zM17 12h.01"/>',
        'share'=>'<path d="M12 16V3m-5 5 5-5 5 5M5 13v8h14v-8"/>',
        'settings'=>'<path d="m9 3-1 3-3 1-2 3 2 2-1 3 3 2 3-1 2 2 3-1 1-3 3-1 1-3-2-2 1-3-3-2-3 1-2-2z"/><circle cx="12" cy="12" r="3"/>',
        'trash'=>'<path d="M3 6h18M9 6V3h6v3M5 6l1 15h12l1-15M10 10v7M14 10v7"/>',
        'user'=>'<circle cx="12" cy="7" r="4"/><path d="M4 21v-3a8 8 0 0 1 16 0v3"/>',
        'clock'=>'<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'plus'=>'<path d="M12 4v16M4 12h16"/>',
        'close'=>'<path d="m6 6 12 12M18 6 6 18"/>',
        'chevron'=>'<path d="m6 9 6 6 6-6"/>',
        'exit'=>'<path d="M9 4H4v16h5m5-12 4 4-4 4m-6-4h13"/>',
    ];
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.($paths[$name]??$paths['qr']).'</svg>';
}
