export default function (fileBlob) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.readAsDataURL(fileBlob);
        reader.onload = () => resolve(reader.result);
        reader.onerror = (error) => reject(error);
    });
}
