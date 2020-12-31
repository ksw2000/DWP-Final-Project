<?php
// this php is for manager user
function render_board_list(){
    $list = Classify::get_list();
    $ret = '';
    foreach($list as $v) {
        $ret .= '<div class="board-list-item" data-board-id="'.$v['ID'].'" onclick="open_modify_board(\''.$v['ID'].'\')">';
        $ret .= '<div class="board-list-item-main">'.$v['NAME_TW'].'('.Classify::get_article_number_by_cid($v['ID']).')</div>';
        $ret .= '<i class="material-icons expand-icon">expand_more</i>';
        $ret .= '</div>';
    }
    return $ret;
}

function render_file_list($file){
    $file_list = $file->get_file_list();
    if(empty($file_list)) return '';
    $ret = '';
    foreach($file_list as $v){
        $file_path = FILE::CLIENT_DIR.$v['FILE_TYPE'].'/'.$v['SERVER_NAME'];
        $ret .= '<div class="file-list-item" data-file-path="'.$v['FILE_TYPE'].'/'.$v['SERVER_NAME'].'">';
        $ret .= '   <div class="file-list-item-header" onclick="open_file_list_setting(\''.$v['FILE_TYPE'].'/'.$v['SERVER_NAME'].'\')">';
        $ret .= '       <a href="javascript: void(0);">'.$v['SERVER_NAME'].'</a>';
        $ret .= '       <i class="material-icons expand-icon">expand_more</i>';
        $ret .= '   </div>';
        $ret .= '   <div class="file-list-item-body">';
        if($v['LINK'] == 'profile/'){
            $ret .= '<p>@'.$v['OWNER'].'的頭貼</p>';
        }else{
            $ret .= '<p>出現在：<a targer="_blank" href="/'.$v['LINK'].'">/'.$v['LINK'].'</a></p>';
        }
        $ret .= '<p>原網址：<a targer="_blank" href="'.$file_path.'">'.$file_path.'</a></p>';
        $ret .= '<p>上傳者：<a targer="_blank" href="/user/'.$v['OWNER'].'">@'.$v['OWNER'].'</a></p>';

        if($v['FILE_TYPE'] == 'img' || $v['FILE_TYPE'] == 'profile'){
            $ret .= '<img src="'.$file_path.'">';
        }else if($v['FILE_TYPE'] == 'music'){
            $ret .= '<audio controls><source src="'.$file_path.'" type="audio/mpeg">您的瀏覽器不支援 HTML5 播放器</audio>';
        }else if($v['FILE_TYPE'] == 'normal'){
            $ret .= 'normal';
        }
        $ret .= '<center><button class="blue delete-button" onclick="delete_file(\''.$v['FILE_TYPE'].'/'.$v['SERVER_NAME'].'\')">刪除</button></center>';
        $ret .= '<center><button class="blue confirm-delete-button" onclick="real_delete_file(\''.$v['SERVER_NAME'].'\',\''.$v['FILE_TYPE'].'\')">確認刪除</button></center>';
        $ret .= '<p class="comment" style="font-size:0.7em;">刪除檔案並不會更改原本出現的地方，刪除仍與文章或用戶有連結的檔案可能會有無法預知的錯誤</p>';
        $ret .= '   </div>';
        $ret .= '</div>';
    }
    if($file->has_next()){
        $ret.= '<center>';
        $ret.= '<button onclick="continue_load_file_list()" class="blue continue-load-file-list-button">載入更多...</button>';
        $ret.= '</center>';
    }
    return $ret;
}

function redner_user_list($user){
    $list = $user->get_user_list();
    $ret = '';
    foreach($list as $v) {
        $online_time = '';
        if($v['ONLINE'] != 0){
            $online_time = '<p>'.render_time($v['ONLINE']).text_r('上線', '上线').'</p>';
        }
        $select_0 = ($v['PERMISSION'] == 0)? 'selected' : '';
        $select_1 = ($v['PERMISSION'] == 1)? 'selected' : '';

        $manage_board_list = Classify::get_cid_managed_by($v['ID']);
        $manage_board = '';
        if(!empty($manage_board_list)){
            foreach($manage_board_list as $cid){
                $manage_board .= '<a class="board-list-short-tag" href="/?tab='.$cid.'">'.Classify::transfer_cid_to_cname($cid, text_r('zh-tw', 'zh-cn')).'板主</a>';
            }
            $manage_board = "<center>${manage_board}</center>";
        }


        $ret .= '
        <div class="user-list-item" data-user-id="'.$v['ID'].'" onclick="open_user_list_setting(\''.$v['ID'].'\')">
            <img src="'.profile_photo_to_url($v['PROFILE']).'">
            <div class="user-list-item-info">
                <p>'.$v['NAME'].'</p>
                <p>@'.$v['ID'].'</p>
                <p>'.permission_to_role($v['PERMISSION']).'</p>
            </div>
            <i class="material-icons user-list-item-more">expand_more</i>
        </div>
        <div class="col setting-user-area" data-user-id="'.$v['ID'].'" style="display:none;">'.$online_time.$manage_board.'
            <p><a target="_blank" href="/user/'.$v['ID'].'" id="setting-user-personal-page-link">'.text_r('前往個人頁', '前往个人页').'</a></p>
            <select class="normal" id="choose-permission">
                <option value="0" '.$select_0.'>'.text_r('一般會員', '一般会员').'</option>
                <option value="1" '.$select_1.'>'.text_r('管理員', '管理员').'</option>
            </select>
            <center style="margin: 15px 0px;"><button class="blue" onclick="update_user_permission(\''.$v['ID'].'\')">'.text_r('權限變更', '权限变更').'</button></center>
        </div>
        ';
    }

    if($user->has_next()){
        $ret.= '<center>';
        $ret.= '<button onclick="continue_load_user_list()" class="blue continue-load-user-list-button">載入更多...</button>';
        $ret.= '</center>';
    }
    return $ret;
}

function render_punishment_list($punishList){
    $list = $punishList->get_list();
    $ret = '';
    foreach($list as $v) {
        $user_info = User::get_user_public_info($v['ID']);
        $deadline  = ($v['DEADLINE'] == Punish::FOREVER)? '永遠' : render_complete_time($v['DEADLINE']);
        $ret .= '
        <div class="punish-list-item" data-serial="'.$v['SERIAL'].'">
            <img src="'.profile_photo_to_url($user_info['PROFILE']).'">
            <div class="punish-list-item-info">
                <p>'.$user_info['NAME'].' @'.$user_info['ID'].'</p>
                <p>'.Classify::transfer_cid_to_cname($v['CLASSIFY_ID'], text_r('zh-tw', 'zh-cn')).'</p>
                <p>直到 '.$deadline.'</p>
            </div>
            <i class="material-icons punish-list-item-close" onclick="delete_punish(\''.$v['SERIAL'].'\')">close</i>
        </div>';
    }

    if($punishList->has_next()){
        $ret.= '<center>';
        $ret.= '<button onclick="continue_load_punishment_list()" class="blue continue-load-punushment-list-button">載入更多...</button>';
        $ret.= '</center>';
    }
    return $ret;
}
?>
