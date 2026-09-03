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

document.querySelectorAll('form[data-vcms-swap-for]').forEach((form) => {
    const target = document.getElementById(form.dataset.vcmsSwapFor);
    if (!target) {
        return;
    }

    target.hidden = true;

    form.addEventListener('submit', (event) => {
        event.preventDefault();

        fetch(form.action, {
            method: form.method || 'POST',
            body: new FormData(form),
        })
            .then((response) => {
                if (!response.ok) {
                    return;
                }
                form.hidden = true;
                target.hidden = false;
            })
            .catch(() => {});
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

function buildThumbNode({ thumb, ext }) {
    if (thumb) {
        const img = document.createElement('img');
        img.className = 'vcms-upload-grid__thumb';
        img.src = thumb;
        img.alt = '';
        return img;
    }
    if (ext) {
        const extBadge = document.createElement('span');
        extBadge.className = 'vcms-upload-grid__ext';
        extBadge.textContent = ext;
        return extBadge;
    }
    return null;
}

function buildFilePreview(container, { thumb, ext, name }) {
    container.innerHTML = '';

    const thumbNode = buildThumbNode({ thumb, ext });
    if (thumbNode) {
        container.appendChild(thumbNode);
    }

    const nameLabel = document.createElement('span');
    nameLabel.className = 'vcms-file-field__name';
    nameLabel.textContent = name || 'No file selected';
    container.appendChild(nameLabel);
}

function createFilePickerItem(item) {
    const template = document.querySelector('[data-vcms-file-picker-item-template]');
    const button = template.content.firstElementChild.cloneNode(true);

    button.dataset.id = item.id;
    button.dataset.name = item.name;
    button.dataset.thumb = item.thumb;
    button.dataset.ext = item.ext;

    const nameLabel = button.querySelector('[data-vcms-file-picker-item-name]');
    const thumbNode = buildThumbNode(item);
    if (thumbNode) {
        button.insertBefore(thumbNode, nameLabel);
    }
    nameLabel.textContent = item.name;

    return button;
}

function buildFilePickerDialog() {
    const template = document.querySelector('[data-vcms-file-picker-template]');
    const dialog = template.content.firstElementChild.cloneNode(true);
    document.body.appendChild(dialog);

    const closeButton = dialog.querySelector('[data-vcms-file-picker-close]');
    const yearSelect = dialog.querySelector('[data-vcms-file-picker-year]');
    const monthSelect = dialog.querySelector('[data-vcms-file-picker-month]');
    const list = dialog.querySelector('[data-vcms-file-picker-list]');

    closeButton.addEventListener('click', () => dialog.close());

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
                filePickerList.appendChild(createFilePickerItem(item));
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
        filePickerDialog.focus();
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
