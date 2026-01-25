<style>
    .invalid-input {
        border: 2px solid red;
    }
</style>

<h1>Create a course</h1>
<?php 
// Creates a new CourseModel if none is passed from the controller
if (!isset($course)) {
    $course = new \app\model\CourseModel();
}
$form = \app\core\form\Form::begin('', "post"); ?>
    <?php echo $form->field($course, 'courseTitle') ?>
    <?php echo $form->field($course, 'courseDesc') ?>
    <?php echo $form->field($course, 'dateTime')->dateField() ?>
    <?php echo $form->field($course, 'maxAttendees') ?>
    <?php echo $form->field($course, 'lecturer') ?>
    <input type="submit" value="Create Course">
<?php $form->end(); ?>

<h1>Edit a course</h1>

<h1>Add a user on a course</h1>

<h1>Remove user from a course</h1>