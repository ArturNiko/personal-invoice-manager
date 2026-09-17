import { ref } from 'vue';

export type Theme = 'light' | 'dark';

const STORAGE_KEY = 'pim-theme';

const theme = ref<Theme>('dark');

const readStoredTheme = (): Theme | null => {
    try {
        const stored = window.localStorage.getItem(STORAGE_KEY);

        return stored === 'light' || stored === 'dark' ? stored : null;
    } catch {
        return null;
    }
};

export const applyTheme = (value: Theme) => {
    const root = document.documentElement;

    root.classList.toggle('light', value === 'light');
    root.classList.toggle('dark', value === 'dark');
    root.style.colorScheme = value;
};

export const setTheme = (value: Theme) => {
    theme.value = value;

    try {
        window.localStorage.setItem(STORAGE_KEY, value);
    } catch {
        // Storage can be unavailable (private mode); keep the in-memory choice.
    }

    applyTheme(value);
};

export const toggleTheme = () => {
    setTheme(theme.value === 'dark' ? 'light' : 'dark');
};

export const initTheme = () => {
    theme.value = readStoredTheme() ?? 'dark';
    applyTheme(theme.value);
};

export const useTheme = () => ({ theme, setTheme, toggleTheme });
