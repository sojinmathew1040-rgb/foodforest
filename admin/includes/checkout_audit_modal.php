<!-- =========================================================================
     Food Forest Sanctuary — Concierge Check-Out & Stay Audit Wizard Modal
     Step-by-step verification of Room Inventory Assets, Food Substitution & Experiences
     ========================================================================= -->
<div class="adm-modal-backdrop" id="modal-checkout-audit" style="z-index: 100050;">
    <div class="adm-modal-content" style="max-width: 840px; max-height: 94vh; display: flex; flex-direction: column;">
        
        <!-- Modal Header -->
        <div class="adm-modal-header" style="border-bottom: var(--adm-border-subtle); padding: 18px 24px; flex-shrink: 0;">
            <div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; background: rgba(197, 160, 89, 0.18); color: var(--adm-gold); padding: 2px 8px; border-radius: 4px; letter-spacing: 0.5px;">
                        Stay Audit & Check-Out
                    </span>
                    <span id="audit-booking-ref-badge" style="font-family: monospace; font-weight: 700; font-size: 13px; color: var(--adm-text-secondary);">
                        #REF
                    </span>
                </div>
                <h3 class="adm-modal-title font-serif" id="audit-guest-title" style="margin-top: 4px; font-size: 20px; color: var(--adm-text-primary);">
                    Guest Stay Verification &amp; Inventory Clearance
                </h3>
            </div>
            <button type="button" class="adm-modal-close" onclick="closeCheckoutAuditModal();" data-close-modal="modal-checkout-audit" style="cursor: pointer;">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <!-- Stepper Navigation Header -->
        <div class="audit-stepper-bar font-sans" style="display: flex; background: var(--adm-bg-main); border-bottom: var(--adm-border-subtle); padding: 10px 24px; gap: 8px; flex-shrink: 0; overflow-x: auto;">
            <button type="button" class="audit-step-tab active" data-step="1" id="audit-tab-1">
                <span class="step-num">1</span>
                <span>🏡 Room &amp; Asset Checklist</span>
            </button>
            <button type="button" class="audit-step-tab" data-step="2" id="audit-tab-2">
                <span class="step-num">2</span>
                <span>🍲 Food &amp; Dish Substitutions</span>
            </button>
            <button type="button" class="audit-step-tab" data-step="3" id="audit-tab-3">
                <span class="step-num">3</span>
                <span>✨ Experiences</span>
            </button>
            <button type="button" class="audit-step-tab" data-step="4" id="audit-tab-4">
                <span class="step-num">4</span>
                <span>🧾 Finalize Bill</span>
            </button>
        </div>

        <!-- Modal Scrollable Body -->
        <div class="adm-modal-body" style="padding: 24px; overflow-y: auto; flex: 1;">
            
            <!-- Hidden inputs -->
            <input type="hidden" id="audit-booking-id" value="">
            <input type="hidden" id="audit-ref-code" value="">

            <!-- STEP 1: Room & Physical Asset Inventory Checklist -->
            <div class="audit-pane active" id="audit-pane-1">
                <div style="background: var(--adm-bg-card); border: var(--adm-border-subtle); border-radius: 10px; padding: 16px; margin-bottom: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 14px; border-bottom: var(--adm-border-subtle); padding-bottom: 12px; flex-wrap: wrap; gap: 10px;">
                        <div>
                            <span style="font-size: 11px; text-transform: uppercase; color: var(--adm-text-muted);">Allocated Sanctuary Villa</span>
                            <div style="font-size: 17px; font-weight: 700; color: var(--adm-gold-light);" id="audit-villa-name">Luxury Canopy Treehouse</div>
                            <div style="font-size: 12px; color: var(--adm-text-secondary); margin-top: 2px;" id="audit-stay-dates">12 Oct 2026 → 14 Oct 2026 (2 Nights)</div>
                        </div>
                        <div style="text-align: right;">
                            <span style="font-size: 11px; text-transform: uppercase; color: var(--adm-text-muted);">Occupancy</span>
                            <div style="font-size: 13.5px; font-weight: 600; color: var(--adm-text-primary);" id="audit-occupancy">2 Adults</div>
                        </div>
                    </div>

                    <!-- Room Assignment Verification Check -->
                    <div style="background: rgba(197, 160, 89, 0.08); border: 1px solid rgba(197, 160, 89, 0.25); border-radius: 8px; padding: 12px; margin-bottom: 16px;">
                        <div style="font-size: 12.5px; font-weight: 700; color: var(--adm-gold-light); margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                            <i class="fa-solid fa-hotel"></i> Check-in Room Allocation Check
                        </div>
                        <div style="font-size: 12px; color: var(--adm-text-secondary); margin-bottom: 8px;">
                            Confirm whether the guest stayed in the booked chalet or if a room upgrade/change was provided upon check-in:
                        </div>
                        <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                            <label style="display: inline-flex; align-items: center; gap: 6px; font-size: 12.5px; color: var(--adm-text-primary); cursor: pointer;">
                                <input type="radio" name="room_allocation_status" value="original" checked onchange="toggleRoomUpgradeSelect(false)" style="accent-color: var(--adm-gold);">
                                <span>Stayed in Booked Villa</span>
                            </label>
                            <label style="display: inline-flex; align-items: center; gap: 6px; font-size: 12.5px; color: var(--adm-gold-light); cursor: pointer;">
                                <input type="radio" name="room_allocation_status" value="upgraded" onchange="toggleRoomUpgradeSelect(true)" style="accent-color: var(--adm-gold);">
                                <span>Upgraded / Moved to Alternative Villa</span>
                            </label>
                        </div>
                        <div id="room-upgrade-selector-wrap" style="display: none; margin-top: 10px;">
                            <label class="adm-label" style="font-size: 11.5px;">Select Actual Villa Stayed In:</label>
                            <select id="audit-allocated-room-select" class="adm-input" style="font-size: 12.5px; padding: 6px 10px;" onchange="onAllocatedRoomChanged(this.value)">
                                <!-- Populated dynamically from available rooms -->
                            </select>
                        </div>
                    </div>

                    <!-- Room Asset Inventory Checklist Header -->
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                        <div style="font-size: 13.5px; font-weight: 700; color: var(--adm-text-primary);">
                            <i class="fa-solid fa-boxes-stacked" style="color: var(--adm-gold); margin-right: 6px;"></i> 
                            Villa Assets &amp; Physical Inventory Inspection:
                        </div>
                        <button type="button" class="adm-btn-action outline" onclick="checkAllRoomInventory(true);" style="font-size: 11px; padding: 4px 10px;">
                            <i class="fa-solid fa-check-double"></i> Mark All Intact
                        </button>
                    </div>

                    <div style="font-size: 12px; color: var(--adm-text-muted); margin-bottom: 12px;">
                        Inspect the chalet before guest departure. Uncheck any missing or damaged property to record replacement penalties.
                    </div>

                    <!-- Dynamic Room Inventory Container -->
                    <div id="audit-inventory-container" style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 16px;">
                        <!-- Dynamically populated from room's inventory checklist -->
                    </div>

                    <!-- Missing & Damaged Asset Assessment: Individual Item Breakdown -->
                    <div id="audit-damage-penalty-box" style="display: none; background: rgba(239, 68, 68, 0.06); border: 1px dashed rgba(239, 68, 68, 0.4); border-radius: 10px; padding: 16px; margin-top: 14px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
                            <div>
                                <div style="display: flex; align-items: center; gap: 8px; color: #F87171; font-size: 13.5px; font-weight: 700;">
                                    <i class="fa-solid fa-triangle-exclamation"></i>
                                    <span>Missing / Damaged Room Asset Penalties (Per-Item Assessment)</span>
                                </div>
                                <div style="font-size: 11.5px; color: var(--adm-text-muted); margin-top: 2px;">
                                    Assign an individual replacement/repair fee for each missing item. Each will be billed as a separate line item on the invoice.
                                </div>
                            </div>
                            <button type="button" class="adm-btn-action" onclick="addCustomDamageItemRow();" style="background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.35); color: #F87171; font-size: 11.5px; padding: 5px 12px;">
                                <i class="fa-solid fa-plus"></i> Add Other Damage / Cleaning Fee
                            </button>
                        </div>

                        <!-- Itemized list container for each missing item -->
                        <div id="audit-missing-items-list" style="display: flex; flex-direction: column; gap: 8px; margin-bottom: 12px;">
                            <!-- Dynamically populated with individual cards per missing item -->
                        </div>

                        <!-- Summary footer with live total -->
                        <div style="display: flex; justify-content: flex-end; align-items: center; border-top: 1px dashed rgba(239, 68, 68, 0.25); padding-top: 10px; gap: 10px;">
                            <span style="font-size: 12.5px; color: var(--adm-text-secondary);">Total Asset Penalties:</span>
                            <strong id="audit-damage-total-display" style="color: #F87171; font-size: 16px; font-family: monospace;">₹0.00</strong>
                        </div>
                    </div>
                </div>

                <div class="adm-form-group" style="margin-bottom: 0;">
                    <label class="adm-label">Housekeeping &amp; Handover Notes (Optional)</label>
                    <input type="text" id="audit-room-notes" class="adm-input" placeholder="e.g. Villa in pristine condition. Key returned to reception desk." style="padding-left: 14px;">
                </div>
            </div>

            <!-- STEP 2: Curated Gastronomy Fulfillment & Dish Substitution -->
            <div class="audit-pane" id="audit-pane-2" style="display: none;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; flex-wrap: wrap; gap: 8px;">
                    <div>
                        <h4 class="font-serif" style="font-size: 17px; color: var(--adm-text-primary); margin-bottom: 2px;">
                            <i class="fa-solid fa-utensils" style="color: var(--adm-gold); margin-right: 6px;"></i> Dining Rituals &amp; Dish Substitutions
                        </h4>
                        <span style="font-size: 12px; color: var(--adm-text-muted);">Verify served dishes. If a booked dish was not available, substitute it with the alternative dish served.</span>
                    </div>
                    <div style="display: flex; gap: 6px;">
                        <button type="button" class="adm-btn-action outline" onclick="checkAllAuditFood(true);" style="font-size: 11.5px; padding: 5px 10px;">
                            <i class="fa-solid fa-check-double"></i> Mark All Provided
                        </button>
                    </div>
                </div>

                <!-- Food items container -->
                <div id="audit-food-list-container" style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 16px;">
                    <!-- Dynamically populated per-dish items -->
                </div>

                <!-- Add Meals to Folio: Dropdown from Restaurant Food Menu or Custom Entry -->
                <div style="background: var(--adm-bg-card); border: 1px solid var(--adm-border-subtle); border-radius: 10px; padding: 16px; margin-top: 12px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
                        <span style="font-size: 13px; font-weight: 700; color: var(--adm-gold-light); display: flex; align-items: center; gap: 6px;">
                            <i class="fa-solid fa-plus-circle"></i> Add Dining &amp; Meals to Guest Folio
                        </span>
                        <div style="display: flex; gap: 8px;">
                            <button type="button" id="btn-toggle-food-menu" class="adm-btn-action gold" onclick="switchFoodAddMode('menu');" style="padding: 4px 10px; font-size: 11.5px;">
                                <i class="fa-solid fa-book-open"></i> From Food Menu
                            </button>
                            <button type="button" id="btn-toggle-food-custom" class="adm-btn-action outline" onclick="switchFoodAddMode('custom');" style="padding: 4px 10px; font-size: 11.5px;">
                                <i class="fa-solid fa-pen-to-square"></i> Custom Off-Menu Item
                            </button>
                        </div>
                    </div>

                    <!-- Mode A: Pick from Restaurant Food Menu (Dropdown) -->
                    <div id="food-add-mode-menu" style="display: block;">
                        <div style="display: grid; grid-template-columns: 2fr 80px 100px auto; gap: 10px; align-items: center;">
                            <div>
                                <label style="font-size: 11px; text-transform: uppercase; color: var(--adm-text-muted); display: block; margin-bottom: 4px;">Choose Dish from Food Menu:</label>
                                <select id="extra-food-menu-picker" class="adm-input" style="padding: 8px 10px; font-size: 12.5px;" onchange="onFoodMenuPickerChanged(this.value);">
                                    <!-- Populated dynamically with optgroups by category -->
                                </select>
                            </div>
                            <div>
                                <label style="font-size: 11px; text-transform: uppercase; color: var(--adm-text-muted); display: block; margin-bottom: 4px;">Qty:</label>
                                <input type="number" id="menu-food-qty" class="adm-input" value="1" min="1" max="50" style="padding: 8px; font-size: 12.5px; text-align: center;">
                            </div>
                            <div>
                                <label style="font-size: 11px; text-transform: uppercase; color: var(--adm-text-muted); display: block; margin-bottom: 4px;">Rate / Set (₹):</label>
                                <div style="position: relative;">
                                    <span style="position: absolute; left: 8px; top: 50%; transform: translateY(-50%); color: var(--adm-gold); font-weight: 700; font-size: 12px;">₹</span>
                                    <input type="number" id="menu-food-price" class="adm-input" placeholder="0" min="0" step="10" style="padding-left: 20px; font-size: 12.5px;">
                                </div>
                            </div>
                            <div style="padding-top: 18px;">
                                <button type="button" class="adm-btn-action gold" onclick="addSelectedMenuDishItem();" style="padding: 8px 16px; font-size: 12.5px; font-weight: 700; height: 38px;">
                                    <i class="fa-solid fa-plus"></i> Add Dish
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Mode B: Enter Custom Off-Menu Meal / Beverage -->
                    <div id="food-add-mode-custom" style="display: none;">
                        <div style="display: grid; grid-template-columns: 1.5fr 1fr 80px 100px auto; gap: 8px; align-items: center;">
                            <div>
                                <label style="font-size: 11px; text-transform: uppercase; color: var(--adm-text-muted); display: block; margin-bottom: 4px;">Custom Dish Name:</label>
                                <input type="text" id="extra-food-heading" class="adm-input" placeholder="e.g. Traditional Herbal Soup" style="padding: 7px 10px; font-size: 12.5px;">
                            </div>
                            <div>
                                <label style="font-size: 11px; text-transform: uppercase; color: var(--adm-text-muted); display: block; margin-bottom: 4px;">Category:</label>
                                <select id="extra-food-category" class="adm-input" style="padding: 7px 8px; font-size: 12px;">
                                    <option value="lunch">Lunch</option>
                                    <option value="dinner">Dinner</option>
                                    <option value="snacks">Evening Snacks</option>
                                    <option value="breakfast">Breakfast</option>
                                </select>
                            </div>
                            <div>
                                <label style="font-size: 11px; text-transform: uppercase; color: var(--adm-text-muted); display: block; margin-bottom: 4px;">Qty:</label>
                                <input type="number" id="extra-food-qty" class="adm-input" value="1" min="1" max="50" style="padding: 7px 8px; font-size: 12.5px; text-align: center;">
                            </div>
                            <div>
                                <label style="font-size: 11px; text-transform: uppercase; color: var(--adm-text-muted); display: block; margin-bottom: 4px;">Price (₹):</label>
                                <input type="number" id="extra-food-price" class="adm-input" placeholder="Price" min="0" step="50" style="padding: 7px 8px; font-size: 12.5px;">
                            </div>
                            <div style="padding-top: 18px;">
                                <button type="button" class="adm-btn-action gold" onclick="addCustomAuditFoodItem();" style="padding: 8px 14px; font-size: 12.5px; height: 38px;">
                                    <i class="fa-solid fa-plus"></i> Add
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- STEP 3: Signature Experiences & Activities Verification -->
            <div class="audit-pane" id="audit-pane-3" style="display: none;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; flex-wrap: wrap; gap: 8px;">
                    <div>
                        <h4 class="font-serif" style="font-size: 17px; color: var(--adm-text-primary); margin-bottom: 2px;">
                            <i class="fa-solid fa-sparkles" style="color: var(--adm-gold); margin-right: 6px;"></i> Experiences &amp; Activities Audit
                        </h4>
                        <span style="font-size: 12px; color: var(--adm-text-muted);">Confirm whether guided wilderness treks and workshops were completed, cancelled, or substituted.</span>
                    </div>
                    <button type="button" class="adm-btn-action outline" onclick="checkAllAuditActivities(true);" style="font-size: 11.5px; padding: 5px 10px;">
                        <i class="fa-solid fa-check-double"></i> Mark All Completed
                    </button>
                </div>

                <!-- Activities container -->
                <div id="audit-activities-list-container" style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 16px;">
                    <!-- Dynamically populated activities -->
                </div>

                <!-- Fast Add Extra Experience -->
                <div style="background: var(--adm-bg-card); border: 1px dashed var(--adm-border-subtle); border-radius: 8px; padding: 12px 14px; margin-top: 10px;">
                    <span style="font-size: 12px; font-weight: 700; color: #0E7490; display: block; margin-bottom: 8px;">
                        <i class="fa-solid fa-person-hiking"></i> Add Extra On-Site Experience / Guide Session
                    </span>
                    <div style="display: grid; grid-template-columns: 2fr 1.2fr 100px auto; gap: 8px; align-items: center;">
                        <input type="text" id="extra-act-title" class="adm-input" placeholder="Activity (e.g. Sunrise Peak Trek)" style="padding: 6px 10px; font-size: 12.5px;">
                        <input type="text" id="extra-act-timing" class="adm-input" placeholder="Timing (e.g. 06:00 AM Sunrise)" style="padding: 6px 8px; font-size: 12px;">
                        <input type="number" id="extra-act-price" class="adm-input" placeholder="Rate (₹)" min="0" step="100" style="padding: 6px 8px; font-size: 12.5px;">
                        <button type="button" class="adm-btn-action cyan" onclick="addCustomAuditActivity();" style="padding: 7px 12px; font-size: 12px; background: rgba(14, 116, 144, 0.2); border: 1px solid #0E7490; color: #38BDF8;">
                            <i class="fa-solid fa-plus"></i> Add
                        </button>
                    </div>
                </div>
            </div>

            <!-- STEP 4: Final Summary & Billing Transition -->
            <div class="audit-pane" id="audit-pane-4" style="display: none;">
                <div style="background: var(--adm-bg-card); border: var(--adm-border-subtle); border-radius: 10px; padding: 20px; margin-bottom: 20px;">
                    <div style="text-align: center; margin-bottom: 18px;">
                        <div style="width: 48px; height: 48px; border-radius: 50%; background: rgba(34, 197, 94, 0.15); border: 1.5px solid #22c55e; color: #22c55e; display: flex; align-items: center; justify-content: center; margin: 0 auto 10px; font-size: 20px;">
                            <i class="fa-solid fa-circle-check"></i>
                        </div>
                        <h4 class="font-serif" style="font-size: 19px; color: var(--adm-text-primary); margin-bottom: 4px;">Stay Verification &amp; Audit Complete</h4>
                        <span style="font-size: 12.5px; color: var(--adm-text-muted);">All room assets, served meals, dish substitutions, and experiences are reconciled.</span>
                    </div>

                    <!-- Live Breakdown Table -->
                    <div style="display: flex; flex-direction: column; gap: 10px; font-size: 13px; border-top: var(--adm-border-subtle); padding-top: 14px; border-bottom: var(--adm-border-subtle); padding-bottom: 14px; margin-bottom: 14px;">
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: var(--adm-text-secondary);"><i class="fa-solid fa-house-chimney" style="color: #22c55e; margin-right: 6px;"></i> Villa Stay Tariff:</span>
                            <strong id="audit-summary-room-rate" style="color: var(--adm-text-primary);">₹0</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: var(--adm-text-secondary);"><i class="fa-solid fa-utensils" style="color: var(--adm-gold); margin-right: 6px;"></i> Gastronomy &amp; Substitutions:</span>
                            <strong id="audit-summary-food-rate" style="color: var(--adm-gold-light);">₹0</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: var(--adm-text-secondary);"><i class="fa-solid fa-sparkles" style="color: #38BDF8; margin-right: 6px;"></i> Experiences &amp; Activities:</span>
                            <strong id="audit-summary-act-rate" style="color: #38BDF8;">₹0</strong>
                        </div>
                        <div id="audit-summary-damage-row" style="display: none; flex-direction: column; gap: 4px;">
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: #F87171;"><i class="fa-solid fa-triangle-exclamation" style="color: #F87171; margin-right: 6px;"></i> Missing / Damaged Asset Penalties:</span>
                                <strong id="audit-summary-damage-rate" style="color: #F87171;">₹0</strong>
                            </div>
                            <div id="audit-summary-damage-breakdown" style="font-size: 11px; color: #FCA5A5; font-style: italic; padding-left: 20px;"></div>
                        </div>
                        <div style="display: flex; justify-content: space-between; border-top: 1px dashed var(--adm-border-subtle); padding-top: 8px; font-size: 15px;">
                            <span style="font-weight: 700; color: #FFFFFF;">Total Reconciled Bill Amount:</span>
                            <strong id="audit-summary-total-rate" style="font-family: var(--adm-font-serif); color: var(--adm-gold); font-size: 18px;">₹0</strong>
                        </div>
                    </div>

                    <label class="audit-checkbox-row" style="background: rgba(168, 85, 247, 0.08); border-color: rgba(168, 85, 247, 0.3);">
                        <input type="checkbox" id="chk-mark-status-completed" checked class="audit-chk" style="accent-color: #c084fc;">
                        <div class="chk-content">
                            <strong style="color: #c084fc;">Mark Reservation as "Checked-Out / Completed"</strong>
                            <span>Updates guest stay status to Departed and records actual checkout timestamp (Now).</span>
                        </div>
                    </label>
                </div>

                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <button type="button" class="adm-btn-action gold" onclick="submitStayAuditAndRedirect('billing');" style="width: 100%; justify-content: center; padding: 13px; font-size: 14px; font-weight: 700;">
                        <i class="fa-solid fa-receipt"></i>
                        <span>Save Audit &amp; Open Billing Folio (Generate Invoice)</span>
                    </button>
                    
                    <button type="button" class="adm-btn-action outline" onclick="submitStayAuditAndRedirect('print');" style="width: 100%; justify-content: center; padding: 11px; font-size: 13.5px;">
                        <i class="fa-solid fa-print"></i>
                        <span>Save Audit &amp; View / Print Official Tax Bill</span>
                    </button>

                    <button type="button" class="adm-btn-action emerald" onclick="submitStayAuditAndRedirect('close');" style="width: 100%; justify-content: center; padding: 11px; font-size: 13.5px;">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>Save Audit &amp; Finish Check-Out</span>
                    </button>
                </div>
            </div>

        </div>

        <!-- Modal Footer Navigation -->
        <div class="adm-modal-footer" style="border-top: var(--adm-border-subtle); padding: 14px 24px; display: flex; justify-content: space-between; align-items: center; flex-shrink: 0;">
            <button type="button" class="adm-btn-action outline" id="btn-audit-prev" onclick="goToAuditStep(currentAuditStep - 1);" style="visibility: hidden;">
                <i class="fa-solid fa-arrow-left"></i> Previous Step
            </button>
            <div style="font-size: 12px; color: var(--adm-text-muted);" id="audit-step-indicator">
                Step 1 of 4: Room &amp; Asset Checklist
            </div>
            <button type="button" class="adm-btn-action gold" id="btn-audit-next" onclick="goToAuditStep(currentAuditStep + 1);">
                <span>Next: Verify Food</span> <i class="fa-solid fa-arrow-right"></i>
            </button>
        </div>

    </div>
</div>

<style>
.audit-stepper-bar {
    scrollbar-width: none;
}
.audit-stepper-bar::-webkit-scrollbar {
    display: none;
}
.audit-step-tab {
    background: transparent;
    border: 1px solid var(--adm-border-subtle);
    color: var(--adm-text-secondary);
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 12px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    white-space: nowrap;
    transition: all 0.2s ease;
}
.audit-step-tab .step-num {
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.1);
    color: inherit;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    font-weight: 700;
}
.audit-step-tab.active {
    background: rgba(197, 160, 89, 0.15);
    border-color: var(--adm-gold);
    color: var(--adm-gold-light);
    font-weight: 700;
}
.audit-step-tab.active .step-num {
    background: var(--adm-gold);
    color: #101F15;
}
.audit-step-tab.completed {
    border-color: #22c55e;
    color: #22c55e;
}
.audit-checkbox-row {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    background: var(--adm-bg-main);
    border: var(--adm-border-subtle);
    border-radius: 8px;
    padding: 10px 12px;
    cursor: pointer;
    transition: all 0.2s ease;
}
.audit-checkbox-row:hover {
    border-color: var(--adm-gold);
}
.audit-checkbox-row.is-missing {
    border-color: rgba(239, 68, 68, 0.5);
    background: rgba(239, 68, 68, 0.05);
}
.audit-checkbox-row input[type="checkbox"] {
    margin-top: 3px;
    accent-color: var(--adm-gold);
    width: 16px;
    height: 16px;
    cursor: pointer;
}
.audit-checkbox-row .chk-content strong {
    font-size: 12.5px;
    color: var(--adm-text-primary);
    display: block;
}
.audit-checkbox-row .chk-content span {
    font-size: 11px;
    color: var(--adm-text-muted);
    display: block;
    margin-top: 2px;
    line-height: 1.35;
}
.audit-dish-card {
    background: var(--adm-bg-card);
    border: var(--adm-border-subtle);
    border-radius: 8px;
    padding: 12px 14px;
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.audit-dish-card.is-substituted {
    border-color: rgba(197, 160, 89, 0.45);
    background: linear-gradient(135deg, rgba(28, 56, 38, 0.3) 0%, rgba(16, 34, 23, 0.4) 100%);
}
.audit-dish-card.is-cancelled {
    opacity: 0.65;
    border-color: rgba(239, 68, 68, 0.3);
}
</style>

<script>
let currentAuditStep = 1;
let currentAuditBooking = null;
let auditFoodItems = [];
let auditActivities = [];
let auditRoomInventory = [];
let availableMenuItems = [];
let availableExperiences = [];
let availableRoomsList = [];
let customDamageItems = [];
let foodAddMode = 'menu';

function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    return String(text)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

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
    customDamageItems = [];

    // Ensure inventory items have necessary penalty fields initialized
    auditRoomInventory.forEach(item => {
        if (item.intact === undefined) item.intact = true;
        if (item.penalty_fee === undefined) item.penalty_fee = 0;
        if (item.missing_qty === undefined) item.missing_qty = item.qty || 1;
        if (item.damage_notes === undefined) item.damage_notes = '';
    });

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

    // Populate Food Menu Dropdown and set default mode
    populateFoodMenuPickerDropdown();
    switchFoodAddMode('menu');

    renderRoomInventoryList();
    renderMissingDamageList();
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

// -------------------------------------------------------------
// 1. ROOM INVENTORY & ITEMIZED DAMAGE / MISSING PENALTIES
// -------------------------------------------------------------
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
                    <strong>${escapeHtml(item.item)}</strong>
                    <span>${escapeHtml(item.notes || 'Standard Item')} • Qty: ${item.qty || 1}</span>
                </div>
            </label>
        `;
    });

    container.innerHTML = html;
    renderMissingDamageList();
}

function toggleInventoryItem(idx, isChecked) {
    if (auditRoomInventory[idx]) {
        auditRoomInventory[idx].intact = isChecked;
        if (!isChecked && !auditRoomInventory[idx].missing_qty) {
            auditRoomInventory[idx].missing_qty = auditRoomInventory[idx].qty || 1;
        }
        if (isChecked) {
            auditRoomInventory[idx].penalty_fee = 0;
            auditRoomInventory[idx].damage_notes = '';
        }
    }
    renderRoomInventoryList();
}

function checkAllRoomInventory(val) {
    auditRoomInventory.forEach(it => {
        it.intact = val;
        if (!val && !it.missing_qty) {
            it.missing_qty = it.qty || 1;
        }
        if (val) {
            it.penalty_fee = 0;
            it.damage_notes = '';
        }
    });
    renderRoomInventoryList();
}

function restoreInventoryItem(origIdx) {
    if (auditRoomInventory[origIdx]) {
        auditRoomInventory[origIdx].intact = true;
        auditRoomInventory[origIdx].penalty_fee = 0;
        auditRoomInventory[origIdx].damage_notes = '';
    }
    renderRoomInventoryList();
}

function updateMissingInvQty(origIdx, val) {
    if (auditRoomInventory[origIdx]) {
        auditRoomInventory[origIdx].missing_qty = Math.max(1, parseInt(val, 10) || 1);
    }
    updateAuditFinalCalculations();
}

function updateMissingInvFee(origIdx, val) {
    if (auditRoomInventory[origIdx]) {
        auditRoomInventory[origIdx].penalty_fee = Math.max(0, parseFloat(val) || 0);
    }
    updateAuditFinalCalculations();
}

function updateMissingInvNotes(origIdx, val) {
    if (auditRoomInventory[origIdx]) {
        auditRoomInventory[origIdx].damage_notes = val;
    }
}

function addCustomDamageItemRow() {
    customDamageItems.push({
        item_name: 'Incidental Damage / Penalty',
        quantity: 1,
        price: 0,
        notes: ''
    });
    renderMissingDamageList();
}

function removeCustomDamageItem(cIdx) {
    customDamageItems.splice(cIdx, 1);
    renderMissingDamageList();
}

function updateCustomDamageField(cIdx, field, val) {
    if (customDamageItems[cIdx]) {
        if (field === 'price') {
            customDamageItems[cIdx].price = Math.max(0, parseFloat(val) || 0);
        } else if (field === 'quantity') {
            customDamageItems[cIdx].quantity = Math.max(1, parseInt(val, 10) || 1);
        } else {
            customDamageItems[cIdx][field] = val;
        }
    }
    updateAuditFinalCalculations();
}

function renderMissingDamageList() {
    const penaltyBox = document.getElementById('audit-damage-penalty-box');
    const listContainer = document.getElementById('audit-missing-items-list');
    if (!penaltyBox || !listContainer) return;

    const missingInv = auditRoomInventory
        .map((it, idx) => ({ ...it, origIdx: idx }))
        .filter(it => it.intact === false);

    if (missingInv.length === 0 && customDamageItems.length === 0) {
        penaltyBox.style.display = 'none';
        listContainer.innerHTML = '';
        updateAuditFinalCalculations();
        return;
    }

    penaltyBox.style.display = 'block';
    let html = '';

    // Render missing room checklist items
    missingInv.forEach(item => {
        const origIdx = item.origIdx;
        const fee = parseFloat(item.penalty_fee || 0);
        const qty = parseInt(item.missing_qty || item.qty || 1, 10);

        html += `
            <div style="background: rgba(20, 35, 25, 0.7); border: 1px solid rgba(239, 68, 68, 0.35); border-radius: 8px; padding: 12px; display: flex; flex-direction: column; gap: 8px;">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="background: rgba(239, 68, 68, 0.2); color: #F87171; font-size: 11px; font-weight: 700; padding: 2px 7px; border-radius: 4px; text-transform: uppercase;">Missing Asset</span>
                        <strong style="color: #FFFFFF; font-size: 13.5px;">${escapeHtml(item.item)}</strong>
                        <span style="color: var(--adm-text-muted); font-size: 11.5px;">(Stock Qty: ${item.qty || 1})</span>
                    </div>
                    <button type="button" class="adm-btn-action" onclick="restoreInventoryItem(${origIdx});" style="font-size: 11px; padding: 3px 8px; background: rgba(34, 197, 94, 0.15); color: #22c55e; border: 1px solid rgba(34, 197, 94, 0.3);" title="Found / Restored to Intact">
                        <i class="fa-solid fa-rotate-left"></i> Mark Intact
                    </button>
                </div>

                <div style="display: grid; grid-template-columns: 80px 140px 1fr; gap: 10px; align-items: center;">
                    <div>
                        <label style="font-size: 10.5px; color: var(--adm-text-muted); text-transform: uppercase; display: block; margin-bottom: 2px;">Missing Qty</label>
                        <input type="number" class="adm-input" value="${qty}" min="1" max="${item.qty || 10}" style="padding: 5px 8px; font-size: 12px; text-align: center;" onchange="updateMissingInvQty(${origIdx}, this.value)">
                    </div>
                    <div>
                        <label style="font-size: 10.5px; color: var(--adm-text-muted); text-transform: uppercase; display: block; margin-bottom: 2px;">Penalty Fee / Unit (₹)</label>
                        <div style="position: relative;">
                            <span style="position: absolute; left: 8px; top: 50%; transform: translateY(-50%); color: #F87171; font-weight: 700; font-size: 11px;">₹</span>
                            <input type="number" class="adm-input" value="${fee > 0 ? fee : ''}" placeholder="0" min="0" step="50" style="padding: 5px 8px 5px 20px; font-size: 12px; font-weight: 700; color: #F87171;" oninput="updateMissingInvFee(${origIdx}, this.value)">
                        </div>
                    </div>
                    <div>
                        <label style="font-size: 10.5px; color: var(--adm-text-muted); text-transform: uppercase; display: block; margin-bottom: 2px;">Damage / Incident Notes</label>
                        <input type="text" class="adm-input" placeholder="e.g. Broken during stay / missing from room" value="${escapeHtml(item.damage_notes || '')}" style="padding: 5px 8px; font-size: 12px;" oninput="updateMissingInvNotes(${origIdx}, this.value)">
                    </div>
                </div>
            </div>
        `;
    });

    // Render ad-hoc custom damage / cleaning items
    customDamageItems.forEach((cd, cIdx) => {
        const fee = parseFloat(cd.price || 0);
        const qty = parseInt(cd.quantity || 1, 10);

        html += `
            <div style="background: rgba(20, 35, 25, 0.7); border: 1px solid rgba(239, 68, 68, 0.35); border-radius: 8px; padding: 12px; display: flex; flex-direction: column; gap: 8px;">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="background: rgba(245, 158, 11, 0.2); color: #F59E0B; font-size: 11px; font-weight: 700; padding: 2px 7px; border-radius: 4px; text-transform: uppercase;">Incidental / Other</span>
                        <input type="text" class="adm-input" value="${escapeHtml(cd.item_name || '')}" placeholder="Description (e.g. Linen Stains / Deep Cleaning)" style="padding: 4px 8px; font-size: 12.5px; font-weight: 700; width: 260px;" oninput="updateCustomDamageField(${cIdx}, 'item_name', this.value)">
                    </div>
                    <button type="button" class="adm-btn-action danger" onclick="removeCustomDamageItem(${cIdx});" style="font-size: 11px; padding: 3px 8px;" title="Remove this fee">
                        <i class="fa-solid fa-trash-can"></i> Remove
                    </button>
                </div>

                <div style="display: grid; grid-template-columns: 80px 140px 1fr; gap: 10px; align-items: center;">
                    <div>
                        <label style="font-size: 10.5px; color: var(--adm-text-muted); text-transform: uppercase; display: block; margin-bottom: 2px;">Qty</label>
                        <input type="number" class="adm-input" value="${qty}" min="1" max="50" style="padding: 5px 8px; font-size: 12px; text-align: center;" onchange="updateCustomDamageField(${cIdx}, 'quantity', this.value)">
                    </div>
                    <div>
                        <label style="font-size: 10.5px; color: var(--adm-text-muted); text-transform: uppercase; display: block; margin-bottom: 2px;">Charge (₹)</label>
                        <div style="position: relative;">
                            <span style="position: absolute; left: 8px; top: 50%; transform: translateY(-50%); color: #F87171; font-weight: 700; font-size: 11px;">₹</span>
                            <input type="number" class="adm-input" value="${fee > 0 ? fee : ''}" placeholder="0" min="0" step="50" style="padding: 5px 8px 5px 20px; font-size: 12px; font-weight: 700; color: #F87171;" oninput="updateCustomDamageField(${cIdx}, 'price', this.value)">
                        </div>
                    </div>
                    <div>
                        <label style="font-size: 10.5px; color: var(--adm-text-muted); text-transform: uppercase; display: block; margin-bottom: 2px;">Reason / Specifics</label>
                        <input type="text" class="adm-input" placeholder="e.g. Excessive mud on upholstery" value="${escapeHtml(cd.notes || '')}" style="padding: 5px 8px; font-size: 12px;" oninput="updateCustomDamageField(${cIdx}, 'notes', this.value)">
                    </div>
                </div>
            </div>
        `;
    });

    listContainer.innerHTML = html;
    updateAuditFinalCalculations();
}

function calculateTotalDamagePenalty() {
    let total = 0;
    auditRoomInventory.forEach(it => {
        if (it.intact === false) {
            const fee = parseFloat(it.penalty_fee || 0);
            const qty = parseInt(it.missing_qty || it.qty || 1, 10);
            total += (fee * qty);
        }
    });
    customDamageItems.forEach(cd => {
        const fee = parseFloat(cd.price || 0);
        const qty = parseInt(cd.quantity || 1, 10);
        total += (fee * qty);
    });
    return total;
}

// -------------------------------------------------------------
// 2. GASTRONOMY, FOOD MENU DROPDOWN & MEAL FULFILLMENT
// -------------------------------------------------------------
function switchFoodAddMode(mode) {
    foodAddMode = mode;
    const modeMenu = document.getElementById('food-add-mode-menu');
    const modeCustom = document.getElementById('food-add-mode-custom');
    const btnMenu = document.getElementById('btn-toggle-food-menu');
    const btnCustom = document.getElementById('btn-toggle-food-custom');

    if (mode === 'menu') {
        if (modeMenu) modeMenu.style.display = 'block';
        if (modeCustom) modeCustom.style.display = 'none';
        if (btnMenu) { btnMenu.className = 'adm-btn-action gold'; }
        if (btnCustom) { btnCustom.className = 'adm-btn-action outline'; }
    } else {
        if (modeMenu) modeMenu.style.display = 'none';
        if (modeCustom) modeCustom.style.display = 'block';
        if (btnMenu) { btnMenu.className = 'adm-btn-action outline'; }
        if (btnCustom) { btnCustom.className = 'adm-btn-action gold'; }
    }
}

function populateFoodMenuPickerDropdown() {
    const sel = document.getElementById('extra-food-menu-picker');
    if (!sel) return;

    if (!availableMenuItems || availableMenuItems.length === 0) {
        sel.innerHTML = '<option value="">No dishes found in Estate Food Menu</option>';
        return;
    }

    const categories = {};
    availableMenuItems.forEach(dish => {
        const cat = (dish.category || 'other').toLowerCase();
        if (!categories[cat]) categories[cat] = [];
        categories[cat].push(dish);
    });

    let html = '<option value="">-- Choose Dish from Estate Food Menu --</option>';
    const order = ['breakfast', 'lunch', 'dinner', 'snacks', 'beverages', 'dessert', 'other'];
    const keys = Object.keys(categories).sort((a, b) => {
        const ia = order.indexOf(a) !== -1 ? order.indexOf(a) : 99;
        const ib = order.indexOf(b) !== -1 ? order.indexOf(b) : 99;
        return ia - ib;
    });

    keys.forEach(cat => {
        const catTitle = cat.charAt(0).toUpperCase() + cat.slice(1);
        html += `<optgroup label="── ${catTitle} ──">`;
        categories[cat].forEach(d => {
            html += `<option value="${d.id}" data-name="${escapeHtml(d.name)}" data-price="${d.price}" data-category="${escapeHtml(d.category)}">${escapeHtml(d.name)} (₹${Number(d.price).toLocaleString('en-IN')})</option>`;
        });
        html += `</optgroup>`;
    });

    sel.innerHTML = html;
}

function onFoodMenuPickerChanged(val) {
    const priceInput = document.getElementById('menu-food-price');
    if (!priceInput) return;
    if (!val) {
        priceInput.value = '';
        return;
    }
    const dish = availableMenuItems.find(d => String(d.id) === String(val));
    if (dish) {
        priceInput.value = dish.price || 0;
    }
}

function addSelectedMenuDishItem() {
    const sel = document.getElementById('extra-food-menu-picker');
    if (!sel || !sel.value) {
        alert('Please choose a dish from the food menu dropdown.');
        return;
    }

    const dishId = sel.value;
    const dish = availableMenuItems.find(d => String(d.id) === String(dishId));
    if (!dish) return;

    const qty = Math.max(1, parseInt(document.getElementById('menu-food-qty')?.value || "1", 10));
    const priceInput = document.getElementById('menu-food-price');
    const price = priceInput && priceInput.value !== '' ? Math.max(0, parseFloat(priceInput.value)) : parseFloat(dish.price || 0);

    auditFoodItems.push({
        heading: dish.name,
        category: dish.category || 'Dining',
        quantity: qty,
        price: price,
        subtotal: qty * price,
        served: true,
        is_substituted: false
    });

    sel.value = '';
    if (priceInput) priceInput.value = '';
    const qtyInput = document.getElementById('menu-food-qty');
    if (qtyInput) qtyInput.value = '1';

    renderAuditFoodList();
}

function renderAuditFoodList() {
    const container = document.getElementById('audit-food-list-container');
    if (!container) return;

    let notesBanner = '';
    if (currentAuditBooking && currentAuditBooking.special_notes && currentAuditBooking.special_notes.trim()) {
        notesBanner = `
            <div style="background: rgba(197, 160, 89, 0.14); border-left: 3.5px solid var(--adm-gold); padding: 11px 15px; border-radius: 6px; font-size: 12.5px; color: var(--adm-text-primary); margin-bottom: 14px; display: flex; align-items: center; gap: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.15);">
                <i class="fa-solid fa-leaf" style="color: var(--adm-gold); font-size: 15px; flex-shrink: 0;"></i>
                <div>
                    <strong style="color: var(--adm-gold-light); text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; display: block;">Guest Dietary Preference / Special Request:</strong>
                    <span style="color: #FFFFFF; font-weight: 700; font-size: 13px;">"${escapeHtml(currentAuditBooking.special_notes)}"</span>
                </div>
            </div>
        `;
    }

    if (auditFoodItems.length === 0) {
        container.innerHTML = notesBanner + `
            <div style="background: var(--adm-bg-main); border: 1px dashed var(--adm-border-subtle); border-radius: 8px; padding: 14px; text-align: center; color: var(--adm-text-muted); font-size: 13px;">
                <i class="fa-solid fa-seedling" style="color: var(--adm-gold); margin-right: 6px;"></i>
                No pre-booked food dishes on initial reservation. Daily meals were ordered à la carte on arrival.
            </div>
        `;
        updateAuditFinalCalculations();
        return;
    }

    let html = notesBanner;
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
                    <div style="display: flex; align-items: center; gap: 12px; flex: 1; min-width: 240px;">
                        <!-- Prominent Served / Not Served Toggle Button -->
                        <button type="button" class="adm-btn-action" onclick="toggleAuditFoodServed(${idx}, ${!isServed})" 
                            style="padding: 5px 10px; font-size: 11.5px; font-weight: 700; border-radius: 6px; display: inline-flex; align-items: center; gap: 6px; cursor: pointer; transition: all 0.2s ease; ${isServed ? 'background: rgba(34, 197, 94, 0.15); color: #22c55e; border: 1px solid rgba(34, 197, 94, 0.35);' : 'background: rgba(239, 68, 68, 0.15); color: #F87171; border: 1px solid rgba(239, 68, 68, 0.4);'}"
                            title="${isServed ? 'Click to mark as Not Provided / Cancelled' : 'Click to mark as Provided / Served'}">
                            <i class="fa-solid ${isServed ? 'fa-circle-check' : 'fa-circle-xmark'}"></i>
                            <span>${isServed ? 'Provided' : 'Not Provided'}</span>
                        </button>

                        <div>
                            <strong style="color: ${isServed ? 'var(--adm-text-primary)' : '#94A3B8'}; font-size: 13.5px; display: block; ${!isServed ? 'text-decoration: line-through;' : ''}">
                                <i class="fa-solid ${catIcon}" style="color: var(--adm-gold); margin-right: 4px; font-size: 11px;"></i>
                                ${escapeHtml(item.heading)}
                                ${isSubstituted ? '<span style="font-size: 10px; background: rgba(197,160,89,0.25); color: var(--adm-gold); padding: 1px 6px; border-radius: 4px; margin-left: 6px; text-transform: uppercase;">Substituted</span>' : ''}
                                ${!isServed ? '<span style="font-size: 10px; background: rgba(239,68,68,0.2); color: #F87171; padding: 1px 6px; border-radius: 4px; margin-left: 6px; text-transform: uppercase;">Cancelled / ₹0</span>' : ''}
                            </strong>
                            <span style="font-size: 11px; color: var(--adm-text-muted); text-transform: uppercase;">
                                ${escapeHtml(item.category)} • ${qty} Set${qty > 1 ? 's' : ''} ${isSubstituted && item.original_dish ? ' • (Originally: ' + escapeHtml(item.original_dish) + ')' : ''}
                            </span>
                            ${item.special_notes ? `<div style="font-size: 11px; color: var(--adm-gold); font-weight: 600; margin-top: 3px; display: flex; align-items: center; gap: 4px;"><i class="fa-solid fa-note-sticky"></i> Note: ${escapeHtml(item.special_notes)}</div>` : ''}
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
                        <i class="fa-solid fa-utensils"></i> Substitute "${escapeHtml(item.heading)}" with actual served dish:
                    </div>
                    <div style="display: grid; grid-template-columns: 2fr 100px auto; gap: 8px; align-items: center;">
                        <select id="sub-select-${idx}" class="adm-input" style="font-size: 12px; padding: 6px 8px;" onchange="onSubstituteSelectChanged(${idx}, this.value)">
                            <option value="">-- Choose Replacement Dish from Menu --</option>
                            ${availableMenuItems.map(d => `<option value="${d.id}">[${d.category.toUpperCase()}] ${escapeHtml(d.name)} (₹${d.price})</option>`).join('')}
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

// -------------------------------------------------------------
// 3. EXPERIENCES AUDIT
// -------------------------------------------------------------
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
        updateAuditFinalCalculations();
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
                                ${escapeHtml(item.title)}
                                ${!isDone ? '<span style="font-size: 10px; background: rgba(239,68,68,0.2); color: #F87171; padding: 1px 6px; border-radius: 4px; margin-left: 6px; text-transform: uppercase;">Not Provided / Cancelled</span>' : ''}
                            </strong>
                            <span style="font-size: 11px; color: var(--adm-text-muted);">
                                ${escapeHtml(item.timing || 'Curated Schedule')} • Guided by Naturalist
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

// -------------------------------------------------------------
// 4. STEP 4 LIVE CALCULATIONS & SUMMARY
// -------------------------------------------------------------
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

    const damageFee = calculateTotalDamagePenalty();

    // Update Step 1 damage penalty total display
    const damageDisplay = document.getElementById('audit-damage-total-display');
    if (damageDisplay) {
        damageDisplay.innerText = '₹' + damageFee.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    // Update Step 4 damage row & itemized breakdown
    const damageRow = document.getElementById('audit-summary-damage-row');
    const damageBreakdown = document.getElementById('audit-summary-damage-breakdown');
    if (damageRow) {
        if (damageFee > 0) {
            damageRow.style.display = 'flex';
            document.getElementById('audit-summary-damage-rate').innerText = '₹' + damageFee.toLocaleString('en-IN', { minimumFractionDigits: 2 });
            
            const itemsList = [];
            auditRoomInventory.forEach(it => {
                if (it.intact === false && parseFloat(it.penalty_fee || 0) > 0) {
                    const q = parseInt(it.missing_qty || it.qty || 1, 10);
                    const p = parseFloat(it.penalty_fee || 0);
                    itemsList.push(`${it.item} (Qty: ${q} @ ₹${p.toLocaleString('en-IN')}) = ₹${(q * p).toLocaleString('en-IN')}`);
                }
            });
            customDamageItems.forEach(cd => {
                const p = parseFloat(cd.price || 0);
                if (p > 0) {
                    const q = parseInt(cd.quantity || 1, 10);
                    itemsList.push(`${cd.item_name || 'Incidental Fee'} (Qty: ${q} @ ₹${p.toLocaleString('en-IN')}) = ₹${(q * p).toLocaleString('en-IN')}`);
                }
            });
            if (damageBreakdown) {
                damageBreakdown.innerHTML = itemsList.map(t => '• ' + escapeHtml(t)).join('<br>');
            }
        } else {
            damageRow.style.display = 'none';
            if (damageBreakdown) damageBreakdown.innerHTML = '';
        }
    }

    const netTotal = roomAmt + foodTotal + actTotal + damageFee;

    document.getElementById('audit-summary-room-rate').innerText = '₹' + roomAmt.toLocaleString('en-IN', { minimumFractionDigits: 2 });
    document.getElementById('audit-summary-food-rate').innerText = '₹' + foodTotal.toLocaleString('en-IN', { minimumFractionDigits: 2 });
    document.getElementById('audit-summary-act-rate').innerText = '₹' + actTotal.toLocaleString('en-IN', { minimumFractionDigits: 2 });
    document.getElementById('audit-summary-total-rate').innerText = '₹' + netTotal.toLocaleString('en-IN', { minimumFractionDigits: 2 });
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

    // Collect individual missing damage items
    const missingDamageItems = [];
    auditRoomInventory.forEach(it => {
        if (it.intact === false) {
            const fee = parseFloat(it.penalty_fee || 0);
            const qty = parseInt(it.missing_qty || it.qty || 1, 10);
            missingDamageItems.push({
                item_name: it.item,
                quantity: qty,
                price: fee,
                notes: it.damage_notes || 'Missing / Damaged Room Asset',
                category: 'asset_damage'
            });
        }
    });

    customDamageItems.forEach(cd => {
        const fee = parseFloat(cd.price || 0);
        const qty = parseInt(cd.quantity || 1, 10);
        missingDamageItems.push({
            item_name: cd.item_name || 'Incidental Damage / Penalty',
            quantity: qty,
            price: fee,
            notes: cd.notes || '',
            category: 'asset_damage'
        });
    });

    const totalDamageFee = calculateTotalDamagePenalty();

    const payload = {
        action: 'save_audit_and_checkout',
        booking_id: bookingId,
        mark_checkout: markCheckout,
        room_notes: roomNotes,
        missing_damage_penalty: totalDamageFee,
        missing_damage_notes: missingDamageItems.map(m => m.item_name + (m.notes ? ' (' + m.notes + ')' : '')).join(', '),
        missing_damage_items: missingDamageItems,
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
</script>
