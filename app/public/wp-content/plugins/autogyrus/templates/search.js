document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('autogyrus-search-form');
  const resultsNode = document.getElementById('autogyrus-results');
  const queryInput = document.getElementById('autogyrus-query');

  if (!form || !resultsNode || !queryInput) {
    return;
  }

  document.querySelectorAll('.search-example').forEach((button) => {
    button.addEventListener('click', () => {
      queryInput.value = button.textContent.trim();
      form.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
    });
  });

  form.addEventListener('submit', async (event) => {
    event.preventDefault();

    const query = queryInput.value.trim();
    if (!query) {
      return;
    }

    resultsNode.innerHTML = '<p>Searching inventory with AI filters...</p>';

    const response = await fetch(`${AutoGyrusData.restUrl}/search`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': AutoGyrusData.nonce,
      },
      body: JSON.stringify({ query }),
    });

    const data = await response.json();
    const redirectUrl = buildInventoryUrl(data.filters || {}, query);

    if (redirectUrl) {
      window.location.href = redirectUrl;
      return;
    }

    resultsNode.innerHTML = '<p>No matching filters were detected. Try a more specific search.</p>';
  });
});

function buildInventoryUrl(filters, query) {
  const archiveUrl = AutoGyrusData.archiveUrl || `${AutoGyrusData.siteUrl}vehicle/`;
  const url = new URL(archiveUrl, window.location.origin);

  const mapping = {
    make: 'make',
    model: 'model',
    bodyType: 'body_type',
    maxPrice: 'max_price',
    maxMileage: 'max_mileage',
    province: 'province',
    drivetrain: 'drivetrain',
    fuelType: 'fuel_type',
    city: 'city',
    postalCode: 'postal_code',
    location: 'location',
    distance: 'distance',
    year: 'year',
    reliability: 'reliability',
  };

  Object.entries(mapping).forEach(([sourceKey, targetKey]) => {
    const value = filters[sourceKey];
    if (value !== undefined && value !== null && value !== '') {
      url.searchParams.set(targetKey, String(value));
    }
  });

  if (query) {
    url.searchParams.set('ai_query', query);
  }

  return url.toString();
}
