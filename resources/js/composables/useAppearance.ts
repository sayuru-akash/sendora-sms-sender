import { ref, watchEffect } from 'vue';

type Appearance = 'light' | 'dark' | 'system';

const appearance = ref<Appearance>(
  (localStorage.getItem('appearance') as Appearance) || 'light'
);

export function useAppearance() {
  function updateAppearance(value: Appearance) {
    appearance.value = value;
    localStorage.setItem('appearance', value);
  }

  watchEffect(() => {
    const isDark =
      appearance.value === 'dark' ||
      (appearance.value === 'system' &&
        window.matchMedia('(prefers-color-scheme: dark)').matches);

    document.documentElement.classList.toggle('dark', isDark);
  });

  return {
    appearance,
    updateAppearance,
  };
}
