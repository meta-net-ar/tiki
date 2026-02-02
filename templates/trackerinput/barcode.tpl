<div class="qr-scanner-wrapper mt-2">
    <input type="text" class="form-control" value='{$data.value}' placeholder="{$field.name}" id="{$field.ins_id}-plain-text">
    <input type="hidden" class="form-control" id="{$field.ins_id}" name="{$field.ins_id}" value='{$field.value|escape:"htmlall"}'>
    <div id="{$field.ins_id}-scanner-container">
        <div id="qr-reader-{$field.ins_id}" class="mb-2"></div>
        <label for="camera-select-{$field.ins_id}" class="mb-2">{tr}Or use camera to scan{/tr}</label>
        <select id="camera-select-{$field.ins_id}" class="form-select"></select>
        <div class="mt-2">
            <button type="button" class="btn btn-primary" id="{$field.ins_id}-start-scanner">
                {icon name="qrcode"} {tr}Start Scanner{/tr}
            </button>
            <button type="button" class="btn btn-secondary d-none" id="{$field.ins_id}-stop-scanner">
                {icon name="stop"} {tr}Stop Scanner{/tr}
            </button>
        </div>
    </div>
    <div id="qr-status-{$field.ins_id}" class="form-text"></div>
</div>

<script type="module">
    import { BarcodeScanner } from "@html5-qrcode/html5-qrcode";
    // Create scanner instance for this field
    const BarcodeScanner_{$field.ins_id} = new BarcodeScanner({
        fieldId: "{$field.ins_id}",
        scannerWidth: {$field.options.scannerWidth|default:250},
        scannerHeight: {$field.options.scannerHeight|default:250},
        outputFormat: "{$data.format|default:$field.options_map.output_format}",
    });
    
    // Initialize scanner
    BarcodeScanner_{$field.ins_id}.init();
    $("#{$field.ins_id}-start-scanner").on("click", function() {
        BarcodeScanner_{$field.ins_id}.startScanner();
        $(this).hide();
        $("#{$field.ins_id}-stop-scanner").show();
    });
    $("#{$field.ins_id}-stop-scanner").on("click", function() {
        BarcodeScanner_{$field.ins_id}.stopScanner();
        $(this).hide();
        $("#{$field.ins_id}-start-scanner").show();
    });
</script>


<style>
    #qr-reader- {
        $field.ins_id
    }

        {
        max-width: 100%;
        display: none;
    }

    #qr-reader- {
        $field.ins_id
    }

    video {
        max-width: 100%;
    }
</style>
