<?php
    $user_info  = User::get_user_public_info($_SESSION['login_id'], TRUE);
    $is_manager = User::is_manager($_SESSION['login_id']);
    $cid_managed_by_moderator = Classify::get_cid_managed_by($_SESSION['login_id']);
    $is_moderator = ($cid_managed_by_moderator != null)? TRUE : FALSE;
?>

<div id="wrapper">
    <div id="bio-container">
        <?php echo render_bio($user_info, TRUE);?>
    </div>
    <div id="tab-container">
        <select id="select-tab">
            <option value="personal"><?php text('個人資料','个人')?></option>
            <?php if($is_manager || $is_moderator):?>
            <option value="manage-punish"><?php text('水桶','水桶')?></option>
            <?php endif;?>
            <?php if($is_manager):?>
            <option value="manage-user"><?php text('用戶管理','用户管理')?></option>
            <option value="add-new-user"><?php text('註冊新用戶','注册新用户')?></option>
            <option value="manage-board">看板管理</a></div>
            <option value="manage-file"><?php text('檔案管理','文件管理')?></option>
            <?php endif;?>
        </select>
        <div class="hide-bigger-than-600px">
            <div class="tab-list now" data-tab="personal"><a href="javascript: void(0);" onclick="change_tab('personal');"><?php text('個人資料','个人')?></a></div>
            <?php if($is_manager || $is_moderator):?>
            <div class="tab-list" data-tab="manage-punish"><a href="javascript: void(0);" onclick="change_tab('manage-punish');"><?php text('水桶','水桶')?></a></div>
            <?php endif;?>
            <?php if($is_manager):?>
            <div class="tab-list" data-tab="manage-user"><a href="javascript: void(0);" onclick="change_tab('manage-user');"><?php text('用戶管理','用户管理')?></a></div>
            <div class="tab-list" data-tab="add-new-user"><a href="javascript: void(0);" onclick="change_tab('add-new-user');"><?php text('註冊新用戶','注册新用户')?></a></div>
            <div class="tab-list" data-tab="manage-board"><a href="javascript: void(0);" onclick="change_tab('manage-board');">看板管理</a></div>
            <div class="tab-list" data-tab="manage-file"><a href="javascript: void(0);" onclick="change_tab('manage-file');"><?php text('檔案管理','文件管理')?></a></div>
            <?php endif;?>
        </div>
    </div>
    <div id="setting-container">
        <div class="tab" data-tab="personal">
            <div class="setting-area" id="change-password">
                <div class="setting-area-title"><?php text('更換密碼', '更换密码')?></div>
                <div class="col">
                    <input class="normal required" id="ori-password" type="password" placeholder="<?php text('原密碼', '原密码')?>" onkeydown="key_enter(this, change_password)">
                </div>
                <div class="col">
                    <input class="normal required" id="new-password" type="password" placeholder="<?php text('新密碼', '新密码')?>" onkeydown="key_enter(this, change_password)">
                </div>
                <div class="col">
                    <input class="normal required" id="retype-password" type="password" placeholder="<?php text('確認新密碼', '确认新密码')?>" onkeydown="key_enter(this, change_password)">
                </div>
                <div class="col" style="text-align: right;">
                    <button class="blue" style="margin: 0px;" onclick="change_password()">更改</button>
                </div>
            </div>
            <div class="setting-area" id="change-email">
                <div class="setting-area-title"><?php text('更換電郵地址', '更换电邮地址')?></div>
                <div class="col">
                    <input class="normal required" id="input-new-email" type="text" placeholder="<?php text('輸入新的郵件地址', '输入新的邮件地址')?>"  value="<?php echo $_SESSION['user_info']['EMAIL']?>" onkeydown="key_enter(this, change_email)">
                </div>
                <div class="col" style="text-align: right;">
                    <button class="blue" style="margin: 0px;" onclick="change_email()">更改</button>
                </div>
            </div>
            <div class="setting-area" id="change-language">
                <div class="setting-area-title"><?php text('選擇語言', '选择语言');?></div>
                <div class="col">
                    <select class="normal" id="select-language">
                        <?php if($_SESSION['user_info']['LANGUAGE'] == 1):?>
                            <option value="zh-cn">简体中文</option>
                        <?php endif;?>
                        <option value="zh-tw">繁體中文</option>
                        <?php if($_SESSION['user_info']['LANGUAGE'] == 0):?>
                            <option value="zh-cn">简体中文</option>
                        <?php endif;?>
                    </select>
                </div>
                <div class="col" style="text-align: right;">
                    <button class="blue" style="margin: 0px;" onclick="change_language()">更改</button>
                </div>
            </div>
            <div class="setting-area">
                <div class="setting-area-title"><?php text('登入資訊', '登入信息')?></div>
                <div class="col">
                    角色：<?php echo permission_to_role($user_info['PERMISSION']);?>
                </div>
                <div class="col">
                    <button class="blue center" onclick="window.location='./login?logout'">登出</button>
                </div>
            </div>

            <?php if($is_manager):?>
            <div class="setting-area">
                <div class="setting-area-title"><?php text('潛水模式', '潜水模式')?></div>
                <div class="col">
                    <div class="onoffswitch">
                        <input type="checkbox" name="onoffswitch" class="onoffswitch-checkbox"
                               id="diving-checkbox" tabindex="0" onchange="switch_diving_mode(this)" <?php if($user_info['DIVING']) echo 'checked';?>>
                        <label class="onoffswitch-label" for="diving-checkbox"></label>
                    </div>
                </div>
                <div class="col">
                    <span class="comment"><?php text('開啟潛水模式後，其他用戶無法知道你是否在線', '潜水模式激活后，其他用户无法知道你是否在线')?></span>
                </div>
            </div>
            <?php endif;?>

            <div class="setting-area">
                <div class="setting-area-title"><?php text('刪除帳號', '删除帐号')?></div>
                <div class="col delete-account-button-col">
                    <button class="red center" onclick="delete_account()"><?php text('刪除帳號', '删除帐号')?></button>
                </div>
                <div class="col delete-account-check-pwd-col" style="display: none;">
                    <input class="normal required" id="delete-account-check-pwd" type="password" placeholder="<?php text('密碼', '密码')?>" onkeydown="key_enter(this, change_password)" autocomplete="off">
                </div>
                <div class="col delete-account-check-pwd-col" style="display: none;">
                    <button class="red center" onclick="real_delete_account()"><?php text('確認刪除帳號', '确认删除帐号')?></button>
                </div>
                <div class="col">
                    <span class="comment"><?php text('刪除帳號會永久刪除所有文章以及回覆，操作不可回復', '删除帐号会永久删除所有帳號資料、文章、留言、回覆、...等，操作不可回复')?></span>
                </div>
            </div>
        </div>
        <div class="tab" data-tab="add-new-user">
            <div class="setting-area" id="add-new-user">
                <div class="setting-area-title"><?php text('註冊新用戶', '注册新用户');?></div>
                <div class="col">
                    <input class="normal required" id="add-user-id" type="text" placeholder="<?php text('帳號', '帐号')?>" onkeydown="key_enter(this, add_new_user)">
                    <span class="comment"><?php text('帳號確定後無法更改', '帐号确定后无法更改')?></span>
                </div>
                <div class="col">
                    <input class="normal required" id="add-user-pwd" type="text" placeholder="<?php text('密碼','密码')?>" onkeydown="key_enter(this, add_new_user)">
                </div>
                <div class="col">
                    <input class="normal required" id="add-user-name" type="text" placeholder="<?php text('暱稱', '暱称')?>" onkeydown="key_enter(this, add_new_user)">
                </div>
                <div class="col">
                    <input class="normal required" id="email" type="text" placeholder="Email" onkeydown="key_enter(this, add_new_user)">
                </div>
                <div class="col">
                    <span><?php text('預設語言', '默认语言')?></span>
                    <select class="normal" id="select-preset-language">
                        <option value="0"><?php text('繁體中文', '繁体中文')?></option>
                        <option value="1"><?php text('簡體中文', '简体中文')?></option>
                    </select>
                </div>
                <div class="col">
                    <span><?php text('選擇角色', '选择角色')?></span>
                    <select class="normal" id="permission">
                        <option value="0"><?php text('一般會員', '一般会员')?></option>
                        <option value="1"><?php text('管理員', '管理员')?></option>
                    </select>
                    <span class="comment"><?php text('若要指定為板主，請至看板管理頁面進行指定', '若要指定为板主，请至看板管理页面进行指定')?></span>
                </div>
                <div class="col">
                    <button class="blue center" onclick="add_new_user()"><?php text('新增', '添加')?></button>
                </div>
            </div>
        </div>
        <div class="tab" data-tab="manage-board"></div>
        <div class="tab" data-tab="manage-user"></div>
        <div class="tab" data-tab="manage-punish"></div>
        <div class="tab" data-tab="manage-file"></div>
    </div>
</div>
<?php include './include/bio-edit.php';?>
<script>
var tab = window.location.href.match(/\?(.*)$/, '');
    tab = (tab === null)? 'personal' : tab[1];
    window.nowTab = tab;

$(function(){
    toggle_bio_edit_area();
    change_tab(tab);
    $("#select-tab").change(function(){
        change_tab($("#select-tab").val());
    });
});

function change_tab(tab){
    window.nowTab = tab;

    if(tab === 'manage-board'){
        load_manage_board();
    }else if(tab == 'manage-user'){
        load_manage_user();
    }else if(tab == 'manage-file'){
        load_manage_file();
    }else if(tab == 'manage-punish'){
        load_manage_punish();
    }

    $("#tab-container .tab-list:not([data-tab='" + tab + "'])").removeClass('now');
    $("#tab-container .tab-list[data-tab='" + tab + "']").addClass('now');
    $("#select-tab option").removeAttr('selected');
    $("#select-tab option[value='" + tab + "']").attr('selected', 'selected');

    $("#setting-container .tab:not([data-tab='" + tab + "'])").fadeOut('fast',function(){
        $("#setting-container .tab[data-tab='" + tab + "']").fadeIn();
    });

    var url = window.location.href.replace(/\?(.*)$/, '');
    url = (tab !== 'personal')? url + '?' + tab : url;
    window.history.replaceState(null, '', url);
}

function change_password(){
    var ori_pwd = $("#change-password #ori-password").val();
    var new_pwd = $("#change-password #new-password").val();
    var retype_pwd = $("#change-password #retype-password").val();

    var err = false;
    for(var i = 0; i < $("#change-password .required").length; i++){
        if($("#change-password .required").eq(i).val() === ''){
            err = true;
            $("#change-password .required").eq(i).addClass('err');
        }else{
            $("#change-password .required").removeClass('err');
        }
    }
    if(err){
        notice('<?php text('請填妥所有欄位','请填妥所有栏位')?>');
        return;
    }

    if(new_pwd != retype_pwd){
        err = true;
        $("#change-password #retype-password").addClass('err');
    }else{
        $("#change-password #retype-password").removeClass('err');
    }

    if(err){
        notice('<?php text('密碼與確認密碼不同','密码与确认密码不同')?>');
        return;
    }

    $.post('/function/user-setting?type=change_password', {
        'ori'    : ori_pwd,
        'new'    : new_pwd,
        'retype' : retype_pwd
    }, function(data){
        if(data['Err']){
            console.log(data['Err']);
            var msg;
            if(data['Err'] === 'password-error'){
                notice('<?php text('原密碼錯誤', '原密码错误')?>');
            }else if(data['Err'] === 'only[a-zA-Z0-9-_]{8,30}'){
                notice('<?php text('新密碼只能由「字母、數字、-、_」組成且介於8~30字', '新密码只能由「字母、数字、-、_」组成且介于8~30字')?>');
            }else if(data['Err'] === 'need-0-9'){
                notice('<?php text('新密碼必需要含有數字', '新密码必需要含有数字')?>');
            }else if(data['Err'] === 'need-a-z'){
                notice('<?php text('新密碼必需要含有英文', '新密码必需要含有英文')?>');
            }else{
                notice(data['Err']);
            }
            return;
        }else{
            $("#change-password #ori-password").val('');
            $("#change-password #new-password").val('');
            $("#change-password #retype-password").val('');
            notice('<?php text('密碼變更成功', '密码变更成功')?>');
        }
    }, 'json');
}

function change_email(){
    $.post('/function/user-setting?type=change_email', {
        'email' : $("#change-email #input-new-email").val()
    }, function(data){
        if(data['Err']){
            console.log(data['Err']);
            notice(data['Err']);
            return;
        }else{
            notice('<?php text('已發送驗證信至新郵箱，請前往確認', '已发送验证信至新邮箱，请前往确认')?>');
        }
    }, 'json');
}

function change_language(){
    $.post('/function/user-setting?type=change_language', {
        'lang' : $("#change-language #select-language").val()
    }, function(data){
        console.log(data);
        if(data['Err']){
            console.log(data['Err']);
            notice(data['Err']);
            return;
        }else{
            notice('<?php text('語言變更成功，重新整理後更新', '语言变更成功，重新整理后更新')?>');
        }
    }, 'json');
}

function delete_account(){
    $(".delete-account-button-col").slideUp();
    $(".delete-account-check-pwd-col").slideDown();
}

function real_delete_account(){
    $.post('/function/user-setting?type=delete_account', {
        'pwd' : $("#delete-account-check-pwd").val()
    }, function(data){
        console.log(data);
        if(data['Err']){
            console.log(data['Err']);
            if(data['Err'] === 'pwd wrong'){
                nocite("密碼錯誤");
            }
            return;
        }else{
            window.location = './login?logout';
        }
    });
}
</script>

<?php if($is_manager || $is_moderator):?>
<script>
function load_manage_punish(){
    $(".tab[data-tab='manage-punish']").html('<div class="loader loader-margin"></div>');
    $.get('/function/manage-punish', {}, function(data){
        $(".tab[data-tab='manage-punish']").fadeOut('fast',function(){
            $(".tab[data-tab='manage-punish']").html(data);
            $(".tab[data-tab='manage-punish']").fadeIn('fast');
        });
    });
}
</script>
<?php endif;?>
<?php if($is_manager):?>
<script>
function switch_diving_mode(o){
    var diving = $(o).is(":checked");
    $.post('/function/setting?type=diving_mode', {
        'diving': diving
    }, function(data){
        if(data['Err']){
            console.log(data['Err'])
            notice(data['Err']);
            return;
        }else{
            if(diving){
                notice('<?php text('潛水模式已開啟', '潜水模式已激活')?>');
            }else{
                notice('<?php text('潛水模式已關閉', '潜水模式已关闭')?>');
            }
        }
    }, 'json');
}

/*function add_new_user(){
    var id = $("#add-new-user #add-user-id").val();
    var pwd = $("#add-new-user #add-user-pwd").val();
    var name = $("#add-new-user #add-user-name").val();
    var email = $("#add-new-user #email").val();

    for(var i = 0, err = false; i<$("#add-new-user .required").length; i++){
        if($("#add-new-user .required").eq(i).val() === ''){
            err = true;
            $("#add-new-user .required").eq(i).addClass('err');
        }else{
            $("#add-new-user .required").removeClass('err');
        }
    }
    if(err) return;

    $.post('/function/user-setting?type=add_new_user', {
        'id': id,
        'pwd': pwd,
        'name': name,
        'email': email,
        'lang': $("#add-new-user #select-preset-language").val(),
        'permission': $("#add-new-user #permission").val()
    }, function(data){
        if(data['Err']){
            if(data['Err'] === 'only[a-zA-Z0-9-_]{8,30}'){
                msg = '<?php text('密碼只能由「字母、數字、-、_」組成且介於8~30字', '密码只能由「字母、数字、-、_」组成且介于8~30字')?>';
            }else if(data['Err'] === 'need-0-9'){
                msg = '<?php text('密碼必需要含有數字', '密码必需要含有数字')?>';
            }else if(data['Err'] === 'need-a-z'){
                msg = '<?php text('密碼必需要含有英文', '密码必需要含有英文')?>';
            }else if(data['Err'] === 'only[a-zA-Z0-9-_{4,30}]'){
                msg = '<?php text('帳號只能由「字母、數字、-、_」組成且介於4~30字', '帐号只能由「字母、数字、-、_」组成且介于4~30字')?>';
            }else if(data['Err'] === 'ID existed'){
                msg = '<?php text('帳號已經存在', '帐号已经存在')?>';
            }else{
                notice(data['Err']);
                return;
            }
            notice(msg);
        }else{
            notice('<?php text('已成功新增用戶', '已成功添加用户')?>');
        }
    }, 'json');
}*/

function load_manage_board(){
    window.moderator_candidate = {};
    $(".tab[data-tab='manage-board']").html('<div class="loader loader-margin"></div>');
    $.get('/function/manage-board', {}, function(data){
        $(".tab[data-tab='manage-board']").fadeOut('fast', function(){
            $(".tab[data-tab='manage-board']").html(data);
            $(".tab[data-tab='manage-board']").fadeIn('fast');
        });
    });
}

function load_manage_user(){
    window.setting_user_info_list = {};
    $(".tab[data-tab='manage-user']").html('<div class="loader loader-margin"></div>');
    $.get('/function/manage-user', {}, function(data){
        $(".tab[data-tab='manage-user']").fadeOut('fast',function(){
            $(".tab[data-tab='manage-user']").html(data);
            $(".tab[data-tab='manage-user']").fadeIn('fast');
        });
    });
}

function load_manage_file(){
    $(".tab[data-tab='manage-file']").html('<div class="loader loader-margin"></div>');
    $.get('/function/manage-file', {}, function(data){
        $(".tab[data-tab='manage-file']").fadeOut('fast',function(){
            $(".tab[data-tab='manage-file']").html(data);
            $(".tab[data-tab='manage-file']").fadeIn('fast');
        });
    });
}
</script>
<?php endif;?>
