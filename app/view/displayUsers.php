<?php
use app\core\Application;
use app\model\UserModel;
$this->title = 'Profile';
$currentUser = Application::$app->user;
?>

<link rel="stylesheet" href="/css/displayUsers.css">

<!-- Shared edit modal (for self and other users if user has permission) -->
<div class="modal fade" id="editProfileModal" tabindex="-1"
    aria-labelledby="editProfileModalLabel" aria-hidden="true">
<div class="modal-dialog" role="document">
    <div class="modal-content">
    <div class="modal-header">
        <h5 class="modal-title" id="editProfileModalLabel">Edit Profile</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close dialog"></button>
    </div>
    <div class="modal-body">
        <?php
        // Creates a form out of the modal
        $dummyUser = new UserModel();
        $form = \app\core\form\Form::begin('/editUser', "post", [
            'class'      => 'edit-user-form',
            'aria-label' => 'Edit user profile',
            'novalidate' => 'novalidate',
        ]);
        ?>
            <!-- UID is sent for identification, fields are filled in based on the selected user -->
            <input type="hidden" name="uid" aria-hidden="true">
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
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" aria-label="Cancel editing">Cancel</button>
                <input type="submit" class="btn btn-primary" value="Update" aria-label="Save profile changes">
            </div>
        <?php $form->end(); ?>
    </div>
    </div>
</div>
</div>

<!-- Enables there to be multiple tabs on the same page -->
<div class="users-wrap">

    <ul class="nav nav-tabs" id="profileTabs" role="tablist" aria-label="Profile sections">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="profile-tab"
                data-bs-toggle="tab" data-bs-target="#profile"
                type="button" role="tab"
                aria-controls="profile" aria-selected="true">Your Profile</button>
        </li>
        
        <!-- Only show the "Other Users" tab if the user has permission to list users -->
        <?php if ($canListUsers): ?>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="users-tab"
                data-bs-toggle="tab" data-bs-target="#users"
                type="button" role="tab"
                aria-controls="users" aria-selected="false">Other Users</button>
        </li>
        <?php endif; ?>
    </ul>

    <div class="tab-content tab-content-fill mt-2">

        <!-- Your Profile tab, viewable to all users -->
        <div class="tab-pane fade show active tab-pane-fill profile-tab-pane"
            id="profile" role="tabpanel" aria-labelledby="profile-tab">
            <div id="profile-info" class="profile-card" aria-label="Your profile information">
                <p><small class="text-uppercase fw-bold text-muted" style="font-size:0.7rem;">Email</small><br><?= htmlspecialchars($currentUser->email) ?></p>
                <p><small class="text-uppercase fw-bold text-muted" style="font-size:0.7rem;">First Name</small><br><?= htmlspecialchars($currentUser->firstName) ?></p>
                <p><small class="text-uppercase fw-bold text-muted" style="font-size:0.7rem;">Last Name</small><br><?= htmlspecialchars($currentUser->lastName) ?></p>
                <p><small class="text-uppercase fw-bold text-muted" style="font-size:0.7rem;">Job Title</small><br><?= htmlspecialchars($currentUser->jobTitle) ?></p>
                <p><small class="text-uppercase fw-bold text-muted" style="font-size:0.7rem;">Access Level</small><br><?= htmlspecialchars($currentUser->accessLevel) ?></p>
                
                <!-- Opens the edit profile modal (see modal code above) through the JS event listener to btn-edit-profile -->
                <button class="btn-edit-profile"
                    data-bs-toggle="modal"
                    data-bs-target="#editProfileModal"
                    data-uid="<?= htmlspecialchars($currentUser->uid) ?>"
                    aria-label="Edit your profile">Edit Profile</button>
            </div>
        </div>

        <!-- Other Users tab -->
        <?php if ($canListUsers): ?>
        <div class="tab-pane fade tab-pane-fill"
            id="users" role="tabpanel" aria-labelledby="users-tab" aria-live="polite">
            <div class="users-header">
                <h2 class="all-users-heading">All Users</h2>
                <button class="btn-create-user"
                    data-bs-toggle="modal"
                    data-bs-target="#createUserModal"
                    aria-label="Create a new user"
                    aria-haspopup="dialog">+ CREATE USER</button>
            </div>
            <div class="users-table-wrap">
                <div class="users-table-scroll">
                    <table class="users-table" role="grid" aria-label="All users" aria-live="polite">
                        <thead>
                            <tr>
                                <th scope="col">ID</th>
                                <th scope="col">Name</th>
                                <th scope="col">Email</th>
                                <th scope="col">Access Level</th>
                                <th scope="col">Edit</th>
                                <th scope="col">Delete</th>
                            </tr>
                        </thead>
                        <tbody id="users-tbody" aria-live="polite" aria-relevant="additions removals"></tbody>
                    </table>
                    <div id="users-loading" class="users-loading" role="status" aria-live="polite">Loading users…</div>
                </div>
                <div class="users-pagination" role="navigation" aria-label="Users pagination">
                    <button class="page-btn" id="users-prev" aria-label="Previous page">&#9664;</button>
                    <span class="page-label" id="users-page-label" aria-live="polite" aria-atomic="true">Page 1</span>
                    <button class="page-btn" id="users-next" aria-label="Next page">&#9654;</button>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>

<!-- Create User Modal -->
<?php if ($canListUsers): ?>
<div class="modal fade" id="createUserModal" tabindex="-1"
    aria-labelledby="createUserModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createUserModalLabel">Create User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close dialog"></button>
            </div>
            <div class="modal-body">
                <?php
                $newUser = new UserModel();
                $form = \app\core\form\Form::begin('/profile', "post", [
                    'class'      => 'create-user-form',
                    'aria-label' => 'Create new user',
                    'novalidate' => 'novalidate',
                ]);
                echo $form->field($newUser, 'email');
                echo $form->field($newUser, 'password')->passwordField();
                echo $form->field($newUser, 'confirmPassword')->passwordField();
                echo $form->field($newUser, 'firstName');
                echo $form->field($newUser, 'lastName');
                echo $form->field($newUser, 'jobTitle')->dropDownField(UserModel::JOB_TITLES);
                $accessLevel_field = $form->field($newUser, 'accessLevel')->dropDownField(UserModel::ACCESS_LEVELS);
                if (!$canEditAccessLevel) $accessLevel_field->readonly();
                echo $accessLevel_field;
                ?>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" aria-label="Cancel creating user">Cancel</button>
                    <input type="submit" class="btn btn-primary" value="Create User" aria-label="Submit new user">
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