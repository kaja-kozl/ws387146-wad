<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<div class="container row">
    <div class="col">
        <h1>GrayRock Logo</h1>
        <p>TRAINING CENTRE</p>
        <p>Vague description of company aims</p>
    </div>
    <div class="col">
        <h1>Sign In</h1>
        <?php 
            // Creates a new UserModel if none is passed from the controller
            if (!isset($model)) {
                $model = new \app\model\UserModel();
            }

            $form = \app\core\form\Form::begin('', "post"); 
            ?>
                <?php echo $form->field($model, 'email')->emailField()?>
                <?php echo $form->field($model, 'password')->passwordField() ?>
                <input type="submit" value="Sign In">
        <?php $form->end(); ?>
    </div>
</div>