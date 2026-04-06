// rezept-modal.js

window.einheitenMap = {};
async function ladeEinheitenMap() {
  if (Object.keys(window.einheitenMap).length > 0) return window.einheitenMap;
  try {
    const res = await fetch('../produkte/einheiten.json');
    if (res.ok) {
      const data = await res.json();
      if (data.Produkteinheiten) window.einheitenMap = data.Produkteinheiten;
      else window.einheitenMap = {};
    }
  } catch (e) {
    window.einheitenMap = {};
  }
  return window.einheitenMap;
}

// --- Carousel-State ---
let _modalCurrentPage = 1;
let _modalZubereitungText = '';

function modalGotoPage(page) {
  _modalCurrentPage = page;

  const seite1 = document.getElementById('modalSeite1');
  const seite2 = document.getElementById('modalSeite2');
  const dot1 = document.getElementById('modalDot1');
  const dot2 = document.getElementById('modalDot2');
  const btnZurueck = document.getElementById('btnZurueck');
  const btnWeiter = document.getElementById('btnWeiter');
  const btnVorlesen = document.getElementById('btnVorlesen');

  // Null-Check: Abbruch wenn Elemente nicht gefunden
  if (!seite1 || !seite2) return;

  if (page === 1) {
    seite1.classList.add('active');
    seite2.classList.remove('active');
    if (dot1) dot1.classList.add('active');
    if (dot2) dot2.classList.remove('active');
    if (btnZurueck) btnZurueck.style.visibility = 'hidden';
    if (btnWeiter) btnWeiter.style.visibility = 'visible';
    if (btnVorlesen) btnVorlesen.style.visibility = 'hidden';
  } else {
    seite1.classList.remove('active');
    seite2.classList.add('active');
    if (dot1) dot1.classList.remove('active');
    if (dot2) dot2.classList.add('active');
    if (btnZurueck) btnZurueck.style.visibility = 'visible';
    if (btnWeiter) btnWeiter.style.visibility = 'hidden';
    if (btnVorlesen) btnVorlesen.style.visibility = 'visible';
  }
}

function modalToggleVorlesen() {
  const btn = document.getElementById('btnVorlesen');
  if (!btn) return;
  if (window.speechSynthesis && window.speechSynthesis.speaking) {
    window.speechSynthesis.cancel();
    btn.innerHTML = '&#128266; Vorlesen';
    btn.setAttribute('aria-label', 'Zubereitung vorlesen');
    return;
  }
  if (!_modalZubereitungText) return;
  const utterance = new SpeechSynthesisUtterance(_modalZubereitungText);
  utterance.lang = 'de-DE';
  utterance.onend = function() {
    btn.innerHTML = '&#128266; Vorlesen';
    btn.setAttribute('aria-label', 'Zubereitung vorlesen');
  };
  utterance.onerror = function() {
    btn.innerHTML = '&#128266; Vorlesen';
    btn.setAttribute('aria-label', 'Zubereitung vorlesen');
  };
  btn.innerHTML = '&#9209; Stopp';
  btn.setAttribute('aria-label', 'Vorlesen stoppen');
  window.speechSynthesis.speak(utterance);
}

async function showPreviewModal(slug, bild) {
  // Sicherstellen dass Modal-DOM vorhanden ist
  const modalEl = document.getElementById('detailsModal');
  if (!modalEl) {
    console.error('Modal-Element #detailsModal nicht gefunden!');
    alert('Fehler beim Laden der Rezeptdetails: Modal nicht gefunden.');
    return;
  }

  // Warten, bis Produktliste geladen ist
  if (!window.produktListe || Object.keys(window.produktListe).length === 0) {
    await ladeProduktListe();
  }
  // Warten, bis Einheiten geladen sind:
  if (!window.einheitenMap || Object.keys(window.einheitenMap).length === 0) {
    await ladeEinheitenMap();
  }
  try {
    const res = await fetch(`rezeptkasten/rezepte/${slug}.json?ts=${Date.now()}`);
    if (!res.ok) throw new Error("Rezeptdaten konnten nicht geladen werden!");
    const details = await res.json();

    // Bild (oben)
    let bildHtml = "";
    if (bild || details.bild) {
      bildHtml = `<img src="${bild || details.bild}" class="modal-img" alt="">`;
    } else {
      bildHtml = `<div class="modal-img-placeholder">
        <span class="material-symbols-outlined" style="font-size:48px; color:#815BE5;">restaurant</span>
      </div>`;
    }

    // Zutaten-Tabelle (IDs aufloesen)
    let zutatenTableHtml = "";
    if (Array.isArray(details.zutaten) && details.zutaten.length > 0) {
      zutatenTableHtml = `<table class="modal-details-table mb-2"><thead>
        <tr><th>Zutat</th><th style="text-align:right;">Menge</th><th>Einheit</th></tr></thead><tbody>`;
      for (const z of details.zutaten) {
        let zName = "";
        if (z.id && window.produktListe && window.produktListe[z.id]) {
          zName = window.produktListe[z.id]._name || window.produktListe[z.id].name || "";
        } else {
          zName = z.name || "Unbekannt";
        }
        zutatenTableHtml += `<tr>
          <td>${zName}</td>
          <td style="text-align:right;">${(z.rezeptmenge !== undefined ? z.rezeptmenge : "")}</td>
         <td>${
  (z.rezepteinheit && window.einheitenMap && window.einheitenMap[z.rezepteinheit])
    ? window.einheitenMap[z.rezepteinheit].label
    : (z.rezepteinheit || "")
}</td>
        </tr>`;
      }
      zutatenTableHtml += `</tbody></table>`;
    }

    // Kalorien
    let kalorienHtml = "";
    if (details.kalorien) {
      kalorienHtml = `<div class="modal-meta"><span>Kalorien: <b>${details.kalorien} kcal</b></span></div>`;
    }

    // Zubereitung (Markdown)
    let zubereitungHtml = "";
    _modalZubereitungText = '';
    if (details.zubereitung) {
      _modalZubereitungText = details.zubereitung;
      const parsedMarkdown = (typeof marked !== 'undefined' && marked.parse)
        ? marked.parse(details.zubereitung)
        : details.zubereitung.replace(/\n/g, '<br>');
      zubereitungHtml = `<div class="modal-section-title">Zubereitung</div>
        <div class="modal-zubereitung">${parsedMarkdown}</div>
        ${kalorienHtml}`;
    }

    // Titel
    const titelEl = document.getElementById('modalRezeptTitel');
    if (titelEl) {
      titelEl.innerHTML = details.name +
        (details.vegetarisch === 'ja' ? ' <span class="modal-vegan-icon material-symbols-outlined" title="Vegetarisch">eco</span>' : '');
    }

    // Seite 1 befuellen
    const seite1Bild = document.getElementById('modalSeite1Bild');
    if (seite1Bild) seite1Bild.innerHTML = bildHtml;

    const seite1Zutaten = document.getElementById('modalSeite1Zutaten');
    if (seite1Zutaten) seite1Zutaten.innerHTML = zutatenTableHtml;

    // Seite 2 befuellen
    const seite2Zubereitung = document.getElementById('modalSeite2Zubereitung');
    if (seite2Zubereitung) seite2Zubereitung.innerHTML = zubereitungHtml;

    // Immer auf Seite 1 zuruecksetzen
    modalGotoPage(1);

    // Speech stoppen wenn Modal geschlossen wird (once: true verhindert Listener-Akkumulation)
    modalEl.addEventListener('hidden.bs.modal', function() {
      if (window.speechSynthesis && window.speechSynthesis.speaking) {
        window.speechSynthesis.cancel();
      }
      const btn = document.getElementById('btnVorlesen');
      if (btn) {
        btn.innerHTML = '&#128266; Vorlesen';
        btn.setAttribute('aria-label', 'Zubereitung vorlesen');
      }
    }, { once: true });

    const modal = new bootstrap.Modal(modalEl);
    modal.show();
  } catch (e) {
    alert("Fehler beim Laden der Rezeptdetails: " + e.message);
  }
}
