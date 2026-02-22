<div id="model3dviewer_container_{$params.uniqueId}" class="model3dviewer-container border border-secondary rounded-2 p-2">
    <div class="model3dviewer-viewer position-relative d-flex align-items-center justify-content-center flex-column" id="{$params.uniqueId}"
        style="width: {$params.width}; height: {$params.height};">
        <div class="model3dviewer-loading text-center">
            <div class="spinner-border text-primary" role="status" aria-hidden="true"></div>
            <span class="model3dviewer-loading-text ms-2">{tra('Loading...')}</span>
        </div>
        <div class="model3dviewer-error alert alert-danger mt-3 d-none" role="alert">
             <i class="bi bi-exclamation-triangle-fill me-2"></i>
             <span class="model3dviewer-error-text">{tra('Error loading model')}</span>
        </div>

        {if $params.controls eq "y"}
            <div class="model3dviewer-controls mb-3 position-absolute bottom-0 start-50 translate-middle-x d-flex flex-row gap-2" id="controls_{$params.uniqueId}">
                <button type="button" id="zoom-in-btn_{$params.uniqueId}"
                    class="btn btn-outline-secondary model3dviewer-zoom-in" title="{tra('Zoom In')}">
                    <i class="fas fa-plus"></i>
                </button>
                <button type="button" id="zoom-out-btn_{$params.uniqueId}"
                    class="btn btn-outline-secondary model3dviewer-zoom-out" title="{tra('Zoom Out')}">
                    <i class="fas fa-minus"></i>
                </button>
                <button type="button" id="reset-btn_{$params.uniqueId}"
                    class="btn btn-outline-secondary model3dviewer-reset" title="{tra('Reset View')}">
                    <i class="fas fa-undo"></i>
                </button>
                <button type="button" id="play-btn_{$params.uniqueId}"
                    class="btn btn-outline-secondary model3dviewer-play" title="{tra('Start/Stop')}">
                    <i class="fas fa-play"></i>
                </button>
                <button type="button" id="fullscreen-btn_{$params.uniqueId}"
                    class="btn btn-outline-secondary model3dviewer-fullscreen" title="{tra('Fullscreen')}">
                    <i class="fas fa-expand"></i>
                </button>
            </div>
        {/if}
    </div>
    <div class="model3dviewer-info mt-3">
        <span class="model3dviewer-info-text text-muted">
            <span class="model3dviewer-label">{tra('Model Information')}:</span>
            <span class="text-primary"> {$fileInfo.basename}</span>
        </span>
        <div class="model3dviewer-additional-info">
            {if !empty($params.desc)}
                <p class="text-muted">
                    <i class="fas fa-arrow-right me-1"></i>{$params.desc}
                </p>
            {/if}
        </div>
    </div>
</div>
