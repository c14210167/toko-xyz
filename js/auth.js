// Typing Animation
const words = [
    'spareparts?',
    'networking?',
    'accessories?',
    'services?',
    'we got all of them!'
];

let wordIndex = 0;
let charIndex = 0;
let isDeleting = false;
let typingSpeed = 100;

function typeWriter() {
    const typingElement = document.getElementById('typingText');
    const currentWord = words[wordIndex];

    if (isDeleting) {
        // Backspace
        typingElement.textContent = currentWord.substring(0, charIndex - 1);
        charIndex--;
        typingSpeed = 50;
    } else {
        // Typing
        typingElement.textContent = currentWord.substring(0, charIndex + 1);
        charIndex++;
        typingSpeed = 100;
    }

    // Check if word is complete
    if (!isDeleting && charIndex === currentWord.length) {
        // Pause at end of word
        typingSpeed = 2000;
        isDeleting = true;
    } else if (isDeleting && charIndex === 0) {
        isDeleting = false;
        wordIndex = (wordIndex + 1) % words.length;
        typingSpeed = 500;
    }

    setTimeout(typeWriter, typingSpeed);
}

// Start typing animation when page loads
window.addEventListener('load', () => {
    setTimeout(typeWriter, 1000);
});

// Form validation (basic)
const forms = document.querySelectorAll('.auth-form');
forms.forEach(form => {
    form.addEventListener('submit', (e) => {
        const password = form.querySelector('#password');
        const confirmPassword = form.querySelector('#confirm_password');
        
        if (confirmPassword && password.value !== confirmPassword.value) {
            e.preventDefault();
            alert('Password dan konfirmasi password tidak cocok!');
            confirmPassword.focus();
        }
    });
});

// Add input focus animation
const inputs = document.querySelectorAll('.form-group input');
inputs.forEach(input => {
    input.addEventListener('focus', function() {
        this.parentElement.style.transform = 'translateY(-2px)';
    });
    
    input.addEventListener('blur', function() {
        this.parentElement.style.transform = 'translateY(0)';
    });
});

// Toggle password visibility
function togglePassword(inputId, button) {
    const input = document.getElementById(inputId);
    
    if (input.type === 'password') {
        input.type = 'text';
        button.textContent = '🙈'; // Hide password icon
    } else {
        input.type = 'password';
        button.textContent = '👁️'; // Show password icon
    }
}