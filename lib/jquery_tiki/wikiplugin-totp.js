
if(!$.fn.initTOTPScanner){
    jQuery.fn.initTOTPScanner = function (videoId){
        var elementContainer = this;

        elementContainer.find(".send-file").on("click", function(){
            elementContainer.find(".file-chooser").trigger("click");
            elementContainer.find(".file-chooser").on("change", function (e){
                var reader = new FileReader();
                reader.onload = function (event) {
                    const codeReader = new ZXing.BrowserQRCodeReader();
                    codeReader
                        .decodeFromImage(undefined, event.target.result)
                        .then(result => addNewTOTP(result.text, elementContainer.find(".send-file")))
                        .catch(err => {
                            alert("Invalid QRCode image.");
                            console.error(err); // eslint-disable-line no-console
                        });
                };
                reader.readAsDataURL(e.target.files[0]);
            });
        });
        if(window.location.protocol === 'https:'){
            elementContainer.find(".camera").on("click", function(){
                elementContainer.find(".video").show();
                codeRead(elementContainer, videoId);
            });
        }else{
            elementContainer.find(".camera").hide();
        }
    };
    function codeRead(elementContainer, videoId){

        const codeReader = new ZXing.BrowserQRCodeReader();
        codeReader
            .listVideoInputDevices()
            .then(videoInputDevices => {
                codeReader
                    .decodeOnceFromVideoDevice(undefined, videoId)
                    .then(result => addNewTOTP(result.text, elementContainer.find(".video")))
                    .catch(err => console.error(err)); // eslint-disable-line no-console
            })
            .catch(err => console.error(err)); // eslint-disable-line no-console
    }

    function addNewTOTP(url, loadingElm){
        var params = getUrlVars(url);
        var data = {action: "add_totp", ...params};
        loadingElm.tikiModal(" ");
        $.ajax({
            type: "POST",
            dataType: "json",
            data, // serializes the form's elements.
            success: function(data, header, s)
            {
                location.reload();
            },
            error: function(data, header, s)
            {
                alert("Error adding totp");
                location.reload();
            }
        });
    }
    function getUrlVars(url) {
        var vars = {};
        var parts = url.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(m,key,value) {
            vars[key] = decodeURIComponent(value);
        });
        return vars;
    }
}
if(!$.fn.initTOTP){

    $.fn.initTOTP = function(){
        var time = -1;
        var elementContainer = this;
        var doingRequest = false;
        function refreshCode(){
            doingRequest = true;
            elementContainer.find(".code").tikiModal(" ");
            $.ajax({
                type: "POST",
                dataType: "json",
                data: {
                    action: "get_code"
                }, // serializes the form's elements.
                success: function(data, header, s)
                {
                    doingRequest = false;
                    elementContainer.find(".code").tikiModal();
                    time = getTime(data.interval);
                    elementContainer.find(".code").html(data.code);
                }
            });
        }

        setInterval(updateExpire, 1000);

        function getTime(interval)
        {
            var d = new Date();
            return  Math.ceil((((Math.floor(d.getTime()/(interval*1000))+1)*(interval*1000)) - d.getTime()) / 1000);
        }

        function updateExpire(){
            if(time >= 0){
                elementContainer.find(".time").html(time);
            }else if(!doingRequest){
                elementContainer.find(".time").html("--");
                refreshCode();
            }
            time = time-1 ;

        }

        var showQRCode = false;
        elementContainer.find(".twoFactorAuthShow").on("click", function(){
            showQRCode = !showQRCode;
            if(showQRCode){
                elementContainer.find(".twoFactorAuthShow").text("Hide QRCode");
            }else{
                elementContainer.find(".twoFactorAuthShow").text("Show QRCode");
            }
            elementContainer.find(".twoFactorAuthCard").slideToggle(400);
            return false;
        });
    };
}








