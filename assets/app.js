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

const startPolling = () => {
    const realtimePaths = [
        '/admin/index.php',
        '/admin/orders.php',
        '/admin/staff.php',
        '/admin/offline_order.php',
        '/admin/sales.php',
        '/user/orders.php',
    ];

    if (!realtimePaths.includes(window.location.pathname)) {
        return;
    }

    setInterval(() => {
        window.location.reload();
    }, 5000);
};

document.addEventListener('DOMContentLoaded', startPolling);

// Real-time product filtering and sorting
const initProductFilters = () => {
    const searchInput = document.querySelector('.search-input');
    const categoryFilters = document.querySelectorAll('.filter-btn');
    const sortSelect = document.querySelector('.sort-select');
    const productGrid = document.querySelector('.product-grid');
    
    if (!productGrid) return;
    
    let currentCategory = 'all';
    let currentSearch = '';
    let currentSort = sortSelect ? sortSelect.value : 'newest';
    
    const filterAndSort = () => {
        const products = Array.from(productGrid.querySelectorAll('.product-card'));
        
        // Filter products
        products.forEach(product => {
            const productCategory = product.dataset.category;
            const productName = product.dataset.name.toLowerCase();
            
            const matchesCategory = currentCategory === 'all' || productCategory === currentCategory;
            const matchesSearch = currentSearch === '' || productName.includes(currentSearch.toLowerCase());
            
            if (matchesCategory && matchesSearch) {
                product.style.display = '';
            } else {
                product.style.display = 'none';
            }
        });
        
        // Sort visible products
        const visibleProducts = products.filter(p => p.style.display !== 'none');
        
        visibleProducts.sort((a, b) => {
            const priceA = parseFloat(a.dataset.price);
            const priceB = parseFloat(b.dataset.price);
            const nameA = a.dataset.name.toLowerCase();
            const nameB = b.dataset.name.toLowerCase();
            const dateA = parseInt(a.dataset.date || '0');
            const dateB = parseInt(b.dataset.date || '0');
            
            switch (currentSort) {
                case 'price_low':
                    return priceA - priceB;
                case 'price_high':
                    return priceB - priceA;
                case 'name':
                    return nameA.localeCompare(nameB);
                case 'newest':
                    return dateB - dateA;
                default:
                    return 0;
            }
        });
        
        // Reorder in DOM
        visibleProducts.forEach(product => {
            productGrid.appendChild(product);
        });
        
        // Show "no results" message if needed
        const noResults = productGrid.querySelector('.no-results');
        if (visibleProducts.length === 0) {
            if (!noResults) {
                const div = document.createElement('div');
                div.className = 'no-results card';
                div.textContent = 'No products found matching your criteria.';
                productGrid.appendChild(div);
            }
        } else {
            if (noResults) {
                noResults.remove();
            }
        }
    };
    
    // Search input handler
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            currentSearch = e.target.value;
            filterAndSort();
        });
    }
    
    // Category filter handlers
    categoryFilters.forEach(filter => {
        filter.addEventListener('click', (e) => {
            e.preventDefault();
            currentCategory = filter.dataset.category;
            
            // Update active state
            categoryFilters.forEach(f => f.classList.remove('active'));
            filter.classList.add('active');
            
            filterAndSort();
        });
    });
    
    // Sort select handler
    if (sortSelect) {
        sortSelect.addEventListener('change', (e) => {
            currentSort = e.target.value;
            filterAndSort();
        });
    }
};

document.addEventListener('DOMContentLoaded', initProductFilters);

// Global cart count updater
async function updateCartCount() {
    const cartBadge = document.querySelector('.cart-badge');
    if (!cartBadge) return;

    try {
        const response = await fetch('/api/cart.php?action=count');
        const data = await response.json();
        
        if (data.success) {
            cartBadge.textContent = data.count;
            cartBadge.style.display = data.count > 0 ? 'flex' : 'none';
        }
    } catch (error) {
        console.error('Error updating cart count');
    }
}

// Update cart count on page load for logged-in users
document.addEventListener('DOMContentLoaded', () => {
    if (document.querySelector('.cart-link')) {
        updateCartCount();
    }
});

// Make updateCartCount available globally
window.updateCartCount = updateCartCount;
