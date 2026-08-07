@php
    $primaryColor = $hotelBranding->color('primary_color', '#2563EB');
    $secondaryColor = $hotelBranding->color('secondary_color', '#0EA5E9');
    $footerColor = $hotelBranding->color('footer_color', '#161B48');
@endphp
<style>
    :root {
        --hotel-primary: {{ $primaryColor }};
        --hotel-secondary: {{ $secondaryColor }};
        --hotel-footer: {{ $footerColor }};
    }

    html.dark .hotel-themed {
        background-color: var(--hotel-footer);
    }

    .hotel-themed .bg-blue-600,
    .hotel-themed .bg-blue-700 {
        background-color: var(--hotel-primary);
    }

    .hotel-themed .hover\:bg-blue-700:hover,
    .hotel-themed .hover\:bg-blue-600:hover {
        background-color: var(--hotel-secondary);
    }

    .hotel-themed .text-blue-600,
    .hotel-themed .text-blue-700,
    .hotel-themed .text-blue-800,
    .hotel-themed .hover\:text-blue-600:hover,
    .hotel-themed .hover\:text-blue-700:hover {
        color: var(--hotel-primary);
    }

    .hotel-themed .border-blue-600 {
        border-color: var(--hotel-primary);
    }

    .hotel-themed .focus\:ring-blue-500:focus {
        --tw-ring-color: var(--hotel-primary);
    }

    .hotel-brand-footer {
        background-color: var(--hotel-footer);
    }

    .hotel-brand-footer .hotel-brand-accent {
        color: var(--hotel-secondary);
    }

    .hotel-brand-footer .hotel-brand-border {
        border-color: color-mix(in srgb, var(--hotel-secondary) 35%, transparent);
    }
</style>
