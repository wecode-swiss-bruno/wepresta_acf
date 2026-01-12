/**
 * Translation composable for WePresta ACF
 */
export function useTranslations() {
  const translations = window.acfConfig?.translations || {}

  /**
   * Decode HTML entities in a string
   */
  function decodeHtmlEntities(text: string): string {
    const textarea = document.createElement('textarea')
    textarea.innerHTML = text
    return textarea.value
  }

  /**
   * Get a translated string with HTML entities decoded
   */
  function t(key: string, fallback?: string): string {
    const rawTranslation = translations[key] || fallback || key
    return decodeHtmlEntities(rawTranslation)
  }

  return { t }
}

