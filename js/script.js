// Smooth scrolling
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// Scroll animations
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

document.querySelectorAll('.feature-card, .service-item').forEach(el => {
    observer.observe(el);
});

// Navbar scroll effect
window.addEventListener('scroll', () => {
    const navbar = document.querySelector('.navbar');
    if (window.scrollY > 100) {
        navbar.style.background = 'rgba(15, 23, 42, 0.98)';
    } else {
        navbar.style.background = 'rgba(15, 23, 42, 0.95)';
    }
});


// Gallery Carousel
let currentSlide = 0;
const slides = document.querySelectorAll('.gallery-slide');
const navDots = document.querySelectorAll('.nav-dot');
let autoSlideInterval;

function showSlide(index) {
    // Remove active class from all
    slides.forEach(slide => slide.classList.remove('active'));
    navDots.forEach(dot => dot.classList.remove('active'));
    
    // Add active class to current
    slides[index].classList.add('active');
    navDots[index].classList.add('active');
    
    currentSlide = index;
}

function nextSlide() {
    let next = (currentSlide + 1) % slides.length;
    showSlide(next);
}

function prevSlide() {
    let prev = (currentSlide - 1 + slides.length) % slides.length;
    showSlide(prev);
}

function startAutoSlide() {
    autoSlideInterval = setInterval(nextSlide, 5000); // Change slide every 5 seconds
}

function stopAutoSlide() {
    clearInterval(autoSlideInterval);
}

// Navigation dots click
navDots.forEach((dot, index) => {
    dot.addEventListener('click', () => {
        showSlide(index);
        stopAutoSlide();
        startAutoSlide(); // Restart auto slide
    });
});

// Next/Prev buttons
document.querySelector('.gallery-btn.next')?.addEventListener('click', () => {
    nextSlide();
    stopAutoSlide();
    startAutoSlide();
});

document.querySelector('.gallery-btn.prev')?.addEventListener('click', () => {
    prevSlide();
    stopAutoSlide();
    startAutoSlide();
});

// Start auto slide when page loads
if (slides.length > 0) {
    startAutoSlide();
}

// Pause on hover
document.querySelector('.gallery-container')?.addEventListener('mouseenter', stopAutoSlide);
document.querySelector('.gallery-container')?.addEventListener('mouseleave', startAutoSlide);