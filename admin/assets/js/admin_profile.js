/* =========================================================================
   Food Forest Sanctuary — Top Right User Profile & Account Management Engine
   Handles:
   1. User Dropdown toggle & dismissal
   2. "My Profile" tab: change username, password, full name, email, avatar
   3. "All Users & Admins" tab: view, search, edit passwords/usernames/avatars, create & delete
   ========================================================================= */

(function() {
    'use strict';

    let allAdminsCache = [];
    let allUsersCache = [];
    let activeFilter = 'all';
    let currentAdminId = 0;

    // Preset Icons map
    const PRESET_ICONS = {
        'preset:shield': '<i class="fa-solid fa-shield-halved" style="color:#DFC289;"></i>',
        'preset:crown': '<i class="fa-solid fa-crown" style="color:#DFC289;"></i>',
        'preset:leaf': '<i class="fa-solid fa-leaf" style="color:#4ADE80;"></i>',
        'preset:key': '<i class="fa-solid fa-key" style="color:#DFC289;"></i>',
        'preset:feather': '<i class="fa-solid fa-feather-pointed" style="color:#60A5FA;"></i>',
        'preset:mountain': '<i class="fa-solid fa-mountain-sun" style="color:#34D399;"></i>',
        'preset:villa': '<i class="fa-solid fa-hotel" style="color:#FBBF24;"></i>',
        'preset:sprout': '<i class="fa-solid fa-seedling" style="color:#4ADE80;"></i>'
    };

    function renderAvatarElement(avatarUrl, fullName, size = 44) {
        fullName = fullName || 'User';
        const parts = fullName.trim().split(' ');
        const initials = (parts[0][0] + (parts[1] ? parts[1][0] : '')).toUpperCase();

        if (avatarUrl && avatarUrl.trim()) {
            if (avatarUrl.startsWith('preset:')) {
                const iconHtml = PRESET_ICONS[avatarUrl] || '<i class="fa-solid fa-user-shield"></i>';
                return `<div style="width:${size}px;height:${size}px;border-radius:50%;background:rgba(197,160,89,0.2);border:1.5px solid var(--adm-gold);display:flex;align-items:center;justify-content:center;font-size:${Math.round(size * 0.44)}px;flex-shrink:0;">${iconHtml}</div>`;
            }
            let src = avatarUrl.trim();
            if (!src.startsWith('http') && !src.startsWith('data:') && !src.startsWith('../')) {
                src = '../' + src.replace(/^\/+/, '');
            }
            return `<img src="${src}" alt="${fullName}" style="width:${size}px;height:${size}px;object-fit:cover;border-radius:50%;border:1.5px solid var(--adm-gold);display:block;flex-shrink:0;">`;
        }
        return `<div style="width:${size}px;height:${size}px;border-radius:50%;background:rgba(197,160,89,0.25);border:1.5px solid var(--adm-gold);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:${Math.round(size * 0.38)}px;color:#DFC289;flex-shrink:0;">${initials}</div>`;
    }

    // Initialize once DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        const userBtn = document.getElementById('adm-topbar-user-btn');
        const userDropdown = document.getElementById('adm-user-dropdown');
        const profileModal = document.getElementById('adm-profile-modal');

        if (!userBtn || !userDropdown) return;

        // 1. Dropdown Toggle
        userBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            const isOpen = userDropdown.classList.contains('is-open');
            closeAllPopups();
            if (!isOpen) {
                userDropdown.classList.add('is-open');
                userBtn.classList.add('is-active');
                userBtn.setAttribute('aria-expanded', 'true');
            }
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!userDropdown.contains(e.target) && !userBtn.contains(e.target)) {
                closeAllPopups();
            }
        });

        // Close with ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeAllPopups();
                if (profileModal && profileModal.classList.contains('is-active')) {
                    window.closeProfileModal();
                }
            }
        });

        function closeAllPopups() {
            if (userDropdown) userDropdown.classList.remove('is-open');
            if (userBtn) {
                userBtn.classList.remove('is-active');
                userBtn.setAttribute('aria-expanded', 'false');
            }
        }

        // 2. Open Profile Modal Global Function
        window.openProfileModal = function(initialTab = 'profile') {
            closeAllPopups();
            if (!profileModal) return;
            profileModal.classList.add('is-active');
            document.body.style.overflow = 'hidden';

            if (initialTab === 'users') {
                window.switchProfileTab('all_users');
            } else {
                window.switchProfileTab('my_profile');
                if (initialTab === 'password') {
                    setTimeout(() => {
                        const passInput = document.getElementById('my-new-pass');
                        if (passInput) {
                            passInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            passInput.focus();
                        }
                    }, 200);
                }
            }
        };

        window.closeProfileModal = function() {
            if (!profileModal) return;
            profileModal.classList.remove('is-active');
            document.body.style.overflow = '';
        };

        // 3. Tab Switching
        window.switchProfileTab = function(tabName) {
            document.querySelectorAll('.adm-profile-tab-btn').forEach(btn => {
                btn.classList.toggle('is-active', btn.getAttribute('data-tab') === tabName);
            });
            document.querySelectorAll('.adm-profile-tab-pane').forEach(pane => {
                pane.style.display = (pane.getAttribute('data-pane') === tabName) ? 'block' : 'none';
            });

            if (tabName === 'all_users') {
                loadAllUsers();
            } else if (tabName === 'my_profile') {
                loadMyProfile();
            }
        };

        // 4. Password Show / Hide Toggles
        document.querySelectorAll('.adm-pass-toggle-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const targetId = this.getAttribute('data-target');
                const input = document.getElementById(targetId);
                if (!input) return;
                const isPass = input.type === 'password';
                input.type = isPass ? 'text' : 'password';
                this.innerHTML = isPass ? '<i class="fa-solid fa-eye-slash"></i>' : '<i class="fa-solid fa-eye"></i>';
            });
        });

        // 5. My Profile Form Engine
        function loadMyProfile() {
            fetch('api/manage_profile.php?action=get_my_profile')
                .then(r => r.json())
                .then(res => {
                    if (!res.success || !res.profile) return;
                    const p = res.profile;
                    currentAdminId = p.id;
                    const usernameInp = document.getElementById('my-username');
                    const nameInp = document.getElementById('my-fullname');
                    const emailInp = document.getElementById('my-email');
                    if (usernameInp) usernameInp.value = p.username || '';
                    if (nameInp) nameInp.value = p.full_name || '';
                    if (emailInp) emailInp.value = p.email || '';

                    updateMyAvatarPreview(p.avatar_url, p.full_name);
                })
                .catch(err => console.warn('Could not fetch admin profile:', err));
        }

        function updateMyAvatarPreview(avatarUrl, name) {
            const container = document.getElementById('my-avatar-preview');
            if (!container) return;
            container.innerHTML = renderAvatarElement(avatarUrl, name, 96);
            const removeBtn = document.getElementById('btn-remove-my-avatar');
            if (removeBtn) {
                removeBtn.style.display = (avatarUrl && avatarUrl.trim()) ? 'inline-flex' : 'none';
            }
        }

        // Avatar file upload input change
        const avatarFileInput = document.getElementById('my-avatar-file');
        if (avatarFileInput) {
            avatarFileInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const container = document.getElementById('my-avatar-preview');
                        if (container) {
                            container.innerHTML = `<img src="${e.target.result}" style="width:96px;height:96px;object-fit:cover;border-radius:50%;border:2px solid var(--adm-gold);display:block;">`;
                        }
                        const removeFlag = document.getElementById('my-remove-avatar-flag');
                        if (removeFlag) removeFlag.value = '0';
                        const presetFlag = document.getElementById('my-avatar-preset');
                        if (presetFlag) presetFlag.value = '';
                        document.querySelectorAll('.my-preset-btn').forEach(b => b.classList.remove('is-active'));
                        const removeBtn = document.getElementById('btn-remove-my-avatar');
                        if (removeBtn) removeBtn.style.display = 'inline-flex';
                    };
                    reader.readAsDataURL(this.files[0]);
                }
            });
        }

        // Remove Avatar button
        const removeAvatarBtn = document.getElementById('btn-remove-my-avatar');
        if (removeAvatarBtn) {
            removeAvatarBtn.addEventListener('click', function(e) {
                e.preventDefault();
                const removeFlag = document.getElementById('my-remove-avatar-flag');
                if (removeFlag) removeFlag.value = '1';
                const presetFlag = document.getElementById('my-avatar-preset');
                if (presetFlag) presetFlag.value = '';
                if (avatarFileInput) avatarFileInput.value = '';
                document.querySelectorAll('.my-preset-btn').forEach(b => b.classList.remove('is-active'));
                const nameInp = document.getElementById('my-fullname');
                updateMyAvatarPreview('', nameInp ? nameInp.value : 'Admin');
            });
        }

        // Preset Avatar click
        document.querySelectorAll('.my-preset-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const presetKey = this.getAttribute('data-preset');
                document.querySelectorAll('.my-preset-btn').forEach(b => b.classList.remove('is-active'));
                this.classList.add('is-active');

                const presetFlag = document.getElementById('my-avatar-preset');
                if (presetFlag) presetFlag.value = presetKey;
                const removeFlag = document.getElementById('my-remove-avatar-flag');
                if (removeFlag) removeFlag.value = '0';
                if (avatarFileInput) avatarFileInput.value = '';

                const container = document.getElementById('my-avatar-preview');
                if (container) {
                    container.innerHTML = renderAvatarElement(presetKey, 'Admin', 96);
                }
                const removeBtn = document.getElementById('btn-remove-my-avatar');
                if (removeBtn) removeBtn.style.display = 'inline-flex';
            });
        });

        // Save My Profile Form Submit
        const myProfileForm = document.getElementById('form-my-profile');
        if (myProfileForm) {
            myProfileForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const saveBtn = document.getElementById('btn-save-my-profile');
                const origBtnText = saveBtn ? saveBtn.innerHTML : 'Save';
                if (saveBtn) {
                    saveBtn.disabled = true;
                    saveBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving Profile...';
                }

                const formData = new FormData(myProfileForm);
                formData.append('action', 'update_my_profile');

                fetch('api/manage_profile.php', {
                    method: 'POST',
                    body: formData
                })
                .then(r => r.json())
                .then(res => {
                    if (saveBtn) {
                        saveBtn.disabled = false;
                        saveBtn.innerHTML = origBtnText;
                    }
                    if (res.success) {
                        if (typeof window.showAdmToast === 'function') {
                            window.showAdmToast(res.message, 'success');
                        } else {
                            alert(res.message);
                        }

                        // Sync Topbar and Sidebar immediately
                        syncLiveAdminUI(res.profile);

                        // Clear password inputs
                        ['my-current-pass', 'my-new-pass', 'my-confirm-pass'].forEach(id => {
                            const inp = document.getElementById(id);
                            if (inp) inp.value = '';
                        });
                    } else {
                        if (typeof window.showAdmToast === 'function') {
                            window.showAdmToast(res.message || 'Update failed', 'error');
                        } else {
                            alert(res.message || 'Update failed');
                        }
                    }
                })
                .catch(err => {
                    if (saveBtn) {
                        saveBtn.disabled = false;
                        saveBtn.innerHTML = origBtnText;
                    }
                    alert('Network error updating profile: ' + err.message);
                });
            });
        }

        function syncLiveAdminUI(profile) {
            if (!profile) return;
            // Topbar name
            const topbarName = document.getElementById('adm-topbar-name');
            if (topbarName) topbarName.textContent = profile.full_name;
            // Sidebar name
            const sidebarName = document.getElementById('adm-sidebar-name');
            if (sidebarName) sidebarName.textContent = profile.full_name;
            // Dropdown name & handle
            const dropdownName = document.getElementById('adm-dropdown-name');
            if (dropdownName) dropdownName.textContent = profile.full_name;
            const dropdownHandle = document.getElementById('adm-dropdown-handle');
            if (dropdownHandle) dropdownHandle.textContent = '@' + profile.username;

            // Topbar Avatar
            const topbarAvatar = document.getElementById('adm-topbar-avatar');
            if (topbarAvatar) {
                topbarAvatar.innerHTML = renderAvatarElement(profile.avatar_url, profile.full_name, 34);
            }
            // Sidebar Avatar
            const sidebarAvatar = document.getElementById('adm-sidebar-avatar');
            if (sidebarAvatar) {
                sidebarAvatar.innerHTML = renderAvatarElement(profile.avatar_url, profile.full_name, 38);
            }
            // Dropdown Avatar
            const dropdownAvatar = document.getElementById('adm-dropdown-avatar-wrap');
            if (dropdownAvatar) {
                dropdownAvatar.innerHTML = renderAvatarElement(profile.avatar_url, profile.full_name, 44);
            }
        }

        // 6. All Users & Admins Management Tab
        function loadAllUsers() {
            const tableBody = document.getElementById('adm-all-users-tbody');
            if (tableBody) {
                tableBody.innerHTML = `<tr><td colspan="6" style="text-align:center;padding:30px;color:var(--adm-gold);"><i class="fa-solid fa-spinner fa-spin"></i> Loading Sanctuary Users & Admins...</td></tr>`;
            }

            fetch('api/manage_profile.php?action=get_all_users')
                .then(r => r.json())
                .then(res => {
                    if (!res.success) {
                        if (tableBody) tableBody.innerHTML = `<tr><td colspan="6" style="text-align:center;padding:20px;color:#F87171;">Failed to load users: ${res.message}</td></tr>`;
                        return;
                    }
                    allAdminsCache = res.admins || [];
                    allUsersCache = res.users || [];
                    currentAdminId = res.current_admin_id || currentAdminId;

                    // Update stats counters
                    const countAll = document.getElementById('count-all-accounts');
                    const countAdmins = document.getElementById('count-admins');
                    const countUsers = document.getElementById('count-users');
                    if (countAll) countAll.textContent = (allAdminsCache.length + allUsersCache.length);
                    if (countAdmins) countAdmins.textContent = allAdminsCache.length;
                    if (countUsers) countUsers.textContent = allUsersCache.length;

                    renderUsersList();
                })
                .catch(err => {
                    if (tableBody) tableBody.innerHTML = `<tr><td colspan="6" style="text-align:center;padding:20px;color:#F87171;">Error loading users: ${err.message}</td></tr>`;
                });
        }

        function renderUsersList() {
            const tableBody = document.getElementById('adm-all-users-tbody');
            if (!tableBody) return;

            const searchQuery = (document.getElementById('user-search-input')?.value || '').toLowerCase().trim();

            let combined = [];
            if (activeFilter === 'all' || activeFilter === 'admins') {
                allAdminsCache.forEach(a => combined.push({ ...a, _is_admin: true }));
            }
            if (activeFilter === 'all' || activeFilter === 'users') {
                allUsersCache.forEach(u => combined.push({ ...u, _is_admin: false }));
            }

            if (searchQuery) {
                combined = combined.filter(acc => {
                    return (acc.full_name && acc.full_name.toLowerCase().includes(searchQuery)) ||
                           (acc.username && acc.username.toLowerCase().includes(searchQuery)) ||
                           (acc.email && acc.email.toLowerCase().includes(searchQuery)) ||
                           (acc.phone && acc.phone.toLowerCase().includes(searchQuery));
                });
            }

            if (combined.length === 0) {
                tableBody.innerHTML = `<tr><td colspan="6" style="text-align:center;padding:32px;color:#94A3B8;"><i class="fa-solid fa-users-slash" style="font-size:24px;margin-bottom:8px;display:block;"></i> No accounts match your filter or search query.</td></tr>`;
                return;
            }

            let html = '';
            combined.forEach(acc => {
                const isAdmin = !!acc._is_admin;
                const isCurrentSelf = isAdmin && (acc.id === currentAdminId);
                const roleBadge = isAdmin ? 
                    `<span class="adm-role-pill admin"><i class="fa-solid fa-shield-halved"></i> ${acc.role || 'Admin'}</span>` : 
                    `<span class="adm-role-pill user"><i class="fa-solid fa-user"></i> Guest / User</span>`;

                const usernameDisplay = acc.username ? `@${acc.username}` : (acc.email ? `@${acc.email.split('@')[0]}` : '—');
                const lastLoginText = acc.last_login ? acc.last_login.replace(' ', '<br><small style="color:#64748B;">') + '</small>' : '<span style="color:#64748B;">Never</span>';

                html += `
                <tr>
                    <td style="width: 50px;">
                        ${renderAvatarElement(acc.avatar_url, acc.full_name, 38)}
                    </td>
                    <td>
                        <strong style="color:#FFFFFF;display:block;font-size:13.5px;">${acc.full_name || 'Unnamed'} ${isCurrentSelf ? '<span style="font-size:10px;background:rgba(197,160,89,0.3);color:var(--adm-gold);padding:2px 6px;border-radius:4px;margin-left:4px;">YOU</span>' : ''}</strong>
                        <span style="font-family:monospace;font-size:11.5px;color:var(--adm-gold);">${usernameDisplay}</span>
                    </td>
                    <td>${roleBadge}</td>
                    <td>
                        <div style="font-size:12.5px;color:#E2E8F0;">${acc.email || '<span style="color:#64748B;">No Email</span>'}</div>
                        ${acc.phone ? `<div style="font-size:11px;color:#94A3B8;"><i class="fa-solid fa-phone" style="font-size:9px;"></i> ${acc.phone}</div>` : ''}
                    </td>
                    <td style="font-size:11.5px;">${lastLoginText}</td>
                    <td style="text-align: right; white-space: nowrap;">
                        <button type="button" class="adm-btn-action gold btn-edit-user" data-id="${acc.id}" data-type="${isAdmin ? 'admin' : 'user'}" style="padding:6px 12px;font-size:11.5px;margin-right:6px;" title="Edit password, username, photo">
                            <i class="fa-solid fa-user-pen"></i> <span>Edit</span>
                        </button>
                        ${!isCurrentSelf ? `
                        <button type="button" class="adm-btn-action outline btn-del-user" data-id="${acc.id}" data-type="${isAdmin ? 'admin' : 'user'}" data-name="${acc.full_name || acc.username}" style="padding:6px 10px;font-size:11.5px;color:#F87171;border-color:rgba(239,68,68,0.35);" title="Delete Account">
                            <i class="fa-solid fa-trash-can"></i>
                        </button>` : ''}
                    </td>
                </tr>`;
            });

            tableBody.innerHTML = html;

            // Attach Edit & Delete button handlers
            tableBody.querySelectorAll('.btn-edit-user').forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = parseInt(this.getAttribute('data-id'), 10);
                    const type = this.getAttribute('data-type');
                    window.openUserEditor(type, id);
                });
            });

            tableBody.querySelectorAll('.btn-del-user').forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = parseInt(this.getAttribute('data-id'), 10);
                    const type = this.getAttribute('data-type');
                    const name = this.getAttribute('data-name');
                    if (confirm(`Are you sure you want to delete account "${name}"? This action cannot be undone.`)) {
                        deleteUserAccount(type, id);
                    }
                });
            });
        }

        // Filter pills click
        document.querySelectorAll('.adm-user-filter-pill').forEach(pill => {
            pill.addEventListener('click', function() {
                document.querySelectorAll('.adm-user-filter-pill').forEach(p => p.classList.remove('is-active'));
                this.classList.add('is-active');
                activeFilter = this.getAttribute('data-filter') || 'all';
                renderUsersList();
            });
        });

        // Search filter input
        const userSearchInput = document.getElementById('user-search-input');
        if (userSearchInput) {
            userSearchInput.addEventListener('input', () => renderUsersList());
        }

        // 7. Add & Edit User Sub-Drawer Engine
        window.openUserEditor = function(type = 'user', id = 0) {
            const drawer = document.getElementById('user-edit-drawer');
            const listSection = document.getElementById('user-list-section');
            if (!drawer || !listSection) return;

            listSection.style.display = 'none';
            drawer.style.display = 'block';

            const form = document.getElementById('form-user-edit');
            if (form) form.reset();

            const titleEl = document.getElementById('user-edit-drawer-title');
            const idInput = document.getElementById('edit-user-id');
            const typeInput = document.getElementById('edit-user-type');
            const roleGroup = document.getElementById('edit-user-role-group');
            const phoneGroup = document.getElementById('edit-user-phone-group');
            const passHint = document.getElementById('edit-user-pass-hint');

            if (idInput) idInput.value = id;
            if (typeInput) typeInput.value = type;

            if (type === 'admin') {
                if (roleGroup) roleGroup.style.display = 'block';
                if (phoneGroup) phoneGroup.style.display = 'none';
            } else {
                if (roleGroup) roleGroup.style.display = 'none';
                if (phoneGroup) phoneGroup.style.display = 'block';
            }

            if (id > 0) {
                // Editing existing account
                const sourceList = (type === 'admin') ? allAdminsCache : allUsersCache;
                const acc = sourceList.find(a => a.id === id);
                if (titleEl) titleEl.innerHTML = `<i class="fa-solid fa-user-pen" style="color:var(--adm-gold);"></i> Edit Account: <span style="color:#FFF;">${acc ? acc.full_name : 'Account'}</span>`;
                if (passHint) passHint.textContent = 'Leave empty to keep existing password. Enter a new password to reset it.';

                if (acc) {
                    const nameInp = document.getElementById('edit-user-fullname');
                    const userInp = document.getElementById('edit-user-username');
                    const emailInp = document.getElementById('edit-user-email');
                    const phoneInp = document.getElementById('edit-user-phone');
                    const roleInp = document.getElementById('edit-user-role');

                    if (nameInp) nameInp.value = acc.full_name || '';
                    if (userInp) userInp.value = acc.username || '';
                    if (emailInp) emailInp.value = acc.email || '';
                    if (phoneInp) phoneInp.value = acc.phone || '';
                    if (roleInp) roleInp.value = acc.role || 'Concierge Lead';

                    updateEditUserAvatarPreview(acc.avatar_url, acc.full_name);
                }
            } else {
                // Creating new account
                if (titleEl) titleEl.innerHTML = `<i class="fa-solid fa-user-plus" style="color:var(--adm-gold);"></i> Create New Account`;
                if (passHint) passHint.textContent = 'Required (at least 6 characters).';
                updateEditUserAvatarPreview('', 'New User');
            }
        };

        window.closeUserEditor = function() {
            const drawer = document.getElementById('user-edit-drawer');
            const listSection = document.getElementById('user-list-section');
            if (drawer) drawer.style.display = 'none';
            if (listSection) listSection.style.display = 'block';
        };

        const btnAddNewUser = document.getElementById('btn-add-new-user');
        if (btnAddNewUser) {
            btnAddNewUser.addEventListener('click', function(e) {
                e.preventDefault();
                window.openUserEditor('admin', 0);
            });
        }

        const btnCancelEditUser = document.getElementById('btn-cancel-edit-user');
        if (btnCancelEditUser) {
            btnCancelEditUser.addEventListener('click', function(e) {
                e.preventDefault();
                window.closeUserEditor();
            });
        }

        // Account Type Toggle within Editor (Admin vs User)
        const editUserTypeSelect = document.getElementById('edit-user-type-selector');
        if (editUserTypeSelect) {
            editUserTypeSelect.addEventListener('change', function() {
                const val = this.value;
                const hiddenType = document.getElementById('edit-user-type');
                if (hiddenType) hiddenType.value = val;
                const roleGroup = document.getElementById('edit-user-role-group');
                const phoneGroup = document.getElementById('edit-user-phone-group');
                if (val === 'admin') {
                    if (roleGroup) roleGroup.style.display = 'block';
                    if (phoneGroup) phoneGroup.style.display = 'none';
                } else {
                    if (roleGroup) roleGroup.style.display = 'none';
                    if (phoneGroup) phoneGroup.style.display = 'block';
                }
            });
        }

        function updateEditUserAvatarPreview(avatarUrl, name) {
            const container = document.getElementById('edit-user-avatar-preview');
            if (!container) return;
            container.innerHTML = renderAvatarElement(avatarUrl, name, 80);
            const removeBtn = document.getElementById('btn-remove-edit-user-avatar');
            if (removeBtn) {
                removeBtn.style.display = (avatarUrl && avatarUrl.trim()) ? 'inline-flex' : 'none';
            }
        }

        // Edit User Avatar file input change
        const editUserFileInput = document.getElementById('edit-user-avatar-file');
        if (editUserFileInput) {
            editUserFileInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const container = document.getElementById('edit-user-avatar-preview');
                        if (container) {
                            container.innerHTML = `<img src="${e.target.result}" style="width:80px;height:80px;object-fit:cover;border-radius:50%;border:2px solid var(--adm-gold);display:block;">`;
                        }
                        const removeFlag = document.getElementById('edit-user-remove-avatar-flag');
                        if (removeFlag) removeFlag.value = '0';
                        const presetFlag = document.getElementById('edit-user-avatar-preset');
                        if (presetFlag) presetFlag.value = '';
                        document.querySelectorAll('.edit-user-preset-btn').forEach(b => b.classList.remove('is-active'));
                        const removeBtn = document.getElementById('btn-remove-edit-user-avatar');
                        if (removeBtn) removeBtn.style.display = 'inline-flex';
                    };
                    reader.readAsDataURL(this.files[0]);
                }
            });
        }

        // Edit User Remove Avatar button
        const btnRemoveEditUserAvatar = document.getElementById('btn-remove-edit-user-avatar');
        if (btnRemoveEditUserAvatar) {
            btnRemoveEditUserAvatar.addEventListener('click', function(e) {
                e.preventDefault();
                const removeFlag = document.getElementById('edit-user-remove-avatar-flag');
                if (removeFlag) removeFlag.value = '1';
                const presetFlag = document.getElementById('edit-user-avatar-preset');
                if (presetFlag) presetFlag.value = '';
                if (editUserFileInput) editUserFileInput.value = '';
                document.querySelectorAll('.edit-user-preset-btn').forEach(b => b.classList.remove('is-active'));
                const nameInp = document.getElementById('edit-user-fullname');
                updateEditUserAvatarPreview('', nameInp ? nameInp.value : 'User');
            });
        }

        // Edit User Preset Avatars
        document.querySelectorAll('.edit-user-preset-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const presetKey = this.getAttribute('data-preset');
                document.querySelectorAll('.edit-user-preset-btn').forEach(b => b.classList.remove('is-active'));
                this.classList.add('is-active');

                const presetFlag = document.getElementById('edit-user-avatar-preset');
                if (presetFlag) presetFlag.value = presetKey;
                const removeFlag = document.getElementById('edit-user-remove-avatar-flag');
                if (removeFlag) removeFlag.value = '0';
                if (editUserFileInput) editUserFileInput.value = '';

                const container = document.getElementById('edit-user-avatar-preview');
                if (container) {
                    container.innerHTML = renderAvatarElement(presetKey, 'User', 80);
                }
                const removeBtn = document.getElementById('btn-remove-edit-user-avatar');
                if (removeBtn) removeBtn.style.display = 'inline-flex';
            });
        });

        // Submit User Edit Form
        const formUserEdit = document.getElementById('form-user-edit');
        if (formUserEdit) {
            formUserEdit.addEventListener('submit', function(e) {
                e.preventDefault();
                const saveBtn = document.getElementById('btn-save-edit-user');
                const origText = saveBtn ? saveBtn.innerHTML : 'Save';
                if (saveBtn) {
                    saveBtn.disabled = true;
                    saveBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving Account...';
                }

                const formData = new FormData(formUserEdit);
                formData.append('action', 'save_user');

                fetch('api/manage_profile.php', {
                    method: 'POST',
                    body: formData
                })
                .then(r => r.json())
                .then(res => {
                    if (saveBtn) {
                        saveBtn.disabled = false;
                        saveBtn.innerHTML = origText;
                    }
                    if (res.success) {
                        if (typeof window.showAdmToast === 'function') {
                            window.showAdmToast(res.message, 'success');
                        } else {
                            alert(res.message);
                        }
                        window.closeUserEditor();
                        loadAllUsers();

                        // If edited self, reload my profile
                        const editedId = parseInt(document.getElementById('edit-user-id')?.value, 10);
                        const editedType = document.getElementById('edit-user-type')?.value;
                        if (editedType === 'admin' && editedId === currentAdminId) {
                            loadMyProfile();
                        }
                    } else {
                        if (typeof window.showAdmToast === 'function') {
                            window.showAdmToast(res.message || 'Operation failed', 'error');
                        } else {
                            alert(res.message || 'Operation failed');
                        }
                    }
                })
                .catch(err => {
                    if (saveBtn) {
                        saveBtn.disabled = false;
                        saveBtn.innerHTML = origText;
                    }
                    alert('Error saving user: ' + err.message);
                });
            });
        }

        function deleteUserAccount(type, id) {
            const formData = new FormData();
            formData.append('action', 'delete_user');
            formData.append('target_type', type);
            formData.append('target_id', id);

            fetch('api/manage_profile.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    if (typeof window.showAdmToast === 'function') {
                        window.showAdmToast(res.message, 'success');
                    } else {
                        alert(res.message);
                    }
                    loadAllUsers();
                } else {
                    if (typeof window.showAdmToast === 'function') {
                        window.showAdmToast(res.message || 'Delete failed', 'error');
                    } else {
                        alert(res.message || 'Delete failed');
                    }
                }
            })
            .catch(err => alert('Network error: ' + err.message));
        }

    });
})();
