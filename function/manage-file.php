<div class="setting-area">
    <div class="setting-area-title"><?php text('檔案管理', '文件管理','File Manager')?></div>
    <div class="col">
        <div style="display: flex; align-items: stretch;">
            <input class="normal" id="find-file" type="text" placeholder="<?php text('以檔名搜尋', '以档名搜寻','Search by Files');?>">
            <button style="min-width: 50px; border-radius: 0px; padding: 3px; height: auto;" class="blue" onclick="find_file()"><?php text('搜尋','搜寻','Search')?></button>
        </div>
    </div>
    <div class="col" id="file-list-loading-area" style="max-width: 500px;"></div>
</div>
<script>
function load_file_list_component(param, callback){
    $.get('/function/load?type=render-file-list', param, function(data){
        if(data['Err']){
            console.log(data['Err']);
            notice(data['Err']);
            return;
        }else{
            window.next_file_list = data['Next_from'];
            callback(data);
        }
    }, 'json');
}

function load_file_list(){
    $("#file-list-loading-area").html('<div class="loader"></div>');
    load_file_list_component({}, function(data){
        $("#file-list-loading-area").fadeOut('fast',function(){
            $("#file-list-loading-area").html(data['Render_result']);
            $("#file-list-loading-area").fadeIn('fast');
        });
    });
}

function find_file(){
    load_file_list_component({
        'q' : $("#find-file").val()
    }, function(data){
        $("#file-list-loading-area").fadeOut('fast',function(){
            $("#file-list-loading-area").html(data['Render_result']);
            $("#file-list-loading-area").fadeIn('fast');
        });
    });
}

$(function(){
    load_file_list();
    $("#find-file").bind('keyup', function(){
        find_file();
    });
});

function open_file_list_setting(fp){
    if($(".file-list-item[data-file='" + fp + "'] .expand-icon").text() === 'expand_more'){
        $(".file-list-item[data-file='" + fp + "'] .expand-icon").text('expand_less');
        $(".file-list-item[data-file='" + fp + "'] .file-list-item-body").slideDown();
    }else{
        $(".file-list-item[data-file='" + fp + "'] .expand-icon").text('expand_more');
        $(".file-list-item[data-file='" + fp + "'] .file-list-item-body").slideUp();
    }
}

function delete_file(f){
    $(".file-list-item[data-file='" + f + "'] .delete-button").fadeOut('fast', function(){
        $(".file-list-item[data-file='" + f + "'] .confirm-delete-button").fadeIn('fast');
        setTimeout(function(){
            $(".file-list-item[data-file='" + f + "'] .confirm-delete-button").fadeOut('fast', function(){
                $(".file-list-item[data-file='" + f + "'] .delete-button").fadeIn('fast');
            });
        }, 5000);
    });
}

function real_delete_file(sn){
    $.post('/function/setting?type=delete_file', {
        'server_name': sn
    }, function(data){
        console.log(data);
        if(data['Err']){
            console.log(data['Err'])
            notice(data['Err']);
            return;
        }else{
            notice('<?php text('刪除成功', '删除成功','Deleted')?>');
            $(".file-list-item[data-file='" + sn + "']").slideUp('fast');
        }
    }, 'json');
}

window.lock_continue_load_file = false;
function continue_load_file_list(){
    window.lock_continue_load_file = true;
    $('.continue-load-file-list-button').hide('fast');
    load_file_list_component({
        'from': window.next_file_list
    }, function(data){
        $("#file-list-loading-area").append(data['Render_result']);
        window.lock_continue_load_file = false;
    });
}
</script>
