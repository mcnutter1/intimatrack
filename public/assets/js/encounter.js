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
        if (locationSelect) {
          locationSelect.value = '';
        }
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
        const cleanupMethod = round.querySelector('select[name$="[cleanup_method]"]');
        const cleanupPartnerSelect = round.querySelector('select[name$="[cleanup_partner_id]"]');
        const details = [];
        if (role) details.push(role.options[role.selectedIndex]?.text || 'Role');
        if (scenario) details.push(scenario.options[scenario.selectedIndex]?.text || 'Scenario');
        if (positions) details.push('Positions: ' + positions);
        if (participantClimax && participantClimax.value) {
          details.push('You: ' + (participantClimax.value === 'yes' ? 'climaxed' : 'no climax'));
        }
        if (partnerClimax && partnerClimax.value) {
          let text = 'Partner: ' + (partnerClimax.value === 'yes' ? 'climaxed' : 'no climax');
          if (partnerClimax.value === 'yes' && partnerClimaxLocation && partnerClimaxLocation.value) {
            text += ' (' + partnerClimaxLocation.options[partnerClimaxLocation.selectedIndex]?.text + ')';
          }
          details.push(text);
        }
        if (cleanupMethod) {
          let cleanupText = 'Cleanup: ' + (cleanupMethod.options[cleanupMethod.selectedIndex]?.text || 'Not recorded');
          if (cleanupPartnerSelect && cleanupPartnerSelect.value) {
            cleanupText += ' by ' + cleanupPartnerSelect.options[cleanupPartnerSelect.selectedIndex]?.text;
          }
          details.push(cleanupText);
        }
        roundsHtml += '<li>' + details.join(' • ') + '</li>';
      });
      if (!roundsHtml) {
        roundsHtml = '<li class="text-muted">Add at least one round.</li>';
      }
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
    block.addEventListener('click', (event) => {
      if (event.target.classList.contains('round-remove')) {
        const rounds = block.querySelectorAll('.round-row');
        if (rounds.length <= 1) {
          return;
        }
        const row = event.target.closest('.round-row');
        if (row) {
          row.remove();
          renderSummary();
        }
      }
    });
  }

  if (participantList) {
    participantList.querySelectorAll('.participant-block').forEach(block => {
      bindParticipant(block);
    });
    participantList.addEventListener('click', (event) => {
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

  updatePartnerClimaxVisibility(document);
  renderSummary();
})();
