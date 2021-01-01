<?php
    $is_manager = User::is_manager($_SESSION['login_id']);
    $cid_managed_by_moderator = Classify::get_cid_managed_by($_SESSION['login_id']);
    $is_moderator = ($cid_managed_by_moderator != null)? TRUE : FALSE;
?>
<div class="setting-area">
    <div class="setting-area-title">水桶</div>
    <div class="col">
        <span><?php text('用戶', '用户')?>ID：</span><br>
        <input type="text" class="normal" placeholder="<?php text('輸入','输入')?>ID" id="punish-id">
    </div>
    <div class="col">
        <span><?php text('選擇看板', '选择看板')?>：</span><br>
        <select class="normal" id="punish-board">
        <?php
            $classify_list = Classify::get_list();
            if($is_manager){
                echo '<option value="all">全部看板</option>';
                foreach($classify_list as $v){
                    echo '<option value="'.$v['ID'].'">'.$v[text_r('NAME_TW', 'NAME_CN', 'NAME_EN')].'</option>';
                }
            }else if($is_moderator){
                foreach($classify_list as $v){
                    if(in_array($v['ID'], $cid_managed_by_moderator)){
                        echo '<option value="'.$v['ID'].'">'.$v[text_r('NAME_TW', 'NAME_CN', 'NAME_EN')].'</option>';
                    }
                }
            }
        ?>
        </select>
    </div>
    <div class="col">
        <span><?php text('結束日期', '结束日期')?>：</span><br>
        <select class="normal" id="punish-day">
            <option value="1">1天</option>
            <option value="3">3天</option>
            <option value="5">5天</option>
            <option value="7">7天</option>
            <option value="30">30天</option>
            <option value="60">60天</option>
            <option value="90">90天</option>
            <option value="forever"><?php text('直到解除封鎖', '直到解除封锁')?></option>
        </select>
        <input class="normal" type="datetime-local" id="punish-date" style="display:none;">
    </div>
    <div class="col">
        <button class="blue center" onclick="add_punish()">送出</button>
    </div>
    <div class="col" id="punishment-list-loading-area">
        <?php
            $punishList = new PunishList;
            if($is_moderator){
                $punishList->get_punish_list($cid_managed_by_moderator);
            }else if($is_manager){
                $punishList->get_punish_list();
            }
            echo render_punishment_list($punishList);
        ?>
    </div>
</div>
<script>
window.next_punishment_list = <?php echo $punishList->get_next();?>;
window.lock_continue_load_punishment = false;
function continue_load_punishment_list(){
    window.lock_continue_load_punishment = true;
    $('.continue-load-punushment-list-button').hide('fast');
    $.get('/function/load?type=load-render-punishment-list', {
        'from': window.next_punishment_list
    }, function(data){
        if(data['Err']){
            console.log(data['Err']);
            notice(data['Err']);
            return;
        }else{
            $("#punishment-list-loading-area").append(data['Render_result']);
            window.next_punishment_list = data['Next_from'];
            window.lock_continue_load_punishment = false;
        }
    }, 'json');
}

function reload_punishment_list(){
    $.get('/function/load?type=load-render-punishment-list', {
        'from': 0
    }, function(data){
        if(data['Err']){
            console.log(data['Err']);
            notice(data['Err']);
            return;
        }else{
            $("#punishment-list-loading-area").html(data['Render_result']);
            window.next_punishment_list = data['Next_from'];
        }
    }, 'json');
}

function add_punish(){
    var delta = $("#punish-day").val();
    var expired = -1;
    if(delta != "forever"){
        try{
            var now = new Date().getTime();
                expired = Number(now) + Number(delta)*86400*1000;
                expired = Math.floor(expired / 1000);
        }catch(e){}
    }

    var id = $("#punish-id").val();
    $.post('/function/setting?type=punish', {
        'id': id,
        'classify': $("#punish-board").val(),
        'expired': expired
    }, function(data){
        if(data['Err']){
            console.log(data['Err']);
            if(data['Err']==='User not found'){
                notice('@' + id + ' <?php text('用戶不存在', '用户不存在')?>');
            }else{
                notice(data['Err']);
            }
            return;
        }else{
            reload_punishment_list();
        }
    }, 'json');
}

function delete_punish(serial){
    $.post('/function/setting?type=delete_punish', {
        'serial': serial
    }, function(data){
        if(data['Err']){
            console.log(data['Err']);
            notice(data['Err']);
            return;
        }else{
            $(".punish-list-item[data-serial='" + serial + "']").fadeOut();
        }
    }, 'json');
}
</script>
