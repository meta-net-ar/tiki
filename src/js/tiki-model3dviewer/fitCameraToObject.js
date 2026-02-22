import { Box3, Vector3 } from "three";

export function fitCameraToObject(camera, controls, object, offset = 1.25) {
    const box = new Box3().setFromObject(object);
    const size = box.getSize(new Vector3());
    const center = box.getCenter(new Vector3());

    // Center the object around (0,0,0)
    object.position.sub(center);

    // Update controls target
    if (controls) {
        controls.target.set(0, 0, 0);
        controls.update();
    }

    const maxDim = Math.max(size.x, size.y, size.z);

    if (camera.isPerspectiveCamera) {
        const fov = camera.fov * (Math.PI / 180);
        let distance = maxDim / 2 / Math.tan(fov / 2);
        distance *= offset;

        // Set camera position directly away from center
        camera.position.set(0, 0, distance);
    } else if (camera.isOrthographicCamera) {
        camera.zoom = (1 / maxDim) * 8; // scale to fit roughly
        camera.updateProjectionMatrix();
    }

    camera.lookAt(0, 0, 0);
}
