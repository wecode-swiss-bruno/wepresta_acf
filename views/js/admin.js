/**
 * Module Starter - JavaScript Back-Office
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        console.log('[WeprestaAcf] Admin JS loaded');

        // Confirmation avant suppression
        $('[data-wepresta_acf-confirm]').on('click', function(e) {
            var message = $(this).data('wepresta_acf-confirm') || 'Êtes-vous sûr ?';
            if (!confirm(message)) {
                e.preventDefault();
                return false;
            }
        });

        // Toggle des options conditionnelles
        $('[data-wepresta_acf-toggle]').on('change', function() {
            var target = $(this).data('wepresta_acf-toggle');
            var $target = $(target);

            if ($(this).is(':checked')) {
                $target.slideDown(200);
            } else {
                $target.slideUp(200);
            }
        }).trigger('change');
    });

})(jQuery);

