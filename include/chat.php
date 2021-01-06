<?php
    $chatbox = new Chatbox;
?>
    <script>
    window.onload=setInterval(get_msg,500);
    
    Date.prototype.today = function ()
    {
        return ((this.getDate() < 10)?"0":"") + this.getDate() +"/"+(((this.getMonth()+1) < 10)?"0":"") + (this.getMonth()+1) +"/"+ this.getFullYear();
    }

    Date.prototype.timeNow = function ()
    {
        return ((this.getHours() < 10)?"0":"") + this.getHours() +":"+ ((this.getMinutes() < 10)?"0":"") + this.getMinutes();
    }

    function goto_chatbox_bottom(){
        $('#main_box').scrollTop($('#main_box')[0].scrollHeight);
    }

    function send_msg()
    {
        var data={};
        data.chat_id='<?php echo $_SESSION['login_id'];?>';
        data.chat_target='<?php echo $be_visited_user;?>';
        var currentdatetime=new Date();
        data.chat_time=currentdatetime.today()+' '+currentdatetime.timeNow();

        //console.log(data);
        data.time_to_chat=true;
        if($('.input_text_box').val()!='')
        {
            data.chat_text=$('.input_text_box').val();
            $.post('/function/chatbox',data,function(data)
            {
                remove_text();
                $('.new').remove();
                $('.main_box').append(data);
                goto_chatbox_bottom();
            });
        }
    }

    function pressEnter()
    {
        var keycode = (navigator.appname == 'Netscape')?event.which:event.keyCode;
        if(keycode == 13)
        {
            send_msg();
            remove_text();
        }
    }

    function remove_text()
    {
        document.getElementById("input_text_box").value='';
    }
    function get_msg($ID,$target)
    {
        var data={};
        data.chat_check=true;
        data.chat_check_new=(document.getElementsByClassName('left').length=='')?0:document.getElementsByClassName('left').length;
        data.find_ID='<?php echo $_SESSION['login_id']?>';
        data.find_target='<?php echo $be_visited_user?>';
        $.get('/function/chatbox',data,function(data)
        {
            if(data!='')
            {
                $('.new').remove();
                $('.main_box').append(data);
                goto_chatbox_bottom();
            }
        });
    }
    </script>

<div id="wrapper">
    <div id="wrapper-flex">
        <div id="bio-container">
        <?php echo render_bio($user_info, $be_visited_user == $_SESSION['login_id']);?>
        </div>
            <?php
                // be visited
                //echo var_dump($user_info);
                // me
            ?>
        <div class="chatbox">
            <div class="chattitle"><?php echo text_r('聊天室','聊天室','Chat');?></div>
            <div class="main_box" id="main_box">
            <?php echo $chatbox->get_msg($_SESSION['login_id'],$be_visited_user);?>
            </div>
            <div class="input">
                <input type="text" id="input_text_box" class="input_text_box" name="text" autocomplete="off" onkeypress="pressEnter()">
                <i class="material-icons" onclick="send_msg()">send</i>
            </div>
        </div>
    </div>
</div>

<script>
$(function(){
    goto_chatbox_bottom();
})
</script>
