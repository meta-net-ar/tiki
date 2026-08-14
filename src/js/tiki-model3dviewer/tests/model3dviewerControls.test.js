import { afterEach, beforeEach, describe, expect, test, vi } from "vitest";
import { createControlsAPI } from "../model3dviewerControls.js";

vi.mock("three", () => {
    class MockVector3 {
        constructor(x = 0, y = 0, z = 0) {
            this.x = x;
            this.y = y;
            this.z = z;
        }
        copy(v) {
            this.x = v.x;
            this.y = v.y;
            this.z = v.z;
            return this;
        }
        sub(v) {
            this.x -= v.x;
            this.y -= v.y;
            this.z -= v.z;
            return this;
        }
        multiplyScalar(s) {
            this.x *= s;
            this.y *= s;
            this.z *= s;
            return this;
        }
        add(v) {
            this.x += v.x;
            this.y += v.y;
            this.z += v.z;
            return this;
        }
    }

    return {
        Vector3: MockVector3,
    };
});

function createDOM(uid) {
    document.body.innerHTML = `
        <div id="${uid}">
            <div id="controls_${uid}">
                <button id="zoom-in-btn_${uid}"></button>
                <button id="zoom-out-btn_${uid}"></button>
                <button id="reset-btn_${uid}"></button>
                <button id="rotate-btn_${uid}"></button>
                <button id="play-btn_${uid}"><i class="fa-play"></i></button>
                <button id="fullscreen-btn_${uid}"><i class="fa-expand"></i></button>
            </div>
        </div>
    `;
}

function makeMockCamera(type = "perspective") {
    return {
        isPerspectiveCamera: type === "perspective",
        isOrthographicCamera: type === "orthographic",
        position: { x: 2, y: 2, z: 2, copy: vi.fn().mockReturnThis(), sub: vi.fn().mockReturnThis(), add: vi.fn().mockReturnThis() },
        zoom: 1,
        updateProjectionMatrix: vi.fn(),
    };
}

function makeMockControls() {
    return {
        target: { x: 0, y: 0, z: 0, copy: vi.fn() },
        update: vi.fn(),
    };
}

function makeMockMixer(actionCount = 1) {
    const actions = Array.from({ length: actionCount }, () => ({
        paused: false,
        play: vi.fn(),
    }));
    return {
        _actions: actions,
        update: vi.fn(),
    };
}

describe("model3dviewerControls", () => {
    const uid = "test_viewer_123";

    beforeEach(() => {
        createDOM(uid);
    });

    afterEach(() => {
        document.body.innerHTML = "";
        vi.resetAllMocks();
        vi.clearAllMocks();
    });

    describe("createControlsAPI", () => {
        test("returns undefined when controls container is not found", () => {
            document.body.innerHTML = "";
            const result = createControlsAPI(makeMockCamera(), makeMockControls(), "nonexistent", null, {}, false);
            expect(result).toBeUndefined();
        });

        test("returns an object with isRotationEnabled and updateMixer", () => {
            const api = createControlsAPI(makeMockCamera(), makeMockControls(), uid, null, {}, false);
            expect(api).toBeDefined();
            expect(typeof api.isRotationEnabled).toBe("function");
            expect(typeof api.updateMixer).toBe("function");
        });
    });

    describe("zoom controls", () => {
        test("zoom in adjusts zoom for orthographic camera", () => {
            const camera = makeMockCamera("orthographic");
            camera.zoom = 1.0;
            createControlsAPI(camera, makeMockControls(), uid, null, {}, false);

            document.getElementById(`zoom-in-btn_${uid}`).click();

            expect(camera.zoom).toBeCloseTo(1.1);
            expect(camera.updateProjectionMatrix).toHaveBeenCalled();
        });

        test("zoom out adjusts zoom for orthographic camera", () => {
            const camera = makeMockCamera("orthographic");
            camera.zoom = 1.0;
            createControlsAPI(camera, makeMockControls(), uid, null, {}, false);

            document.getElementById(`zoom-out-btn_${uid}`).click();

            expect(camera.zoom).toBeCloseTo(1 / 1.1);
            expect(camera.updateProjectionMatrix).toHaveBeenCalled();
        });

        test("zoom in calls manualDolly for perspective camera", () => {
            const camera = makeMockCamera("perspective");
            const controls = makeMockControls();
            createControlsAPI(camera, controls, uid, null, {}, false);

            document.getElementById(`zoom-in-btn_${uid}`).click();

            expect(controls.update).toHaveBeenCalled();
        });

        test("zoom out calls manualDolly for perspective camera", () => {
            const camera = makeMockCamera("perspective");
            const controls = makeMockControls();
            createControlsAPI(camera, controls, uid, null, {}, false);

            document.getElementById(`zoom-out-btn_${uid}`).click();

            expect(controls.update).toHaveBeenCalled();
        });
    });

    describe("reset button", () => {
        test("restores camera position and controls target", () => {
            const camera = makeMockCamera("perspective");
            const controls = makeMockControls();
            const initialState = {
                position: { x: 1, y: 1, z: 1 },
                target: { x: 0, y: 0, z: 0 },
            };

            createControlsAPI(camera, controls, uid, null, initialState, false);

            document.getElementById(`reset-btn_${uid}`).click();

            expect(camera.position.copy).toHaveBeenCalledWith(initialState.position);
            expect(controls.target.copy).toHaveBeenCalledWith(initialState.target);
            expect(controls.update).toHaveBeenCalled();
        });

        test("restores zoom for orthographic camera", () => {
            const camera = makeMockCamera("orthographic");
            const controls = makeMockControls();
            const initialState = {
                position: { x: 1, y: 1, z: 1 },
                target: { x: 0, y: 0, z: 0 },
                zoom: 1.5,
            };

            createControlsAPI(camera, controls, uid, null, initialState, false);
            camera.zoom = 3.0;

            document.getElementById(`reset-btn_${uid}`).click();

            expect(camera.zoom).toBe(1.5);
            expect(camera.updateProjectionMatrix).toHaveBeenCalled();
        });
    });

    describe("play/pause button", () => {
        test("toggles animation playback on click", () => {
            const mixer = makeMockMixer(1);
            createControlsAPI(makeMockCamera(), makeMockControls(), uid, mixer, {}, false);

            document.getElementById(`play-btn_${uid}`).click();

            expect(mixer._actions[0].paused).toBe(false);
            expect(mixer._actions[0].play).toHaveBeenCalled();
        });

        test("pauses animation on second click", () => {
            const mixer = makeMockMixer(1);
            createControlsAPI(makeMockCamera(), makeMockControls(), uid, mixer, {}, false);

            const btn = document.getElementById(`play-btn_${uid}`);
            btn.click(); // play
            btn.click(); // pause

            expect(mixer._actions[0].paused).toBe(true);
        });

        test("toggles icon classes between fa-play and fa-pause", () => {
            const mixer = makeMockMixer(1);
            createControlsAPI(makeMockCamera(), makeMockControls(), uid, mixer, {}, false);

            const btn = document.getElementById(`play-btn_${uid}`);
            const icon = btn.querySelector("i");

            btn.click(); // play
            expect(icon.classList.contains("fa-pause")).toBe(true);
            expect(icon.classList.contains("fa-play")).toBe(false);

            btn.click(); // pause
            expect(icon.classList.contains("fa-play")).toBe(true);
            expect(icon.classList.contains("fa-pause")).toBe(false);
        });

        test("does nothing when mixer is null", () => {
            createControlsAPI(makeMockCamera(), makeMockControls(), uid, null, {}, false);

            expect(() => {
                document.getElementById(`play-btn_${uid}`).click();
            }).not.toThrow();
        });
    });

    describe("updateMixer", () => {
        test("updates mixer when playing", () => {
            const mixer = makeMockMixer(1);
            const api = createControlsAPI(makeMockCamera(), makeMockControls(), uid, mixer, {}, true);

            api.updateMixer(0.016);

            expect(mixer.update).toHaveBeenCalledWith(0.016);
        });

        test("does not update mixer when paused", () => {
            const mixer = makeMockMixer(1);
            const api = createControlsAPI(makeMockCamera(), makeMockControls(), uid, mixer, {}, false);

            api.updateMixer(0.016);

            expect(mixer.update).not.toHaveBeenCalled();
        });
    });
});
