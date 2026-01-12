/**
 * ACF Code Generator Composable
 * 
 * Generates code snippets for ACF fields to use in templates.
 * Supports Smarty and Twig syntax.
 */

import { ref, computed } from 'vue'
import type { AcfField } from '@/types'

export type CodeLanguage = 'smarty' | 'twig' | 'shortcode'

interface CodeSnippet {
  language: CodeLanguage
  label: string
  code: string
  description?: string
}

export function useCodeGenerator() {
  const selectedLanguage = ref<CodeLanguage>('smarty')
  const copiedSlug = ref<string | null>(null)

  /**
   * Generate code snippets for a field
   */
  function generateFieldCode(field: AcfField): CodeSnippet[] {
    const slug = field.slug
    const type = field.type
    const snippets: CodeSnippet[] = []

    // Smarty snippets
    snippets.push({
      language: 'smarty',
      label: 'Smarty - Value',
      code: `{$acf->field('${slug}')}`,
      description: 'Get escaped field value'
    })

    if (needsRender(type)) {
      snippets.push({
        language: 'smarty',
        label: 'Smarty - Render',
        code: `{$acf->render('${slug}')}`,
        description: 'Render as HTML'
      })
    }

    if (type === 'repeater') {
      snippets.push({
        language: 'smarty',
        label: 'Smarty - Repeater',
        code: generateRepeaterSmarty(field),
        description: 'Loop through repeater rows'
      })
    }

    snippets.push({
      language: 'smarty',
      label: 'Smarty - Check',
      code: `{if $acf->has('${slug}')}\n  {$acf->render('${slug}')}\n{/if}`,
      description: 'Conditional display'
    })

    // Twig snippets
    snippets.push({
      language: 'twig',
      label: 'Twig - Value',
      code: `{{ acf_field('${slug}') }}`,
      description: 'Get escaped field value'
    })

    if (needsRender(type)) {
      snippets.push({
        language: 'twig',
        label: 'Twig - Render',
        code: `{{ acf_render('${slug}') }}`,
        description: 'Render as HTML'
      })
    }

    if (type === 'repeater') {
      snippets.push({
        language: 'twig',
        label: 'Twig - Repeater',
        code: generateRepeaterTwig(field),
        description: 'Loop through repeater rows'
      })
    }

    snippets.push({
      language: 'twig',
      label: 'Twig - Check',
      code: `{% if acf_has('${slug}') %}\n  {{ acf_render('${slug}') }}\n{% endif %}`,
      description: 'Conditional display'
    })

    // Shortcode snippets
    snippets.push({
      language: 'shortcode',
      label: 'Shortcode - Basic',
      code: `[acf field="${slug}"]`,
      description: 'Use in CMS/Description'
    })

    if (needsRender(type)) {
      snippets.push({
        language: 'shortcode',
        label: 'Shortcode - Render',
        code: `[acf_render field="${slug}"]`,
        description: 'Render as HTML'
      })
    }

    if (type === 'repeater') {
      snippets.push({
        language: 'shortcode',
        label: 'Shortcode - Repeater',
        code: generateRepeaterShortcode(field),
        description: 'Loop through repeater'
      })
    }

    return snippets
  }

  /**
   * Generate quick copy code for the selected language
   */
  function getQuickCode(field: AcfField): string {
    const slug = field.slug
    const type = field.type

    if (selectedLanguage.value === 'smarty') {
      if (type === 'repeater') {
        return `{foreach $acf->repeater('${slug}') as $row}...{/foreach}`
      }
      return needsRender(type) 
        ? `{$acf->render('${slug}')}` 
        : `{$acf->field('${slug}')}`
    }

    if (selectedLanguage.value === 'twig') {
      if (type === 'repeater') {
        return `{% for row in acf_repeater('${slug}') %}...{% endfor %}`
      }
      return needsRender(type) 
        ? `{{ acf_render('${slug}') }}` 
        : `{{ acf_field('${slug}') }}`
    }

    // Shortcode
    if (type === 'repeater') {
      return `[acf_repeater slug="${slug}"]...[/acf_repeater]`
    }
    return needsRender(type) 
      ? `[acf_render field="${slug}"]` 
      : `[acf field="${slug}"]`
  }

  /**
   * Copy code to clipboard
   */
  async function copyToClipboard(code: string, slug?: string): Promise<boolean> {
    try {
      await navigator.clipboard.writeText(code)
      if (slug) {
        copiedSlug.value = slug
        setTimeout(() => {
          copiedSlug.value = null
        }, 2000)
      }
      return true
    } catch (err) {
      console.error('Failed to copy:', err)
      return false
    }
  }

  /**
   * Check if field type needs render method
   */
  function needsRender(type: string): boolean {
    return [
      'richtext', 'image', 'gallery', 'video', 'file', 'files',
      'relation', 'color', 'star_rating', 'boolean', 'repeater'
    ].includes(type)
  }

  /**
   * Generate Smarty code for repeater
   */
  function generateRepeaterSmarty(field: AcfField): string {
    const slug = field.slug
    const children = field.children || []
    
    if (children.length === 0) {
      return `{foreach $acf->repeater('${slug}') as $row}\n  {* subfield values: {$row.subfield_slug} *}\n{/foreach}`
    }

    const subfields = children.slice(0, 3)
      .map(sf => `  <p>{$row.${sf.slug}}</p>`)
      .join('\n')
    
    return `{foreach $acf->repeater('${slug}') as $row}\n${subfields}\n{/foreach}`
  }

  /**
   * Generate Twig code for repeater
   */
  function generateRepeaterTwig(field: AcfField): string {
    const slug = field.slug
    const children = field.children || []
    
    if (children.length === 0) {
      return `{% for row in acf_repeater('${slug}') %}\n  {# subfield values: {{ row.subfield_slug }} #}\n{% endfor %}`
    }

    const subfields = children.slice(0, 3)
      .map(sf => `  <p>{{ row.${sf.slug} }}</p>`)
      .join('\n')
    
    return `{% for row in acf_repeater('${slug}') %}\n${subfields}\n{% endfor %}`
  }

  /**
   * Generate Shortcode for repeater
   */
  function generateRepeaterShortcode(field: AcfField): string {
    const slug = field.slug
    const children = field.children || []
    
    if (children.length === 0) {
      return `[acf_repeater slug="${slug}"]\n  {row.subfield_slug}\n[/acf_repeater]`
    }

    const subfields = children.slice(0, 3)
      .map(sf => `  {row.${sf.slug}}`)
      .join('\n')
    
    return `[acf_repeater slug="${slug}"]\n${subfields}\n[/acf_repeater]`
  }

  return {
    selectedLanguage,
    copiedSlug,
    generateFieldCode,
    getQuickCode,
    copyToClipboard,
    needsRender
  }
}
