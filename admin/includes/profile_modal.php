<?php
// =========================================================================
// Food Forest Sanctuary — Profile & All Users Management Modal Component
// Included dynamically in admin/includes/header.php
// =========================================================================
?>

<!-- Luxury Profile & All Users Management Modal -->
<div class="adm-profile-modal-overlay" id="adm-profile-modal" role="dialog" aria-modal="true" aria-labelledby="adm-profile-modal-title">
    <div class="adm-profile-modal-card">
        <!-- Modal Header -->
        <div class="adm-profile-modal-header">
            <h3 class="adm-profile-modal-title" id="adm-profile-modal-title">
                <i class="fa-solid fa-user-shield"></i>
                <span>Sanctuary Credentials &amp; User Management</span>
            </h3>
            <button type="button" class="adm-profile-modal-close" onclick="closeProfileModal();" aria-label="Close dialog">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <!-- Navigation Tabs -->
        <div class="adm-profile-tabs" role="tablist">
            <button type="button" class="adm-profile-tab-btn is-active" data-tab="my_profile" onclick="switchProfileTab('my_profile');" role="tab">
                <i class="fa-solid fa-user-gear"></i>
                <span>My Profile &amp; Security</span>
            </button>
            <button type="button" class="adm-profile-tab-btn" data-tab="all_users" onclick="switchProfileTab('all_users');" role="tab">
                <i class="fa-solid fa-users-gear"></i>
                <span>All Users &amp; Admins (<span id="count-all-accounts">...</span>)</span>
            </button>
        </div>

        <!-- Tab 1: My Profile & Password -->
        <div class="adm-profile-tab-content adm-profile-tab-pane" data-pane="my_profile">
            <form id="form-my-profile" autocomplete="off">
                <!-- Avatar Live Customization Box -->
                <div class="adm-avatar-edit-box">
                    <div class="adm-avatar-preview-wrap" id="my-avatar-preview" onclick="document.getElementById('my-avatar-file').click();" title="Click to upload profile photo">
                        <!-- Populated by JS -->
                        <div class="fallback"><i class="fa-solid fa-user-shield"></i></div>
                        <div class="overlay">
                            <i class="fa-solid fa-camera"></i>
                            <span>Change</span>
                        </div>
                    </div>

                    <div class="adm-avatar-actions">
                        <div style="font-size: 13.5px; font-weight: 700; color: #FFFFFF;">
                            Profile Picture
                            <span style="font-size: 11px; font-weight: 400; color: var(--adm-gold); margin-left: 6px;">(Auto-compressed &amp; framed)</span>
                        </div>
                        <p style="font-size: 11.5px; color: #94A3B8; margin: 0 0 8px;">Upload a personal portrait or choose an organic sanctuary avatar emblem below.</p>

                        <!-- Hidden File Input & Flags -->
                        <input type="file" id="my-avatar-file" name="avatar_file" accept="image/*" style="display: none;">
                        <input type="hidden" id="my-avatar-preset" name="avatar_preset" value="">
                        <input type="hidden" id="my-remove-avatar-flag" name="remove_avatar" value="0">

                        <div class="adm-avatar-actions-btns">
                            <button type="button" class="adm-btn-action gold" onclick="document.getElementById('my-avatar-file').click();" style="padding: 6px 14px; font-size: 12px;">
                                <i class="fa-solid fa-upload"></i> <span>Upload Photo</span>
                            </button>
                            <button type="button" class="adm-btn-action outline" id="btn-remove-my-avatar" style="padding: 6px 14px; font-size: 12px; color: #F87171; border-color: rgba(239, 68, 68, 0.4); display: none;">
                                <i class="fa-solid fa-trash-can"></i> <span>Remove Photo</span>
                            </button>
                        </div>

                        <!-- Curated Luxury Sanctuary Emblem Presets -->
                        <div style="margin-top: 10px;">
                            <span style="font-size: 11px; font-weight: 600; color: #94A3B8; text-transform: uppercase; letter-spacing: 0.5px;">Or Choose Sanctuary Emblem:</span>
                            <div class="adm-avatar-presets-grid">
                                <button type="button" class="adm-avatar-preset-btn my-preset-btn" data-preset="preset:shield" title="Emerald Shield"><i class="fa-solid fa-shield-halved"></i></button>
                                <button type="button" class="adm-avatar-preset-btn my-preset-btn" data-preset="preset:crown" title="Golden Crown"><i class="fa-solid fa-crown"></i></button>
                                <button type="button" class="adm-avatar-preset-btn my-preset-btn" data-preset="preset:leaf" title="Organic Leaf"><i class="fa-solid fa-leaf"></i></button>
                                <button type="button" class="adm-avatar-preset-btn my-preset-btn" data-preset="preset:key" title="Concierge Key"><i class="fa-solid fa-key"></i></button>
                                <button type="button" class="adm-avatar-preset-btn my-preset-btn" data-preset="preset:feather" title="Estate Quill"><i class="fa-solid fa-feather-pointed"></i></button>
                                <button type="button" class="adm-avatar-preset-btn my-preset-btn" data-preset="preset:mountain" title="Misty Mountain"><i class="fa-solid fa-mountain-sun"></i></button>
                                <button type="button" class="adm-avatar-preset-btn my-preset-btn" data-preset="preset:villa" title="Sanctuary Chalet"><i class="fa-solid fa-hotel"></i></button>
                                <button type="button" class="adm-avatar-preset-btn my-preset-btn" data-preset="preset:sprout" title="Forest Sprout"><i class="fa-solid fa-seedling"></i></button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Account Credentials Fields -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px;">
                    <div class="adm-form-group">
                        <label class="adm-label" for="my-username" style="font-size: 12px; font-weight: 700; color: var(--adm-gold); display: block; margin-bottom: 6px;">
                            <i class="fa-solid fa-at"></i> Username *
                        </label>
                        <input type="text" id="my-username" name="username" class="adm-input" required style="width: 100%; padding: 10px 14px; font-size: 13.5px; background: rgba(7, 20, 13, 0.7); border: 1px solid rgba(197, 160, 89, 0.3); border-radius: 8px; color: #FFFFFF;" placeholder="e.g. admin">
                        <small style="font-size: 10.5px; color: #94A3B8; display: block; margin-top: 4px;">Used for signing into the concierge dashboard.</small>
                    </div>

                    <div class="adm-form-group">
                        <label class="adm-label" for="my-fullname" style="font-size: 12px; font-weight: 700; color: #FFFFFF; display: block; margin-bottom: 6px;">
                            <i class="fa-solid fa-signature"></i> Full Display Name *
                        </label>
                        <input type="text" id="my-fullname" name="full_name" class="adm-input" required style="width: 100%; padding: 10px 14px; font-size: 13.5px; background: rgba(7, 20, 13, 0.7); border: 1px solid rgba(197, 160, 89, 0.3); border-radius: 8px; color: #FFFFFF;" placeholder="e.g. Food Forest Concierge">
                    </div>
                </div>

                <div class="adm-form-group" style="margin-bottom: 24px;">
                    <label class="adm-label" for="my-email" style="font-size: 12px; font-weight: 700; color: #FFFFFF; display: block; margin-bottom: 6px;">
                        <i class="fa-solid fa-envelope"></i> Email Address
                    </label>
                    <input type="email" id="my-email" name="email" class="adm-input" style="width: 100%; padding: 10px 14px; font-size: 13.5px; background: rgba(7, 20, 13, 0.7); border: 1px solid rgba(197, 160, 89, 0.3); border-radius: 8px; color: #FFFFFF;" placeholder="e.g. foodforestkanthalloor@gmail.com">
                </div>

                <!-- Password Change Box -->
                <div style="background: rgba(0, 0, 0, 0.3); border: 1px solid rgba(197, 160, 89, 0.25); border-radius: 12px; padding: 18px 20px; margin-bottom: 24px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                        <div style="font-size: 13.5px; font-weight: 700; color: var(--adm-gold); display: flex; align-items: center; gap: 8px;">
                            <i class="fa-solid fa-key"></i> Change Password
                        </div>
                        <span style="font-size: 11px; color: #94A3B8;">Leave blank if not changing password</span>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px;">
                        <div class="adm-form-group">
                            <label class="adm-label" for="my-current-pass" style="font-size: 11.5px; color: #CBD5E1; display: block; margin-bottom: 4px;">Current Password</label>
                            <div style="position: relative;">
                                <input type="password" id="my-current-pass" name="current_password" class="adm-input" style="width: 100%; padding: 9px 36px 9px 12px; font-size: 13px; background: rgba(7, 20, 13, 0.9); border: 1px solid rgba(255, 255, 255, 0.15); border-radius: 6px; color: #FFFFFF;" placeholder="••••••••">
                                <button type="button" class="adm-pass-toggle-btn" data-target="my-current-pass" style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #94A3B8; cursor: pointer; padding: 4px;">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="adm-form-group">
                            <label class="adm-label" for="my-new-pass" style="font-size: 11.5px; color: #CBD5E1; display: block; margin-bottom: 4px;">New Password</label>
                            <div style="position: relative;">
                                <input type="password" id="my-new-pass" name="new_password" class="adm-input" style="width: 100%; padding: 9px 36px 9px 12px; font-size: 13px; background: rgba(7, 20, 13, 0.9); border: 1px solid rgba(255, 255, 255, 0.15); border-radius: 6px; color: #FFFFFF;" placeholder="Min. 6 chars">
                                <button type="button" class="adm-pass-toggle-btn" data-target="my-new-pass" style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #94A3B8; cursor: pointer; padding: 4px;">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="adm-form-group">
                            <label class="adm-label" for="my-confirm-pass" style="font-size: 11.5px; color: #CBD5E1; display: block; margin-bottom: 4px;">Confirm New Password</label>
                            <div style="position: relative;">
                                <input type="password" id="my-confirm-pass" name="confirm_password" class="adm-input" style="width: 100%; padding: 9px 36px 9px 12px; font-size: 13px; background: rgba(7, 20, 13, 0.9); border: 1px solid rgba(255, 255, 255, 0.15); border-radius: 6px; color: #FFFFFF;" placeholder="Re-type new pass">
                                <button type="button" class="adm-pass-toggle-btn" data-target="my-confirm-pass" style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #94A3B8; cursor: pointer; padding: 4px;">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Footer -->
                <div style="display: flex; justify-content: flex-end; gap: 12px;">
                    <button type="button" class="adm-btn-action outline" onclick="closeProfileModal();" style="padding: 10px 20px;">
                        Cancel
                    </button>
                    <button type="submit" class="adm-btn-action gold" id="btn-save-my-profile" style="padding: 10px 24px; font-weight: 700;">
                        <i class="fa-solid fa-floppy-disk"></i> <span>Save Profile Changes</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Tab 2: All Users & Admins Management -->
        <div class="adm-profile-tab-content adm-profile-tab-pane" data-pane="all_users" style="display: none;">
            
            <!-- VIEW A: List of Users & Admins -->
            <div id="user-list-section">
                <!-- Top Filter & Action Bar -->
                <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin-bottom: 18px;">
                    <!-- Filter Pills -->
                    <div style="display: inline-flex; background: rgba(0, 0, 0, 0.35); padding: 3px; border-radius: 30px; border: 1px solid rgba(197, 160, 89, 0.25);">
                        <button type="button" class="adm-user-filter-pill is-active" data-filter="all" style="background: none; border: none; border-radius: 20px; padding: 6px 14px; font-size: 12px; color: #E2E8F0; cursor: pointer; font-weight: 600;">
                            All Accounts
                        </button>
                        <button type="button" class="adm-user-filter-pill" data-filter="admins" style="background: none; border: none; border-radius: 20px; padding: 6px 14px; font-size: 12px; color: #E2E8F0; cursor: pointer; font-weight: 600;">
                            Admins (<span id="count-admins">0</span>)
                        </button>
                        <button type="button" class="adm-user-filter-pill" data-filter="users" style="background: none; border: none; border-radius: 20px; padding: 6px 14px; font-size: 12px; color: #E2E8F0; cursor: pointer; font-weight: 600;">
                            Registered Users (<span id="count-users">0</span>)
                        </button>
                    </div>

                    <!-- Search Input & Create Button -->
                    <div style="display: flex; gap: 10px; align-items: center; flex-grow: 1; justify-content: flex-end;">
                        <div style="position: relative; max-width: 260px; width: 100%;">
                            <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); font-size: 12px; color: #94A3B8;"></i>
                            <input type="text" id="user-search-input" class="adm-input" placeholder="Search accounts..." style="width: 100%; padding: 7px 12px 7px 32px; font-size: 12.5px; background: rgba(7, 20, 13, 0.7); border: 1px solid rgba(197, 160, 89, 0.3); border-radius: 20px; color: #FFFFFF;">
                        </div>

                        <button type="button" class="adm-btn-action gold" id="btn-add-new-user" style="padding: 7px 15px; font-size: 12px; white-space: nowrap;">
                            <i class="fa-solid fa-user-plus"></i> <span>Add New Account</span>
                        </button>
                    </div>
                </div>

                <!-- Table Container -->
                <div style="overflow-x: auto; max-height: 480px; border: 1px solid rgba(197, 160, 89, 0.25); border-radius: 12px; background: rgba(16, 31, 21, 0.3);">
                    <table class="adm-users-table">
                        <thead>
                            <tr>
                                <th>Photo</th>
                                <th>Name &amp; Username</th>
                                <th>Account Role</th>
                                <th>Contact Information</th>
                                <th>Last Active</th>
                                <th style="text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="adm-all-users-tbody">
                            <!-- Populated dynamically via JS -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- VIEW B: Edit / Create User Drawer -->
            <div id="user-edit-drawer" style="display: none;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 1px solid rgba(197, 160, 89, 0.2);">
                    <h4 id="user-edit-drawer-title" style="margin: 0; font-size: 16px; font-weight: 700; color: #FFFFFF; display: flex; align-items: center; gap: 8px;">
                        <!-- Filled by JS -->
                    </h4>
                    <button type="button" class="adm-btn-action outline" onclick="closeUserEditor();" style="padding: 5px 12px; font-size: 12px;">
                        <i class="fa-solid fa-arrow-left"></i> Back to User List
                    </button>
                </div>

                <form id="form-user-edit" autocomplete="off">
                    <input type="hidden" id="edit-user-id" name="target_id" value="0">
                    <input type="hidden" id="edit-user-type" name="target_type" value="user">

                    <!-- Avatar Live Customization Box for Target User -->
                    <div class="adm-avatar-edit-box">
                        <div class="adm-avatar-preview-wrap" id="edit-user-avatar-preview" onclick="document.getElementById('edit-user-avatar-file').click();" title="Click to upload profile photo" style="width: 80px; height: 80px;">
                            <div class="fallback"><i class="fa-solid fa-user"></i></div>
                            <div class="overlay"><i class="fa-solid fa-camera"></i></div>
                        </div>

                        <div class="adm-avatar-actions">
                            <div style="font-size: 13px; font-weight: 700; color: #FFFFFF;">User Profile Picture</div>
                            <input type="file" id="edit-user-avatar-file" name="avatar_file" accept="image/*" style="display: none;">
                            <input type="hidden" id="edit-user-avatar-preset" name="avatar_preset" value="">
                            <input type="hidden" id="edit-user-remove-avatar-flag" name="remove_avatar" value="0">

                            <div class="adm-avatar-actions-btns">
                                <button type="button" class="adm-btn-action gold" onclick="document.getElementById('edit-user-avatar-file').click();" style="padding: 5px 12px; font-size: 11.5px;">
                                    <i class="fa-solid fa-upload"></i> Upload
                                </button>
                                <button type="button" class="adm-btn-action outline" id="btn-remove-edit-user-avatar" style="padding: 5px 12px; font-size: 11.5px; color: #F87171; border-color: rgba(239, 68, 68, 0.4); display: none;">
                                    Remove
                                </button>
                            </div>

                            <div class="adm-avatar-presets-grid" style="margin-top: 6px;">
                                <button type="button" class="adm-avatar-preset-btn edit-user-preset-btn" data-preset="preset:shield" title="Shield"><i class="fa-solid fa-shield-halved"></i></button>
                                <button type="button" class="adm-avatar-preset-btn edit-user-preset-btn" data-preset="preset:crown" title="Crown"><i class="fa-solid fa-crown"></i></button>
                                <button type="button" class="adm-avatar-preset-btn edit-user-preset-btn" data-preset="preset:leaf" title="Leaf"><i class="fa-solid fa-leaf"></i></button>
                                <button type="button" class="adm-avatar-preset-btn edit-user-preset-btn" data-preset="preset:key" title="Key"><i class="fa-solid fa-key"></i></button>
                                <button type="button" class="adm-avatar-preset-btn edit-user-preset-btn" data-preset="preset:mountain" title="Mountain"><i class="fa-solid fa-mountain-sun"></i></button>
                            </div>
                        </div>
                    </div>

                    <!-- Row 1: Account Type & Full Name -->
                    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 16px; margin-bottom: 16px;">
                        <div class="adm-form-group">
                            <label class="adm-label" style="font-size: 12px; font-weight: 700; color: var(--adm-gold); display: block; margin-bottom: 6px;">
                                Account Classification *
                            </label>
                            <select id="edit-user-type-selector" class="adm-input" style="width: 100%; padding: 10px 14px; font-size: 13px; background: rgba(7, 20, 13, 0.85); border: 1px solid rgba(197, 160, 89, 0.35); border-radius: 8px; color: #FFFFFF;">
                                <option value="admin">Administrator (Full Concierge Access)</option>
                                <option value="user" selected>Guest / Regular User Account</option>
                            </select>
                        </div>

                        <div class="adm-form-group">
                            <label class="adm-label" for="edit-user-fullname" style="font-size: 12px; font-weight: 700; color: #FFFFFF; display: block; margin-bottom: 6px;">
                                Full Name *
                            </label>
                            <input type="text" id="edit-user-fullname" name="full_name" class="adm-input" required style="width: 100%; padding: 10px 14px; font-size: 13.5px; background: rgba(7, 20, 13, 0.7); border: 1px solid rgba(197, 160, 89, 0.3); border-radius: 8px; color: #FFFFFF;" placeholder="e.g. Samuel Mathew">
                        </div>
                    </div>

                    <!-- Row 2: Username & Email -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                        <div class="adm-form-group">
                            <label class="adm-label" for="edit-user-username" style="font-size: 12px; font-weight: 700; color: var(--adm-gold); display: block; margin-bottom: 6px;">
                                Username / Handle
                            </label>
                            <input type="text" id="edit-user-username" name="username" class="adm-input" style="width: 100%; padding: 10px 14px; font-size: 13.5px; background: rgba(7, 20, 13, 0.7); border: 1px solid rgba(197, 160, 89, 0.3); border-radius: 8px; color: #FFFFFF;" placeholder="e.g. samuel">
                        </div>

                        <div class="adm-form-group">
                            <label class="adm-label" for="edit-user-email" style="font-size: 12px; font-weight: 700; color: #FFFFFF; display: block; margin-bottom: 6px;">
                                Email Address *
                            </label>
                            <input type="email" id="edit-user-email" name="email" class="adm-input" required style="width: 100%; padding: 10px 14px; font-size: 13.5px; background: rgba(7, 20, 13, 0.7); border: 1px solid rgba(197, 160, 89, 0.3); border-radius: 8px; color: #FFFFFF;" placeholder="e.g. samuel@gmail.com">
                        </div>
                    </div>

                    <!-- Row 3: Role (Admin) or Phone (User) -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                        <div class="adm-form-group" id="edit-user-role-group">
                            <label class="adm-label" for="edit-user-role" style="font-size: 12px; font-weight: 700; color: #FFFFFF; display: block; margin-bottom: 6px;">
                                Administrative Role
                            </label>
                            <select id="edit-user-role" name="role" class="adm-input" style="width: 100%; padding: 10px 14px; font-size: 13px; background: rgba(7, 20, 13, 0.85); border: 1px solid rgba(197, 160, 89, 0.35); border-radius: 8px; color: #FFFFFF;">
                                <option value="General Manager">General Manager</option>
                                <option value="Concierge Lead">Concierge Lead</option>
                                <option value="Kitchen Manager">Kitchen Manager / Chef</option>
                                <option value="Staff Administrator">Staff Administrator</option>
                            </select>
                        </div>

                        <div class="adm-form-group" id="edit-user-phone-group">
                            <label class="adm-label" for="edit-user-phone" style="font-size: 12px; font-weight: 700; color: #FFFFFF; display: block; margin-bottom: 6px;">
                                Phone Number
                            </label>
                            <input type="tel" id="edit-user-phone" name="phone" class="adm-input" style="width: 100%; padding: 10px 14px; font-size: 13.5px; background: rgba(7, 20, 13, 0.7); border: 1px solid rgba(197, 160, 89, 0.3); border-radius: 8px; color: #FFFFFF;" placeholder="e.g. +91 9876543210">
                        </div>

                        <!-- Direct Password Reset / Assignment Field -->
                        <div class="adm-form-group" style="grid-column: span 2;">
                            <label class="adm-label" for="edit-user-pass" style="font-size: 12px; font-weight: 700; color: var(--adm-gold); display: block; margin-bottom: 6px;">
                                <i class="fa-solid fa-lock"></i> Set / Reset Password
                            </label>
                            <div style="position: relative; max-width: 400px;">
                                <input type="password" id="edit-user-pass" name="new_password" class="adm-input" style="width: 100%; padding: 10px 38px 10px 14px; font-size: 13.5px; background: rgba(7, 20, 13, 0.9); border: 1px solid rgba(197, 160, 89, 0.35); border-radius: 8px; color: #FFFFFF;" placeholder="Enter new password (min. 6 characters)...">
                                <button type="button" class="adm-pass-toggle-btn" data-target="edit-user-pass" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #94A3B8; cursor: pointer; padding: 4px;">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </div>
                            <small id="edit-user-pass-hint" style="font-size: 11px; color: #94A3B8; display: block; margin-top: 5px;">Leave empty to keep existing password.</small>
                        </div>
                    </div>

                    <!-- Buttons -->
                    <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px; padding-top: 16px; border-top: 1px solid rgba(197, 160, 89, 0.2);">
                        <button type="button" class="adm-btn-action outline" id="btn-cancel-edit-user" style="padding: 10px 20px;">
                            Cancel
                        </button>
                        <button type="submit" class="adm-btn-action gold" id="btn-save-edit-user" style="padding: 10px 24px; font-weight: 700;">
                            <i class="fa-solid fa-floppy-disk"></i> <span>Save Account Details</span>
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>
