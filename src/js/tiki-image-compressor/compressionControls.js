/**
 * Compression Controls Module
 * 
 * Manages UI controls for image compression settings in tiki-upload.tpl
 * Detects and applies custom user selections via DOM/jQuery manipulation
 */

let compressionSettings = {};
let initialServerPrefs = {}; // Store original server preferences for reset

/**
 * Initialize compression controls with server settings
 * Attaches event listeners to DOM elements
 * Reads any existing custom values from form inputs
 * 
 * @param {Object} initialValues - Server preferences
 *   - quality: number (0-100)
 *   - maxSize: number (MB)
 *   - saveOriginal: string ('y' or 'n')
 *   - preset: string ('slider' or 'levels')
 */
function initCompressionControls(initialValues = {}) {
    Object.assign(compressionSettings, initialValues);
    // Store copy of server defaults for "Back to default" button
    Object.assign(initialServerPrefs, initialValues);
    
    // DON'T read form values on init - just use server defaults for display
    // Form values will be read when actually needed (before upload)
    
    console.log('Compression controls initialized:', compressionSettings);
    console.log('Server preferences stored:', initialServerPrefs);
    // attachEventListeners is called separately from initializeCompressionUI
}

/**
 * Read custom values from form inputs if they exist and differ from defaults
 * This handles the case where user has already customized before module loads
 */
function readCustomFormValues() {
    // Check quality slider FIRST (takes precedence if user touched it)
    const slider = document.getElementById("compression_quality_main");
    if (slider) {
        const sliderValue = parseInt(slider.value, 10);
        if (!isNaN(sliderValue) && sliderValue >= 0 && sliderValue <= 100) {
            compressionSettings.quality = sliderValue;
            // Slider wins - don't fall through to preset
        }
    }

    // Fall back to preset only if slider is empty/invalid
    if (!compressionSettings.quality) {
        const presetRadios = document.querySelectorAll('[name="compression_preset_main"]:checked');
        if (presetRadios.length > 0) {
            const presetValue = presetRadios[0].value;
            const presetMap = {
                'high': 90,
                'balanced': 75,
                'compressed': 60
            };
            if (presetMap[presetValue]) {
                compressionSettings.quality = presetMap[presetValue];
            }
        }
    }

    // Check max dimension input (pixel size)
    const maxDimensionInput = document.querySelector('[name="compression_max_dimension"]');
    if (maxDimensionInput && maxDimensionInput.value) {
        const value = parseInt(maxDimensionInput.value, 10);
        if (!isNaN(value) && value > 0) {
            compressionSettings.maxDimension = value;
        }
    }

    // Check min file size to compress (MB threshold)
    const maxSizeInput = document.querySelector('[name="compression_max_size"]');
    if (maxSizeInput && maxSizeInput.value) {
        const value = parseFloat(maxSizeInput.value);
        if (!isNaN(value) && value > 0) {
            compressionSettings.maxSize = value;
        }
    }

    // Check save original checkbox
    const saveOriginalChk = document.getElementById("compression_save_original_main");
    if (saveOriginalChk) {
        compressionSettings.saveOriginal = saveOriginalChk.checked ? "y" : "n";
    }
}

/**
 * Attach event listeners to compression control elements
 * Updates compressionSettings when user changes any control
 */
function attachEventListeners() {
    // Quality preset radios - update on change
    document.querySelectorAll('[name="compression_preset_main"]').forEach((el) => {
        el.addEventListener("change", () => {
            const presetMap = {
                'high': 90,
                'balanced': 75,
                'compressed': 60
            };
            if (presetMap[el.value]) {
                compressionSettings.quality = presetMap[el.value];
                // Update slider and display to match preset
                const slider = document.getElementById("compression_quality_main");
                const display = document.getElementById("quality_display_main");
                if (slider) slider.value = compressionSettings.quality;
                if (display) display.textContent = compressionSettings.quality + "%";
                console.log('Preset selected:', el.value, 'Quality:', compressionSettings.quality);
            }
        });
    });

    // Quality slider - update on input (slider takes precedence)
    const slider = document.getElementById("compression_quality_main");
    if (slider) {
        slider.addEventListener("input", () => {
            const value = parseInt(slider.value, 10);
            if (value >= 0 && value <= 100) {
                compressionSettings.quality = value;
                const display = document.getElementById("quality_display_main");
                if (display) display.textContent = value + "%";
                console.log('Quality slider changed to:', compressionSettings.quality);
            }
        });
    }

    // Save original checkbox - update on change
    const saveOriginalChk = document.getElementById("compression_save_original_main");
    if (saveOriginalChk) {
        saveOriginalChk.addEventListener("change", () => {
            compressionSettings.saveOriginal = saveOriginalChk.checked ? "y" : "n";
            console.log('Save original changed to:', compressionSettings.saveOriginal);
        });
    }

    // Max dimension input - update on input with validation
    const maxDimensionInput = document.querySelector('[name="compression_max_dimension"]');
    if (maxDimensionInput) {
        maxDimensionInput.addEventListener("input", () => {
            const value = parseInt(maxDimensionInput.value, 10);
            if (!isNaN(value) && value > 0) {
                compressionSettings.maxDimension = value;
                console.log('Max dimension changed to:', compressionSettings.maxDimension);
            }
        });
    }

    // Min file size to compress input - update on input with validation
    const maxSizeInput = document.querySelector('[name="compression_max_size"]');
    if (maxSizeInput) {
        maxSizeInput.addEventListener("input", () => {
            const value = parseFloat(maxSizeInput.value);
            if (!isNaN(value) && value > 0) {
                compressionSettings.maxSize = value;
                console.log('Min file size changed to:', compressionSettings.maxSize);
            }
        });
    }
}

/**
 * Get current compression settings (includes any user customizations)
 * 
 * @returns {Object} - Copy of current settings with user customizations
 */
function getCompressionSettings() {
    // Re-read form values to ensure we have latest user input
    readCustomFormValues();
    return { ...compressionSettings };
}

/**
 * Toggle between default summary and custom controls UI
 * 
 * @param {Boolean} enable - Show custom controls
 */
function toggleCustomCompressionMain(enable = true) {
    const defaultSummary = document.getElementById("compression_default_summary");
    const customControls = document.getElementById("compression_custom_controls");
    
    if (defaultSummary) defaultSummary.classList.toggle("d-none", enable);
    if (customControls) customControls.classList.toggle("d-none", !enable);

    if (enable) {
        const settings = mapCompressionSettingsToOptions();
        console.log("Custom compression UI shown with settings:", settings);
        
        // Restore quality display
        const display = document.getElementById("quality_display_main");
        if (display) display.textContent = settings.qualityPercent + "%";

        // Restore max dimension input
        const maxDimensionInput = document.querySelector('[name="compression_max_dimension"]');
        if (maxDimensionInput) maxDimensionInput.value = settings.maxDimension || 1600;

        // Restore max file size input
        const maxSizeInput = document.querySelector('[name="compression_max_size"]');
        if (maxSizeInput) maxSizeInput.value = settings.maxSize || 1;

        // Restore save original checkbox
        const saveOriginalChk = document.getElementById("compression_save_original_main");
        if (saveOriginalChk) saveOriginalChk.checked = settings.saveOriginal === "y";
    } else {
        // Back to default - reset compressionSettings to server preferences
        Object.assign(compressionSettings, initialServerPrefs);
        console.log('Reset to server preferences:', compressionSettings);
        
        // Update default summary with server values
        updateDefaultSummary(initialServerPrefs);
    }
}

/**
 * Map compression settings to compression options format
 * Reads current form values to include user customizations
 * 
 * @returns {Object} - Formatted compression options with user customizations
 */
function mapCompressionSettingsToOptions() {
    // Get latest settings including user customizations
    const s = getCompressionSettings();

    return {
        maxDimension: s.maxDimension ? parseInt(s.maxDimension, 10) : 1600,
        qualityPercent: s.quality ? parseInt(s.quality, 10) : 85,
        saveOriginal: s.saveOriginal || "n",
    };
}

/**
 * Update default summary display with preference values
 * 
 * @param {Object} prefs - Preference values (quality, maxDimension, saveOriginal)
 */
function updateDefaultSummary(prefs) {
    const maxDimensionEl = document.getElementById("default_max_size");
    if (maxDimensionEl) maxDimensionEl.textContent = (prefs.maxDimension || 1600) + " px";

    const minFileEl = document.getElementById("default_min_file");
    if (minFileEl) minFileEl.textContent = (prefs.maxSize || 1) + " MB";

    const saveOriginalEl = document.getElementById("default_save_original");
    if (saveOriginalEl) saveOriginalEl.textContent = (prefs.saveOriginal === "y") ? "Yes" : "No";

    const unzipEl = document.getElementById("default_unzip");
    if (unzipEl) unzipEl.textContent = "No";
}

/**
 * Complete initialization function for compression UI
 * Handles all setup in one call: preferences, DOM wiring, event listeners, toggles, form integration
 * Everything self-contained - no window exposure, no external dependencies
 * 
 * @param {Object} config - Server preferences (quality, maxDimension, saveOriginal, preset)
 */
function initializeCompressionUI(config = {}) {
    // Initialize with server settings immediately
    initCompressionControls(config);
    
    // Attach all event listeners after init
    attachEventListeners();
    console.log('[CompressionUI] ✓ Event listeners attached');
    
    // Use event delegation for toggle button - works even if button not yet in DOM
    document.addEventListener('click', (e) => {
        if (e.target?.id === 'customize-compression-toggle' || e.target?.closest('#customize-compression-toggle')) {
            console.log('[CompressionUI] Toggle button clicked');
            toggleCustomCompressionMain(true);
        }
        if (e.target?.id === 'compression-back-to-default' || e.target?.closest('#compression-back-to-default')) {
            console.log('[CompressionUI] Back button clicked');
            toggleCustomCompressionMain(false);
        }
    });
    console.log('[CompressionUI] ✓ Toggle listeners attached (event delegation)');
    
    // Update default summary display with server values (not form values)
    updateDefaultSummary(compressionSettings);
    console.log('[CompressionUI] ✓ Default summary updated');
    
    // Wire up file upload form - sync settings before submit
    const attachFormListener = () => {
        const uploadForm = document.querySelector('form[name="upload"]') || document.querySelector('form[enctype="multipart/form-data"]');
        if (uploadForm) {
            uploadForm.addEventListener('submit', syncCompressionSettingsToForm, true);
            console.log('[CompressionUI] ✓ Upload form listener attached');
            return true;
        }
        return false;
    };
    
    // Try to attach form listener, if it fails wait for DOMContentLoaded
    if (!attachFormListener()) {
        if (document.readyState === 'loading') {
            console.log('[CompressionUI] Waiting for DOM to find upload form...');
            document.addEventListener('DOMContentLoaded', attachFormListener, { once: true });
        } else {
            console.warn('[CompressionUI] ⚠ Upload form not found');
        }
    }
    
    console.log('[CompressionUI] ✓ Fully initialized');
}

/**
 * Compress files in form input before upload
 * @private
 */
async function compressFilesBeforeUpload(uploadForm) {
    const { compress, isSupported } = await import('@tiki/image-compressor');
    
    const settings = mapCompressionSettingsToOptions();
    const fileInputs = uploadForm.querySelectorAll('input[type="file"][name="userfile[]"]');
    
    console.log('[CompressionUI] Starting compression for', fileInputs.length, 'files');
    
    for (const fileInput of fileInputs) {
        if (!fileInput.files || fileInput.files.length === 0) continue;
        
        const file = fileInput.files[0];
        
        // Check if file should be compressed
        if (!isSupported(file)) {
            console.log('[CompressionUI] File not supported for compression:', file.name);
            continue;
        }
        
        // Check if file size exceeds minimum threshold
        const minSizeMB = compressionSettings.maxSize || 1;
        const fileSizeMB = file.size / (1024 * 1024);
        if (fileSizeMB < minSizeMB) {
            console.log('[CompressionUI] File too small:', file.name, `(${fileSizeMB.toFixed(2)}MB < ${minSizeMB}MB)`);
            continue;
        }
        
        try {
            console.log('[CompressionUI] Compressing:', file.name, 'with settings:', settings);
            const result = await compress(file, settings);
            
            if (result.success) {
                // Replace the file in the input with the compressed blob
                const dataTransfer = new DataTransfer();
                const compressedFile = new File([result.blob], file.name, { type: result.blob.type });
                dataTransfer.items.add(compressedFile);
                fileInput.files = dataTransfer.files;
                
                console.log('[CompressionUI] ✓ Compressed:', file.name, 
                    'from', (file.size / 1024).toFixed(2), 'KB to', 
                    (result.blob.size / 1024).toFixed(2), 'KB');
            } else if (result.skipped) {
                console.log('[CompressionUI] Skipped:', file.name, '-', result.reason);
            } else {
                console.warn('[CompressionUI] ✗ Compression failed:', file.name, '-', result.error);
            }
        } catch (err) {
            console.error('[CompressionUI] Error compressing file:', file.name, err);
            // Continue with original file if compression fails
        }
    }
}

/**
 * Sync current compression settings to form as hidden fields before upload
 * Prevents duplicate fields by updating existing ones instead of creating new
 * @private
 */
function syncCompressionSettingsToForm(e) {
    if (!(e.target instanceof HTMLFormElement)) return;
    
    const uploadForm = e.target;
    const settings = mapCompressionSettingsToOptions();
    
    // Compress files first if compression is enabled
    const isCompressionEnabled = compressionSettings.quality || compressionSettings.maxDimension;
    console.log('[CompressionUI] Compression enabled:', isCompressionEnabled, 'Settings:', compressionSettings);
    
    if (isCompressionEnabled) {
        // Prevent form submission, compress files, then submit
        e.preventDefault();
        compressFilesBeforeUpload(uploadForm).then(() => {
            // After compression complete, record settings and submit form
            const setOrUpdateHidden = (name, value) => {
                let input = uploadForm.querySelector(`input[name="${name}"]`);
                if (!input) {
                    input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = name;
                    uploadForm.appendChild(input);
                }
                input.value = value;
            };
            
            setOrUpdateHidden('compression_quality_used', settings.qualityPercent);
            setOrUpdateHidden('compression_max_dimension_used', settings.maxDimension);
            setOrUpdateHidden('compression_save_original_used', settings.saveOriginal);
            console.log('[CompressionUI] Compression complete, submitting form');
            
            // Remove the submit listener before resubmitting to avoid infinite loop
            uploadForm.removeEventListener('submit', syncCompressionSettingsToForm, true);
            uploadForm.submit();
        }).catch((err) => {
            console.error('[CompressionUI] Compression error:', err);
            e.preventDefault();
            alert('Error compressing files. Please try again.');
        });
        return;
    }
    
    // No compression needed, just record settings and continue
    const setOrUpdateHidden = (name, value) => {
        let input = uploadForm.querySelector(`input[name="${name}"]`);
        if (!input) {
            input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            uploadForm.appendChild(input);
        }
        input.value = value;
    };
    
    // Sync compression settings to form
    setOrUpdateHidden('compression_quality_used', settings.qualityPercent);
    setOrUpdateHidden('compression_max_dimension_used', settings.maxDimension);
    setOrUpdateHidden('compression_save_original_used', settings.saveOriginal);
    
    console.log('[CompressionUI] Form synced with settings:', settings);
}

export default initializeCompressionUI;
