document.addEventListener('DOMContentLoaded', () => {

    const { currentUserUid, currentUserData } = window.GR;

    // ── Flash messages ──
    function showFlash(type, message) {
        document.querySelectorAll('.site-flash').forEach(e => e.remove());
        const flash = document.createElement('div');
        flash.className = `alert alert-${type} alert-dismissible fade show site-flash`;
        flash.innerHTML = `${message}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>`;
        document.body.appendChild(flash);
        setTimeout(() => flash.remove(), 3500);
    }

    // ── Populate shared edit modal before it opens ──
    const editModal = document.querySelector('#editProfileModal');
    editModal?.addEventListener('show.bs.modal', function(e) {
        const uid  = e.relatedTarget?.dataset.uid;
        if (!uid) return;

        const user = allUsers.find(u => u.uid === uid) ||
            (uid === currentUserUid ? currentUserData : null);

        if (!user) return;

        const f = this.querySelector('.edit-user-form');
        f.querySelector('[name="uid"]').value             = user.uid;
        f.querySelector('[name="email"]').value           = user.email;
        f.querySelector('[name="firstName"]').value       = user.firstName;
        f.querySelector('[name="lastName"]').value        = user.lastName;
        f.querySelector('[name="jobTitle"]').value        = user.jobTitle;
        f.querySelector('[name="accessLevel"]').value     = user.accessLevel;
        f.querySelector('[name="password"]').value        = 'Password';
        f.querySelector('[name="confirmPassword"]').value = '';

        // Reset confirmPassword visibility
        const confirmGroup = f.querySelector('[name="confirmPassword"]')?.closest('.form-group');
        if (confirmGroup) confirmGroup.style.display = 'none';
    });

    // ── confirmPassword toggle ──
    const editForm = document.querySelector('.edit-user-form');
    if (editForm) {
        const passwordField = editForm.querySelector('[name="password"]');
        const confirmGroup  = editForm.querySelector('[name="confirmPassword"]')?.closest('.form-group');
        if (passwordField && confirmGroup) {
            confirmGroup.style.display = 'none';
            passwordField.addEventListener('input', function() {
                confirmGroup.style.display = this.value !== 'Password' ? 'block' : 'none';
            });
        }
    }

    // ── Edit user form submission ──
    editModal?.querySelector('.edit-user-form')?.addEventListener('submit', function(e) {
        e.preventDefault();
        fetch('/editUser', { method: 'POST', body: new FormData(this) })
            .then(r => r.json())
            .then(data => {
                if (data.flash) showFlash(data.flash.type, data.flash.message);
                if (data.success) {
                    // Update profile card if editing self
                    if (data.user.uid === currentUserUid) {
                        const profileInfo = document.querySelector('#profile-info');
                        if (profileInfo) {
                            profileInfo.querySelector('p:nth-child(1)').lastChild.textContent = data.user.email;
                            profileInfo.querySelector('p:nth-child(2)').lastChild.textContent = data.user.firstName;
                            profileInfo.querySelector('p:nth-child(3)').lastChild.textContent = data.user.lastName;
                            profileInfo.querySelector('p:nth-child(4)').lastChild.textContent = data.user.jobTitle;
                            profileInfo.querySelector('p:nth-child(5)').lastChild.textContent = data.user.accessLevel;
                        }
                        // Keep local currentUserData in sync
                        Object.assign(currentUserData, data.user);
                    }
                    // Update table row if present
                    const userRow = document.querySelector(`tr[data-uid="${data.user.uid}"]`);
                    if (userRow) {
                        userRow.querySelector('td:nth-child(2)').textContent = `${data.user.firstName} ${data.user.lastName}`;
                        userRow.querySelector('td:nth-child(3)').textContent = data.user.email;
                        userRow.querySelector('td:nth-child(4)').textContent = data.user.accessLevel;
                    }
                    // Update allUsers cache
                    const idx = allUsers.findIndex(u => u.uid === data.user.uid);
                    if (idx !== -1) allUsers[idx] = { ...allUsers[idx], ...data.user };

                    bootstrap.Modal.getInstance(editModal)?.hide();
                } else {
                    const errors = data.errors;
                    if (errors && typeof errors === 'object') {
                        Object.entries(errors).forEach(([field, msg]) => {
                            const input = this.querySelector(`[name="${field}"]`);
                            if (input) {
                                input.classList.add('invalid-input');
                                const fb = input.closest('.form-group')?.querySelector('.invalid-feedback');
                                if (fb) fb.textContent = Array.isArray(msg) ? msg[0] : msg;
                            }
                        });
                    } else {
                        showFlash('danger', data.error || 'Failed to update profile.');
                    }
                }
            })
            .catch(() => showFlash('danger', 'Unexpected error occurred.'));
    });

    // ── Lazy-load users ──
    const USERS_PER_PAGE = 8;
    let allUsers    = [];
    let currentPage = 0;

    function renderUsersPage() {
        const tbody = document.querySelector('#users-tbody');
        if (!tbody) return;
        const pages = Math.max(1, Math.ceil(allUsers.length / USERS_PER_PAGE));
        if (currentPage >= pages) currentPage = pages - 1;

        tbody.innerHTML = '';
        allUsers.slice(currentPage * USERS_PER_PAGE, (currentPage + 1) * USERS_PER_PAGE)
            .forEach(user => tbody.appendChild(buildUserRow(user)));

        const prev  = document.querySelector('#users-prev');
        const next  = document.querySelector('#users-next');
        const label = document.querySelector('#users-page-label');
        if (prev)  prev.disabled  = currentPage === 0;
        if (next)  next.disabled  = currentPage >= pages - 1;
        if (label) label.textContent = `Page ${currentPage + 1} of ${pages}`;
    }

    document.querySelector('#users-prev')?.addEventListener('click', () => {
        if (currentPage > 0) { currentPage--; renderUsersPage(); }
    });
    document.querySelector('#users-next')?.addEventListener('click', () => {
        const pages = Math.max(1, Math.ceil(allUsers.length / USERS_PER_PAGE));
        if (currentPage < pages - 1) { currentPage++; renderUsersPage(); }
    });

    const usersTab = document.querySelector('#users-tab');

    function loadUsers() {
        if (usersTab.dataset.loaded) return;
        usersTab.dataset.loaded = '1';
        fetch('/getUsers')
            .then(r => r.json())
            .then(data => {
                document.querySelector('#users-loading')?.remove();
                if (!data.users) {
                    document.querySelector('#users-tbody').innerHTML =
                        '<tr><td colspan="6">Failed to load users.</td></tr>';
                    return;
                }
                allUsers = data.users;
                renderUsersPage();
            })
            .catch(() => {
                const loading = document.querySelector('#users-loading');
                if (loading) loading.textContent = 'Failed to load users.';
            });
    }

    usersTab?.addEventListener('shown.bs.tab', loadUsers);

    // Trigger immediately if users tab is already active on page load
    if (usersTab?.classList.contains('active')) loadUsers();

    // ── Build a table row for a user ──
    function buildUserRow(user) {
        const tr = document.createElement('tr');
        tr.dataset.uid = user.uid;
        tr.innerHTML = `
            <td>${user.uid}</td>
            <td>${user.firstName} ${user.lastName}</td>
            <td>${user.email}</td>
            <td>${user.accessLevel}</td>
            <td>
                <button type="button" class="btn-table-edit"
                    data-bs-toggle="modal"
                    data-bs-target="#editProfileModal"
                    data-uid="${user.uid}">✏️</button>
            </td>
            <td>
                <button type="button" class="btn-table-delete delete-user-btn"
                    data-id="${user.uid}">🗑</button>
            </td>
        `;
        tr.querySelector('.delete-user-btn').addEventListener('click', handleDelete);
        return tr;
    }

    // ── Create user ──
    const createForm = document.querySelector('.create-user-form');
    if (createForm) {
        createForm.addEventListener('submit', function(e) {
            e.preventDefault();
            fetch('/profile', { method: 'POST', body: new FormData(createForm) })
                .then(r => r.json())
                .then(data => {
                    if (data.flash) showFlash(data.flash.type, data.flash.message);
                    if (data.success) {
                        createForm.reset();
                        bootstrap.Modal.getInstance(document.querySelector('#createUserModal'))?.hide();
                        allUsers.push(data.user);
                        currentPage = Math.ceil(allUsers.length / USERS_PER_PAGE) - 1;
                        renderUsersPage();
                    } else if (data.errors) {
                        Object.entries(data.errors).forEach(([field, messages]) => {
                            const input = createForm.querySelector(`[name="${field}"]`);
                            if (input) {
                                input.classList.add('invalid-input');
                                const fb = input.closest('.form-group')?.querySelector('.invalid-feedback');
                                if (fb) fb.textContent = messages[0];
                            }
                        });
                    }
                })
                .catch(() => showFlash('danger', 'Unexpected error occurred.'));
        });

        createForm.querySelectorAll('input, select').forEach(input => {
            input.addEventListener('input', function() {
                this.classList.remove('invalid-input');
                const fb = this.closest('.form-group')?.querySelector('.invalid-feedback');
                if (fb) fb.textContent = '';
            });
        });
    }

    // ── Delete user ──
    async function handleDelete() {
        const userId = this.dataset.id;

        if (!confirm('This will permanently delete the user. Their courses will be reassigned to you.')) {
            return;
        }

        fetch('/delUser', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'uid=' + encodeURIComponent(userId)
        })
        .then(r => r.json())
        .then(data => {
            if (data.flash) showFlash(data.flash.type, data.flash.message);
            if (data.success === true) {
                allUsers = allUsers.filter(u => u.uid !== userId);
                if (currentPage > 0 && currentPage >= Math.ceil(allUsers.length / USERS_PER_PAGE)) {
                    currentPage--;
                }
                renderUsersPage();
            } else {
                showFlash('danger', 'Failed to delete user.');
            }
        })
        .catch(() => showFlash('danger', 'Something went wrong.'));
    }

});