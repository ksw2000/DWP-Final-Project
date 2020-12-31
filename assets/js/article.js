// like: 0 dislike: 1
function like_this_article(obj, type, article_serial){
    var yes = $(obj).hasClass('yes');
    $(".article-interactive .interactive-btn").removeClass('yes');
    if(!yes) $(obj).addClass('yes');

    $.post('/function/like', {
        'article_serial': article_serial,
        'type': type
    },function(data){
        console.log(data);
        if(data['Err']){
            console.log(data['Err']);
            notice(data['Err']);
            return;
        }else{
            $("#like-num").text(data['Num-like']);
            $("#dislike-num").text(data['Num-dislike']);
        }
    }, 'json');
}

function delete_article(serial){
    $(".list[data-serial='" + serial + "'] .confirm-delete-area").fadeIn('slow', function(){
        setTimeout(function(){
            $(".list[data-serial='" + serial + "'] .confirm-delete-area").fadeOut('slow');
        }, 5000);
    })
}

function real_delete_article(serial){
    $.post('/function/article?action=delete', {
        serial: serial
    }, function(data){
        if(data['Err']){
            console.log(data['Err']);
            notice(data['Err']);
            return;
        }

        $(".list[data-serial='" + serial + "']").slideUp('slow');
    }, 'json');
}

function delete_this_article(){
    $("#real-delete-article-button").fadeIn('slow', function(){
        setTimeout(function(){
            $("#real-delete-article-button").fadeOut('slow');
        }, 5000);
    })
}

function real_delete_this_article(serial){
    $.post('/function/article?action=delete', {
        serial: serial
    }, function(data){
        if(data['Err']){
            console.log(data['Err']);
            notice(data['Err']);
            return;
        }

        location.href = '/user';
    }, 'json');
}

function star_this_article(obj, serial){
    $.post('/function/star', {
        'serial': serial
    },function(data){
        console.log(data);
        if(data['Err']){
            console.log(data['Err']);
            return;
        }else{
            if(data['Num'] === 1){
                $(obj).addClass('yes');
                $(obj).children("i").text('bookmark');
            }else{
                $(obj).removeClass('yes');
                $(obj).children("i").text('bookmark_border');
            }
        }
    },'json');
}

function like_reply(obj, type, reply_serial){
    var yes = $(obj).hasClass('yes');
    $(".reply[data-serial='" + reply_serial + "'] .interactive").removeClass('yes');
    console.log($(obj).hasClass('yes'));
    if(!yes) $(obj).addClass('yes');

    $.post('/function/like', {
        'reply_serial': reply_serial,
        'type': type
    },function(data){
        console.log(data);

        if(data['Err']){
            console.log(data['Err']);
            notice(data['Err']);
            return;
        }else{
            $(".reply[data-serial='" + reply_serial + "'] .like-num").text(data['Num-like']);
            $(".reply[data-serial='" + reply_serial + "'] .dislike-num").text(data['Num-dislike']);
        }

    }, 'json');
}

function delete_reply(serial){
    $(".reply[data-serial='" + serial + "'] .confirm-delete-area").fadeIn('slow', function(){
        setTimeout(function(){
            $(".reply[data-serial='" + serial + "'] .confirm-delete-area").fadeOut('slow');
        }, 5000);
    })
}

function real_delete_reply(serial){
    $.post('/function/reply?type=delete', {
        'serial': serial
    }, function(data){
        if(data['Err']){
            console.log(data['Err']);
            notice(data['Err']);
            return;
        }else{
            $(".reply-list .reply[data-serial='" + serial+ "']").slideUp();
        }
    }, 'json');
}

function change_list_view_mode(mode){
    console.log(mode);
    window.view_mode = mode;
    continue_load_article(0);
}
