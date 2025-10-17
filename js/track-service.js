// Add smooth scroll animations
document.addEventListener('DOMContentLoaded', function() {
    // Animate info cards on scroll
    const cards = document.querySelectorAll('.info-card');
    
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);
    
    cards.forEach(card => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(30px)';
        card.style.transition = 'all 0.6s ease-out';
        observer.observe(card);
    });

    // Auto-capitalize service number input
    const serviceInput = document.querySelector('input[name="service_number"]');
    if (serviceInput) {
        serviceInput.addEventListener('input', function(e) {
            this.value = this.value.toUpperCase();
        });
    }
});