
let currentAuditStep = 1;
let currentAuditBooking = null;
let auditFoodItems = [];
let auditActivities = [];
let auditRoomInventory = [];
let availableMenuItems = [];
let availableExperiences = [];
let availableRoomsList = [];

function openCheckoutAuditModal(bookingInput) {
    currentAuditStep = 1;
    goToAuditStep(1);

    let bookingId = (typeof bookingInput === 'object' && bookingInput !== null) ? (bookingInput.id || 0) : parseInt(bookingInput, 10);
    let refCode = (typeof bookingInput === 'object' && bookingInput !== null) ? (bookingInput.reference_code || '') : '';

    if (bookingId > 0 || refCode) {
        fetch('api_checkout_audit.php?action=get_audit_data&booking_id=' + bookingId + '&ref=' + encodeURIComponent(refCode))
            .then(async res => {
                const text = await res.text();
                try {
                    return JSON.parse(text);
                } catch (e) {
                    throw new Error('Server error (' + res.status + '): ' + (text.substring(0, 200) || 'Invalid JSON response'));
                }
            })
            .then(data => {
                if (data.success && data.booking) {
                    availableMenuItems = data.available_menu_items || [];
                    availableExperiences = data.available_experiences || [];
                    availableRoomsList = data.available_rooms || [];
                    populateCheckoutAuditModal(data.booking, data.food_items || [], data.activities || [], data.room_inventory || []);
                    if (typeof openAdmModal === 'function') {
                        openAdmModal('modal-checkout-audit');
                    }
                    const m = document.getElementById('modal-checkout-audit');
                    if (m) {
                        m.style.removeProperty('display');
                        m.classList.add('is-open');
                        document.body.style.overflow = 'hidden';
                    }
                } else {
                    alert(data.error || 'Could not load stay audit data.');
                }
            })
            .catch(err => {
                console.error('Stay Audit API Error:', err);
                alert(err.message || 'Failed to connect to Stay Audit API.');
            });
    }
}

function closeCheckoutAuditModal() {
    if (typeof closeAdmModal === 'function') {
        closeAdmModal('modal-checkout-audit');
    }
    const m = document.getElementById('modal-checkout-audit');
    if (m) {
        m.classList.remove('is-open');
        m.style.removeProperty('display');
    }
    document.body.style.overflow = '';
}

document.addEventListener('DOMContentLoaded', function() {
    const m = document.getElementById('modal-checkout-audit');
    if (m) {
        m.addEventListener('click', function(e) {
            if (e.target === m) {
                closeCheckoutAuditModal();
            }
        });
    }
});

function populateCheckoutAuditModal(b, foodList, actList, invList) {
    currentAuditBooking = b;
    auditFoodItems = JSON.parse(JSON.stringify(foodList || []));
    auditActivities = JSON.parse(JSON.stringify(actList || []));
    auditRoomInventory = JSON.parse(JSON.stringify(invList || []));

    document.getElementById('audit-booking-id').value = b.id;
    document.getElementById('audit-ref-code').value = b.reference_code;
    document.getElementById('audit-booking-ref-badge').innerText = '#' + b.reference_code;
    document.getElementById('audit-guest-title').innerText = b.guest_name + ' — Stay Audit & Clearance';
    
    const villaTitle = b.room_title || ((b.villa_type === 'treehouse') ? 'Luxury Canopy Treehouse' : 'Traditional Earthen Mudhouse');
    document.getElementById('audit-villa-name').innerText = villaTitle;
    document.getElementById('audit-stay-dates').innerText = b.checkin_date + ' → ' + b.checkout_date + ' (' + (b.nights || 1) + ' Nights)';
    document.getElementById('audit-occupancy').innerText = (b.guests_count || 2) + ' Guests (' + (b.adults_count || 2) + ' Adults' + (b.kids_count > 0 ? ', ' + b.kids_count + ' Kids' : '') + ')';

    // Populate Available Rooms Dropdown
    const roomSelect = document.getElementById('audit-allocated-room-select');
    if (roomSelect && availableRoomsList.length > 0) {
        roomSelect.innerHTML = availableRoomsList.map(r => `
            <option value="${r.slug}" ${r.slug === b.villa_type ? 'selected' : ''}>
                ${r.title} (Base: ₹${Number(r.base_price).toLocaleString('en-IN')})
            </option>
        `).join('');
    }

    renderRoomInventoryList();
    renderAuditFoodList();
    renderAuditActivitiesList();
    updateAuditFinalCalculations();
}

function toggleRoomUpgradeSelect(show) {
    const wrap = document.getElementById('room-upgrade-selector-wrap');
    if (wrap) wrap.style.display = show ? 'block' : 'none';
}

function onAllocatedRoomChanged(newSlug) {
    if (!newSlug || !currentAuditBooking) return;
    const selectedRoom = availableRoomsList.find(r => r.slug === newSlug);
    if (selectedRoom) {
        document.getElementById('audit-villa-name').innerText = selectedRoom.title + ' (Upgraded / Transferred)';
        currentAuditBooking.room_title = selectedRoom.title;
        currentAuditBooking.villa_type = selectedRoom.slug;
    }
}

// 1. ROOM INVENTORY CHECKLIST
function renderRoomInventoryList() {
    const container = document.getElementById('audit-inventory-container');
    if (!container) return;

    let html = '';
    auditRoomInventory.forEach((item, idx) => {
        const isIntact = item.intact !== false;
        html += `
            <label class="audit-checkbox-row ${!isIntact ? 'is-missing' : ''}">
                <input type="checkbox" class="audit-inv-chk" ${isIntact ? 'checked' : ''} onchange="toggleInventoryItem(${idx}, this.checked)">
                <div class="chk-content">
                    <strong>${item.item}</strong>
                    <span>${item.notes || 'Standard Item'} • Qty: ${item.qty || 1}</span>
                </div>
            </label>
        `;
    });

    container.innerHTML = html;
    checkMissingInventoryStatus();
}

function toggleInventoryItem(idx, isChecked) {
    if (auditRoomInventory[idx]) {
        auditRoomInventory[idx].intact = isChecked;
    }
    renderRoomInventoryList();
}

function checkAllRoomInventory(val) {
    auditRoomInventory.forEach(it => it.intact = val);
    renderRoomInventoryList();
}

function checkMissingInventoryStatus() {
    const missing = auditRoomInventory.filter(it => it.intact === false);
    const penaltyBox = document.getElementById('audit-damage-penalty-box');
    if (penaltyBox) {
        if (missing.length > 0) {
            penaltyBox.style.display = 'block';
            const missingNames = missing.map(m => m.item).join(', ');
            document.getElementById('audit-damage-notes').value = 'Missing/Damaged: ' + missingNames;
        } else {
            penaltyBox.style.display = 'none';
            document.getElementById('audit-damage-notes').value = '';
            document.getElementById('audit-damage-penalty-fee').value = '';
        }
    }
}

// 2. GASTRONOMY & DISH SUBSTITUTIONS
function renderAuditFoodList() {
    const container = document.getElementById('audit-food-list-container');
    if (!container) return;

    if (auditFoodItems.length === 0) {
        container.innerHTML = `
            <div style="background: var(--adm-bg-main); border: 1px dashed var(--adm-border-subtle); border-radius: 8px; padding: 14px; text-align: center; color: var(--adm-text-muted); font-size: 13px;">
                <i class="fa-solid fa-seedling" style="color: var(--adm-gold); margin-right: 6px;"></i>
                No pre-booked food dishes on initial reservation. Daily meals were ordered à la carte on arrival.
            </div>
        `;
        return;
    }

    let html = '';
    auditFoodItems.forEach((item, idx) => {
        const isBfast = (item.category || '').toLowerCase() === 'breakfast';
        const price = parseFloat(item.price || 0);
        const qty = parseInt(item.quantity || 1, 10);
        const isServed = item.served !== false;
        const isSubstituted = Boolean(item.is_substituted);
        const subtotal = isServed ? (price * qty) : 0;

        const catIcon = isBfast ? 'fa-mug-saucer' : (item.category === 'lunch' ? 'fa-bowl-rice' : (item.category === 'snacks' ? 'fa-cookie-bite' : 'fa-fire-burner'));

        html += `
            <div class="audit-dish-card font-sans ${isSubstituted ? 'is-substituted' : ''} ${!isServed ? 'is-cancelled' : ''}">
                <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap;">
                    <div style="display: flex; align-items: center; gap: 10px; flex: 1; min-width: 220px;">
                        <input type="checkbox" ${isServed ? 'checked' : ''} onchange="toggleAuditFoodServed(${idx}, this.checked)" style="accent-color: var(--adm-gold); width: 16px; height: 16px;" title="Delivered & Served">
                        <div>
                            <strong style="color: var(--adm-text-primary); font-size: 13.5px; display: block;">
                                <i class="fa-solid ${catIcon}" style="color: var(--adm-gold); margin-right: 4px; font-size: 11px;"></i>
                                ${item.heading}
                                ${isSubstituted ? '<span style="font-size: 10px; background: rgba(197,160,89,0.25); color: var(--adm-gold); padding: 1px 6px; border-radius: 4px; margin-left: 6px; text-transform: uppercase;">Substituted</span>' : ''}
                                ${!isServed ? '<span style="font-size: 10px; background: rgba(239,68,68,0.2); color: #F87171; padding: 1px 6px; border-radius: 4px; margin-left: 6px; text-transform: uppercase;">Not Served / Cancelled</span>' : ''}
                            </strong>
                            <span style="font-size: 11px; color: var(--adm-text-muted); text-transform: uppercase;">
                                ${item.category} • ${qty} Set${qty > 1 ? 's' : ''} ${isSubstituted && item.original_dish ? ' • (Originally: ' + item.original_dish + ')' : ''}
                            </span>
                        </div>
                    </div>

                    <div style="text-align: right; flex-shrink: 0; display: flex; align-items: center; gap: 10px;">
                        <div>
                            <span style="font-weight: 700; color: ${isBfast ? '#22c55e' : (!isServed ? '#94A3B8' : 'var(--adm-gold-light)')}; font-size: 13.5px; display: block;">
                                ${!isServed ? 'Cancelled (₹0)' : (isBfast || price === 0 ? 'Included (₹0)' : ('₹' + subtotal.toLocaleString('en-IN')))}
                            </span>
                            ${price > 0 && isServed ? `<small style="font-size: 10px; color: var(--adm-text-muted);">@ ₹${price}/set</small>` : ''}
                        </div>

                        <!-- Substitute Button -->
                        <button type="button" class="adm-btn-action" onclick="toggleSubstituteDrawer(${idx});" style="background: rgba(197, 160, 89, 0.15); color: var(--adm-gold); border: 1px solid rgba(197, 160, 89, 0.3); padding: 4px 8px; font-size: 11px;" title="Substitute with another dish">
                            <i class="fa-solid fa-arrow-right-arrow-left"></i> Substitute
                        </button>

                        <button type="button" class="adm-btn-action danger" onclick="removeAuditFoodItem(${idx})" style="padding: 4px 7px; font-size: 11px;" title="Remove Dish">
                            <i class="fa-solid fa-trash-can"></i>
                        </button>
                    </div>
                </div>

                <!-- Inline Substitution Selector Drawer -->
                <div id="substitute-drawer-${idx}" style="display: none; background: rgba(0,0,0,0.25); border: 1px dashed rgba(197, 160, 89, 0.4); border-radius: 6px; padding: 10px; margin-top: 6px;">
                    <div style="font-size: 11.5px; font-weight: 700; color: var(--adm-gold); margin-bottom: 6px;">
                        <i class="fa-solid fa-utensils"></i> Substitute "${item.heading}" with actual served dish:
                    </div>
                    <div style="display: grid; grid-template-columns: 2fr 100px auto; gap: 8px; align-items: center;">
                        <select id="sub-select-${idx}" class="adm-input" style="font-size: 12px; padding: 6px 8px;" onchange="onSubstituteSelectChanged(${idx}, this.value)">
                            <option value="">-- Choose Replacement Dish from Menu --</option>
                            ${availableMenuItems.map(d => `<option value="${d.id}" data-name="${d.name}" data-price="${d.price}" data-cat="${d.category}">[${d.category.toUpperCase()}] ${d.name} (₹${d.price})</option>`).join('')}
                        </select>
                        <input type="number" id="sub-custom-price-${idx}" class="adm-input" placeholder="Price (₹)" value="${price}" min="0" step="50" style="font-size: 12px; padding: 6px 8px;">
                        <button type="button" class="adm-btn-action gold" onclick="applyDishSubstitution(${idx});" style="font-size: 11.5px; padding: 6px 12px;">
                            <i class="fa-solid fa-check"></i> Apply Swap
                        </button>
                    </div>
                </div>
            </div>
        `;
    });

    container.innerHTML = html;
    updateAuditFinalCalculations();
}

function toggleAuditFoodServed(idx, isChecked) {
    if (auditFoodItems[idx]) {
        auditFoodItems[idx].served = isChecked;
    }
    renderAuditFoodList();
}

function checkAllAuditFood(val) {
    auditFoodItems.forEach(fi => fi.served = val);
    renderAuditFoodList();
}

function removeAuditFoodItem(idx) {
    auditFoodItems.splice(idx, 1);
    renderAuditFoodList();
}

function toggleSubstituteDrawer(idx) {
    const d = document.getElementById('substitute-drawer-' + idx);
    if (d) {
        d.style.display = (d.style.display === 'none' || d.style.display === '') ? 'block' : 'none';
    }
}

function onSubstituteSelectChanged(idx, dishId) {
    if (!dishId) return;
    const selected = availableMenuItems.find(d => String(d.id) === String(dishId));
    if (selected) {
        const priceInput = document.getElementById('sub-custom-price-' + idx);
        if (priceInput) priceInput.value = selected.price;
    }
}

function applyDishSubstitution(idx) {
    const sel = document.getElementById('sub-select-' + idx);
    const customPriceInput = document.getElementById('sub-custom-price-' + idx);
    const dishId = sel.value;
    const item = auditFoodItems[idx];

    if (!dishId) {
        alert('Please select a replacement dish from the menu.');
        return;
    }

    const selectedDish = availableMenuItems.find(d => String(d.id) === String(dishId));
    if (!selectedDish) return;

    const newPrice = parseFloat(customPriceInput.value || selectedDish.price || "0");
    const originalName = item.is_substituted ? item.original_dish : item.heading;

    item.original_dish = originalName;
    item.heading = selectedDish.name;
    item.category = selectedDish.category;
    item.price = newPrice;
    item.subtotal = item.quantity * newPrice;
    item.is_substituted = true;
    item.served = true;

    renderAuditFoodList();
}

function addCustomAuditFoodItem() {
    const heading = document.getElementById('extra-food-heading').value.trim();
    const cat = document.getElementById('extra-food-category').value;
    const qty = parseInt(document.getElementById('extra-food-qty').value || "1", 10);
    const price = parseFloat(document.getElementById('extra-food-price').value || "0");

    if (!heading) {
        alert('Please enter dish name.');
        return;
    }

    auditFoodItems.push({
        heading: heading,
        category: cat,
        quantity: qty,
        price: price,
        subtotal: qty * price,
        served: true,
        is_substituted: false
    });

    document.getElementById('extra-food-heading').value = '';
    document.getElementById('extra-food-price').value = '';
    renderAuditFoodList();
}

// 3. EXPERIENCES AUDIT
function renderAuditActivitiesList() {
    const container = document.getElementById('audit-activities-list-container');
    if (!container) return;

    if (auditActivities.length === 0) {
        container.innerHTML = `
            <div style="background: var(--adm-bg-main); border: 1px dashed var(--adm-border-subtle); border-radius: 8px; padding: 14px; text-align: center; color: var(--adm-text-muted); font-size: 13px;">
                <i class="fa-solid fa-compass" style="color: var(--adm-gold); margin-right: 6px;"></i>
                No add-on signature experiences requested on initial booking.
            </div>
        `;
        return;
    }

    let html = '';
    auditActivities.forEach((item, idx) => {
        const isDone = item.completed !== false;
        const price = parseFloat(item.price || 0);

        html += `
            <div class="audit-dish-card font-sans ${!isDone ? 'is-cancelled' : ''}">
                <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap;">
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; flex: 1; min-width: 220px;">
                        <input type="checkbox" ${isDone ? 'checked' : ''} onchange="toggleAuditActivityCompleted(${idx}, this.checked)" style="accent-color: #0E7490; width: 16px; height: 16px;">
                        <div>
                            <strong style="color: var(--adm-text-primary); font-size: 13.5px; display: block;">
                                <i class="fa-solid fa-sparkles" style="color: #0E7490; margin-right: 4px; font-size: 11px;"></i>
                                ${item.title}
                                ${!isDone ? '<span style="font-size: 10px; background: rgba(239,68,68,0.2); color: #F87171; padding: 1px 6px; border-radius: 4px; margin-left: 6px; text-transform: uppercase;">Not Provided / Cancelled</span>' : ''}
                            </strong>
                            <span style="font-size: 11px; color: var(--adm-text-muted);">
                                ${item.timing || 'Curated Schedule'} • Guided by Naturalist
                            </span>
                        </div>
                    </label>
                    <div style="text-align: right; flex-shrink: 0; display: flex; align-items: center; gap: 8px;">
                        <span style="font-weight: 700; color: ${!isDone ? '#94A3B8' : '#38BDF8'}; font-size: 13px;">
                            ${!isDone ? 'Cancelled (₹0)' : (price > 0 ? ('₹' + price.toLocaleString('en-IN')) : 'Included (₹0)')}
                        </span>
                        <button type="button" class="adm-btn-action danger" onclick="removeAuditActivity(${idx})" style="padding: 3px 6px; font-size: 11px;" title="Remove Activity">
                            <i class="fa-solid fa-trash-can"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
    });

    container.innerHTML = html;
    updateAuditFinalCalculations();
}

function toggleAuditActivityCompleted(idx, isChecked) {
    if (auditActivities[idx]) {
        auditActivities[idx].completed = isChecked;
    }
    renderAuditActivitiesList();
}

function checkAllAuditActivities(val) {
    auditActivities.forEach(ac => ac.completed = val);
    renderAuditActivitiesList();
}

function removeAuditActivity(idx) {
    auditActivities.splice(idx, 1);
    renderAuditActivitiesList();
}

function addCustomAuditActivity() {
    const title = document.getElementById('extra-act-title').value.trim();
    const timing = document.getElementById('extra-act-timing').value.trim() || 'Curated Timing';
    const price = parseFloat(document.getElementById('extra-act-price').value || "0");

    if (!title) {
        alert('Please enter activity title.');
        return;
    }

    auditActivities.push({
        title: title,
        timing: timing,
        quantity: 1,
        price: price,
        subtotal: price,
        completed: true
    });

    document.getElementById('extra-act-title').value = '';
    document.getElementById('extra-act-timing').value = '';
    document.getElementById('extra-act-price').value = '';
    renderAuditActivitiesList();
}

// 4. STEP 4 LIVE CALCULATIONS
function updateAuditFinalCalculations() {
    if (!currentAuditBooking) return;

    let roomAmt = parseFloat(currentAuditBooking.room_amount || 0);
    if (roomAmt <= 0) {
        roomAmt = parseFloat(currentAuditBooking.total_amount || 0) - parseFloat(currentAuditBooking.food_amount || 0);
    }

    let foodTotal = 0;
    auditFoodItems.forEach(fi => {
        if (fi.served !== false) {
            foodTotal += (parseFloat(fi.price || 0) * parseInt(fi.quantity || 1, 10));
        }
    });

    let actTotal = 0;
    auditActivities.forEach(ac => {
        if (ac.completed !== false) {
            actTotal += parseFloat(ac.price || 0);
        }
    });

    const damageFee = parseFloat(document.getElementById('audit-damage-penalty-fee')?.value || "0");
    const damageRow = document.getElementById('audit-summary-damage-row');
    if (damageRow) {
        if (damageFee > 0) {
            damageRow.style.display = 'flex';
            document.getElementById('audit-summary-damage-rate').innerText = '₹' + damageFee.toLocaleString('en-IN');
        } else {
            damageRow.style.display = 'none';
        }
    }

    const netTotal = roomAmt + foodTotal + actTotal + damageFee;

    document.getElementById('audit-summary-room-rate').innerText = '₹' + roomAmt.toLocaleString('en-IN');
    document.getElementById('audit-summary-food-rate').innerText = '₹' + foodTotal.toLocaleString('en-IN');
    document.getElementById('audit-summary-act-rate').innerText = '₹' + actTotal.toLocaleString('en-IN');
    document.getElementById('audit-summary-total-rate').innerText = '₹' + netTotal.toLocaleString('en-IN');
}

function goToAuditStep(stepNum) {
    if (stepNum < 1) stepNum = 1;
    if (stepNum > 4) stepNum = 4;
    currentAuditStep = stepNum;

    for (let i = 1; i <= 4; i++) {
        const p = document.getElementById('audit-pane-' + i);
        const t = document.getElementById('audit-tab-' + i);
        if (p) p.style.display = (i === stepNum) ? 'block' : 'none';
        if (t) {
            t.classList.toggle('active', i === stepNum);
            t.classList.toggle('completed', i < stepNum);
        }
    }

    const prevBtn = document.getElementById('btn-audit-prev');
    const nextBtn = document.getElementById('btn-audit-next');
    const stepInd = document.getElementById('audit-step-indicator');

    if (prevBtn) prevBtn.style.visibility = (stepNum > 1) ? 'visible' : 'hidden';

    if (stepNum === 1) {
        if (stepInd) stepInd.innerText = 'Step 1 of 4: Room & Asset Checklist';
        if (nextBtn) {
            nextBtn.innerHTML = '<span>Next: Verify Food</span> <i class="fa-solid fa-arrow-right"></i>';
            nextBtn.style.display = 'inline-flex';
        }
    } else if (stepNum === 2) {
        if (stepInd) stepInd.innerText = 'Step 2 of 4: Food & Dish Substitutions';
        if (nextBtn) {
            nextBtn.innerHTML = '<span>Next: Verify Experiences</span> <i class="fa-solid fa-arrow-right"></i>';
            nextBtn.style.display = 'inline-flex';
        }
    } else if (stepNum === 3) {
        if (stepInd) stepInd.innerText = 'Step 3 of 4: Signature Experiences';
        if (nextBtn) {
            nextBtn.innerHTML = '<span>Next: Final Summary</span> <i class="fa-solid fa-arrow-right"></i>';
            nextBtn.style.display = 'inline-flex';
        }
    } else if (stepNum === 4) {
        if (stepInd) stepInd.innerText = 'Step 4 of 4: Finalize & Generate Invoice';
        if (nextBtn) nextBtn.style.display = 'none';
        updateAuditFinalCalculations();
    }
}

document.querySelectorAll('.audit-step-tab').forEach(tab => {
    tab.addEventListener('click', function() {
        const s = parseInt(this.getAttribute('data-step'), 10);
        goToAuditStep(s);
    });
});

function submitStayAuditAndRedirect(destination) {
    const bookingId = document.getElementById('audit-booking-id').value;
    const markCheckout = document.getElementById('chk-mark-status-completed')?.checked || false;
    const roomNotes = document.getElementById('audit-room-notes')?.value || '';
    const damageFee = parseFloat(document.getElementById('audit-damage-penalty-fee')?.value || "0");
    const damageNotes = document.getElementById('audit-damage-notes')?.value || '';

    const payload = {
        action: 'save_audit_and_checkout',
        booking_id: bookingId,
        mark_checkout: markCheckout,
        room_notes: roomNotes,
        missing_damage_penalty: damageFee,
        missing_damage_notes: damageNotes,
        food_items: auditFoodItems,
        activities: auditActivities,
        room_inventory: auditRoomInventory
    };

    fetch('api_checkout_audit.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(async res => {
        const text = await res.text();
        try {
            return JSON.parse(text);
        } catch (e) {
            throw new Error('Server error (' + res.status + '): ' + (text.substring(0, 200) || 'Invalid server response'));
        }
    })
    .then(data => {
        if (data.success) {
            closeCheckoutAuditModal();
            if (destination === 'billing') {
                window.location.href = data.billing_url;
            } else if (destination === 'print') {
                window.open(data.print_bill_url, '_blank');
                window.location.reload();
            } else {
                window.location.reload();
            }
        } else {
            alert(data.error || 'Failed to save stay audit.');
        }
    })
    .catch(err => {
        console.error('Stay Audit Save Error:', err);
        alert(err.message || 'Network error while saving stay audit.');
    });
}
