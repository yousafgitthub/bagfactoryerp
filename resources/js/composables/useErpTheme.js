import { onMounted, ref, watch } from 'vue';

const STORAGE_KEY = 'bag-factory-erp-theme';

export function useErpTheme() {
    const isDark = ref(false);

    const apply = (dark) => {
        document.documentElement.classList.toggle('dark', dark);
        localStorage.setItem(STORAGE_KEY, dark ? 'dark' : 'light');
    };

    onMounted(() => {
        const stored = localStorage.getItem(STORAGE_KEY);
        if (stored === 'dark' || stored === 'light') {
            isDark.value = stored === 'dark';
        } else {
            isDark.value = window.matchMedia('(prefers-color-scheme: dark)').matches;
        }
        apply(isDark.value);
    });

    watch(isDark, (value) => apply(value));

    const toggle = () => {
        isDark.value = !isDark.value;
    };

    return { isDark, toggle };
}