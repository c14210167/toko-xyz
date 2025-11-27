// Motivational quotes typing animation
let quoteIndex = 0;
let charIndex = 0;
let isDeleting = false;
let typingSpeed = 80; // Slower typing
let deletingSpeed = 40;
let pauseTime = 3000; // Pause 3 seconds before deleting

function typeMotivation() {
    const textElement = document.getElementById('motivationText');
    const currentQuote = motivationalQuotes[quoteIndex];
    
    if (isDeleting) {
        // Deleting
        textElement.textContent = currentQuote.substring(0, charIndex - 1);
        charIndex--;
        typingSpeed = deletingSpeed;
        
        if (charIndex === 0) {
            isDeleting = false;
            quoteIndex = (quoteIndex + 1) % motivationalQuotes.length;
            typingSpeed = 500; // Pause before typing next
        }
    } else {
        // Typing
        textElement.textContent = currentQuote.substring(0, charIndex + 1);
        charIndex++;
        typingSpeed = 80;
        
        if (charIndex === currentQuote.length) {
            isDeleting = true;
            typingSpeed = pauseTime; // Pause at end
        }
    }
    
    setTimeout(typeMotivation, typingSpeed);
}

// Start typing animation
if (typeof motivationalQuotes !== 'undefined') {
    setTimeout(typeMotivation, 1000);
}

// Smooth scroll for navigation
document.querySelectorAll('.nav-item').forEach(item => {
    item.addEventListener('click', function(e) {
        document.querySelectorAll('.nav-item').forEach(nav => nav.classList.remove('active'));
        this.classList.add('active');
    });
});

console.log('Staff dashboard loaded successfully');