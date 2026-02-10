/**
 * Products page interactivity
 */
document.addEventListener('DOMContentLoaded', function () {

    // Toggle product detail panels
    document.querySelectorAll('.product-toggle').forEach(function (toggle) {
        toggle.addEventListener('click', function () {
            var productId = this.getAttribute('data-product-id');
            var details = document.querySelector('[data-details-for="' + productId + '"]');
            var arrow = this.querySelector('.expand-arrow');

            if (details) {
                details.classList.toggle('hidden');
                if (arrow) {
                    arrow.classList.toggle('rotate-180');
                }

                // Re-initialize Lucide icons for newly visible elements
                if (!details.classList.contains('hidden') && typeof lucide !== 'undefined') {
                    lucide.createIcons();
                }
            }
        });
    });
});
