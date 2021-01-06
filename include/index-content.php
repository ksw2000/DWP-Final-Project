<?php
    $tab = 'all';
    if($url_id == 'index'){
        $tab = !empty($_GET['tab'])? $_GET['tab'] : 'all';
    }else if($url_id == 'article'){
        $article_info = Article::get_info_by_serial($_GET['serial']);
        $tab = $article_info['CLASSIFY'];
    }else if($url_id == 'user'){
        $tab = 'user';
    }

    $classify_list = Classify::get_list();

    // 預設值：
    $next_reply = -1;
    $next_article_list = -1;
?>
<!-- navigation header -->
<div id="bookmark"> <!-- Device size bigger than 700px -->
    <div class="bigger-than-700px bookmark-list<?php if($tab == 'user') echo ' now';?>" data-tab="user"><a href="/user" onclick="goto_user_by_user_id('<?php echo $_SESSINO['login_id']?>'); return false;"><?php text('用戶', '用戶', 'User');?></a></div>
    <div class="bigger-than-700px bookmark-list<?php if($tab == 'all') echo ' now';?>" data-tab="all"><a href="/" onclick="goto_index_by_tab('all'); return false;"><?php text('全部看板', '全部看板', 'All')?></a></div>
    <?php
        foreach($classify_list as $v){
            $now = ($tab == $v['ID'])? ' now' : '';
            echo '<div class="bigger-than-700px bookmark-list'.$now.'" data-tab="'.$v['ID'].'"><a href="javascript: void(0);" onclick="goto_index_by_tab(\''.$v['ID'].'\');">'.$v[text_r('NAME_TW', 'NAME_CN','NAME_EN')].'</a></div>';
        }
    ?>
    <select id="select-tab" class="smaller-than-700px"> <!-- Device size smaller than 700px -->
        <option value="all"><?php text('全部看板', '全部看板', 'All')?></option>
        <?php
            foreach($classify_list as $v){
                $selected = ($tab == $v['ID'])? ' selected' : '';
                echo '<option value="'.$v['ID'].'"'.$selected.'>'.$v[text_r('NAME_TW', 'NAME_CN','NAME_EN')].'</option>';
            }
        ?>
    </select>
</div>

<div id="wrapper">
    <div id="wrapper-flex">
    <?php if($url_id == 'index'):?>
        <div id="global-info">
        <?php echo render_global_info();//Get the number of online users?>
        </div>
        <div id="bio-container" style="display: none;"></div>
        <div id="article-container" class="article-container" style="display: none;"></div>
    <?php endif?>

    <?php if($url_id == 'user'): //swap webpage to edit profile ?>
        <div id="global-info" style="display:none;"></div>
        <div id="bio-container">
        <?php echo render_bio($user_info, $be_visited_user == $_SESSION['login_id']);?>
        </div>
        <div id="article-container" class="article-container" style="display: none;"></div>
    <?php endif?>

    <?php if($url_id == 'article'): // swap webpage to post status?>
        <div id="global-info" style="display:none;"></div>
        <div id="bio-container">
        <?php
            echo render_bio(User::get_user_public_info($article_info['USER']['ID'], User::MORE_INFO), $article_info['USER']['ID'] == $_SESSION['login_id']);
        ?>
        </div>
        <div id="article-container" class="article-container">
    <?php
        $render_article_data = render_complete_article($article_info, $_SESSION['login_id']);
        echo $render_article_data['Render_result'];
        $next_reply = $render_article_data['Next_reply'];
    ?>
        </div>
    <?php endif?>

    <div id="article-list" class="article-list" <?php if($url_id == 'article') echo 'style="display: none;"'?>><!-- the search bar and icons -->
        <div class="mode-switch">
            <div class="search-box">
                <input class="normal" type="search" id="search" onchange="search()" onkeyup="search()" placeholder="<?php text('搜尋','搜寻','Search')?>...">
                <i class="material-icons icon-button search-icon" onclick="search()">search</i>
            </div>
            <i class="material-icons icon-button" onclick="goto_star_article()" id="goto-star-article-icon">bookmark</i>
            <i class="material-icons icon-button" onclick="change_list_view_mode('list')">view_headline</i>
            <i class="material-icons icon-button" onclick="change_list_view_mode('detail')">view_list</i>
        </div><!-- switch-box-->
        <div id="article-list-hot-reload">
    <?php
        if($url_id == 'user'){// Write Article
            $article = new Article;
            if($tab == 'user'){
                $article->get_article_list_by_userid($be_visited_user);
                echo render_article_list($article, $_SESSION['login_id'], RENDER_FISRT_LIST);
            }
        }else{
            $article = new Article;
            $article->get_published_article_list(($tab != 'all') ? $tab : null);

            if($tab == 'all'){
                echo render_article_list($article, $_SESSION['login_id'], RENDER_FISRT_LIST);
            }else{
                echo render_article_list($article, $_SESSION['login_id'], RENDER_CLASSIFY_TOP_BUTTON | RENDER_FISRT_LIST);
            }
        }
        $next_article_list = $article->get_next();
    ?>
        </div><!-- article-list-hot-reload -->
    </div><!-- article-list -->
</div><!--wrapper-flex-->
</div><!--wrapper-->

<script>
window.mode = '<?php echo $url_id;?>';
window.classify = '<?php echo $tab;?>';
window.visited_user = '<?php echo $be_visited_user;?>';
window.query_mode = false;
window.next_article_list = <?php echo $next_article_list;?>;
window.lock_continue_load_article = false;
window.lock_continue_load_reply = false;

$(function(){
    if(window.mode == 'index'){
        index_mode_init();
    }else if(window.mode == 'article'){
        article_mode_init();
        article_mode_init_reply_part();
    }else if(window.mode == 'user'){
        user_mode_init();
    }

    $(window).scroll(function(){
        if(window.mode == 'index' || window.mode == 'user'){
            if(!window.lock_continue_load_article){
                last = $(document).height() - $(window).height()-150;
                if($(window).scrollTop() >= last){
                    continue_load_article(window.next_article_list);
                }
            }
        }

        if(window.mode == 'article'){
            if(!window.lock_continue_load_reply){
                last = $(document).height() - $(window).height()-150;
                if($(window).scrollTop() >= last){
                    continue_load_reply();
                }
            }
        }
    });

    $("#select-tab").on('change', function(){
        goto_index_by_tab($("#select-tab").val());
    });
});

function search(){
    window.query = $("#search").val();
    if(window.query != ''){
        window.query_mode = true;
    }else{
        window.query_mode = false;
    }
    continue_load_article(0);
}

function continue_load_article(from){ //162
    if(typeof from === 'undefined') from = window.next_article_list;
    window.lock_continue_load_article = true;
    $('.continue-load-article-button').hide('fast');

    var param = {};

    param.from = from;

    if(window.mode == 'index'){
        param.type = 'render-published-article-list';
        param.classify = (window.classify === "all")? null : window.classify;
    }else if(window.mode == 'user'){
        param.type = 'render-article-list-by-user-id';
        param.user_id = window.visited_user;
        if(window.classify == 'star') param.star = '';
    }

    if(window.query_mode){
        param.query = window.query;
    }

    if(typeof window.view_mode !== 'undefined' && window.view_mode == 'list'){
        param.view_mode = window.view_mode;
    }

    if(from === 0){
        $("#article-list-hot-reload").fadeOut('fast', function(){
            $("#article-list-hot-reload").html('<div class="loader loader-margin"></div>');
            $("#article-list-hot-reload").fadeIn('fast');
            $.get('/function/load', param, function(data){
                if(data['Err']){
                    console.log(data['Err']);
                }else{
                    window.next_article_list = data['Next_from'];
                    $("#article-list-hot-reload").html(data['Render_result']);
                }
                window.lock_continue_load_article = false;
            }, 'json');
        });
    }else{
        $.get('/function/load', param, function(data){
            if(data['Err']){
                console.log(data['Err']);
            }else{
                window.next_article_list = data['Next_from'];
                $("#article-list-hot-reload").append(data['Render_result']);
            }
            window.lock_continue_load_article = false;
        }, 'json');
    }
}

function render_bio(be_visited_user){
    $("#bio-container").html('<div class="loader loader-margin"></div>');
    $.get('/function/load',{
        'type': 'render_bio',
        'be_visited_user': be_visited_user
    }, function(data){
        $("#bio-container").html(data['Render_result']);
    }, 'json');
}


/*-------- INDEX MODE --------*/
function index_mode_init(){
    var tab = window.location.href.match(/[\?&]tab=(.*)$/, '');
    window.classify = (tab === null)? 'all' : tab[1];
    window.next_article_list = <?php echo $next_article_list;?>;
    window.setInterval(function(){
        $.get('/function/load',{
            'type' : 'render-online-list'
        }, function(data){
            $('.online-list').html(data['Render_result']);
        }, 'json');
    }, 40000);
    $("#goto-star-article-icon").hide();
}

function goto_index_by_tab(tab){
    index_mode_init();
    window.mode = 'index';
    window.classify = tab;

    var url = (tab !== 'all')? '/?tab=' + tab : '/';
    pjax(url, tab);

    $("#article-list").show();
    $("#article-container").hide();
    $("#bio-container").fadeOut(function(){
        $("#global-info").fadeIn();
    });

    $("#bookmark .bookmark-list:not([data-tab='" + tab + "'])").removeClass('now');
    $("#bookmark .bookmark-list[data-tab='" + tab + "']").addClass('now');

    continue_load_article(0);

    $("#global-info").html('<div class="loader loader-margin"></div>');
    $.get('/function/load',{
        'type': 'render_global_info'
    }, function(data){
        $('#global-info').html(data['Render_result']);
    }, 'json');
}

/*-------- ARTICLE MODE --------*/

function article_mode_init(){
    window.next_reply = <?php echo $next_reply;?>;
    var article_serial = window.location.href.match(/article\/(.*)$/, '');
    window.article_serial = (article_serial != null) ? article_serial[1] : '';
    window.floor = 0;
}

function article_mode_init_reply_part(){ //外面幹來  get from github
    BalloonEditor.create(document.querySelector('#main-reply-editor'),{
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

function goto_article(be_visited_user_id, serial){
    var url  = '/article/' + serial;
    window.history.replaceState(null, '', url);
    window.mode = 'article';

    article_mode_init();
    $("#article-list").hide();
    $("#article-container").show();
    $("#global-info").fadeOut(function(){
        $("#bio-container").css('display', '');
        $("#bio-container").addClass('show_in_pc');
    });
    $("#article-container").html('<div class="loader loader-margin"></div>');
    $("#bio-container").html('<div class="loader loader-margin"></div>');

    $.get('/function/load',{
        'type': 'render-complete-article',
        'serial': serial
    }, function(data){
        $('#article-container').html(data['Render_result']);
        window.next_reply = data['Next_reply'];
        $("#bookmark .bookmark-list:not([data-tab='" + data['Classify'] + "'])").removeClass('now');
        $("#bookmark .bookmark-list[data-tab='" + data['Classify'] + "']").addClass('now');
        $("#bookmark #select-tab option").removeAttr('selected');
        $("#bookmark #select-tab option[value='" + data['Classify'] + "']").attr('selected', 'selected');
        article_mode_init_reply_part();
    }, 'json');

    render_bio(be_visited_user);
}

function add_reply(article_serial){
    var content = window.editor.getData();
    if(content === ''){
        $("#main-reply-editor").css('border-color', '#f00');
        return;
    }

    $.post('/function/reply?type=add', {
        'article_serial': article_serial,
        'tag': window.floor,
        'content': content
    }, function(data){
        if(data['Err']){
            console.log(data['Err']);
            notice(data['Err']);
            return;
        }

        var replay_serial = data['New_serial'];
        window.editor.setData('');
        cancel_reply_to_floor();

        $.post('/function/load?type=load-reply', {
            'serial': replay_serial
        },function(data){
            if(data['Err']){
                console.log(data['Err']);
                notice(data['Err']);
                return;
            }

            $(".reply-list #hot-reload").prepend(data['Render_reply']);
            mark_reply_animate(replay_serial);
        }, 'json');
    }, 'json');
}

function cancel_reply_to_floor(){
    window.floor = 0;
    $("#main-reply .reply-floor").slideUp('fast');
}

function move_to_floor(floor){
    window.scrollTo({
        top : $(".reply[data-floor='" + floor + "']")[0].offsetTop - 40,
        behavior: 'smooth'
    });
}

function reply_to_which_floor(floor){
    move_to_floor(0);
    window.floor = floor;
    $("#main-reply .reply-floor").html('回覆 B' + floor +
    '<a href="javascript:void(0);" onclick="cancel_reply_to_floor(\'' +
    floor + '\')"><i class="material-icons" style="vertical-align: middle; font-size:1em;">clear</i></a>');

    $("#main-reply .reply-floor").slideDown('fast');
}

function close_edit_reply_box(serial){
    $(".reply-list .reply[data-serial='" + serial + "'] .main-content-edit-box").fadeOut();
    window.subEditor[serial].destroy().then(function(){
        $(".reply-list .reply[data-serial='" + serial + "'] .main-content").removeClass('sub-editor');
        $(".reply-list .reply[data-serial='" + serial + "'] .main-content").html(window.content[serial]);
    });
}

function open_edit_reply_box(serial){
    $(".reply-list .reply[data-serial='" + serial + "'] .main-content-edit-box").show();
    $(".reply-list .reply[data-serial='" + serial + "'] .main-content").addClass('sub-editor');
    if(window.content == null){
        window.content = new Object();
    }
    window.content[serial] = $(".reply-list .reply[data-serial='" + serial + "'] .main-content").html();

    BalloonEditor.create(document.querySelector(".reply-list .reply[data-serial='" + serial + "'] .main-content"),{
        toolbar: {
            items: ['undo', 'redo', 'bold', 'italic', 'link']
        },
        language: 'zh',
        licenseKey: ''
    }).then(editor => {
        if(window.subEditor == null){
            window.subEditor = new Object();
        }
        window.subEditor[serial] = editor;
    }).catch(error => {
        console.error('Oops, something went wrong!');
        console.error('Please, report the following error on https://github.com/ckeditor/ckeditor5/issues with the build id and the error stack trace:' );
        console.warn('Build id: oazkkbvl856-y87g8s9179pi');
        console.error(error);
    });
}

function send_edit_reply(serial){
    var content = window.subEditor[serial].getData();
    window.content = content;   // for hot reload
    if(content === ''){
        $(".reply-list .reply[data-serial='" + serial + "'] .main-content").css('border-color', '#f00');
        return;
    }

    $.post('/function/reply?type=update', {
        'serial': serial,
        'content': content
    }, function(data){
        if(data['Err']){
            console.log(data['Err']);
            notice(data['Err']);
            return;
        }else{
            // update window.content in advance
            // and close_edit_reply_box() will hot reload() the new content
            close_edit_reply_box(serial);
        }
    }, 'json');
}

function mark_reply_animate(serial){
    $(".reply-list .reply[data-serial='" + serial + "']").css('background-color', '#faf1bb');
    setTimeout(function(){
        $(".reply-list .reply[data-serial='" + serial + "']").css('transition', 'background-color 2000ms');
        $(".reply-list .reply[data-serial='" + serial + "']").css('background-color', 'transparent');
    }, 5000);
}

function continue_load_reply(){
    window.lock_continue_load_reply = true;
    $('.continue-load-reply-button').hide('fast');
    $.get('/function/load', {
        'type': 'load-reply-list',
        'article_serial': window.article_serial,
        'from': window.next_reply
    },function(data){
        if(data['Err']){
            console.log(data['Err']);
        }else{
            $(".reply-list").append(data['Render_result']);
            window.next_reply = data['Next_from'];
            window.lock_continue_load_reply = false;
        }
    }, 'json');
}

/*-------- USER MODE --------*/
function user_mode_init(){
    window.mode = 'user';
    window.classify = 'user';
    $("#goto-star-article-icon").show();
    $("#goto-star-article-icon").removeClass('yes');
    $("#bookmark .bookmark-list:not([data-tab='" + window.classify + "'])").removeClass('now');
    $("#bookmark .bookmark-list[data-tab='" + window.classify + "']").addClass('now');
}

function goto_user_by_user_id(be_visited_user){
    user_mode_init();
    window.visited_user = be_visited_user;
    $("#article-list").show();
    $("#article-container").hide();
    $("#global-info").fadeOut(function(){
        $("#bio-container").fadeIn();
    });

    var url = '/user/' + be_visited_user;
    window.history.replaceState(null, '', url);

    continue_load_article(0);

    render_bio(be_visited_user);
}

function goto_star_article(){
    if($("#goto-star-article-icon").hasClass('yes')){
        $("#goto-star-article-icon").removeClass('yes');
        window.classify = 'user';
    }else{
        $("#goto-star-article-icon").addClass('yes');
        window.classify = 'star';
    }
    continue_load_article(0);
}
</script>
<?php include './include/bio-edit.php';?>
