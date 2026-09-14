document.addEventListener('DOMContentLoaded', () => {
  const vehicleId = Number(AutoDriveTheme?.vehicleId || 0);

  bindMobileDrawer();
  bindInventoryMobilePanels();
  bindInventoryResultsFocus();

  if (vehicleId) {
    fetchVehicleInsights(vehicleId);
    bindInsuranceEstimator(vehicleId);
    bindFavoriteButton(vehicleId);
    bindLeadButtons(vehicleId);
    bindReadMoreToggle();
  }
});

function iconMarkup(name) {
  const icons = {
    forecast: '<svg viewBox="0 0 24 24" role="img" focusable="false"><path d="M4 18h16M6 14l3-4 3 2 5-7" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" /><circle cx="18" cy="5" r="1.5" fill="currentColor" /></svg>',
    winter: '<svg viewBox="0 0 24 24" role="img" focusable="false"><path d="M12 2v20M4.5 6.5l15 11M4.5 17.5l15-11" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" /></svg>',
    maintenance: '<svg viewBox="0 0 24 24" role="img" focusable="false"><path d="M14.4 5.6a4.8 4.8 0 0 0-6.3 6.3L4.8 15.2a1.6 1.6 0 1 0 2.3 2.3l3.3-3.3a4.8 4.8 0 0 0 6.3-6.3l-2.1 2.1-3-3 2.1-2.1z" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" /></svg>',
    reliability: '<svg viewBox="0 0 24 24" role="img" focusable="false"><path d="M12 3l7 4v5c0 4-2.6 7.9-7 9-4.4-1.1-7-5-7-9V7l7-4z" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" /><path d="M9.2 12.4l1.8 1.8 3.8-4.2" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" /></svg>',
    insurance: '<svg viewBox="0 0 24 24" role="img" focusable="false"><path d="M12 3l7 4v5c0 4-2.6 7.9-7 9-4.4-1.1-7-5-7-9V7l7-4z" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" /><path d="M9 12.5h6" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" /></svg>',
    engine: '<svg viewBox="0 0 24 24" role="img" focusable="false"><path d="M7 8h3l2-2h3v2h2l2 2v4l-2 2h-2v2h-3l-2-2H7V8z" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" /><path d="M10 10h4v4h-4z" fill="none" stroke="currentColor" stroke-width="1.6" /></svg>',
    transmission: '<svg viewBox="0 0 24 24" role="img" focusable="false"><circle cx="6" cy="6" r="2" fill="none" stroke="currentColor" stroke-width="1.7" /><circle cx="18" cy="6" r="2" fill="none" stroke="currentColor" stroke-width="1.7" /><circle cx="12" cy="18" r="2" fill="none" stroke="currentColor" stroke-width="1.7" /><path d="M6 8v4h6v4M18 8v4h-6" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" /></svg>',
    electrical: '<svg viewBox="0 0 24 24" role="img" focusable="false"><path d="M13 2L5 13h5l-1 9 8-11h-5l1-9z" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" /></svg>',
    safety: '<svg viewBox="0 0 24 24" role="img" focusable="false"><path d="M12 3l7 4v5c0 4-2.6 7.9-7 9-4.4-1.1-7-5-7-9V7l7-4z" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" /><path d="M9.2 12.4l1.8 1.8 3.8-4.2" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" /></svg>',
    interior: '<svg viewBox="0 0 24 24" role="img" focusable="false"><path d="M6 10h12v8H6z" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" /><path d="M8 10V6h8v4M9 18v3M15 18v3" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" /></svg>',
    cooling: '<svg viewBox="0 0 24 24" role="img" focusable="false"><path d="M12 3v18M5 7l14 10M5 17L19 7" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" /></svg>',
  };

  return icons[name] || '';
}

function scoreBand(score) {
  const value = Number(score || 0);
  if (value >= 80) return 'high';
  if (value >= 50) return 'medium';
  return 'low';
}

function updateButtonLabel(button, label) {
  const labelNode = button.querySelector('.button__label');
  if (labelNode) {
    labelNode.textContent = label;
    return;
  }

  button.textContent = label;
}

function bindReadMoreToggle() {
  document.querySelectorAll('[data-readmore]').forEach((node) => {
    const content = node.querySelector('.listing-description__content');
    const toggle = node.querySelector('[data-readmore-toggle]');

    if (!content || !toggle) {
      return;
    }

    const collapsedHeight = 190;
    const isCollapsible = () => content.scrollHeight > collapsedHeight + 20;

    const sync = (expanded) => {
      node.classList.toggle('is-expanded', expanded);
      toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
      toggle.textContent = expanded ? 'Read less' : 'Read more';
      content.style.maxHeight = expanded ? `${content.scrollHeight}px` : `${collapsedHeight}px`;
    };

    if (!isCollapsible()) {
      toggle.hidden = true;
      content.style.maxHeight = 'none';
      return;
    }

    sync(false);
    toggle.addEventListener('click', () => {
      const expanded = !node.classList.contains('is-expanded');
      sync(expanded);
    });

    window.addEventListener('resize', () => {
      if (node.classList.contains('is-expanded')) {
        content.style.maxHeight = `${content.scrollHeight}px`;
      }
    }, { passive: true });
  });
}

async function fetchVehicleInsights(vehicleId) {
  const response = await fetch(`${AutoDriveTheme.restUrl}/vehicle/${vehicleId}/insights`);
  const vehicle = await response.json();

  renderFutureValue(vehicle.future_value || {});
  renderWinterScore(vehicle.winter || {});
  renderMaintenance(vehicle.maintenance || {});
  renderReliability(vehicle.reliability || {});
}

function renderFutureValue(data) {
  const node = document.getElementById('future-value');
  if (!node) return;

  const predictions = Object.entries(data.predictions || {});
  const scoreValue = Number.isFinite(Number(data.score))
    ? Math.max(0, Math.min(100, Number(data.score)))
    : scoreBand(data.rating) === 'high'
      ? 84
      : scoreBand(data.rating) === 'medium'
        ? 66
        : 42;
  const predictionValues = predictions.map(([, value]) => Number(value || 0)).filter((value) => Number.isFinite(value));
  const minimumPrediction = predictionValues.length ? Math.min(...predictionValues) : 0;
  const maximumPrediction = predictionValues.length ? Math.max(...predictionValues) : 0;
  const predictionRange = Math.max(1, maximumPrediction - minimumPrediction);
  const forecastIcon = iconMarkup('forecast');
  const currentPrice = Number(data.current_price || 0);
  const predictionMap = Object.fromEntries(predictions.map(([label, value]) => [label, Number(value || 0)]));
  const fiveYearValue = Number.isFinite(predictionMap.five_year)
    ? predictionMap.five_year
    : Number.isFinite(predictionMap['5_year'])
      ? predictionMap['5_year']
      : predictionValues.length
        ? predictionValues[predictionValues.length - 1]
        : 0;
  const fiveYearDepreciation = currentPrice > 0
    ? Math.max(0, ((currentPrice - fiveYearValue) / currentPrice) * 100)
    : 0;
  const resaleBand = fiveYearDepreciation < 30
    ? 'exceptional'
    : fiveYearDepreciation < 40
      ? 'great'
      : fiveYearDepreciation < 50
        ? 'good'
        : 'weak';
  const resaleLabel = fiveYearDepreciation < 30
    ? 'Exceptional resale value'
    : fiveYearDepreciation < 40
      ? 'Great resale value'
      : fiveYearDepreciation < 50
        ? 'Good resale value'
        : 'Average resale value';
  const ratingIcon = resaleBand === 'exceptional'
    ? '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 3l2.5 5 5.5.8-4 3.9.9 5.5L12 15.6 7.1 18.2l.9-5.5-4-3.9 5.5-.8L12 3z" fill="currentColor"/></svg>'
    : resaleBand === 'great'
      ? '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M8 21h8M12 17v4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M7 5h10l-1 6a4 4 0 0 1-4 3.2A4 4 0 0 1 8 11L7 5z" fill="currentColor"/><path d="M7 5h10l-1 6a4 4 0 0 1-4 3.2A4 4 0 0 1 8 11L7 5z" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/></svg>'
      : '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 4l2.1 4.3 4.8.7-3.5 3.4.8 4.8L12 15.9 7.8 17.2l.8-4.8L5.1 9l4.8-.7L12 4z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M12 8.2l.9 1.8 2 .3-1.5 1.5.4 2-1.8-.9-1.8.9.4-2L8.1 10.3l2-.3L12 8.2z" fill="currentColor"/></svg>';

  node.innerHTML = `
    <div class="future-value-panel">
      <div class="future-value-panel__header">
        <div class="future-value-panel__intro">
          <span class="future-value-panel__icon" aria-hidden="true">${forecastIcon}</span>
          <div>
            <p class="eyebrow">Future Price Prediction</p>
            <h4><span class="future-value-panel__rating-icon future-value-panel__rating-icon--${resaleBand}">${ratingIcon}</span><span>${data.rating || 'Pending'}</span></h4>
            <p class="future-value-panel__summary">${data.summary || ''}</p>
          </div>
        </div>
        <div class="future-value-panel__header-chip future-value-panel__header-chip--${resaleBand}">
          <span>${resaleLabel}</span>
        </div>
      </div>

      <div class="future-value-panel__forecast-grid">
        ${predictions.map(([label, value]) => {
          const numericValue = Number(value || 0);
          const depreciation = currentPrice > 0
            ? Math.max(0, ((currentPrice - numericValue) / currentPrice) * 100)
            : 0;
          return `
            <article class="future-value-panel__forecast-card">
              <span>${label.replace(/_/g, ' ')}</span>
              <strong>$${numericValue.toLocaleString()}</strong>
              <small class="future-value-panel__depreciation">-${depreciation.toFixed(1)}% depreciation</small>
            </article>
          `;
        }).join('')}
      </div>
    </div>
  `;
}

function renderWinterScore(data) {
  const node = document.getElementById('winter-score');
  if (!node) return;

  node.innerHTML = `
    <div class="panel-section-header">
      <span class="panel-section-header__icon" aria-hidden="true">${iconMarkup('winter')}</span>
      <div>
        <p class="eyebrow">Canada winter readiness</p>
        <h4>${data.score || 0}/100</h4>
      </div>
    </div>
    <div class="score-ring score-ring--${scoreBand(data.score)}" style="--score:${Number(data.score || 0)};">
      <div class="score-ring__inner">
        <strong>${data.score || 0}</strong>
      </div>
    </div>
    <p class="vehicle-intel-card__badge">${data.badge || 'Pending'}</p>
    <p class="vehicle-intel-card__summary">${data.summary || ''}</p>
  `;
}

function renderMaintenance(data) {
  const node = document.getElementById('maintenance-forecast');
  if (!node) return;

  const services = data.services || [];
  const ownership = data.ownership_cost_forecast || {};

  node.innerHTML = `
    <div class="panel-section-header">
      <span class="panel-section-header__icon" aria-hidden="true">${iconMarkup('maintenance')}</span>
      <div>
        <p class="eyebrow">Maintenance forecast</p>
        <h4>${data.risk || 'Pending'} Risk</h4>
      </div>
    </div>
    <p class="vehicle-intel-card__summary">Upcoming service planning and ownership cost outlook based on mileage and vehicle profile.</p>
    <div class="timeline-list">
      ${services.map((service) => `
        <article class="timeline-item">
          <span class="timeline-item__dot" aria-hidden="true"></span>
          <div class="timeline-item__body">
            <strong>${service.label || 'Upcoming service'}</strong>
            <span>$${Number(service.cost || 0).toLocaleString()} · Due in ${service.due_in || ''}</span>
          </div>
        </article>
      `).join('')}
    </div>
    <div class="forecast-grid forecast-grid--ownership">
      ${Object.entries(ownership).map(([label, value]) => `
        <article class="forecast-step forecast-step--compact">
          <span class="forecast-step__label">${label.replace('_', ' ')}</span>
          <strong>$${Number(value || 0).toLocaleString()}</strong>
          <small>Ownership outlook</small>
        </article>
      `).join('')}
    </div>
  `;
}

function renderReliability(data) {
  const node = document.getElementById('reliability-score');
  if (!node) return;

  const categories = Object.entries(data.categories || {});
  const categoryIcons = {
    engine: iconMarkup('engine'),
    transmission: iconMarkup('transmission'),
    electrical: iconMarkup('electrical'),
    safety: iconMarkup('safety'),
    interior: iconMarkup('interior'),
    'cooling system': iconMarkup('cooling'),
  };
  node.innerHTML = `
    <div class="panel-section-header">
      <span class="panel-section-header__icon" aria-hidden="true">${iconMarkup('reliability')}</span>
      <div>
        <p class="eyebrow">Reliability &amp; health</p>
        <h4>${data.score || 0}/100</h4>
      </div>
    </div>
    <div class="reliability-grid">
      <div class="score-meter score-meter--${scoreBand(data.score)}" style="--score:${Number(data.score || 0)};">
        <div class="score-meter__top">
          <span class="score-meter__label">Health score</span>
          <strong class="score-meter__value">${data.score || 0}</strong>
        </div>
        <div class="score-meter__track" aria-hidden="true">
          <span class="score-meter__fill"></span>
          <span class="score-meter__knob"></span>
        </div>
        <div class="score-meter__scale" aria-hidden="true">
          <span>Poor</span>
          <span>Average</span>
          <span>Great</span>
        </div>
      </div>
      <div class="status-note">
        <span class="status-note__label">${data.rating || 'Pending'}</span>
        <p>${data.summary || ''}</p>
      </div>
    </div>
    <div class="metric-rows">
      ${categories.map(([label, value]) => `
        <div class="metric-row">
          <div class="metric-row__labels">
            <span class="metric-row__label"><span class="metric-row__icon">${categoryIcons[label.toLowerCase()] || iconMarkup('reliability')}</span>${label}</span>
            <strong>${value}/100</strong>
          </div>
        </div>
      `).join('')}
    </div>
    <div class="stack-list stack-list--compact">
      ${(data.known_issues || []).map((item) => `<div class="service-item service-item--soft"><strong>Known issue</strong><span>${item}</span></div>`).join('')}
      ${(data.common_complaints || []).map((item) => `<div class="service-item service-item--soft"><strong>Complaint</strong><span>${item}</span></div>`).join('')}
      ${(data.recall_history || []).map((item) => `<div class="service-item service-item--soft"><strong>Recall</strong><span>${item}</span></div>`).join('')}
    </div>
  `;
}

function bindInsuranceEstimator(vehicleId) {
  const node = document.getElementById('insurance-estimator');
  if (!node) return;

  const renderEstimate = async () => {
    const payload = {
      province: node.dataset.province || 'AB',
      driver_age: node.dataset.driverAge || '35',
      driving_experience: node.dataset.drivingExperience || '10',
    };
    const response = await fetch(`${AutoDriveTheme.restUrl}/vehicle/${vehicleId}/insurance`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': AutoDriveTheme.nonce,
      },
      body: JSON.stringify(payload),
    });
    const data = await response.json();
    const provinces = Array.isArray(data.provinces) ? data.provinces : [];

    node.innerHTML = `
      <div class="insurance-estimator__header">
        <div class="panel-section-header">
          <span class="panel-section-header__icon" aria-hidden="true">${iconMarkup('insurance')}</span>
          <div>
            <p class="eyebrow">Insurance cost estimator</p>
            <h4>Province comparison</h4>
          </div>
        </div>
        <p>Estimated annual insurance range based on driver profile and vehicle category.</p>
      </div>
      <div class="insurance-table insurance-table--premium">
        <div class="insurance-table__head">Province</div>
        <div class="insurance-table__head">Low (CAD)</div>
        <div class="insurance-table__head">Average (CAD)</div>
        <div class="insurance-table__head">High (CAD)</div>
        ${provinces.map((item) => `
          <div class="insurance-table__cell insurance-table__cell--province${item.code === payload.province ? ' is-active' : ''}"><span class="province-pill">${item.label}</span></div>
          <div class="insurance-table__cell">$${Number(item.low || 0).toLocaleString()}</div>
          <div class="insurance-table__cell">$${Number(item.average || 0).toLocaleString()}</div>
          <div class="insurance-table__cell">$${Number(item.high || 0).toLocaleString()}</div>
        `).join('')}
      </div>
    `;
  };

  renderEstimate();
}

function bindFavoriteButton(vehicleId) {
  const button = document.getElementById('favorite-vehicle');
  if (!button) return;

  button.addEventListener('click', async () => {
    if (!AutoDriveTheme.isLoggedIn) {
      window.location.href = `${AutoDriveTheme.archiveUrl}?login=1`;
      return;
    }

    const response = await fetch(`${AutoDriveTheme.restUrl}/vehicle/${vehicleId}/favorite`, {
      method: 'POST',
      headers: { 'X-WP-Nonce': AutoDriveTheme.nonce },
    });
    const data = await response.json();
    updateButtonLabel(button, data.favorited ? `Saved (${data.count})` : 'Save Vehicle');
  });
}

function bindLeadButtons(vehicleId) {
  document.querySelectorAll('[data-lead-type]').forEach((button) => {
    button.addEventListener('click', async () => {
      const type = button.getAttribute('data-lead-type');
      const name = window.prompt('Your name');
      if (!name) return;
      const email = window.prompt('Your email');
      if (!email) return;
      const phone = window.prompt('Your phone number');

      await fetch(`${AutoDriveTheme.restUrl}/vehicle/${vehicleId}/lead`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-WP-Nonce': AutoDriveTheme.nonce,
        },
        body: JSON.stringify({
          type,
          name,
          email,
          phone,
          message: `Request submitted from vehicle detail page for ${type}.`,
        }),
      });

      updateButtonLabel(button, type === 'test_drive' ? 'Test Drive Requested' : 'Dealer Contacted');
    });
  });
}

function bindInventoryMobilePanels() {
  const buttons = document.querySelectorAll('[data-mobile-toggle]');
  const panels = document.querySelectorAll('.inventory-mobile-panel');
  const aiPanel = document.getElementById('inventory-ai-search');
  const filtersPanel = document.getElementById('inventory-filters');
  const buttonsRow = document.querySelector('.inventory-mobile-tools');
  const header = document.querySelector('.inventory-header');

  if (!buttons.length || !panels.length || !aiPanel || !filtersPanel || !buttonsRow || !header) {
    return;
  }

  const isMobile = () => window.matchMedia('(max-width: 760px)').matches;

  const placePanels = (mobile) => {
    if (mobile) {
      if (buttonsRow.nextElementSibling !== aiPanel) {
        buttonsRow.insertAdjacentElement('afterend', aiPanel);
      }
      if (aiPanel.nextElementSibling !== filtersPanel) {
        aiPanel.insertAdjacentElement('afterend', filtersPanel);
      }
      return;
    }

    if (header.lastElementChild !== aiPanel) {
      header.appendChild(aiPanel);
    }
  };

  const syncState = () => {
    const mobile = isMobile();
    placePanels(mobile);

    panels.forEach((panel) => {
      if (mobile) {
        panel.hidden = !panel.classList.contains('is-open');
      } else {
        panel.hidden = false;
        panel.classList.remove('is-open');
      }
    });

    buttons.forEach((button) => {
      const targetId = button.getAttribute('data-mobile-toggle');
      const targetPanel = targetId ? document.getElementById(targetId) : null;
      button.setAttribute('aria-expanded', targetPanel && targetPanel.classList.contains('is-open') ? 'true' : 'false');
    });
  };

  buttons.forEach((button) => {
    button.addEventListener('click', () => {
      const targetId = button.getAttribute('data-mobile-toggle');
      const targetPanel = targetId ? document.getElementById(targetId) : null;
      if (!targetPanel) return;

      const open = !targetPanel.classList.contains('is-open');
      panels.forEach((panel) => {
        panel.classList.toggle('is-open', panel === targetPanel ? open : false);
      });

      buttons.forEach((otherButton) => {
        const otherTargetId = otherButton.getAttribute('data-mobile-toggle');
        const otherTargetPanel = otherTargetId ? document.getElementById(otherTargetId) : null;
        const active = otherTargetPanel && otherTargetPanel.classList.contains('is-open');
        otherButton.classList.toggle('is-active', active);
        otherButton.setAttribute('aria-expanded', active ? 'true' : 'false');
      });

      syncState();

      if (open) {
        targetPanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    });
  });

  window.addEventListener('resize', syncState, { passive: true });
  syncState();
}

function bindInventoryResultsFocus() {
  const archivePage = document.getElementById('vehicle-archive');
  const aiPanel = document.getElementById('inventory-ai-search');
  if (!archivePage || !aiPanel) {
    return;
  }
  if (window.matchMedia('(max-width: 760px)').matches) {
    return;
  }

  const art = document.querySelector('.inventory-header__art');
  const artImage = art ? art.querySelector('img') : null;

  const scrollToDesktopArt = () => {
    const targetNode = aiPanel || artImage;
    if (!targetNode) {
      aiPanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
      return;
    }

    const targetTop = targetNode.getBoundingClientRect().bottom + window.pageYOffset + 16;
    window.scrollTo({ top: Math.max(0, targetTop), behavior: 'smooth' });
  };

  if ('scrollRestoration' in window.history) {
    window.history.scrollRestoration = 'manual';
  }

  const runScroll = () => {
    if (artImage && !artImage.complete) {
      artImage.addEventListener('load', scrollToDesktopArt, { once: true });
      return;
    }

    scrollToDesktopArt();
  };

  window.addEventListener('load', runScroll, { once: true });
  setTimeout(runScroll, 150);
}

function bindMobileDrawer() {
  const toggle = document.querySelector('.site-menu-toggle');
  const drawer = document.getElementById('site-drawer');
  const overlay = document.querySelector('.site-drawer-overlay');
  const closeButton = document.querySelector('.site-drawer__close');

  if (!toggle || !drawer || !overlay || !closeButton) {
    return;
  }

  const isMobile = () => window.matchMedia('(max-width: 1180px)').matches;

  const setOpen = (open) => {
    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    drawer.classList.toggle('is-open', open);
    drawer.setAttribute('aria-hidden', open ? 'false' : 'true');
    overlay.hidden = !open;
    document.documentElement.classList.toggle('has-drawer-open', open);
  };

  toggle.addEventListener('click', () => setOpen(!drawer.classList.contains('is-open')));
  overlay.addEventListener('click', () => setOpen(false));
  closeButton.addEventListener('click', () => setOpen(false));

  drawer.querySelectorAll('a').forEach((link) => {
    link.addEventListener('click', () => setOpen(false));
  });

  window.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      setOpen(false);
    }
  });

  window.addEventListener('resize', () => {
    if (!isMobile()) {
      setOpen(false);
    }
  }, { passive: true });
}
