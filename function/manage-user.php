<div class="setting-area">
    <div class="setting-area-title"><?php text('用戶管理', '用户管理')?></div>
    <div class="col">
        <div style="display: flex; align-items: stretch;">
            <input class="normal" id="find-user" type="text" placeholder="<?php text('以ID或名稱搜尋', '以ID或名称搜寻');?>">
            <button style="width: 50px; border-radius: 0px; padding: 3px; height: auto;" class="blue" onclick="find_user()"><?php text('搜尋','搜寻')?></button>
        </div>
    </div>
    <div class="col user-list" id="user-list-loading-area"></div>
</div>

<script>
function load_user_list_component(param, callback){
    $.get('/function/load?type=load-render-user-list', param, function(data){
        if(data['Err']){
            console.log(data['Err']);
            notice(data['Err']);
            return;
        }else{
            $.each(data['User_info_list'], function(k, v) {
                window.setting_user_info_list[k] = v;
            });
            window.next_user_list = data['Next_from'];
            callback(data);
        }
    }, 'json');
}

function load_user_list(){
    $("#user-list-loading-area").html('<div class="loader"></div>');
    load_user_list_component({}, function(data){
        $("#user-list-loading-area").fadeOut('fast',function(){
            $("#user-list-loading-area").html(data['Render_result']);
            $("#user-list-loading-area").fadeIn('fast');
        });
    });
}

function find_user(){
    load_user_list_component({
        'q' : $("#find-user").val()
    }, function(data){
        if(typeof window.user_list_hash === 'undefined' || data['Hash'] != window.user_list_hash){
            $("#user-list-loading-area").fadeOut('fast', function(){
                window.user_list_hash = data['Hash'];
                $("#user-list-loading-area").html(data['Render_result']);
                $("#user-list-loading-area").fadeIn('fast');
            });
        }
    });
}

$(function(){
    load_user_list();
    $("#find-user").bind('keyup', function(){
        find_user();
    });
});

function open_user_list_setting(user_id){
    $(".setting-user-area:not([data-user-id='" + user_id + "'])").slideUp();
    $(".setting-user-area[data-user-id='" + user_id + "']").slideToggle();
    let $expand_icon = $(".user-list-item[data-user-id='" + user_id + "'] i.user-list-item-more");
    if($expand_icon.text() == "expand_more"){
        $expand_icon.text("expand_less");
    }else{
        $expand_icon.text("expand_more");
    }
    $(".user-list-item:not([data-user-id='" + user_id + "']) i.user-list-item-more").text("expand_more");
}

function update_user_permission(user_id){
    var permission = $("#choose-permission").val();

    $.post('/function/setting?type=update_user_permission', {
        'user_id': user_id,
        'permission': permission
    }, function(data){
        if(data['Err']){
            console.log(data['Err']);
            notice(data['Err']);
            return;
        }else{
            notice('<?php text('權限更改成功', '权限更改成功')?>');
        }
    }, 'json');
}

window.lock_continue_load_article = false;
function continue_load_user_list(){
    window.lock_continue_load_article = true;
    $('.continue-load-user-list-button').hide('fast');
    load_user_list_component({
        'from': window.next_user_list
    }, function(data){
        $("#user-list-loading-area").append(data['Render_result']);
        window.lock_continue_load_article = false;
    });
}
</script>
