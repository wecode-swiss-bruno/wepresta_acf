<?php

declare(strict_types=1);

namespace WeprestaAcf\Application\FieldType;

use Symfony\Component\Form\Extension\Core\Type\HiddenType;

/**
 * Star Rating Field - Interactive star picker for product ratings.
 *
 * Admin: Clickable stars to select rating
 * Frontend: Visual star display
 */
class StarRatingField extends AbstractFieldType
{
    public function getType(): string
    {
        return 'star_rating';
    }

    public function getLabel(): string
    {
        return 'Star Rating';
    }

    public function getCategory(): string
    {
        return 'choice';
    }

    public function getIcon(): string
    {
        return 'star';
    }

    public function getFormType(): string
    {
        return HiddenType::class;
    }

    public function getDefaultConfig(): array
    {
        return [
            'max_stars' => 5,
            'allow_half' => false,
            'default_value' => 0,
            'star_size' => 24,
            'color_filled' => '#ffc107',
            'color_empty' => '#e0e0e0',
        ];
    }

    public function getConfigSchema(): array
    {
        return [
            'max_stars' => [
                'type' => 'number',
                'label' => 'Maximum Stars',
                'default' => 5,
                'min' => 1,
                'max' => 10,
            ],
            'allow_half' => [
                'type' => 'boolean',
                'label' => 'Allow Half Stars',
                'default' => false,
            ],
            'default_value' => [
                'type' => 'number',
                'label' => 'Default Value',
                'default' => 0,
            ],
            'star_size' => [
                'type' => 'number',
                'label' => 'Star Size (px)',
                'default' => 24,
                'min' => 16,
                'max' => 48,
            ],
            'color_filled' => [
                'type' => 'color',
                'label' => 'Filled Star Color',
                'default' => '#ffc107',
            ],
            'color_empty' => [
                'type' => 'color',
                'label' => 'Empty Star Color',
                'default' => '#e0e0e0',
            ],
        ];
    }



    /**
     * Render stars for frontend display.
     */
    public function renderValue(mixed $value, array $fieldConfig = [], array $renderOptions = []): string
    {
        // Extract value for current language if translatable
        $actualValue = $this->extractTranslatableValue($value);

        $maxStars = (int) ($fieldConfig['max_stars'] ?? 5);
        $rating = (float) $actualValue;
        $allowHalf = !empty($fieldConfig['allow_half']);
        $starSize = (int) ($fieldConfig['star_size'] ?? 24);
        $colorFilled = htmlspecialchars($fieldConfig['color_filled'] ?? '#ffc107', ENT_QUOTES, 'UTF-8');
        $colorEmpty = htmlspecialchars($fieldConfig['color_empty'] ?? '#e0e0e0', ENT_QUOTES, 'UTF-8');

        $html = \sprintf(
            '<span class="acf-star-rating-display" style="--star-size: %dpx; --color-filled: %s; --color-empty: %s;" aria-label="Rating: %s out of %d stars">',
            $starSize,
            $colorFilled,
            $colorEmpty,
            $rating,
            $maxStars
        );

        for ($i = 1; $i <= $maxStars; ++$i) {
            if ($i <= $rating) {
                $html .= '<span class="acf-star acf-star--filled">★</span>';
            } elseif ($allowHalf && ($i - 0.5) <= $rating) {
                $html .= '<span class="acf-star acf-star--half">★</span>';
            } else {
                $html .= '<span class="acf-star">★</span>';
            }
        }

        // Optional: show numeric value
        if (!empty($renderOptions['show_value'])) {
            $html .= \sprintf(' <span class="acf-star-rating-value">(%s/%d)</span>', $rating, $maxStars);
        }

        $html .= '</span>';

        // Add CSS for frontend
        $html .= $this->getInlineStyles();

        return $html;
    }

    public function normalizeValue(mixed $value, array $fieldConfig = []): mixed
    {
        $allowHalf = !empty($fieldConfig['allow_half']);
        $val = (float) $value;

        if (!$allowHalf) {
            $val = round($val);
        }

        return max(0, min($val, $fieldConfig['max_stars'] ?? 5));
    }

    public function validate(mixed $value, array $fieldConfig = [], array $validation = []): array
    {
        $errors = [];
        $maxStars = (int) ($fieldConfig['max_stars'] ?? 5);

        if (!empty($validation['required']) && empty($value)) {
            $errors[] = 'Rating is required';
        }

        if ($value !== null && $value !== '' && ((float) $value < 0 || (float) $value > $maxStars)) {
            $errors[] = 'Rating must be between 0 and ' . $maxStars;
        }

        return $errors;
    }

    public function supportsTranslation(): bool
    {
        return false;
    }

    /**
     * JS template for repeater/flexible content.
     */
    public function getJsTemplate(array $field): string
    {
        $config = $this->getFieldConfig($field);
        $maxStars = (int) ($config['max_stars'] ?? 5);
        $slug = $this->escapeAttr($field['slug'] ?? '');

        $stars = '';

        for ($i = 1; $i <= $maxStars; ++$i) {
            $stars .= \sprintf('<span class="acf-star" data-value="%d">★</span>', $i);
        }

        return \sprintf(
            '<input type="hidden" class="acf-subfield-input acf-star-rating-value" data-subfield="%s" value="{value}">' .
            '<div class="acf-star-rating-picker" data-max="%d">%s</div>',
            $slug,
            $maxStars,
            $stars
        );
    }

    /**
     * Get scoped CSS styles.
     */
    private function getInlineStyles(): string
    {
        return <<<'CSS'
            <style>
            .acf-star-rating-picker {
                display: inline-flex;
                align-items: center;
                gap: 2px;
                user-select: none;
            }
            .acf-star-rating-picker .acf-star {
                font-size: var(--star-size, 24px);
                color: var(--color-empty, #e0e0e0);
                cursor: pointer;
                transition: color 0.15s ease, transform 0.1s ease;
                line-height: 1;
            }
            .acf-star-rating-picker .acf-star:hover {
                transform: scale(1.15);
            }
            .acf-star-rating-picker .acf-star--filled,
            .acf-star-rating-picker .acf-star--hover {
                color: var(--color-filled, #ffc107);
            }
            .acf-star-rating-picker .acf-star--half {
                background: linear-gradient(90deg, var(--color-filled, #ffc107) 50%, var(--color-empty, #e0e0e0) 50%);
                -webkit-background-clip: text;
                background-clip: text;
                -webkit-text-fill-color: transparent;
            }
            .acf-star-clear {
                font-size: 12px;
                color: #999;
                margin-left: 8px;
                padding: 0 4px;
                opacity: 0.6;
            }
            .acf-star-clear:hover {
                opacity: 1;
                color: #dc3545;
            }
            .acf-star-value-display {
                margin-left: 8px;
                font-size: 12px;
                color: #666;
            }
            /* Frontend display */
            .acf-star-rating-display {
                display: inline-flex;
                gap: 2px;
            }
            .acf-star-rating-display .acf-star {
                font-size: var(--star-size, 24px);
                color: var(--color-empty, #e0e0e0);
                line-height: 1;
            }
            .acf-star-rating-display .acf-star--filled {
                color: var(--color-filled, #ffc107);
            }
            .acf-star-rating-display .acf-star--half {
                background: linear-gradient(90deg, var(--color-filled, #ffc107) 50%, var(--color-empty, #e0e0e0) 50%);
                -webkit-background-clip: text;
                background-clip: text;
                -webkit-text-fill-color: transparent;
            }
            </style>
            CSS;
    }

    /**
     * Get inline JavaScript for star picker interactivity.
     */
    private function getInlineScript(string $inputId): string
    {
        $escapedId = addslashes($inputId);

        return <<<JS
            <script>
            (function() {
                const input = document.getElementById('{$escapedId}');
                if (!input) return;

                const picker = input.nextElementSibling;
                if (!picker || !picker.classList.contains('acf-star-rating-picker')) return;

                const stars = picker.querySelectorAll('.acf-star');
                const clearBtn = picker.querySelector('.acf-star-clear');
                const valueDisplay = picker.querySelector('.acf-star-value-display');
                const maxStars = parseInt(picker.dataset.max) || 5;
                const allowHalf = picker.dataset.half === '1';

                function updateStars(value) {
                    stars.forEach((star, index) => {
                        const starValue = index + 1;
                        star.classList.remove('acf-star--filled', 'acf-star--half', 'acf-star--hover');

                        if (starValue <= value) {
                            star.classList.add('acf-star--filled');
                        } else if (allowHalf && (starValue - 0.5) <= value) {
                            star.classList.add('acf-star--half');
                        }
                    });

                    if (valueDisplay) {
                        valueDisplay.textContent = value > 0 ? value + '/' + maxStars : '';
                    }
                }

                function setValue(value) {
                    input.value = value;
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                    updateStars(value);
                }

                // Click handler
                stars.forEach((star, index) => {
                    star.addEventListener('click', function(e) {
                        let value = index + 1;

                        // If clicking same star, allow toggling off or half
                        if (parseFloat(input.value) === value) {
                            if (allowHalf) {
                                value = value - 0.5;
                            } else {
                                value = 0;
                            }
                        }

                        setValue(value);
                    });

                    // Hover preview
                    star.addEventListener('mouseenter', function() {
                        const hoverValue = index + 1;
                        stars.forEach((s, i) => {
                            s.classList.toggle('acf-star--hover', i < hoverValue);
                        });
                    });
                });

                picker.addEventListener('mouseleave', function() {
                    stars.forEach(s => s.classList.remove('acf-star--hover'));
                });

                // Clear button
                if (clearBtn) {
                    clearBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        setValue(0);
                    });
                }

                // Initialize display
                updateStars(parseFloat(input.value) || 0);
            })();
            </script>
            JS;
    }
}
