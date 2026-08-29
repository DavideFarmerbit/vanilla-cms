document.querySelectorAll('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (!window.confirm(form.dataset.confirm)) {
            event.preventDefault();
        }
    });
});

document.querySelectorAll('a[data-confirm]').forEach((link) => {
    link.addEventListener('click', (event) => {
        if (!window.confirm(link.dataset.confirm)) {
            event.preventDefault();
        }
    });
});

document.querySelectorAll('[data-vcms-dropzone]').forEach((dropzone) => {
    const input = dropzone.querySelector('[data-vcms-dropzone-input]');

    ['dragenter', 'dragover'].forEach((eventName) => {
        dropzone.addEventListener(eventName, (event) => {
            event.preventDefault();
            dropzone.classList.add('vcms-dropzone--active');
        });
    });

    ['dragleave', 'drop'].forEach((eventName) => {
        dropzone.addEventListener(eventName, (event) => {
            event.preventDefault();
            dropzone.classList.remove('vcms-dropzone--active');
        });
    });

    dropzone.addEventListener('drop', (event) => {
        const files = event.dataTransfer?.files;
        if (!files || files.length === 0) {
            return;
        }
        input.files = files;
        dropzone.requestSubmit();
    });
});

let filePickerDialog = null;
let filePickerList = null;
let filePickerYearSelect = null;
let filePickerMonthSelect = null;
let activeFileField = null;
const filePickerState = { type: '', offset: 0, hasMore: true, loading: false };

function setFileFieldClearEnabled(clearButton, enabled) {
    clearButton.disabled = !enabled;
    clearButton.classList.toggle('vcms-icon-btn--disabled', !enabled);
}

function buildFilePreview(container, { thumb, ext, name }) {
    container.innerHTML = '';

    if (thumb) {
        const img = document.createElement('img');
        img.className = 'vcms-upload-grid__thumb';
        img.src = thumb;
        img.alt = '';
        container.appendChild(img);
    } else if (ext) {
        const extBadge = document.createElement('span');
        extBadge.className = 'vcms-upload-grid__ext';
        extBadge.textContent = ext;
        container.appendChild(extBadge);
    }

    const nameLabel = document.createElement('span');
    nameLabel.className = 'vcms-file-field__name';
    nameLabel.textContent = name || 'No file selected';
    container.appendChild(nameLabel);
}

function buildFilePickerDialog() {
    const dialog = document.createElement('dialog');
    dialog.className = 'vcms-file-picker-dialog';

    const filters = document.createElement('div');
    filters.className = 'vcms-file-picker-dialog__filters';

    const yearSelect = document.createElement('select');
    yearSelect.className = 'vcms-field__input';
    const yearAllOption = new Option('All years', '');
    yearSelect.appendChild(yearAllOption);
    const currentYear = new Date().getFullYear();
    for (let year = currentYear; year >= currentYear - 5; year--) {
        yearSelect.appendChild(new Option(String(year), String(year)));
    }

    const monthSelect = document.createElement('select');
    monthSelect.className = 'vcms-field__input';
    monthSelect.appendChild(new Option('All months', ''));
    const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    monthNames.forEach((label, index) => {
        monthSelect.appendChild(new Option(label, String(index + 1).padStart(2, '0')));
    });

    filters.append(yearSelect, monthSelect);

    const list = document.createElement('div');
    list.className = 'vcms-upload-grid vcms-file-picker-dialog__list';

    const closeButton = document.createElement('button');
    closeButton.type = 'button';
    closeButton.className = 'vcms-btn vcms-file-picker-dialog__close';
    closeButton.textContent = 'Close';
    closeButton.addEventListener('click', () => dialog.close());

    dialog.append(closeButton, filters, list);
    document.body.appendChild(dialog);

    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) {
            dialog.close();
        }
    });

    yearSelect.addEventListener('change', resetAndLoadFilePicker);
    monthSelect.addEventListener('change', resetAndLoadFilePicker);

    list.addEventListener('scroll', () => {
        if (list.scrollTop + list.clientHeight >= list.scrollHeight - 100) {
            loadMoreFilePickerItems();
        }
    });

    list.addEventListener('click', (event) => {
        const item = event.target.closest('[data-vcms-file-picker-item]');
        if (!item) {
            return;
        }
        if (activeFileField) {
            activeFileField.input.value = item.dataset.id;
            buildFilePreview(activeFileField.preview, item.dataset);
            setFileFieldClearEnabled(activeFileField.clear, true);
        }
        dialog.close();
    });

    filePickerDialog = dialog;
    filePickerList = list;
    filePickerYearSelect = yearSelect;
    filePickerMonthSelect = monthSelect;
}

function resetAndLoadFilePicker() {
    filePickerList.innerHTML = '';
    filePickerState.offset = 0;
    filePickerState.hasMore = true;
    loadMoreFilePickerItems();
}

function loadMoreFilePickerItems() {
    if (filePickerState.loading || !filePickerState.hasMore) {
        return;
    }
    filePickerState.loading = true;

    const params = new URLSearchParams({
        type: filePickerState.type,
        year: filePickerYearSelect.value,
        month: filePickerMonthSelect.value,
        offset: String(filePickerState.offset),
    });

    fetch(`/admin/uploads/api?${params.toString()}`)
        .then((response) => response.json())
        .then((data) => {
            data.items.forEach((item) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'vcms-upload-grid__item';
                button.setAttribute('data-vcms-file-picker-item', '');
                button.dataset.id = item.id;
                button.dataset.name = item.name;
                button.dataset.thumb = item.thumb;
                button.dataset.ext = item.ext;
                buildFilePreview(button, item);
                filePickerList.appendChild(button);
            });

            filePickerState.offset += data.items.length;
            filePickerState.hasMore = data.hasMore;
            filePickerState.loading = false;
        })
        .catch(() => {
            filePickerState.loading = false;
        });
}

document.querySelectorAll('[data-vcms-file-field-open]').forEach((button) => {
    button.addEventListener('click', () => {
        const field = button.closest('[data-vcms-file-field]');

        if (!filePickerDialog) {
            buildFilePickerDialog();
        }

        activeFileField = {
            input: field.querySelector('[data-vcms-file-field-input]'),
            preview: field.querySelector('[data-vcms-file-field-preview]'),
            clear: field.querySelector('[data-vcms-file-field-clear]'),
        };
        filePickerState.type = field.dataset.allowedType;
        filePickerYearSelect.value = '';
        filePickerMonthSelect.value = '';

        filePickerDialog.showModal();
        resetAndLoadFilePicker();
    });
});

document.querySelectorAll('[data-vcms-file-field-clear]').forEach((button) => {
    button.addEventListener('click', () => {
        const field = button.closest('[data-vcms-file-field]');
        field.querySelector('[data-vcms-file-field-input]').value = '';
        buildFilePreview(field.querySelector('[data-vcms-file-field-preview]'), {});
        setFileFieldClearEnabled(button, false);
    });
});

document.querySelectorAll('[data-vcms-repeater]').forEach((repeater) => {
    const items = repeater.querySelector(':scope > [data-vcms-repeater-items]');
    const template = repeater.querySelector(':scope > template[data-vcms-repeater-template]');
    const addButton = repeater.querySelector(':scope > [data-vcms-repeater-add]');

    let nextIndex = items.children.length;

    function createItem() {
        const index = `new-${nextIndex++}`;
        const html = template.innerHTML.replaceAll('__VCMS_REPEATER_INDEX__', index);
        const wrapper = document.createElement('div');
        wrapper.innerHTML = html.trim();
        return wrapper.firstElementChild;
    }

    addButton.addEventListener('click', () => {
        items.appendChild(createItem());
    });

    items.addEventListener('click', (event) => {
        const insertButton = event.target.closest('[data-vcms-repeater-insert]');
        if (insertButton && insertButton.closest('[data-vcms-repeater-items]') === items) {
            const item = insertButton.closest('[data-vcms-repeater-item]');
            item.insertAdjacentElement('beforebegin', createItem());
            return;
        }

        const deleteButton = event.target.closest('[data-vcms-repeater-delete]');
        if (deleteButton && deleteButton.closest('[data-vcms-repeater-items]') === items) {
            deleteButton.closest('[data-vcms-repeater-item]').remove();
        }
    });
});
