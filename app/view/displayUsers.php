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
    window.GR = <?= json_encode([
        'currentUserUid'  => $currentUser->uid,
        'currentUserData' => [
            'uid'         => $currentUser->uid,
            'email'       => $currentUser->email,
            'firstName'   => $currentUser->firstName,
            'lastName'    => $currentUser->lastName,
            'jobTitle'    => $currentUser->jobTitle,
            'accessLevel' => $currentUser->accessLevel,
        ]
    ]) ?>;
</script>
<script src="/js/users.js"></script>
