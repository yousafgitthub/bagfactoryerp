<script setup>
defineProps({
    variant: {
        type: String,
        default: 'hero',
    },
});
</script>

<template>
    <svg v-if="variant === 'hero'" viewBox="0 0 640 460" class="h-auto w-full" role="img" aria-label="Illustrated shopping bags and film rolls">
        <defs>
            <linearGradient id="hero-bg" x1="0" y1="0" x2="1" y2="1">
                <stop offset="0" stop-color="#e7f0ff" />
                <stop offset="1" stop-color="#f8fbff" />
            </linearGradient>
            <linearGradient id="bag-white" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0" stop-color="#ffffff" />
                <stop offset="1" stop-color="#e8eef8" />
            </linearGradient>
            <linearGradient id="roll" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0" stop-color="#ffffff" />
                <stop offset="1" stop-color="#dbe4f0" />
            </linearGradient>
        </defs>
        <rect x="12" y="12" width="616" height="436" rx="28" fill="url(#hero-bg)" />
        <g opacity="0.35" stroke="#93c5fd" fill="none">
            <path d="M40 300h560M40 250h560M80 40v280M160 40v280M240 40v280M520 40v280" />
        </g>
        <path d="M430 78h78l-8 168h-62z" fill="#ef4444" />
        <path d="M448 78c8 18 22 28 36 28" fill="none" stroke="#b91c1c" stroke-width="6" stroke-linecap="round" />
        <path d="M508 118h70l-8 150h-54z" fill="#22c55e" />
        <path d="M528 150c10 8 18 8 28 0" fill="none" stroke="#166534" stroke-width="4" />
        <circle cx="548" cy="186" r="16" fill="none" stroke="#14532d" stroke-width="3" />
        <path d="M548 174v8l6 4" fill="none" stroke="#14532d" stroke-width="3" stroke-linecap="round" />
        <path d="M250 70h150l-14 230H264z" fill="url(#bag-white)" stroke="#cbd5e1" />
        <path d="M292 70c0-28 18-40 33-40s33 12 33 40" fill="none" stroke="#64748b" stroke-width="8" stroke-linecap="round" />
        <path d="M118 150h92l-8 150h-76z" fill="#2563eb" />
        <path d="M142 150c6 16 16 24 28 24s20-8 26-24" fill="none" stroke="#1e3a8a" stroke-width="6" stroke-linecap="round" />
        <g transform="translate(70 300)">
            <ellipse cx="70" cy="28" rx="70" ry="22" fill="#2563eb" />
            <rect x="0" y="28" width="140" height="46" fill="#1d4ed8" />
            <ellipse cx="70" cy="74" rx="70" ry="22" fill="#1e40af" />
            <ellipse cx="70" cy="28" rx="28" ry="10" fill="#93c5fd" />
        </g>
        <g transform="translate(210 318)">
            <ellipse cx="58" cy="24" rx="58" ry="18" fill="#e2e8f0" />
            <rect x="0" y="24" width="116" height="40" fill="url(#roll)" />
            <ellipse cx="58" cy="64" rx="58" ry="18" fill="#cbd5e1" />
            <ellipse cx="58" cy="24" rx="22" ry="8" fill="#94a3b8" />
        </g>
        <g transform="translate(360 312)">
            <ellipse cx="62" cy="24" rx="62" ry="18" fill="#facc15" />
            <rect x="0" y="24" width="124" height="42" fill="#eab308" />
            <ellipse cx="62" cy="66" rx="62" ry="18" fill="#ca8a04" />
            <ellipse cx="62" cy="24" rx="24" ry="8" fill="#fef9c3" />
        </g>
        <g transform="translate(500 324)">
            <ellipse cx="52" cy="20" rx="52" ry="16" fill="#22c55e" />
            <rect x="0" y="20" width="104" height="36" fill="#16a34a" />
            <ellipse cx="52" cy="56" rx="52" ry="16" fill="#15803d" />
            <ellipse cx="52" cy="20" rx="18" ry="7" fill="#bbf7d0" />
        </g>
    </svg>

    <svg v-else-if="variant === 'shopping'" viewBox="0 0 320 180" class="h-full w-full" aria-hidden="true">
        <rect width="320" height="180" rx="16" fill="#f8fbff" />
        <path d="M118 28h84l-8 112h-68z" fill="#ffffff" stroke="#cbd5e1" />
        <path d="M142 28c0-16 10-24 18-24s18 8 18 24" fill="none" stroke="#64748b" stroke-width="5" />
        <path d="M70 70h46l-4 70h-38z" fill="#93c5fd" />
    </svg>

    <svg v-else-if="variant === 'printed'" viewBox="0 0 320 180" class="h-full w-full" aria-hidden="true">
        <rect width="320" height="180" rx="16" fill="#f8fbff" />
        <path d="M70 36h52l-5 100h-42z" fill="#22c55e" />
        <path d="M132 28h58l-6 112h-46z" fill="#facc15" />
        <circle cx="155" cy="78" r="12" fill="#0f2744" />
        <path d="M200 40h50l-5 96h-40z" fill="#ef4444" />
    </svg>

    <svg v-else-if="variant === 'custom'" viewBox="0 0 320 180" class="h-full w-full" aria-hidden="true">
        <rect width="320" height="180" rx="16" fill="#f8fbff" />
        <ellipse cx="92" cy="78" rx="48" ry="16" fill="#2563eb" />
        <rect x="44" y="78" width="96" height="36" fill="#1d4ed8" />
        <ellipse cx="92" cy="114" rx="48" ry="16" fill="#1e40af" />
        <ellipse cx="200" cy="86" rx="44" ry="15" fill="#eab308" />
        <rect x="156" y="86" width="88" height="32" fill="#ca8a04" />
        <ellipse cx="200" cy="118" rx="44" ry="15" fill="#a16207" />
    </svg>

    <svg v-else-if="variant === 'dana'" viewBox="0 0 320 180" class="h-full w-full" aria-hidden="true">
        <rect width="320" height="180" rx="16" fill="#f8fbff" />
        <g fill="#e2e8f0" stroke="#94a3b8">
            <circle cx="120" cy="78" r="16" />
            <circle cx="148" cy="70" r="18" />
            <circle cx="176" cy="82" r="15" />
            <circle cx="138" cy="100" r="17" />
            <circle cx="166" cy="104" r="14" />
            <circle cx="196" cy="96" r="16" />
            <circle cx="154" cy="90" r="12" fill="#f8fafc" />
        </g>
    </svg>

    <svg v-else viewBox="0 0 640 360" class="h-full w-full" role="img" aria-label="Illustrated bag factory">
        <rect width="640" height="360" rx="24" fill="#e8f1ff" />
        <rect x="40" y="150" width="220" height="160" fill="#1e3a8a" />
        <rect x="70" y="180" width="40" height="28" fill="#bfdbfe" />
        <rect x="130" y="180" width="40" height="28" fill="#bfdbfe" />
        <rect x="190" y="180" width="40" height="28" fill="#bfdbfe" />
        <rect x="70" y="230" width="40" height="28" fill="#93c5fd" />
        <rect x="130" y="230" width="40" height="28" fill="#93c5fd" />
        <path d="M40 150 150 80l110 70" fill="#0f2744" />
        <rect x="250" y="190" width="150" height="120" fill="#1d4ed8" />
        <rect x="300" y="80" width="28" height="110" fill="#334155" />
        <ellipse cx="470" cy="200" rx="90" ry="28" fill="#ffffff" />
        <rect x="380" y="200" width="180" height="50" fill="#e2e8f0" />
        <ellipse cx="470" cy="250" rx="90" ry="28" fill="#cbd5e1" />
        <ellipse cx="470" cy="200" rx="34" ry="12" fill="#94a3b8" />
        <rect x="40" y="310" width="560" height="12" fill="#0f2744" opacity="0.15" />
    </svg>
</template>
