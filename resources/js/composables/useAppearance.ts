import {
    applyResolvedTheme,
    DEFAULT_APPEARANCE,
    getStoredAppearance,
    migrateAppearance,
    resolveTheme,
    type Appearance,
} from '@/lib/theme';
import { onMounted, ref } from 'vue';

export type { Appearance };

export function updateTheme(value: Appearance) {
    applyResolvedTheme(resolveTheme(value));
}

const setCookie = (name: string, value: string, days = 365) => {
    if (typeof document === 'undefined') {
        return;
    }

    const maxAge = days * 24 * 60 * 60;

    document.cookie = `${name}=${value};path=/;max-age=${maxAge};SameSite=Lax`;
};

const mediaQuery = () => {
    if (typeof window === 'undefined') {
        return null;
    }

    return window.matchMedia('(prefers-color-scheme: dark)');
};

const handleSystemThemeChange = () => {
    const currentAppearance = getStoredAppearance();

    updateTheme(currentAppearance || DEFAULT_APPEARANCE);
};

export function initializeTheme() {
    if (typeof window === 'undefined') {
        return;
    }

    const raw = localStorage.getItem('appearance');
    const savedAppearance = raw ? migrateAppearance(raw) : null;

    if (raw && raw !== savedAppearance) {
        localStorage.setItem('appearance', savedAppearance!);
        setCookie('appearance', savedAppearance!);
    }

    updateTheme(savedAppearance || DEFAULT_APPEARANCE);

    mediaQuery()?.addEventListener('change', handleSystemThemeChange);
}

export function useAppearance() {
    const appearance = ref<Appearance>(DEFAULT_APPEARANCE);

    onMounted(() => {
        const savedAppearance = getStoredAppearance();

        if (savedAppearance) {
            appearance.value = savedAppearance;
        }
    });

    function updateAppearance(value: Appearance) {
        appearance.value = value;

        localStorage.setItem('appearance', value);
        setCookie('appearance', value);
        updateTheme(value);
    }

    return {
        appearance,
        updateAppearance,
    };
}
