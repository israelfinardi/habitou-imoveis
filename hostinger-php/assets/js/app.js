// --- Favoritos -------------------------------------------------------------
// capture:true: o botão de favoritar também aparece dentro do popup do
// mapa (results-map.js), e o Leaflet chama stopPropagation() nos cliques
// dentro do popup na fase de bubble — sem capture, este handler nunca
// rodaria para esses cliques.
document.addEventListener('click', function (e) {
  const btn = e.target.closest('.js-favorite-btn');
  if (!btn) return;
  e.preventDefault();
  const propertyId = btn.dataset.propertyId;
  fetch(APP_BASE + 'actions/favorite.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'property_id=' + encodeURIComponent(propertyId),
  })
    .then((r) => r.json())
    .then((data) => {
      if (data.error === 'unauthenticated') {
        window.location.href = APP_BASE + 'login.php';
        return;
      }
      btn.classList.toggle('is-favorite', data.favorite);
      const isOverlay = btn.classList.contains('js-favorite-overlay');
      const svg = btn.querySelector('svg');
      svg.setAttribute('fill', data.favorite ? '#C1502E' : (isOverlay ? 'rgba(0,0,0,.5)' : 'none'));
      svg.setAttribute('stroke', data.favorite ? (isOverlay ? '#fff' : '#C1502E') : (isOverlay ? '#fff' : '#717171'));
    });
}, true);

// --- Comparador (localStorage) ---------------------------------------------
const COMPARE_KEY = 'habitou:compare';
const COMPARE_MAX = 4;

function readCompare() {
  try {
    return JSON.parse(localStorage.getItem(COMPARE_KEY) || '[]');
  } catch (e) {
    return [];
  }
}

function writeCompare(items) {
  localStorage.setItem(COMPARE_KEY, JSON.stringify(items));
  renderCompareBar();
}

function toggleCompare(id, title, imageUrl) {
  let items = readCompare();
  const exists = items.some((i) => i.id === id);
  if (exists) {
    items = items.filter((i) => i.id !== id);
  } else {
    items.push({ id, title, imageUrl });
    items = items.slice(0, COMPARE_MAX);
  }
  writeCompare(items);
  return !exists;
}

function renderCompareBar() {
  const bar = document.getElementById('compare-bar');
  const itemsEl = document.getElementById('compare-items');
  const link = document.getElementById('compare-link');
  if (!bar) return;
  const items = readCompare();
  if (items.length === 0) {
    bar.classList.add('hidden');
    return;
  }
  bar.classList.remove('hidden');
  itemsEl.innerHTML = items
    .map(
      (i) => `
    <div class="flex shrink-0 items-center gap-2 rounded-lg border border-brand-border px-2 py-1">
      ${i.imageUrl ? `<img src="${i.imageUrl}" class="h-8 w-10 rounded object-cover" alt="">` : ''}
      <span class="max-w-[140px] truncate text-xs">${i.title}</span>
      <button type="button" data-id="${i.id}" class="js-compare-remove text-xs text-brand-text-secondary hover:text-red-600">&times;</button>
    </div>`
    )
    .join('');
  link.href = APP_BASE + 'comparar.php?ids=' + items.map((i) => i.id).join(',');
}

document.addEventListener('click', function (e) {
  const toggleBtn = e.target.closest('.js-compare-toggle');
  if (toggleBtn) {
    const active = toggleCompare(toggleBtn.dataset.id, toggleBtn.dataset.title, toggleBtn.dataset.image || '');
    toggleBtn.textContent = active ? 'Remover da comparação' : 'Adicionar à comparação';
    toggleBtn.classList.toggle('bg-brand-primary/10', active);
    toggleBtn.classList.toggle('text-brand-primary', active);
    toggleBtn.classList.toggle('border-brand-primary', active);
  }
  const removeBtn = e.target.closest('.js-compare-remove');
  if (removeBtn) {
    writeCompare(readCompare().filter((i) => i.id !== removeBtn.dataset.id));
  }
  if (e.target.id === 'compare-clear') {
    writeCompare([]);
  }
});

document.addEventListener('DOMContentLoaded', function () {
  renderCompareBar();
  const toggleBtn = document.querySelector('.js-compare-toggle');
  if (toggleBtn) {
    const active = readCompare().some((i) => i.id === toggleBtn.dataset.id);
    if (active) {
      toggleBtn.textContent = 'Remover da comparação';
      toggleBtn.classList.add('bg-brand-primary/10', 'text-brand-primary', 'border-brand-primary');
    }
  }
});

// --- Galeria de fotos --------------------------------------------------
function propertyGalleryInit(root) {
  const mainImg = root.querySelector('.js-gallery-main img');
  const thumbs = root.querySelectorAll('.js-gallery-thumb');
  const counter = root.querySelector('.js-gallery-counter');
  thumbs.forEach((thumb, idx) => {
    thumb.addEventListener('click', () => {
      mainImg.src = thumb.dataset.full;
      thumbs.forEach((t) => t.classList.remove('border-brand-primary'));
      thumb.classList.add('border-brand-primary');
      if (counter) counter.textContent = idx + 1 + ' / ' + thumbs.length;
    });
  });
}
document.querySelectorAll('.js-gallery').forEach(propertyGalleryInit);
