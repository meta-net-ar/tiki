import { Color, HemisphereLight, DirectionalLight } from "three";

export function getExtensionFromUrl(url) {
    const u = new URL(url, window.location.origin);
    const filename = u.searchParams.get("filename");

    if (filename) {
        const extMatch = filename.match(/\.([a-z0-9]+)$/i);
        if (extMatch) {
            return extMatch[1].toLowerCase();
        }
    }

    // fallback: try to get from pathname (likely fails for tiki)
    const path = u.pathname;
    const match = path.match(/\.([a-z0-9]+)$/i);
    return match ? match[1].toLowerCase() : null;
}

export function sanitizeColor(inputColor, fallback = "#ffffff") {
    if (typeof inputColor !== "string") return fallback;

    let color = inputColor.trim().toLowerCase();

    // Add leading '#' if missing
    if (!color.startsWith("#") && /^[0-9a-f]{3,8}$/i.test(color)) {
        color = `#${color}`;
    }

    // Expand 3-digit hex (#abc → #aabbcc)
    if (/^#[0-9a-f]{3}$/i.test(color)) {
        color = "#" + color[1].repeat(2) + color[2].repeat(2) + color[3].repeat(2);
    }
    // Ensure 6-digit hex or valid CSS color
    if (/^#[0-9a-f]{6,}$/i.test(color)) {
        color = color.slice(0, 7); // Keep # + 6 hex digits
    }

    // Accept only valid 6-digit hex or CSS-named colors
    if (/^#[0-9a-f]{6}$/i.test(color)) {
        return color;
    }

    try {
        new Color(color);
        return color;
    } catch {
        return fallback;
    }
}

export function setupLighting(scene, shadow, lightType) {
    // Remove existing lights before adding new ones
    scene.traverse((obj) => {
        if (obj.isLight) scene.remove(obj);
    });

    const lights = [];

    // Common bottom fill light (soft light from below)
    const bottomLight = new DirectionalLight(0xffffff, 0.25);
    bottomLight.position.set(0, -5, 0);
    bottomLight.target.position.set(0, 0, 0);
    bottomLight.castShadow = shadow;

    switch (lightType) {
        case "":
            // Ambient hemisphere + directional key light (casts shadow)
            lights.push(new HemisphereLight(0xffffff, 0x444444, 1));
            const dirLightDefault = new DirectionalLight(0xffffff, 0.8);
            dirLightDefault.position.set(5, 10, 5);
            dirLightDefault.castShadow = shadow;
            lights.push(dirLightDefault);
            break;

        case "studio":
            // Studio-style 3-point lighting with shadows on key light
            const keyLight = new DirectionalLight(0xffffff, 1.2);
            keyLight.position.set(5, 5, 5);
            keyLight.castShadow = shadow;

            const fillLight = new DirectionalLight(0xffffff, 0.6);
            fillLight.position.set(-5, 2, 5);

            const backLight = new DirectionalLight(0xffffff, 0.4);
            backLight.position.set(0, 5, -5);

            lights.push(keyLight, fillLight, backLight);
            break;

        case "rembrandt":
            // Rembrandt style lighting with rim
            const remLight = new DirectionalLight(0xffffff, 1);
            remLight.position.set(2, 5, 3);
            remLight.castShadow = shadow;

            const rimLight = new DirectionalLight(0xffffff, 0.4);
            rimLight.position.set(-2, 3, -3);

            lights.push(remLight, rimLight);
            break;

        case "portrait":
            // Portrait lighting: key directional + soft hemisphere fill
            const portraitKey = new DirectionalLight(0xffffff, 1);
            portraitKey.position.set(3, 4, 2);

            const portraitFill = new HemisphereLight(0xffffff, 0x222222, 0.6);

            lights.push(portraitKey, portraitFill);
            break;

        case "soft":
            // Soft ambient lighting only
            const softLight = new HemisphereLight(0xffffff, 0x888888, 0.8);
            lights.push(softLight);
            break;

        default:
            // Fallback ambient
            lights.push(new HemisphereLight(0xffffff, 0x444444, 1));
    }

    // Add the bottom fill light to all presets
    lights.push(bottomLight);

    // Add bottomLight target for correct orientation
    scene.add(bottomLight.target);

    // Add all lights to scene
    lights.forEach((l) => scene.add(l));
}
