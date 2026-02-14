const initAddressAutocomplete = () => {
    const input = document.getElementById('delivery_address');
    if (!input) {
        return;
    }

    if (typeof google === 'undefined' || !google.maps || !google.maps.places) {
        return;
    }

    const autocomplete = new google.maps.places.Autocomplete(input, {
        types: ['geocode'],
    });

    autocomplete.addListener('place_changed', () => {
        const place = autocomplete.getPlace();
        if (place && place.formatted_address) {
            input.value = place.formatted_address;
        }
    });
};

window.initAddressAutocomplete = initAddressAutocomplete;

document.addEventListener('DOMContentLoaded', () => {
    const confirmButtons = document.querySelectorAll('[data-confirm]');
    confirmButtons.forEach((button) => {
        button.addEventListener('click', (event) => {
            const message = button.getAttribute('data-confirm');
            if (!window.confirm(message)) {
                event.preventDefault();
            }
        });
    });

    if (typeof google !== 'undefined' && google.maps && google.maps.places) {
        initAddressAutocomplete();
    }
});
