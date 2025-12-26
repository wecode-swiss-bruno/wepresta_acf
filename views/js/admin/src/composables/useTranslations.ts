/**
 * Translation composable for ACF-PS
 */
export function useTranslations() {
  const translations = window.acfpsConfig?.translations || {}

  /**
   * Get a translated string
   */
  function t(key: string, fallback?: string): string {
    return translations[key] || fallback || key
  }

  return { t }
}

