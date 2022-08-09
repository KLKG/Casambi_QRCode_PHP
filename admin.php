<?php
    include 'config/config.php';
    include 'functions/web_helper.php';
    include 'functions/casambi.php';

    $logged_in = 0;
    $NEW_CODE = "";
    session_start();

    if(isset($_SESSION["logged_in"]) && $_SESSION["logged_in"] === true){
        $logged_in = 1;
    }

    if (isset($_GET['site'])){
        $SITE = cleanSite($_GET['site']);
    }else{
        $SITE = '';
    }


    if (($logged_in == 0)&&($SITE == 'login')){
        if (isset($_POST['username'])){
            $login_username = cleanUserPass($_POST['username']);
        }else{
            $login_username = '';
        }

        if (isset($_POST['current-password'])){
            $login_password = cleanUserPass($_POST['current-password']);
        }else{
            $login_password = '';
        }

        $db_link = mysqli_connect($db_host, $db_user, $db_password, $db_database);

        if($db_link === false){
            die("ERROR: Could not connect. " . mysqli_connect_error());
        }

        $sql = "select count(*) as ctr from user where username='".mysqli_real_escape_string($db_link, $login_username)."' and password='".mysqli_real_escape_string($db_link, $login_password)."' limit 1;";
        $result = mysqli_query($db_link, $sql);
        while ( $data = mysqli_fetch_object( $result)){
            if ( $data->ctr== 1){
                $logged_in = 1;
                session_start();
                $_SESSION["logged_in"] = true;
                $_SESSION["username"] = $username;
            }
         }    

         mysqli_close($db_link);
         $SITE = '';
    }

    if (($logged_in == 1)&&($SITE == 'delete')){
        if (isset($_GET['code'])){
            $code_del = cleanCode($_GET['code']);
        }else{
            $code_del = '';
        }

        $db_link = mysqli_connect($db_host, $db_user, $db_password, $db_database);

        if($db_link === false){
            die("ERROR: Could not connect. " . mysqli_connect_error());
        }
        
        $sql2 = "delete from qrcodes where code='".mysqli_real_escape_string($db_link, $code_del)."';";
        $result2 = mysqli_query($db_link, $sql2);

        $sql2 = "delete from qrcode_level where code='".mysqli_real_escape_string($db_link, $code_del)."';";
        $result2 = mysqli_query($db_link, $sql2);

        mysqli_close($db_link);
        $SITE = 'list';
    }

    if (($logged_in == 1)&&($SITE == 'add')){
        $db_link = mysqli_connect($db_host, $db_user, $db_password, $db_database);

        if($db_link === false){
            die("ERROR: Could not connect. " . mysqli_connect_error());
        }

        $foundnewstring = 0;    
        while ($foundnewstring == 0) {
            $new_code = cleanCode(substr(md5(time()), 0, 10));

            $sql = "select count(*) as ctr from qrcodes where code='".mysqli_real_escape_string($db_link, $new_code)."' limit 1;";
            $result = mysqli_query($db_link, $sql);
            while ( $data = mysqli_fetch_object( $result)){
                if ( $data->ctr== 0){
                    $foundnewstring = 1;
                }
             }  
        }

        mysqli_close($db_link);
        $SITE = 'edit';
        $NEW_CODE = $new_code;
    } 

    if (($logged_in == 1)&&($SITE == 'save')){
        if (isset($_POST['code'])){
            $code_save = cleanCode($_POST['code']);
        }else{
            $code_save = '';
        }

        if (isset($_POST['code_type'])){
            $code_type_save_temp = cleanText($_POST['code_type']);
            if ($code_type_save_temp == "level"){
                $code_type_save = 1;
            }
            if ($code_type_save_temp == "tc"){
                $code_type_save = 2;
            }
            if ($code_type_save_temp == "rgbw"){
                $code_type_save = 3;
            }
            if ($code_type_save_temp == "none"){
                $code_type_save = 0;
            }
            echo $_POST['code_type'].' - '.$code_type_save_temp.' - '.$code_type_save;
        }else{
            $code_type_save = 0;
        }

        if (isset($_POST['lithernet_id'])){
            $lithernet_id_save = cleanNumber($_POST['lithernet_id']);
        }else{
            $lithernet_id_save = 0;
        }

        if (isset($_POST['target_type'])){
            $target_type_save_temp = cleanText($_POST['target_type']);
            if ($target_type_save_temp == "broadcast"){
                $target_type_save = 0;
            }
            if ($target_type_save_temp == "device"){
                $target_type_save = 1;
            }
            if ($target_type_save_temp == "group"){
                $target_type_save = 2;
            }
            if ($target_type_save_temp == "scene"){
                $target_type_save = 4;
            }
            if ($target_type_save_temp == "none"){
                $target_type_save = 255;
            }
        }else{
            $target_type_save = 0;
        }

        if (isset($_POST['target_id'])){
            $target_id_save = cleanNumber($_POST['target_id']);
        }else{
            $target_id_save = 0;
        }

        if (isset($_POST['mode'])){
            $mode_save = cleanSite($_POST['mode']);
        }else{
            $mode_save = 0;
        }

        $db_link = mysqli_connect($db_host, $db_user, $db_password, $db_database);

        if($db_link === false){
            die("ERROR: Could not connect. " . mysqli_connect_error());
        }

        if ($mode_save == "update"){
            $sql6 = "update qrcodes set code_type='".mysqli_real_escape_string($db_link, $code_type_save)."', lithernet_id='".mysqli_real_escape_string($db_link, $lithernet_id_save)."', target_type='".mysqli_real_escape_string($db_link, $target_type_save)."', target_id='".mysqli_real_escape_string($db_link, $target_id_save)."' where code='".mysqli_real_escape_string($db_link, $code_save)."';";
            $result6 = mysqli_query($db_link, $sql6);
        }else if($mode_save == "insert"){
            $sql6 = "insert into qrcodes (code, code_type, lithernet_id, target_type, target_id) values ('".mysqli_real_escape_string($db_link, $code_save)."', '".mysqli_real_escape_string($db_link, $code_type_save)."', '".mysqli_real_escape_string($db_link, $lithernet_id_save)."', '".mysqli_real_escape_string($db_link, $target_type_save)."', '".mysqli_real_escape_string($db_link, $target_id_save)."');";
            $result6 = mysqli_query($db_link, $sql6);
            $sql6 = "insert into qrcode_level (code) values ('".mysqli_real_escape_string($db_link, $code_save)."');";
            $result6 = mysqli_query($db_link, $sql6);
        }

        mysqli_close($db_link);
        $SITE = 'list';
    }
 
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <meta name="description" content="" />
        <meta name="author" content="" />
        <title>Casambi QR-Code Scanner - Admin Area</title>
        <!-- Favicon-->
        <link rel="icon" type="image/x-icon" href="assets/favicon.ico" />
        <!-- Font Awesome icons (free version)-->
        <script src="js/all.js"></script>
        <!-- Google fonts-->
        <link href="css/fonts.css" rel="stylesheet" type="text/css" />
        <!-- Core theme CSS (includes Bootstrap)-->
        <link href="css/style.css" rel="stylesheet" />
    </head>
    <body id="page-top">
        <!-- Navigation-->
        <nav class="navbar bg-secondary text-uppercase fixed-top" id="mainNav">
            <div class="container">
                <?php 
                echo'<a class="navbar-brand" href="admin.php"><img class="startscreen-avatar" style="height: 3rem" src="assets/img/only_logo_white.svg" alt="Lithernet" /></a>';
                if ($logged_in == 1){
                    echo'<a class="nav-link py-3 px-0 px-lg-3 rounded text-white" href="admin.php?site=list">List Codes</a>';
                }
                ?>
            </div>
        </nav>
        <!-- startscreen-->
        <?php
        if ($logged_in == 0){
            echo '<section class="startscreen bg-primary text-white text-center">
                <div class="container d-flex align-items-center flex-column">
                    <!-- startscreen Avatar Image-->
                    <img class="startscreen-avatar mb-5" src="assets/img/logo_white.svg" alt="Lithernet" />
                    <!-- Icon Divider-->
                    <div class="divider-custom divider-light">
                        <div class="divider-custom-line"></div>
                        <div class="divider-custom-icon"><i class="fas fa-key"></i></div>
                        <div class="divider-custom-line"></div>
                    </div>
                    <!-- Form-->
                    <div class="text-center mt-4">
                        <form action="admin.php?site=login" method="post">
                            <input class="btn btn-xl btn-outline-light" type="text" name="username" id="username" autocomplete="username" placeholder="Username" value="" maxlength="20"><br><br>
                            <input class="btn btn-xl btn-outline-light" type="password" name="current-password" id="current-password" autocomplete="current-password" placeholder="Password" value="" maxlength="20"><br><br>
                            <input class="btn btn-xl btn-outline-light" type="Submit" name="login" value="login">
                        </form>
                    </div>
                </div>
            </section>';
        }
        ?>
        <!-- login-->
        <?php
        if (($SITE == '')&&($logged_in == 1)){
            echo '<section class="startscreen bg-primary text-white text-center">
                <div class="container d-flex align-items-center flex-column">
                    <!-- startscreen Avatar Image-->
                    <img class="startscreen-avatar mb-5" src="assets/img/logo_white.svg" alt="Lithernet" />
                    <!-- Icon Divider-->
                    <div class="divider-custom divider-light">
                        <div class="divider-custom-line"></div>
                        <div class="divider-custom-icon"><i class="fas fa-qrcode"></i></div>
                        <div class="divider-custom-line"></div>
                    </div>
                    <!-- startscreen Subheading-->
                    <p class="startscreen-subheading font-weight-light mb-0">Casambi QR-Code Scanner</p><br><br>
                    <p class="startscreen-subheading font-weight-light mb-0">Admin-Area</p>
                </div>
            </section>';
        }
        ?>
        <!-- List Section-->
        <?php
        if (($SITE == 'list')&&($logged_in == 1)){
            echo '<section class="startscreen bg-primary text-white mb-0" id="scan">
                <div class="container">
                    <h2 class="page-section-heading text-center text-uppercase text-white">List Code</h2>
                    <div class="divider-custom divider-light">
                        <div class="divider-custom-line"></div>
                        <div class="divider-custom-icon"><i class="fas fa-table-list"></i></div>
                        <div class="divider-custom-line"></div>
                    </div>
                </div>    
                <div class="text-center mt-4">
                    <a class="btn btn-xl btn-outline-light" href="admin.php?site=add"><i class="fas fa-plus"></i> Add Code</a><br><br>
                </div>
                <div class="container" id="codes_table">
                    <table style="margin: 0 auto; min-width: 60vw;">
                        <tr style="border-bottom: medium solid #ffffff;">
                            <th>Code</th>
                            <th>Type</th>
                            <th>Lithernet ID</th>
                            <th>Target Type</th>
                            <th>Target ID</th>
                            <th>Edit</th>
                            <th>Print</th>
                            <th>Delete</th>
                        </tr>';

            $db_link = mysqli_connect($db_host, $db_user, $db_password, $db_database);

            if($db_link === false){
                die("ERROR: Could not connect. " . mysqli_connect_error());
            }
            
            $sql3 = "select * from qrcodes;";
            $result3 = mysqli_query($db_link, $sql3);
            if ($result3) {
                $firstentry = 1;
                while ($data3 = mysqli_fetch_object($result3)){
                    $code =  $data3->code;
                    $code_type =  $data3->code_type;
                    $lithernet_id = $data3->lithernet_id;
                    $target_type = $data3->target_type;
                    $target_id = $data3->target_id;

                    if ($firstentry){
                        echo'<tr>';
                        $firstentry = 0;
                    }else{
                        echo'<tr style="border-top: thin solid #ffffff;">';
                    }
                    echo'<td>'.$code.'</td>';

                    switch($code_type){
                        case 1:
                            echo '<td>Level</td>';
                            break;
                        case 2:
                            echo '<td>Tc</td>';
                            break;
                        case 3:
                            echo '<td>RGBW</td>';
                            break;
                        default:
                            echo '<td>None</td>';
                            break;
                    }

                    echo'<td>'.$lithernet_id.'</td>';

                    switch($target_type){
                        case 0:
                            echo '<td>Broadcast</td>';
                            break;                        
                        case 1:
                            echo '<td>Device</td>';
                            break;
                        case 2:
                            echo '<td>Group</td>';
                            break;
                        case 4:
                            echo '<td>Scene</td>';
                            break;
                        default:
                            echo '<td>None</td>';
                            break;
                    }

                    echo'<td>'.$target_id.'</td> 
                        <td><a class="nav-link py-3 px-0 px-lg-3 rounded text-white" href="admin.php?site=edit&code='.$code.'"><i class="fas fa-pen"></i></a></td>                           
                        <td><a class="nav-link py-3 px-0 px-lg-3 rounded text-white" href="admin.php?site=print&code='.$code.'"><i class="fas fa-print"></i></a></td>
                        <td><a class="nav-link py-3 px-0 px-lg-3 rounded text-white" href="admin.php?site=delete&code='.$code.'"><i class="fas fa-trash-can"></i></a></td>   
                        </tr>';
                }
            }
            
            mysqli_close($db_link);

            echo'</table>                    
                </div>
            </section>';
        }
        ?>
        <!-- Edit Section-->
        <?php
        if (($SITE == 'edit')&&($logged_in == 1)){
            if ($NEW_CODE == ""){
                if (isset($_GET['code'])){
                    $code_edit = cleanCode($_GET['code']);
                }else{
                    $code_edit = '';
                } 
                echo "code: ".$code_edit.";\n";

                $db_link = mysqli_connect($db_host, $db_user, $db_password, $db_database);

                if($db_link === false){
                    die("ERROR: Could not connect. " . mysqli_connect_error());
                }

                $sql4 = "select * from qrcodes where code='".$code_edit."';";
                $result4 = mysqli_query($db_link, $sql4);
                while ($data4 = mysqli_fetch_object($result4)){
                    $code =  $data4->code;
                    $code_type =  $data4->code_type;
                    $lithernet_id = $data4->lithernet_id;
                    $target_type = $data4->target_type;
                    $target_id = $data4->target_id;            
                }
                $mode = "update";
                mysqli_close($db_link);
            }else{
                $code = $NEW_CODE;
                $code_type =  0;
                $lithernet_id = 255;
                $target_type = 0;
                $target_id = 0;  
                $mode = "insert";
            }

            echo '<section class="startscreen bg-primary text-white mb-0" id="code">
                <div class="container">
                    <!-- code Section Heading-->
                    <h2 class="page-section-heading text-center text-uppercase text-white">Enter Code</h2>
                    <!-- Icon Divider-->
                    <div class="divider-custom divider-light">
                        <div class="divider-custom-line"></div>
                        <div class="divider-custom-icon"><i class="fas fa-keyboard"></i></div>
                        <div class="divider-custom-line"></div>
                    </div>
                    <!-- Form-->
                    <div class="text-center mt-4">
                        <form action="admin.php?site=save" method="post">
                            <input type="hidden" name="mode" id="mode" value="'.$mode.'"><br><br>
                            <table style="margin: 0 auto; min-width: 3vw;">
                            <tr><td><label for="code" style="padding-right: 1rem">Code:</label></td><td><input class="btn btn-xl btn-outline-light" type="text" name="code" id="code" readonly="readonly" value="'.$code.'" maxlength="10"></td></tr>';

                            switch($code_type){
                                case 1:
                                    echo '<tr><td><label for="code_type" style="padding-right: 1rem">Type:</label></td><td><select name="code_type" id="code_type" class="btn btn-xl btn-outline-light" style="width: 100%;"><option selected>Level</option><option>Tc</option><option>RGBW</option><option>None</option></select></td></tr>';
                                    break;
                                case 2:
                                    echo '<tr><td><label for="code_type" style="padding-right: 1rem">Type:</label></td><td><select name="code_type" id="code_type" class="btn btn-xl btn-outline-light" style="width: 100%;"><option>Level</option><option selected>Tc</option><option>RGBW</option><option>None</option></select></td></tr>';
                                    break;
                                case 3:   
                                    echo '<tr><td><label for="code_type" style="padding-right: 1rem">Type:</label></td><td><select name="code_type" id="code_type" class="btn btn-xl btn-outline-light" style="width: 100%;"><option>Level</option><option>Tc</option><option selected>RGBW</option><option>None</option></select></td></tr>';
                                    break;
                                default:
                                    echo '<tr><td><label for="code_type" style="padding-right: 1rem">Type:</label></td><td><select name="code_type" id="code_type" class="btn btn-xl btn-outline-light" style="width: 100%;"><option>Level</option><option>Tc</option><option>RGBW</option><option selected>None</option></select></td></tr>';
                                    break;
                            }

            echo '          <tr><td><label for="lithernet_id" style="padding-right: 1rem">Lithernet ID:</label></td><td><input class="btn btn-xl btn-outline-light" type="text" name="lithernet_id" id="lithernet_id" value="'.$lithernet_id.'" maxlength="10"></td></tr>';

                            switch($target_type){
                                case 0:
                                    echo '<tr><td><label for="target_type" style="padding-right: 1rem">Target Type:</label></td><td><select name="target_type" id="target_type" class="btn btn-xl btn-outline-light" style="width: 100%;"><option selected>Broadcast</option><option>Device</option><option>Group</option><option>Scene</option><option>None</option></select></td></tr>';
                                    break;                                
                                case 1:
                                    echo '<tr><td><label for="target_type" style="padding-right: 1rem">Target Type:</label></td><td><select name="target_type" id="target_type" class="btn btn-xl btn-outline-light" style="width: 100%;"><option>Broadcast</option><option selected>Device</option><option>Group</option><option>Scene</option><option>None</option></select></td></tr>';
                                    break;
                                case 2:
                                    echo '<tr><td><label for="target_type" style="padding-right: 1rem">Target Type:</label></td><td><select name="target_type" id="target_type" class="btn btn-xl btn-outline-light" style="width: 100%;"><option>Broadcast</option><option>Device</option><option selected>Group</option><option>Scene</option><option>None</option></select></td></tr>';
                                    break;
                                case 4:   
                                    echo '<tr><td><label for="target_type" style="padding-right: 1rem">Target Type:</label></td><td><select name="target_type" id="target_type" class="btn btn-xl btn-outline-light" style="width: 100%;"><option>Broadcast</option><option>Device</option><option>Group</option><option selected>Scene</option><option>None</option></select></td></tr>';
                                    break;
                                default:
                                    echo '<tr><td><label for="target_type" style="padding-right: 1rem">Target Type:</label></td><td><select name="target_type" id="target_type" class="btn btn-xl btn-outline-light" style="width: 100%;"><option>Broadcast</option><option>Device</option><option>Group</option><option>Scene</option><option selected>None</option></select></td></tr>';
                                    break;
                            }

            echo '          <tr><td><label for="target_id" style="padding-right: 1rem">Target ID:</label></td><td><input class="btn btn-xl btn-outline-light" type="text" name="target_id" id="target_id" value="'.$target_id.'" maxlength="10"></td></tr>
                            </table><br><br>
                            <input class="btn btn-xl btn-outline-light" type="Submit" name="save" value="save">
                        </form>
                    </div>
                </div>
            </section>';
        }
        ?>
        <!-- Print Section-->
        <?php
        if (($SITE == 'print')&&($logged_in == 1)){
            if (isset($_GET['code'])){
                $code_print = cleanCode($_GET['code']);
            }else{
                $code_print = '';
            }         

            include('functions/phpqrcode.php');
            $file = "temp/".$code_print.".png";
            QRcode::png($code_print, $file, QR_ECLEVEL_H, 4);
            echo '<section class="startscreen bg-primary text-white mb-0" id="code">
                <div class="container">
                    <!-- code Section Heading-->
                    <h2 class="page-section-heading text-center text-uppercase text-white">Download</h2>
                    <!-- Icon Divider-->
                    <div class="divider-custom divider-light">
                        <div class="divider-custom-line"></div>
                        <div class="divider-custom-icon"><i class="fas fa-download"></i></div>
                        <div class="divider-custom-line"></div>
                    </div>
                    <div class="text-center mt-4">
                        <img src="'.$file.'" /><br><br>
                        right click on image and save to file
                    </div>
                </div>
            </section>';
        }
        ?>
        <!-- Copyright Section-->
        <div class="copyright py-4 text-center text-white">
            <div class="container"><small>Copyright &copy; Licht Manufaktur Berlin GmbH 2022</small></div>
        </div>
    </body>
</html>