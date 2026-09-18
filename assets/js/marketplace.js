/**
 * SkillSwap - Marketplace, Booking & Dashboard Interactions
 * Hackathon ID: AZIS-SNTAGG | Track 2: Real-World AI Products
 */

document.addEventListener('DOMContentLoaded', () => {
    initBookingModalTriggers();
    initBookingFormSubmit();
    initPostGigFormSubmit();
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

        const clientNameInput = bookingForm.querySelector('[name="client_name"]');
        const clientName = clientNameInput ? clientNameInput.value.trim() : '';

        if (clientName.length < 2) {
            showAlertPopup('Validation Error', 'Client name must be at least 2 characters.', '⚠️');
            showToast('⚠️ Client name must be at least 2 characters.', 'error');
            if (clientNameInput) clientNameInput.focus();
            return;
        }

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span>Submitting booking...</span>';

        const formData = new FormData(bookingForm);

        try {
            const response = await fetch('actions/book_gig.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const result = await response.json();

            if (response.ok && result.status === 'success') {
                closeModal('booking-modal');
                showToast(`✨ Booking Confirmed! Status: Pending for ${result.data.creator_name}`, 'success');
                
                setTimeout(() => {
                    window.location.href = `my_bookings.php?client_name=${encodeURIComponent(formData.get('client_name'))}&booked=1`;
                }, 800);
            } else {
                const errMsg = result.message || 'Error processing booking request.';
                showAlertPopup('Booking Notice', errMsg, '⚠️');
                showToast('⚠️ ' + errMsg, 'error');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        } catch (err) {
            console.error('Booking submission error:', err);
            showAlertPopup('Error', 'An unexpected network error occurred. Please try again.', '⚠️');
            showToast('⚠️ Network error. Please try again.', 'error');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    });
}

/**
 * 2b. Post a Gig Form Submission with Popup Validation (Feature 1)
 */
function initPostGigFormSubmit() {
    const postGigForm = document.getElementById('post-gig-form');
    if (!postGigForm) return;

    postGigForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const titleInput = postGigForm.querySelector('[name="title"]');
        const categoryInput = postGigForm.querySelector('[name="category"]');
        const rateInput = postGigForm.querySelector('[name="rate"]');
        const descInput = postGigForm.querySelector('[name="description"]');
        const submitBtn = postGigForm.querySelector('button[type="submit"]');

        const title = titleInput ? titleInput.value.trim() : '';
        const category = categoryInput ? categoryInput.value : '';
        const rate = rateInput ? parseFloat(rateInput.value) : 0;
        const description = descInput ? descInput.value.trim() : '';

        // Client-side instant validation
        if (title.length < 3 || title.length > 150) {
            showAlertPopup('Validation Error', 'Gig Title must be between 3 and 150 characters.', '⚠️');
            showToast('⚠️ Gig Title must be between 3 and 150 characters.', 'error');
            if (titleInput) titleInput.focus();
            return;
        }

        if (!category) {
            showAlertPopup('Validation Error', 'Please select a valid category from the dropdown.', '⚠️');
            showToast('⚠️ Please select a category.', 'error');
            if (categoryInput) categoryInput.focus();
            return;
        }

        if (isNaN(rate) || rate <= 0 || rate > 50000) {
            showAlertPopup('Validation Error', 'Rate must be a positive amount between $1.00 and $50,000.00.', '⚠️');
            showToast('⚠️ Rate must be between $1.00 and $50,000.00.', 'error');
            if (rateInput) rateInput.focus();
            return;
        }

        if (description.length < 10 || description.length > 2000) {
            showAlertPopup('Validation Error', `Description must be between 10 and 2000 characters.<br><br><small style="color:var(--text-muted);">Current character count: ${description.length} characters</small>`, '⚠️');
            showToast('⚠️ Description must be between 10 and 2000 characters.', 'error');
            if (descInput) descInput.focus();
            return;
        }

        const originalBtnText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span>Publishing gig...</span>';

        const formData = new FormData(postGigForm);

        try {
            const response = await fetch('actions/post_gig.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const result = await response.json();

            if (response.ok && result.status === 'success') {
                showToast('✨ Gig published successfully to the marketplace!', 'success');
                setTimeout(() => {
                    window.location.href = 'creator.php?posted=1#my-gigs';
                }, 700);
            } else {
                const errorMsg = result.message || (result.errors ? result.errors.join('<br>') : 'Error publishing gig.');
                showAlertPopup('Validation Error', errorMsg, '⚠️');
                showToast('⚠️ ' + errorMsg, 'error');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            }
        } catch (err) {
            console.error('Post gig error:', err);
            showAlertPopup('Submission Error', 'An error occurred while submitting. Please try again.', '⚠️');
            showToast('⚠️ Submission error occurred.', 'error');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
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
 * 4. Comprehensive Client-side Live Filtering, Category Switching & Sorting (Feature 2 & DP3)
 */
function initLiveSearchFilter() {
    const searchInput = document.getElementById('marketplace-search');
    const searchClearBtn = document.getElementById('search-clear-btn');
    const gigsGrid = document.getElementById('marketplace-gigs-grid');
    const sortSelect = document.getElementById('sort-select');
    const filterForm = document.getElementById('marketplace-filter-form');
    const categoryInput = document.getElementById('marketplace-category-input');
    const categoryChips = document.querySelectorAll('#category-chips-container .category-chip, .category-chips .category-chip');
    const noGigsFound = document.getElementById('no-gigs-found');
    const resetAllBtn = document.getElementById('reset-all-filters-btn');
    const clearAllBtn = document.getElementById('clear-all-btn');

    function applyMarketplaceFilters(overrides = {}) {
        const currentCategory = overrides.category !== undefined 
            ? overrides.category 
            : (categoryInput ? categoryInput.value : 'All');

        const currentQuery = overrides.query !== undefined 
            ? overrides.query.toLowerCase().trim() 
            : (searchInput ? searchInput.value.toLowerCase().trim() : '');

        const currentSort = overrides.sort !== undefined 
            ? overrides.sort 
            : (sortSelect ? sortSelect.value : 'fair');

        // 1. Sync Form Controls
        if (categoryInput) categoryInput.value = currentCategory;
        if (sortSelect && overrides.sort !== undefined) sortSelect.value = currentSort;
        if (searchInput && overrides.query !== undefined) searchInput.value = overrides.query;

        // 2. Toggle Search Clear Button
        if (searchClearBtn) {
            searchClearBtn.style.display = (searchInput && searchInput.value.trim().length > 0) ? 'inline-flex' : 'none';
        }

        // 3. Update Category Chips Active State
        categoryChips.forEach(chip => {
            const chipCat = chip.getAttribute('data-category') || 'All';
            if (chipCat.toLowerCase() === currentCategory.toLowerCase() || (currentCategory.toLowerCase() === 'all' && chipCat.toLowerCase() === 'all')) {
                chip.classList.add('active');
            } else {
                chip.classList.remove('active');
            }
        });

        // 4. Filter and Sort Gig Cards in DOM
        if (gigsGrid) {
            const gigCards = Array.from(gigsGrid.querySelectorAll('.gig-card'));
            let matchCount = 0;

            // Visibility Filtering
            gigCards.forEach(card => {
                const title = (card.getAttribute('data-title') || '').toLowerCase();
                const desc = (card.getAttribute('data-desc') || '').toLowerCase();
                const creator = (card.getAttribute('data-creator') || '').toLowerCase();
                const cardCat = (card.getAttribute('data-category') || '').toLowerCase();

                const categoryMatch = (currentCategory.toLowerCase() === 'all' || !currentCategory || cardCat === currentCategory.toLowerCase());
                const searchMatch = (!currentQuery || title.includes(currentQuery) || desc.includes(currentQuery) || creator.includes(currentQuery) || cardCat.includes(currentQuery));

                if (categoryMatch && searchMatch) {
                    card.style.display = 'flex';
                    matchCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            // Sorting
            if (gigCards.length > 0) {
                gigCards.sort((a, b) => {
                    const idA = parseInt(a.getAttribute('data-id') || '0', 10);
                    const idB = parseInt(b.getAttribute('data-id') || '0', 10);
                    const rateA = parseFloat(a.getAttribute('data-rate') || '0');
                    const rateB = parseFloat(b.getAttribute('data-rate') || '0');
                    const createdA = parseInt(a.getAttribute('data-created') || '0', 10);
                    const createdB = parseInt(b.getAttribute('data-created') || '0', 10);
                    const ratingA = parseFloat(a.getAttribute('data-rating') || '0');
                    const ratingB = parseFloat(b.getAttribute('data-rating') || '0');

                    if (currentSort === 'newest') {
                        return (createdB - createdA) || (idB - idA);
                    } else if (currentSort === 'cheapest') {
                        return (rateA - rateB) || (idB - idA);
                    } else if (currentSort === 'expensive') {
                        return (rateB - rateA) || (idB - idA);
                    } else {
                        // 'fair' default ranking (or id DESC tie-breaker)
                        return (ratingB - ratingA) || (idB - idA);
                    }
                });

                gigCards.forEach(card => gigsGrid.appendChild(card));
            }

            // 5. Update Empty State Message
            if (noGigsFound) {
                noGigsFound.style.display = matchCount === 0 ? 'block' : 'none';
            }
        }

        // 6. Synchronize URL State
        try {
            const url = new URL(window.location.href);
            if (currentCategory && currentCategory.toLowerCase() !== 'all') {
                url.searchParams.set('category', currentCategory);
            } else {
                url.searchParams.delete('category');
            }
            if (currentQuery) {
                url.searchParams.set('q', currentQuery);
            } else {
                url.searchParams.delete('q');
            }
            if (currentSort && currentSort !== 'fair') {
                url.searchParams.set('sort', currentSort);
            } else {
                url.searchParams.delete('sort');
            }
            window.history.replaceState({}, '', url.pathname + (url.search ? url.search : ''));
        } catch (err) {
            // Silently handled
        }
    }

    // Category Chip Click Handler
    categoryChips.forEach(chip => {
        chip.addEventListener('click', (e) => {
            if (e.metaKey || e.ctrlKey || e.shiftKey) return; // Allow opening in new tab
            e.preventDefault();
            const targetCat = chip.getAttribute('data-category') || 'All';

            // If the grid was rendered with only a specific category from server and target is missing:
            const allCards = gigsGrid ? gigsGrid.querySelectorAll('.gig-card') : [];
            const hasTargetCards = gigsGrid ? gigsGrid.querySelector(`.gig-card[data-category="${targetCat}"]`) : null;
            if (targetCat.toLowerCase() !== 'all' && !hasTargetCards && allCards.length < 5) {
                window.location.href = chip.getAttribute('href');
                return;
            }

            applyMarketplaceFilters({ category: targetCat });
        });
    });

    // Live Search Input Handler
    if (searchInput) {
        let debounceTimer;
        searchInput.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                applyMarketplaceFilters();
            }, 60);
        });
    }

    // Search Clear Button Handler
    if (searchClearBtn) {
        searchClearBtn.addEventListener('click', () => {
            if (searchInput) {
                searchInput.value = '';
                searchInput.focus();
            }
            applyMarketplaceFilters({ query: '' });
        });
    }

    // Sort Dropdown Change Handler
    if (sortSelect) {
        sortSelect.addEventListener('change', () => {
            applyMarketplaceFilters({ sort: sortSelect.value });
        });
    }

    // Filter Form Submit Handler
    if (filterForm) {
        filterForm.addEventListener('submit', (e) => {
            e.preventDefault();
            applyMarketplaceFilters();
        });
    }

    // Reset All Buttons
    if (resetAllBtn) {
        resetAllBtn.addEventListener('click', (e) => {
            e.preventDefault();
            applyMarketplaceFilters({ category: 'All', query: '', sort: 'fair' });
        });
    }
    if (clearAllBtn) {
        clearAllBtn.addEventListener('click', (e) => {
            e.preventDefault();
            applyMarketplaceFilters({ category: 'All', query: '', sort: 'fair' });
        });
    }

    // Initial Sort & Filter Application on Page Ready
    applyMarketplaceFilters();
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
