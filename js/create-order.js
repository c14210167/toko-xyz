// Issue database untuk setiap device type
const issueDatabase = {
    laptop: [
        'Layar mati / tidak menyala',
        'Laptop lemot / hang',
        'Keyboard rusak / tidak berfungsi',
        'Baterai cepat habis',
        'Overheating / panas berlebih',
        'Tidak bisa charging',
        'Speaker tidak bunyi',
        'Touchpad tidak berfungsi',
        'Lainnya'
    ],
    printer: [
        'Paper jam',
        'Hasil print bergaris',
        'Tidak bisa print / error',
        'Tinta bocor',
        'Print buram / tidak jelas',
        'Bunyi berisik',
        'Tidak terdeteksi komputer',
        'Lainnya'
    ],
    computer: [
        'Tidak bisa booting',
        'Blue screen / BSOD',
        'Komputer lemot',
        'No display / layar gelap',
        'Bunyi beep',
        'Mati sendiri',
        'USB port tidak berfungsi',
        'Upgrade hardware',
        'Lainnya'
    ],
    other: []
};

let selectedDevice = '';
let selectedIssue = '';

// Step 1: Device Selection
document.querySelectorAll('.device-card').forEach(card => {
    card.addEventListener('click', function() {
        selectedDevice = this.dataset.device;
        console.log('Device selected:', selectedDevice);
        
        if (selectedDevice === 'other') {
            goToStep('custom');
        } else {
            loadIssues(selectedDevice);
            goToStep(2);
        }
    });
});

function loadIssues(deviceType) {
    const issueGrid = document.getElementById('issueGrid');
    const issues = issueDatabase[deviceType];
    
    issueGrid.innerHTML = '';
    
    issues.forEach(issue => {
        const issueCard = document.createElement('div');
        issueCard.className = 'issue-card' + (issue === 'Lainnya' ? ' other' : '');
        issueCard.textContent = issue;
        
        issueCard.addEventListener('click', function() {
            if (issue === 'Lainnya') {
                goToStep('custom');
            } else {
                selectedIssue = issue;
                console.log('Issue selected:', selectedIssue);
                proceedToDetails();
            }
        });
        
        issueGrid.appendChild(issueCard);
    });
}

function proceedToDetails() {
    document.getElementById('deviceType').value = getDeviceName(selectedDevice);
    document.getElementById('issueType').value = selectedIssue;
    goToStep(3);
}

function proceedFromCustomIssue() {
    const customIssue = document.getElementById('customIssue').value.trim();
    if (!customIssue) {
        alert('Mohon jelaskan kerusakan terlebih dahulu');
        return;
    }
    selectedIssue = customIssue;
    console.log('Custom issue:', selectedIssue);
    proceedToDetails();
}

function getDeviceName(device) {
    const names = {
        laptop: 'Laptop',
        printer: 'Printer',
        computer: 'Komputer / PC',
        other: 'Lainnya'
    };
    return names[device] || device;
}

function goToStep(step) {
    document.querySelectorAll('.step-container').forEach(container => {
        container.classList.remove('active');
    });
    
    if (step === 'custom') {
        document.getElementById('stepCustom').classList.add('active');
    } else {
        document.getElementById('step' + step).classList.add('active');
    }
    
    console.log('Moved to step:', step);
}

// Global validation function untuk form submit
function validateForm() {
    console.log('=== validateForm called ===');
    console.log('Selected Device:', selectedDevice);
    console.log('Selected Issue:', selectedIssue);
    
    if (!selectedDevice || !selectedIssue) {
        alert('Silakan pilih device dan issue terlebih dahulu!');
        return false;
    }
    
    const form = document.getElementById('orderForm');
    
    // Remove old hidden inputs jika ada
    const oldDevice = form.querySelector('input[name="device_type"]');
    const oldIssue = form.querySelector('input[name="issue_type"]');
    if (oldDevice) oldDevice.remove();
    if (oldIssue) oldIssue.remove();
    
    // Add new hidden inputs
    const deviceInput = document.createElement('input');
    deviceInput.type = 'hidden';
    deviceInput.name = 'device_type';
    deviceInput.value = selectedDevice;
    form.appendChild(deviceInput);
    console.log('Added device_type:', deviceInput.value);
    
    const issueInput = document.createElement('input');
    issueInput.type = 'hidden';
    issueInput.name = 'issue_type';
    issueInput.value = selectedIssue;
    form.appendChild(issueInput);
    console.log('Added issue_type:', issueInput.value);
    
    // Log all form data
    console.log('=== ALL FORM DATA ===');
    const allInputs = form.querySelectorAll('input, textarea');
    allInputs.forEach(input => {
        if (input.name && input.type !== 'hidden') {
            console.log(input.name + ':', input.value);
        }
    });
    
    console.log('Form validation passed, submitting...');
    return true; // Allow form to submit
}

// Create floating particles
function createParticles() {
    const particlesBg = document.querySelector('.particles-bg');
    if (!particlesBg) return;
    
    for (let i = 0; i < 20; i++) {
        const particle = document.createElement('div');
        particle.style.position = 'absolute';
        particle.style.width = Math.random() * 4 + 2 + 'px';
        particle.style.height = particle.style.width;
        particle.style.background = 'rgba(6, 182, 212, 0.3)';
        particle.style.borderRadius = '50%';
        particle.style.left = Math.random() * 100 + '%';
        particle.style.top = Math.random() * 100 + '%';
        particle.style.animation = `float ${Math.random() * 10 + 10}s infinite ease-in-out`;
        particle.style.animationDelay = Math.random() * 5 + 's';
        particlesBg.appendChild(particle);
    }
}

createParticles();

console.log('create-order.js loaded successfully');
console.log('validateForm function is ready');