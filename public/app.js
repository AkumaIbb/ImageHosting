const api = {
  upload: '/api/upload.php',
  register: '/api/register.php',
  me: '/api/me.php',
};

const dropzone = document.querySelector('#dropzone');
const fileInput = document.querySelector('#fileInput');
const fileName = document.querySelector('#fileName');
const uploadButton = document.querySelector('#uploadButton');
const uploadStatus = document.querySelector('#uploadStatus');
const registerButton = document.querySelector('#registerButton');
const accountStatus = document.querySelector('#accountStatus');
const accountLink = document.querySelector('#accountLink');
const adminLink = document.querySelector('#adminLink');

const i18n = window.IH_I18N || {};
const t = (key, fallback) => i18n[key] || fallback || key;
const format = (template, variables = {}) =>
  template.replace(/\{\{(\w+)\}\}/g, (_, token) =>
    Object.prototype.hasOwnProperty.call(variables, token) ? variables[token] : ''
  );

const state = {
  files: [],
  isUploading: false,
};

const updateFileDisplay = () => {
  if (state.files.length === 0) {
    fileName.value = t('app.files_none', 'No files selected');
    return;
  }
  if (state.files.length === 1) {
    const [file] = state.files;
    fileName.value = `${file.name} (${Math.round(file.size / 1024)} KB)`;
    return;
  }
  fileName.value = format(t('app.files_many', '{{count}} files selected'), {
    count: state.files.length,
  });
};

const renderStatus = (title, detail) => {
  uploadStatus.innerHTML = `
    <strong>${title}</strong>
    <small>${detail}</small>
  `;
};

const renderAccountStatus = (title, detail, extra = '') => {
  if (!accountStatus) {
    return;
  }
  accountStatus.innerHTML = `
    <strong>${title}</strong>
    <small>${detail}</small>
    ${extra}
  `;
};

const requestJson = async (url, options = {}) => {
  const response = await fetch(url, options);
  const rawText = await response.text();

  console.log('API status', response.status);
  console.log('API raw response', rawText);

  let data;
  try {
    data = rawText ? JSON.parse(rawText) : null;
  } catch (error) {
    console.error('API returned invalid JSON', rawText);
    throw new Error(t('app.invalid_response', 'Invalid server response'));
  }

  console.log('API parsed JSON', data);

  if (!response.ok) {
    const requestError = new Error(t('app.upload_failed', 'Upload failed'));
    requestError.requestId = data?.request_id;
    throw requestError;
  }

  return data;
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
      format(t('app.images_ready_title', '{{count}} image(s) ready'), {
        count: state.files.length,
      }),
      t('app.images_ready_detail', 'Click start upload to continue.')
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
    renderStatus(
      t('app.select_image_first', 'Please select an image first.'),
      t('app.select_image_detail', 'Drag files here or use Ctrl+V.')
    );
    return;
  }
  const formData = new FormData();
  state.files.forEach((file) => {
    formData.append('files[]', file);
  });

  state.isUploading = true;
  dropzone.classList.add('is-loading');
  renderStatus(
    t('app.upload_in_progress_title', 'Upload in progress ...'),
    t('app.upload_in_progress_detail', 'Please wait a moment.')
  );

  try {
    const data = await requestJson(api.upload, {
      method: 'POST',
      body: formData,
    });
    if (!data.ok) {
      const apiError = new Error(t('app.upload_failed', 'Upload failed'));
      apiError.requestId = data.request_id;
      throw apiError;
    }
    renderStatus(
      t('app.upload_done_title', 'Upload complete!'),
      t('app.upload_done_detail', 'Redirecting to management ...')
    );
    setTimeout(() => {
      window.location.href = data.manage_url;
    }, 800);
  } catch (error) {
    console.error(t('app.upload_failed', 'Upload failed'), error);
    if (error.requestId) {
      console.error('request_id', error.requestId);
    }
    renderStatus(
      t('app.upload_failed_title', 'Upload failed'),
      t('app.upload_failed_detail', 'Please try again.')
    );
  } finally {
    state.isUploading = false;
    dropzone.classList.remove('is-loading');
  }
});

const initAccount = async () => {
  if (!accountStatus) {
    return;
  }
  try {
    const data = await requestJson(api.me);
    if (data?.user_id) {
      renderAccountStatus(
        t('app.account_active_title', 'Account active.'),
        t(
          'app.account_active_detail',
          'Your access key is stored in the cookie. Please keep it safe.'
        )
      );
      if (accountLink) {
        accountLink.style.display = 'inline-flex';
      }
      if (data.is_admin && adminLink) {
        adminLink.style.display = 'inline-flex';
      }
      if (registerButton) {
        registerButton.style.display = 'none';
      }
      return;
    }
  } catch (error) {
    console.warn('Account check failed', error);
  }
};

if (registerButton) {
  registerButton.addEventListener('click', async () => {
    renderAccountStatus(
      t('app.account_create_title', 'Creating account ...'),
      t('app.account_create_detail', 'Please wait.')
    );
    try {
      const data = await requestJson(api.register, { method: 'POST' });
      if (!data?.user_id) {
        throw new Error('Antwort ohne User-ID');
      }
      const userId = data.user_id;
      const copyId = async () => {
        try {
          await navigator.clipboard.writeText(userId);
        } catch (error) {
          console.warn('Clipboard copy failed', error);
        }
      };
      const extra = `
        <div style="margin-top: 10px; display: grid; gap: 6px;">
          <code style="font-size: 1rem; word-break: break-all;">${userId}</code>
          <button class="button" id="copyUserId" type="button">${t('app.copy_key_label', 'Copy access key')}</button>
          <small>${t('app.copy_key_note', 'This key cannot be recovered. Please store it safely.')}</small>
        </div>
      `;
      renderAccountStatus(
        t('app.account_created_title', 'Account created.'),
        t('app.account_created_detail', 'Access key shown once:'),
        extra
      );
      if (accountLink) {
        accountLink.style.display = 'inline-flex';
      }
      if (data.is_admin && adminLink) {
        adminLink.style.display = 'inline-flex';
      }
      registerButton.style.display = 'none';
      const copyButton = document.querySelector('#copyUserId');
      if (copyButton) {
        copyButton.addEventListener('click', copyId);
        copyButton.addEventListener('keydown', (event) => {
          if (event.key === 'Enter' || event.key === ' ') {
            copyId();
          }
        });
      }
    } catch (error) {
      console.error('Account registration failed', error);
      renderAccountStatus(
        t('app.account_create_failed_title', 'Account could not be created.'),
        t('app.account_create_failed_detail', 'Please try again.')
      );
    }
  });
}

initAccount();
