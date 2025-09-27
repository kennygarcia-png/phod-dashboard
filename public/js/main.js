/**
 * PhOD Main JavaScript
 */

// Mobile menu toggle
document.addEventListener('DOMContentLoaded', function() {
    const navToggle = document.querySelector('.nav-toggle');
    const nav = document.querySelector('.nav');
    
    if (navToggle && nav) {
        navToggle.addEventListener('click', function() {
            nav.classList.toggle('active');
            
            // Change icon (if using hamburger/X)
            const icon = this.textContent;
            this.textContent = icon === '☰' ? '✕' : '☰';
        });
    }
    
    // Close mobile menu when clicking outside
    document.addEventListener('click', function(e) {
        if (nav && !nav.contains(e.target) && !navToggle.contains(e.target)) {
            nav.classList.remove('active');
            if (navToggle) navToggle.textContent = '☰';
        }
    });
});
