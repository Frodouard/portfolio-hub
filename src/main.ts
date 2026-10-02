document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('contactForm') as HTMLFormElement | null;
  const status = document.querySelector('.form-status') as HTMLParagraphElement | null;

  if (!form || !status) {
    return;
  }

  form.addEventListener('submit', async (event: Event) => {
    event.preventDefault();

    const formData = new FormData(form);
    const payload = new URLSearchParams();

    for (const [key, value] of formData.entries()) {
      payload.append(key, String(value));
    }

    status.textContent = 'Sending your inquiry...';

    try {
      const response = await fetch('contact.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
        },
        body: payload.toString(),
      });

      const result = await response.json() as { success?: boolean; message?: string };

      if (!response.ok || result.success === false) {
        throw new Error(result.message || 'Unable to send inquiry.');
      }

      status.textContent = result.message || 'Your inquiry has been saved successfully.';
      form.reset();
    } catch (error) {
      const message = error instanceof Error ? error.message : 'Something went wrong. Please try again.';
      status.textContent = message;
    }
  });
});
