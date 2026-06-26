function isRecogitoAnnotation(item) {
  if (!item || typeof item !== 'object' || Array.isArray(item)) return false;

  const target = item.target;
  if (!target || !target.selector) return false;

  const selectors = Array.isArray(target.selector)
    ? target.selector
    : [target.selector];

  return selectors.some(s =>
    s &&
    (typeof s.start === 'number' || typeof s.end === 'number' || s.quote)
  );
}

/**
 * Transform backend response to Recogito-compatible format
 * Recogito expects: [{id, bodies, target}, ...]
 */
export function transformBackendResponse(data) {

  console.log('[Recogito][transform] raw response:', data);

  if (!data?.rows || !Array.isArray(data.rows)) return [];

  return data.rows
    .map(row => ({...row?.annotation, threadId: row?.threadId, commentDate: row?.commentDate ?? null, realName: row?.realName ?? null}))
    .filter(isRecogitoAnnotation);
}

/**
 * Convert W3C annotation back to legacy backend format
 */
export function convertToLegacyPayload(w3cAnnotation, objectUri, threadId = null) {

  const payload = {
    threadId,
    text: '',
    quote: w3cAnnotation?.target?.selector[0]?.quote || '',
    data: {...w3cAnnotation, annotationlib: 'recogito'},
    uri: String(objectUri || '').trim(),
  };

  return payload;
}

/**
 * Convert old OpenAnnotator format to W3C model
 */
export function convertToW3C(oldAnnotation) {
  console.log('[Recogito][convertToW3C] input:', oldAnnotation);

  if (!oldAnnotation) {
    console.warn('[Recogito][convertToW3C] null input');
    return null;
  }

  try {
    const quote = typeof oldAnnotation.quote === 'string' ? oldAnnotation.quote : '';
    const text = typeof oldAnnotation.text === 'string' ? oldAnnotation.text : '';

    let start = 0;
    let end = 0;

    if (oldAnnotation.ranges?.length) {
      const range = oldAnnotation.ranges[0];
      start = parseInt(range.startOffset) || 0;
      end = parseInt(range.endOffset) || 0;
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
        start,
        end
      });
    }

    const converted = {
      id: String(oldAnnotation.id || ''),
      bodies: [
        {
          type: 'TextualBody',
          value: text,
          purpose: 'commenting'
        }
      ],
      target: {
        selector
      }
    };

    console.log('[Recogito][convertToW3C] output:', converted);

    return converted;
  } catch (e) {
    console.error('[Recogito][convertToW3C] error:', e);

    return {
      id: String(oldAnnotation?.id || ''),
      bodies: [
        {
          type: 'TextualBody',
          value: oldAnnotation?.text || '',
          purpose: 'commenting'
        }
      ],
      target: {
        selector: [
          {
            type: 'TextQuoteSelector',
            exact: oldAnnotation?.quote || '',
            prefix: '',
            suffix: ''
          }
        ]
      }
    };
  }
}

