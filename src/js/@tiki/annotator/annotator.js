import { createTextAnnotator } from '@recogito/text-annotator';
import '@recogito/text-annotator/text-annotator.css';

/**
 * Transform backend response to Recogito-compatible format
 * Backend returns: {total, rows}
 * Recogito expects: [{id, bodies, target}, ...]
 */
function transformBackendResponse(data) {
  if (!data || !data.rows) {
    return [];
  }

  const annotations = data.rows.map(dbAnnotation => {
    if (dbAnnotation.lib === 'recogito') {
      return dbAnnotation;
    } else if (dbAnnotation.ranges || dbAnnotation.text !== undefined) {
      return convertToW3C(dbAnnotation);
    }
    return dbAnnotation;
  });

  const w3cAnnotations = annotations
    .map(anno => {
      if (!anno || !anno.id) {
        return null;
      }
      
      if (!anno.bodies || !anno.target) {
        return null;
      }
      
      return anno;
    })
    .filter(anno => anno !== null);

  return w3cAnnotations;
}

export function initRecogito(config) {
  const objectUri = String(config.objectUri || '').trim();
  const csrfToken = config.csrfToken || '';
  
  if (!objectUri) {
    return null;
  }

  const container = document.getElementById(config.containerId);
  if (!container) {
    return null;
  }

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

  // Load existing annotations
  const searchUrl = `tiki-ajax_services.php?controller=annotation&action=search&uri=${encodeURIComponent(objectUri)}`;
  fetch(searchUrl, { credentials: 'include' })
    .then(r => {
      if (!r.ok) throw new Error(`HTTP ${r.status}`);
      return r.json();
    })
    .then(data => {
      const w3cAnnotations = transformBackendResponse(data);
      
      if (w3cAnnotations.length === 0) {
        return;
      }
      
      anno.setAnnotations(w3cAnnotations);
    })
    .catch(err => {
      console.error('[Recogito] Load error:', err);
    });

  // Create new annotation
  anno.on('createAnnotation', annotation => {
    if (!objectUri || objectUri.trim() === '') {
      return;
    }

    const payload = convertToLegacyPayload(annotation, objectUri);
    
    if (!payload.uri || !payload.uri.includes(':')) {
      return;
    }

    if (csrfToken) {
      payload.ticket = csrfToken;
    }

    const jsonBody = JSON.stringify(payload);

    fetch('tiki-ajax_services.php?controller=annotation&action=create', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: jsonBody
    })
      .then(r => {
        if (!r.ok) {
          return r.text().then(text => {
            let errorMsg = text;
            const bodyMatch = text.match(/<body[^>]*>([\s\S]*?)<\/body>/i);
            if (bodyMatch) {
              errorMsg = bodyMatch[1].substring(0, 500).replace(/<[^>]*>/g, '');
            }
            console.error('[Recogito] Create error:', errorMsg);
            throw new Error(`HTTP ${r.status}: ${errorMsg}`);
          });
        }
        return r.json();
      })
      .then(data => {
        annotation.id = data.id;
      })
      .catch(err => console.error('[Recogito] Create error:', err));
  });

  // Update annotation
  anno.on('updateAnnotation', annotation => {
    const payload = convertToLegacyPayload(annotation, objectUri);
    
    if (csrfToken) {
      payload.ticket = csrfToken;
    }
    
    fetch(`tiki-ajax_services.php?controller=annotation&action=update&threadId=${encodeURIComponent(annotation.id)}`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify(payload)
    })
      .then(r => {
        if (!r.ok) return r.text().then(text => { 
          const bodyMatch = text.match(/<body[^>]*>([\s\S]*?)<\/body>/i);
          let errorMsg = bodyMatch ? bodyMatch[1].substring(0, 500).replace(/<[^>]*>/g, '') : text.substring(0, 500);
          console.error('[Recogito] Update error:', errorMsg);
          throw new Error(`HTTP ${r.status}: ${errorMsg}`);
        });
        return r.json();
      })
      .catch(err => console.error('[Recogito] Update error:', err));
  });

  // Delete annotation
  anno.on('deleteAnnotation', annotation => {
    const deleteUrl = `tiki-ajax_services.php?controller=annotation&action=destroy&threadId=${encodeURIComponent(annotation.id)}` + (csrfToken ? `&ticket=${encodeURIComponent(csrfToken)}` : '');
    
    fetch(deleteUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include'
    })
      .catch(err => console.error('[Recogito] Delete error:', err));
  });

  return anno;
}

/**
 * Convert old OpenAnnotator format to Recogito data model
 * Use BOTH TextQuoteSelector (robust) and TextPositionSelector (fast) for maximum compatibility
 */
function convertToW3C(oldAnnotation) {
  if (!oldAnnotation) {
    return null;
  }
  
  try {
    const quote = typeof oldAnnotation.quote === 'string' ? oldAnnotation.quote : '';
    const text = typeof oldAnnotation.text === 'string' ? oldAnnotation.text : '';
    let start = 0;
    let end = 0;
    
    if (oldAnnotation.ranges && Array.isArray(oldAnnotation.ranges) && oldAnnotation.ranges.length > 0) {
      const range = oldAnnotation.ranges[0];
      if (range && typeof range === 'object') {
        start = parseInt(range.startOffset) || 0;
        end = parseInt(range.endOffset) || 0;
      }
    }

    const selector = [
      {
        type: 'TextQuoteSelector',
        exact: quote,
        prefix: '',
        suffix: ''
      }
    ];
    
    if (start >= 0 && end > start) {
      selector.push({
        type: 'TextPositionSelector',
        start: start,
        end: end
      });
    }

    const converted = {
      id: String(oldAnnotation.id || ''),
      bodies: [{
        type: 'TextualBody',
        value: text,
        purpose: 'commenting'
      }],
      target: {
        selector: selector
      }
    };
    
    JSON.stringify(converted);
    return converted;
    
  } catch (e) {
    return {
      id: String(oldAnnotation?.id || ''),
      bodies: [{
        type: 'TextualBody',
        value: typeof oldAnnotation?.text === 'string' ? oldAnnotation.text : '',
        purpose: 'commenting'
      }],
      target: {
        selector: [{
          type: 'TextQuoteSelector',
          exact: typeof oldAnnotation?.quote === 'string' ? oldAnnotation.quote : '',
          prefix: '',
          suffix: ''
        }]
      }
    };
  }
}

/**
 * Convert W3C annotation back to legacy payload format for backend
 * Backend expects: {text, quote, ranges, uri}
 */
function convertToLegacyPayload(w3cAnnotation, objectUri) {
  let textBody = '';
  let selectorStart = 0;
  let selectorEnd = 0;
  let selectorQuote = '';

  if (w3cAnnotation.bodies && Array.isArray(w3cAnnotation.bodies)) {
    const commentBody = w3cAnnotation.bodies.find(b => b && b.purpose === 'commenting');
    if (commentBody && commentBody.value) {
      textBody = String(commentBody.value);
    }
  }

  if (w3cAnnotation.target && w3cAnnotation.target.selector) {
    const selectors = Array.isArray(w3cAnnotation.target.selector) 
      ? w3cAnnotation.target.selector 
      : [w3cAnnotation.target.selector];
    
    const quoteSelector = selectors.find(s => s && s.type === 'TextQuoteSelector');
    const posSelector = selectors.find(s => s && s.type === 'TextPositionSelector');
    const legacySelector = selectors.find(s => s && (s.quote || s.start !== undefined));
    
    if (quoteSelector) {
      selectorQuote = quoteSelector.exact || '';
    } else if (legacySelector && legacySelector.quote) {
      selectorQuote = legacySelector.quote;
    }
    
    if (posSelector) {
      selectorStart = parseInt(posSelector.start) || 0;
      selectorEnd = parseInt(posSelector.end) || 0;
    } else if (legacySelector && legacySelector.start !== undefined) {
      selectorStart = parseInt(legacySelector.start) || 0;
      selectorEnd = parseInt(legacySelector.end) || 0;
    }
  }

  const uri = String(objectUri || '').trim();

  return {
    text: textBody,
    quote: selectorQuote,
    ranges: [{
      startOffset: selectorStart,
      endOffset: selectorEnd
    }],
    uri: uri
  };
}