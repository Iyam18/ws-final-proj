<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#f3f7f8">
  <title>MediSched — clinic care, well scheduled</title>
  <script>
    try {
      const savedTheme = localStorage.getItem('medisched-theme');
      document.documentElement.dataset.theme = savedTheme || (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    } catch {
      document.documentElement.dataset.theme = 'light';
    }
  </script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/styles.css?v=button-field-interactions-1">
  <script src="assets/app.js?v=patient-address-required-1" defer></script>
</head>
<body>
  <main class="app-shell">
    <aside class="sidebar">
      <a class="brand" href="#" aria-label="MediSched home">
        <span class="brand-mark" aria-hidden="true">✚</span>
        <span>Medi<span class="brand-accent">Sched</span></span>
      </a>
      <div class="sidebar-caption">CLINIC WORKSPACE</div>
      <nav class="navigation" aria-label="Main navigation">
        <button class="nav-item active" type="button" data-page="dashboard"><span class="nav-icon" aria-hidden="true">⌂</span>Overview</button>
        <button class="nav-item" type="button" data-page="appointments"><span class="nav-icon" aria-hidden="true">▦</span>Appointments<span class="nav-count" id="nav-appointment-count">0</span></button>
        <button class="nav-item" type="button" data-page="patients"><span class="nav-icon" aria-hidden="true">♡</span>Patient records<span class="nav-count" id="nav-patient-count">0</span></button>
      </nav>
      <div class="sidebar-note">
        <span class="note-spark" aria-hidden="true">✚</span>
        <p>Better organized care.<br><strong>One patient at a time.</strong></p>
      </div>
      <div class="profile">
        <div class="avatar" id="avatar">M</div>
        <div class="profile-copy"><strong id="profile-name">Clinic staff</strong><span>Staff workspace</span></div>
        <button class="icon-button logout-button" id="logout-button" type="button" aria-label="Sign out" title="Sign out">↗</button>
      </div>
    </aside>

    <section class="main-panel">
      <header class="topbar">
        <span class="date-label" id="date-label"></span>
        <div class="topbar-actions">
          <span class="secure-label"><span class="secure-dot"></span> Private clinic workspace</span>
          <button id="theme-toggle" class="theme-toggle" type="button" aria-label="Switch to dark mode" aria-pressed="false" title="Switch to dark mode">
            <span class="theme-icon theme-sun" aria-hidden="true">☀</span>
            <span class="theme-icon theme-moon" aria-hidden="true">☾</span>
            <span class="theme-knob" aria-hidden="true"></span>
          </button>
        </div>
      </header>

      <section class="content">
        <div id="dashboard-view">
          <div class="welcome-row">
            <div>
              <p class="eyebrow">YOUR CLINIC, IN SYNC</p>
              <h1 id="greeting">Good morning.</h1>
              <p class="subtitle">A clearer schedule helps you focus on care.</p>
            </div>
            <button id="dashboard-book-button" class="primary-button" type="button"><span aria-hidden="true">＋</span> Book appointment</button>
          </div>

          <section class="dashboard-grid" aria-label="Clinic overview">
            <article class="dashboard-card today-card">
              <div class="dashboard-card-top"><span class="dashboard-icon">◷</span><span class="dashboard-label">TODAY'S VISITS</span></div>
              <strong id="dashboard-today">0</strong><span>appointments scheduled</span>
            </article>
            <article class="dashboard-card week-card">
              <div class="dashboard-card-top"><span class="dashboard-icon">✓</span><span class="dashboard-label">COMPLETED THIS WEEK</span></div>
              <strong id="dashboard-week">0</strong><span>visits completed</span>
            </article>
            <article class="dashboard-card patients-card">
              <div class="dashboard-card-top"><span class="dashboard-icon">♡</span><span class="dashboard-label">PATIENT RECORDS</span></div>
              <strong id="dashboard-patients">0</strong><span>patients in your clinic</span>
            </article>
          </section>

          <section class="schedule-panel" aria-label="Today's appointments">
            <div class="list-heading">
              <div><p class="eyebrow">UP NEXT</p><h2>Today's schedule <span class="count-pill" id="today-list-count">0</span></h2></div>
              <button class="text-button" type="button" data-open-page="appointments">View all appointments →</button>
            </div>
            <div id="today-appointment-list" class="appointment-list"></div>
            <div id="today-empty" class="empty-state" hidden><div class="empty-illustration" aria-hidden="true">✓</div><h3>No visits scheduled today</h3><p>Your clinic schedule is clear for now.</p></div>
          </section>
        </div>

        <section id="appointments-view" hidden>
          <div class="page-heading">
            <div><p class="eyebrow">CLINIC SCHEDULE</p><h1>Appointments</h1><p class="subtitle">Review visits, update their status, and keep care on track.</p></div>
            <button id="appointments-book-button" class="primary-button" type="button"><span aria-hidden="true">＋</span> New appointment</button>
          </div>
          <section class="booking-card" id="booking-card" aria-label="Book an appointment">
            <div class="section-heading">
              <div class="section-icon" aria-hidden="true">▦</div>
              <div><p class="eyebrow">SCHEDULE A VISIT</p><h2>Book an appointment</h2><p class="section-copy">Choose a patient and visit time.</p></div>
            </div>
            <form id="appointment-form" class="appointment-form">
              <label class="field patient-select-field" for="appointment-patient">Patient
                <span class="patient-select-control">
                  <select id="appointment-patient" class="patient-select" name="patient_id" required><option value="" selected disabled hidden>Select a patient</option></select>
                  <span class="patient-select-chevron" aria-hidden="true">⌄</span>
                </span>
              </label>
              <label class="field" for="appointment-reason">Visit reason
                <input id="appointment-reason" name="reason" type="text" maxlength="160" placeholder="e.g. Annual checkup" required>
              </label>
              <label class="field" for="appointment-date">Date and time
                <input id="appointment-date" name="scheduled_at" type="datetime-local" required>
              </label>
              <label class="field notes-field" for="appointment-notes">Visit notes <span class="optional-label">OPTIONAL</span>
                <input id="appointment-notes" name="notes" type="text" maxlength="2000" placeholder="Keep visit notes concise and relevant">
              </label>
              <p class="privacy-hint">Only authorized staff signed into this workspace can view these records.</p>
              <button class="primary-button book-submit" type="submit"><span aria-hidden="true">＋</span> Add to schedule</button>
            </form>
          </section>
          <div class="list-tools appointment-tools">
            <div class="filter-tabs" role="group" aria-label="Filter appointments">
              <button class="filter-tab active" type="button" data-filter="all">All</button>
              <button class="filter-tab" type="button" data-filter="today">Today</button>
              <button class="filter-tab" type="button" data-filter="upcoming">Upcoming</button>
              <button class="filter-tab" type="button" data-filter="scheduled">Scheduled</button>
              <button class="filter-tab" type="button" data-filter="completed">Completed</button>
              <button class="filter-tab" type="button" data-filter="cancelled">Cancelled</button>
            </div>
            <label class="search-box"><span aria-hidden="true">⌕</span><span class="sr-only">Search appointments</span><input id="appointment-search" type="search" placeholder="Search visits..." autocomplete="off"></label>
          </div>
          <div id="appointment-list" class="appointment-list"></div>
          <div id="appointment-empty" class="empty-state" hidden><div class="empty-illustration" aria-hidden="true">▦</div><h3>No appointments found</h3><p>Try another filter or book a new visit.</p></div>
        </section>

        <section id="patients-view" hidden>
          <div class="page-heading">
            <div><p class="eyebrow">PATIENT DIRECTORY</p><h1>Patient records</h1><p class="subtitle">Contact details and relevant care notes in one place.</p></div>
            <button id="add-patient-button" class="primary-button" type="button"><span aria-hidden="true">＋</span> Add patient</button>
          </div>
          <div class="list-tools patient-tools">
            <p class="privacy-hint">Handle patient information with care. Avoid entering unnecessary sensitive details.</p>
            <label class="search-box"><span aria-hidden="true">⌕</span><span class="sr-only">Search patients</span><input id="patient-search" type="search" placeholder="Search patients..." autocomplete="off"></label>
          </div>
          <div class="filter-tabs patient-filter-tabs" role="group" aria-label="Filter patient records">
            <button class="filter-tab active" type="button" data-patient-filter="active">Active</button>
            <button class="filter-tab" type="button" data-patient-filter="archived">Archived</button>
          </div>
          <div id="patient-list" class="patient-list"></div>
          <div id="patient-empty" class="empty-state" hidden><div class="empty-illustration" aria-hidden="true">♡</div><h3 id="patient-empty-title">No patient records yet</h3><p id="patient-empty-copy">Add a patient record before booking their first appointment.</p></div>
        </section>

        <div id="loading-state" class="loading-state">Preparing your clinic workspace…</div>
      </section>
      <footer class="footer"><span>MEDISCHED <span class="brand-accent">✚</span></span></footer>
    </section>
  </main>

  <div id="toast" class="toast" role="status" aria-live="polite"></div>

  <dialog id="patient-dialog" class="action-dialog">
    <form id="patient-form" class="dialog-panel">
      <div class="dialog-icon patient-dialog-icon" aria-hidden="true">♡</div>
      <p class="eyebrow">PATIENT RECORD</p>
      <h2 id="patient-dialog-title">Add a patient</h2>
      <p class="dialog-copy">Enter only the information your clinic needs.</p>
      <label class="field" for="patient-name">Full name<input id="patient-name" name="full_name" type="text" maxlength="120" autocomplete="name" required></label>
      <div class="patient-contact-grid">
        <label class="field" for="patient-dob">Date of birth<input id="patient-dob" name="date_of_birth" type="date"></label>
        <label class="field" for="patient-phone">Phone<input id="patient-phone" name="phone" type="tel" pattern="(09[0-9]{9}|\+639[0-9]{9})" maxlength="13" autocomplete="tel" placeholder="09XXXXXXXXX or +639XXXXXXXXX" title="Enter a Philippine mobile number beginning with 09 or +639"></label>
      </div>
      <label class="field" for="patient-email">Email<input id="patient-email" name="email" type="email" maxlength="254" autocomplete="email"></label>
      <label class="field" for="patient-address">Address<input id="patient-address" name="address" type="text" maxlength="255" autocomplete="street-address" required></label>
      <label class="field" for="patient-allergies">Allergies &amp; medical conditions <span class="optional-label">OPTIONAL</span><textarea id="patient-allergies" name="allergies" maxlength="2000" rows="4" placeholder="List known allergies, ongoing illnesses, or chronic conditions"></textarea></label>
      <label class="field" for="patient-medical-notes">Relevant care notes <span class="optional-label">OPTIONAL</span><textarea id="patient-medical-notes" name="medical_notes" maxlength="2000" rows="3"></textarea></label>
      <p id="patient-error" class="form-error" role="alert"></p>
      <div class="dialog-actions"><button id="patient-cancel" class="secondary-button" type="button">Cancel</button><button id="patient-submit" class="primary-button" type="submit">Save patient</button></div>
    </form>
  </dialog>

  <dialog id="appointment-edit-dialog" class="action-dialog">
    <form id="appointment-edit-form" class="dialog-panel">
      <div class="dialog-icon edit-dialog-icon" aria-hidden="true">▦</div>
      <p class="eyebrow">VISIT DETAILS</p>
      <h2>Edit appointment</h2>
      <div class="field">
        <span>Patient</span>
        <input id="edit-appointment-patient" name="patient_id" type="hidden">
        <div class="appointment-patient-readonly">
          <span class="appointment-patient-copy">
            <strong id="edit-appointment-patient-name"></strong>
            <small id="edit-appointment-patient-phone"></small>
          </span>
          <span id="edit-appointment-patient-archived" class="patient-archived-badge" hidden>Archived</span>
        </div>
        <small class="appointment-patient-hint">To change the patient, cancel this appointment and create a new one.</small>
      </div>
      <label class="field" for="edit-appointment-reason">Visit reason<input id="edit-appointment-reason" name="reason" maxlength="160" required></label>
      <label class="field" for="edit-appointment-date">Date and time<input id="edit-appointment-date" name="scheduled_at" type="datetime-local" required></label>
      <label class="field" for="edit-appointment-status">Status<select id="edit-appointment-status" name="status"><option value="scheduled">Scheduled</option><option value="completed">Completed</option><option value="cancelled">Cancelled</option></select></label>
      <label class="field" for="edit-appointment-notes">Visit notes<textarea id="edit-appointment-notes" name="notes" maxlength="2000" rows="3"></textarea></label>
      <p id="appointment-edit-error" class="form-error" role="alert"></p>
      <div class="dialog-actions"><button id="appointment-edit-cancel" class="secondary-button" type="button">Cancel</button><button id="appointment-edit-submit" class="primary-button" type="submit">Save changes</button></div>
    </form>
  </dialog>

  <dialog id="delete-dialog" class="action-dialog">
    <div class="dialog-panel">
      <div class="dialog-icon delete-dialog-icon" aria-hidden="true">×</div>
      <p class="eyebrow">A QUICK CHECK</p>
      <h2>Remove this appointment?</h2>
      <p class="dialog-copy">This will permanently remove the visit from the schedule.</p>
      <p id="delete-appointment-label" class="delete-task-preview"></p>
      <p id="delete-error" class="form-error" role="alert"></p>
      <div class="dialog-actions"><button id="delete-cancel" class="secondary-button" type="button">Keep appointment</button><button id="delete-confirm" class="danger-button" type="button">Remove appointment</button></div>
    </div>
  </dialog>

  <dialog id="delete-patient-dialog" class="action-dialog">
    <div class="dialog-panel">
      <div class="dialog-icon delete-dialog-icon" aria-hidden="true">×</div>
      <p class="eyebrow">PATIENT RECORD</p>
      <h2>Delete patient record permanently?</h2>
      <p class="dialog-copy">Only records without appointments can be permanently deleted. Archive patients with appointment history instead.</p>
      <p id="delete-patient-label" class="delete-task-preview"></p>
      <p id="delete-patient-error" class="form-error" role="alert"></p>
      <div class="dialog-actions"><button id="delete-patient-cancel" class="secondary-button" type="button">Cancel</button><button id="delete-patient-confirm" class="danger-button" type="button">Delete permanently</button></div>
    </div>
  </dialog>

  <dialog id="archive-patient-dialog" class="action-dialog">
    <div class="dialog-panel">
      <div class="dialog-icon edit-dialog-icon" aria-hidden="true">▣</div>
      <p class="eyebrow">PATIENT RECORD</p>
      <h2>Archive this patient?</h2>
      <p class="dialog-copy">The record and appointment history will be kept. Archived patients won’t be available for new appointments, and you can restore them later.</p>
      <p id="archive-patient-label" class="delete-task-preview"></p>
      <p id="archive-patient-error" class="form-error" role="alert"></p>
      <div class="dialog-actions"><button id="archive-patient-cancel" class="secondary-button" type="button">Cancel</button><button id="archive-patient-confirm" class="primary-button" type="button">Archive patient</button></div>
    </div>
  </dialog>

  <dialog id="logout-dialog" class="action-dialog">
    <div class="dialog-panel">
      <div class="dialog-icon logout-dialog-icon" aria-hidden="true">↗</div><p class="eyebrow">BEFORE YOU GO</p><h2>Sign out of MediSched?</h2>
      <p class="dialog-copy">Your clinic records are saved. Sign out when you’re finished.</p><p id="logout-error" class="form-error" role="alert"></p>
      <div class="dialog-actions"><button id="logout-cancel" class="secondary-button" type="button">Stay signed in</button><button id="logout-confirm" class="danger-button" type="button">Sign out</button></div>
    </div>
  </dialog>

  <dialog id="auth-dialog" class="auth-dialog">
    <div class="auth-panel">
      <div class="auth-brand"><span class="brand-mark" aria-hidden="true">✚</span><span>Medi<span class="brand-accent">Sched</span></span></div>
      <p class="eyebrow" id="auth-eyebrow">CLINIC STAFF ACCESS</p><h2 id="auth-title">Welcome back.</h2><p class="auth-intro" id="auth-intro">Sign in to manage your clinic schedule.</p>
      <form id="auth-form">
        <label id="name-field" class="field" hidden for="auth-name">Your name<input id="auth-name" name="name" type="text" maxlength="60" autocomplete="name"></label>
        <label class="field" for="auth-email">Work email<input id="auth-email" name="email" type="email" maxlength="254" autocomplete="email" required></label>
        <label class="field" for="auth-password">Password<input id="auth-password" name="password" type="password" minlength="8" maxlength="72" autocomplete="current-password" required></label>
        <p id="auth-error" class="form-error" role="alert"></p><button id="auth-submit" class="primary-button auth-submit" type="submit">Sign in <span aria-hidden="true">→</span></button>
      </form>
      <p class="auth-switch"><span id="auth-switch-copy">New clinic workspace?</span> <button id="auth-switch" type="button">Create staff account</button></p>
      <p class="privacy-note">Demo application. Do not enter real patient information unless deployed in a properly secured, compliant environment.</p>
    </div>
  </dialog>
</body>
</html>
