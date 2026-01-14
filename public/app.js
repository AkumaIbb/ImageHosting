const api = {
  upload: '/api/upload.php',
  createAlbum: '/api/album_create.php',
  listImages: '/api/images.php',
  deleteImage: '/api/delete.php',
};

const dropzone = document.querySelector('#dropzone');
const fileInput = document.querySelector('#fileInput');
const fileName = document.querySelector('#fileName');
const uploadButton = document.querySelector('#uploadButton');
const uploadResponse = document.querySelector('#uploadResponse');
const albumName = document.querySelector('#albumName');
const albumButton = document.querySelector('#albumButton');
const albumResponse = document.querySelector('#albumResponse');
const listButton = document.querySelector('#listButton');
const listResponse = document.querySelector('#listResponse');
const deleteId = document.querySelector('#deleteId');
const deleteButton = document.querySelector('#deleteButton');
const deleteResponse = document.querySelector('#deleteResponse');

const state = {
  file: null,
};

const updateFileDisplay = (file) => {
  if (!file) {
    fileName.value = '';
    return;
  }
  fileName.value = `${file.name} (${Math.round(file.size / 1024)} KB)`;
};

const renderResponse = (element, payload) => {
  element.textContent = JSON.stringify(payload, null, 2);
};

const renderError = (element, error) => {
  element.textContent = `Fehler: ${error.message}`;
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
  return response.text();
};

const handleFile = (file) => {
  state.file = file;
  updateFileDisplay(file);
};

['dragenter', 'dragover'].forEach((eventName) => {
  dropzone.addEventListener(eventName, (event) => {
    event.preventDefault();
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
  const [file] = event.dataTransfer.files;
  if (file) {
    handleFile(file);
  }
});

fileInput.addEventListener('change', (event) => {
  const [file] = event.target.files;
  if (file) {
    handleFile(file);
  }
});

window.addEventListener('paste', (event) => {
  const [item] = event.clipboardData?.files ?? [];
  if (item) {
    handleFile(item);
  }
});

uploadButton.addEventListener('click', async () => {
  if (!state.file) {
    uploadResponse.textContent = 'Bitte zuerst eine Bilddatei auswählen.';
    return;
  }
  const formData = new FormData();
  formData.append('image', state.file);
  uploadResponse.textContent = 'Upload läuft ...';
  try {
    const data = await requestJson(api.upload, {
      method: 'POST',
      body: formData,
    });
    renderResponse(uploadResponse, data);
  } catch (error) {
    renderError(uploadResponse, error);
  }
});

albumButton.addEventListener('click', async () => {
  albumResponse.textContent = 'Album wird erstellt ...';
  try {
    const data = await requestJson(api.createAlbum, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        name: albumName.value || 'Neues Album',
      }),
    });
    renderResponse(albumResponse, data);
  } catch (error) {
    renderError(albumResponse, error);
  }
});

listButton.addEventListener('click', async () => {
  listResponse.textContent = 'Bildliste wird geladen ...';
  try {
    const data = await requestJson(api.listImages);
    renderResponse(listResponse, data);
  } catch (error) {
    renderError(listResponse, error);
  }
});

deleteButton.addEventListener('click', async () => {
  deleteResponse.textContent = 'Löschauftrag wird gesendet ...';
  try {
    const data = await requestJson(api.deleteImage, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        id: deleteId.value || 'example.png',
      }),
    });
    renderResponse(deleteResponse, data);
  } catch (error) {
    renderError(deleteResponse, error);
  }
});

console.info('ImageHosting API endpoints', api);
