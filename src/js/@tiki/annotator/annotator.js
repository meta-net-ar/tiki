console.log('[Recogito] Module loaded!');

import { createTextAnnotator } from '@recogito/text-annotator';
import '@recogito/text-annotator/text-annotator.css';

console.log('[Recogito] Dependencies imported successfully');

/**
 * Initialize Recogito annotator for inline comments
 * @param {Object} config - Configuration object
 * @param {string} config.containerId - DOM element ID to attach annotator
 * @param {string} config.objectUri - Object identifier (type:id)
 * @param {string} config.userId - Current user ID
 * @param {string} config.userName - Current user name
 * @param {boolean} config.canPost - Whether user can create/edit annotations
 * @param {boolean} config.showUserInfo - Show author name and date in popup
 */
export function initRecogito(config) {
  console.group('[Recogito] Initialization started');
  console.log('[Recogito] Config:', config);

  const container = document.getElementById(config.containerId);
  if (!container) {
    console.error(`[Recogito] ERROR: Container #${config.containerId} not found`);
    console.groupEnd();
    return null;
  }
  console.log(`[Recogito] ✓ Container found:`, container);

  // Create annotator instance
  console.log('[Recogito] Creating annotator instance...');
  const anno = createTextAnnotator(container, {
    annotatingEnabled: config.canPost,
    user: {
      id: config.userId,
      name: config.userName
    },
    // Allow selection from any text
    selectionMode: 'all'
  });

  if (!anno) {
    console.error('[Recogito] ERROR: Failed to create annotator instance');
    console.groupEnd();
    return null;
  }
  console.log('[Recogito] ✓ Annotator instance created:', anno);

  // Load existing annotations
  console.log('[Recogito] Loading existing annotations...');
  const searchUrl = `tiki-ajax_services.php?controller=annotation&action=search&uri=${encodeURIComponent(config.objectUri)}`;
  console.log('[Recogito] Search URL:', searchUrl);

  fetch(searchUrl)
    .then(r => {
      console.log('[Recogito] Search response status:', r.status);
      if (!r.ok) {
        return r.text().then(text => {
          console.log('[Recogito] Search error response:', text);
          throw new Error(`Search HTTP ${r.status}: ${text}`);
        });
      }
      return r.json();
    })
    .then(annotations => {
      console.log('[Recogito] ✓ Annotations loaded:', annotations);
      if (Array.isArray(annotations)) {
        console.log(`[Recogito] Loading ${annotations.length} annotations into annotator`);
        anno.loadAnnotations(annotations);
        console.log('[Recogito] ✓ Annotations loaded into UI');
      } else {
        console.warn('[Recogito] Response is not an array:', typeof annotations);
      }
    })
    .catch(err => {
      console.error('[Recogito] ERROR loading annotations:', err);
    });

  // Save new annotation
  anno.on('createAnnotation', annotation => {
    console.group('[Recogito] Creating annotation');
    console.log('[Recogito] Raw annotation from Recogito:', annotation);

    // Ensure proper structure for Recogito
    const now = new Date().toISOString();
    annotation.bodies = annotation.bodies || [];
    annotation.target = annotation.target || {};
    annotation.target.selector = annotation.target.selector || [];

    // Build proper W3C annotation
    const payload = {
      id: annotation.id,
      bodies: annotation.bodies.length > 0 ? annotation.bodies : [{
        type: 'TextualBody',
        value: '',  // Will be filled by Recogito
        purpose: 'commenting'
      }],
      target: {
        selector: annotation.target.selector,
        creator: {
          id: config.userId,
          name: config.userName
        },
        created: now,
        updated: now
      },
      uri: config.objectUri
    };

    console.log('[Recogito] Payload to send:', payload);

    fetch('tiki-ajax_services.php?controller=annotation&action=create', {
      method: 'POST',
      headers: { 
        'Content-Type': 'application/json',
        'X-CSRF-Token': config.csrfToken || ''
      },
      body: JSON.stringify(payload)
    })
      .then(r => {
        console.log('[Recogito] Create response status:', r.status);
        if (!r.ok) {
          return r.text().then(text => {
            console.log('[Recogito] Error response body:', text);
            throw new Error(`HTTP ${r.status}: ${text}`);
          });
        }
        return r.json();
      })
      .then(data => {
        console.log('[Recogito] ✓ Create response:', data);
        annotation.id = data.id;
        console.log('[Recogito] ✓ Annotation saved with ID:', annotation.id);
        console.groupEnd();
      })
      .catch(err => {
        console.error('[Recogito] ERROR creating annotation:', err);
        console.groupEnd();
      });
  });

  // Update annotation
  anno.on('updateAnnotation', (annotation, previous) => {
    console.group('[Recogito] Updating annotation');
    console.log('[Recogito] Previous:', previous);
    console.log('[Recogito] Updated:', annotation);

    annotation.target = annotation.target || {};
    annotation.target.updated = new Date().toISOString();

    const updateUrl = `tiki-ajax_services.php?controller=annotation&action=update&threadId=${encodeURIComponent(annotation.id)}`;
    console.log('[Recogito] Update URL:', updateUrl);

    fetch(updateUrl, {
      method: 'POST',
      headers: { 
        'Content-Type': 'application/json',
        'X-CSRF-Token': config.csrfToken || ''
      },
      body: JSON.stringify(annotation)
    })
      .then(r => {
        console.log('[Recogito] Update response status:', r.status);
        if (!r.ok) {
          return r.text().then(text => {
            console.log('[Recogito] Update error response:', text);
            throw new Error(`Update HTTP ${r.status}: ${text}`);
          });
        }
        return r.json();
      })
      .then(data => {
        console.log('[Recogito] ✓ Update response:', data);
        console.groupEnd();
      })
      .catch(err => {
        console.error('[Recogito] ERROR updating annotation:', err);
        console.groupEnd();
      });
  });

  // Delete annotation
  anno.on('deleteAnnotation', annotation => {
    console.group('[Recogito] Deleting annotation');
    console.log('[Recogito] Annotation ID:', annotation.id);

    const deleteUrl = `tiki-ajax_services.php?controller=annotation&action=destroy&threadId=${encodeURIComponent(annotation.id)}`;
    console.log('[Recogito] Delete URL:', deleteUrl);

    fetch(deleteUrl, {
      method: 'POST',
      headers: {
        'X-CSRF-Token': config.csrfToken || ''
      }
    })
      .then(r => {
        console.log('[Recogito] Delete response status:', r.status);
        if (!r.ok) {
          return r.text().then(text => {
            console.log('[Recogito] Delete error response:', text);
            throw new Error(`Delete HTTP ${r.status}: ${text}`);
          });
        }
        return r.json();
      })
      .then(data => {
        console.log('[Recogito] ✓ Delete response:', data);
        console.groupEnd();
      })
      .catch(err => {
        console.error('[Recogito] ERROR deleting annotation:', err);
        console.groupEnd();
      });
  });

  console.log('[Recogito] ✓ All event listeners attached');
  console.groupEnd();

  return anno;
}
