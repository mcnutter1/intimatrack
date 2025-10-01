(function(){
  const participantList = document.getElementById('participant-list');
  const addParticipantBtn = document.getElementById('add-participant');
  const participantTemplate = document.getElementById('participant-template');
  const roundTemplate = document.getElementById('round-template');
  const summaryBody = document.getElementById('encounter-summary-body');

  function updatePartnerClimaxVisibility(root=document) {
    root.querySelectorAll('.partner-climax-select').forEach(select => {
      const container = select.closest('.round-row').querySelector('.partner-climax-location');
      if (!container) return;
      if (select.value === 'yes') {
        container.style.display = '';
      } else {
        container.style.display = 'none';
        const locationSelect = container.querySelector('select');
        if (locationSelect) locationSelect.value = '';
      }
    });
  }

  function renderSummary() {
    if (!summaryBody) return;
    const participantBlocks = participantList ? participantList.querySelectorAll('.participant-block') : [];
    if (!participantBlocks.length) {
      summaryBody.innerHTML = '<div class="text-muted small">Add participants and rounds to see the summary.</div>';
      return;
    }
    const pieces = [];
    participantBlocks.forEach(block => {
      const partnerSelect = block.querySelector('.participant-partner-select');
      const partnerName = partnerSelect && partnerSelect.value ? partnerSelect.options[partnerSelect.selectedIndex].text : 'Unassigned partner';
      let roundsHtml = '';
      block.querySelectorAll('.round-row').forEach(round => {
        const role = round.querySelector('select[name$="[role]"]');
        const scenario = round.querySelector('select[name$="[scenario]"]');
        const positions = Array.from(round.querySelectorAll('select[name$="[positions][]"] option:checked')).map(opt => opt.text).join(', ');
        const participantClimax = round.querySelector('select[name$="[participant_climax]"]');
        const partnerClimax = round.querySelector('select.partner-climax-select');
        const partnerClimaxLocation = round.querySelector('.partner-climax-location select');
        const durationInput = round.querySelector('input[name$="[duration_minutes]"]');
        const satisfactionInput = round.querySelector('input[name$="[satisfaction_rating]"]');
        const cleanupMethod = round.querySelector('select[name$="[cleanup_method]"]');
        const cleanupPartnerSelect = round.querySelector('select[name$="[cleanup_partner_id]"]');
        const details = [];
        if (role) details.push(role.options[role.selectedIndex]?.text || 'Role');
        if (scenario) details.push(scenario.options[scenario.selectedIndex]?.text || 'Scenario');
        if (positions) details.push('Positions: ' + positions);
        if (participantClimax && participantClimax.value) details.push('You: ' + (participantClimax.value === 'yes' ? 'climaxed' : 'no climax'));
        if (partnerClimax && partnerClimax.value) {
          let text = 'Partner: ' + (partnerClimax.value === 'yes' ? 'climaxed' : 'no climax');
          if (partnerClimax.value === 'yes' && partnerClimaxLocation && partnerClimaxLocation.value) {
            text += ' (' + partnerClimaxLocation.options[partnerClimaxLocation.selectedIndex]?.text + ')';
          }
          details.push(text);
        }
        if (durationInput && durationInput.value) details.push('Duration: ' + durationInput.value + ' min');
        if (satisfactionInput && satisfactionInput.value) details.push('Satisfaction: ' + satisfactionInput.value + '/10');
        if (cleanupMethod && (cleanupMethod.value || (cleanupPartnerSelect && cleanupPartnerSelect.value))) {
          let cleanupText = 'Cleanup: ' + (cleanupMethod.options[cleanupMethod.selectedIndex]?.text || 'Not recorded');
          if (cleanupPartnerSelect && cleanupPartnerSelect.value) cleanupText += ' by ' + cleanupPartnerSelect.options[cleanupPartnerSelect.selectedIndex]?.text;
          details.push(cleanupText);
        }
        roundsHtml += '<li>' + details.join(' • ') + '</li>';
      });
      if (!roundsHtml) roundsHtml = '<li class="text-muted">Add at least one round.</li>';
      pieces.push('<div class="mb-2"><strong>' + partnerName + '</strong><ul class="small mb-1">' + roundsHtml + '</ul></div>');
    });
    summaryBody.innerHTML = pieces.join('');
  }

  function bindParticipant(block) {
    if (!block) return;
    const addRoundBtn = block.querySelector('.add-round');
    if (addRoundBtn) {
      addRoundBtn.addEventListener('click', () => {
        const pid = block.dataset.participantIndex;
        const nextIndex = Number(block.dataset.nextRoundIndex || '0');
        const templateHtml = roundTemplate.innerHTML.replaceAll('__PID__', pid).replaceAll('__RID__', nextIndex.toString());
        const fragment = document.createElement('div');
        fragment.innerHTML = templateHtml.trim();
        const roundElement = fragment.firstElementChild;
        block.querySelector('.rounds-list').appendChild(roundElement);
        block.dataset.nextRoundIndex = (nextIndex + 1).toString();
        updatePartnerClimaxVisibility(roundElement);
        renderSummary();
      });
    }
    block.addEventListener('click', event => {
      if (event.target.classList.contains('round-remove')) {
        const rounds = block.querySelectorAll('.round-row');
        if (rounds.length <= 1) return;
        const row = event.target.closest('.round-row');
        if (row) {
          row.remove();
          renderSummary();
        }
      }
    });
  }

  if (participantList) {
    participantList.querySelectorAll('.participant-block').forEach(bindParticipant);
    participantList.addEventListener('click', event => {
      if (event.target.classList.contains('participant-remove')) {
        const block = event.target.closest('.participant-block');
        if (!block) return;
        block.remove();
        renderSummary();
      }
    });
    participantList.addEventListener('change', () => {
      updatePartnerClimaxVisibility(participantList);
      renderSummary();
    });
  }

  if (addParticipantBtn && participantTemplate && participantList) {
    addParticipantBtn.addEventListener('click', () => {
      const nextIndex = Number(participantList.dataset.nextIndex || participantList.children.length);
      participantList.dataset.nextIndex = (nextIndex + 1).toString();
      const templateHtml = participantTemplate.innerHTML.replaceAll('__PID__', nextIndex.toString());
      const wrapper = document.createElement('div');
      wrapper.innerHTML = templateHtml.trim();
      const participantBlock = wrapper.firstElementChild;
      participantList.appendChild(participantBlock);
      bindParticipant(participantBlock);
      updatePartnerClimaxVisibility(participantBlock);
      renderSummary();
    });
  }

  renderSummary();

  const savedLocationDataEl = document.getElementById('saved-location-data');
  let savedLocations = [];
  if (savedLocationDataEl) {
    try {
      savedLocations = JSON.parse(savedLocationDataEl.textContent || '[]');
    } catch (err) {
      savedLocations = [];
    }
  }

  const locationWrapper = document.querySelector('.location-search-wrapper');
  const locationLabelInput = document.getElementById('location-label');
  const locationSearchResults = document.getElementById('location-search-results');
  const activeLocationPreview = document.getElementById('active-location-preview');
  const activeLocationLabelEl = document.getElementById('active-location-label');
  const changeLocationBtn = document.getElementById('change-location');
  const latitudeInput = document.querySelector('input[name="latitude"]');
  const longitudeInput = document.querySelector('input[name="longitude"]');
  const saveLocationFlagInput = document.getElementById('save-location-flag');
  const friendlyHiddenInput = document.getElementById('save-location-friendly');
  const mapContainer = document.getElementById('encounter-map');

  const saveLocationModalEl = document.getElementById('saveLocationModal');
  const modalDisplay = document.getElementById('modal-location-display');
  const modalFriendlyInput = document.getElementById('modal-friendly-label');
  const modalSaveCheckbox = document.getElementById('modal-save-checkbox');
  const modalSaveBtn = document.getElementById('modal-save-confirm');
  const modalUseBtn = document.getElementById('modal-use-once');
  let saveLocationModal = null;
  if (saveLocationModalEl && typeof bootstrap !== 'undefined') {
    saveLocationModal = new bootstrap.Modal(saveLocationModalEl);
  }

  let map = null;
  let marker = null;
  let geocodeAbortController = null;
  let searchDebounceTimer = null;
  let pendingLocation = null;
  let currentQuery = '';

  const parseCoord = value => {
    const num = parseFloat(value);
    return Number.isFinite(num) ? num : null;
  };

  const setCoordinateInputs = (lat, lng) => {
    if (latitudeInput) latitudeInput.value = lat !== undefined && lat !== null ? Number(lat).toFixed(6) : '';
    if (longitudeInput) longitudeInput.value = lng !== undefined && lng !== null ? Number(lng).toFixed(6) : '';
  };

  const updateMarker = (lat, lng) => {
    if (!map) return;
    if (lat === null || lng === null) {
      if (marker) {
        map.removeLayer(marker);
        marker = null;
      }
      return;
    }
    if (!marker) {
      marker = L.marker([lat, lng]).addTo(map);
    } else {
      marker.setLatLng([lat, lng]);
    }
    map.setView([lat, lng], Math.max(map.getZoom(), 13));
  };

  const initMap = () => {
    if (!mapContainer || typeof L === 'undefined') return;
    const initialLat = parseCoord(mapContainer.dataset.lat);
    const initialLng = parseCoord(mapContainer.dataset.lng);
    const hasInitial = initialLat !== null && initialLng !== null;
    map = L.map(mapContainer, {zoomControl: true});
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);
    map.setView(hasInitial ? [initialLat, initialLng] : [37.7749, -122.4194], hasInitial ? 13 : 3);
    if (hasInitial) marker = L.marker([initialLat, initialLng]).addTo(map);

    map.on('click', event => {
      const {lat, lng} = event.latlng;
      setCoordinateInputs(lat, lng);
      updateMarker(lat, lng);
      setSaveState(0, '');
      showActiveLocationPreview({label: locationLabelInput?.value || 'Pinned location', isSaved: false});
      hideSearchResults();
    });
  };

  const hideSearchResults = () => {
    if (locationSearchResults) {
      locationSearchResults.innerHTML = '';
      locationSearchResults.hidden = true;
    }
  };

  const setSaveState = (flag, friendlyLabel) => {
    if (saveLocationFlagInput) saveLocationFlagInput.value = flag ? '1' : '0';
    if (friendlyHiddenInput) friendlyHiddenInput.value = friendlyLabel ? friendlyLabel : '';
  };

  const showActiveLocationPreview = ({label, isSaved}) => {
    if (!locationLabelInput || !activeLocationPreview || !activeLocationLabelEl) return;
    locationLabelInput.classList.add('d-none');
    activeLocationPreview.classList.remove('d-none');
    activeLocationPreview.dataset.saved = isSaved ? '1' : '0';
    activeLocationLabelEl.textContent = label;
  };

  const resetLocationInput = () => {
    if (!locationLabelInput || !activeLocationPreview) return;
    activeLocationPreview.classList.add('d-none');
    locationLabelInput.classList.remove('d-none');
    locationLabelInput.focus();
    setSaveState(0, '');
  };

  const findSavedLocation = label => {
    if (!label) return null;
    const needle = label.trim().toLowerCase();
    return savedLocations.find(loc => (loc.label || '').toLowerCase() === needle) || null;
  };

  const filterSavedMatches = query => {
    if (!query) return [];
    const needle = query.trim().toLowerCase();
    return savedLocations
      .filter(loc => loc.label && loc.label.toLowerCase().includes(needle))
      .slice(0, 6)
      .map(loc => ({
        type: 'saved',
        display: loc.label,
        label: loc.label,
        lat: loc.latitude !== null ? parseFloat(loc.latitude) : null,
        lng: loc.longitude !== null ? parseFloat(loc.longitude) : null
      }));
  };

  const renderSearchResults = items => {
    if (!locationSearchResults) return;
    if (!items.length) {
      hideSearchResults();
      return;
    }
    const fragment = document.createDocumentFragment();
    items.forEach(item => {
      const button = document.createElement('button');
      button.type = 'button';
      button.className = 'location-search-result';
      button.textContent = item.display;
      button.dataset.type = item.type;
      button.dataset.label = item.label;
      if (item.lat !== null) button.dataset.lat = item.lat;
      if (item.lng !== null) button.dataset.lng = item.lng;
      fragment.appendChild(button);
    });
    locationSearchResults.innerHTML = '';
    locationSearchResults.appendChild(fragment);
    locationSearchResults.hidden = false;
  };

  const updateSearchResults = (query, remoteResults) => {
    if (!locationSearchResults) return;
    const trimmed = (query || '').trim();
    if (!trimmed) {
      hideSearchResults();
      return;
    }
    const savedMatches = filterSavedMatches(trimmed);
    let items = [...savedMatches];
    if (remoteResults && Array.isArray(remoteResults)) {
      remoteResults.forEach(res => {
        const display = res.display_name;
        const lat = parseFloat(res.lat);
        const lng = parseFloat(res.lon);
        if (!display) return;
        if (items.some(item => item.display.toLowerCase() === display.toLowerCase())) return;
        items.push({ type: 'remote', display, label: display, lat, lng });
      });
    }
    renderSearchResults(items);
  };

  const searchLocations = query => {
    const trimmed = (query || '').trim();
    if (!trimmed || trimmed.length < 3) {
      updateSearchResults(query, []);
      return;
    }
    if (geocodeAbortController) geocodeAbortController.abort();
    geocodeAbortController = new AbortController();
    const params = new URLSearchParams({ format: 'jsonv2', addressdetails: '0', limit: '6', q: trimmed });
    fetch(`https://nominatim.openstreetmap.org/search?${params.toString()}`, {
      headers: { 'Accept': 'application/json' },
      signal: geocodeAbortController.signal
    })
      .then(response => {
        if (!response.ok) throw new Error('Geocode request failed');
        return response.json();
      })
      .then(results => {
        if (trimmed.toLowerCase() === currentQuery.toLowerCase()) {
          updateSearchResults(trimmed, Array.isArray(results) ? results : []);
        }
      })
      .catch(err => {
        if (err.name === 'AbortError') return;
        console.warn('Geocode error', err);
      });
  };

  const applySavedLocation = record => {
    if (!record) return;
    const lat = record.lat !== null ? record.lat : parseCoord(latitudeInput?.value);
    const lng = record.lng !== null ? record.lng : parseCoord(longitudeInput?.value);
    if (locationLabelInput) locationLabelInput.value = record.label;
    setSaveState(0, '');
    setCoordinateInputs(lat, lng);
    updateMarker(lat, lng);
    showActiveLocationPreview({label: record.label, isSaved: true});
    hideSearchResults();
  };

  const applyRemoteLocation = ({label, lat, lng}, friendlyLabel, shouldSave) => {
    const finalLabel = friendlyLabel && friendlyLabel.trim() ? friendlyLabel.trim() : label;
    if (locationLabelInput) locationLabelInput.value = finalLabel;
    setSaveState(shouldSave ? 1 : 0, shouldSave ? finalLabel : '');
    setCoordinateInputs(lat, lng);
    updateMarker(lat, lng);
    showActiveLocationPreview({label: finalLabel, isSaved: shouldSave});
    hideSearchResults();
    if (shouldSave) {
      const existing = findSavedLocation(finalLabel);
      if (!existing) {
        savedLocations.push({ label: finalLabel, latitude: lat, longitude: lng });
      }
    }
  };

  const handleRemoteSelection = item => {
    pendingLocation = item;
    if (!saveLocationModal) {
      applyRemoteLocation(item, item.label, false);
      pendingLocation = null;
      return;
    }
    modalDisplay.textContent = item.label;
    modalFriendlyInput.value = item.label;
    modalSaveCheckbox.checked = true;
    saveLocationModal.show();
  };

  if (saveLocationModalEl) {
    saveLocationModalEl.addEventListener('hidden.bs.modal', () => {
      pendingLocation = null;
    });
  }

  if (modalSaveBtn) {
    modalSaveBtn.addEventListener('click', () => {
      if (!pendingLocation) return;
      const friendly = modalFriendlyInput.value.trim() || pendingLocation.label;
      const shouldSave = modalSaveCheckbox.checked;
      applyRemoteLocation(pendingLocation, friendly, shouldSave);
      saveLocationModal.hide();
      pendingLocation = null;
    });
  }

  if (modalUseBtn) {
    modalUseBtn.addEventListener('click', () => {
      if (!pendingLocation) return;
      const friendly = modalFriendlyInput.value.trim() || pendingLocation.label;
      applyRemoteLocation(pendingLocation, friendly, false);
      if (saveLocationModal) saveLocationModal.hide();
      pendingLocation = null;
    });
  }

  if (changeLocationBtn) {
    changeLocationBtn.addEventListener('click', () => {
      resetLocationInput();
      hideSearchResults();
    });
  }

  if (locationLabelInput) {
    locationLabelInput.addEventListener('input', () => {
      const value = locationLabelInput.value;
      currentQuery = value || '';
      updateSearchResults(value, []);
      if (searchDebounceTimer) clearTimeout(searchDebounceTimer);
      searchDebounceTimer = setTimeout(() => searchLocations(currentQuery), 350);
    });
    locationLabelInput.addEventListener('blur', () => {
      setTimeout(() => hideSearchResults(), 150);
    });
  }

  if (locationSearchResults) {
    locationSearchResults.addEventListener('mousedown', event => {
      const target = event.target.closest('.location-search-result');
      if (!target) return;
      event.preventDefault();
      const type = target.dataset.type;
      const label = target.dataset.label || '';
      const lat = parseCoord(target.dataset.lat);
      const lng = parseCoord(target.dataset.lng);
      if (!label) return;
      if (type === 'saved') {
        applySavedLocation({label, lat, lng});
      } else {
        handleRemoteSelection({label, lat, lng});
      }
    });
  }

  const coordInputs = [latitudeInput, longitudeInput];
  coordInputs.forEach(input => {
    if (!input) return;
    input.addEventListener('change', () => {
      const lat = parseCoord(latitudeInput?.value);
      const lng = parseCoord(longitudeInput?.value);
      if (lat !== null && lng !== null) updateMarker(lat, lng);
      else updateMarker(null, null);
    });
  });

  initMap();

  if (locationWrapper && locationLabelInput && activeLocationPreview && activeLocationLabelEl) {
    const initialLabel = locationWrapper.dataset.initialLabel || locationLabelInput.value;
    const lat = parseCoord(latitudeInput?.value);
    const lng = parseCoord(longitudeInput?.value);
    if (initialLabel) {
      const saved = findSavedLocation(initialLabel);
      if (saved && (lat === null || lng === null)) {
        setCoordinateInputs(saved.latitude, saved.longitude);
        updateMarker(saved.latitude, saved.longitude);
      }
      showActiveLocationPreview({label: initialLabel, isSaved: !!saved});
    }
  }
})();
