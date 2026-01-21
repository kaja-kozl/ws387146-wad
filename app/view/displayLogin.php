<style>
    .invalid-input {
        border: 2px solid red;
    }
</style>

<h1>Login</h1>
<?php 
// Creates a new UserModel if none is passed from the controller
if (!isset($model)) {
    $model = new \app\model\UserModel();
}

$form = \app\core\form\Form::begin('', "post"); 
?>
    <?php echo $form->field($model, 'email')->emailField()?>
    <?php echo $form->field($model, 'password')->passwordField() ?>
    <input type="submit" value="Login">
<?php $form->end(); ?>

<h1>Create Account</h1>
<?php $form = \app\core\form\Form::begin('', "post"); ?>
    <?php echo $form->field($model, 'email') ?>
    <?php echo $form->field($model, 'password')->passwordField() ?>
    <?php echo $form->field($model, 'confirmPassword')->passwordField() ?>
    <?php echo $form->field($model, 'firstName') ?>
    <?php echo $form->field($model, 'lastName') ?>
    <?php echo $form->field($model, 'jobTitle') ?>
    <input type="submit" value="Create Account">
<?php $form->end(); ?>
