// AUCA Student Portal — login page behavior

document.addEventListener('DOMContentLoaded', () => {
  // Show/hide password toggles
  document.querySelectorAll('.toggle-password').forEach((btn) => {
    btn.addEventListener('click', () => {
      const targetId = btn.getAttribute('data-target');
      const input = document.getElementById(targetId);
      if (!input) return;
      const isHidden = input.type === 'password';
      input.type = isHidden ? 'text' : 'password';
      btn.textContent = isHidden ? '🙈' : '👁';
      btn.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
    });
  });

  // Basic client-side check (server still validates everything)
  const form = document.getElementById('loginForm');
  if (form) {
    form.addEventListener('submit', (e) => {
      const identifier = form.querySelector('#identifier');
      const password = form.querySelector('#password');
      if (!identifier.value.trim() || !password.value.trim()) {
        e.preventDefault();
        alert('Please fill in both fields.');
      }
    });
  }
});