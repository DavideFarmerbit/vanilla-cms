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
