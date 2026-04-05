const saveButton = document.getElementById('btnSpeichern');
const saveStatus = document.getElementById('saveStatus');
const tabelle = document.querySelector('#zuordnungTabelle');

let changes = [];

if (tabelle) {
  tabelle.addEventListener('change', (e) => {
    const tr = e.target.closest('tr');
    if (!tr) return;
    const id = tr.querySelector('.produkt-id')?.dataset.id;
    const name = e.target.name;
    if (!id || !name) return;
    const val = (e.target.type === 'checkbox') ? (e.target.checked ? 'ja' : 'nein') : e.target.value;

    // Änderung ersetzen oder hinzufügen
    const idx = changes.findIndex(c => c.id === id && c.field === name);
    if (idx > -1) {
      changes[idx].value = val;
    } else {
      changes.push({ id, field: name, value: val });
    }
  });
} else {
  console.error('Tabelle nicht gefunden.');
}

if (saveButton && saveStatus) {
  saveButton.addEventListener('click', async () => {
    if (changes.length === 0) {
      saveStatus.innerHTML = '<span class="text-muted">Keine Änderungen.</span>';
      return;
    }
    saveStatus.innerHTML = '<span class="text-info">Speichern läuft...</span>';
    try {
      const res = await fetch('api_produkte.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ bulkUpdate: true, data: changes })
      });
      let json = {};
      try {
        json = await res.json();
      } catch (e) {
        saveStatus.innerHTML = '<span class="text-danger">Fehlerhafte Serverantwort.</span>';
        return;
      }
      if (json.success) {
        saveStatus.innerHTML = '<span class="text-success">Änderungen gespeichert.</span>';
        changes = [];
        setTimeout(() => saveStatus.innerHTML = '', 2000);
      } else {
        saveStatus.innerHTML = '<span class="text-danger">Fehler: ' + (json.error || 'Unbekannt') + '</span>';
      }
    } catch (err) {
      saveStatus.innerHTML = '<span class="text-danger">Netzwerkfehler: ' + err + '</span>';
    }
  });
} else {
  console.error('Speicher-Button oder Statusanzeige nicht gefunden!');
}

// ======= NEUES PRODUKT SPEICHERN (MODAL) =======
const produktSpeichernBtn = document.getElementById('btnProduktSpeichern');
const produktModal = document.getElementById('produktHinzufuegenModal');
const produktStatus = document.createElement('div');
produktStatus.style.marginTop = '8px';

// Füge Statusfeld in Modal ein (nur einmal!)
const produktModalBody = produktModal?.querySelector('.modal-body');
if (produktModalBody) {
  produktModalBody.appendChild(produktStatus);
}

function getModalValue(selector) {
  const el = document.getElementById(selector);
  return el ? el.value : '';
}
function getModalChecked(selector) {
  const el = document.getElementById(selector);
  return el && el.checked ? 'ja' : 'nein';
}

function clearProduktStatus(ms = 2000) {
  setTimeout(() => produktStatus.innerHTML = '', ms);
}

// Checkbox „Standard“ beim Öffnen immer unselected
if (produktModal) {
  produktModal.addEventListener('show.bs.modal', () => {
    const standardCheckbox = document.getElementById('neuesProduktStandard');
    if (standardCheckbox) standardCheckbox.checked = false;
    produktStatus.innerHTML = '';
    // Optional: Alle Felder leeren (ergänzt)
    const resetInputIds = [
      'neuesProduktName',
      'neuesProduktMengeProPackung',
      'neuesProduktRezepteinheit',
      'neuesProduktGrundeinheit',
      'neuesProduktPackungsart',
      'neuesProduktAbteilung',
      'neuesProduktArt'
    ];
    resetInputIds.forEach(id => {
      const el = document.getElementById(id);
      if (el) {
        if (el.type === 'checkbox') el.checked = false;
        else if (el.tagName === 'SELECT') el.selectedIndex = 0;
        else el.value = '';
      }
    });
  });
}

if (produktSpeichernBtn) {
  produktSpeichernBtn.addEventListener('click', async () => {
    const name = getModalValue('neuesProduktName').trim();
    const rezepteinheit = getModalValue('neuesProduktRezepteinheit');
    const grundeinheit = getModalValue('neuesProduktGrundeinheit');
    const verpackungseinheit = getModalValue('neuesProduktPackungsart');
    const supermarktmenge = parseInt(getModalValue('neuesProduktMengeProPackung'), 10) || 0;
    const abteilung = getModalValue('neuesProduktAbteilung');
    const produktart = getModalValue('neuesProduktArt');
    const standard = getModalChecked('neuesProduktStandard');

    // Frontend-Validierung
    let errorMsg = '';
    if (!name) errorMsg += 'Name fehlt.<br>';
    if (!rezepteinheit) errorMsg += 'Rezepteinheit wählen.<br>';
    if (!grundeinheit) errorMsg += 'Grundeinheit wählen.<br>';
    if (!verpackungseinheit) errorMsg += 'Packungsart wählen.<br>';
    if (!abteilung) errorMsg += 'Abteilung wählen.<br>';
    if (!produktart) errorMsg += 'Produktart wählen.<br>';
    if (!(supermarktmenge > 0)) errorMsg += 'Menge muss > 0 sein.<br>';

    if (errorMsg) {
      produktStatus.innerHTML = '<span class="text-danger">' + errorMsg + '</span>';
      clearProduktStatus(4000);
      return;
    }

    produktStatus.innerHTML = '<span class="text-info">Wird gespeichert...</span>';

    try {
      const res = await fetch('api_produkte.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          neu: true,
          name,
          rezepteinheit,
          grundeinheit,
          verpackungseinheit,
          supermarktmenge,
          abteilung,
          produktart,
          standard
        })
      });
      let json = {};
      try {
        json = await res.json();
      } catch (e) {
        produktStatus.innerHTML = '<span class="text-danger">Fehlerhafte Serverantwort.</span>';
        clearProduktStatus(4000);
        return;
      }
      if (json.success) {
        produktStatus.innerHTML = '<span class="text-success">Produkt gespeichert.</span>';
        clearProduktStatus();
        // Modal schließen
        const modalInstance = bootstrap.Modal.getInstance(produktModal);
        if (modalInstance) modalInstance.hide();
        // Seite neu laden, damit neues Produkt sichtbar ist
        setTimeout(() => location.reload(), 700);
      } else if (json.errors && Array.isArray(json.errors)) {
        let errList = json.errors.map(e => (e.msg ? e.msg : 'Fehler')).join('<br>');
        produktStatus.innerHTML = '<span class="text-danger">' + errList + '</span>';
        clearProduktStatus(5000);
      } else {
        produktStatus.innerHTML = '<span class="text-danger">Fehler: ' + (json.error || 'Unbekannt') + '</span>';
        clearProduktStatus(5000);
      }
    } catch (err) {
      produktStatus.innerHTML = '<span class="text-danger">Netzwerkfehler: ' + err + '</span>';
      clearProduktStatus(5000);
    }
  });
}
// Löschen-Funktion für Produkt-Zeile
window.loeschen = async function(btn) {
  const tr = btn.closest('tr');
  const id = tr?.querySelector('.produkt-id')?.dataset.id;
  if (!id) {
    alert('Produkt-ID nicht gefunden.');
    return;
  }
  if (!confirm('Produkt wirklich löschen?')) return;

  // Statusfeld (optional, kann angepasst werden)
  if (typeof saveStatus !== 'undefined') {
    saveStatus.innerHTML = '<span class="text-info">Löschen läuft...</span>';
  }

  try {
    const res = await fetch('api_produkte.php', {
      method: 'DELETE',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id })
    });
    const json = await res.json();
    if (json.success) {
      tr.remove();
      if (typeof saveStatus !== 'undefined') {
        saveStatus.innerHTML = '<span class="text-success">Produkt gelöscht.</span>';
        setTimeout(() => saveStatus.innerHTML = '', 2000);
      }
      // Optional: Produkt-Zähler aktualisieren
      const countElem = document.getElementById('produktCount');
      if (countElem) {
        const match = countElem.textContent.match(/\d+/);
        if (match) {
          const newCount = Math.max(0, parseInt(match[0], 10) - 1);
          countElem.textContent = 'Produkte (' + newCount + ')';
        }
      }
    } else {
      alert('Fehler beim Löschen: ' + (json.error || 'Unbekannt'));
      if (typeof saveStatus !== 'undefined') {
        saveStatus.innerHTML = '<span class="text-danger">Fehler beim Löschen.</span>';
      }
    }
  } catch (err) {
    alert('Netzwerkfehler: ' + err);
    if (typeof saveStatus !== 'undefined') {
      saveStatus.innerHTML = '<span class="text-danger">Netzwerkfehler.</span>';
    }
  }
};
// Live-Suche für Produkt-Tabelle (nur Name-Spalte)
const produktSuche = document.getElementById('produktSuche');
const produktTabelle = document.getElementById('zuordnungTabelle');

if (produktSuche && produktTabelle) {
  produktSuche.addEventListener('input', function() {
    const filter = produktSuche.value.trim().toLowerCase();
    const rows = produktTabelle.querySelectorAll('tbody tr');
    rows.forEach(row => {
      // Korrigiert: Name-Zelle ist ein <input>, kein <td>, daher .value
      const nameInput = row.querySelector('input[name="name"]');
      const nameText = nameInput ? nameInput.value.toLowerCase() : '';
      row.style.display = nameText.includes(filter) ? '' : 'none';
    });
  });
}
// ENDE