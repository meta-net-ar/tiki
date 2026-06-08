import { Html5Qrcode, Html5QrcodeSupportedFormats } from "html5-qrcode";

// Barcode scanner class
class BarcodeScanner {
    // Shared state across instances

    constructor(config) {
        const { fieldId, scannerWidth = 250, scannerHeight = 250, outputFormat } = config;

        this.fieldId = fieldId;
        this.scannerWidth = scannerWidth;
        this.scannerHeight = scannerHeight;
        this.outputFormat = outputFormat;

        this.selectedCameraId = null;
        this.qrScanner = null;
    }

    async init() {
        // Setup camera selection
        await this.setupCameras();

        // Setup event listeners
        this.setupEventListeners();
    }

    async setupCameras() {
        try {
            // First check/request camera permissions
            await navigator.mediaDevices.getUserMedia({ video: true });

            // If we get here, permissions are granted - get camera list
            const devices = await navigator.mediaDevices.enumerateDevices();
            const cameras = devices.filter((device) => device.kind === "videoinput");

            if (cameras.length === 0) {
                throw new Error("No cameras found. Please ensure at least one camera is connected.");
            }

            // Update camera selection dropdown
            const cameraSelect = document.getElementById(`camera-select-${this.fieldId}`);
            $(cameraSelect).html("");

            cameras.forEach((camera) => {
                const option = $("<option></option>");
                option.val(camera.deviceId);
                option.text(camera.label || `Camera ${cameras.indexOf(camera) + 1}`);
                $(cameraSelect).append(option);
            });

            // Set initial camera selection
            this.selectedCameraId = cameras[0].deviceId;
            $(`#camera-select-${this.fieldId}`).change(() => {
                this.selectedCameraId = $(`#camera-select-${this.fieldId}`).find(":selected").val();
            });
        } catch (err) {
            console.error(err); // eslint-disable-line no-console
            if (err.name === "NotAllowedError") {
                this.showStatus("error", "Site does not have camera permissions. Please ensure camera permissions are granted.");
            } else {
                this.showStatus("error", err.message);
            }
        }
    }

    setupEventListeners() {
        const fieldId = this.fieldId;
        const outputFormat = this.outputFormat;

        $(`#${fieldId}-plain-text`).on("change", function () {
            const value = JSON.stringify({
                format: outputFormat,
                value: $(this).val(),
            });
            $(`#${fieldId}`).val(value);
        });
    }

    showStatus(type, message) {
        const alertClass = type === "success" ? "alert-success" : type === "warning" ? "alert-warning" : "alert-danger";
        $(`#qr-status-${this.fieldId}`).html(`<div class="alert ${alertClass}">${message}</div>`);
    }

    startScanner() {
        var html5QrCode = new Html5Qrcode(`qr-reader-${this.fieldId}`);
        this.qrScanner = html5QrCode;

        var scannerConfig = {
            fps: 10,
            qrbox: {
                width: this.scannerWidth,
                height: this.scannerHeight,
            },
            showTorchButtonIfSupported: true,
            useBarcodeDetectorIfSupported: false,
        };

        if (this.supportedFormat !== "Auto") {
            scannerConfig.formatsToSupport = [Html5QrcodeSupportedFormats[this.supportedFormat]];
        }

        $(`#qr-reader-${this.fieldId}`).show();

        const outputFormat = this.outputFormat;

        const self = this; // Capture 'this' before the callback
        html5QrCode
            .start(
                this.selectedCameraId,
                scannerConfig,
                (decodedText, decodedResult) => {
                    // const format = decodedResult.result.format.formatName;
                    const value = JSON.stringify({
                        format: outputFormat,
                        value: decodedText,
                    });
                    document.getElementById(self.fieldId).value = value;
                    let message = tr("Bar Code scanned successfully!");
                    if (decodedText.length !== 0) {
                        message += " " + tr("Data: ") + decodedText;
                    }
                    self.showStatus("success", message);
                    $(`#${self.fieldId}-plain-text`).val(decodedText);
                    $(`#${self.fieldId}-stop-scanner`).trigger("click");
                },
                (errorMessage) => {
                    // Silent error handling
                }
            )
            .catch((err) => {
                this.showStatus("error", tr("Error starting BarCode scanner. Please ensure camera permissions are granted."));
            });
    }

    stopScanner() {
        if (this.qrScanner) {
            this.qrScanner.stop().then(() => {
                $(`#qr-reader-${this.fieldId}`).hide();
            });
        }
    }
}

export { BarcodeScanner };
