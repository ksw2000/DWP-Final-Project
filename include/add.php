<style>
.main-edit-wrapper{
    padding: 10px 10px 50px;
    margin: 0px auto;
    max-width: 800px;
}

input[type='text']{
    background-color: transparent;
    border-width: 0px 0px 2px 0px;
    border-bottom: 2px solid #333;
    box-sizing: border-box;
    color: #333;
    font-size: 1.5em;
    line-height: 1.5em;
    transition: border-bottom 500ms;
    width: 100%;
}

input[type='text']:focus{
    border-bottom: 2px solid #0087ff;
}

select{
    border: 1px solid #333;
    background-color: transparent;
    font-size: 1.2em;
    margin: 10px 0px;
    max-width: 250px;
    width: 100%;
}

.attachment{
    cursor: pointer;
    overflow: hidden;
    position: relative;
}

.attachment input{
    border: 0px;
    bottom: 0px;
    cursor: pointer;
    height: 100px;
    margin: 0px;
    position: absolute;
    right: 0px;
    width: 500px;
}

.loader{
    display: none;
}

#button-area{
    margin: 10px 0px;
    text-align: right;
}

#editor .ck-content, #editor .ck-editor__editable{
    background-color: transparent;
    border-width: 2px;
    color: #333;
    min-height: 200px;
}
</style>

<?php
$preset = array(
    'TITLE' => '',
    'CONTENT' => '',
    'CLASSIFY' => '',
    'ATTACHMENT' => '{}',
);

$edit = FALSE;
if(isset($_GET['edit'])){
    if(!empty($_GET['serial'])){
        $info = Article::get_info_by_serial($_GET['serial']);
        if($info['USER']['ID'] == $_SESSION['login_id']){
            $edit = TRUE;
            $preset['TITLE'] = $info['TITLE'];
            $preset['CONTENT'] = $info['CONTENT'];
            $preset['CLASSIFY'] = $info['CLASSIFY'];
            $preset['ATTACHMENT'] = $info['ATTACHMENT'];
        }
    }
}

// board list
$list = '<select>';

$classify_list = Classify::get_list();
foreach($classify_list as $v) {
    $select = '';
    if($preset['CLASSIFY'] == $v['ID']){
        $select = ' selected';
    }
    $list .= '<option value="'.$v['ID'].'"'.$select.'>'.$v[text_r('NAME_TW', 'NAME_CN', 'NAME_EN')].'</option>';
}
$list .= '</select>';
?>

<div id="wrapper">
    <div class="main-edit-wrapper">
        <div id="title"><input type="text" placeholder="<?php text('標題', '标题', 'Title')?>" value="<?php echo $preset['TITLE']?>"></div>

        <div id="classify">
            <?php echo $list;?>
        </div>
        <div id="editor">
            <div id="mainEditor"><?php echo $preset['CONTENT']?></div>
        </div>
        <div id="button-area">
            <button id="attach-pic" onchange="attach(event, 'img')" class="attachment green"><?php text('圖片', '图片', 'Photo')?>
                <form enctype="multipart/form-data">
                    <input type="file" multiple="" name="img" accept="image/*">
                </form>
            </button>
            <button id="attach-music" onchange="attach(event, 'music')" class="attachment green"><?php text('音樂', '音乐', 'Music')?>
                <form enctype="multipart/form-data">
                    <input type="file" multiple="" name="music" accept="audio/*">
                </form>
            </button>
            <button id="attach-video" onchange="attach(event, 'video')" class="attachment green"><?php text('影片', '视频', 'Video')?>
                <form enctype="multipart/form-data">
                    <input type="file" multiple="" name="video" accept="video/mp4">
                </form>
            </button>
            <button id="attach-normal" onchange="attach(event, 'normal')" class="attachment green"><?php text('檔案', '档案', 'File')?>
                <form enctype="multipart/form-data">
                    <input type="file" multiple="" name="normal" accept="*/*">
                </form>
            </button>
            <?php if($edit):?>
                <button id="publish" class="blue" onclick="publish()"><?php text('編輯完成', '编辑完成', 'Done');?></button>
            <?php else:?>
                <button id="publish" class="blue" onclick="publish()"><?php text('發佈', '发布', 'Post');?></button>
            <?php endif;?>
        </div>
        <div id="attachment-area">
            <div class="loader"></div>
            <div class="load-attachment">
            <?php echo render_attachment_list($preset['ATTACHMENT'], TRUE);?>
            </div>
        </div>
    </div>
</div>

<script src="/assets/js/ckeditor5/build/ckeditor.js"></script>
<script>
function get_serial_by_url(){
    let serial = window.location.href.match(/edit\/(.*?)$/);
    return (serial === null)? -1 : serial[1];
}

var Editor = new (function(){
    this.editor = null;
    this._serial = get_serial_by_url();
    this.getTitle = function(){
        return $("#title input").val();
    }
    this.getData = function(){
        return this.editor.getData();
    }
    this.getClassify = function(){
        return $("#classify select").val();
    }
    this.getSerial = function(){
        return this._serial;
    }
<?php if($edit):?>
    this.attachment = <?php echo $preset['ATTACHMENT']?>;
<?php else:?>
    this.attachment = {
        client_name: [],
        server_name: [],
        path :[],
        type: []
    };
<?php endif;?>

})();

ClassicEditor.create(document.querySelector('#mainEditor'), {
    toolbar: {
        items: [
            'undo',
            'redo',
            'bold',
            'italic',
            'link',
            'imageUpload',
            'bulletedList',
            'numberedList'
        ]
    },
    language: 'zh',
    image: {
        toolbar: [
            'imageTextAlternative',
            'imageStyle:full',
            'imageStyle:side'
        ]
    },
    licenseKey: '',
}).then(editor => {
    Editor.editor = editor;
}).catch( error => {
    console.error( 'Oops, something went wrong!' );
    console.error( 'Please, report the following error on https://github.com/ckeditor/ckeditor5/issues with the build id and the error stack trace:' );
    console.warn( 'Build id: v3g60egyk4w9-8o5dagsr24d3' );
    console.error( error );
});

function publish(){
    $.post('/function/article?action=publish', {
        serial: Editor.getSerial(),
        title: Editor.getTitle(),
        classify: Editor.getClassify(),
        content: Editor.getData(),
        attachment: JSON.stringify(Editor.attachment)
    }, function(data){
        console.log(data);
        if(data['Err']){
            console.log(data['Err']);
            notice(data['Err']);
        }else{
            localStorage.removeItem('editor-buffer');
            window.location = '/article/' + data['Serial'];
        }
    }, 'json');
}

function attach(e, filetype){
    var form = new FormData();
    console.log(e.target.files);
    for(var i=0; i<e.target.files.length; i++){
        if(e.target.files[i].size > 16*1024*1024){
            notice("檔案需小於 16 MB");
            return;
        }
        // php use 'files[]' to get multiple files
        // $_FILE['files']
        form.append('files[]', e.target.files[i]);
    }
    if(!e.target.files.length) return;
    $("#attachment-area div.loader").fadeIn();
    setTimeout(function(){
        $.ajax({
            url: '/function/upload?type='+filetype,
            processData: false,
            contentType: false,
            mimeType: 'multipart/form-data',
            data: form,
            type: 'POST',
            success: function(data){
                if(data['Err']){
                    console.log(data);
                    notice(data['Err']);
                    return;
                }else{
                    data = data['File_list'];
                    for(let i=0; i<e.target.files.length; i++){
                        Editor.attachment.client_name.push(e.target.files[i].name);
                        Editor.attachment.server_name.push(data[i].Filename);
                        Editor.attachment.path.push(data[i].Client_path);
                        Editor.attachment.type.push(filetype);
                    }
                    $.post('/function/load?type=render-attachment-list-editable', {
                        attachment: JSON.stringify(Editor.attachment)
                    }, function(data){
                        console.log(data);
                        if(data['Err']){
                            console.log(data['Err']);
                            notice(data['Err']);
                        }else{
                            $("#attachment-area .load-attachment").html(data['Result']);
                        }
                        $("#attachment-area div.loader").fadeOut();
                    }, 'json');
                }
            },
            dataType: 'json'
        });
    }, 700);
}

function delete_attachment(file_name){
    $("[data-file-name='" + file_name + "']").slideUp('slow');
    try{
        index = Editor.attachment.server_name.indexOf(file_name);
        if (index> -1) {
            Editor.attachment.client_name.splice(index, 1);
            Editor.attachment.server_name.splice(index, 1);
            Editor.attachment.path.splice(index, 1);
            Editor.attachment.type.splice(index, 1);
        }
    }catch(e){
        console.log(e);
    }
}

$(function(){
    if(get_serial_by_url() == -1){
        if(localStorage.getItem('editor-buffer')){
            Editor.editor.setData(localStorage.getItem('editor-buffer'));
        }
        setInterval(function(){
            localStorage.setItem('editor-buffer', Editor.getData());
        }, 5000);
    }
});
</script>
