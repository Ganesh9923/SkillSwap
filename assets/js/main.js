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
            // If currently on client-specific page, smoothly transition to Creator Hub
            if (window.location.pathname.includes('my_bookings.php')) {
                currentUrl.pathname = currentUrl.pathname.replace('my_bookings.php', 'creator.php');
            }
        } else if (val.startsWith('client_')) {
            const clientName = val.replace('client_', '');
            currentUrl.searchParams.set('as_client', clientName);
            // If on creator-specific page and switching to client, redirect to My Bookings
            if (window.location.pathname.includes('creator.php') || window.location.pathname.includes('post_gig.php')) {
                currentUrl.pathname = currentUrl.pathname.replace(/creator\.php|post_gig\.php/, 'my_bookings.php');
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
 * 4. Global Alert / Validation Modal Popup
 */
function showAlertPopup(title, message, icon = '⚠️') {
    const modal = document.getElementById('alert-popup-modal');
    const titleEl = document.getElementById('alert-popup-title');
    const msgEl = document.getElementById('alert-popup-message');
    const iconEl = document.getElementById('alert-popup-icon');

    if (titleEl) titleEl.textContent = title;
    if (msgEl) msgEl.innerHTML = message;
    if (iconEl) iconEl.textContent = icon;

    openModal('alert-popup-modal');
}

/**
 * 5. Toast Notification Utility
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
    
    let icon = 'ℹ️';
    if (type === 'success') icon = '✨';
    if (type === 'error') icon = '⚠️';
    if (type === 'warning') icon = '⏳';

    toast.innerHTML = `<span style="font-size: 1.15rem; line-height: 1;">${icon}</span> <div style="flex:1;">${message}</div>`;
    container.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100%)';
        toast.style.transition = 'all 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 4500);
}

// Auto-detect URL notifications on page load
document.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('error')) {
        const errorMsg = urlParams.get('error');
        showAlertPopup('Validation Notice', errorMsg, '⚠️');
        showToast(errorMsg, 'error');
        const cleanUrl = new URL(window.location.href);
        cleanUrl.searchParams.delete('error');
        window.history.replaceState({}, '', cleanUrl.toString());
    }
    if (urlParams.has('posted')) {
        showToast('✨ Gig published successfully to the marketplace!', 'success');
        const cleanUrl = new URL(window.location.href);
        cleanUrl.searchParams.delete('posted');
        window.history.replaceState({}, '', cleanUrl.toString());
    }
    if (urlParams.has('booked')) {
        showToast('✨ Booking submitted with status Pending!', 'success');
        const cleanUrl = new URL(window.location.href);
        cleanUrl.searchParams.delete('booked');
        window.history.replaceState({}, '', cleanUrl.toString());
    }
});
