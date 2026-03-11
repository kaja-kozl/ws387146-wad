<?php 

use app\core\Application;
$this->title = 'Profile'; 

$currentUser = Application::$app->user;

function renderEditUserModal($editedUser) { ?>
    <div class="modal fade" id="editProfileModal-<?= $editedUser->uid ?>" tabindex="-1" aria-labelledby="editProfileModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title" id="editProfileModalLabel">Edit Profile</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <?php $form = \app\core\form\Form::begin('/editUser', "post", ['class' => 'edit-user-form']); ?>
                <input type="hidden" name="uid" value="<?= $editedUser->uid ?>">
                <?php echo $form->field($editedUser, 'email')->setValue($editedUser->email); ?>
                <?php echo $form->field($editedUser, 'password')->passwordField()->setValue("Password"); ?>
                <?php echo $form->field($editedUser, 'confirmPassword')->passwordField(); ?>
                <?php echo $form->field($editedUser, 'firstName')->setValue($editedUser->firstName); ?>
                <?php echo $form->field($editedUser, 'lastName')->setValue($editedUser->lastName); ?>
                <?php $jobTitle_field = $form->field($editedUser, 'jobTitle')->dropDownField([
                    'banking_and_finance' => 'Banking & Finance',
                    'biohazard_remidiation' => 'Bio-hazard Remidiation',
                    'human_resources' => 'Human Resources',
                    'hypnotisation' => 'Hypnotisation',
                    'intern' => 'Intern',
                    'legal' => 'Legal',
                    'management' => 'Management',
                    'mass_surveillance' => 'Mass Surveillance',
                    'project_management' => 'Project Management',
                    'ritualistic_sacrifice' => 'Ritualistic Sacrifice',
                    'sales' => 'Sales',
                    'software_development' => 'Software Development'
                ])->setValue($editedUser->jobTitle); 
                
                if ($editedUser->accessLevel == 'user') {
                    $jobTitle_field->readonly();
                }
                echo $jobTitle_field;
                ?>
                <?php $accessLevel_field = $form->field($editedUser, 'accessLevel')->dropDownField([
                    'user' => 'User',
                    'admin' => 'Admin',
                    'super_user' => 'Super User'
                ])->setValue($editedUser->accessLevel); 
                
                if ($editedUser->accessLevel !== 'super_user') {
                    $accessLevel_field->readonly();
                }
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
<?php } ?>

<link rel="stylesheet" href="/css/displayUsers.css">

<?php renderEditUserModal($currentUser); ?>

<div class="users-wrap">

    <ul class="nav nav-tabs" id="profileTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="profile-tab" data-bs-toggle="tab"
                data-bs-target="#profile" type="button" role="tab">Your Profile</button>
        </li>
        <?php if ($currentUser->accessLevel !== 'user'): ?>
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
                <button class="btn-edit-profile" data-bs-toggle="modal" data-bs-target="#editProfileModal-<?= $currentUser->uid ?>">Edit Profile</button>
            </div>
        </div>

        <!-- ── Other Users tab ── -->
        <?php if ($currentUser->accessLevel !== 'user'): ?>
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
                        <tbody id="users-tbody">
                            <!-- Rows loaded lazily when tab is first opened -->
                        </tbody>
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
<div class="modal fade" id="createUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php
                if (!isset($model)) { $model = new \app\model\UserModel(); }
                $newUser = $model;
                ?>
                <?php $form = \app\core\form\Form::begin('/profile', "post", ['class' => 'create-user-form']); ?>
                    <?php echo $form->field($newUser, 'email') ?>
                    <?php echo $form->field($newUser, 'password')->passwordField() ?>
                    <?php echo $form->field($newUser, 'confirmPassword')->passwordField() ?>
                    <?php echo $form->field($newUser, 'firstName') ?>
                    <?php echo $form->field($newUser, 'lastName') ?>
                    <?php echo $form->field($newUser, 'jobTitle')->dropDownField([
                        'banking_and_finance' => 'Banking & Finance',
                        'biohazard_remidiation' => 'Bio-hazard Remidiation',
                        'human_resources' => 'Human Resources',
                        'hypnotisation' => 'Hypnotisation',
                        'intern' => 'Intern',
                        'legal' => 'Legal',
                        'management' => 'Management',
                        'mass_surveillance' => 'Mass Surveillance',
                        'project_management' => 'Project Management',
                        'ritualistic_sacrifice' => 'Ritualistic Sacrifice',
                        'sales' => 'Sales',
                        'software_development' => 'Software Development'
                    ]) ?>
                    <?php echo $form->field($newUser, 'accessLevel')->dropDownField([
                        'user' => 'User',
                        'admin' => 'Admin',
                        'super_user' => 'Super User'
                    ]) ?>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <input type="submit" class="btn btn-primary" value="Create User">
                    </div>
                <?php $form->end(); ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {

    // ── Flash messages ──
    function showFlash(type, message) {
        document.querySelectorAll('.site-flash').forEach(e => e.remove());
        const flash = document.createElement('div');
        flash.className = `alert alert-${type} alert-dismissible fade show site-flash`;
        flash.innerHTML = `${message}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>`;
        document.body.appendChild(flash);
        setTimeout(() => flash.remove(), 3500);
    }

    // ── confirmPassword toggle in edit modals ──
    document.querySelectorAll('.edit-user-form').forEach(form => {
        const passwordField = form.querySelector('[name="password"]');
        const confirmField  = form.querySelector('[name="confirmPassword"]')?.closest('.form-group');
        if (passwordField && confirmField) {
            confirmField.style.display = 'none';
            passwordField.addEventListener('input', function() {
                confirmField.style.display = this.value !== 'Password' ? 'block' : 'none';
            });
        }
    });

    // ── Edit user form submissions ──
    document.querySelectorAll('.edit-user-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            fetch('/editUser', { method: 'POST', body: new FormData(form) })
                .then(r => r.json())
                .then(data => {
                    if (data.flash) showFlash(data.flash.type, data.flash.message);
                    if (data.success) {
                        const uidInput = form.querySelector('[name="uid"]');
                        if (uidInput) {
                            const profileInfo = document.querySelector('#profile-info');
                            if (profileInfo && data.user.uid === <?= json_encode($currentUser->uid) ?>) {
                                profileInfo.innerHTML = `
                                    <p><small class="text-uppercase fw-bold text-muted" style="font-size:0.7rem;letter-spacing:0.06em;">Email</small><br>${data.user.email}</p>
                                    <p><small class="text-uppercase fw-bold text-muted" style="font-size:0.7rem;letter-spacing:0.06em;">First Name</small><br>${data.user.firstName}</p>
                                    <p><small class="text-uppercase fw-bold text-muted" style="font-size:0.7rem;letter-spacing:0.06em;">Last Name</small><br>${data.user.lastName}</p>
                                    <p><small class="text-uppercase fw-bold text-muted" style="font-size:0.7rem;letter-spacing:0.06em;">Job Title</small><br>${data.user.jobTitle}</p>
                                    <p><small class="text-uppercase fw-bold text-muted" style="font-size:0.7rem;letter-spacing:0.06em;">Access Level</small><br>${data.user.accessLevel}</p>
                                    <button class="btn-edit-profile"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editProfileModal-${data.user.uid}">Edit Profile</button>
                                `;
                            }
                            const userRow = document.querySelector(`tr[data-uid="${data.user.uid}"]`);
                            if (userRow) {
                                userRow.querySelector('td:nth-child(2)').textContent = `${data.user.firstName} ${data.user.lastName}`;
                                userRow.querySelector('td:nth-child(3)').textContent = data.user.email;
                                userRow.querySelector('td:nth-child(4)').textContent = data.user.accessLevel;
                            }
                        }
                        const modalEl = form.closest('.modal');
                        bootstrap.Modal.getInstance(modalEl)?.hide();
                    } else {
                        const errors = data.errors;
                        if (errors && typeof errors === 'object') {
                            Object.entries(errors).forEach(([field, msg]) => {
                                const input = form.querySelector(`[name="${field}"]`);
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
    });

    // ── Lazy-load users on tab click ──
    const USERS_PER_PAGE = 8;
    let allUsers = [];
    let currentPage = 0;

    function renderUsersPage() {
        const tbody = document.querySelector('#users-tbody');
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
    usersTab?.addEventListener('shown.bs.tab', function() {
        if (usersTab.dataset.loaded) return;
        usersTab.dataset.loaded = '1';
        fetch('/getUsers')
            .then(r => r.json())
            .then(data => {
                document.querySelector('#users-loading')?.remove();
                allUsers = data.users || [];
                renderUsersPage();
            })
            .catch(() => {
                document.querySelector('#users-loading').textContent = 'Failed to load users.';
            });
    });

    // ── Build a table row for a user ──
    function buildUserRow(user) {
        const tr = document.createElement('tr');
        tr.dataset.uid = user.uid;
        tr.innerHTML = `
            <td>${user.uid}</td>
            <td>
                ${user.firstName} ${user.lastName}
                <span class="user-email">${user.email}</span>
                <span class="user-access">${user.accessLevel}</span>
            </td>
            <td>${user.email}</td>
            <td>${user.accessLevel}</td>
            <td>
                <button type="button" class="btn-table-edit"
                    data-bs-toggle="modal"
                    data-bs-target="#editProfileModal-${user.uid}">✏️</button>
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
        const btn    = this;
        const userId = btn.dataset.id;

        if (!await grConfirm('This will permanently delete the user. Their courses will be reassigned to you.', 'Delete User')) {
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