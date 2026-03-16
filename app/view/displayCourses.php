<?php 
$this->title = 'Courses';
use app\core\Application;
$currentUser = Application::$app->user;
?>

<link rel="stylesheet" href="/css/displayCourses.css">

<!-- ── Course detail modal ────────────────────────────────────────── -->
<div class="modal fade" id="course-modal" tabindex="-1" aria-labelledby="modal-title" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-title">Course Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div id="view-pane">
                <div class="modal-body">
                    <div id="modal-body"><div class="modal-spinner"><div class="spinner-wheel"></div></div></div>
                    <div id="attendees" style="display:none;">
                        <hr>
                        <h6>Attendees</h6>
                        <div class="mb-3 d-flex gap-2">
                            <select id="attendee-select" class="form-select form-select-sm">
                                <option value="">Select a user to add...</option>
                            </select>
                            <button id="add-attendee" class="btn btn-sm btn-primary">Add</button>
                        </div>
                        <table class="table table-sm">
                            <thead><tr><th>Name</th><th>Email</th><th>Job Title</th><th></th></tr></thead>
                            <tbody id="attendees-tbody">
                                <tr id="no-attendees"><td colspan="4"><em>No attendees yet.</em></td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" id="enrol-btn"  class="btn btn-success" style="display:none;"></button>
                    <button type="button" id="edit-btn"   class="btn btn-primary" style="display:none;">Edit</button>
                    <button type="button" id="delete-btn" class="btn btn-danger"  style="display:none;">Delete</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>

            <div id="edit-pane" style="display:none;">
                <form id="edit-form">
                    <div class="modal-body">
                        <input type="hidden" name="uid" id="edit-uid">
                        <div class="form-group mb-2">
                            <label>Course Title</label>
                            <input type="text" name="courseTitle" id="edit-title" class="form-control">
                            <div class="invalid-feedback" id="err-courseTitle"></div>
                        </div>
                        <div class="form-group mb-2">
                            <label>Course Description</label>
                            <textarea name="courseDesc" id="edit-desc" class="form-control"></textarea>
                            <div class="invalid-feedback" id="err-courseDesc"></div>
                        </div>
                        <div class="date-row">
                            <div class="form-group mb-2">
                                <label>Start Date & Time</label>
                                <input type="datetime-local" name="startDate" id="edit-start" class="form-control">
                                <div class="invalid-feedback" id="err-startDate"></div>
                            </div>
                            <div class="form-group mb-2">
                                <label>End Date & Time</label>
                                <input type="datetime-local" name="endDate" id="edit-end" class="form-control">
                                <div class="invalid-feedback" id="err-endDate"></div>
                            </div>
                        </div>
                        <div class="form-group mb-2">
                            <label>Max Attendees</label>
                            <input type="number" name="maxAttendees" id="edit-max" class="form-control">
                            <div class="invalid-feedback" id="err-maxAttendees"></div>
                        </div>
                        <div class="form-group mb-2">
                            <label>Lecturer</label>
                            <?php if ($currentUser->accessLevel === 'super_user'): ?>
                            <select name="lecturer" id="edit-lecturer" class="form-control">
                                <option value="">Select lecturer</option>
                                <?php foreach ($lecturerOptions ?? [] as $uid => $name): ?>
                                    <option value="<?= htmlspecialchars($uid) ?>"><?= htmlspecialchars($name) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php else: ?>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($currentUser->firstName . ' ' . $currentUser->lastName) ?>" readonly>
                                <input type="hidden" name="lecturer" id="edit-lecturer" value="<?= htmlspecialchars($currentUser->uid) ?>">
                            <?php endif; ?>
                            <div class="invalid-feedback" id="err-lecturer"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                        <button type="button" id="cancel-edit" class="btn btn-secondary">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ── Add Course modal ───────────────────────────────────────────── -->
<div class="modal fade" id="add-course-modal" tabindex="-1" aria-labelledby="add-course-title" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="add-course-title">Add Course</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="course-form">
                <?php if (!isset($course)) { $course = new \app\model\CourseModel(); } ?>
                <?php $form = \app\core\form\Form::begin('/courses', "post", ['class' => 'create-course-form']); ?>
                    <?php echo $form->field($course, 'courseTitle') ?>
                    <?php echo $form->field($course, 'courseDesc')->textareaField() ?>
                    <div class="date-row">
                        <?php echo $form->field($course, 'startDate')->dateField() ?>
                        <?php echo $form->field($course, 'endDate')->dateField() ?>
                    </div>
                    <?php echo $form->field($course, 'maxAttendees')->numberField() ?>
                    <?php if ($currentUser->accessLevel === 'super_user'): ?>
                        <?php echo $form->field($course, 'lecturer')->dropDownField($lecturerOptions ?? []) ?>
                    <?php else: ?>
                        <div class="form-group mb-2">
                            <label>Lecturer</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($currentUser->firstName . ' ' . $currentUser->lastName) ?>" readonly>
                            <input type="hidden" name="lecturer" value="<?= htmlspecialchars($currentUser->uid) ?>">
                        </div>
                    <?php endif; ?>
                    <div class="d-flex gap-2 mt-3">
                        <button type="submit" class="btn btn-primary">Create Course</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                <?php $form->end(); ?>
            </div>
        </div>
    </div>
</div>

<!-- ── Two-column layout ─────────────────────────────────────────── -->
<div class="courses-wrap">
<div class="courses-layout">

    <!-- Left: Your Activity -->
    <div class="col-activity">
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
                <table class="activity-table">
                    <thead>
                        <tr><th>Title</th><th>Date Time</th><th style="text-align:right;">Capacity</th></tr>
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
                            class="activity-row<?= $i >= 10 ? ' d-none' : '' ?>">
                            <td class="title-cell">
                                <button class="course-link" data-uid="<?= htmlspecialchars($course->uid) ?>">
                                    <?= htmlspecialchars($course->courseTitle) ?>
                                </button>
                                <span class="row-sub">
                                    <?= htmlspecialchars($dateLabel) ?>
                                    <?= $timeLabel ? ' · ' . $timeLabel : '' ?>
                                    · <?= $count ?>/<?= htmlspecialchars($course->maxAttendees) ?>
                                </span>
                            </td>
                            <td class="date-cell<?= $completed ? ' is-completed' : ($isToday ? ' is-today' : '') ?>">
                                <?= htmlspecialchars($dateLabel) ?>
                                <?php if ($timeLabel): ?><br><span class="time-label"><?= $timeLabel ?></span><?php endif; ?>
                            </td>
                            <td style="text-align:right;"><span class="capacity-badge"><?= $count ?>/<?= htmlspecialchars($course->maxAttendees) ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="pagination-bar">
                    <button class="page-btn filter-btn d-none" id="activity-filter-btn" title="Toggle filter">&#9782;</button>
                    <button class="page-btn" id="activity-prev">&#9664;</button>
                    <span class="page-label" id="activity-page-label">All Activity</span>
                    <button class="page-btn" id="activity-next">&#9654;</button>
                </div>
            </div>
            <?php else: ?>
                <p class="empty-msg" id="no-activity-msg">Create some courses or enrol onto them.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Right: Recently Added -->
    <div class="col-courses">
        <h2 class="section-heading">Recently Added</h2>
        <div id="active-courses">
            <?php if (!empty($activeCourses)): ?>
            <div class="card-grid-wrap">
                <div class="card-grid" id="courses-grid" data-per-page="<?= $currentUser->accessLevel === 'user' ? 6 : 4 ?>">
                    <?php foreach ($activeCourses as $i => $course):
                        $count          = $enrolledCounts[$course->uid] ?? 0;
                        $isLecturer     = ($course->lecturer === $currentUser->uid);
                        $isEnrolled     = isset($enrolledUidSet[$course->uid]);
                        $lecturerObject = $lecturers[$course->lecturer] ?? null;
                        $lecturerName   = $lecturerObject
                            ? $lecturerObject->firstName . ' ' . $lecturerObject->lastName
                            : $course->lecturer;
                        $dateStr = (new DateTime($course->startDate))->format('d M Y H:i');
                    ?>
                    <div class="course-card<?= ($i >= ($currentUser->accessLevel === 'user' ? 6 : 4)) ? ' d-none' : '' ?>"
                         data-uid="<?= htmlspecialchars($course->uid) ?>"
                         data-lecturer-uid="<?= htmlspecialchars($course->lecturer) ?>"
                         data-index="<?= $i ?>">
                        <div class="card-body">
                            <div class="card-title">
                                <button class="course-link" data-uid="<?= htmlspecialchars($course->uid) ?>">
                                    <?= htmlspecialchars($course->courseTitle) ?>
                                </button>
                            </div>
                            <div class="card-lecturer"><?= htmlspecialchars($lecturerName) ?></div>
                            <div class="card-meta">
                                <span class="card-date"><?= htmlspecialchars($dateStr) ?></span>
                                <span class="capacity-badge"><?= $count ?>/<?= htmlspecialchars($course->maxAttendees) ?></span>
                            </div>
                        </div>
                        <div class="card-action">
                            <?php if ($isLecturer): ?>
                                <button class="card-btn card-btn--owner" disabled>Your Course</button>
                            <?php elseif ($isEnrolled): ?>
                                <button class="card-btn card-btn--unenrol unenroll-btn" data-uid="<?= htmlspecialchars($course->uid) ?>">Unenroll</button>
                            <?php else: ?>
                                <button class="card-btn card-btn--enrol enroll-btn" data-uid="<?= htmlspecialchars($course->uid) ?>">Sign Up</button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php if (count($activeCourses) > 4): ?>
                <div class="pagination-bar pagination-bar--cards">
                    <button class="page-btn" id="courses-prev">&#9664;</button>
                    <span class="page-label" id="courses-page-label">Page 1</span>
                    <button class="page-btn" id="courses-next">&#9654;</button>
                </div>
                <?php endif; ?>
            </div>
            <?php else: ?>
                <p class="empty-msg" id="no-active-msg">No courses available yet.</p>
            <?php endif; ?>
        </div>

        <?php if ($currentUser->accessLevel === 'admin' || $currentUser->accessLevel === 'super_user'): ?>
        <button class="add-course-btn" data-bs-toggle="modal" data-bs-target="#add-course-modal">
            ADD COURSE
        </button>
        <?php endif; ?>
    </div>

</div>
</div>

<script>
    window.GR = {
        currentUserUid:   <?= json_encode($currentUser->uid) ?>,
        currentUserLevel: <?= json_encode($currentUser->accessLevel) ?>
    };
</script>
<script src="/js/courses.js"></script>