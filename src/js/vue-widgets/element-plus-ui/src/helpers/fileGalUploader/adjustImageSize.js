export default function (imageData, maxWidth, maxHeight, imageType) {
    return new Promise((resolve, reject) => {
        const image = new Image();
        image.onload = function () {
            const canvas = document.createElement("canvas");
            const ctx = canvas.getContext("2d");
            let width = image.width;
            let height = image.height;

            if (width > maxWidth || height > maxHeight) {
                const ratio = Math.min(maxWidth / width, maxHeight / height);
                width *= ratio;
                height *= ratio;
                canvas.width = width;
                canvas.height = height;
                ctx.drawImage(image, 0, 0, width, height);
                resolve(canvas.toDataURL(imageType));
            } else {
                resolve(imageData);
            }
        };
        image.onerror = function (error) {
            reject(error);
        };
        image.src = imageData;
    });
}
