/**
 * SkillSwap - Marketplace, Booking & Dashboard Interactions
 * Hackathon ID: AZIS-SNTAGG | Track 2: Real-World AI Products
 */

document.addEventListener('DOMContentLoaded', () => {
    initBookingModalTriggers();
    initBookingFormSubmit();
    initCreatorDashboardActions();
    initCardSheenEffects();
    initLiveSearchFilter();
});

/**
 * 1. Booking Modal Trigger & Data Population (Feature 3)
 */
function initBookingModalTriggers() {
    document.querySelectorAll('.book-gig-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const gigId = btn.getAttribute('data-gig-id');
            const gigTitle = btn.getAttribute('data-gig-title');
            const creatorName = btn.getAttribute('data-creator-name');
            const gigRate = btn.getAttribute('data-gig-rate');

            const modalGigId = document.getElementById('modal-gig-id');
            const modalGigTitle = document.getElementById('modal-gig-title');
            const modalCreatorName = document.getElementById('modal-creator-name');
            const modalGigRate = document.getElementById('modal-gig-rate');

            if (modalGigId) modalGigId.value = gigId;
            if (modalGigTitle) modalGigTitle.textContent = gigTitle;
            if (modalCreatorName) modalCreatorName.textContent = creatorName;
            if (modalGigRate) modalGigRate.textContent = gigRate;

            openModal('booking-modal');
        });
    });
}

/**
 * 2. Booking Form Submission (Feature 3 -> Pending Status)
 */
function initBookingFormSubmit() {
    const bookingForm = document.getElementById('booking-form');
    if (!bookingForm) return;

    bookingForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const submitBtn = bookingForm.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span>Booking...</span>';

        const formData = new FormData(bookingForm);

        try {
            const response = await fetch('actions/book_gig.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'Accept': 'application/json'
                }
            });

            const result = await response.json();

            if (result.status === 'success') {
                closeModal('booking-modal');
                showToast(`✨ Booking Confirmed! Status: Pending for ${result.data.creator_name}`, 'success');
                
                // Show instant confirmation overlay or redirect to My Bookings
                setTimeout(() => {
                    window.location.href = `my_bookings.php?client_name=${encodeURIComponent(formData.get('client_name'))}&booked=1`;
                }, 900);
            } else {
                showToast(result.message || 'Error processing booking', 'error');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        } catch (err) {
            console.error(err);
            // Fallback to standard form submit if JSON parse fails
            bookingForm.submit();
        }
    });
}

/**
 * 3. Creator Dashboard Status Actions (Feature 4 & DP1 Rejection)
 */
function initCreatorDashboardActions() {
    // Accept Booking Action
    document.querySelectorAll('.btn-accept-booking').forEach(btn => {
        btn.addEventListener('click', async (e) => {
            const bookingId = btn.getAttribute('data-booking-id');
            const card = document.getElementById(`booking-card-${bookingId}`);

            if (confirm('Accept this booking and start project with client?')) {
                await updateStatus(bookingId, 'Accepted', null, card);
            }
        });
    });

    // Decline Booking Action (DP1 - with Reason capture)
    document.querySelectorAll('.btn-decline-booking').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const bookingId = btn.getAttribute('data-booking-id');
            const declineModalId = document.getElementById('decline-booking-id');
            if (declineModalId) {
                declineModalId.value = bookingId;
                openModal('decline-modal');
            } else {
                const reason = prompt('Reason for declining (DP1 Feedback for client):', 'Schedule currently full for this timeframe');
                if (reason !== null) {
                    const card = document.getElementById(`booking-card-${bookingId}`);
                    updateStatus(bookingId, 'Declined', reason, card);
                }
            }
        });
    });

    // Decline form submission inside modal
    const declineForm = document.getElementById('decline-form');
    if (declineForm) {
        declineForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const bookingId = document.getElementById('decline-booking-id').value;
            const reason = document.getElementById('decline-reason-select').value;
            const customReason = document.getElementById('decline-custom-reason').value;
            const finalReason = customReason ? customReason : reason;

            closeModal('decline-modal');
            const card = document.getElementById(`booking-card-${bookingId}`);
            await updateStatus(bookingId, 'Declined', finalReason, card);
        });
    }
}

async function updateStatus(bookingId, status, reason = null, cardElement = null) {
    try {
        const formData = new FormData();
        formData.append('booking_id', bookingId);
        formData.append('status', status);
        if (reason) formData.append('decline_reason', reason);

        const response = await fetch('actions/update_booking.php', {
            method: 'POST',
            body: formData,
            headers: { 'Accept': 'application/json' }
        });

        const result = await response.json();
        if (result.status === 'success') {
            showToast(`Booking #${bookingId} marked as ${status}`, status === 'Accepted' ? 'success' : 'info');
            
            // Live DOM update without page reload
            if (cardElement) {
                const badgeContainer = cardElement.querySelector('.status-badge-container');
                const actionsContainer = cardElement.querySelector('.booking-actions');
                
                if (badgeContainer) {
                    const badgeClass = status.toLowerCase();
                    badgeContainer.innerHTML = `
                        <span class="status-badge ${badgeClass}">
                            <span class="status-dot"></span>
                            ${status}
                        </span>
                    `;
                }
                if (actionsContainer) {
                    actionsContainer.innerHTML = `<span class="text-muted" style="font-size: 0.85rem;">Status Updated</span>`;
                }
            } else {
                setTimeout(() => window.location.reload(), 600);
            }
        } else {
            showToast(result.message || 'Error updating status', 'error');
        }
    } catch (err) {
        console.error(err);
        showToast('Network error while updating booking', 'error');
    }
}

/**
 * 4. Client-side Search & Category Filtering (Feature 2)
 */
function initLiveSearchFilter() {
    const searchInput = document.getElementById('marketplace-search');
    const gigCards = document.querySelectorAll('.gig-card');
    const sortSelect = document.getElementById('sort-select');

    if (searchInput) {
        let debounceTimer;
        searchInput.addEventListener('input', (e) => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                const query = e.target.value.toLowerCase().trim();
                let matchCount = 0;

                gigCards.forEach(card => {
                    const title = (card.getAttribute('data-title') || '').toLowerCase();
                    const desc = (card.getAttribute('data-desc') || '').toLowerCase();
                    const creator = (card.getAttribute('data-creator') || '').toLowerCase();
                    const cat = (card.getAttribute('data-category') || '').toLowerCase();

                    if (!query || title.includes(query) || desc.includes(query) || creator.includes(query) || cat.includes(query)) {
                        card.style.display = 'flex';
                        matchCount++;
                    } else {
                        card.style.display = 'none';
                    }
                });

                const noResults = document.getElementById('no-gigs-found');
                if (noResults) {
                    noResults.style.display = matchCount === 0 ? 'block' : 'none';
                }
            }, 180);
        });
    }

    if (sortSelect) {
        sortSelect.addEventListener('change', (e) => {
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('sort', e.target.value);
            window.location.href = currentUrl.toString();
        });
    }
}

/**
 * 5. Glacial Card Sheen & Tilt Micro-Interactions (Craft)
 */
function initCardSheenEffects() {
    const cards = document.querySelectorAll('.gig-card, .glass-panel');
    cards.forEach(card => {
        card.addEventListener('mousemove', (e) => {
            const rect = card.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            card.style.setProperty('--mouse-x', `${x}px`);
            card.style.setProperty('--mouse-y', `${y}px`);
        });
    });
}
