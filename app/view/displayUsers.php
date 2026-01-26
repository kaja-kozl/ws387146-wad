<style>
.invalid-input {
    border-color: #dc3545;
}
</style>

<?php 
// Creates a new UserModel if none is passed from the controller
if (!isset($model)) {
    $model = new \app\model\UserModel();
}
$newUser = $model;
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
<!-- See if this can be editted to take a const enum data type -->
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
    <input type="submit" value="Create Account">
<?php $form->end(); ?>