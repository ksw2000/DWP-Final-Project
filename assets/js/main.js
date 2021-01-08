function close_expand_more(obj){
    $(obj).children('ul.more-tool-list').fadeOut();
}

function open_expand_more(obj){
    $(obj).children('ul.more-tool-list').slideDown();
}

function notice_close(){
    $("#notice").slideUp(500);
}

function set_top(serial, reset){
    $.post('/function/setting?type=set_top', {
        'serial': serial,
        'reset': reset
    }, function(data){
        if(data['Err']){
            console.log(data['Err']);
            notice(data['Err']);
            return;
        }else{
            if(typeof window.refresh_function === 'function'){
                window.refresh_function();
            }
            window.scrollTo({ top : 0, behavior: 'smooth'});
        }
    }, 'json');
}

function notice(msg){
    $("#notice #notice-content").html(msg);
    $("#notice").slideDown({
        start: function(){
            $(this).css({
                display: "flex"
            })
        },
        complete: function(){
            setTimeout(function(){
                notice_close();
            }, 5000);
        }
    });
}

function Inbox(){
    this.lock_continue_load_notice = false;
    $('.continue-load-notice-button').hide('fast');
    var self = this;
    this.next = 0;
    this.load = function(from){
        self.lock_continue_load_notice = true;
        $.get('/function/load', {
            'type': 'render-inbox',
            'from': from
        }, function(data){
            console.log(data);
            if(data['Err']){
                console.log(data['Err']);
            }else{
                self.next = data['Next_from'];
                if(from === 0){
                    $("#inbox-list").html(data['Render_result']);
                }else{
                    $("#inbox-list").append(data['Render_result']);
                }
                self.lock_continue_load_notice = false;
            }
        }, 'json');
    }

    this.continue_load = function(){
        $('.continue-load-notice-button').hide('fast');
        if(self.lock_continue_load_notice){
            self.load(self.next);
        }
    }

    this.update_read_time = function(){
        $.get('/function/notice?type=update_read_time', {}, function(){
            $("#new-message").fadeOut();
        });
    }

    this.goto_notice = function(serial, url){
        $.post('/function/notice?type=set_already_read', {
            'serial': serial
        },function(data){
            console.log(data);
            location.href = '/'+url;
        });
    }

    this.check_new_notice = function(){
        $.get('/function/notice?type=load_inbox_not_read_num', {},
        function(data){
            if(data['Num'] > 0){
                $("#new-message").fadeIn();
            }else{
                $("#new-message").fadeOut();
            }
        }, 'json');
    }

    this.delete_notice = function(serial){
        $.post('/function/notice?type=delete', {
            'serial': serial
        }, function(data){
            if(data['Err']){
                console.log(data['Err']);
            }else{
                $(".list[data-serial='" + serial + "']").slideUp();
            }
        }, 'json');
    }

    // constructor
    self.check_new_notice();
    setInterval(function(){
        self.check_new_notice();
    }, 40000);
    self.load(0);
}

function toggleInbox(){
    if($('#inbox').css('display') === 'none'){
        if(typeof window.inbox === 'undefined'){
            window.inbox = new Inbox();
        }
        window.inbox.update_read_time();
        $("#notifications-icon").text("close");
    }else{
        $("#notifications-icon").text("notifications");
    }
    $("#inbox").slideToggle();
}

function key_enter(e, call){
    var keycode;
    if(window.event){
        keycode = window.event.keyCode;
    }else if(e){
        keycode = e.which;
    }
    if(keycode == 13){
        call();
    }
}

// like: 0 dislike: 1
function like_this_article(obj, type, article_serial){
    var yes = $(obj).hasClass('yes');
    $(".article-interactive .interactive-btn").removeClass('yes');
    if(!yes) $(obj).addClass('yes');

    $.post('/function/like', {
        'article_serial': article_serial,
        'type': type
    },function(data){
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

function pjax(url, title){
    document.title = title;
    window.history.replaceState({
        index: url
    }, title, url);
}
