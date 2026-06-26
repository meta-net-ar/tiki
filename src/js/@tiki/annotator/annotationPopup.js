export function openAnnotationPopup({ annotation, showAuthor = false, mode = 'create' }) {
  return new Promise(resolve => {

    document.getElementById('recogito-comment-popup')?.remove();

    const popup = document.createElement('div');
    popup.id = 'recogito-comment-popup';

    popup.style.position = 'fixed';
    popup.style.background = '#fff';
    popup.style.border = '1px solid #ccc';
    popup.style.borderRadius = '6px';
    popup.style.boxShadow = '0 4px 12px rgba(0,0,0,.15)';
    popup.style.padding = '10px';
    popup.style.minWidth = '280px';
    popup.style.zIndex = '999999';

    // Position near selection if create, otherwise center-ish
    if (mode === 'create') {
      const selection = window.getSelection();
      if (selection.rangeCount) {
        const rect = selection.getRangeAt(0).getBoundingClientRect();
        popup.style.left = `${rect.left + rect.width / 2}px`;
        popup.style.top = `${rect.top - 10}px`;
        popup.style.transform = 'translate(-50%, -100%)';
      }
    } else {
      popup.style.left = '50%';
      popup.style.top = '20%';
      popup.style.transform = 'translate(-50%, 0)';
    }

    const existingComment =
      annotation?.bodies?.find(b => b.purpose === 'commenting')?.value || '';

    const realName = annotation?.realName || '';
    const commentDate = annotation?.commentDate || '';

    popup.innerHTML = `
      <div style="margin-bottom:8px;font-weight:bold">
        ${mode === 'create' ? 'Add comment' : 'Edit annotation'}
      </div>
    
      <div style="font-size:12px;color:#555;margin-bottom:8px">
          ${showAuthor && realName ? `<div><b>Author:</b> ${realName}</div>` : ''}
          ${showAuthor && commentDate ? `<div><b>Date:</b> ${commentDate}</div>` : ''}
      </div>

      <textarea
        rows="4"
        style="width:100%;box-sizing:border-box;resize:vertical"
      >${existingComment}</textarea>

      <div style="display:flex;justify-content:space-between;gap:8px;margin-top:10px">

        <div>
          ${mode === 'edit' ? `
            <button class="delete-btn" style="color:red">Delete</button>
          ` : ''}
        </div>

        <div style="display:flex;gap:8px">
          <button class="cancel-btn">Cancel</button>
          <button class="save-btn">
            ${mode === 'create' ? 'Create' : 'Update'}
          </button>
        </div>
      </div>
    `;

    document.body.appendChild(popup);

    const textarea = popup.querySelector('textarea');
    const saveBtn = popup.querySelector('.save-btn');
    const cancelBtn = popup.querySelector('.cancel-btn');
    const deleteBtn = popup.querySelector('.delete-btn');

    function cleanup() {
      document.removeEventListener('mousedown', outsideClick);
      popup.remove();
    }

    saveBtn.onclick = () => {
      const value = textarea.value.trim();

      cleanup();

      resolve({
        action: mode === 'create' ? 'create' : 'update',
        comment: value
      });
    };

    cancelBtn.onclick = () => {
      cleanup();
      resolve({ action: 'cancel' });
    };

    if (deleteBtn) {
      deleteBtn.onclick = () => {
        cleanup();
        resolve({ action: 'delete' });
      };
    }

    function outsideClick(e) {
      if (!popup.contains(e.target)) {
        cleanup();
        resolve({ action: 'cancel' });
      }
    }

    setTimeout(() => {
      document.addEventListener('mousedown', outsideClick);
    }, 0);

    textarea.addEventListener('keydown', e => {
      if (e.key === 'Escape') {
        cleanup();
        resolve({ action: 'cancel' });
      }

      if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) {
        saveBtn.click();
      }
    });

  });
}
