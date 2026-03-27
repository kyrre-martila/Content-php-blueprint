(function () {
  const csrfToken = window.__EDITOR_MODE && window.__EDITOR_MODE.csrfToken ? window.__EDITOR_MODE.csrfToken : '';
  if (!csrfToken) {
    return;
  }

  function textContentOf(element) {
    return (element.textContent || '').trim();
  }

  async function saveField(target, value) {
    const payload = new URLSearchParams();
    payload.set('_csrf_token', csrfToken);
    payload.set('target_type', target.dataset.editType || '');
    payload.set('field', target.dataset.editField || '');
    payload.set('value', value);

    if (target.dataset.editType === 'content_item') {
      payload.set('id', target.dataset.editId || '');
    }

    if (target.dataset.editType === 'pattern_block') {
      payload.set('content_id', target.dataset.editContentId || '');
      payload.set('block_index', target.dataset.editBlockIndex || '');
    }

    const response = await fetch('/admin/editor-mode/update', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
      },
      body: payload.toString()
    });

    const result = await response.json();

    if (!response.ok || !result.ok) {
      throw new Error(result.error || 'save_failed');
    }

    return typeof result.value === 'string' ? result.value : value;
  }

  function buildInlineEditor(target) {
    const startValue = textContentOf(target);
    const textareaMode = target.dataset.editField === 'body' || target.innerHTML.includes('<br');
    const input = document.createElement(textareaMode ? 'textarea' : 'input');
    input.className = 'editor-inline-input';
    input.value = startValue;

    if (textareaMode) {
      input.rows = 4;
    }

    const actions = document.createElement('span');
    actions.className = 'editor-inline-actions';

    const save = document.createElement('button');
    save.type = 'button';
    save.textContent = 'Save';

    const cancel = document.createElement('button');
    cancel.type = 'button';
    cancel.textContent = 'Cancel';

    actions.append(save, cancel);

    const wrapper = document.createElement('span');
    wrapper.className = 'editor-inline-wrapper';
    wrapper.append(input, actions);

    const originalHtml = target.innerHTML;
    target.innerHTML = '';
    target.appendChild(wrapper);

    cancel.addEventListener('click', function () {
      target.innerHTML = originalHtml;
    });

    save.addEventListener('click', async function () {
      save.disabled = true;
      try {
        const updatedValue = await saveField(target, input.value);
        target.textContent = updatedValue;
      } catch (error) {
        save.disabled = false;
        alert('Failed to save field.');
      }
    });

    input.focus();
  }

  document.querySelectorAll('.editor-editable').forEach(function (element) {
    element.addEventListener('click', function (event) {
      if (element.querySelector('.editor-inline-wrapper')) {
        return;
      }

      event.preventDefault();
      buildInlineEditor(element);
    });
  });
})();
