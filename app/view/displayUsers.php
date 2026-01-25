<?php 
// Creates a new UserModel if none is passed from the controller
if (!isset($newUser)) {
    $newUser = new \app\model\UserModel();
}
?>

<h1>Users</h1>

<h1>Edit a user</h1>

<h1>Delete a user</h1>

<h1>Create User</h1>
<?php $form = \app\core\form\Form::begin('', "post"); ?>
    <?php echo $form->field($newUser, 'email') ?>
    <?php echo $form->field($newUser, 'password')->passwordField() ?>
    <?php echo $form->field($newUser, 'confirmPassword')->passwordField() ?>
    <?php echo $form->field($newUser, 'firstName') ?>
    <?php echo $form->field($newUser, 'lastName') ?>
    <?php echo $form->field($newUser, 'jobTitle') ?>
    <?php echo $form->field($newUser, 'accessLevel')->dropDownField([
        'user' => 'User',
        'admin' => 'Admin',
        'superuser' => 'Super User'
    ]) ?>
    <input type="submit" value="Create Account">
<?php $form->end(); ?>