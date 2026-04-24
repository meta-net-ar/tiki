import {
    Clock,
    Scene,
    PerspectiveCamera,
    LoopRepeat,
    WebGLRenderer,
    MeshStandardMaterial,
    Mesh,
    AnimationMixer,
    LoopOnce,
    PCFSoftShadowMap,
    PlaneGeometry,
    OrthographicCamera,
    Color,
    ShadowMaterial,
} from "three";
import { GLTFLoader } from "three/examples/jsm/loaders/GLTFLoader.js";
import { STLLoader } from "three/examples/jsm/loaders/STLLoader.js";
import { OBJLoader } from "three/examples/jsm/loaders/OBJLoader.js";
import { FBXLoader } from "three/examples/jsm/loaders/FBXLoader.js";
import { ColladaLoader } from "three/examples/jsm/loaders/ColladaLoader.js";
import { TDSLoader } from "three/examples/jsm/loaders/TDSLoader.js";
import { PLYLoader } from "three/examples/jsm/loaders/PLYLoader.js";
import { OrbitControls } from "three/examples/jsm/controls/OrbitControls.js";
import { getExtensionFromUrl, sanitizeColor, setupLighting } from "./model3dviewer_helper.js";
import { fitCameraToObject } from "./fitCameraToObject.js";
import { createControlsAPI } from "./model3dviewerControls.js";

export function initViewer(containerId, options) {
    const {
        modelUrl,
        autoRotate,
        autoplay,
        loop,
        controls: controlsEnabled,
        camera: cameraStr,
        cameraType, // perspective or orthographic
        backgroundColor,
        shadow,
        lightType,
        exposure = 1.0,
        uid,
    } = options || {};

    const container = document.getElementById(containerId);
    if (!container || !modelUrl) return;

    let mixer = null;
    let model = null;
    let controlsAPI = null;

    const loaderEl = container.querySelector(".model3dviewer-loading");
    const errorEl = container.querySelector(".model3dviewer-error");

    const width = container.clientWidth;
    const height = container.clientHeight;
    const aspect = width / height;

    const clock = new Clock();
    const ext = getExtensionFromUrl(modelUrl);

    // === Set up scene ===
    const scene = new Scene();
    scene.background = new Color(sanitizeColor(backgroundColor));

    const shadowPlane = new Mesh(new PlaneGeometry(50, 50), new ShadowMaterial({ opacity: 0.25 }));
    shadowPlane.rotation.x = -Math.PI / 2;
    shadowPlane.position.y = 0; // or model's lowest Y
    shadowPlane.receiveShadow = true;
    scene.add(shadowPlane);

    // === Apply lighting preset ===
    setupLighting(scene, shadow, lightType);

    // === Camera Selection ===
    let camera;
    if (cameraType === "orthographic") {
        const frustumSize = 5;
        camera = new OrthographicCamera(-frustumSize * aspect, frustumSize * aspect, frustumSize, -frustumSize, 0.1, 1000);
        camera.zoom = 1.5;
        camera.updateProjectionMatrix();
    } else {
        camera = new PerspectiveCamera(45, aspect, 0.1, 1000);
        camera.position.set(2, 2, 2);
    }

    // === Set renderer ===
    const renderer = new WebGLRenderer({ antialias: true });
    renderer.setSize(width, height);
    renderer.shadowMap.enabled = true;
    renderer.shadowMap.type = PCFSoftShadowMap;

    container.appendChild(renderer.domElement);

    // === Controls ===
    const controls = new OrbitControls(camera, renderer.domElement);

    controls.enableDamping = true; // for smooth motion
    controls.dampingFactor = 0.05;
    controls.enableZoom = true;
    controls.enablePan = false;
    controls.rotateSpeed = 1.0;
    controls.zoomSpeed = 1.2;
    controls.autoRotate = autoRotate;
    controls.autoRotateSpeed = 2.0; // adjust speed as needed
    controls.update();

    function showPlayPauseButton(show) {
        const playPauseBtn = document.getElementById(`play-btn_${uid}`);
        if (playPauseBtn) {
            playPauseBtn.style.display = show ? "inline-block" : "none";
        }
    }

    function canSupportsAnimation() {
        return ["glb", "gltf", "fbx"].includes(ext.toLowerCase());
    }

    // === handle Errors ===
    const handleError = (error) => {
        if (loaderEl) loaderEl.style.display = "none";
        const canvasEl = container.querySelector("canvas");

        if (canvasEl) {
            canvasEl.style.display = "none";
        }
        if (errorEl) {
            errorEl.classList.remove("d-none");

            const errorTextEl = errorEl.querySelector(".model3dviewer-error-text");
            if (errorTextEl) {
                let message = error?.message || error?.toString?.() || "Unknown error";

                // remove redundant "Error: " prefix
                message = message.replace(/^Error:\s*/, "");

                errorTextEl.textContent = "Error loading model: " + message;
            }
        }
    };

    const onLoad = (loadedModel) => {
        model = loadedModel.scene;
        scene.add(model);

        if (loaderEl) loaderEl.style.display = "none";
        fitCameraToObject(camera, controls, model);
        const initialState = {
            position: camera.position.clone(),
            target: controls.target.clone(),
            zoom: camera.zoom,
        };

        if (canSupportsAnimation() && loadedModel.animations?.length > 0 && controlsEnabled) {
            mixer = new AnimationMixer(model);

            loadedModel.animations.forEach((clip) => {
                const action = mixer.clipAction(clip);
                action.setLoop(loop ? LoopRepeat : LoopOnce);

                if (autoplay) {
                    action.play(); // Start animation
                } else {
                    action.paused = true; // Paused, waiting for user to click play
                }
            });
            showPlayPauseButton(true);
        } else {
            showPlayPauseButton(false);
        }

        if (controlsEnabled) {
            controlsAPI = createControlsAPI(camera, controls, uid, mixer, initialState, autoplay);
        }
    };

    switch (ext) {
        case "glb":
        case "gltf":
            new GLTFLoader().load(modelUrl, (gltf) => onLoad(gltf), undefined, handleError);
            break;

        case "stl":
            new STLLoader().load(
                modelUrl,
                (geometry) => {
                    const material = new MeshStandardMaterial({ color: 0x888888 });
                    const mesh = new Mesh(geometry, material);
                    onLoad({ scene: mesh });
                },
                undefined,
                handleError
            );
            break;

        case "obj":
            new OBJLoader().load(modelUrl, (obj) => onLoad({ scene: obj }), undefined, handleError);
            break;

        case "fbx":
            new FBXLoader().load(modelUrl, (fbx) => onLoad({ scene: fbx }), undefined, handleError);
            break;
        case "dae":
            new ColladaLoader().load(
                modelUrl,
                (collada) => onLoad({ scene: collada.scene, animations: collada.animations || [] }),
                undefined,
                handleError
            );
            break;

        case "3ds":
            new TDSLoader().load(modelUrl, (object) => onLoad({ scene: object }), undefined, handleError);
            break;

        case "ply":
            new PLYLoader().load(
                modelUrl,
                (geometry) => {
                    const material = new MeshStandardMaterial({ color: 0xaaaaaa });
                    const mesh = new Mesh(geometry, material);
                    onLoad({ scene: mesh });
                },
                undefined,
                handleError
            );
            break;
        default:
            handleError(new Error("Unsupported file type: " + ext));
    }

    renderer.setAnimationLoop(function (time) {
        // if (model && controlsAPI && controlsAPI.isRotationEnabled()) {
        //     model.rotation.y = time / 2000;
        // }
        const delta = clock.getDelta();
        if (mixer) mixer.update(delta);

        controls.update();
        renderer.render(scene, camera);
    });
}
