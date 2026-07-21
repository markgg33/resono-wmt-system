// ============================
// AUTO LOGOUT (12 hours absolute, 9 hours idle, 20-min warning)
// ============================

// Absolute timeout = 12 * 60 * 60 = 43,200 seconds
// Idle timeout   = 9 * 60 * 60 = 32,400 seconds
// Warning        = 20 * 60 = 1,200 seconds
const ABSOLUTE_TIMEOUT = 43200; // total session time (12 hours)
const IDLE_TIMEOUT = 32400;     // idle logout (9 hours)
const WARNING_TIME = 3600;      // warning 1 hour before logout

let warningShown = false;
let sessionStart = Date.now();          // absolute session start (fixed)
let lastActivity = Date.now();          // last activity timestamp (resets on activity)
let countdownInterval;

// 🔹 Reset idle timer on any user activity
function resetSessionTimer() {
  lastActivity = Date.now();
  // If a warning is displayed, hide it and allow the user to continue
  warningShown = false;
  hideLogoutWarning();
}

// 🔹 Show non-blocking logout warning popup
function showLogoutWarning(remaining, type = 'idle') {
  if (document.getElementById('logoutWarning')) return; // avoid duplicates

  const div = document.createElement('div');
  div.id = 'logoutWarning';
  const reason = type === 'absolute' ? 'session time limit' : 'inactivity';
  div.innerHTML = `
    <div style="
      position: fixed;
      bottom: 20px;
      right: 20px;
      background: #fff3cd;
      border: 1px solid #ffeeba;
      color: #856404;
      padding: 15px 20px;
      border-radius: 10px;
      box-shadow: 0 0 10px rgba(0,0,0,0.15);
      z-index: 9999;
      font-family: system-ui, sans-serif;
    ">
      ⚠️ You will be logged out due to ${reason} in 
      <span id="logoutCountdown">${remaining}</span> second(s).<br>
      <button id="stayLoggedIn" style="
        margin-top: 8px;
        background: #198754;
        color: white;
        border: none;
        padding: 6px 12px;
        border-radius: 6px;
        cursor: pointer;
      ">Stay Logged In</button>
    </div>
  `;
  document.body.appendChild(div);

  // Extend session if user interacts with button
  document.getElementById('stayLoggedIn').addEventListener('click', () => {
    resetSessionTimer();
  });

  // Live countdown update every second
  countdownInterval = setInterval(() => {
    const countdownEl = document.getElementById('logoutCountdown');
    if (!countdownEl) return;
    const current = parseInt(countdownEl.textContent, 10);
    if (current > 1) {
      countdownEl.textContent = current - 1;
    }
  }, 1000);
}

// 🔹 Hide warning popup
function hideLogoutWarning() {
  const div = document.getElementById('logoutWarning');
  if (div) div.remove();
  clearInterval(countdownInterval);
}

// 🔹 Check session every second and decide whether to warn or logout
setInterval(() => {
  const now = Date.now();
  const elapsedAbsolute = Math.floor((now - sessionStart) / 1000);
  const elapsedIdle = Math.floor((now - lastActivity) / 1000);
  const remainingAbsolute = ABSOLUTE_TIMEOUT - elapsedAbsolute;
  const remainingIdle = IDLE_TIMEOUT - elapsedIdle;

  // Determine which timeout is sooner
  const willTriggerRemaining = Math.min(remainingAbsolute, remainingIdle);
  const triggerType = (remainingAbsolute <= remainingIdle) ? 'absolute' : 'idle';

  // Show warning if within WARNING_TIME of whichever timeout comes first
  if (willTriggerRemaining <= WARNING_TIME && willTriggerRemaining > 0 && !warningShown) {
    warningShown = true;
    showLogoutWarning(willTriggerRemaining, triggerType);
  }

  // Auto logout when either timeout hits zero
  if (remainingIdle <= 0 || remainingAbsolute <= 0) {
    hideLogoutWarning();
    window.location.href = '../backend/logout.php';
  }
}, 1000);

// 🔹 Reset idle timer when user interacts with page
['mousemove', 'keydown', 'click', 'scroll', 'touchstart'].forEach((evt) => {
  window.addEventListener(evt, resetSessionTimer, { passive: true });
});

// Expose a small keep-alive helper (optional): call to reset server-side session via fetch
function keepServerSessionAlive() {
  // Optional: uncomment and adapt the endpoint if you have a keepalive route
  // fetch('../backend/keepalive.php', { method: 'POST', credentials: 'include' }).catch(() => {});
}

// Optionally ping server periodically while user is active to keep server session alive
// setInterval(() => { keepServerSessionAlive(); }, 5 * 60 * 1000);
