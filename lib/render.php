<?php
function render_complete_time($timestamp){
    return date("Y-m-d H:i:s", $timestamp);
}

function render_title($url_id, $param, $be_visited_user){
    $title = text_r('龍哥論壇', '龙哥论坛', 'Longer Forum');

    if($url_id == 'user' || $url_id == 'chat'){
        $user_info = User::get_user_public_info($be_visited_user);
        $title = $user_info['NAME'].'::'.$title;
    }else if($url_id == 'add'){
        if(isset($param['edit'])){
            $title = text_r('修改貼文', '修改贴文', 'Edit').'::'.$title;
        }else{
            $title = text_r('貼文', '贴文', 'Post').'::'.$title;
        }
    }else if($url_id == 'setting'){
        $title = text_r('設定', '设置','Setting').'::'.$title;
    }else if($url_id == 'article'){
        $article_info = Article::get_info_by_serial($param['serial']);
        $title = $article_info['TITLE'].'::'.$article_info['USER']['NAME'].'::'.$title;
    }

    return $title;
}

function render_time($timestamp){
    // 小於一分鐘
    if(time()-$timestamp < 60){
        return text_r('剛剛', '刚刚', 'Just now');
    }

    // 小於一小時內
    if(time()-$timestamp < 60*60){
        return ceil((time()-$timestamp)/60) .text_r('分鐘前', '分钟前', ' minutes ago');
    }

    // 小於一天
    if(time()-$timestamp < 24*60*60){
        return ceil((time()-$timestamp)/60/60) .text_r('小時前', '小时前', ' hours ago');
    }

    // 同一年
    if(date("Y", time()) == date("Y", $timestamp)){
        return date("n-j", $timestamp);
    }
    return date("Y-n-j", $timestamp);
}

function profile_photo_to_url($row_user_profile){
    return ($row_user_profile == '')? '/assets/img/preset.jpg' : $row_user_profile;
}

// permission 0 一般會員 1 管理員
function permission_to_role($role){
    switch ($role) {
        case '0':
            return text_r('一般會員', '一般会员', 'Member');
        case '1':
            return text_r('管理員', '管理员', 'Administrator');
        default:
            return '';
    }
}

function render_reply($reply_list_item, $reader_info, $classify_id = null){
    $row = $reply_list_item;
    $now_clinet_interactive_with_the_reply = Interactive::get_user_interactive_with_the_reply($reader_info['ID'], $row['SERIAL']);
    $class_yes_like = ($now_clinet_interactive_with_the_reply == 0)? 'yes' : '';
    $class_yes_dislike = ($now_clinet_interactive_with_the_reply == 1)? 'yes' : '';

    $is_moderator = FALSE;
    if($classify_id != null){
        $is_moderator = Classify::is_moderator($reader_info['ID'], $classify_id);
    }

    $ret  = '';
    $ret .= '
        <div class="reply" data-floor="'.$row['FLOOR'].'" data-serial="'.$row['SERIAL'].'">
            <img src="'.profile_photo_to_url($row['USER']['PROFILE']).'" class="profile">
            <div class="reply-main-content">
                <div class="confirm-delete-area" onclick="real_delete_reply(\''.$row['SERIAL'].'\')"><i class="material-icons">delete</i></div>
                <div class="more-tool" tabindex="-1" onfocus="open_expand_more(this)" onblur="close_expand_more(this)">
                    <i class="material-icons expand_more_icon">expand_more</i>
                    <ul class="more-tool-list">';
    if($reader_info['ID'] == $row['USER']['ID']){
        $ret .= '<li><a href="javascript:void(0);" onclick="open_edit_reply_box(\''.$row['SERIAL'].'\')"><i class="material-icons">edit</i>'.text_r('編輯留言', '编辑留言', 'Edit').'</a></li>';
        $ret .= '<li><a href="javascript:void(0);" onclick="delete_reply(\''.$row['SERIAL'].'\')"><i class="material-icons">delete</i>'.text_r('刪除留言', '删除留言', 'Delete').'</a></li>';
        $ret .= '<li><a href="javascript:void(0);" onclick="reply_to_which_floor(\''.$row['FLOOR'].'\')"><i class="material-icons">reply</i>'.text_r('回覆', '回复', 'Reply').'</a></li>';
    }else if($reader_info['PERMISSION'] == 1 || $is_moderator){
        $ret .= '<li><a href="javascript:void(0);" onclick="delete_reply(\''.$row['SERIAL'].'\')"><i class="material-icons">delete</i>'.text_r('權限刪除', '权限删除', 'Delete').'</a></li>';
        $ret .= '<li><a href="javascript:void(0);" onclick="reply_to_which_floor(\''.$row['FLOOR'].'\')"><i class="material-icons">reply</i>'.text_r('回覆', '回复', 'Reply').'</a></li>';
    }else{
        $ret .= '<li><a href="javascript:void(0);" onclick="reply_to_which_floor(\''.$row['FLOOR'].'\')"><i class="material-icons">reply</i>'.text_r('回覆', '回复' ,'Reply').'</a></li>';
    }

    $ret .= '       </ul>
                </div>
                <a href="javascript:void(0);" title="'.text_r('回覆', '回复', 'Reply').' B'.$row['FLOOR'].'" onclick="reply_to_which_floor(\''.$row['FLOOR'].'\')">B'.$row['FLOOR'].'</a> ·
                <a href="/user/'.$row['USER']['ID'].'">'.$row['USER']['NAME'].'</a> ·
                <a href="javascript:void(0);" title="'.render_complete_time($row['TIME']).'" class="gray">'.render_time($row['TIME']).'</a>
                <br>
    ';
    if(!empty($row['TAG']) && $row['TAG']>0){
        $ret .= '<p>To: <a href="javascript:void(0);" onclick="move_to_floor(\''.$row['TAG'].'\')">B'.$row['TAG'].'</a></p>';
    }
    $ret .= '<div class="main-content">'.$row['CONTENT'].'</div>';

    if($reader_info['ID'] == $row['USER']['ID']){
        $ret .= '<div class="main-content-edit-box">';
        $ret .= '<button class="blue" onclick="close_edit_reply_box(\''.$row['SERIAL'].'\')">取消</button> <button class="blue" onclick="send_edit_reply(\''.$row['SERIAL'].'\')">完成</button>';
        $ret .= '</div>';
    }
    $ret .= '
                <div class="reply-interactive interactive">
                    <span><a href="javascript:void(0);" onclick="like_reply(this, 0, '.$row['SERIAL'].')" class="interactive '.$class_yes_like.'" data-type="like-btn"><i class="material-icons" style="vertical-align: middle; font-size:1em;">thumb_up</i></a></span>
                    <span class="like-num">'.$row['LIKE_NUM'].'</span>
                    <span><a href="javascript:void(0);" onclick="like_reply(this, 1, '.$row['SERIAL'].')" class="interactive '.$class_yes_dislike.'" data-type="dislike-btn"><i class="material-icons" style="vertical-align: middle; font-size:1em;">thumb_down</i></a></span>
                    <span class="dislike-num">'.$row['DISLIKE_NUM'].'</span>
                </div>
            </div>
        </div>
    ';
    return $ret;
}

function render_reply_list($reply, $reader_info, $classify_id = null){
    $reply_list = $reply->get_reply_list();
    $has_next_reply = $reply->has_next();
    $ret = '';
    foreach($reply_list as $key => $row){
        // 留言已被刪除
        if(empty($row['USER'])){
            continue;
        }
        $ret .= render_reply($row, $reader_info, $classify_id);
    }

    if($has_next_reply){
        $ret.= '<center>';
        $ret.= '<button onclick="continue_load_reply()" class="blue continue-load-reply-button">'.text_r('載入更多...', '加载更多...', 'Loading...').'</button>';
        $ret.= '</center>';
    }
    return $ret;
}

// now_client_user_public_info
// aka. 現在登入者的相關資訊
function render_complete_article($article_info, $reader_id){
    $reader_info  = User::get_user_public_info($reader_id);
    $is_moderator = Classify::is_moderator($reader_info['ID'], $article_info['CLASSIFY']);

    $reply = new Reply;
    $reply->get_reply_list_by_article_serial($article_info['SERIAL']);

    // 偵測現在觀看文章的用戶是否有點擊推或噓
    $now_clinet_interactive_with_the_article = Interactive::get_user_interactive_with_the_article($reader_id, $article_info['SERIAL']);

    $class_yes_like = ($now_clinet_interactive_with_the_article == 0)? 'yes' : '';
    $class_yes_dislike = ($now_clinet_interactive_with_the_article == 1)? 'yes' : '';

    // 偵測現在觀看文章的用戶是否有收藏該文
    $bookmark_icon = 'bookmark_border';
    $class_star_btn = '';
    if(Interactive::has_user_starred_the_article($reader_id, $article_info['SERIAL'])){
        $bookmark_icon = 'bookmark';
        $class_star_btn = 'yes';
    }

    $ret['Render_result'] = '
        <div class="article-header">'.$article_info['TITLE'].'</div>
        <div class="article-info">
            <div class="article-info-profile"><img src="'.profile_photo_to_url($article_info['USER']['PROFILE']).'"></div>
            <div>
                <span><a class="gray" href="/user/'.$article_info['USER']['ID'].'" onclick="goto_user_by_user_id(\''.$article_info['USER']['ID'].'\'); return false;">'.$article_info['USER']['NAME'].'</a>
                <a class="gray" href="/user/'.$article_info['USER']['ID'].'" onclick="goto_user_by_user_id(\''.$article_info['USER']['ID'].'\'); return false;">@'.$article_info['USER']['ID'].'</a> ·
                <a class="gray" href="javascript: void(0);" title="'.render_complete_time($article_info['PUBLISH']).'">'.render_time($article_info['PUBLISH']).'</a></span><br>
                <span><a class="gray" href="/?tab='.$article_info['CLASSIFY'].'" onclick="goto_index_by_tab(\''.$article_info['CLASSIFY'].'\'); return false;">'.Classify::transfer_cid_to_cname($article_info['CLASSIFY'], text_r('zh-tw', 'zh-cn', 'en')).'</a></span><br>
            </div>
            <div id="real-delete-article-button" class="confirm-delete-area" onclick="real_delete_this_article(\''.$article_info['SERIAL'].'\')"><i class="material-icons">delete</i></div>';

        if($article_info['USER']['ID'] == $reader_info['ID'] || $reader_info['PERMISSION'] == 1 || $is_moderator){
            $ret['Render_result'] .= '
            <div class="more-tool" tabindex="-1" onfocus="open_expand_more(this)" onblur="close_expand_more(this)">
                <i class="material-icons expand_more_icon">expand_more</i>
                    <ul class="more-tool-list">';
            // 為本人時可以編輯刪除
            if($article_info['USER']['ID'] == $reader_info['ID']){
                $ret['Render_result'] .= '
                    <li><a href="/edit/'.$article_info['SERIAL'].'"><i class="material-icons">edit</i>'.text_r('編輯文章', '编辑文章', 'Edit').'</a></li>';
                $ret['Render_result'] .= '
                    <li><a href="javascript:void(0);" onclick="delete_this_article()"><i class="material-icons">delete</i>'.text_r('刪除文章', '删除文章', 'Delete').'</a></li>';
            // 為管理員時可以使用權限刪除
            }else if($reader_info['PERMISSION'] == 1 || $is_moderator){
                $ret['Render_result'] .= '
                    <li><a href="javascript:void(0);" onclick="delete_this_article()"><i class="material-icons">delete</i>'.text_r('權限刪除', '权限删除', 'Delete').'</a></li>';
            }
            $ret['Render_result'] .= '
                    </ul>
            </div>';
        }

        $ret['Render_result'] .= '</div><!--article info-->
        <div class="article-body">'.$article_info['CONTENT'].'</div>
        <div class="article-attachment-area">'.render_attachment_list($article_info['ATTACHMENT']).'</div>
        <div class="article-interactive interactive">
            <span onclick="like_this_article(\'.article-interactive #like-btn\', 0, \''.$article_info['SERIAL'].'\')">
                <a href="javascript:void(0);" class="interactive-btn '.$class_yes_like.'" id="like-btn" data-type="like-btn">
                    <i class="material-icons" style="vertical-align: middle; font-size:1em;">thumb_up</i>
                </a>
                <b id="like-num">'.$article_info['LIKE_NUM'].'</b>
            </span><span onclick="like_this_article(\'.article-interactive #dislike-btn\', 1, \''.$article_info['SERIAL'].'\')">
                <a href="javascript:void(0);" class="interactive-btn '.$class_yes_dislike.'" id="dislike-btn" data-type="dislike-btn">
                    <i class="material-icons" style="vertical-align: middle; font-size:1em;">thumb_down</i>
                </a>
                <b id="dislike-num">'.$article_info['DISLIKE_NUM'].'</b>
            </span><span onclick="star_this_article(\'.article-interactive .star-button\', \''.$article_info['SERIAL'].'\')">
                <a href="javascript:void(0);" class="star-button '.$class_star_btn.'" data-type="star-btn"><i class="material-icons" style="vertical-align: middle; font-size:1em;">'.$bookmark_icon.'</i></a>
            </span>
        </div>';

    $ret['Render_result'] .= '
        <div class="reply-list">
            <div class="reply" id="main-reply" data-floor="0">
                <img src="'.profile_photo_to_url($reader_info['PROFILE']).'" class="profile">
                <div style="width: 100%; padding:10px;">
                    <div class="reply-floor"></div>
                    <div style="display: flex; align-items: flex-end;">
                        <div id="main-reply-editor"></div>
                        <div class="reply-button">
                            <a href="javascript:void(0);" onclick="add_reply(\''.$article_info['SERIAL'].'\')"><i class="material-icons" style="vertical-align: middle; font-size: 1.5em;">send</i></a>
                        </div>
                    </div>
                </div>
            </div>
            <div id="hot-reload"></div>';
    $ret['Render_result'] .= render_reply_list($reply, $reader_info, $article_info['CLASSIFY']);
    $ret['Render_result'] .= '</div>';  // reply-list
    $ret['Next_reply'] = $reply->get_next();
    return $ret;
}

const RENDER_CLASSIFY_TOP_BUTTON = 0b1;  // 分類看板置頂
const RENDER_QUERY_MODE          = 0b10;  // 搜尋模式
const RENDER_FISRT_LIST          = 0b100;  // 第一份
const RENDER_STAR_MODE           = 0b1000;  // 收藏模式
const RENDER_MEDIA_MODE          = 0b10000;  // 媒體模式
const RENDER_LIST_MODE           = 0b100000;  // 精簡模式

function render_article_list($article, $reader_id, $flags = 0){
    $reader_info = User::get_user_public_info($reader_id);
    $article_info_list = $article->get_article_info_list();
    $has_next = $article->has_next();
    $ret = '';

    if(empty($article_info_list) && (($flags & RENDER_FISRT_LIST) == RENDER_FISRT_LIST)){
        if(($flags & RENDER_QUERY_MODE) == RENDER_QUERY_MODE){
            $ret .= '<div class="tip-no-article">Oops! '.text_r('查無相關文章', '查无相关文章', 'There is no related Article').'</div>';
        }else if(($flags & RENDER_STAR_MODE) == RENDER_STAR_MODE){
            $ret .= '<div class="tip-no-article">Oops! '.text_r('尚無收藏的文章', '尚无收藏的文章', 'No Starred Articles').'</div>';
        }else{
            $ret .= '<div class="tip-no-article">Oops! '.text_r('空空如也，尚無文章', '空空如也，尚无文章', 'No Articles').'</div>';
        }
    }

    foreach($article_info_list as $index => $row){
        $is_moderator = Classify::is_moderator($reader_info['ID'], $row['CLASSIFY']);

        $ret .= '
        <div class="list" data-serial="'.$row['SERIAL'].'">
            <div class="confirm-delete-area" onclick="real_delete_article(\''.$row['SERIAL'].'\')"><i class="material-icons">delete</i></div>
            <div class="start">
                <div class="profile-photo"><img src="'.profile_photo_to_url($row['USER']['PROFILE']).'">'.((USER::is_online($row['USER']['ID']))? '<div class="online"></div>' : '').'</div>
                <div class="column">
                    <div>
                        <span><a href="/user/'.$row['USER']['ID'].'" onclick="goto_user_by_user_id(\''.$row['USER']['ID'].'\'); return false;">'.$row['USER']['NAME'].'</a>
                            <a href="/user/'.$row['USER']['ID'].'" onclick="goto_user_by_user_id(\''.$row['USER']['ID'].'\'); return false;">@'.$row['USER']['ID'].'</a> ·
                            <a title="'.render_complete_time($row['PUBLISH']).'" href="javascript:void(0);">'.render_time($row['PUBLISH']).'</a>
                        </span>';
        $ret .= (($flags & RENDER_LIST_MODE) != RENDER_LIST_MODE)? '</div><div>' : '';
        $ret .= '<span><a href="/?tab='.$row['CLASSIFY'].'" onclick="goto_index_by_tab(\''.$row['CLASSIFY'].'\'); return false;">'.Classify::transfer_cid_to_cname($row['CLASSIFY'], text_r('zh-tw', 'zh-cn', 'en')).'</a>';
        if($row['TOP'] == 1 && (($flags & RENDER_CLASSIFY_TOP_BUTTON) == RENDER_CLASSIFY_TOP_BUTTON)){
            $ret .= ' | '.text_r('已置頂', '已置顶', 'Top');
        }
        $ret .= '</span></div>';

        if(($flags & RENDER_LIST_MODE) == RENDER_LIST_MODE){
            $ret .= '<div><span><a class="title-brief-mode" href="/article/'.$row['SERIAL'].'" onclick="goto_article(\''.$row['USER']['ID'].'\', \''.$row['SERIAL'].'\'); return false;">'.$row['TITLE'].'</a></span></div>';
        }
        $ret .= '</div>'; // <div class="column">

        if($row['USER']['ID'] == $reader_info['ID'] || $reader_info['PERMISSION'] == 1 || $is_moderator || ($flags & RENDER_STAR_MODE) == RENDER_STAR_MODE){
            $ret .= '<div class="more-tool" tabindex="-1" onfocus="open_expand_more(this)" onblur="close_expand_more(this)">
                        <i class="material-icons expand_more_icon">expand_more</i>
                        <ul class="more-tool-list">';
            // 管理員可將文章置頂(限分類看板)
            if((($flags & RENDER_CLASSIFY_TOP_BUTTON) == RENDER_CLASSIFY_TOP_BUTTON)
                && ($reader_info['PERMISSION'] == 1 || $is_moderator)){
                if($row['TOP'] == 1){
                    $ret .= '<li><a href="javascript:void(0);" onclick="set_top(\''.$row['SERIAL'].'\', true)"><i class="material-icons">star</i>'.text_r('取消置頂', '取消置顶', 'Unpin').'</a></li>';
                }else{
                    $ret .= '<li><a href="javascript:void(0);" onclick="set_top(\''.$row['SERIAL'].'\', false)"><i class="material-icons">star</i>'.text_r('置頂', '置顶', 'Pin').'</a></li>';
                }
            }
            // 可取消收藏
            if(($flags & RENDER_STAR_MODE) == RENDER_STAR_MODE){
                $ret .= '<li><a href="javascript:void(0);" onclick="cancel_star(\''.$row['SERIAL'].'\')"><i class="material-icons">bookmark_border</i>'.text_r('取消收藏', '取消收藏', 'Unstar').'</a></li>';
            }
            // 為本人時可以編輯刪除
            if($row['USER']['ID'] == $reader_info['ID']){
                $ret .= '<li><a href="/edit/'.$row['SERIAL'].'"><i class="material-icons">edit</i>'.text_r('編輯文章', '编辑文章', 'Edit').'</a></li>';
                $ret .= '<li><a href="javascript:void(0);" onclick="delete_article(\''.$row['SERIAL'].'\')"><i class="material-icons">delete</i>'.text_r('刪除文章', '删除文章', 'Delete').'</a></li>';
            // 為管理員時可以使用權限刪除
            }else if($reader_info['PERMISSION'] == 1 || $is_moderator){
                $ret .= '<li><a href="javascript:void(0);" onclick="delete_article(\''.$row['SERIAL'].'\')"><i class="material-icons">delete</i>'.text_r('權限刪除', '权限删除', 'Delete').'</a></li>';
            }
            $ret .= '</ul></div>';
        }

        $end = '';
        if((($flags & RENDER_MEDIA_MODE) == RENDER_MEDIA_MODE) || (($flags & RENDER_LIST_MODE) == RENDER_LIST_MODE)){
            $attachment_list = json_decode($row['ATTACHMENT'], TRUE);
            $num_img_files = 0;
            $num_music_files = 0;
            $num_video_files = 0;
            $num_normal_files = 0;

            if(json_last_error() == JSON_ERROR_NONE){
                foreach($attachment_list['type'] as $v){
                    switch($v) {
                        case 'img':     $num_img_files++;   break;
                        case 'music':   $num_music_files++; break;
                        case 'video':   $num_video_files++; break;
                        case 'normal':  $num_normal_files++;
                    }
                }
            }
            $end = '
                <span><i class="material-icons" style="vertical-align: middle; font-size:1em;">image</i></span>
                <span>'.$num_img_files.'</span>
                <span><i class="material-icons" style="vertical-align: middle; font-size:1em;">audiotrack</i></span>
                <span>'.$num_music_files.'</span>
                <span><i class="material-icons" style="vertical-align: middle; font-size:1em;">videocam</i></span>
                <span>'.$num_video_files.'</span>
                <span><i class="material-icons" style="vertical-align: middle; font-size:1em;">attach_file</i></span>
                <span>'.$num_normal_files.'</span>
            ';
        }

        if(($flags & RENDER_LIST_MODE) != RENDER_LIST_MODE){
            $end = '
                <span><i class="material-icons" style="vertical-align: middle; font-size:1em;">thumb_up</i></span>
                <span>'.$row['LIKE_NUM'].'</span>
                <span><i class="material-icons" style="vertical-align: middle; font-size:1em;">thumb_down</i></span>
                <span>'.$row['DISLIKE_NUM'].'</span>
                <span><i class="material-icons" style="vertical-align: middle; font-size:1em;">comment</i></span>
                <span>'.$row['REPLY_NUM'].'</span>
            ';
        }

        $ret .= '</div>';
        if(($flags & RENDER_LIST_MODE) != RENDER_LIST_MODE){
            $ret .= '<div class="title"><a href="/article/'.$row['SERIAL'].'" onclick="goto_article(\''.$row['USER']['ID'].'\', \''.$row['SERIAL'].'\'); return false;">'.$row['TITLE'].'</a></div>';
            $ret .= '<div class="brief">'.cut_content($row['CONTENT'], 40);
            $ret .= render_attachment_list($row['ATTACHMENT'], FALSE, TRUE);
            $ret .= '</div>';
            $ret .= '<div class="end">'.$end.'</div>';
        }else{
            $ret .= '<div class="end">'.$end.'</div>';
        }
        $ret .= '</div>';
    }

    if($has_next){
        $ret .= '<button onclick="continue_load_article()" class="blue continue-load-article-button center">';
        $ret .= text_r('載入', '加载','Loading').'更多...</button>';
    }else if(!empty($article_info_list)){
        $ret .= '<div class="article-list-end"><div class="circle"></div></div>';
    }

    return $ret;
}

function render_bio_edit_area($user_info){ //edit the user info
    $birthday = $user_info['MORE_INFO_HTMLENTITIES']['BIRTHDAY'];
    $hobby    = $user_info['MORE_INFO_HTMLENTITIES']['HOBBY'];
    $from     = $user_info['MORE_INFO_HTMLENTITIES']['COME_FROM'];
    $link     = $user_info['MORE_INFO_HTMLENTITIES']['LINK'];
    $bio      = $user_info['MORE_INFO']['BIO'];

    return '
    <div id="bio-edit">
        <div class="col">
            <i class="material-icons">person</i><input class="normal" type="text" placeholder="'.text_r('名字', '名字', 'Name').'" value="'.$user_info['NAME'].'" id="name">
        </div>
        <div class="col">
            <i class="material-icons">cake</i><input class="normal" type="text" placeholder="'.text_r('生日是', '生日是', 'Birthday').'..." value="'.$birthday.'" id="birthday">
        </div>
        <div class="col">
            <i class="material-icons">sentiment_satisfied</i><input class="normal" type="text" placeholder="'.text_r('興趣是', '兴趣是', 'Hobby').'..." value="'.$hobby.'" id="hobby">
        </div>
        <div class="col">
            <i class="material-icons">place</i><input class="normal" type="text" placeholder="'.text_r('來自', '来自', 'Homeland').'..." value="'.$from.'" id="from">
        </div>
        <div class="col">
            <i class="material-icons">link</i><input class="normal" type="text" placeholder="'.text_r('連結', '链結', 'Link').'" value="'.$link.'" id="link">
        </div>
        <div class="col">
            <div id="bio-content-editor">'.$bio.'</div>
        </div>
        <div class="col">
            <button onclick="edit_bio()" class="blue center">'.text_r('儲存變更', '保存变更', 'Save').'</button>
        </div>
    </div>
    ';
}

//Edit profile Picture(Profile Page)
function render_bio($user_info, $include_edit_area = FALSE){
    $birthday = $user_info['MORE_INFO_HTMLENTITIES']['BIRTHDAY'];
    $hobby    = $user_info['MORE_INFO_HTMLENTITIES']['HOBBY'];
    $from     = $user_info['MORE_INFO_HTMLENTITIES']['COME_FROM'];
    $link     = $user_info['MORE_INFO_HTMLENTITIES']['LINK'];
    $bio      = $user_info['MORE_INFO']['BIO'];

    $online = (USER::is_online($user_info['ID']))? text_r('上線中', '在线','Online'): text_r('離線', '离线','Offline'); //Check online or offline
    $ret = '';
    if($include_edit_area){//change profile picture
        $ret .= '
        <div id="profile">
            <div id="profile-photo-uploading" class="pseudo-img" style="display:none;">
                <div class="loader loader-margin"></div>
            </div>
            <img id="profile-photo" src="'.profile_photo_to_url($user_info['PROFILE']).'">
            <div id="change-profile"><form enctype="multipart/form-data">'.text_r('更換頭像', '更换头像',"Change Profile Picture").'
            <input id="profile-upload" type="file" accept="image/*" onchange="upload_profile_btn_onchange()">
            </form></div>
        </div>
        ';
    }else{
        $ret .= '
        <div id="profile-disable-edit">
            <img id="profile-photo" src="'.profile_photo_to_url($user_info['PROFILE']).'">
        </div>
        ';
    }
    $ret .='
    <div id="name-id-info">
        <p style="font-size: 2em;">'.$user_info['NAME'].'</p>
        <p style="color: #af9d86;">@'.$user_info['ID'].'</p>
    </div>
    <div id="bio">';

    if($include_edit_area){
        $ret .= '
        <div id="bio-edit-tool">
            <span onclick="toggle_bio_edit_area()"><i class="material-icons" style="vertical-align: bottom;" id="i-edit">edit</i></span>
        </div>
        ';
        $ret .= render_bio_edit_area($user_info);
    }

    $ret .= '<div id="bio-list"><ul>';
    $ret .= '<li><i class="material-icons">message</i><a href="http://localhost/chat/'.$user_info['ID'].'">'.text_r('傳訊息', '传讯息','Chat').'</a></li>';
    $ret .= '<li><i class="material-icons">adjust</i>'.$online.'</li>';
    $ret .= ($birthday != '')? '<li><i class="material-icons">cake</i>'.$birthday.'</li>' : '';
    $ret .= ($hobby != '')? '<li><i class="material-icons">sentiment_satisfied</i>'.$hobby.'</li>' : '';
    $ret .= ($from != '')? '<li><i class="material-icons">place</i>'.$from.'</li>' : '';
    $ret .= ($link != '')? '<li><i class="material-icons">link</i><a href="'.$link.'">'.$link.'</a></li>' : '';
    $ret .= '</ul></div>';
    $ret .='<div id="bio-content">'.$bio.'</div>';
    if($birthday == '' && $hobby == '' && $from == '' && $link == '' && $bio ==''){
        if($include_edit_area){
            $ret .= '<center class="comment">'.text_r('尚未完成自我介紹', '尚未完成自我介绍', 'Talk more about yourself :)').'<br>'.text_r('點擊右上方的筆', '点击右上方的笔', 'Click the Pen above').'<i class="material-icons" style="font-size:1em;">edit</i>'.text_r('進行編輯', '进行编辑', 'Edit').'</center>';
        }
    }
    $ret .='</div>';

    return $ret;
}

// $attachment_list is the json string
function render_attachment_list($attachment_list, $editable = FALSE, $snapshot_mode = FALSE){
    /*
        json
        client_name: 原先上傳的檔名
        server_name: 隨機生成的檔名
        path: client_path 存取連結(相對於域名)
        type: img, music, video, normal
    */
    $attachment_list = json_decode($attachment_list);
    if(json_last_error() != JSON_ERROR_NONE){
        return '';
    }
    if(empty($attachment_list->type)){
        return '';
    }
    $img_list = array();
    $music_list = array();
    $video_list = array();
    $normal_list = array();
    $a = $b = $c = $d = 0;
    foreach($attachment_list->type as $i => $val){
        $tmp = array(
            'client_name' => $attachment_list->client_name[$i],
            'server_name' => $attachment_list->server_name[$i],
            'path' => $attachment_list->path[$i],
            'type' => $attachment_list->type[$i],
        );

        if($snapshot_mode && $val == 'normal'){
            $normal_list[$d++] = $tmp;
        }else if(!$snapshot_mode){
            switch($val){
                case 'img':
                    $img_list[$a++] = $tmp;
                    break;
                case 'music':
                    $music_list[$b++] = $tmp;
                    break;
                case 'video':
                    $video_list[$c++] = $tmp;
                    break;
                case 'normal':
                    $normal_list[$d++] = $tmp;
            }
        }
    }

    if(!$snapshot_mode){
        $ret = '<div class="attachment-list">';
    }else{
        $ret = '<div class="attachment-list-snapshot">';
    }
    // 渲染圖片
    if(!empty($img_list)){
        $ret.= '<div class="img-list">';
        foreach($img_list as $v){
            $ret .= '<div class="img-list-item" data-file-name="'.$v['server_name'].'">';
            if($editable){
                $ret .= '<p><a href="'.$v['path'].'">'.$v['client_name'].'</a><a href="javascript:void(0);" class="del-list-item"  onclick="delete_attachment(\''.$v['server_name'].'\')"><i class="material-icons">close</i></a></p>';
            }
            $ret .= '<img src="'.$v['path'].'" />';
            $ret .= '</div>';
        }
        $ret.= '</div>';
    }

    // 渲染音樂
    if(!empty($music_list)){
        $ret.= '<div class="music-list">';
        foreach($music_list as $v){
            $ret .= '<div class ="music-list-item" data-file-name="'.$v['server_name'].'">';
            if($editable){
                $ret .= '<p><a href="'.$v['path'].'">'.$v['client_name'].'</a>';
                $ret .= '<a href="javascript:void(0);" class="del-list-item" onclick="delete_attachment(\''.$v['server_name'].'\')"><i class="material-icons">close</i></a>';
            }
            $ret .='</p>';
            $ret .= '<audio controls controlsList="nodownload"><source src="'.$v['path'].'">'.text_r('您的瀏覽器不支援 HTML5 播放器', '您的浏览器不支持 HTML5 播放器', 'Your browser does not support HTML5 player').'</audio>';
            $ret .= '</div>';
        }
        $ret.= '</div>';
    }

    // 渲染影片
    if(!empty($video_list)){
        $ret.= '<div class="video-list">';
        foreach($video_list as $v){
            $ret .= '<div class ="video-list-item" data-file-name="'.$v['server_name'].'">';
            if($editable){
                $ret .= '<p><a href="'.$v['path'].'">'.$v['client_name'].'</a>';
                $ret .= '<a href="javascript:void(0);" class="del-list-item" onclick="delete_attachment(\''.$v['server_name'].'\')"><i class="material-icons">close</i></a>';
            }
            $ret .='</p>';
            $ret .= '<video controls controlsList="nodownload"><source src="'.$v['path'].'">'.text_r('您的瀏覽器不支援 HTML5 播放器', '您的浏览器不支持 HTML5 播放器', 'Your browser does not support HTML5 player').'</audio>';
            $ret .= '</div>';
        }
        $ret.= '</div>';
    }

    // 渲染附件
    if(!empty($normal_list)){
        $ret.= '<div class="normal-list">';
        if(!$snapshot_mode){
            $ret.= '<p>'.text_r('檔案附件：', '文件附件：', 'Files:').'</p>';
        }
        $ret.= '<ul>';
        foreach($normal_list as $v){
            $ret .= '<li data-file-name="'.$v['server_name'].'"><a href="'.$v['path'].'">'.$v['client_name'].'</a>';
            if($editable){
                $ret .= '<a href="javascript:void(0);" class="del-list-item" onclick="delete_attachment(\''.$v['server_name'].'\')"><i class="material-icons">close</i></a>';
            }
            $ret .='</li>';
        }
        $ret.= '</ul>';
        $ret.= '</div>';
    }
    $ret .= '</div>';
    return $ret;
}

function render_online_list(){
    $list = User::get_online_list(); //User::get_online_list() returns id of the user
    $ret = '';
    if(empty($list)) return $ret;
    foreach($list as $v){
        $ret .= '<div class="online-list-item"><img src="'.profile_photo_to_url(User::get_profile($v)).'">
                    <div class="online-list-item-element">
                        <div class="online-list-item-col" onclick="window.location=\'/user/'.$v.'\'">'.text_r("個人資料","个人资料","Profile").'</div>
                        <div class="online-list-item-col" onclick="window.location=\'/chat/'.$v.'\'">'.text_r("聊天室","聊天室","Chat").'</div>
                    </div>
                </div>';
        //'<div class="online-list-item" onclick="window.location=\'/user/'.$v.'\'"><img src="'.profile_photo_to_url(User::get_profile($v)).'"></div>';
    }
    return $ret;
}

function render_global_info(){//show which users are online
    $ret  = '<div class="online-list-title">'.text_r('上線中', '上线中','Online').' ('.User::get_online_number().')</div>';
    $ret .= '<div class="online-list">'.render_online_list().'</div>';
    return $ret;
}

function render_notice_list($notice, $flags = 0){
    $list = $notice->get_notice_list();
    $len  = count($list);
    $ret  = '';

    if(empty($list) && (($flags & RENDER_FISRT_LIST) == RENDER_FISRT_LIST)){
        $ret .= '<div class="tip-no-article">'.text_r('尚無通知', '尚无通知', 'No new notification').'</div>';
    }

    foreach($list as $index => $row){
        $row['ID_FROM'] = User::get_user_public_info($row['ID_FROM']);
        $class_new_notice = ($row['ALREADY_READ'] == 0)? ' new-notice' : '';
        $ret .= '
        <div class="list'.$class_new_notice.'" data-serial="'.$row['NOTICE_SERIAL'].'">
            <div class="start">
                <img src="'.profile_photo_to_url($row['ID_FROM']['PROFILE']).'">
                <div class="column">
                    <div><span><a href="javascript:void(0);">'.$row['ID_FROM']['NAME'].'</a> @'.$row['ID_FROM']['ID'].' · <a title="'.render_complete_time($row['TIME']).'" href="javascript:void(0);">'.render_time($row['TIME']).'</a></span><span>';
        switch($row['TYPE']){
            case Notice::COMMENT_TO_YOUR_ARTICLE:
                $ret .= text_r('新的留言', '新的留言', 'New comment');
                break;
            case Notice::NEW_LIKE:
                $ret .= text_r('新的按讚', '新的按赞', 'New like');
                break;
            case Notice::REPLY_TO_YOUR_COMMENT:
                $ret .= text_r('有人回覆你', '有人回复你', 'You have a message');
                break;
            case Notice::YOUR_ARTICLE_IS_DELETED:
                break;
            case Notice::YOUR_COMMENT_IS_DELETED:
                break;
        }
        $ret .= '</span>';
        $ret .= '<div><span class="title-brief-mode"><a href="javascript:void(0);" onclick="window.inbox.goto_notice(\''.$row['NOTICE_SERIAL'].'\', \''.$row['LINK'].'\')">';
        switch($row['TYPE']){
            case Notice::COMMENT_TO_YOUR_ARTICLE:
                /*
                    [0] => article/GU9h7CYs?reply=83
                    [1] => GU9h7CYs
                    [2] => 83
                */
                preg_match('/article\/(.*?)\?reply=([0-9]+)/', $row['LINK'], $matches);
                $article_serial = $matches[1];
                $article_info = Article::get_info_by_serial($article_serial);

                $ret .= text_r('你的貼文', '你的贴文', 'Your post').' "'.$article_info['TITLE'].'" '.text_r(' 有新的留言', ' 有新的留言', 'has new Comment');
                break;
            case Notice::NEW_LIKE:
                $article_serial = str_replace('article/', '', $row['LINK']);
                $article_info = Article::get_info_by_serial($article_serial);
                $ret .= '"'.$article_info['TITLE'].'" '.text_r('有新的按讚', '有新的按赞', 'New like');
                break;
            case Notice::REPLY_TO_YOUR_COMMENT:
                /*
                    [0] => article/6IxeZqzz?reply=84&reply_to=44
                    [1] => 6IxeZqzz
                    [2] => 84
                    [3] => 44
                */
                preg_match('/article\/(.*?)\?reply=([0-9]+)\&reply_to=([0-9]+)/', $row['LINK'], $match);
                $article_serial = $matches[1];
                $article_info = Article::get_info_by_serial($article_serial);
                $ret .= '"'.$row['ID_FROM']['NAME'].'" '.text_r('回覆了你在', '回复了你在', 'replied your comment at').' "'.$article_info['TITLE'].text_r(' 的留言', ' 的留言');
                break;
            case Notice::YOUR_ARTICLE_IS_DELETED:
                break;
            case Notice::YOUR_COMMENT_IS_DELETED:
                break;
        }
        $ret .= '</a></span></div>';
        $ret .= '</div></div>
                <div class="more-tool more-tool-vertical-center" onclick="window.inbox.delete_notice(\''.$row['NOTICE_SERIAL'].'\')">
                    <i class="material-icons expand_more_icon">close</i>
                </div>
            </div>';
        $ret .= '</div>';
    }

    if($notice->has_next()){
        $ret .= '<button onclick="window.inbox.continue_load()" class="blue continue-load-notice-button center">'.text_r('載入更多...', '加载更多...', 'More...').'</button>';
    }
    return $ret;
}
?>
