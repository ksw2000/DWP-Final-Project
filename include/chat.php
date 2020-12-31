
<div id="wrapper">
    <img src="/assets/img/cover.png?20201201" id="cover-photo">
    <div id="wrapper-flex">
        <div id="bio-container">
        <?php echo render_bio($user_info, $be_visited_user == $_SESSION['login_id']);?>
        </div>
        <div>
            <?php
                // be visited
                echo var_dump($user_info);
                // me

            ?>
        </div>
    </div>
</div>
