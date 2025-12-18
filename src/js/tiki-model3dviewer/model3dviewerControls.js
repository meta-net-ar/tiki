import { Vector3 } from "three";

export function createControlsAPI(camera, controls, uid, mixer, initialState = {}, isPlaying) {
    const controlsContainer = document.getElementById(`controls_${uid}`);
    if (!controlsContainer) {
        return;
    }

    const zoomInBtn = controlsContainer.querySelector(`#zoom-in-btn_${uid}`);
    const zoomOutBtn = controlsContainer.querySelector(`#zoom-out-btn_${uid}`);
    const resetBtn = controlsContainer.querySelector(`#reset-btn_${uid}`);
    const rotateBtn = controlsContainer.querySelector(`#rotate-btn_${uid}`);
    const playPauseBtn = controlsContainer.querySelector(`#play-btn_${uid}`);
    const fullScreenBtn = controlsContainer.querySelector(`#fullscreen-btn_${uid}`);

    function manualDolly(camera, controls, scaleFactor) {
        const offset = new Vector3();
        offset.copy(camera.position).sub(controls.target);
        offset.multiplyScalar(scaleFactor);
        camera.position.copy(controls.target).add(offset);
        controls.update();
    }

    zoomInBtn?.addEventListener("click", () => {
        if (camera.isOrthographicCamera) {
            camera.zoom *= 1.1;
            camera.updateProjectionMatrix();
        } else if (camera.isPerspectiveCamera) {
            manualDolly(camera, controls, 0.9);
        }
    });

    zoomOutBtn?.addEventListener("click", () => {
        if (camera.isOrthographicCamera) {
            camera.zoom /= 1.1;
            camera.updateProjectionMatrix();
        } else if (camera.isPerspectiveCamera) {
            manualDolly(camera, controls, 1.1);
        }
    });

    resetBtn?.addEventListener("click", () => {
        if (initialState.position) {
            camera.position.copy(initialState.position);
        }

        if (initialState.target) {
            controls.target.copy(initialState.target);
        }

        if (camera.isOrthographicCamera && initialState.zoom !== undefined) {
            camera.zoom = initialState.zoom;
            camera.updateProjectionMatrix();
        }

        controls.update();
    });

    rotateBtn?.addEventListener("click", () => {
        rotateEnabled = !rotateEnabled;
    });

    playPauseBtn?.addEventListener("click", () => {
        if (!mixer) return;

        isPlaying = !isPlaying;

        mixer._actions?.forEach((action) => {
            if (isPlaying) {
                action.paused = false;
                action.play();
            } else {
                action.paused = true;
            }
        });

        const icon = playPauseBtn.querySelector("i");
        if (icon) {
            icon.classList.toggle("fa-play", !isPlaying);
            icon.classList.toggle("fa-pause", isPlaying);
        }
    });

    fullScreenBtn?.addEventListener("click", () => {
        const viewerDiv = document.getElementById(uid);
        if (!document.fullscreenElement) {
            viewerDiv.requestFullscreen?.() || viewerDiv.webkitRequestFullscreen?.() || viewerDiv.msRequestFullscreen?.();
            fullScreenBtn.querySelector("i").classList.replace("fa-expand", "fa-compress");
        } else {
            document.exitFullscreen?.() || document.webkitExitFullscreen?.() || document.msExitFullscreen?.();
            fullScreenBtn.querySelector("i").classList.replace("fa-compress", "fa-expand");
        }
    });

    // Reset icon when user exits fullscreen via ESC
    document.addEventListener("fullscreenchange", () => {
        if (!document.fullscreenElement) {
            fullScreenBtn.querySelector("i").classList.replace("fa-compress", "fa-expand");
        }
    });

    return {
        isRotationEnabled() {
            return rotateEnabled;
        },
        updateMixer(delta) {
            if (mixer && isPlaying) {
                mixer.update(delta);
            }
        },
    };
}
