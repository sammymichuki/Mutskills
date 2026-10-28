// Interactive search functionality
const searchInput = document.querySelector('.search-input');
const searchIcon = document.querySelector('.search-icon');

if (searchInput) {
    if (searchIcon) {
        searchInput.addEventListener('focus', () => {
            searchIcon.style.color = '#10b981';
        });

        searchInput.addEventListener('blur', () => {
            searchIcon.style.color = '#9ca3af';
        });
    }

    searchInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            const query = encodeURIComponent(searchInput.value.trim());
            window.location.href = 'dashboard.php?q=' + query;
        }
    });

    // Typing effect for search placeholder
    const searchPlaceholders = [
        'Search skills, services, or students...',
        'Find tutoring services...',
        'Look for graphic design...',
        'Search photography services...',
        'Find cooking classes...'
    ];
    
    let currentIndex = 0;
    setInterval(() => {
        currentIndex = (currentIndex + 1) % searchPlaceholders.length;
        searchInput.placeholder = searchPlaceholders[currentIndex];
    }, 3000);
}

// Add smooth hover effects for buttons
const buttons = document.querySelectorAll('.btn-primary, .btn-secondary, .join-btn');
buttons.forEach(button => {
    button.addEventListener('mouseenter', () => {
        button.style.transform = 'translateY(-2px)';
    });
    
    button.addEventListener('mouseleave', () => {
        button.style.transform = 'translateY(0)';
    });
});

// Category cards clickability
document.querySelectorAll('.category-card').forEach(card => {
    card.style.cursor = 'pointer';
    card.addEventListener('click', () => {
        const cat = encodeURIComponent(card.dataset.category || '');
        window.location.href = 'dashboard.php?category=' + cat;
    });
});

// Add scroll effect to header
window.addEventListener('scroll', () => {
    const header = document.querySelector('.header');
    if (header) {
        if (window.scrollY > 50) {
            header.style.boxShadow = '0 2px 20px rgba(0,0,0,0.1)';
        } else {
            header.style.boxShadow = 'none';
        }
    }
});