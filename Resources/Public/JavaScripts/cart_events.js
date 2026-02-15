var cart_events = (function () {

    var eventDates = document.getElementsByClassName('event-event-date');

    for (var eventDateCount=0; eventDateCount < eventDates.length; eventDateCount++) {
        var addToCartForms = eventDates[eventDateCount].querySelectorAll('form.add-to-cart-form');
        for (var addToCartFormsCount=0; addToCartFormsCount < addToCartForms.length; addToCartFormsCount++) {
            var priceCategorySelects = addToCartForms[addToCartFormsCount].querySelectorAll('.price-category-select');
            for (var priceCategorySelectCount=0; priceCategorySelectCount < priceCategorySelects.length; priceCategorySelectCount++) {
                (function(eventDate) {
                    priceCategorySelects[priceCategorySelectCount].addEventListener('change', function(){ updatePriceCategory(this, eventDate) }, false);
                })(eventDates[eventDateCount]);
            }
        }
    }

    function updatePriceCategory(element, eventDate) {
        if (!element || !element.selectedOptions || !element.selectedOptions[0] || !eventDate) {
            return;
        }

        var selectedOption = element.selectedOptions[0];
        var selectedValue = selectedOption.value;
        var style = selectedOption.style.display;
        var title = selectedOption.getAttribute('data-title');
        var regularPrice = selectedOption.getAttribute('data-regular-price');
        var specialPrice = selectedOption.getAttribute('data-special-price');

        eventDate.querySelectorAll('.event-date-price-category').forEach(el => {
            el.style.display = 'none';
        });
        
        var selectedButton = eventDate.querySelector('.event-date-price-category-' + selectedValue);
        if (selectedButton) {
            selectedButton.style.display = style;
        }

        if (title) {
            var regularPriceEl = eventDate.querySelector('.event-date-price .regular-price');
            if (regularPriceEl) regularPriceEl.style.display = 'none';
            
            var specialPriceEl = eventDate.querySelector('.event-date-price .special-price');
            if (specialPriceEl) specialPriceEl.style.display = 'block';

            var titleEl = eventDate.querySelector('.event-date-price .special-price .title');
            if (titleEl) titleEl.innerHTML = title;

            var regularPricePriceEl = eventDate.querySelector('.event-date-price .regular-price .price');
            if (regularPricePriceEl) regularPricePriceEl.innerHTML = '';

            var specialPriceRegularPriceEl = eventDate.querySelector('.event-date-price .special-price .regular-price .price');
            if (specialPriceRegularPriceEl) specialPriceRegularPriceEl.innerHTML = regularPrice;

            var specialPriceSpecialPriceEl = eventDate.querySelector('.event-date-price .special-price .special-price .price');
            if (specialPriceSpecialPriceEl) specialPriceSpecialPriceEl.innerHTML = specialPrice;
        } else {
            var regularPriceEl = eventDate.querySelector('.event-date-price .regular-price');
            if (regularPriceEl) regularPriceEl.style.display = 'block';
            
            var specialPriceEl = eventDate.querySelector('.event-date-price .special-price');
            if (specialPriceEl) specialPriceEl.style.display = 'none';

            var titleEl = eventDate.querySelector('.event-date-price .special-price .title');
            if (titleEl) titleEl.innerHTML = '';

            var regularPricePriceEl = eventDate.querySelector('.event-date-price .regular-price .price');
            if (regularPricePriceEl) regularPricePriceEl.innerHTML = regularPrice;

            var specialPriceRegularPriceEl = eventDate.querySelector('.event-date-price .special-price .regular-price .price');
            if (specialPriceRegularPriceEl) specialPriceRegularPriceEl.innerHTML = '';
            
            var specialPriceSpecialPriceEl = eventDate.querySelector('.event-date-price .special-price .special-price .price');
            if (specialPriceSpecialPriceEl) specialPriceSpecialPriceEl.innerHTML = '';
        }
    }

})();
