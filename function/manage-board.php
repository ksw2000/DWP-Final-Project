<div class="setting-area" id="manage-board">
    <div class="setting-area-title"><?php text('新增看板', '添加看板')?></div>
    <div class="col">
        <input class="normal required" id="board-id" type="text" placeholder="<?php text('輸入英文', '输入英文')?>ID e.g. music">
        <span class="comment" style="font-size: 0.88em;">英文 ID <?php text('在新增看板後即無法更改', '在添加看板后即无法更改')?></span>
    </div>
    <div class="col">
        <input class="normal required" id="board-name-t" type="text" placeholder="<?php text('輸入繁中名稱', '输入繁中名称')?> e.g. 音樂討論板">
    </div>
    <div class="col">
        <input class="normal required" id="board-name-s" type="text" placeholder="<?php text('輸入簡中名稱', '输入简中名称')?> e.g. 音乐讨论板">
    </div>
    <div class="col">
        <div style="display: flex; align-items: stretch;">
            <input class="normal" id="input-add-moderator-list" type="text" placeholder="指定板主(ID)" onkeydown="key_enter_add_moderator(this, '#input-add-moderator-list', '#ul-add-moderator-list')">
            <button style="width: 50px; border-radius: 0px; padding: 3px; height: auto;" class="blue" onclick="add_moderator('#input-add-moderator-list', '#ul-add-moderator-list')"><?php text('新增','添加')?></button>
        </div>
        <ul class="horizontal-list" id="ul-add-moderator-list"></ul>
        <span class="comment" style="font-size: 0.88em;"><?php text('管理員權限高於板主，僅有一般成員成被指定為板主。板主能刪除板上的文章、留言，及水桶', '管理员权限高于板主，仅有一般成员成被指定为板主。板主能删除板上的文章、留言，及水桶')?></span>
    </div>
    <div class="col">
        <button class="blue center" onclick="add_new_board(this, '#real-add-new-board-butoon')"><?php text('新增看板', '添加看板')?></button>
        <button class="blue center" id="real-add-new-board-butoon" style="display: none;" onclick="real_add_new_board()"><?php text('確認新增看板', '确认添加看板')?></button>
    </div>
</div>
<div class="setting-area">
    <div class="setting-area-title">看板列表</div>
    <div id="board-list-loading-area"></div>
</div>
<div class="setting-area" id="setting-board-area" style="display: none;">
    <p><span>看板 ID </span><span id="modify-board-id"></span></p>
    <p><?php text('修改繁中名稱', '修改繁中名称', 'Chinese name(traditional)')?></p>
    <input class="normal required" id="modify-board-name-t" type="text">
    <p><?php text('修改簡中名稱', '修改简中名称', 'Chinese name(simplify)')?></p>
    <input class="normal required" id="modify-board-name-s" type="text">
    <p><?php text('修改英文名稱', '修改英文名称', 'English name')?></p>
    <input class="normal required" id="modify-board-name-en" type="text">
    <p><?php text('指定板主' , '指定板主')?></p>
    <div style="display: flex; align-items: stretch;">
        <input class="normal" id="input-modify-moderator-list" type="text" placeholder="指定板主(ID)" onkeydown="key_enter_add_moderator(this, '#input-modify-moderator-list', '#ul-modify-moderator-list')">
        <button style="width: 50px; border-radius: 0px; padding: 3px; height: auto;" class="blue" onclick="add_moderator('#input-modify-moderator-list', '#ul-modify-moderator-list')"><?php text('新增', '添加')?></button>
    </div>
    <ul class="horizontal-list" id="ul-modify-moderator-list"></ul>
    <center style="margin: 10px 0px;">
        <button class="blue" onclick="delete_board()" id="delete-board-button"><?php text('刪除看板', '删除看板')?></button>
        <button class="blue" onclick="modify_board()">修改</button>
    </center>
    <span class="comment"><?php text('因安全性問題，僅允許刪除文章數為空的看板，若要刪除有文章的看板請聯絡工程師', '因安全性问题，仅允许删除文章数为空的看板，若要删除有文章的看板请联系程序员')?></span>
</div>

<script>
function load_board_list(){
    $("#board-list-loading-area").html('<div class="loader">');
    $.post('/function/load?type=load-render-board-list', {
    }, function(data){
        console.log(data);
        if(data['Err']){
            console.log(data['Err']);
            notice(data['Err']);
            return;
        }else{
            $("#board-list-loading-area").fadeOut('fast', function(){
                $("#board-list-loading-area").html(data['Render_result']);
                $("#board-list-loading-area").fadeIn('fast');
            });
        }
    }, 'json');
}

$(function(){
    load_board_list();
});

function open_modify_board(id){
    window.modify_board_id = id;
    if($(".board-list-item[data-board-id='" + id + "'] i").text() === 'expand_more'){
        load_modify_board(id, function(){
            $(".board-list-item:not([data-board-id='" + id + "']) i").text('expand_more');
            $(".board-list-item[data-board-id='" + id + "'] i").text('expand_less');
            $("#setting-board-area").slideUp('fast', function(){
                $("#setting-board-area").insertAfter($(".board-list-item[data-board-id='" + id + "']"));
                $("#setting-board-area").slideDown('fast');
            });
        });
    }else{
        $(".board-list-item i").text('expand_more');
        $("#setting-board-area").slideUp('fast');
    }
}

function refresh_board_list(){
    $("#setting-board-area").hide();
    $("#setting-board-area").insertAfter("#board-list-loading-area");
    load_board_list();
}

function load_modify_board(id, callback){
    $.get('/function/load', {
        'type': 'load-board-info',
        'cid': id
    }, function(data){
        console.log(data);
        if(data['Err']){
            console.log(data['Err']);
            notice(data['Err']);
            return;
        }else{
            data = data['Info'];
            $("#modify-board-id").text(id);
            $("#modify-board-name-t").val(data['NAME_TW']);
            $("#modify-board-name-s").val(data['NAME_CN']);
            $("#modify-board-name-en").val(data['NAME_EN']);

            let input_obj = '#input-modify-moderator-list';
            let list_obj = '#ul-modify-moderator-list';

            try{
                data['MODERATOR'] = JSON.parse(data['MODERATOR']);
                window.moderator_candidate[input_obj] = data['MODERATOR'];
            }catch(e){
                data['MODERATOR'] = [];
                window.moderator_candidate[input_obj] = [];
            }

            $(list_obj).html('');
            try{
                for(var i=0; i<data['MODERATOR'].length; i++){
                    $(list_obj).append('<li onclick="remove_from_moderator(this, \'' + input_obj + '\')">' + data['MODERATOR'][i] + '</li>');
                }
            }catch(e){}

            if(data['num_articles'] > 0){
                $('#delete-board-button').hide();
            }else{
                $('#delete-board-button').show();
            }
            callback();
        }
    }, 'json');
}

function add_new_board(obj, that){
    for(var i = 0, err = false; i<$("#manage-board .required").length; i++){
        if($("#manage-board .required").eq(i).val() === ''){
            err = true;
            $("#manage-board .required").eq(i).addClass('err');
        }else{
            $("#manage-board .required").removeClass('err');
        }
    }
    if(err) return;

    $(obj).fadeOut('fast', function() {
        $(that).fadeIn('fast');
    });
    setTimeout(function(){
        $(that).fadeOut('slow', function() {
            $(obj).fadeIn('slow');
        });
    }, 5000);
}

function real_add_new_board(){
    $.post('/function/setting?type=add_new_board', {
        'board_id' : $("#manage-board #board-id").val(),
        'board_name' : {
            'zh-tw' : $("#manage-board #board-name-t").val(),
            'zh-cn' : $("#manage-board #board-name-s").val()
        },
        'moderator_list' : JSON.stringify(window.moderator_candidate['#input-add-moderator-list'])
    }, function(data){
        console.log(data);
        if(data['Err']){
            console.log(data['Err']);
            notice(data['Err']);
            return;
        }else{
            refresh_board_list();
        }
    }, 'json');
}

function delete_board(id){
    $.post('/function/setting?type=delete_board', {
        'board_id' : window.modify_board_id
    }, function(data){
        if(data['Err']){
            console.log(data['Err']);
            notice(data['Err']);
            return;
        }else{
            refresh_board_list();
            notice('<?php text('看板刪除成功', '看板删除成功')?>');
        }
    }, 'json');
}

function modify_board(){
    for(var i = 0, err = false; i<$("#modify-board .required").length; i++){
        if($("#modify-board .required").eq(i).val() === ''){
            err = true;
            $("#modify-board .required").eq(i).addClass('err');
        }else{
            $("#modify-board .required").removeClass('err');
        }
    }
    if(err) return;

    $.post('/function/setting?type=modify_board', {
        'board_id' : window.modify_board_id,
        'board_name' : {
            'zh-tw' : $("#modify-board-name-t").val(),
            'zh-cn' : $("#modify-board-name-s").val(),
            'en' : $("#modify-board-name-en").val()
        },
        'moderator_list' : JSON.stringify(window.moderator_candidate['#input-modify-moderator-list'])
    }, function(data){
        if(data['Err']){
            console.log(data['Err']);
            notice(data['Err']);
            return;
        }else{
            refresh_board_list();
            notice('看板修改成功');
        }
    }, 'json');
}

function key_enter_add_moderator(e, input_obj, list_obj){
    var keycode = (window.event)? window.event.keyCode : ((e)? e.which : null);
    if(keycode == 13) add_moderator(input_obj, list_obj);
}

function add_moderator(input_obj, list_obj){
    var id = $(input_obj).val();

    if(typeof window.moderator_candidate[input_obj] === 'undefined'){
        window.moderator_candidate[input_obj] = [];
    }else{
        var len = (window.moderator_candidate[input_obj])? window.moderator_candidate[input_obj].length : 0;
        for(var i=0; i<len; i++){
            if(id === window.moderator_candidate[input_obj][i]){
                notice("<?php text('已在清單內', '已在清单内')?>");
                return;
            }
        }
    }

    $.post('/function/setting?type=check_the_user_can_be_moderator', {
        'id' : id
    }, function(data){
        if(data['Err']){
            console.log(data['Err']);
            if(data['Err'] === 'Is manager'){
                notice("<?php text('該候選人是管理員等級，不適用板主', '该候选人是管理员等级，不适用板主')?>");
                return;
            }else if(data['Err'] === 'User not found'){
                notice("<?php text('找不到該 ID', '找不到该 ID')?>");
                return;
            }
            notice(data['Err']);
        }else{
            if(window.moderator_candidate[input_obj]){
                window.moderator_candidate[input_obj].push(id);
            }else{
                window.moderator_candidate[input_obj]= [];
                window.moderator_candidate[input_obj].push(id);
            }
            $(list_obj).append('<li onclick="remove_from_moderator(this, \'' + input_obj + '\')">' + id + '</li>');
        }
    }, 'json');
}

function remove_from_moderator(self_obj, input_obj){
    id = $(self_obj).text();
    $(self_obj).remove();
    window.moderator_candidate[input_obj].splice(window.moderator_candidate[input_obj].indexOf(id), 1);
}
</script>
