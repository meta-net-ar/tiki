import { createTextAnnotator } from '@recogito/text-annotator';
import { openAnnotationPopup } from './annotationPopup.js';
import { convertToLegacyPayload, transformBackendResponse, convertToW3C } from './helpers.js';
import { saveAnnotationBackend, updateAnnotationBackend, deleteAnnotationBackend, loadAnnotations } from './api.js';

import { injectRecogitoCSS } from './annotatorStyles.js';

let isCreating = false;

export async function initRecogito(config) {

  const objectUri = String(config.objectUri || '').trim();
  const csrfToken = config.csrfToken || '';
  const showUserInfo = config.showUserInfo || false;

  if (!objectUri) {
    return null;
  }

  const container = document.getElementById(config.containerId);

  if (!container) {
    return null;
  }

  injectRecogitoCSS();

  const anno = createTextAnnotator(container, {
    annotatingEnabled: config.canPost,
    user: {
      id: config.userId,
      name: config.userName
    },
    selectionMode: 'all'
  });

  if (!anno) {
    return null;
  }

  /**
   * Load existing annotations
   */
  const annotationsList = await loadAnnotations(objectUri, transformBackendResponse);
  console.log('[Recogito][init] loaded annotations:', annotationsList);
  anno.setAnnotations(annotationsList);

  /**
   * Click an annotation
  */
  anno.on('selectionChanged', async annotations => {
    if (isCreating) return;

    if (!annotations || annotations.length === 0) return;

    const annotation = annotations[0];

    const res = await openAnnotationPopup({
      annotation,
      mode: 'edit',
      showAuthor: showUserInfo
    });

    if (res.action === 'cancel') {
      anno.setSelected(undefined);
      return;
    }

    if (res.action === 'delete') {
      anno.removeAnnotation(annotation);
      return;
    }

    if (res.action === 'update') {
      let body = annotation.bodies.find(b => b.purpose === 'commenting');

      if (!body) {
        body = {
          purpose: 'commenting',
          value: res.comment
        };
        annotation.bodies.push(body);
      } else {
        body.value = res.comment;
      }

      const payload = convertToLegacyPayload(annotation, objectUri, annotation.threadId);
      anno.updateAnnotation(annotation);
    }
  });

  /**
   * Create annotation
   */
  anno.on('createAnnotation', async annotation => {
    isCreating = true;
    if (!objectUri?.trim()) {
      return;
    }

    try {
      const result = await openAnnotationPopup({
        annotation,
        mode: 'create',
        showAuthor: false
      });

      if (!result.action || result?.action === 'cancel') {
        anno.removeAnnotation(annotation.id);
        anno.setSelected(undefined);
        isCreating = false;
        return;
      }

      annotation.bodies.push({
        purpose: 'commenting',
        value: result?.comment || ''
      });

      let payload = convertToLegacyPayload(annotation, objectUri);
      payload = { ...payload, text: result?.comment || '' };

      if (!payload.uri || !payload.uri.includes(':')) {
        return;
      }

      if (csrfToken) {
        payload.ticket = csrfToken;
      }

      isCreating = false;
      anno.setSelected(undefined);
      await saveAnnotationBackend(payload);
    } finally {
      isCreating = false;
      anno.setSelected(undefined);
    }
  });

  /**
   * Update annotation
   */
  anno.on('updateAnnotation', annotation => {
    const payload = convertToLegacyPayload(annotation, objectUri, annotation.threadId);

    if (csrfToken) {
      payload.ticket = csrfToken;
    }
    updateAnnotationBackend(payload);
  });

  /**
   * Delete annotation
   */
  anno.on('deleteAnnotation', annotation => {
    if(!annotation?.threadId) {
      return;
    }

    deleteAnnotationBackend(annotation.threadId);

  });

  return anno;
}

