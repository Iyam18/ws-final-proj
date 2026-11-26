const state = {
  csrf: '',
  user: null,
  patients: [],
  appointments: [],
  dashboard: { today_appointments: 0, completed_this_week: 0, patient_count: 0 },
  page: 'dashboard',
  filter: 'all',
  appointmentSearch: '',
  patientSearch: '',
  patientFilter: 'active',
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
  themeToggle: document.querySelector('#theme-toggle'),
  appointmentForm: document.querySelector('#appointment-form'),
  appointmentPatient: document.querySelector('#appointment-patient'),
  appointmentReason: document.querySelector('#appointment-reason'),
  appointmentDate: document.querySelector('#appointment-date'),
  appointmentNotes: document.querySelector('#appointment-notes'),
  appointmentList: document.querySelector('#appointment-list'),
  todayAppointmentList: document.querySelector('#today-appointment-list'),
  appointmentEmpty: document.querySelector('#appointment-empty'),
  todayEmpty: document.querySelector('#today-empty'),
  patientList: document.querySelector('#patient-list'),
  patientEmpty: document.querySelector('#patient-empty'),
  patientEmptyTitle: document.querySelector('#patient-empty-title'),
  patientEmptyCopy: document.querySelector('#patient-empty-copy'),
  patientDialog: document.querySelector('#patient-dialog'),
  patientForm: document.querySelector('#patient-form'),
  patientDialogTitle: document.querySelector('#patient-dialog-title'),
  patientName: document.querySelector('#patient-name'),
  patientDob: document.querySelector('#patient-dob'),
  patientPhone: document.querySelector('#patient-phone'),
  patientEmail: document.querySelector('#patient-email'),
  patientAddress: document.querySelector('#patient-address'),
  patientAllergies: document.querySelector('#patient-allergies'),
  patientMedicalNotes: document.querySelector('#patient-medical-notes'),
  patientError: document.querySelector('#patient-error'),
  patientSubmit: document.querySelector('#patient-submit'),
  editAppointmentDialog: document.querySelector('#appointment-edit-dialog'),
  editAppointmentForm: document.querySelector('#appointment-edit-form'),
  editAppointmentPatient: document.querySelector('#edit-appointment-patient'),
  editAppointmentPatientName: document.querySelector('#edit-appointment-patient-name'),
  editAppointmentPatientPhone: document.querySelector('#edit-appointment-patient-phone'),
  editAppointmentPatientArchived: document.querySelector('#edit-appointment-patient-archived'),
  editAppointmentReason: document.querySelector('#edit-appointment-reason'),
  editAppointmentDate: document.querySelector('#edit-appointment-date'),
  editAppointmentStatus: document.querySelector('#edit-appointment-status'),
  editAppointmentNotes: document.querySelector('#edit-appointment-notes'),
  editAppointmentError: document.querySelector('#appointment-edit-error'),
  editAppointmentSubmit: document.querySelector('#appointment-edit-submit'),
  deleteDialog: document.querySelector('#delete-dialog'),
  deleteAppointmentLabel: document.querySelector('#delete-appointment-label'),
  deleteError: document.querySelector('#delete-error'),
  deleteConfirm: document.querySelector('#delete-confirm'),
  deletePatientDialog: document.querySelector('#delete-patient-dialog'),
  deletePatientLabel: document.querySelector('#delete-patient-label'),
  deletePatientError: document.querySelector('#delete-patient-error'),
  deletePatientConfirm: document.querySelector('#delete-patient-confirm'),
  archivePatientDialog: document.querySelector('#archive-patient-dialog'),
  archivePatientLabel: document.querySelector('#archive-patient-label'),
  archivePatientError: document.querySelector('#archive-patient-error'),
  archivePatientConfirm: document.querySelector('#archive-patient-confirm'),
  logoutDialog: document.querySelector('#logout-dialog'),
  logoutError: document.querySelector('#logout-error'),
  logoutConfirm: document.querySelector('#logout-confirm'),
  loadingState: document.querySelector('#loading-state'),
  toast: document.querySelector('#toast'),
};

let toastTimer;
let editingPatientId = null;
let deletingPatientId = null;
let archivingPatientId = null;
let deletingAppointmentId = null;
let editingAppointmentId = null;

function showToast(message) {
  elements.toast.textContent = message;
  elements.toast.classList.add('visible');
  window.clearTimeout(toastTimer);
  toastTimer = window.setTimeout(() => elements.toast.classList.remove('visible'), 3200);
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

function applyTheme(theme, persist = false) {
  const dark = theme === 'dark';
  document.documentElement.dataset.theme = dark ? 'dark' : 'light';
  document.documentElement.style.colorScheme = dark ? 'dark' : 'light';
  elements.themeToggle.setAttribute('aria-pressed', String(dark));
  elements.themeToggle.setAttribute('aria-label', dark ? 'Switch to light mode' : 'Switch to dark mode');
  elements.themeToggle.title = dark ? 'Switch to light mode' : 'Switch to dark mode';
  document.querySelector('meta[name="theme-color"]').content = dark ? '#151f20' : '#f3f7f8';
  if (persist) {
    try {
      localStorage.setItem('medisched-theme', dark ? 'dark' : 'light');
    } catch {
      showToast('Theme changed, but this browser could not save your preference.');
    }
  }
}

applyTheme(document.documentElement.dataset.theme || 'light');
elements.themeToggle.addEventListener('click', () => {
  applyTheme(document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark', true);
});

function updateHeader() {
  const name = state.user?.name || 'Clinic staff';
  document.querySelector('#profile-name').textContent = name;
  document.querySelector('#avatar').textContent = Array.from(name)[0]?.toUpperCase() || 'M';
  const hour = new Date().getHours();
  const greeting = hour < 12 ? 'Good morning' : hour < 18 ? 'Good afternoon' : 'Good evening';
  document.querySelector('#greeting').replaceChildren(
    document.createTextNode(`${greeting}, `),
    Object.assign(document.createElement('span'), { textContent: `${name.split(' ')[0]}.` }),
  );
  document.querySelector('#date-label').textContent = new Intl.DateTimeFormat(undefined, {
    weekday: 'long', month: 'long', day: 'numeric',
  }).format(new Date()).toUpperCase();
}

function localDateString(date = new Date()) {
  return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
}

function formatDate(value, options = { weekday: 'short', month: 'short', day: 'numeric' }) {
  const [year, month, day] = value.slice(0, 10).split('-').map(Number);
  return new Intl.DateTimeFormat(undefined, options).format(new Date(year, month - 1, day));
}

function formatTime(value) {
  const [hour, minute] = value.slice(11, 16).split(':').map(Number);
  return new Intl.DateTimeFormat(undefined, { hour: 'numeric', minute: '2-digit' }).format(new Date(2000, 0, 1, hour, minute));
}

function appointmentLocalValue(value) {
  return value.slice(0, 16).replace(' ', 'T');
}

function updateDashboard() {
  document.querySelector('#dashboard-today').textContent = state.dashboard.today_appointments;
  document.querySelector('#dashboard-week').textContent = state.dashboard.completed_this_week;
  document.querySelector('#dashboard-patients').textContent = state.dashboard.patient_count;
  document.querySelector('#nav-patient-count').textContent = state.dashboard.patient_count;
  document.querySelector('#nav-appointment-count').textContent = state.appointments.filter((item) => item.status === 'scheduled').length;
}

function refreshPatientOptions() {
  const activePatients = state.patients.filter((patient) => !patient.archived_at);
  const selected = elements.appointmentPatient.value;
  const placeholder = new Option('Select a patient', '', true, true);
  placeholder.disabled = true;
  placeholder.hidden = true;
  const options = [placeholder];
  for (const patient of activePatients) options.push(new Option(patient.full_name, patient.id));
  elements.appointmentPatient.replaceChildren(...options);
  if ([...elements.appointmentPatient.options].some((option) => option.value === selected)) {
    elements.appointmentPatient.value = selected;
  }
}

function makeButton(label, text, className = 'icon-button task-action') {
  const button = document.createElement('button');
  button.type = 'button';
  button.className = className;
  button.setAttribute('aria-label', label);
  button.title = label;
  button.textContent = text;
  return button;
}

function createAppointmentCard(appointment) {
  const article = document.createElement('article');
  article.className = 'appointment-card';

  const time = document.createElement('div');
  time.className = 'appointment-time';
  const timeValue = document.createElement('strong');
  timeValue.textContent = formatTime(appointment.scheduled_at);
  const dateValue = document.createElement('span');
  dateValue.textContent = formatDate(appointment.scheduled_at);
  time.append(timeValue, dateValue);

  const content = document.createElement('div');
  content.className = 'appointment-content';
  const patient = document.createElement('h3');
  patient.textContent = appointment.patient_name;
  const reason = document.createElement('p');
  reason.textContent = appointment.reason;
  content.append(patient, reason);
  const metadata = document.createElement('div');
  metadata.className = 'appointment-meta';
  const status = document.createElement('span');
  status.className = `meta-badge status-${appointment.status}`;
  status.textContent = appointment.status;
  metadata.append(status);
  content.append(metadata);
  if (appointment.notes) {
    const note = document.createElement('p');
    note.className = 'appointment-note';
    note.textContent = appointment.notes;
    content.append(note);
  }

  const actions = document.createElement('div');
  actions.className = 'appointment-actions';
  if (appointment.status === 'scheduled') {
    const complete = makeButton('Mark appointment completed', '✓');
    complete.addEventListener('click', () => updateAppointment(appointment.id, {
      patient_id: appointment.patient_id,
      reason: appointment.reason,
      notes: appointment.notes,
      scheduled_at: appointmentLocalValue(appointment.scheduled_at),
      status: 'completed',
    }));
    actions.append(complete);
  }
  const edit = makeButton('Edit appointment', '✎');
  edit.addEventListener('click', () => openAppointmentEditor(appointment));
  const remove = makeButton('Remove appointment', '×', 'icon-button task-action delete-action');
  remove.addEventListener('click', () => openAppointmentDelete(appointment));
  actions.append(edit, remove);
  article.append(time, content, actions);
  return article;
}

function filteredAppointments() {
  const today = localDateString();
  const term = state.appointmentSearch.toLocaleLowerCase();
  return state.appointments.filter((appointment) => {
    const appointmentDate = appointment.scheduled_at.slice(0, 10);
    if (state.filter === 'today' &&
        (appointment.status !== 'scheduled' || appointmentDate !== today)) return false;
    if (state.filter === 'upcoming' &&
        (appointment.status !== 'scheduled' || appointmentDate <= today)) return false;
    if (['scheduled', 'completed', 'cancelled'].includes(state.filter) &&
        appointment.status !== state.filter) return false;
    return `${appointment.patient_name} ${appointment.reason} ${appointment.notes}`
      .toLocaleLowerCase().includes(term);
  }).sort((left, right) => {
    const statusRank = { scheduled: 0, completed: 1, cancelled: 2 };
    if (statusRank[left.status] !== statusRank[right.status]) return statusRank[left.status] - statusRank[right.status];
    if (left.status === 'scheduled') return left.scheduled_at.localeCompare(right.scheduled_at);
    return right.scheduled_at.localeCompare(left.scheduled_at);
  });
}

function renderAppointments() {
  const filtered = filteredAppointments();
  elements.appointmentList.replaceChildren(...filtered.map(createAppointmentCard));
  elements.appointmentEmpty.hidden = filtered.length !== 0;
  document.querySelectorAll('.filter-tab[data-filter]').forEach((button) => {
    button.classList.toggle('active', button.dataset.filter === state.filter);
  });

  const today = localDateString();
  const todayAppointments = state.appointments
    .filter((appointment) => appointment.status === 'scheduled' && appointment.scheduled_at.slice(0, 10) === today)
    .sort((left, right) => left.scheduled_at.localeCompare(right.scheduled_at));
  elements.todayAppointmentList.replaceChildren(...todayAppointments.slice(0, 5).map(createAppointmentCard));
  document.querySelector('#today-list-count').textContent = todayAppointments.length;
  elements.todayEmpty.hidden = todayAppointments.length !== 0;
  updateDashboard();
}

function createPatientCard(patient) {
  const card = document.createElement('article');
  card.className = 'patient-card';
  const identity = document.createElement('div');
  identity.className = 'patient-identity';
  const avatar = document.createElement('span');
  avatar.className = 'patient-avatar';
  avatar.textContent = Array.from(patient.full_name)[0]?.toUpperCase() || 'P';
  const nameBlock = document.createElement('div');
  nameBlock.style.minWidth = '0';
  const name = document.createElement('h3');
  name.textContent = patient.full_name;
  if (patient.archived_at) {
    const archivedBadge = document.createElement('span');
    archivedBadge.className = 'patient-archived-badge';
    archivedBadge.textContent = 'Archived';
    name.append(' ', archivedBadge);
  }
  const birth = document.createElement('p');
  birth.textContent = patient.date_of_birth ? `DOB · ${formatDate(patient.date_of_birth, { year: 'numeric', month: 'short', day: 'numeric' })}` : 'Date of birth not added';
  nameBlock.append(name, birth);
  identity.append(avatar, nameBlock);

  const contact = document.createElement('div');
  contact.className = 'patient-contact';
  const phone = document.createElement('span');
  phone.textContent = patient.phone || 'No phone number';
  const email = document.createElement('span');
  email.textContent = patient.email || 'No email';
  contact.append(phone, email);

  const history = document.createElement('span');
  history.className = 'patient-history';
  history.textContent = `${patient.appointment_count} ${Number(patient.appointment_count) === 1 ? 'appointment' : 'appointments'} · ${patient.allergies ? 'medical history recorded' : 'no history recorded'}`;
  const actions = document.createElement('div');
  actions.className = 'patient-actions';
  const edit = makeButton('Edit patient record', '✎');
  edit.addEventListener('click', () => openPatientEditor(patient));
  actions.append(edit);
  if (patient.archived_at) {
    const restore = makeButton('Restore patient record', '↶');
    restore.addEventListener('click', () => updatePatientArchive(patient, false));
    actions.append(restore);
  } else {
    const archive = makeButton('Archive patient record', '▣');
    archive.addEventListener('click', () => openPatientArchive(patient));
    actions.append(archive);
  }
  if (Number(patient.appointment_count) === 0) {
    const remove = makeButton('Delete patient record permanently', '×', 'icon-button task-action delete-action');
    remove.addEventListener('click', () => openPatientDelete(patient));
    actions.append(remove);
  }
  card.append(identity, contact, history, actions);
  return card;
}

function renderPatients() {
  const term = state.patientSearch.toLocaleLowerCase();
  const patients = state.patients.filter((patient) =>
    (state.patientFilter === 'archived' ? Boolean(patient.archived_at) : !patient.archived_at) &&
    `${patient.full_name} ${patient.phone} ${patient.email}`.toLocaleLowerCase().includes(term));
  elements.patientList.replaceChildren(...patients.map(createPatientCard));
  elements.patientEmpty.hidden = patients.length !== 0;
  elements.patientEmptyTitle.textContent = state.patientFilter === 'archived'
    ? 'No archived patient records'
    : state.patientSearch ? 'No matching patient records' : 'No patient records yet';
  elements.patientEmptyCopy.textContent = state.patientFilter === 'archived'
    ? 'Archived records will appear here. You can restore them at any time.'
    : state.patientSearch ? 'Try another name, phone number, or email.' : 'Add a patient record before booking their first appointment.';
  document.querySelectorAll('[data-patient-filter]').forEach((button) => {
    button.classList.toggle('active', button.dataset.patientFilter === state.patientFilter);
  });
  updateDashboard();
}

function setPage(page) {
  state.page = page;
  document.querySelectorAll('.nav-item').forEach((button) => {
    button.classList.toggle('active', button.dataset.page === page);
  });
  document.querySelector('#dashboard-view').hidden = page !== 'dashboard';
  document.querySelector('#appointments-view').hidden = page !== 'appointments';
  document.querySelector('#patients-view').hidden = page !== 'patients';
  if (page === 'appointments') renderAppointments();
  if (page === 'patients') renderPatients();
}

async function refreshDashboard() {
  state.dashboard = await api('dashboard');
  updateDashboard();
}

async function refreshAfterSave(refreshers) {
  const results = await Promise.allSettled(refreshers);
  const failures = results.filter((result) => result.status === 'rejected');
  if (failures.length) {
    failures.forEach((result) => console.error('A saved change could not be fully refreshed.', result.reason));
    showToast('The change was saved, but some details did not refresh. Reload the page to update them.');
  }
}

async function loadWorkspace() {
  const [patientsPayload, appointmentsPayload, dashboardPayload] = await Promise.all([
    api('patients'), api('appointments'), api('dashboard'),
  ]);
  state.patients = patientsPayload.patients;
  state.appointments = appointmentsPayload.appointments;
  state.dashboard = dashboardPayload;
  refreshPatientOptions();
  renderAppointments();
  renderPatients();
  elements.loadingState.hidden = true;
}

function setAuthMode(mode) {
  const registering = mode === 'register';
  elements.authTitle.textContent = registering ? 'A fresh start.' : 'Welcome back.';
  elements.authEyebrow.textContent = registering ? 'SET UP YOUR CLINIC ACCESS' : 'CLINIC STAFF ACCESS';
  elements.authIntro.textContent = registering
    ? 'Create a staff account for your MediSched workspace.'
    : 'Sign in to manage your clinic schedule.';
  elements.nameField.hidden = !registering;
  document.querySelector('#auth-name').required = registering;
  document.querySelector('#auth-password').setAttribute('autocomplete', registering ? 'new-password' : 'current-password');
  elements.authSubmit.firstChild.textContent = registering ? 'Create staff account ' : 'Sign in ';
  elements.authSwitchCopy.textContent = registering ? 'Already have a staff account?' : 'New clinic workspace?';
  elements.authSwitch.textContent = registering ? 'Sign in' : 'Create staff account';
  elements.authError.textContent = '';
  elements.authForm.dataset.mode = mode;
}

function openAuth() {
  setAuthMode('login');
  if (!elements.authDialog.open) elements.authDialog.showModal();
  window.setTimeout(() => document.querySelector('#auth-email').focus(), 50);
}

function openPatientEditor(patient = null) {
  editingPatientId = patient?.id ?? null;
  elements.patientDialogTitle.textContent = patient ? 'Edit patient record' : 'Add a patient';
  elements.patientSubmit.textContent = patient ? 'Save changes' : 'Save patient';
  elements.patientError.textContent = '';
  elements.patientForm.reset();
  if (patient) {
    elements.patientName.value = patient.full_name;
    elements.patientDob.value = patient.date_of_birth || '';
    elements.patientPhone.value = patient.phone;
    elements.patientEmail.value = patient.email;
    elements.patientAddress.value = patient.address;
    elements.patientAllergies.value = patient.allergies;
    elements.patientMedicalNotes.value = patient.medical_notes;
  }
  elements.patientDialog.showModal();
  elements.patientName.focus();
}

function openPatientDelete(patient) {
  deletingPatientId = patient.id;
  elements.deletePatientLabel.textContent = patient.full_name;
  elements.deletePatientError.textContent = '';
  elements.deletePatientConfirm.disabled = false;
  elements.deletePatientDialog.showModal();
}

function openPatientArchive(patient) {
  archivingPatientId = patient.id;
  elements.archivePatientLabel.textContent = patient.full_name;
  elements.archivePatientError.textContent = '';
  elements.archivePatientConfirm.disabled = false;
  elements.archivePatientDialog.showModal();
}

function openAppointmentEditor(appointment) {
  editingAppointmentId = appointment.id;
  elements.editAppointmentForm.reset();
  elements.editAppointmentPatient.value = String(appointment.patient_id);
  elements.editAppointmentPatientName.textContent = appointment.patient_name;
  elements.editAppointmentPatientPhone.textContent = appointment.patient_phone || 'Patient record linked to this visit';
  const patient = state.patients.find((item) => String(item.id) === String(appointment.patient_id));
  elements.editAppointmentPatientArchived.hidden = !patient?.archived_at;
  elements.editAppointmentReason.value = appointment.reason;
  elements.editAppointmentDate.value = appointmentLocalValue(appointment.scheduled_at);
  elements.editAppointmentStatus.value = appointment.status;
  elements.editAppointmentNotes.value = appointment.notes;
  elements.editAppointmentError.textContent = '';
  elements.editAppointmentSubmit.disabled = false;
  elements.editAppointmentDialog.showModal();
  elements.editAppointmentReason.focus();
}

function openAppointmentDelete(appointment) {
  deletingAppointmentId = appointment.id;
  elements.deleteAppointmentLabel.textContent = `${appointment.patient_name} · ${appointment.reason}`;
  elements.deleteError.textContent = '';
  elements.deleteConfirm.disabled = false;
  elements.deleteDialog.showModal();
}

function patientFormData() {
  return {
    full_name: elements.patientName.value,
    date_of_birth: elements.patientDob.value,
    phone: elements.patientPhone.value,
    email: elements.patientEmail.value,
    address: elements.patientAddress.value,
    allergies: elements.patientAllergies.value,
    medical_notes: elements.patientMedicalNotes.value,
  };
}

elements.authSwitch.addEventListener('click', () => {
  setAuthMode(elements.authForm.dataset.mode === 'register' ? 'login' : 'register');
});

elements.authForm.addEventListener('submit', async (event) => {
  event.preventDefault();
  elements.authError.textContent = '';
  const form = new FormData(elements.authForm);
  const registering = elements.authForm.dataset.mode === 'register';
  const body = { email: form.get('email'), password: form.get('password') };
  if (registering) body.name = form.get('name');
  elements.authSubmit.disabled = true;
  try {
    const payload = await api(registering ? 'register' : 'login', { method: 'POST', body });
    state.user = payload.user;
    state.csrf = payload.csrf;
    elements.authDialog.close();
    elements.authForm.reset();
    updateHeader();
    await loadWorkspace();
  } catch (error) {
    elements.authError.textContent = error.message;
  } finally {
    elements.authSubmit.disabled = false;
  }
});

elements.appointmentForm.addEventListener('submit', async (event) => {
  event.preventDefault();
  if (!state.patients.some((patient) => !patient.archived_at)) {
    showToast(state.patients.length
      ? 'Restore an archived patient or add a new patient before booking.'
      : 'Add a patient record before booking an appointment.');
    if (state.patients.length === 0) openPatientEditor();
    else {
      state.patientFilter = 'active';
      setPage('patients');
    }
    return;
  }
  const submit = elements.appointmentForm.querySelector('button[type="submit"]');
  submit.disabled = true;
  try {
    const payload = await api('create-appointment', {
      method: 'POST',
      body: {
        patient_id: elements.appointmentPatient.value,
        reason: elements.appointmentReason.value,
        notes: elements.appointmentNotes.value,
        scheduled_at: elements.appointmentDate.value,
      },
    });
    state.appointments.unshift(payload.appointment);
    elements.appointmentForm.reset();
    refreshPatientOptions();
    elements.appointmentDate.value = defaultAppointmentTime();
    renderAppointments();
    showToast('Appointment added to the clinic schedule.');
    await refreshAfterSave([refreshDashboard()]);
  } catch (error) {
    showToast(error.message);
  } finally {
    submit.disabled = false;
  }
});

async function savePatient(event) {
  event.preventDefault();
  elements.patientError.textContent = '';
  elements.patientSubmit.disabled = true;
  try {
    const action = editingPatientId === null ? 'create-patient' : 'update-patient';
    const body = patientFormData();
    if (editingPatientId !== null) body.id = editingPatientId;
    const payload = await api(action, { method: 'POST', body });
    if (editingPatientId === null) state.patients.push({ ...payload.patient, appointment_count: 0 });
    else state.patients = state.patients.map((patient) => patient.id === payload.patient.id
      ? { ...patient, ...payload.patient }
      : patient);
    elements.patientDialog.close();
    refreshPatientOptions();
    renderPatients();
    showToast(editingPatientId === null ? 'Patient record created.' : 'Patient record updated.');
    await refreshAfterSave([refreshDashboard()]);
  } catch (error) {
    elements.patientError.textContent = error.message;
  } finally {
    elements.patientSubmit.disabled = false;
  }
}
elements.patientForm.addEventListener('submit', savePatient);
document.querySelector('#patient-cancel').addEventListener('click', () => elements.patientDialog.close());
elements.patientDialog.addEventListener('close', () => {
  editingPatientId = null;
  elements.patientForm.reset();
  elements.patientError.textContent = '';
});

async function updateAppointment(id, changes, errorElement = null) {
  try {
    const payload = await api('update-appointment', { method: 'POST', body: { id, ...changes } });
    state.appointments = state.appointments.map((appointment) => appointment.id === payload.appointment.id
      ? payload.appointment
      : appointment);
    renderAppointments();
    showToast(changes.status === 'completed' ? 'Appointment marked complete.' : 'Appointment updated.');
    if (changes.status) await refreshAfterSave([refreshDashboard()]);
    return true;
  } catch (error) {
    if (errorElement) errorElement.textContent = error.message;
    else showToast(error.message);
    return false;
  }
}

elements.editAppointmentForm.addEventListener('submit', async (event) => {
  event.preventDefault();
  if (editingAppointmentId === null) return;
  elements.editAppointmentError.textContent = '';
  elements.editAppointmentSubmit.disabled = true;
  const saved = await updateAppointment(editingAppointmentId, {
    patient_id: elements.editAppointmentPatient.value,
    reason: elements.editAppointmentReason.value,
    scheduled_at: elements.editAppointmentDate.value,
    status: elements.editAppointmentStatus.value,
    notes: elements.editAppointmentNotes.value,
  }, elements.editAppointmentError);
  elements.editAppointmentSubmit.disabled = false;
  if (saved) {
    elements.editAppointmentDialog.close();
    editingAppointmentId = null;
  }
});
document.querySelector('#appointment-edit-cancel').addEventListener('click', () => elements.editAppointmentDialog.close());
elements.editAppointmentDialog.addEventListener('close', () => {
  editingAppointmentId = null;
  elements.editAppointmentForm.reset();
  elements.editAppointmentError.textContent = '';
});

async function deleteAppointment() {
  if (deletingAppointmentId === null) return;
  elements.deleteConfirm.disabled = true;
  elements.deleteError.textContent = '';
  try {
    await api('delete-appointment', { method: 'POST', body: { id: deletingAppointmentId } });
    state.appointments = state.appointments.filter((appointment) => appointment.id !== deletingAppointmentId);
    elements.deleteDialog.close();
    renderAppointments();
    showToast('Appointment removed.');
    await refreshAfterSave([refreshDashboard()]);
  } catch (error) {
    elements.deleteError.textContent = error.message;
  } finally {
    elements.deleteConfirm.disabled = false;
  }
}
document.querySelector('#delete-cancel').addEventListener('click', () => elements.deleteDialog.close());
elements.deleteConfirm.addEventListener('click', deleteAppointment);
elements.deleteDialog.addEventListener('close', () => {
  deletingAppointmentId = null;
  elements.deleteAppointmentLabel.textContent = '';
  elements.deleteError.textContent = '';
});

async function deletePatient() {
  if (deletingPatientId === null) return;
  elements.deletePatientConfirm.disabled = true;
  elements.deletePatientError.textContent = '';
  try {
    await api('delete-patient', { method: 'POST', body: { id: deletingPatientId } });
    state.patients = state.patients.filter((patient) => patient.id !== deletingPatientId);
    elements.deletePatientDialog.close();
    refreshPatientOptions();
    renderPatients();
    showToast('Patient record permanently deleted.');
    await refreshAfterSave([refreshDashboard()]);
  } catch (error) {
    elements.deletePatientError.textContent = error.message;
  } finally {
    elements.deletePatientConfirm.disabled = false;
  }
}
document.querySelector('#delete-patient-cancel').addEventListener('click', () => elements.deletePatientDialog.close());
elements.deletePatientConfirm.addEventListener('click', deletePatient);
elements.deletePatientDialog.addEventListener('close', () => {
  deletingPatientId = null;
  elements.deletePatientLabel.textContent = '';
  elements.deletePatientError.textContent = '';
});

async function updatePatientArchive(patient, archived, errorElement = null) {
  try {
    const payload = await api(archived ? 'archive-patient' : 'restore-patient', {
      method: 'POST',
      body: { id: patient.id },
    });
    state.patients = state.patients.map((item) => item.id === patient.id
      ? { ...item, archived_at: payload.archived_at }
      : item);
    refreshPatientOptions();
    renderPatients();
    showToast(archived ? 'Patient record archived. Appointment history was kept.' : 'Patient record restored.');
    await refreshAfterSave([refreshDashboard()]);
    return true;
  } catch (error) {
    if (errorElement) errorElement.textContent = error.message;
    else showToast(error.message);
    return false;
  }
}

elements.archivePatientConfirm.addEventListener('click', async () => {
  if (archivingPatientId === null) return;
  const patient = state.patients.find((item) => item.id === archivingPatientId);
  if (!patient) return;
  elements.archivePatientConfirm.disabled = true;
  elements.archivePatientError.textContent = '';
  try {
    const saved = await updatePatientArchive(patient, true, elements.archivePatientError);
    if (saved) elements.archivePatientDialog.close();
  } catch (error) {
    elements.archivePatientError.textContent = error.message;
  } finally {
    elements.archivePatientConfirm.disabled = false;
  }
});
document.querySelector('#archive-patient-cancel').addEventListener('click', () => elements.archivePatientDialog.close());
elements.archivePatientDialog.addEventListener('close', () => {
  archivingPatientId = null;
  elements.archivePatientLabel.textContent = '';
  elements.archivePatientError.textContent = '';
});

document.querySelectorAll('.nav-item').forEach((button) => button.addEventListener('click', () => setPage(button.dataset.page)));
document.querySelectorAll('[data-open-page="appointments"]').forEach((button) => button.addEventListener('click', () => setPage('appointments')));
document.querySelectorAll('.filter-tab[data-filter]').forEach((button) => button.addEventListener('click', () => {
  state.filter = button.dataset.filter;
  renderAppointments();
}));
document.querySelector('#appointment-search').addEventListener('input', (event) => {
  state.appointmentSearch = event.target.value.trim();
  renderAppointments();
});
document.querySelector('#patient-search').addEventListener('input', (event) => {
  state.patientSearch = event.target.value.trim();
  renderPatients();
});
document.querySelectorAll('[data-patient-filter]').forEach((button) => button.addEventListener('click', () => {
  state.patientFilter = button.dataset.patientFilter;
  renderPatients();
}));
document.querySelector('#add-patient-button').addEventListener('click', () => openPatientEditor());
function openAppointmentBooking() {
  setPage('appointments');
  document.querySelector('#booking-card').scrollIntoView({ behavior: 'smooth', block: 'start' });
  elements.appointmentPatient.focus({ preventScroll: true });
}
document.querySelector('#dashboard-book-button').addEventListener('click', openAppointmentBooking);
document.querySelector('#appointments-book-button').addEventListener('click', openAppointmentBooking);

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
    state.patients = [];
    state.appointments = [];
    state.csrf = payload.csrf;
    elements.logoutDialog.close();
    openAuth();
  } catch (error) {
    elements.logoutError.textContent = error.message;
  } finally {
    elements.logoutConfirm.disabled = false;
  }
});

document.addEventListener('keydown', (event) => {
  if (event.key.toLowerCase() !== 'n' || event.ctrlKey || event.metaKey || event.altKey) return;
  const target = event.target;
  const typing = target instanceof HTMLElement &&
    (target.isContentEditable || ['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName));
  if (typing || document.querySelector('dialog[open]')) return;
  event.preventDefault();
  if (state.page === 'patients') openPatientEditor();
  else openAppointmentBooking();
});

elements.authDialog.addEventListener('cancel', (event) => {
  if (!state.user) event.preventDefault();
});

function defaultAppointmentTime() {
  const date = new Date();
  date.setMinutes(0, 0, 0);
  date.setHours(date.getHours() + 1);
  const local = new Date(date.getTime() - date.getTimezoneOffset() * 60000);
  return local.toISOString().slice(0, 16);
}

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

elements.appointmentDate.value = defaultAppointmentTime();
updateHeader();
initialize();
