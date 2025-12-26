/**
 * Translation composable for WePresta ACF
 */
export function useTranslations() {
  const translations = window.acfConfig?.translations || {}

  /**
   * Get a translated string
   */
  function t(key: string, fallback?: string): string {
    return translations[key] || fallback || key
  }

  return { t }
}

