<?php
$this->title = 'Courses';
use app\core\Application;
$currentUser = Application::$app->user;
?>

<link rel="stylesheet" href="/css/displayCourses.css">

<!-- ── Course detail modal ── -->
<div class="modal fade" id="course-modal" tabindex="-1"
    aria-labelledby="modal-title" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-title">Course Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close dialog"></button>
            </div>

            <div id="view-pane">
                <div class="modal-body">
                    <div id="modal-body" aria-live="polite" aria-atomic="true">
                        <div class="modal-spinner" role="status" aria-label="Loading course details">
                            <div class="spinner-wheel"></div>
                        </div>
                    </div>
                    <div id="attendees" style="display:none;" aria-label="Course attendees">
                        <hr>
                        <h6 id="attendees-heading">Attendees</h6>
                        <div class="mb-3 d-flex gap-2" role="group" aria-label="Add attendee">
                            <label for="attendee-select" class="visually-hidden">Select a user to add</label>
                            <select id="attendee-select" class="form-select form-select-sm"
                                aria-label="Select a user to add to this course">
                                <option value="">Select a user to add...</option>
                            </select>
                            <button id="add-attendee" class="btn btn-sm btn-primary"
                                aria-label="Add selected user to course">Add</button>
                        </div>
                        <table class="table table-sm" aria-labelledby="attendees-heading">
                            <thead>
                                <tr>
                                    <th scope="col">Name</th>
                                    <th scope="col">Email</th>
                                    <th scope="col">Job Title</th>
                                    <th scope="col"><span class="visually-hidden">Actions</span></th>
                                </tr>
                            </thead>
                            <tbody id="attendees-tbody" aria-live="polite" aria-relevant="additions removals">
                                <tr id="no-attendees"><td colspan="4"><em>No attendees yet.</em></td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" id="enrol-btn"  class="btn btn-success" style="display:none;" aria-label="Enrol on this course"></button>
                    <button type="button" id="edit-btn"   class="btn btn-primary" style="display:none;" aria-label="Edit this course">Edit</button>
                    <button type="button" id="delete-btn" class="btn btn-danger"  style="display:none;" aria-label="Delete this course">Delete</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" aria-label="Close dialog">Close</button>
                </div>
            </div>

            <div id="edit-pane" style="display:none;" aria-label="Edit course form">
                <form id="edit-form" aria-label="Edit course details" novalidate>
                    <div class="modal-body">
                        <input type="hidden" name="uid" id="edit-uid" aria-hidden="true">
                        <div class="form-group mb-2">
                            <label for="edit-title">Course Title</label>
                            <input type="text" name="courseTitle" id="edit-title" class="form-control"
                                aria-required="true" aria-describedby="err-courseTitle">
                            <div class="invalid-feedback" id="err-courseTitle" role="alert" aria-live="assertive"></div>
                        </div>
                        <div class="form-group mb-2">
                            <label for="edit-desc">Course Description</label>
                            <textarea name="courseDesc" id="edit-desc" class="form-control"
                                aria-required="true" aria-describedby="err-courseDesc"></textarea>
                            <div class="invalid-feedback" id="err-courseDesc" role="alert" aria-live="assertive"></div>
                        </div>
                        <div class="date-row">
                            <div class="form-group mb-2">
                                <label for="edit-start">Start Date &amp; Time</label>
                                <input type="datetime-local" name="startDate" id="edit-start" class="form-control"
                                    aria-required="true" aria-describedby="err-startDate">
                                <div class="invalid-feedback" id="err-startDate" role="alert" aria-live="assertive"></div>
                            </div>
                            <div class="form-group mb-2">
                                <label for="edit-end">End Date &amp; Time</label>
                                <input type="datetime-local" name="endDate" id="edit-end" class="form-control"
                                    aria-required="true" aria-describedby="err-endDate">
                                <div class="invalid-feedback" id="err-endDate" role="alert" aria-live="assertive"></div>
                            </div>
                        </div>
                        <div class="form-group mb-2">
                            <label for="edit-max">Max Attendees</label>
                            <input type="number" name="maxAttendees" id="edit-max" class="form-control"
                                aria-required="true" aria-describedby="err-maxAttendees">
                            <div class="invalid-feedback" id="err-maxAttendees" role="alert" aria-live="assertive"></div>
                        </div>
                        <div class="form-group mb-2">
                            <label for="edit-lecturer">Lecturer</label>
                            <?php if ($canSelectLecturer): ?>
                            <select name="lecturer" id="edit-lecturer" class="form-control"
                                aria-required="true" aria-describedby="err-lecturer">
                                <option value="">Select lecturer</option>
                                <?php foreach ($lecturerOptions ?? [] as $uid => $name): ?>
                                    <option value="<?= htmlspecialchars($uid) ?>"><?= htmlspecialchars($name) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php else: ?>
                                <input type="text" id="edit-lecturer-display" class="form-control"
                                    value="<?= htmlspecialchars($currentUser->firstName . ' ' . $currentUser->lastName) ?>"
                                    readonly aria-label="Lecturer (read only)">
                                <input type="hidden" name="lecturer" id="edit-lecturer"
                                    value="<?= htmlspecialchars($currentUser->uid) ?>" aria-hidden="true">
                            <?php endif; ?>
                            <div class="invalid-feedback" id="err-lecturer" role="alert" aria-live="assertive"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary" aria-label="Save course changes">Save Changes</button>
                        <button type="button" id="cancel-edit" class="btn btn-secondary" aria-label="Cancel editing">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ── Add Course modal ── -->
<div class="modal fade" id="add-course-modal" tabindex="-1"
    aria-labelledby="add-course-title" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="add-course-title">Add Course</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close dialog"></button>
            </div>
            <div class="modal-body" id="course-form">
                <?php if (!isset($course)) { $course = new \app\model\CourseModel(); } ?>
                <?php $form = \app\core\form\Form::begin('/courses', "post", [
                    'class'      => 'create-course-form',
                    'aria-label' => 'Create new course',
                    'novalidate' => 'novalidate',
                ]); ?>
                    <?php echo $form->field($course, 'courseTitle') ?>
                    <?php echo $form->field($course, 'courseDesc')->textareaField() ?>
                    <div class="date-row" role="group" aria-label="Course dates">
                        <?php echo $form->field($course, 'startDate')->dateField() ?>
                        <?php echo $form->field($course, 'endDate')->dateField() ?>
                    </div>
                    <?php echo $form->field($course, 'maxAttendees')->numberField() ?>
                    <?php if ($canSelectLecturer): ?>
                        <?php echo $form->field($course, 'lecturer')->dropDownField($lecturerOptions ?? []) ?>
                    <?php else: ?>
                        <div class="form-group mb-2">
                            <label for="add-lecturer-display">Lecturer</label>
                            <input type="text" id="add-lecturer-display" class="form-control"
                                value="<?= htmlspecialchars($currentUser->firstName . ' ' . $currentUser->lastName) ?>"
                                readonly aria-label="Lecturer (read only)">
                            <input type="hidden" name="lecturer"
                                value="<?= htmlspecialchars($currentUser->uid) ?>" aria-hidden="true">
                        </div>
                    <?php endif; ?>
                    <div class="d-flex gap-2 mt-3">
                        <button type="submit" class="btn btn-primary" aria-label="Create this course">Create Course</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" aria-label="Cancel creating course">Cancel</button>
                    </div>
                <?php $form->end(); ?>
            </div>
        </div>
    </div>
</div>

<!-- ── Two-column layout ── -->
<div class="courses-wrap">
<div class="courses-layout">

    <!-- Left: Your Activity -->
    <div class="col-activity" aria-label="Your course activity">
        <h2 class="section-heading">Your Activity</h2>
        <div id="user-activity">
            <?php
            if (!empty($userActivity)) usort($userActivity, function($a, $b) {
                $now = new DateTime();
                $aCompleted = (new DateTime($a->endDate)) < $now ? 1 : 0;
                $bCompleted = (new DateTime($b->endDate)) < $now ? 1 : 0;
                if ($aCompleted !== $bCompleted) return $aCompleted - $bCompleted;
                return strtotime($a->startDate) - strtotime($b->startDate);
            });
            if (!empty($userActivity)): ?>
            <div class="activity-wrap">
                <table class="activity-table" aria-label="Your course activity">
                    <thead>
                        <tr>
                            <th scope="col">Title</th>
                            <th scope="col">Date Time</th>
                            <th scope="col" style="text-align:right;">Capacity</th>
                        </tr>
                    </thead>
                    <tbody id="activity-tbody">
                        <?php foreach ($userActivity as $i => $course):
                            $count     = $enrolledCounts[$course->uid] ?? 0;
                            $now       = new DateTime();
                            $end       = new DateTime($course->endDate);
                            $completed = $end < $now;
                            $start     = new DateTime($course->startDate);
                            $isToday   = $start->format('Y-m-d') === $now->format('Y-m-d');
                            $dateLabel = $completed ? 'COMPLETED' : ($isToday ? 'TODAY' : $start->format('d M Y'));
                            $timeLabel = (!$completed && $isToday) ? $start->format('H:i') : '';
                        ?>
                        <tr data-uid="<?= htmlspecialchars($course->uid) ?>"
                            data-lecturer-uid="<?= htmlspecialchars($course->lecturer) ?>"
                            data-completed="<?= $completed ? '1' : '0' ?>"
                            data-owned="<?= $course->lecturer === $currentUser->uid ? '1' : '0' ?>"
                            data-index="<?= $i ?>"
                            class="activity-row<?= $i >= 10 ? ' d-none' : '' ?>"
                            aria-label="<?= htmlspecialchars($course->courseTitle) ?>">
                            <td class="title-cell">
                                <button class="course-link" data-uid="<?= htmlspecialchars($course->uid) ?>"
                                    aria-label="View details for <?= htmlspecialchars($course->courseTitle) ?>">
                                    <?= htmlspecialchars($course->courseTitle) ?>
                                </button>
                                <span class="row-sub" aria-hidden="true">
                                    <?= htmlspecialchars($dateLabel) ?>
                                    <?= $timeLabel ? ' · ' . $timeLabel : '' ?>
                                    · <?= $count ?>/<?= htmlspecialchars($course->maxAttendees) ?>
                                </span>
                            </td>
                            <td class="date-cell<?= $completed ? ' is-completed' : ($isToday ? ' is-today' : '') ?>"
                                aria-label="Date: <?= htmlspecialchars($dateLabel) ?><?= $timeLabel ? ' at ' . $timeLabel : '' ?>">
                                <?= htmlspecialchars($dateLabel) ?>
                                <?php if ($timeLabel): ?><br><span class="time-label"><?= $timeLabel ?></span><?php endif; ?>
                            </td>
                            <td style="text-align:right;"
                                aria-label="Capacity: <?= $count ?> of <?= htmlspecialchars($course->maxAttendees) ?>">
                                <span class="capacity-badge" aria-hidden="true"><?= $count ?>/<?= htmlspecialchars($course->maxAttendees) ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="pagination-bar" role="navigation" aria-label="Activity pagination">
                    <button class="page-btn filter-btn d-none" id="activity-filter-btn"
                        aria-label="Toggle completed courses filter">&#9782;</button>
                    <button class="page-btn" id="activity-prev" aria-label="Previous activity page">&#9664;</button>
                    <span class="page-label" id="activity-page-label"
                        aria-live="polite" aria-atomic="true">All Activity</span>
                    <button class="page-btn" id="activity-next" aria-label="Next activity page">&#9654;</button>
                </div>
            </div>
            <?php else: ?>
                <p class="empty-msg" id="no-activity-msg">Create some courses or enrol onto them.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Right: Recently Added -->
    <div class="col-courses" aria-label="Recently added courses">
        <h2 class="section-heading">Recently Added</h2>
        <div id="active-courses">
            <?php if (!empty($activeCourses)): ?>
            <div class="card-grid-wrap">
                <div class="card-grid" id="courses-grid"
                    data-per-page="<?= $cardsPerPage ?>"
                    role="list"
                    aria-label="Available courses">
                    <?php foreach ($activeCourses as $i => $course):
                        $count      = $enrolledCounts[$course->uid] ?? 0;
                        $isLecturer = ($course->lecturer === $currentUser->uid);
                        $isEnrolled = isset($enrolledUidSet[$course->uid]);
                        $dateStr    = (new DateTime($course->startDate))->format('d M Y H:i');
                    ?>
                    <div class="course-card<?= ($i >= $cardsPerPage) ? ' d-none' : '' ?>"
                         data-uid="<?= htmlspecialchars($course->uid) ?>"
                         data-lecturer-uid="<?= htmlspecialchars($course->lecturer) ?>"
                         data-index="<?= $i ?>"
                         role="listitem"
                         aria-label="<?= htmlspecialchars($course->courseTitle) ?>">
                        <div class="card-body">
                            <div class="card-title">
                                <button class="course-link" data-uid="<?= htmlspecialchars($course->uid) ?>"
                                    aria-label="View details for <?= htmlspecialchars($course->courseTitle) ?>">
                                    <?= htmlspecialchars($course->courseTitle) ?>
                                </button>
                            </div>
                            <div class="card-lecturer" aria-label="Lecturer: <?= htmlspecialchars($course->lecturerName) ?>">
                                <?= htmlspecialchars($course->lecturerName) ?>
                            </div>
                            <div class="card-meta">
                                <span class="card-date" aria-label="Start date: <?= htmlspecialchars($dateStr) ?>">
                                    <?= htmlspecialchars($dateStr) ?>
                                </span>
                                <span class="capacity-badge"
                                    aria-label="<?= $count ?> of <?= htmlspecialchars($course->maxAttendees) ?> places filled">
                                    <?= $count ?>/<?= htmlspecialchars($course->maxAttendees) ?>
                                </span>
                            </div>
                        </div>
                        <div class="card-action">
                            <?php if ($isLecturer): ?>
                                <button class="card-btn card-btn--owner" disabled
                                    aria-label="You are the lecturer for this course"
                                    aria-disabled="true">Your Course</button>
                            <?php elseif ($isEnrolled): ?>
                                <button class="card-btn card-btn--unenrol unenroll-btn"
                                    data-uid="<?= htmlspecialchars($course->uid) ?>"
                                    aria-label="Unenroll from <?= htmlspecialchars($course->courseTitle) ?>">Unenroll</button>
                            <?php else: ?>
                                <button class="card-btn card-btn--enrol enroll-btn"
                                    data-uid="<?= htmlspecialchars($course->uid) ?>"
                                    aria-label="Sign up for <?= htmlspecialchars($course->courseTitle) ?>">Sign Up</button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php if (count($activeCourses) > $cardsPerPage): ?>
                <div class="pagination-bar pagination-bar--cards" role="navigation" aria-label="Courses pagination">
                    <button class="page-btn" id="courses-prev" aria-label="Previous courses page">&#9664;</button>
                    <span class="page-label" id="courses-page-label"
                        aria-live="polite" aria-atomic="true">Page 1</span>
                    <button class="page-btn" id="courses-next" aria-label="Next courses page">&#9654;</button>
                </div>
                <?php endif; ?>
            </div>
            <?php else: ?>
                <p class="empty-msg" id="no-active-msg">No courses available yet.</p>
            <?php endif; ?>
        </div>

        <?php if ($canAddCourse): ?>
        <button class="add-course-btn"
            data-bs-toggle="modal"
            data-bs-target="#add-course-modal"
            aria-label="Add a new course"
            aria-haspopup="dialog">ADD COURSE</button>
        <?php endif; ?>
    </div>

</div>
</div>

<script>
    window.GR = <?= json_encode([
        'currentUserUid'   => $currentUser->uid,
        'currentUserLevel' => $currentUser->accessLevel,
    ]) ?>;
</script>
<script src="/js/courses.js"></script>