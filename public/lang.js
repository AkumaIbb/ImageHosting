const langSelect = document.getElementById('langSelect');

if (langSelect) {
  langSelect.addEventListener('change', (event) => {
    const selected = event.target.value;
    const url = new URL(window.location.href);
    url.searchParams.set('lang', selected);
    window.location.href = url.toString();
  });
}
