document.addEventListener('DOMContentLoaded', () => {

    const { currentUserUid, currentUserLevel } = window.GR || {};

    let current = null;

    // Fetch wrapper
    function post(url, fd) {
        return fetch(url, { method: 'POST', body: fd })
            .then(r => {
                const ct = r.headers.get('Content-Type') || '';
                if (!ct.includes('application/json')) {
                    return r.text().then(html => {
                        console.error(`Non-JSON response from ${url}:`, html);
                        throw new Error(`Server returned non-JSON response (${r.status})`);
                    });
                }
                return r.json();
            })
            .catch(err => { console.error(err); alert('Something went wrong.'); });
    }

    function flash(type, msg) {
        document.querySelectorAll('.site-flash').forEach(e => e.remove());
        const el = document.createElement('div');
        el.className = `alert alert-${type} alert-dismissible fade show site-flash`;
        el.innerHTML = `${msg}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>`;
        document.body.appendChild(el);
        setTimeout(() => el.remove(), 3500);
    }

    // Update all capacity badges for a course (card + activity row)
    function updateCapacityBadges(uid, enrolled, max) {
        const text = `${enrolled}/${max}`;
        document.querySelectorAll(`.course-card[data-uid="${uid}"] .capacity-badge,
            tr[data-uid="${uid}"] .capacity-badge`).forEach(b => b.textContent = text);
    }

    function emptyRow() {
        const tr = document.createElement('tr');
        tr.id = 'no-attendees';
        const td = document.createElement('td');
        td.colSpan = 4;
        td.innerHTML = '<em>No attendees yet.</em>';
        tr.appendChild(td);
        return tr;
    }

    // ---- Form validation ----
    function clearErrors(form) {
        form.querySelectorAll('input, select, textarea').forEach(el => {
            el.addEventListener('input', function() {
                this.classList.remove('invalid-input');
                const fb = this.closest('.form-group')?.querySelector('.invalid-feedback');
                if (fb) fb.textContent = '';
            });
        });
    }

    function showErrors(form, errors) {
        Object.entries(errors).forEach(([field, msg]) => {
            const el = form.querySelector(`[name="${field}"]`);
            if (!el) return;
            el.classList.add('invalid-input');
            const fb = el.closest('.form-group')?.querySelector('.invalid-feedback');
            if (fb) fb.textContent = msg;
        });
    }

    // ---- Table helpers ----
    function ensureTbody(containerId, tbodyId, withEnrol = false) {
        let tbody = document.querySelector(`#${tbodyId}`);
        if (!tbody) {
            const enrolCol = withEnrol ? '<th>Enrolment</th>' : '';
            document.querySelector(`#${containerId}`).innerHTML = `
                <table cellpadding="10"><thead><tr>
                    <th>Course Title</th><th>Start Date</th>
                    <th>Max Attendees</th><th>Lecturer</th>${enrolCol}
                </tr></thead><tbody id="${tbodyId}"></tbody></table>`;
            tbody = document.querySelector(`#${tbodyId}`);
        }
        return tbody;
    }

    function activityRow(course) {
        const tr = document.createElement('tr');
        tr.dataset.uid = course.uid;
        tr.dataset.lecturerUid = course.lecturerUid;

        const td = document.createElement('td');
        const btn = document.createElement('button');
        btn.className   = 'course-link';
        btn.dataset.uid = course.uid;
        btn.textContent = course.courseTitle;
        btn.addEventListener('click', openCourse);
        td.appendChild(btn);
        tr.appendChild(td);

        ['startDate', 'maxAttendees', 'lecturer'].forEach(f => {
            const cell = document.createElement('td');
            cell.textContent = course[f];
            tr.appendChild(cell);
        });
        return tr;
    }

    function activeRow(course, isEnrolled) {
        const tr = activityRow(course);
        const td = document.createElement('td');
        td.appendChild(enrollBtn(course.uid, isEnrolled, course.lecturerUid === currentUserUid));
        tr.appendChild(td);
        return tr;
    }

    function enrollBtn(uid, isEnrolled, isOwner) {
        const btn = document.createElement('button');
        if (isOwner) {
            btn.className   = 'btn btn-sm btn-secondary';
            btn.textContent = 'Your course';
            btn.disabled    = true;
            return btn;
        }
        btn.dataset.uid = uid;
        if (isEnrolled) {
            btn.className   = 'btn btn-sm btn-warning unenroll-btn';
            btn.textContent = 'Unenroll';
            btn.addEventListener('click', onUnenroll);
        } else {
            btn.className   = 'btn btn-sm btn-success enroll-btn';
            btn.textContent = 'Enroll';
            btn.addEventListener('click', onEnroll);
        }
        return btn;
    }

    function syncRow(course) {
        // Update activity table row
        const tr = document.querySelector(`#activity-tbody tr[data-uid="${course.uid}"]`);
        if (tr) {
            const link = tr.querySelector('.course-link');
            if (link) link.textContent = course.courseTitle;
        }
        // Update card in grid
        const card = document.querySelector(`#courses-grid .course-card[data-uid="${course.uid}"]`);
        if (card) {
            const cardLink = card.querySelector('.course-link');
            if (cardLink) cardLink.textContent = course.courseTitle;
            const lecturer = card.querySelector('.card-lecturer');
            if (lecturer) lecturer.textContent = course.lecturer;
            const date = card.querySelector('.card-date');
            if (date) date.textContent = course.startDate;
        }
    }

    function syncEnrolBtn(uid, isEnrolled) {
        const card = document.querySelector(`#courses-grid .course-card[data-uid="${uid}"]`);
        if (!card) return;
        const action = card.querySelector('.card-action');
        const btn = document.createElement('button');
        if (isEnrolled) {
            btn.className   = 'card-btn card-btn--unenrol unenroll-btn';
            btn.textContent = 'Unenroll';
            btn.dataset.uid = uid;
            btn.addEventListener('click', onUnenroll);
        } else {
            btn.className   = 'card-btn card-btn--enrol enroll-btn';
            btn.textContent = 'Sign Up';
            btn.dataset.uid = uid;
            btn.addEventListener('click', onEnroll);
        }
        action.innerHTML = '';
        action.appendChild(btn);
    }

    function addCourseCard(course, isEnrolled) {
        const grid = document.querySelector('#courses-grid');
        if (!grid) return;
        const idx  = grid.querySelectorAll('.course-card').length;
        const card = document.createElement('div');
        card.className    = 'course-card' + (idx >= 4 ? ' d-none' : '');
        card.dataset.uid  = course.uid;
        card.dataset.index = idx;

        const body = document.createElement('div');
        body.className = 'card-body';

        const titleDiv = document.createElement('div');
        titleDiv.className = 'card-title';
        const btn = document.createElement('button');
        btn.className   = 'course-link';
        btn.dataset.uid = course.uid;
        btn.textContent = course.courseTitle;
        btn.addEventListener('click', openCourse);
        titleDiv.appendChild(btn);

        const lecturer = document.createElement('div');
        lecturer.className   = 'card-lecturer';
        lecturer.textContent = course.lecturer;

        const meta = document.createElement('div');
        meta.className = 'card-meta';
        const dateSpan = document.createElement('span');
        dateSpan.className   = 'card-date';
        dateSpan.textContent = course.startDate;
        const badge = document.createElement('span');
        badge.className   = 'capacity-badge';
        badge.textContent = (course.enrolledCount || 0) + '/' + course.maxAttendees;
        meta.appendChild(dateSpan);
        meta.appendChild(badge);

        body.appendChild(titleDiv);
        body.appendChild(lecturer);
        body.appendChild(meta);

        const action = document.createElement('div');
        action.className = 'card-action';
        const isOwner = course.lecturerUid === currentUserUid;
        if (isOwner) {
            const ob = document.createElement('button');
            ob.className   = 'card-btn card-btn--owner';
            ob.textContent = 'Your Course';
            ob.disabled    = true;
            action.appendChild(ob);
        } else {
            const eb = document.createElement('button');
            eb.className   = isEnrolled ? 'card-btn card-btn--unenrol unenroll-btn' : 'card-btn card-btn--enrol enroll-btn';
            eb.textContent = isEnrolled ? 'Unenroll' : 'Sign Up';
            eb.dataset.uid = course.uid;
            eb.addEventListener('click', isEnrolled ? onUnenroll : onEnroll);
            action.appendChild(eb);
        }

        card.appendChild(body);
        card.appendChild(action);
        grid.appendChild(card);
        renderCoursesPage();
    }

    function addActivity(course) {
        if (document.querySelector(`#activity-tbody tr[data-uid="${course.uid}"]`)) return;
        document.querySelector('#no-activity-msg')?.remove();

        const tbody = document.querySelector('#activity-tbody');
        if (!tbody) return;

        const idx   = tbody.querySelectorAll('tr').length;
        const start = course.startDate ? new Date(course.startDate) : null;
        const end   = course.endDate   ? new Date(course.endDate)   : null;
        const now   = new Date();
        const completed = end && end < now;
        const isToday   = start && start.toDateString() === now.toDateString();

        let dateLabel = '';
        if (completed)     dateLabel = 'COMPLETED';
        else if (isToday)  dateLabel = 'TODAY';
        else if (start)    dateLabel = start.toLocaleDateString('en-GB', { day:'2-digit', month:'short', year:'numeric' });

        const tr = document.createElement('tr');
        tr.className       = 'activity-row' + (idx >= 7 ? ' d-none' : '');
        tr.dataset.uid         = course.uid;
        tr.dataset.lecturerUid = course.lecturerUid;
        tr.dataset.index       = idx;

        const tdTitle = document.createElement('td');
        tdTitle.className = 'title-cell';
        const btn = document.createElement('button');
        btn.className   = 'course-link';
        btn.dataset.uid = course.uid;
        btn.textContent = course.courseTitle;
        btn.addEventListener('click', openCourse);
        tdTitle.appendChild(btn);

        const tdDate = document.createElement('td');
        tdDate.className   = 'date-cell' + (completed ? ' is-completed' : isToday ? ' is-today' : '');
        tdDate.textContent = dateLabel;
        if (isToday && start) {
            const br   = document.createElement('br');
            const time = document.createElement('span');
            time.className   = 'time-label';
            time.textContent = start.toLocaleTimeString('en-GB', { hour:'2-digit', minute:'2-digit' });
            tdDate.appendChild(br);
            tdDate.appendChild(time);
        }

        const tdCap  = document.createElement('td');
        tdCap.style.textAlign = 'right';
        const badge  = document.createElement('span');
        badge.className   = 'capacity-badge';
        badge.textContent = (course.enrolledCount || 0) + '/' + course.maxAttendees;
        tdCap.appendChild(badge);

        tr.appendChild(tdTitle);
        tr.appendChild(tdDate);
        tr.appendChild(tdCap);
        tbody.appendChild(tr);
        renderActivityPage();
    }

    function removeActivity(uid) {
        const row = document.querySelector(`#activity-tbody tr[data-uid="${uid}"]`);
        if (row?.dataset.lecturerUid !== currentUserUid) row?.remove();
    }

    // ---- Attendee table ----
    function attendeeRow(attendee, courseUid) {
        const tr = document.createElement('tr');
        tr.dataset.uid = attendee.uid;

        ['name', 'email', 'jobTitle'].forEach(f => {
            const td = document.createElement('td');
            td.textContent = attendee[f];
            tr.appendChild(td);
        });

        const td = document.createElement('td');
        const canRemove = currentUserLevel === 'super_user'
            || current?.lecturerUid === currentUserUid
            || attendee.uid === currentUserUid;

        if (canRemove) {
            const btn = document.createElement('button');
            btn.className   = 'btn btn-sm btn-danger';
            btn.textContent = 'Remove';
            btn.addEventListener('click', () => removeAttendee(attendee.uid, courseUid, tr));
            td.appendChild(btn);
        }
        tr.appendChild(td);
        return tr;
    }

    // Renders the attendees of the course in the modal with necessary information
    function renderAttendees(attendees, allUsers, courseUid) {
        const tbody    = document.querySelector('#attendees-tbody');
        const dropdown = document.querySelector('#attendee-select');

        document.querySelector('#attendees').style.display = '';
        tbody.innerHTML = '';

        if (attendees.length === 0) {
            tbody.appendChild(emptyRow());
        } else {
            attendees.forEach(a => tbody.appendChild(attendeeRow(a, courseUid)));
        }

        const enrolled = new Set(attendees.map(a => a.uid));
        dropdown.innerHTML = '<option value="">Select a user to add...</option>';
        Object.entries(allUsers).forEach(([uid, name]) => {
            if (enrolled.has(uid)) return;
            const opt = document.createElement('option');
            opt.value = uid;
            opt.textContent = name;
            dropdown.appendChild(opt);
        });
        dropdown._users = allUsers;

        // Disable/hide add attendee controls if at capacity
        const isFull = current.enrolledCount >= current.maxAttendees;
        const addBtn = document.querySelector('#add-attendee');
        const addMessage = document.querySelector('#course-full-message') || document.createElement('div');

        if (isFull) {
            addBtn.style.display = 'none';
            dropdown.style.display = 'none';
        } else {
            addBtn.style.display = '';
            dropdown.style.display = '';
            addBtn.disabled = false;
            dropdown.disabled = false;
            if (addMessage.parentNode) {
                addMessage.remove();
            }
        }
    }

    // Add / remove attendee
    document.querySelector('#add-attendee').addEventListener('click', () => {
        const dropdown = document.querySelector('#attendee-select');
        const uid = dropdown.value;
        if (!uid || !current) return;

        const fd = new FormData();
        fd.append('courseUid', current.uid);
        fd.append('userUid', uid);

        post('/addAttendee', fd).then(data => {
            if (data?.flash) flash(data.flash.type, data.flash.message);
            if (!data?.success) return;
            document.querySelector('#no-attendees')?.remove();
            document.querySelector('#attendees-tbody').appendChild(attendeeRow(data.attendee, current.uid));
            dropdown.querySelector(`option[value="${data.attendee.uid}"]`)?.remove();
            dropdown.value = '';
            current.enrolledCount++;
            if (current.enrolledCount >= current.maxAttendees) {
                document.querySelector('#add-attendee').disabled = true;
                document.querySelector('#attendee-select').disabled = true;
            }
            if (data.enrolledCount !== undefined) updateCapacityBadges(current.uid, data.enrolledCount, current.maxAttendees);
        });
    });

    async function removeAttendee(uid, courseUid, row) {
        if (!await confirm('Remove this attendee from the course?', 'Remove Attendee')) return;

        const fd = new FormData();
        fd.append('courseUid', courseUid);
        fd.append('userUid', uid);

        post('/removeAttendee', fd).then(data => {
            if (data?.flash) flash(data.flash.type, data.flash.message);
            if (!data?.success) return;

            row.remove();

            const dropdown = document.querySelector('#attendee-select');
            if (dropdown._users?.[uid]) {
                const opt = document.createElement('option');
                opt.value = uid;
                opt.textContent = dropdown._users[uid];
                dropdown.appendChild(opt);
            }

            if (uid === currentUserUid) {
                setEnrolState(document.querySelector('#enrol-btn'), false);
                syncEnrolBtn(courseUid, false);
                removeActivity(courseUid);
            }

            current.enrolledCount--;
            if (current.enrolledCount < current.maxAttendees) {
                document.querySelector('#add-attendee').disabled = false;
                document.querySelector('#attendee-select').disabled = false;
            }

            if (data.enrolledCount !== undefined) updateCapacityBadges(courseUid, data.enrolledCount, current.maxAttendees);

            if (!document.querySelector('#attendees-tbody').children.length) {
                document.querySelector('#attendees-tbody').appendChild(emptyRow());
            }
        });
    }

    // Courses Modal 
    function viewMode() {
        document.querySelector('#view-pane').style.display = '';
        document.querySelector('#edit-pane').style.display = 'none';
    }

    function editMode(course) {
        document.querySelector('#view-pane').style.display = 'none';
        // Displays the edit pane from the HTML
        document.querySelector('#edit-pane').style.display = '';
        // Pre-fills the form with the current course details
        document.querySelector('#edit-uid').value = course.uid;
        document.querySelector('#edit-title').value = course.courseTitle;
        document.querySelector('#edit-desc').value = course.courseDesc;
        document.querySelector('#edit-start').value = course.startDate;
        document.querySelector('#edit-end').value = course.endDate;
        document.querySelector('#edit-end').min = course.startDate; // Ensure end date cannot be before start
        document.querySelector('#edit-max').value = course.maxAttendees;
        document.querySelector('#edit-lecturer').value = course.lecturerUid; // Read only for non-superusers
        document.querySelectorAll('#edit-form .invalid-feedback').forEach(el => el.textContent = '');
        document.querySelectorAll('#edit-form input, #edit-form select, #edit-form textarea')
            .forEach(el => el.classList.remove('invalid-input'));
    }

    // Takes all the data from the openCourse function
    function renderCourse(course, isEnrolled, privileged, attendees, allUsers) {
        document.querySelector('#modal-title').textContent = course.courseTitle;

        const body    = document.querySelector('#modal-body');
        const isOwner = course.lecturerUid === currentUserUid;
        // Identifies if the user can modify the course
        const canEdit = isOwner || currentUserLevel === 'super_user';

        // Only displays certain buttons if the user has permissions
        body.innerHTML = '';
        document.querySelector('#edit-btn').style.display   = canEdit ? '' : 'none';
        document.querySelector('#delete-btn').style.display = canEdit ? '' : 'none';
        document.querySelector('#edit-btn').dataset.uid     = course.uid;
        document.querySelector('#delete-btn').dataset.uid   = course.uid;

        // Enrol button displayed for everyone except the owner of the course
        const btn = document.querySelector('#enrol-btn');
        btn.style.display = isOwner ? 'none' : '';
        if (!isOwner) {
            btn.dataset.uid = course.uid;
            if (current.enrolledCount >= course.maxAttendees) {
                btn.disabled = true;
                btn.textContent = 'Course Full';
                btn.onclick = null;
            } else {
                setEnrolState(btn, isEnrolled);
            }
        }

        // Populating the different fields
        [['Description', course.courseDesc], ['Lecturer', course.lecturer],
         ['Start Date', course.startDate], ['End Date', course.endDate],
         ['Max Attendees', course.maxAttendees]
        ].forEach(([label, val]) => {
            const p = document.createElement('p');
            const s = document.createElement('strong');
            s.textContent = label + ': ';
            p.appendChild(s);
            p.appendChild(document.createTextNode(val));
            body.appendChild(p);
        });

        // A privileged user should be able to also see all the attendees using the renderAttendees() function
        if (privileged) {
            renderAttendees(attendees, allUsers, course.uid);
        } else {
            document.querySelector('#attendees').style.display = 'none';
        }
    }

    function setEnrolState(btn, isEnrolled) {
        btn.textContent = isEnrolled ? 'Unenroll' : 'Enroll';
        btn.className   = isEnrolled ? 'btn btn-warning' : 'btn btn-success';
        btn.onclick     = () => isEnrolled ? unenrollModal(btn.dataset.uid) : enrollModal(btn.dataset.uid);
    }

    // Open course modal
    function openCourse() {

        // Creates a modal and it's buttons
        const uid = this.dataset.uid;
        document.querySelector('#modal-body').innerHTML = '<div class="modal-spinner"><div class="spinner-wheel"></div></div>';
        document.querySelector('#modal-title').textContent = 'Course Details';
        document.querySelector('#attendees').style.display = 'none';
        ['#enrol-btn', '#edit-btn', '#delete-btn'].forEach(s => {
            document.querySelector(s).style.display = 'none';
        });
        viewMode();

        // Opens a course-modal modal (see displayCourses.php)
        new bootstrap.Modal(document.querySelector('#course-modal')).show();

        // Fetches the course details and stores it in FormData (JS API)
        const fd = new FormData();
        fd.append('uid', uid);

        // Fetches the course details from the server based on the UID of the selected course
        post('/viewCourse', fd).then(data => {
            if (data?.success) {
                current = data.course;
                current.enrolledCount = data.enrolledCount;
                // Sends the modal with course details and attendees if the user has permissions to seperate function
                renderCourse(current, data.isEnrolled, data.isPrivileged, data.attendees, data.allUsers);
            } else {
                document.querySelector('#modal-body').innerHTML = '<p>Could not load course.</p>';
            }
        });
    }

    document.querySelectorAll('.course-link').forEach(el => el.addEventListener('click', openCourse));

    // ---- Enrol / unenrol ----
    function enrollModal(uid) {
        const fd = new FormData();
        fd.append('uid', uid);
        post('/enrollCourse', fd).then(data => {
            if (data?.flash) flash(data.flash.type, data.flash.message);
            if (!data?.success) return;
            setEnrolState(document.querySelector('#enrol-btn'), true);
            syncEnrolBtn(uid, true);
            addActivity(current);
            if (data.enrolledCount !== undefined) {
                current.enrolledCount = data.enrolledCount;
                updateCapacityBadges(uid, data.enrolledCount, current.maxAttendees);
            }
        });
    }

    function unenrollModal(uid) {
        const fd = new FormData();
        fd.append('uid', uid);
        post('/unenrollCourse', fd).then(data => {
            if (data?.flash) flash(data.flash.type, data.flash.message);
            if (!data?.success) return;
            setEnrolState(document.querySelector('#enrol-btn'), false);
            syncEnrolBtn(uid, false);
            removeActivity(uid);
            if (data.enrolledCount !== undefined) {
                current.enrolledCount = data.enrolledCount;
                updateCapacityBadges(uid, data.enrolledCount, current.maxAttendees);
            }
        });
    }

    // AJAX requests for enrol/unenrol buttons in course cards and activity table
    function onEnroll() {
        const uid  = this.dataset.uid;
        const btn  = this;
        const card = btn.closest('.course-card');
        const fd   = new FormData();
        fd.append('uid', uid);

        // Sends the request to /enrollCourse and updates the button state + capacity badges on success
        post('/enrollCourse', fd).then(data => {
            if (data?.flash) flash(data.flash.type, data.flash.message); // Flash message
            if (!data?.success) return; // Stop if enrolment failed
            btn.className   = 'card-btn card-btn--unenrol unenroll-btn';
            btn.textContent = 'Unenroll'; // Makes the button look like an unenroll button
            btn.removeEventListener('click', onEnroll);
            btn.addEventListener('click', onUnenroll); // Changes the event listeners to unenroll
            const max = card?.querySelector('.capacity-badge')?.textContent.split('/')[1]; // Gets the max capacity from the badge to update it with the new enrolled count
            if (data.enrolledCount !== undefined) updateCapacityBadges(uid, data.enrolledCount, max);
            
            // Adds it to YourActivity
            if (card) addActivity({
                uid:           card.dataset.uid,
                lecturerUid:   card.dataset.lecturerUid,
                courseTitle:   card.querySelector('.course-link')?.textContent.trim(),
                startDate:     card.querySelector('.card-date')?.textContent,
                endDate:       '',
                maxAttendees:  max,
                lecturer:      card.querySelector('.card-lecturer')?.textContent,
                enrolledCount: data.enrolledCount ?? 0,
            });
        });
    }

    function onUnenroll() {
        const uid = this.dataset.uid;
        const btn = this;
        const fd  = new FormData();
        fd.append('uid', uid);
        post('/unenrollCourse', fd).then(data => {
            if (data?.flash) flash(data.flash.type, data.flash.message);
            if (!data?.success) return;
            btn.className   = 'card-btn card-btn--enrol enroll-btn';
            btn.textContent = 'Sign Up';
            btn.removeEventListener('click', onUnenroll);
            btn.addEventListener('click', onEnroll);
            if (data.enrolledCount !== undefined) updateCapacityBadges(uid, data.enrolledCount, btn.closest('.course-card')?.querySelector('.capacity-badge')?.textContent.split('/')[1]);
            removeActivity(uid);
        });
    }

    // Edit a course, event listener for changing modal view form
    document.querySelector('#edit-btn').addEventListener('click', () => {
        if (current) editMode(current);
    });

    // Cancel button just turns it back to view mode without saving changes
    document.querySelector('#cancel-edit').addEventListener('click', viewMode);

    // Update end date min when start date changes
    document.querySelector('#edit-start').addEventListener('change', function() {
        const startVal = this.value;
        if (startVal) {
            document.querySelector('#edit-end').min = startVal;
        }
    });

    // AJAX request for submitting the edit form
    document.querySelector('#edit-form').addEventListener('submit', function(e) {
        e.preventDefault();
        const startVal = document.querySelector('#edit-start').value;
        const endVal = document.querySelector('#edit-end').value;
        if (startVal && endVal) {
            const start = new Date(startVal);
            const end = new Date(endVal);
            if (end <= start) {
                showErrors(this, {endDate: ['End date must be after the start date.']});
                return;
            }
        }
        // Sends a POST request to editCourse with the data that is in the form
        post('/editCourse', new FormData(this)).then(data => {
            if (data?.flash) flash(data.flash.type, data.flash.message); // Sets a flash message
            if (data?.success) { // Updates the course details on success and returns the view
                current = data.course;
                const isEnrolled = document.querySelector('#enrol-btn').textContent === 'Unenroll';
                renderCourse(current, isEnrolled, true, [], document.querySelector('#attendee-select')._users || {});
                viewMode();
                syncRow(data.course); // Changes the course title in the activity table and card
                bootstrap.Modal.getInstance(document.querySelector('#course-modal')).hide();
            } else if (data?.errors) {
                // Otherwise, shows errors
                showErrors(this, data.errors);
            }
        });
    });

    // Delete a course, event listener for delete button in course modal
    document.querySelector('#delete-btn').addEventListener('click', async function() {
        const uid = this.dataset.uid;
        if (!await confirm('This will permanently delete the course and remove all enrolments.', 'Delete Course')) return;
        const fd = new FormData();
        fd.append('uid', uid);
        post('/deleteCourse', fd).then(data => {
            if (data?.flash) flash(data.flash.type, data.flash.message);
            if (!data?.success) return;
            bootstrap.Modal.getInstance(document.querySelector('#course-modal')).hide();
            document.querySelectorAll(`tr[data-uid="${uid}"]`).forEach(r => r.remove());
            document.querySelectorAll(`.course-card[data-uid="${uid}"]`).forEach(c => c.remove());
            renderActivityPage();
            renderCoursesPage();
        });
    });

    // ---- Create ----
    const createForm = document.querySelector('.create-course-form');
    if (createForm) {
        clearErrors(createForm);
        createForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const startVal = document.querySelector('input[name="startDate"]').value;
            const endVal = document.querySelector('input[name="endDate"]').value;
            if (startVal && endVal) {
                const start = new Date(startVal);
                const end = new Date(endVal);
                if (end <= start) {
                    showErrors(this, {endDate: ['End date must be after the start date.']});
                    return;
                }
            }
            post('/courses', new FormData(createForm)).then(data => {
                if (data?.flash) flash(data.flash.type, data.flash.message);
                if (data?.success) {
                    createForm.reset();
                    bootstrap.Modal.getInstance(document.querySelector('#add-course-modal'))?.hide();
                    document.querySelector('#no-active-msg')?.remove();
                    addCourseCard(data.course, false);
                    if (data.course.lecturerUid === currentUserUid) {
                        addActivity(data.course);
                    }
                } else if (data?.errors) {
                    showErrors(createForm, data.errors);
                }
            });
        });
    }

    // Set min on create form end date when start changes
    document.querySelector('#add-course-modal').addEventListener('shown.bs.modal', function() {
        const startInput = document.querySelector('input[name="startDate"]');
        const endInput = document.querySelector('input[name="endDate"]');
        if (startInput && endInput) {
            startInput.addEventListener('change', function() {
                endInput.min = this.value;
            });
        }
    });

    document.querySelectorAll('.enroll-btn').forEach(el => el.addEventListener('click', onEnroll));
    document.querySelectorAll('.unenroll-btn').forEach(el => el.addEventListener('click', onUnenroll));

    // ---- Activity table pagination + history toggle ----
    const ROW_HEIGHT = 42; // px per row, matches CSS
    let ROWS_PER_PAGE = 10;
    let activityPage = 0;

    function calcRowsPerPage() {
        const tbody = document.querySelector('#activity-tbody');
        const wrap  = document.querySelector('.activity-wrap');
        if (!tbody || !wrap || wrap.clientHeight === 0) return;
        const available = wrap.clientHeight
            - (document.querySelector('.activity-table thead')?.offsetHeight || 38)
            - (document.querySelector('.activity-wrap .pagination-bar')?.offsetHeight || 40);
        if (available <= 0) return;
        const rows = Math.min(14, Math.max(1, Math.floor(available / ROW_HEIGHT)));
        if (rows !== ROWS_PER_PAGE) {
            ROWS_PER_PAGE = rows;
            if (activityPage * ROWS_PER_PAGE >= getActivityRows().length) activityPage = 0;
            renderActivityPage();
        }
    }
    let historyMode = false; // false = all, true = completed only

    function getActivityRows() {
        const all = Array.from(document.querySelectorAll('.activity-row'));
        return historyMode ? all.filter(r => r.dataset.completed === '1') : all;
    }

    function renderActivityPage() {
        const tbody = document.querySelector('#activity-tbody');
        const all = Array.from(document.querySelectorAll('.activity-row'));
        const visible = getActivityRows();
        const pages = Math.max(1, Math.ceil(visible.length / ROWS_PER_PAGE));
        if (activityPage >= pages) activityPage = pages - 1;

        // Hide all first, then show only the current page of filtered rows
        all.forEach(r => r.classList.add('d-none'));
        visible.slice(activityPage * ROWS_PER_PAGE, (activityPage + 1) * ROWS_PER_PAGE)
               .forEach(r => r.classList.remove('d-none'));

        // Remove old placeholder rows, then pad to ROWS_PER_PAGE with empty ones
        tbody.querySelectorAll('.activity-placeholder').forEach(r => r.remove());
        const shown = visible.slice(activityPage * ROWS_PER_PAGE, (activityPage + 1) * ROWS_PER_PAGE).length;
        for (let i = shown; i < ROWS_PER_PAGE; i++) {
            const tr = document.createElement('tr');
            tr.className = 'activity-placeholder';
            tr.innerHTML = '<td colspan="3">&nbsp;</td>';
            tbody.appendChild(tr);
        }

        const prev  = document.querySelector('#activity-prev');
        const next  = document.querySelector('#activity-next');
        const label = document.querySelector('#activity-page-label');
        if (prev)  prev.disabled  = activityPage === 0;
        if (next)  next.disabled  = activityPage >= pages - 1;
        if (label) {
            if (historyMode) {
                label.textContent = pages > 1 ? `History ${activityPage + 1}/${pages} (toggle)` : 'History (toggle)';
            } else {
                label.textContent = pages > 1 ? `Page ${activityPage + 1} of ${pages} (toggle)` : 'All Activity (toggle)';
            }
        }
    }

    document.querySelector('#activity-prev')?.addEventListener('click', () => {
        if (activityPage > 0) { activityPage--; renderActivityPage(); }
    });
    document.querySelector('#activity-next')?.addEventListener('click', () => {
        const pages = Math.max(1, Math.ceil(getActivityRows().length / ROWS_PER_PAGE));
        if (activityPage < pages - 1) { activityPage++; renderActivityPage(); }
    });

    // Click filter button or label to toggle history mode
    const activityFilterBtn = document.querySelector('#activity-filter-btn');
    if (activityFilterBtn) activityFilterBtn.classList.remove('d-none');
    function toggleHistory() { historyMode = !historyMode; activityPage = 0; renderActivityPage(); }
    document.querySelector('#activity-page-label')?.addEventListener('click', toggleHistory);
    activityFilterBtn?.addEventListener('click', toggleHistory);

    renderActivityPage();

    // Double rAF ensures flex layout is fully settled before measuring
    requestAnimationFrame(() => requestAnimationFrame(() => {
        calcRowsPerPage();
        const activityWrap = document.querySelector('.activity-wrap');
        if (activityWrap) new ResizeObserver(() => calcRowsPerPage()).observe(activityWrap);
    }));

    // ---- Course card pagination ----
    const CARDS_PER_PAGE = parseInt(document.querySelector('#courses-grid')?.dataset.perPage) || 4;
    let coursesPage = 0;

    // Set grid rows to match cards per page (2 cols, so rows = perPage / 2)
    const coursesGridEl = document.querySelector('#courses-grid');
    if (coursesGridEl) {
        const rows = Math.ceil(CARDS_PER_PAGE / 2);
        coursesGridEl.style.gridTemplateRows = Array(rows).fill('1fr').join(' ');
    }

    function renderCoursesPage() {
        const cards = Array.from(document.querySelectorAll('.course-card'));
        const pages = Math.max(1, Math.ceil(cards.length / CARDS_PER_PAGE));
        if (coursesPage >= pages) coursesPage = pages - 1;

        cards.forEach((c, i) => c.classList.toggle('d-none',
            i < coursesPage * CARDS_PER_PAGE || i >= (coursesPage + 1) * CARDS_PER_PAGE));

        const prev  = document.querySelector('#courses-prev');
        const next  = document.querySelector('#courses-next');
        const label = document.querySelector('#courses-page-label');
        if (prev)  prev.disabled  = coursesPage === 0;
        if (next)  next.disabled  = coursesPage >= pages - 1;
        if (label) label.textContent = `Page ${coursesPage + 1} of ${pages}`;
    }

    document.querySelector('#courses-prev')?.addEventListener('click', () => {
        if (coursesPage > 0) { coursesPage--; renderCoursesPage(); }
    });
    document.querySelector('#courses-next')?.addEventListener('click', () => {
        const pages = Math.max(1, Math.ceil(document.querySelectorAll('.course-card').length / CARDS_PER_PAGE));
        if (coursesPage < pages - 1) { coursesPage++; renderCoursesPage(); }
    });

    renderCoursesPage();

});