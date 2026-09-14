document.addEventListener('DOMContentLoaded', () => {
  const vinInput = document.querySelector('.acf-field[data-name="vin"] input');
  if (!vinInput || typeof AutoGyrusAdmin === 'undefined') {
    return;
  }

  vinInput.addEventListener('blur', async () => {
    const vin = vinInput.value.trim().toUpperCase();
    if (vin.length !== 17) {
      return;
    }

    const response = await fetch(`${AutoGyrusAdmin.restUrl}/vin-decode`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': AutoGyrusAdmin.nonce,
      },
      body: JSON.stringify({ vin }),
    });

    const data = await response.json();
    setAcfValue('make', data.make || '');
    setAcfValue('model', data.model || '');
    setAcfValue('year', data.year || '');
    setAcfValue('body_type', data.body_type || '');
    setAcfValue('drivetrain', data.drivetrain || '');
    setAcfValue('fuel_type', data.fuel_type || '');
    setAcfValue('transmission', data.transmission || '');
  });
});

function setAcfValue(fieldName, value) {
  const field = document.querySelector(`.acf-field[data-name="${fieldName}"] input, .acf-field[data-name="${fieldName}"] select, .acf-field[data-name="${fieldName}"] textarea`);
  if (!field) {
    return;
  }

  field.value = value;
  field.dispatchEvent(new Event('change', { bubbles: true }));
}
