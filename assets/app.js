const state = {
  csrf: '',
  user: null,
  tasks: [],
  filter: 'all',
  search: '',
  authMode: 'login',
  dashboard: { due_today: 0, high_priority: 0, completed_this_week: 0, weekly_goal: 10 },
};

const elements = {
  authDialog: document.querySelector('#auth-dialog'),
  authForm: document.querySelector('#auth-form'),
  authError: document.querySelector('#auth-error'),
  nameField: document.querySelector('#name-field'),
  authTitle: document.querySelector('#auth-title'),
  authEyebrow: document.querySelector('#auth-eyebrow'),
  authIntro: document.querySelector('#auth-intro'),
  authSubmit: document.querySelector('#auth-submit'),
  authSwitchCopy: document.querySelector('#auth-switch-copy'),
  authSwitch: document.querySelector('#auth-switch'),
  taskForm: document.querySelector('#task-form'),
  taskTitle: document.querySelector('#task-title'),
  taskDescription: document.querySelector('#task-description'),
  taskPriority: document.querySelector('#task-priority'),
  taskDueDate: document.querySelector('#task-due-date'),
  taskRecurrence: document.querySelector('#task-recurrence'),
  themeToggle: document.querySelector('#theme-toggle'),
  taskList: document.querySelector('#task-list'),
  editRecurrence: document.querySelector('#edit-recurrence'),
  emptyState: document.querySelector('#empty-state'),
  loadingState: document.querySelector('#loading-state'),
  toast: document.querySelector('#toast'),
  editDialog: document.querySelector('#edit-dialog'),
  editForm: document.querySelector('#edit-form'),
  editTitle: document.querySelector('#edit-title'),
  editDescription: document.querySelector('#edit-description'),
  editPriority: document.querySelector('#edit-priority'),
  editDueDate: document.querySelector('#edit-due-date'),
  editError: document.querySelector('#edit-error'),
  editSubmit: document.querySelector('#edit-submit'),
  deleteDialog: document.querySelector('#delete-dialog'),
  deleteTitle: document.querySelector('#delete-task-title'),
  deleteError: document.querySelector('#delete-error'),
  deleteConfirm: document.querySelector('#delete-confirm'),
  logoutDialog: document.querySelector('#logout-dialog'),
  logoutError: document.querySelector('#logout-error'),
  logoutConfirm: document.querySelector('#logout-confirm'),
};

let toastTimer;
let editingTaskId = null;
let deletingTaskId = null;

function initializePriorityPickers() {
  document.querySelectorAll('.priority-select').forEach((select) => {
    const picker = document.createElement('div');
    picker.className = 'priority-picker';
    const trigger = document.createElement('button');
    trigger.type = 'button';
    trigger.className = 'priority-picker-trigger';
    trigger.setAttribute('aria-label', 'Priority');
    trigger.setAttribute('aria-haspopup', 'listbox');
    trigger.setAttribute('aria-expanded', 'false');
    trigger.setAttribute('aria-controls', `${select.id}-options`);
    const value = document.createElement('span');
    const caret = document.createElement('span');
    caret.className = 'priority-picker-caret';
    caret.setAttribute('aria-hidden', 'true');
    caret.textContent = '⌄';
    trigger.append(value, caret);

    const menu = document.createElement('div');
    menu.id = `${select.id}-options`;
    menu.className = 'priority-picker-menu';
    menu.setAttribute('role', 'listbox');
    menu.setAttribute('aria-label', 'Priority');
    menu.hidden = true;

    const options = [...select.options].map((option) => {
      const choice = document.createElement('button');
      choice.type = 'button';
      choice.className = 'priority-picker-choice';
      choice.dataset.priority = option.value;
      choice.setAttribute('role', 'option');
      choice.textContent = option.textContent;
      choice.addEventListener('click', () => {
        select.value = option.value;
        select.dispatchEvent(new Event('change', { bubbles: true }));
        syncPriorityPicker(select);
        closePriorityPicker(picker, true);
      });
      return choice;
    });
    menu.append(...options);
    picker.append(trigger, menu);
    select.hidden = true;
    select.setAttribute('aria-hidden', 'true');
    select.tabIndex = -1;
    select.after(picker);

    trigger.addEventListener('click', () => {
      if (menu.hidden) {
        closePriorityPickers();
        menu.hidden = false;
        trigger.setAttribute('aria-expanded', 'true');
        menu.querySelector('[aria-selected="true"]')?.focus();
      } else {
        closePriorityPicker(picker);
      }
    });

    trigger.addEventListener('keydown', (event) => {
      if (event.key !== 'ArrowDown' && event.key !== 'ArrowUp') return;
      event.preventDefault();
      if (menu.hidden) {
        menu.hidden = false;
        trigger.setAttribute('aria-expanded', 'true');
      }
      const selectedIndex = options.findIndex((choice) => choice.getAttribute('aria-selected') === 'true');
      const nextIndex = event.key === 'ArrowDown'
        ? (selectedIndex + 1) % options.length
        : (selectedIndex - 1 + options.length) % options.length;
      options[nextIndex].focus();
    });

    menu.addEventListener('keydown', (event) => {
      const currentIndex = options.indexOf(document.activeElement);
      let nextIndex = currentIndex;
      if (event.key === 'ArrowDown') nextIndex = (currentIndex + 1) % options.length;
      else if (event.key === 'ArrowUp') nextIndex = (currentIndex - 1 + options.length) % options.length;
      else if (event.key === 'Home') nextIndex = 0;
      else if (event.key === 'End') nextIndex = options.length - 1;
      else if (event.key === 'Escape') {
        event.preventDefault();
        closePriorityPicker(picker, true);
        return;
      } else if (event.key === 'Tab') {
        closePriorityPicker(picker);
        return;
      } else {
        return;
      }
      event.preventDefault();
      options[nextIndex].focus();
    });

    syncPriorityPicker(select);
  });

  document.addEventListener('pointerdown', (event) => {
    if (!(event.target instanceof Element) || !event.target.closest('.priority-picker')) {
      closePriorityPickers();
    }
  });
}

function syncPriorityPicker(select) {
  const picker = select.nextElementSibling;
  if (!picker?.classList.contains('priority-picker')) return;
  const selected = select.selectedOptions[0];
  picker.querySelector('.priority-picker-trigger span:first-child').textContent = selected.textContent;
  picker.querySelectorAll('.priority-picker-choice').forEach((choice) => {
    choice.setAttribute('aria-selected', String(choice.dataset.priority === select.value));
  });
}

function closePriorityPicker(picker, restoreFocus = false) {
  const menu = picker.querySelector('.priority-picker-menu');
  if (menu.hidden) return;
  menu.hidden = true;
  const trigger = picker.querySelector('.priority-picker-trigger');
  trigger.setAttribute('aria-expanded', 'false');
  if (restoreFocus) trigger.focus();
}

function closePriorityPickers() {
  document.querySelectorAll('.priority-picker').forEach((picker) => closePriorityPicker(picker));
}

initializePriorityPickers();

function applyTheme(theme, persist = false) {
  const isDark = theme === 'dark';
  document.documentElement.dataset.theme = isDark ? 'dark' : 'light';
  document.documentElement.style.colorScheme = isDark ? 'dark' : 'light';
  elements.themeToggle.setAttribute('aria-pressed', String(isDark));
  elements.themeToggle.setAttribute('aria-label', isDark ? 'Switch to light mode' : 'Switch to dark mode');
  elements.themeToggle.title = isDark ? 'Switch to light mode' : 'Switch to dark mode';
  document.querySelector('meta[name="theme-color"]').content = isDark ? '#151b18' : '#f6f7f4';
  if (persist) {
    try {
      localStorage.setItem('daymark-theme', isDark ? 'dark' : 'light');
    } catch {
      showToast('Theme changed, but this browser could not save your preference.');
    }
  }
}

applyTheme(document.documentElement.dataset.theme || 'light');
elements.themeToggle.addEventListener('click', () => {
  const nextTheme = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
  applyTheme(nextTheme, true);
});

function showToast(message) {
  elements.toast.textContent = message;
  elements.toast.classList.add('visible');
  window.clearTimeout(toastTimer);
  toastTimer = window.setTimeout(() => elements.toast.classList.remove('visible'), 2800);
}

async function api(action, { method = 'GET', body } = {}) {
  const headers = { Accept: 'application/json' };
  if (body !== undefined) {
    headers['Content-Type'] = 'application/json';
    headers['X-CSRF-Token'] = state.csrf;
  }
  const response = await fetch(`api.php?action=${encodeURIComponent(action)}`, {
    method,
    headers,
    credentials: 'same-origin',
    body: body === undefined ? undefined : JSON.stringify(body),
  });
  let payload;
  try {
    payload = await response.json();
  } catch {
    throw new Error('The server returned an unexpected response.');
  }
  if (!response.ok) {
    throw Object.assign(new Error(payload.error || 'Something went wrong.'), {
      code: payload.code,
      status: response.status,
    });
  }
  return payload;
}

function setAuthMode(mode) {
  state.authMode = mode;
  const registering = mode === 'register';
  elements.nameField.hidden = !registering;
  document.querySelector('#auth-name').required = registering;
  elements.authTitle.textContent = registering ? 'A fresh start.' : 'Welcome back.';
  elements.authEyebrow.textContent = registering ? 'MAKE YOUR SPACE' : 'YOUR SPACE STARTS HERE';
  elements.authIntro.textContent = registering
    ? 'Create your account and make room for what matters.'
    : 'Sign in to pick up where you left off.';
  const arrow = document.createElement('span');
  arrow.setAttribute('aria-hidden', 'true');
  arrow.textContent = '→';
  elements.authSubmit.replaceChildren(
    document.createTextNode(registering ? 'Create account ' : 'Sign in '),
    arrow,
  );
  elements.authSwitchCopy.textContent = registering ? 'Already have an account?' : 'New to Daymark?';
  elements.authSwitch.textContent = registering ? 'Sign in' : 'Create an account';
  elements.authError.textContent = '';
  document.querySelector('#auth-password').setAttribute('autocomplete', registering ? 'new-password' : 'current-password');
}

function openAuth() {
  setAuthMode('login');
  if (!elements.authDialog.open) elements.authDialog.showModal();
  window.setTimeout(() => document.querySelector('#auth-email').focus(), 50);
}

function updateHeader() {
  const name = state.user?.name || 'Your account';
  document.querySelector('#profile-name').textContent = name;
  document.querySelector('#avatar').textContent = Array.from(name)[0]?.toUpperCase() || 'D';
  const hour = new Date().getHours();
  const greeting = hour < 12 ? 'Good morning' : hour < 18 ? 'Good afternoon' : 'Good evening';
  document.querySelector('#greeting').replaceChildren(
    document.createTextNode(`${greeting}, `),
    Object.assign(document.createElement('span'), { textContent: name.split(' ')[0] + '.' }),
  );
  document.querySelector('#date-label').textContent = new Intl.DateTimeFormat(undefined, {
    weekday: 'long', month: 'long', day: 'numeric',
  }).format(new Date()).toUpperCase();
}

function updateCounts() {
  const pending = state.tasks.filter((task) => task.status === 'pending').length;
  const completed = state.tasks.length - pending;
  const percent = state.tasks.length ? Math.round((completed / state.tasks.length) * 100) : 0;
  const today = localDateString();
  const dueToday = state.tasks.filter((task) => task.status === 'pending' && task.due_date === today).length;
  document.querySelector('#all-count').textContent = state.tasks.length;
  document.querySelector('#pending-count').textContent = pending;
  document.querySelector('#completed-count').textContent = completed;
  document.querySelector('#today-count').textContent = dueToday;
  document.querySelector('#progress-value').textContent = `${percent}%`;
  document.querySelector('#progress-ring').style.setProperty('--progress', `${percent * 3.6}deg`);
  document.querySelector('#progress-copy').textContent = state.tasks.length
    ? completed === state.tasks.length ? 'Everything is complete!' : `${pending} ${pending === 1 ? 'task' : 'tasks'} to go`
    : 'You’ve got this';
  document.querySelector('#dashboard-today').textContent = state.dashboard.due_today;
  document.querySelector('#dashboard-priority').textContent = state.dashboard.high_priority;
  document.querySelector('#dashboard-week').textContent = state.dashboard.completed_this_week;
  document.querySelector('#week-progress-bar').style.width =
    `${Math.min(100, state.dashboard.completed_this_week / state.dashboard.weekly_goal * 100)}%`;
}

function localDateString(date = new Date()) {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}

function formatDueDate(value) {
  const [year, month, day] = value.split('-').map(Number);
  return new Intl.DateTimeFormat(undefined, { month: 'short', day: 'numeric' })
    .format(new Date(year, month - 1, day));
}

function createTaskElement(task) {
  const article = document.createElement('article');
  article.className = `task-item${task.status === 'completed' ? ' is-completed' : ''}`;
  article.dataset.taskId = task.id;

  const checkbox = document.createElement('button');
  checkbox.type = 'button';
  checkbox.className = 'task-check';
  checkbox.setAttribute('aria-label', task.status === 'completed' ? 'Mark task in progress' : 'Mark task complete');
  checkbox.textContent = task.status === 'completed' ? '✓' : '';
  checkbox.addEventListener('click', () => updateTask(task.id, {
    status: task.status === 'completed' ? 'pending' : 'completed',
  }));
  article.append(checkbox);

  const content = document.createElement('div');
  content.className = 'task-content';
  const title = document.createElement('h3');
  title.textContent = task.title;
  content.append(title);
  if (task.description) {
    const description = document.createElement('p');
    description.textContent = task.description;
    content.append(description);
  }
  const metadata = document.createElement('div');
  metadata.className = 'task-metadata';
  const priority = document.createElement('span');
  priority.className = `priority-badge priority-${task.priority}`;
  priority.textContent = `${task.priority} priority`;
  metadata.append(priority);
  if (task.due_date) {
    const dueDate = document.createElement('span');
    const overdue = task.status === 'pending' && task.due_date < localDateString();
    dueDate.className = `due-date-badge${overdue ? ' is-overdue' : ''}`;
    dueDate.textContent = overdue ? `Overdue · ${formatDueDate(task.due_date)}` : `Due ${formatDueDate(task.due_date)}`;
    metadata.append(dueDate);
  }
  if (task.recurrence !== 'none') {
    const repeat = document.createElement('span');
    repeat.className = 'recurrence-badge';
    repeat.textContent = `↻ ${task.recurrence}`;
    metadata.append(repeat);
  }
  content.append(metadata);
  article.append(content);

  const actions = document.createElement('div');
  actions.className = 'task-actions';
  const edit = document.createElement('button');
  edit.type = 'button';
  edit.className = 'icon-button task-action';
  edit.setAttribute('aria-label', 'Edit task');
  edit.title = 'Edit task';
  edit.textContent = '✎';
  edit.addEventListener('click', () => openEditDialog(task));
  const remove = document.createElement('button');
  remove.type = 'button';
  remove.className = 'icon-button task-action delete-action';
  remove.setAttribute('aria-label', 'Delete task');
  remove.title = 'Delete task';
  remove.textContent = '×';
  remove.addEventListener('click', () => openDeleteDialog(task));
  actions.append(edit, remove);
  article.append(actions);
  return article;
}

function setFilter(filter) {
  state.filter = filter;
  document.querySelectorAll('.nav-item').forEach((item) => item.classList.toggle('active', item.dataset.filter === filter));
  renderTasks();
}

function visibleTasks() {
  const today = localDateString();
  return state.tasks.filter((task) => {
    if (state.filter === 'all') return true;
    if (state.filter === 'pending' || state.filter === 'completed') return task.status === state.filter;
    if (state.filter === 'today') return task.status === 'pending' && task.due_date === today;
    return false;
  }).filter((task) => `${task.title} ${task.description}`
    .toLocaleLowerCase().includes(state.search.toLocaleLowerCase()))
    .sort((left, right) => {
      const statusOrder = { pending: 0, completed: 1 };
      const priorityOrder = { high: 0, medium: 1, low: 2 };
      return statusOrder[left.status] - statusOrder[right.status]
        || priorityOrder[left.priority] - priorityOrder[right.priority]
        || (left.due_date || '9999-12-31').localeCompare(right.due_date || '9999-12-31')
        || right.created_at.localeCompare(left.created_at)
        || Number(right.id) - Number(left.id);
    });
}

function renderTasks() {
  updateCounts();
  const filtered = visibleTasks();
  elements.taskList.replaceChildren(...filtered.map(createTaskElement));
  document.querySelector('#visible-count').textContent = filtered.length;
  const labels = {
    all: 'All tasks',
    today: 'Today',
    pending: 'In progress',
    completed: 'Completed',
  };
  document.querySelector('#list-title').firstChild.textContent = `${labels[state.filter] || 'Tasks'} `;
  document.querySelector('#list-eyebrow').textContent = 'YOUR LIST';
  elements.emptyState.hidden = filtered.length > 0;
  if (state.tasks.length === 0) {
    document.querySelector('#empty-title').textContent = 'A fresh start.';
    document.querySelector('#empty-copy').textContent = 'Add your first task above and make today yours.';
  } else if (filtered.length === 0) {
    document.querySelector('#empty-title').textContent = state.search ? 'Nothing found.' : 'All caught up.';
    document.querySelector('#empty-copy').textContent = state.search
      ? 'Try another search or check your filters.'
      : 'There’s nothing here right now. Enjoy the breathing room.';
  }
}

async function loadWorkspace() {
  const [taskPayload, dashboardPayload] = await Promise.all([
    api('tasks'),
    api('dashboard'),
  ]);
  state.tasks = taskPayload.tasks;
  state.dashboard = dashboardPayload;
  elements.loadingState.hidden = true;
  renderTasks();
}

async function refreshDashboard() {
  state.dashboard = await api('dashboard');
  updateCounts();
}

async function refreshAfterMutation(refreshers) {
  const results = await Promise.allSettled(refreshers);
  const failures = results.filter((result) => result.status === 'rejected');
  if (failures.length) {
    failures.forEach((result) => console.error('A saved change could not be fully refreshed.', result.reason));
    showToast('The change was saved, but some details could not refresh. Reload the page to update them.');
  }
}

async function updateTask(id, changes, errorElement = null) {
  try {
    const payload = await api('update-task', { method: 'POST', body: { id, ...changes } });
    const { task } = payload;
    state.tasks = state.tasks.map((item) => item.id === task.id ? task : item);
    if (payload.next_task) state.tasks.unshift(payload.next_task);
    renderTasks();
    if (changes.status) {
      showToast(payload.next_task ? 'Completed. Your next repeat is ready.' : changes.status === 'completed' ? 'One more thing done. Nice work!' : 'Task moved back to your list.');
      await refreshAfterMutation([refreshDashboard()]);
    } else {
      showToast('Task updated.');
    }
    return true;
  } catch (error) {
    if (errorElement) errorElement.textContent = error.message;
    else showToast(error.message);
    return false;
  }
}

function openEditDialog(task) {
  editingTaskId = task.id;
  elements.editTitle.value = task.title;
  elements.editDescription.value = task.description;
  elements.editPriority.value = task.priority;
  syncPriorityPicker(elements.editPriority);
  elements.editDueDate.value = task.due_date || '';
  elements.editRecurrence.value = task.recurrence || 'none';
  elements.editError.textContent = '';
  elements.editSubmit.disabled = false;
  elements.editDialog.showModal();
  elements.editTitle.focus();
}

function openDeleteDialog(task) {
  deletingTaskId = task.id;
  elements.deleteTitle.textContent = task.title;
  elements.deleteError.textContent = '';
  elements.deleteConfirm.disabled = false;
  elements.deleteDialog.showModal();
}

elements.editForm.addEventListener('submit', async (event) => {
  event.preventDefault();
  if (editingTaskId === null) return;
  elements.editError.textContent = '';
  elements.editSubmit.disabled = true;
  const saved = await updateTask(editingTaskId, {
    title: elements.editTitle.value,
    description: elements.editDescription.value,
    priority: elements.editPriority.value,
    due_date: elements.editDueDate.value,
    recurrence: elements.editRecurrence.value,
  }, elements.editError);
  elements.editSubmit.disabled = false;
  if (saved) {
    elements.editDialog.close();
    editingTaskId = null;
  }
});

document.querySelector('#edit-cancel').addEventListener('click', () => elements.editDialog.close());
elements.editDialog.addEventListener('close', () => {
  editingTaskId = null;
  elements.editForm.reset();
  elements.editError.textContent = '';
});

async function deleteTask() {
  if (deletingTaskId === null) return;
  const id = deletingTaskId;
  elements.deleteError.textContent = '';
  elements.deleteConfirm.disabled = true;
  try {
    await api('delete-task', { method: 'POST', body: { id } });
    state.tasks = state.tasks.filter((task) => task.id !== id);
    elements.deleteDialog.close();
    renderTasks();
    showToast('Task removed.');
    await refreshAfterMutation([refreshDashboard()]);
  } catch (error) {
    elements.deleteError.textContent = error.message;
  } finally {
    elements.deleteConfirm.disabled = false;
  }
}

document.querySelector('#delete-cancel').addEventListener('click', () => elements.deleteDialog.close());
elements.deleteConfirm.addEventListener('click', deleteTask);
elements.deleteDialog.addEventListener('close', () => {
  deletingTaskId = null;
  elements.deleteTitle.textContent = '';
  elements.deleteError.textContent = '';
});

elements.authSwitch.addEventListener('click', () => setAuthMode(state.authMode === 'login' ? 'register' : 'login'));
elements.authForm.addEventListener('submit', async (event) => {
  event.preventDefault();
  elements.authError.textContent = '';
  const form = new FormData(elements.authForm);
  const action = state.authMode === 'register' ? 'register' : 'login';
  const body = { email: form.get('email'), password: form.get('password') };
  if (state.authMode === 'register') body.name = form.get('name');

  elements.authSubmit.disabled = true;
  try {
    const payload = await api(action, { method: 'POST', body });
    state.user = payload.user;
    state.csrf = payload.csrf;
    elements.authDialog.close();
    elements.authForm.reset();
    updateHeader();
    await loadWorkspace();
  } catch (error) {
    if (state.user) {
      elements.loadingState.hidden = true;
      showToast(error.message);
    } else {
      elements.authError.replaceChildren(document.createTextNode(error.message));
      if (error.code === 'account_not_found') {
        const createAccount = document.createElement('button');
        createAccount.type = 'button';
        createAccount.className = 'auth-error-action';
        createAccount.textContent = 'Create an account';
        createAccount.addEventListener('click', () => {
          setAuthMode('register');
          document.querySelector('#auth-name').focus();
        });
        elements.authError.append(document.createTextNode(' '), createAccount);
      }
    }
  } finally {
    elements.authSubmit.disabled = false;
  }
});

elements.taskForm.addEventListener('submit', async (event) => {
  event.preventDefault();
  const title = elements.taskTitle.value.trim();
  if (!title) return;
  const submit = elements.taskForm.querySelector('button[type="submit"]');
  submit.disabled = true;
  try {
    const payload = await api('create-task', {
      method: 'POST',
      body: {
        title,
        description: elements.taskDescription.value.trim(),
        priority: elements.taskPriority.value,
        due_date: elements.taskDueDate.value,
        recurrence: elements.taskRecurrence.value,
      },
    });
    state.tasks.unshift(payload.task);
    elements.taskForm.reset();
    syncPriorityPicker(elements.taskPriority);
    renderTasks();
    showToast('Added to your list.');
    elements.taskTitle.focus();
    await refreshAfterMutation([refreshDashboard()]);
  } catch (error) {
    showToast(error.message);
  } finally {
    submit.disabled = false;
  }
});

document.querySelectorAll('.nav-item').forEach((button) => {
  button.addEventListener('click', () => setFilter(button.dataset.filter));
});

document.querySelector('#search-input').addEventListener('input', (event) => {
  state.search = event.target.value.trim();
  renderTasks();
});

document.addEventListener('keydown', (event) => {
  if (event.key.toLowerCase() !== 'n' || event.ctrlKey || event.metaKey || event.altKey) return;
  const target = event.target;
  const typing = target instanceof HTMLElement &&
    (target.isContentEditable || ['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName));
  if (typing || document.querySelector('dialog[open]')) return;
  event.preventDefault();
  elements.taskTitle.focus();
});

document.querySelector('#logout-button').addEventListener('click', () => {
  elements.logoutError.textContent = '';
  elements.logoutConfirm.disabled = false;
  elements.logoutDialog.showModal();
});

document.querySelector('#logout-cancel').addEventListener('click', () => elements.logoutDialog.close());
elements.logoutConfirm.addEventListener('click', async () => {
  elements.logoutError.textContent = '';
  elements.logoutConfirm.disabled = true;
  try {
    const payload = await api('logout', { method: 'POST', body: {} });
    state.user = null;
    state.tasks = [];
    state.csrf = payload.csrf;
    elements.logoutDialog.close();
    openAuth();
  } catch (error) {
    elements.logoutError.textContent = error.message;
  } finally {
    elements.logoutConfirm.disabled = false;
  }
});

elements.authDialog.addEventListener('cancel', (event) => {
  if (!state.user) event.preventDefault();
});

async function initialize() {
  try {
    const payload = await api('bootstrap');
    state.csrf = payload.csrf;
    state.user = payload.user;
    if (state.user) {
      updateHeader();
      await loadWorkspace();
    } else {
      elements.loadingState.hidden = true;
      openAuth();
    }
  } catch (error) {
    elements.loadingState.textContent = error.message;
  }
}

updateHeader();
initialize();
