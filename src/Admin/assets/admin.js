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

/**
 * Submits a form via fetch instead of navigating, expecting a JSON `{success, message?}` body back.
 * Composable via data attributes, so plain forms can opt into just the parts they need:
 *   - data-vcms-ajax: submit in the background and show the response message inline (base behavior).
 *   - data-vcms-swap-for="id": on success, hide this form and reveal the element with that id.
 *   - data-vcms-reload-on-success: on success, reload the page (for forms whose result changes
 *     other server-rendered content on the page, e.g. toggling a conditional block).
 */
document.querySelectorAll('form[data-vcms-ajax], form[data-vcms-swap-for], form[data-vcms-reload-on-success]').forEach((form) => {
    const swapTarget = form.dataset.vcmsSwapFor ? document.getElementById(form.dataset.vcmsSwapFor) : null;
    if (swapTarget) {
        swapTarget.hidden = true;
    }

    function setLoading(isLoading) {
        const submitButton = form.querySelector('button[type="submit"]');
        submitButton?.classList.toggle('vcms-btn--loading', isLoading);
        if (submitButton) {
            submitButton.disabled = isLoading;
        }
    }

    function showMessage(text, isError) {
        let message = form.querySelector('[data-vcms-form-message]');
        if (!text) {
            message?.remove();
            return;
        }
        if (!message) {
            message = document.createElement('p');
            message.dataset.vcmsFormMessage = '';
            form.prepend(message);
        }
        message.textContent = text;
        message.className = isError ? 'vcms-form__message vcms-form__message--error' : 'vcms-form__message vcms-form__message--success';
    }

    form.addEventListener('submit', (event) => {
        // Respect other submit handlers on this form (e.g. data-confirm) cancelling the submit.
        if (event.defaultPrevented) {
            return;
        }
        event.preventDefault();
        setLoading(true);

        fetch(form.action, {
            method: form.method || 'POST',
            body: new FormData(form),
        })
            .then((response) => response.json())
            .then((data) => {
                if (!data.success) {
                    setLoading(false);
                    showMessage(data.message || 'Something went wrong.', true);
                    return;
                }

                if ('vcmsReloadOnSuccess' in form.dataset) {
                    // Leave the button in its loading state: the reload itself is the confirmation.
                    window.location.reload();
                    return;
                }

                setLoading(false);
                form.reset();
                showMessage(data.message, false);
                if (swapTarget) {
                    form.hidden = true;
                    swapTarget.hidden = false;
                }
            })
            .catch(() => {
                setLoading(false);
                showMessage('Something went wrong.', true);
            });
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

document.addEventListener('click', (event) => {
    const openButton = event.target.closest('[data-vcms-file-field-open]');
    if (openButton) {
        const field = openButton.closest('[data-vcms-file-field]');

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
        return;
    }

    const clearButton = event.target.closest('[data-vcms-file-field-clear]');
    if (clearButton) {
        const field = clearButton.closest('[data-vcms-file-field]');
        field.querySelector('[data-vcms-file-field-input]').value = '';
        buildFilePreview(field.querySelector('[data-vcms-file-field-preview]'), {});
        setFileFieldClearEnabled(clearButton, false);
    }
});

/**
 * Generic "open a <template>'s content in a popup dialog" behavior. Any element can opt in with
 * data-vcms-popup="templateId", pointing at a <template id="templateId"> elsewhere on the page.
 * The page must render one shared dialog via data-vcms-popup-dialog (see render_admin_popup_dialog()).
 */
document.querySelectorAll('[data-vcms-popup-dialog]').forEach((dialog) => {
    dialog.querySelector('[data-vcms-popup-close]')?.addEventListener('click', () => dialog.close());

    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) {
            dialog.close();
        }
    });
});

function openPopup(trigger) {
    const template = document.getElementById(trigger.dataset.vcmsPopup);
    const dialog = document.querySelector('[data-vcms-popup-dialog]');
    if (!template || !dialog) {
        return;
    }

    const body = dialog.querySelector('[data-vcms-popup-body]');
    body.innerHTML = '';
    body.appendChild(template.content.cloneNode(true));

    dialog.showModal();
    dialog.focus();
}

document.addEventListener('click', (event) => {
    const trigger = event.target.closest('[data-vcms-popup]');
    if (trigger) {
        openPopup(trigger);
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key !== 'Enter' && event.key !== ' ') {
        return;
    }
    const trigger = event.target.closest('[data-vcms-popup]');
    if (trigger) {
        event.preventDefault();
        openPopup(trigger);
    }
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
