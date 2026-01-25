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


