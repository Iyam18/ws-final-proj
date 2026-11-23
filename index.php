<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#f6f7f4">
  <title>Daymark — make room for what matters</title>
  <script>
    try {
      const savedTheme = localStorage.getItem('daymark-theme');
      const theme = savedTheme || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
      document.documentElement.dataset.theme = theme;
    } catch {
      document.documentElement.dataset.theme = 'light';
    }
  </script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/styles.css">
  <script src="assets/app.js" defer></script>
</head>
<body>
  <main class="app-shell">
    <aside class="sidebar">
      <a class="brand" href="#" aria-label="Daymark home">
        <span class="brand-mark" aria-hidden="true">d</span>
        <span>daymark<span class="brand-period">.</span></span>
      </a>
      <div class="sidebar-caption">YOUR WORKSPACE</div>
      <nav class="navigation" aria-label="Task filters">
        <button class="nav-item active" type="button" data-filter="all">
          <span class="nav-icon" aria-hidden="true">◫</span><span>All tasks</span><span class="nav-count" id="all-count">0</span>
        </button>
        <button class="nav-item" type="button" data-filter="today">
          <span class="nav-icon" aria-hidden="true">☀</span><span>Today</span><span class="nav-count" id="today-count">0</span>
        </button>
        <button class="nav-item" type="button" data-filter="pending">
          <span class="nav-icon" aria-hidden="true">◷</span><span>In progress</span><span class="nav-count" id="pending-count">0</span>
        </button>
        <button class="nav-item" type="button" data-filter="completed">
          <span class="nav-icon" aria-hidden="true">✓</span><span>Completed</span><span class="nav-count" id="completed-count">0</span>
        </button>
      </nav>
      <div class="sidebar-note">
        <span class="note-spark" aria-hidden="true">✳</span>
        <p>Small steps add up.<br><strong>Keep showing up.</strong></p>
      </div>
      <div class="profile">
        <div class="avatar" id="avatar">D</div>
        <div class="profile-copy"><strong id="profile-name">Your account</strong><span>Personal workspace</span></div>
        <button class="icon-button logout-button" id="logout-button" type="button" aria-label="Sign out" title="Sign out">↗</button>
      </div>
    </aside>

    <section class="main-panel">
      <header class="topbar">
        <span class="date-label" id="date-label"></span>
        <div class="topbar-actions">
          <span class="secure-label"><span class="secure-dot"></span> Your space, your pace</span>
          <button id="theme-toggle" class="theme-toggle" type="button" aria-label="Switch to dark mode" aria-pressed="false" title="Switch to dark mode">
            <span class="theme-icon theme-sun" aria-hidden="true">☀</span>
            <span class="theme-icon theme-moon" aria-hidden="true">☾</span>
            <span class="theme-knob" aria-hidden="true"></span>
          </button>
        </div>
      </header>
      <section class="content">
        <div class="welcome-row">
          <div>
            <p class="eyebrow">A LITTLE PROGRESS, EVERY DAY</p>
            <h1 id="greeting">Make today <span>count.</span></h1>
            <p class="subtitle">A clear mind starts with a clear list.</p>
          </div>
          <div class="progress-card">
            <div class="progress-ring" id="progress-ring"><span id="progress-value">0%</span></div>
            <div><strong>Daily progress</strong><span id="progress-copy">You’ve got this</span></div>
          </div>
        </div>

        <section class="dashboard-grid" aria-label="Task overview">
          <article class="dashboard-card today-card">
            <div class="dashboard-card-top"><span class="dashboard-icon">☀</span><span class="dashboard-label">DUE TODAY</span></div>
            <strong id="dashboard-today">0</strong><span>tasks to focus on</span>
          </article>
          <article class="dashboard-card priority-card">
            <div class="dashboard-card-top"><span class="dashboard-icon">◆</span><span class="dashboard-label">HIGH PRIORITY</span></div>
            <strong id="dashboard-priority">0</strong><span>items need attention</span>
          </article>
          <article class="dashboard-card week-card">
            <div class="dashboard-card-top"><span class="dashboard-icon">↗</span><span class="dashboard-label">THIS WEEK</span></div>
            <strong id="dashboard-week">0</strong><span>tasks completed</span>
            <div class="week-progress"><span id="week-progress-bar"></span></div>
          </article>
        </section>

        <section class="composer-card" aria-label="Add a task">
          <div class="composer-heading"><span class="composer-icon" aria-hidden="true">✳</span><div><h2>What’s on your mind?</h2><p>Get it out of your head and onto your list.</p></div></div>
          <form id="task-form" class="task-form">
            <label class="sr-only" for="task-title">Task title</label>
            <div class="quick-title-wrap"><input id="task-title" name="title" type="text" maxlength="120" placeholder="e.g. Plan the week ahead" required><kbd class="quick-key">N</kbd></div>
            <button class="primary-button" type="submit"><span aria-hidden="true">＋</span> Add task</button>
            <label class="sr-only" for="task-description">Optional task details</label>
            <input id="task-description" class="description-input" name="description" type="text" maxlength="1000" placeholder="Add a little detail (optional)">
            <div class="task-options">
              <div class="task-option priority-option">
                <span class="option-label"><span class="option-icon" aria-hidden="true">◆</span> Priority</span>
                <select id="task-priority" name="priority" class="priority-select">
                  <option value="low">Low</option>
                  <option value="medium" selected>Medium</option>
                  <option value="high">High</option>
                </select>
              </div>
              <label class="task-option date-option" for="task-due-date">
                <span class="option-label"><span class="option-icon" aria-hidden="true">▦</span> Due date</span>
                <input id="task-due-date" name="due_date" type="date">
              </label>
              <label class="task-option" for="task-recurrence">
                <span class="option-label"><span class="option-icon recurrence-option-icon" aria-hidden="true">⟳</span> Repeat</span>
                <select id="task-recurrence" name="recurrence">
                  <option value="none">No repeat</option>
                  <option value="daily">Every day</option>
                  <option value="weekly">Every week</option>
                  <option value="monthly">Every month</option>
                </select>
              </label>
            </div>
          </form>
        </section>

        <div class="list-heading">
          <div><p class="eyebrow" id="list-eyebrow">YOUR LIST</p><h2 id="list-title">All tasks <span id="visible-count">0</span></h2></div>
          <div class="list-tools">
            <label class="search-box"><span aria-hidden="true">⌕</span><span class="sr-only">Search tasks</span><input id="search-input" type="search" placeholder="Find a task..." autocomplete="off"></label>
          </div>
        </div>
        <div id="task-list" class="task-list" aria-live="polite"></div>
        <div id="empty-state" class="empty-state" hidden>
          <div class="empty-illustration" aria-hidden="true"><span>✓</span></div>
          <h3 id="empty-title">A fresh start.</h3>
          <p id="empty-copy">Add your first task above and make today yours.</p>
        </div>
        <div id="loading-state" class="loading-state">Getting your space ready…</div>
      </section>
      <footer class="footer"><span>Made for more mindful days.</span><span>DAYMARK <span class="brand-period">✳</span></span></footer>
    </section>
  </main>

  <div id="toast" class="toast" role="status" aria-live="polite"></div>

  <dialog id="edit-dialog" class="action-dialog">
    <form id="edit-form" class="dialog-panel">
      <div class="dialog-icon edit-dialog-icon" aria-hidden="true">✎</div>
      <p class="eyebrow">MAKE IT YOURS</p>
      <h2>Edit task</h2>
      <p class="dialog-copy">Make a quick change and keep moving.</p>
      <label class="auth-field" for="edit-title">Task name<input id="edit-title" name="title" type="text" maxlength="120" required></label>
      <label class="auth-field" for="edit-description">Details <span class="optional-label">OPTIONAL</span><textarea id="edit-description" name="description" maxlength="1000" rows="3"></textarea></label>
      <div class="edit-task-options">
        <div class="auth-field edit-option-field">
          <span class="option-label"><span class="option-icon" aria-hidden="true">◆</span> Priority</span>
          <select id="edit-priority" name="priority" class="priority-select">
            <option value="low">Low</option>
            <option value="medium">Medium</option>
            <option value="high">High</option>
          </select>
        </div>
        <label class="auth-field edit-option-field" for="edit-due-date">
          <span class="option-label"><span class="option-icon" aria-hidden="true">▦</span> Due date</span>
          <input id="edit-due-date" name="due_date" type="date">
        </label>
        <label class="auth-field edit-option-field" for="edit-recurrence">
          <span class="option-label"><span class="option-icon recurrence-option-icon" aria-hidden="true">⟳</span> Repeat</span>
          <select id="edit-recurrence" name="recurrence">
            <option value="none">No repeat</option>
            <option value="daily">Every day</option>
            <option value="weekly">Every week</option>
            <option value="monthly">Every month</option>
          </select>
        </label>
      </div>
      <p id="edit-error" class="form-error" role="alert"></p>
      <div class="dialog-actions">
        <button id="edit-cancel" class="secondary-button" type="button">Cancel</button>
        <button id="edit-submit" class="primary-button" type="submit">Save changes <span aria-hidden="true">→</span></button>
      </div>
    </form>
  </dialog>

  <dialog id="delete-dialog" class="action-dialog">
    <div class="dialog-panel">
      <div class="dialog-icon delete-dialog-icon" aria-hidden="true">×</div>
      <p class="eyebrow">A QUICK CHECK</p>
      <h2>Remove this task?</h2>
      <p class="dialog-copy">This task will be permanently removed from your list.</p>
      <p id="delete-task-title" class="delete-task-preview"></p>
      <p id="delete-error" class="form-error" role="alert"></p>
      <div class="dialog-actions">
        <button id="delete-cancel" class="secondary-button" type="button">Keep task</button>
        <button id="delete-confirm" class="danger-button" type="button">Yes, delete task</button>
      </div>
    </div>
  </dialog>

  <dialog id="logout-dialog" class="action-dialog">
    <div class="dialog-panel">
      <div class="dialog-icon logout-dialog-icon" aria-hidden="true">↗</div>
      <p class="eyebrow">BEFORE YOU GO</p>
      <h2>Sign out of Daymark?</h2>
      <p class="dialog-copy">Your tasks are saved. You’ll need to sign in again to see them.</p>
      <p id="logout-error" class="form-error" role="alert"></p>
      <div class="dialog-actions">
        <button id="logout-cancel" class="secondary-button" type="button">Stay signed in</button>
        <button id="logout-confirm" class="danger-button" type="button">Sign out</button>
      </div>
    </div>
  </dialog>

  <dialog id="auth-dialog" class="auth-dialog">
    <div class="auth-panel">
      <div class="auth-brand"><span class="brand-mark" aria-hidden="true">d</span><span>daymark<span class="brand-period">.</span></span></div>
      <p class="eyebrow" id="auth-eyebrow">YOUR SPACE STARTS HERE</p>
      <h2 id="auth-title">Welcome back.</h2>
      <p class="auth-intro" id="auth-intro">Sign in to pick up where you left off.</p>
      <form id="auth-form">
        <label id="name-field" class="auth-field" hidden for="auth-name">Your name<input id="auth-name" name="name" type="text" maxlength="60" autocomplete="name"></label>
        <label class="auth-field" for="auth-email">Email address<input id="auth-email" name="email" type="email" maxlength="254" autocomplete="email" required></label>
        <label class="auth-field" for="auth-password">Password<input id="auth-password" name="password" type="password" minlength="8" maxlength="72" autocomplete="current-password" required></label>
        <p id="auth-error" class="form-error" role="alert"></p>
        <button id="auth-submit" class="primary-button auth-submit" type="submit">Sign in <span aria-hidden="true">→</span></button>
      </form>
      <p class="auth-switch"><span id="auth-switch-copy">New to Daymark?</span> <button id="auth-switch" type="button">Create an account</button></p>
      <p class="privacy-note">Your tasks are private and only visible to you.</p>
    </div>
  </dialog>
</body>
</html>
