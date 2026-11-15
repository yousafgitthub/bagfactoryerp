<script setup>
defineProps({
    name: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <template v-if="name === 'home'">
            <path d="M4 10.5 12 4l8 6.5V20a1 1 0 0 1-1 1h-5v-6H10v6H5a1 1 0 0 1-1-1z" />
        </template>
        <template v-else-if="name === 'box'">
            <path d="M3 8 12 4l9 4-9 4z" />
            <path d="M3 8v8l9 4 9-4V8" />
            <path d="M12 12v8" />
        </template>
        <template v-else-if="name === 'gear'">
            <circle cx="12" cy="12" r="3" />
            <path d="M12 3v2.2M12 18.8V21M3 12h2.2M18.8 12H21M5.6 5.6l1.6 1.6M16.8 16.8l1.6 1.6M18.4 5.6l-1.6 1.6M7.2 16.8l-1.6 1.6" />
        </template>
        <template v-else-if="name === 'bag'">
            <path d="M6 8h12l-1.2 12H7.2z" />
            <path d="M9 8V7a3 3 0 0 1 6 0v1" />
        </template>
        <template v-else-if="name === 'factory'">
            <path d="M3 21V11l5 3V10l5 3V8l8 4v9z" />
            <path d="M7 21v-3h2v3M14 21v-3h2v3" />
        </template>
        <template v-else-if="name === 'users'">
            <circle cx="9" cy="8" r="3" />
            <path d="M3.5 19c.4-2.8 2.8-4.5 5.5-4.5s5.1 1.7 5.5 4.5" />
            <circle cx="17" cy="9" r="2" />
            <path d="M16 14.6c2 .4 3.5 1.8 4 4.4" />
        </template>
        <template v-else-if="name === 'clock'">
            <circle cx="12" cy="12" r="8" />
            <path d="M12 8v5l3 2" />
        </template>
        <template v-else-if="name === 'wallet'">
            <rect x="3" y="6" width="18" height="13" rx="2" />
            <path d="M3 10h18" />
            <path d="M16 15h2" />
        </template>
        <template v-else-if="name === 'cart'">
            <circle cx="9" cy="20" r="1" />
            <circle cx="17" cy="20" r="1" />
            <path d="M3 4h2l2.2 11h11.3l2-7H7" />
        </template>
        <template v-else-if="name === 'truck'">
            <path d="M3 7h11v10H3z" />
            <path d="M14 11h4l3 3v3h-7" />
            <circle cx="7" cy="18.5" r="1.5" />
            <circle cx="18" cy="18.5" r="1.5" />
        </template>
        <template v-else-if="name === 'trend'">
            <path d="M4 16l5-5 4 3 7-8" />
            <path d="M14 6h6v6" />
        </template>
        <template v-else-if="name === 'receipt'">
            <path d="M6 3h12v18l-2-1.2L14 21l-2-1.2L10 21l-2-1.2L6 21z" />
            <path d="M9 8h6M9 12h6" />
        </template>
        <template v-else-if="name === 'building'">
            <path d="M4 21V5h10v16" />
            <path d="M14 21v-8h6v8" />
            <path d="M7 8h2M7 12h2M7 16h2M17 16h2" />
        </template>
        <template v-else-if="name === 'chart'">
            <path d="M4 19V5" />
            <path d="M4 19h16" />
            <path d="M8 16v-4M12 16V8M16 16v-6" />
        </template>
        <template v-else-if="name === 'settings'">
            <circle cx="12" cy="12" r="3" />
            <path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9c.3.7.9 1.2 1.6 1.3H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z" />
        </template>
        <template v-else-if="name === 'search'">
            <circle cx="11" cy="11" r="6" />
            <path d="m20 20-3.5-3.5" />
        </template>
        <template v-else-if="name === 'sun'">
            <circle cx="12" cy="12" r="4" />
            <path d="M12 3v2M12 19v2M3 12h2M19 12h2M5.6 5.6l1.4 1.4M17 17l1.4 1.4M18.4 5.6 17 7M7 17l-1.4 1.4" />
        </template>
        <template v-else-if="name === 'moon'">
            <path d="M21 14.5A8.5 8.5 0 0 1 9.5 3 7 7 0 1 0 21 14.5z" />
        </template>
        <template v-else-if="name === 'bell'">
            <path d="M6 16V11a6 6 0 1 1 12 0v5l1.5 2h-15z" />
            <path d="M10 19a2 2 0 0 0 4 0" />
        </template>
        <template v-else-if="name === 'bolt'">
            <path d="M13 2 4 14h7l-1 8 9-12h-7z" />
        </template>
        <template v-else-if="name === 'cube'">
            <path d="M12 3 20 7.5v9L12 21 4 16.5v-9z" />
            <path d="M12 12 20 7.5M12 12v9M12 12 4 7.5" />
        </template>
        <template v-else-if="name === 'alert'">
            <path d="M12 3 2 20h20z" />
            <path d="M12 9v5" />
            <path d="M12 17h.01" />
        </template>
        <template v-else-if="name === 'globe'">
            <circle cx="12" cy="12" r="9" />
            <path d="M3 12h18M12 3c2.5 2.8 3.8 5.8 3.8 9s-1.3 6.2-3.8 9c-2.5-2.8-3.8-5.8-3.8-9S9.5 5.8 12 3z" />
        </template>
        <template v-else-if="name === 'chevron'">
            <path d="m9 6 6 6-6 6" />
        </template>
        <template v-else>
            <circle cx="12" cy="12" r="4" />
        </template>
    </svg>
</template>
