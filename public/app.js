const api = {
  upload: '/api/upload.php',
};

const dropzone = document.querySelector('#dropzone');
const fileInput = document.querySelector('#fileInput');
const fileName = document.querySelector('#fileName');
const uploadButton = document.querySelector('#uploadButton');
const uploadStatus = document.querySelector('#uploadStatus');

const state = {
  files: [],
  isUploading: false,
};

const updateFileDisplay = () => {
  if (state.files.length === 0) {
    fileName.value = 'Keine Dateien ausgewählt';
    return;
  }
  if (state.files.length === 1) {
    const [file] = state.files;
    fileName.value = `${file.name} (${Math.round(file.size / 1024)} KB)`;
    return;
  }
  fileName.value = `${state.files.length} Dateien ausgewählt`;
};

const renderStatus = (title, detail) => {
  uploadStatus.innerHTML = `
    <strong>${title}</strong>
    <small>${detail}</small>
  `;
};

const requestJson = async (url, options = {}) => {
  const response = await fetch(url, options);
  const contentType = response.headers.get('Content-Type') ?? '';
  if (!response.ok) {
    throw new Error(`Request fehlgeschlagen (${response.status})`);
  }
  if (contentType.includes('application/json')) {
    return response.json();
  }
  throw new Error('Ungültige Serverantwort');
};

const addFiles = (incoming, append = true) => {
  const validFiles = Array.from(incoming).filter((file) =>
    file.type.startsWith('image/')
  );
  if (!append) {
    state.files = validFiles;
  } else {
    state.files = [...state.files, ...validFiles];
  }
  updateFileDisplay();
  if (validFiles.length > 0) {
    renderStatus(
      `${state.files.length} Bild(er) bereit`,
      'Klicke auf Upload starten, um fortzufahren.'
    );
  }
};

['dragenter', 'dragover'].forEach((eventName) => {
  dropzone.addEventListener(eventName, (event) => {
    event.preventDefault();
    if (state.isUploading) {
      return;
    }
    dropzone.classList.add('is-active');
  });
});

['dragleave', 'drop'].forEach((eventName) => {
  dropzone.addEventListener(eventName, (event) => {
    event.preventDefault();
    dropzone.classList.remove('is-active');
  });
});

dropzone.addEventListener('drop', (event) => {
  if (state.isUploading) {
    return;
  }
  addFiles(event.dataTransfer.files, false);
});

fileInput.addEventListener('change', (event) => {
  if (state.isUploading) {
    return;
  }
  addFiles(event.target.files, false);
});

window.addEventListener('paste', (event) => {
  if (state.isUploading) {
    return;
  }
  const files = event.clipboardData?.files ?? [];
  if (files.length > 0) {
    addFiles(files, true);
  }
});

uploadButton.addEventListener('click', async () => {
  if (state.files.length === 0) {
    renderStatus('Bitte zuerst ein Bild auswählen.', 'Ziehe Dateien hier hinein oder nutze Strg+V.');
    return;
  }
  const formData = new FormData();
  state.files.forEach((file) => {
    formData.append('files[]', file);
  });

  state.isUploading = true;
  dropzone.classList.add('is-loading');
  renderStatus('Upload läuft ...', 'Bitte kurze Geduld.');

  try {
    const data = await requestJson(api.upload, {
      method: 'POST',
      body: formData,
    });
    if (!data.ok) {
      throw new Error(data.error || 'Upload fehlgeschlagen.');
    }
    renderStatus('Upload abgeschlossen!', 'Weiterleitung zur Verwaltung ...');
    setTimeout(() => {
      window.location.href = data.manage_url;
    }, 800);
  } catch (error) {
    renderStatus('Upload fehlgeschlagen.', error.message);
  } finally {
    state.isUploading = false;
    dropzone.classList.remove('is-loading');
  }
});
