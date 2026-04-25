import { afterEach, describe, expect, test, vi } from "vitest";
import { fitCameraToObject } from "../fitCameraToObject.js";

vi.mock("three", () => {
    let storedSize = { x: 2, y: 2, z: 2 };

    class MockBox3 {
        setFromObject(obj) {
            if (obj._size) storedSize = obj._size;
            return this;
        }
        getSize() {
            return storedSize;
        }
        getCenter() {
            return { x: 1, y: 1, z: 1 };
        }
    }

    class MockVector3 {
        constructor(x = 0, y = 0, z = 0) {
            this.x = x;
            this.y = y;
            this.z = z;
        }
    }

    return {
        Box3: MockBox3,
        Vector3: MockVector3,
    };
});

function makeMockCamera(type = "perspective") {
    return {
        isPerspectiveCamera: type === "perspective",
        isOrthographicCamera: type === "orthographic",
        fov: 45,
        position: { set: vi.fn(), copy: vi.fn() },
        lookAt: vi.fn(),
        zoom: 1,
        updateProjectionMatrix: vi.fn(),
    };
}

function makeMockControls() {
    return {
        target: { set: vi.fn(), copy: vi.fn() },
        update: vi.fn(),
    };
}

function makeMockObject(sizeX = 2, sizeY = 2, sizeZ = 2) {
    return {
        position: {
            sub: vi.fn(),
        },
        _size: { x: sizeX, y: sizeY, z: sizeZ },
    };
}

describe("fitCameraToObject", () => {
    afterEach(() => {
        vi.resetAllMocks();
        vi.clearAllMocks();
    });

    test("sets camera position for perspective camera", () => {
        const camera = makeMockCamera("perspective");
        const controls = makeMockControls();
        const object = makeMockObject();

        fitCameraToObject(camera, controls, object);

        expect(camera.position.set).toHaveBeenCalled();
        expect(camera.lookAt).toHaveBeenCalledWith(0, 0, 0);
        expect(controls.target.set).toHaveBeenCalledWith(0, 0, 0);
        expect(controls.update).toHaveBeenCalled();
    });

    test("adjusts zoom for orthographic camera", () => {
        const camera = makeMockCamera("orthographic");
        const controls = makeMockControls();
        const object = makeMockObject();

        fitCameraToObject(camera, controls, object);

        expect(camera.updateProjectionMatrix).toHaveBeenCalled();
        expect(camera.lookAt).toHaveBeenCalledWith(0, 0, 0);
    });

    test("centers the object by subtracting center", () => {
        const camera = makeMockCamera("perspective");
        const controls = makeMockControls();
        const object = makeMockObject();

        fitCameraToObject(camera, controls, object);

        expect(object.position.sub).toHaveBeenCalled();
    });

    test("works without controls", () => {
        const camera = makeMockCamera("perspective");
        const object = makeMockObject();

        expect(() => fitCameraToObject(camera, null, object)).not.toThrow();
        expect(camera.position.set).toHaveBeenCalled();
    });
});
