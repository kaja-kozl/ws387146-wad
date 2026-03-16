<?php
use app\core\Application;
use app\model\UserModel;
$this->title = 'Profile';
$currentUser = Application::$app->user;
?>

<link rel="stylesheet" href="/css/displayUsers.css">

<!-- ── Shared edit modal (populated by JS for any user) ── -->
<div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel" aria-hidden="true">
<div class="modal-dialog">
    <div class="modal-content">
    <div class="modal-header">
        <h5 class="modal-title" id="editProfileModalLabel">Edit Profile</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <div class="modal-body">
        <?php
        $dummyUser = new UserModel();
        $form = \app\core\form\Form::begin('/editUser', "post", ['class' => 'edit-user-form']);
        ?>
            <input type="hidden" name="uid">
            <?php echo $form->field($dummyUser, 'email'); ?>
            <?php echo $form->field($dummyUser, 'password')->passwordField()->setValue("Password"); ?>
            <?php echo $form->field($dummyUser, 'confirmPassword')->passwordField(); ?>
            <?php echo $form->field($dummyUser, 'firstName'); ?>
            <?php echo $form->field($dummyUser, 'lastName'); ?>
            <?php
            $jobTitle_field = $form->field($dummyUser, 'jobTitle')->dropDownField(UserModel::JOB_TITLES);
            if (!$canEditJobTitle) $jobTitle_field->readonly();
            echo $jobTitle_field;
            ?>
            <?php
            $accessLevel_field = $form->field($dummyUser, 'accessLevel')->dropDownField(UserModel::ACCESS_LEVELS);
            if (!$canEditAccessLevel) $accessLevel_field->readonly();
            echo $accessLevel_field;
            ?>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <input type="submit" class="btn btn-primary" value="Update">
            </div>
        <?php $form->end(); ?>
    </div>
    </div>
</div>
</div>

<div class="users-wrap">

    <ul class="nav nav-tabs" id="profileTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="profile-tab" data-bs-toggle="tab"
                data-bs-target="#profile" type="button" role="tab">Your Profile</button>
        </li>
        <?php if ($canListUsers): ?>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="users-tab" data-bs-toggle="tab"
                data-bs-target="#users" type="button" role="tab">Other Users</button>
        </li>
        <?php endif; ?>
    </ul>

    <div class="tab-content tab-content-fill mt-2">

        <!-- ── Your Profile tab ── -->
        <div class="tab-pane fade show active tab-pane-fill profile-tab-pane" id="profile" role="tabpanel">
            <div id="profile-info" class="profile-card">
                <p><small class="text-uppercase fw-bold text-muted" style="font-size:0.7rem;letter-spacing:0.06em;">Email</small><br><?= htmlspecialchars($currentUser->email) ?></p>
                <p><small class="text-uppercase fw-bold text-muted" style="font-size:0.7rem;letter-spacing:0.06em;">First Name</small><br><?= htmlspecialchars($currentUser->firstName) ?></p>
                <p><small class="text-uppercase fw-bold text-muted" style="font-size:0.7rem;letter-spacing:0.06em;">Last Name</small><br><?= htmlspecialchars($currentUser->lastName) ?></p>
                <p><small class="text-uppercase fw-bold text-muted" style="font-size:0.7rem;letter-spacing:0.06em;">Job Title</small><br><?= htmlspecialchars($currentUser->jobTitle) ?></p>
                <p><small class="text-uppercase fw-bold text-muted" style="font-size:0.7rem;letter-spacing:0.06em;">Access Level</small><br><?= htmlspecialchars($currentUser->accessLevel) ?></p>
                <button class="btn-edit-profile"
                    data-bs-toggle="modal"
                    data-bs-target="#editProfileModal"
                    data-uid="<?= htmlspecialchars($currentUser->uid) ?>">Edit Profile</button>
            </div>
        </div>

        <!-- ── Other Users tab ── -->
        <?php if ($canListUsers): ?>
        <div class="tab-pane fade tab-pane-fill" id="users" role="tabpanel">
            <div class="users-header">
                <h1 class="all-users-heading">All Users</h1>
                <button class="btn-create-user" data-bs-toggle="modal" data-bs-target="#createUserModal">+ CREATE USER</button>
            </div>
            <div class="users-table-wrap">
                <div class="users-table-scroll">
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Access Level</th>
                                <th>Edit</th>
                                <th>Delete</th>
                            </tr>
                        </thead>
                        <tbody id="users-tbody"></tbody>
                    </table>
                    <div id="users-loading" class="users-loading">Loading users…</div>
                </div>
                <div class="users-pagination">
                    <button class="page-btn" id="users-prev">&#9664;</button>
                    <span class="page-label" id="users-page-label">Page 1</span>
                    <button class="page-btn" id="users-next">&#9654;</button>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>

<!-- ── Create User Modal ── -->
<?php if ($canListUsers): ?>
<div class="modal fade" id="createUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php
                $newUser = new UserModel();
                $form = \app\core\form\Form::begin('/profile', "post", ['class' => 'create-user-form']);
                echo $form->field($newUser, 'email');
                echo $form->field($newUser, 'password')->passwordField();
                echo $form->field($newUser, 'confirmPassword')->passwordField();
                echo $form->field($newUser, 'firstName');
                echo $form->field($newUser, 'lastName');
                echo $form->field($newUser, 'jobTitle')->dropDownField(UserModel::JOB_TITLES);
                echo $form->field($newUser, 'accessLevel')->dropDownField(UserModel::ACCESS_LEVELS);
                ?>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <input type="submit" class="btn btn-primary" value="Create User">
                </div>
                <?php $form->end(); ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', () => {

    const currentUserUid = <?= json_encode($currentUser->uid) ?>;

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

        // Find from loaded users list, or fall back to current user data
        const user = allUsers.find(u => u.uid === uid) || (uid === currentUserUid ? {
            uid:         currentUserUid,
            email:       <?= json_encode($currentUser->email) ?>,
            firstName:   <?= json_encode($currentUser->firstName) ?>,
            lastName:    <?= json_encode($currentUser->lastName) ?>,
            jobTitle:    <?= json_encode($currentUser->jobTitle) ?>,
            accessLevel: <?= json_encode($currentUser->accessLevel) ?>,
        } : null);

        if (!user) return;

        const f = this.querySelector('.edit-user-form');
        f.querySelector('[name="uid"]').value         = user.uid;
        f.querySelector('[name="email"]').value       = user.email;
        f.querySelector('[name="firstName"]').value   = user.firstName;
        f.querySelector('[name="lastName"]').value    = user.lastName;
        f.querySelector('[name="jobTitle"]').value    = user.jobTitle;
        f.querySelector('[name="accessLevel"]').value = user.accessLevel;
        f.querySelector('[name="password"]').value    = 'Password';
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
                            profileInfo.querySelector('p:nth-child(1) br')?.nextSibling && (profileInfo.querySelector('p:nth-child(1)').lastChild.textContent = data.user.email);
                            profileInfo.querySelector('p:nth-child(2)').lastChild.textContent = data.user.firstName;
                            profileInfo.querySelector('p:nth-child(3)').lastChild.textContent = data.user.lastName;
                            profileInfo.querySelector('p:nth-child(4)').lastChild.textContent = data.user.jobTitle;
                            profileInfo.querySelector('p:nth-child(5)').lastChild.textContent = data.user.accessLevel;
                        }
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
</script>