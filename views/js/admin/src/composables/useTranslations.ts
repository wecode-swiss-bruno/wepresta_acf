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
   * Get a translated string with HTML entities decoded and placeholders replaced
   */
  function t(key: string, fallback?: string, params?: Record<string, string | number>): string {
    let rawTranslation = translations[key] || fallback || key

    // Replace placeholders if params provided
    if (params) {
      Object.entries(params).forEach(([name, value]) => {
        rawTranslation = rawTranslation.replace(`%${name}%`, String(value))
      })
    }

    return decodeHtmlEntities(rawTranslation)
  }

  return { t }
}

