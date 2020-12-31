<!--圖片上傳預覽區-->
<div id="fixed_container">
    <div class="container" id="image_cropper">
        <p style="margin-top:10px;">
            <button class="blue" id="button-crop">剪裁</button>
            <button class="blue" onclick="close_crop()"><?php text('關閉', '关闭')?></button>
        </p>
        <img id="upload-image" src="" style="width:100%; height:auto;">
    </div>
    <div id="crop-result"></div>
    <div style="height:40px; width:100%;"></div>
</div>
<script>
$("#bio-edit #name").change(function(){
    if($("#bio-edit #name").val() === ''){
        $("#bio-edit #name").addClass('err');
    }else{
        $("#bio-edit #name").removeClass('err');
    }
});

function toggle_bio_edit_area(){
    $("#bio-edit").slideToggle('fast');
    $("#bio-list").slideToggle('fast');
    $("#bio-content").slideToggle('fast');
    $("#change-profile").fadeToggle('slow');

    if($("#bio-edit-tool #i-edit").text() === "edit"){
        $("#bio-edit-tool #i-edit").text('close');
        if(window.editor){
            // nothing to do
        }else{
            BalloonEditor.create(document.querySelector('#bio-content-editor'),{
                toolbar: {
                    items: [
                        'undo',
                        'redo',
                        'bold',
                        'italic',
                        'link'
                    ]
                },
                language: 'zh',
                licenseKey: ''
            }).then(editor => {
                window.editor = editor;
            }).catch(error => {
                console.error( 'Oops, something went wrong!' );
                console.error( 'Please, report the following error on https://github.com/ckeditor/ckeditor5/issues with the build id and the error stack trace:' );
                console.warn( 'Build id: oazkkbvl856-y87g8s9179pi' );
                console.error( error );
            });
        }
    }else{
        $("#bio-edit-tool #i-edit").text('edit');
    }
}

function edit_bio(){
    var more_info = {
        'BIRTHDAY' : $("#bio-edit #birthday").val(),
        'HOBBY'    : $("#bio-edit #hobby").val(),
        'FROM'     : $("#bio-edit #from").val(),
        'LINK'     : $("#bio-edit #link").val(),
        'BIO'      : window.editor.getData()
    };

    $.post('/function/user-setting?type=edit_bio', {
        'name'      : $("#bio-edit #name").val(),
        'more_info' : JSON.stringify(more_info)
    }, function(data){
        if(data['Err']){
            console.log(data);
            if(data['Err'] == 'name con be none-empty'){
                $("#bio-edit #name").css("border", "1px solid #d20202");
                $("#bio-edit #name").attr("placeholder", "<?php text('不可為空', '不可为空')?>");
            }
        }else{
            $("#bio-container").fadeOut('slow', function() {
                $("#bio-container").html(data['render']);
                $("#bio-container").fadeIn('slow');
            });
        }
    }, 'json');
}
</script>
<script src="/assets/js/cropper.min.js"></script>
<script>
function transfer_base64_to_form_data(param_base64Data){
    var blob = (function(base64Data){
        var byteString;
        if (base64Data.split(',')[0].indexOf('base64') >= 0)
            byteString = atob(base64Data.split(',')[1]);
        else
            byteString = unescape(base64Data.split(',')[1]);
        var mimeString = base64Data.split(',')[0].split(':')[1].split(';')[0];
        var ia = new Uint8Array(byteString.length);
        for (var i = 0; i < byteString.length; i++) {
            ia[i] = byteString.charCodeAt(i);
        }

        return new Blob([ia], {type:mimeString});
    })(param_base64Data);
    var fd = new FormData(document.forms[0]);
    //arg[0] 等效於 form 中的 name, arg[2] 則是指定檔名給base64
    fd.append('profile', blob);
    return fd;
}

function cropper_init() {
    var image = document.getElementById('upload-image');
    var button = document.getElementById('button-crop');
    var result = document.getElementById('crop-result');
    var croppable = false;
    var cropper = new Cropper(image, {
        aspectRatio: 1,
        viewMode: 3,
        dragMode: 'move',
        zoomable: false,
        movable: false,
        autoCrop: true,
        ready: function () {
            croppable = true;
        }
    });

    button.onclick = function () {
        var croppedCanvas;
        var roundedCanvas;
        var roundedImage;

        if (!croppable) {
            return;
        }

        // Crop
        croppedCanvas = cropper.getCroppedCanvas();

        // Round
        roundedCanvas = (function (sourceCanvas) {
            var canvas = document.createElement('canvas');
            var context = canvas.getContext('2d');
            var width = sourceCanvas.width;
            var height = sourceCanvas.height;

            canvas.width = width;
            canvas.height = height;
            context.imageSmoothingEnabled = true;
            context.drawImage(sourceCanvas, 0, 0, width, height);
            context.globalCompositeOperation = 'destination-in';
            context.beginPath();
            //context.arc(width / 2, height / 2, Math.min(width, height) / 2, 0, 2 * Math.PI, true);
            context.fill();
            return canvas;
        })(croppedCanvas);

        // Show
        roundedImage = document.createElement('img');
        roundedImage.src = roundedCanvas.toDataURL();
        roundedImage.id = 'crop-result-img';
        roundedImage.style.display = 'none';
        result.innerHTML = '';
        result.appendChild(roundedImage);
        $("#fixed_container").slideUp();

        profile_upload();
    };
}

var image_cropper_ori = $("#image_cropper").html();
function upload_profile_btn_onchange(){
    $("#image_cropper").html(image_cropper_ori);
    var file = $('#profile-upload')[0].files[0];
    var reader = new FileReader;
    reader.onload = function(e) {
        $('#upload-image').attr('src', e.target.result);
        cropper_init();
    };
    reader.readAsDataURL(file);
    $("#fixed_container").slideDown();
    $("#profile-upload").val("");   //reset file-input
}

function close_crop(){
    $("#fixed_container").slideUp();
}

function profile_upload(){
    var img_base64 = $("#crop-result-img").attr("src");
    $.ajax({
        async: true,
        cache: false,
        contentType: false,
        mimeType: 'multipart/form-data',
        processData: false,
        dataType: 'json',
        data: transfer_base64_to_form_data(img_base64),
        type: "POST",
        url: "/function/upload?type=profile",
        success: function(data){
            console.log(data);
            $("#profile-photo").attr("src", data['File'][0].Client_path);
        },
        beforeSend: function(){
            $("#profile-photo").attr("src", "/assets/img/loading.gif");
        },
        error: function(xhr, ajaxOptions, thrownError){
            console.log(xhr.status, thrownError);
            notice("圖片上傳發生錯誤");
        }
    });
}
</script>
