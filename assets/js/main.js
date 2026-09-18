/**
 * SkillSwap - Core JavaScript & Persona Management
 * Hackathon ID: AZIS-SNTAGG | Track 2: Real-World AI Products
 */

document.addEventListener('DOMContentLoaded', () => {
    initScrollReveals();
    initPersonaSwitcher();
    initModals();
});

/**
 * 1. Scroll-Staged Reveals via IntersectionObserver
 */
function initScrollReveals() {
    const revealElements = document.querySelectorAll('.reveal');
    if (!revealElements.length) return;

    const revealObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('active');
                observer.unobserve(entry.target);
            }
        });
    }, {
        root: null,
        threshold: 0.08,
        rootMargin: '0px 0px -40px 0px'
    });

    revealElements.forEach(el => revealObserver.observe(el));
}

/**
 * 2. Zero-Auth Universal Persona Switcher
 * Remembers selection in localStorage and updates current view seamlessly.
 */
function initPersonaSwitcher() {
    const personaSelect = document.getElementById('persona-select');
    if (!personaSelect) return;

    // Check if localStorage has a saved persona
    const savedPersona = localStorage.getItem('skillswap_persona');
    if (savedPersona && !window.location.search.includes('as_')) {
        // Sync select value if matching
        if (personaSelect.value !== savedPersona) {
            // Optional auto-sync if desired
        }
    }

    personaSelect.addEventListener('change', (e) => {
        const val = e.target.value;
        localStorage.setItem('skillswap_persona', val);

        const currentUrl = new URL(window.location.href);
        currentUrl.searchParams.delete('as_creator');
        currentUrl.searchParams.delete('as_client');

        if (val.startsWith('creator_')) {
            const creatorId = val.replace('creator_', '');
            currentUrl.searchParams.set('as_creator', creatorId);
            // If on client page, redirect to creator hub
            if (window.location.pathname.includes('my_bookings.php')) {
                currentUrl.pathname = currentUrl.pathname.replace('my_bookings.php', 'creator.php');
            }
        } else if (val.startsWith('client_')) {
            const clientName = val.replace('client_', '');
            currentUrl.searchParams.set('as_client', clientName);
            // If on creator page and switching to client, redirect to client hub
            if (window.location.pathname.includes('creator.php') || window.location.pathname.includes('post_gig.php')) {
                currentUrl.pathname = currentUrl.pathname.replace(/creator\.php|post_gig\.php/, 'client.php');
            }
        }

        window.location.href = currentUrl.toString();
    });
}

/**
 * 3. Modal Dialog Controller
 */
function initModals() {
    document.querySelectorAll('[data-modal-close]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const modal = btn.closest('.modal-overlay');
            if (modal) closeModal(modal.id);
        });
    });

    document.querySelectorAll('.modal-overlay').forEach(modal => {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeModal(modal.id);
            }
        });
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-overlay.active').forEach(modal => {
                closeModal(modal.id);
            });
        }
    });
}

function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

/**
 * 4. Toast Notification Utility
 */
function showToast(message, type = 'info') {
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'toast-container';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    
    let icon = '❄️';
    if (type === 'success') icon = '✨';
    if (type === 'error') icon = '⚠️';

    toast.innerHTML = `<span>${icon}</span> <div>${message}</div>`;
    container.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100%)';
        toast.style.transition = 'all 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}
