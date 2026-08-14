
<div id="totp_{$id}">
    <input hidden type="text" value="get_code" name="action">
    <div class="panel panel-primary">
        <div class="panel-heading"> <h3 class="panel-title">{tr}Add new application{/tr}</h3> </div>
        <div class="panel-body">

            <span>{tr}Use:{/tr}</span>
            <div class="btn-group" >
                <button type="button" class=" camera btn btn-primary">{tr}Camera{/tr}</button>
                <button type="button" class="send-file btn btn-primary">{tr}Send QRCode File{/tr}</button>
            </div>
            <input type="file" class="file-chooser" accept="image/*" style="display:none;" />
            <br>
            <img class="qrcode" style="display: none" src="" alt="">
            <video
                    id="video_{$id}"
                    class="video"
                    width="300"
                    height="200"
                    style="border: 1px solid gray; display: none"
            ></video>
        </div>

    </div>
</div>

{jq}
    $("#totp_{{$id}}").initTOTPScanner("video_{{$id}}");
{/jq}
