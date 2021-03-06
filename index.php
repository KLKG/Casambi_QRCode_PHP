<?php
    include 'config/config.php';
    include 'functions/web_helper.php';
    include 'functions/casambi.php';

    if (isset( $_GET['site'])){
        $SITE = cleanSite($_GET['site']);
    }else{
        $SITE = '';
    }

    if($SITE == 'control'){
        if (isset( $_POST['scan_code'])){
            $CODE = cleanCode($_POST['scan_code']);
        }else{
            $CODE = '';
        }

        if (isset( $_POST['run'])){
            $RUN = cleanNumber($_POST['run']);
        }else{
            $RUN = 0;
        }

        if (isset( $_POST['level'])){
            $LEVEL = cleanNumber($_POST['level']);
        }else{
            $LEVEL = 0;
        }

        if (isset( $_POST['red'])){
            $RED = cleanNumber($_POST['red']);
        }else{
            $RED = 0;
        }

        if (isset( $_POST['green'])){
            $GREEN = cleanNumber($_POST['green']);
        }else{
            $GREEN = 0;
        }

        if (isset( $_POST['blue'])){
            $BLUE = cleanNumber($_POST['blue']);
        }else{
            $BLUE = 0;
        }

        if (isset( $_POST['white'])){
            $WHITE = cleanNumber($_POST['white']);
        }else{
            $WHITE = 0;
        }

        if (isset( $_POST['tc'])){
            $TC = cleanNumber($_POST['tc']);
        }else{
            $TC = 0;
        }

        $db_link = mysqli_connect($db_host, $db_user, $db_password, $db_database);

        if($db_link === false){
            die("ERROR: Could not connect. " . mysqli_connect_error());
        }

        $sql = "select * from qrcodes where code='".mysqli_real_escape_string($db_link, $CODE)."';";
        $result = mysqli_query($db_link, $sql);
        if (!$result) {
            $code_type =  0;
            $lithernet_id = 255;
            $target_type = 255;
            $target_id = 255;
        }else{
            while ($data = mysqli_fetch_object($result)){
                $code_type =  $data->code_type;
                $lithernet_id = $data->lithernet_id;
                $target_type = $data->target_type;
                $target_id = $data->target_id;
            }
        }

        if($RUN == 1){
            $sql2 = "update qrcode_level set level='".mysqli_real_escape_string($db_link, $LEVEL)."', red='".mysqli_real_escape_string($db_link, $RED)."', green='".mysqli_real_escape_string($db_link, $GREEN)."', blue='".mysqli_real_escape_string($db_link, $BLUE)."', white='".mysqli_real_escape_string($db_link, $WHITE)."', tc='".mysqli_real_escape_string($db_link, $TC)."' where code='".mysqli_real_escape_string($db_link, $CODE)."';";
            $result2 = mysqli_query($db_link, $sql2);    
            
            if(!($sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP))){
                $errorcode = socket_last_error();
                $errormsg = socket_strerror($errorcode);
                die("Couldn't create socket: [$errorcode] $errormsg \n");
            }
            if ($operation_mode != "demo"){
                socket_set_option($sock, SOL_SOCKET, SO_BROADCAST, 1); 
                $message = buildcasambiString($code_type, $lithernet_id, $target_type, $target_id, $LEVEL, $RED, $GREEN, $BLUE, $WHITE, $TC);
                socket_sendto($sock, $message, 100 , 0 , $lithernet_base_ip , $lithernet_base_port);
                socket_close($sock);
            }
        }else{
            $sql3 = "select * from qrcode_level where code='".mysqli_real_escape_string($db_link, $CODE)."';";
            $result3 = mysqli_query($db_link, $sql3);
            while ($data3 = mysqli_fetch_object($result3)){
                $LEVEL =  $data3->level;
                $RED = $data3->red;
                $GREEN = $data3->green;
                $BLUE = $data3->blue;
                $WHITE = $data3->white;
                $TC = $data3->tc;
            }          
        }

        mysqli_close($db_link);
    }
 
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <meta name="description" content="" />
        <meta name="author" content="" />
        <title>Casambi QR-Code Scanner</title>
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
            <div class="container" style="justify-content: flex-start">
                <a class="navbar-brand" href="index.php"><img class="startscreen-avatar" style="height: 3rem" src="assets/img/only_logo_white.svg" alt="Lithernet" /></a>
                <a class="nav-link py-3 px-lg-5 rounded text-white" href="index.php?site=scan"> Scan </a>
                <a class="nav-link py-3 px-lg-5 rounded text-white" href="index.php?site=code"> Enter Code </a>
            </div>
        </nav>
        <!-- startscreen-->
        <?php
        if ($SITE == ''){
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
                    <p class="startscreen-subheading font-weight-light mb-0">Casambi QR-Code Scanner</p>
                </div>
            </section>';
        }
        ?>
        <!-- Scan Section-->
        <?php
        if ($SITE == 'scan'){
            echo '<section class="startscreen bg-primary text-white mb-0" id="scan">
                <div class="container">
                    <h2 class="page-section-heading text-center text-uppercase text-white">Scan Code</h2>
                    <div class="divider-custom divider-light">
                        <div class="divider-custom-line"></div>
                        <div class="divider-custom-icon"><i class="fas fa-camera"></i></div>
                        <div class="divider-custom-line"></div>
                    </div>
                </div>    
                <div class="container" style="width: 45vw" id="reader"></div>
                <div class="text-center mt-4">
                <form action="index.php?site=control" method="post" id="scanform">
                    <table style="margin: 0 auto; min-width: 3vw;">
                        <tr><td><label for="scan_code" style="padding-right: 1rem">Result:</label></td><td><input class="btn btn-xl btn-outline-light" type="text" id="scan_code" name="scan_code" readonly="readonly" value=""></td></tr>
                    </table>
                    <br><br>
                    <input class="btn btn-xl btn-outline-light" type="Submit" name="send" value="send">
                </form>
                </div>
            </section>';
        }
        ?>
        <!-- Code Section-->
        <?php
        if ($SITE == 'code'){
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
                        <form action="index.php?site=control" method="post">
                            <input class="btn btn-xl btn-outline-light" type="text" name="scan_code" id="scan_code" value="" maxlength="10"><br><br>
                            <input class="btn btn-xl btn-outline-light" type="Submit" name="send" value="send">
                        </form>
                    </div>
                </div>
            </section>';
        }
        ?>
        <!-- control Section-->
        <?php
        if ($SITE == 'control'){
            echo '<section class="startscreen bg-primary text-white mb-0" id="control">
                <div class="container">
                    <!-- About Section Heading-->
                    <h2 class="page-section-heading text-center text-uppercase text-white">control - '.$CODE.'</h2>
                    <!-- Icon Divider-->
                    <div class="divider-custom divider-light">
                        <div class="divider-custom-line"></div>
                        <div class="divider-custom-icon"><i class="fas fa-sliders"></i></div>
                        <div class="divider-custom-line"></div>
                    </div>
                    <!-- Form-->
                    <div class="text-center mt-4">';
            switch ($code_type) {
                case 0: //Empty
                    echo "No valid code or control type found.";
                    break;
                case 1://Level
                    echo'<form action="index.php?site=control" method="post">
                            <input type="hidden" name="scan_code" id="scan_code" value="'.$CODE.'"><br><br>
                            <input type="hidden" name="run" id="run" value="1"><br><br>
                            <table style="margin: 0 auto; min-width: 3vw;">
                                <tr><td><label for="level" style="padding-right: 1rem">Level:</label></td><td><input type="range" sytle="width: 21%" id="level" name="level" min="0" max="255" step="1" value="'.$LEVEL.'" oninput="this.nextElementSibling.value = this.value"><output style="padding-left: 1rem" id="sl_level" name="sl_level">'.$LEVEL.'</output></td></tr>
                            </table><br><br>
                            <input class="btn btn-xl btn-outline-light" type="Submit" name="send" value="send">
                        </form>';
                    break;
                case 2://TC
                    echo'<form action="index.php?site=control" method="post">
                            <input type="hidden" name="scan_code" id="scan_code" value="'.$CODE.'"><br><br>
                            <input type="hidden" name="run" id="run" value="1"><br><br>
                            <table style="margin: 0 auto; min-width: 3vw;">
                                <tr><td><label for="level" style="padding-right: 1rem">Level:</label></td><td><input type="range" sytle="width: 21%" id="level" name="level" min="0" max="255" step="1" value="'.$LEVEL.'" oninput="this.nextElementSibling.value = this.value"><output style="padding-left: 1rem" id="sl_level" name="sl_level">'.$LEVEL.'</output></td></tr>
                                <tr><td>&nbsp;</td><td>&nbsp;</td></tr>
                                <tr><td><label for="tc" style="padding-right: 1rem">Tc:</label></td><td><input type="range" sytle="width: 21%" id="tc" name="tc" min="0" max="255" step="1" value="'.$TC.'" oninput="this.nextElementSibling.value = this.value"><output style="padding-left: 1rem" for="tc">'.$TC.'</output></td></tr>
                            </table><br><br>
                            <input class="btn btn-xl btn-outline-light" type="Submit" name="send" value="send">
                        </form>';
                    break;
                case 3://RGBW
                    echo'<form action="index.php?site=control" method="post">
                            <input type="hidden" name="scan_code" id="scan_code" value="'.$CODE.'"><br><br>
                            <input type="hidden" name="run" id="run" value="1"><br><br>
                            <table style="margin: 0 auto; min-width: 3vw;">
                                <tr><td><label for="level" style="padding-right: 1rem">Level:</label></td><td><input type="range" sytle="width: 21%" id="level" name="level" min="0" max="255" step="1" value="'.$LEVEL.'" oninput="this.nextElementSibling.value = this.value"><output style="padding-left: 1rem" id="sl_level" name="sl_level">'.$LEVEL.'</output></td></tr>
                                <tr><td>&nbsp;</td><td>&nbsp;</td></tr>
                                <tr><td><label for="red" style="padding-right: 1rem">Red:</label></td><td><input type="range" sytle="width: 21%" id="red" name="red" min="0" max="255" step="1" value="'.$RED.'" oninput="this.nextElementSibling.value = this.value"><output style="padding-left: 1rem" for="red">'.$RED.'</output></td></tr>
                                <tr><td><label for="green" style="padding-right: 1rem">Green:</label></td><td><input type="range" sytle="width: 21%" id="green" name="green" min="0" max="255" step="1" value="'.$GREEN.'" oninput="this.nextElementSibling.value = this.value"><output style="padding-left: 1rem" for="green">'.$GREEN.'</output></td></tr>
                                <tr><td><label for="blue" style="padding-right: 1rem">Blue:</label></td><td><input type="range" sytle="width: 21%" id="blue" name="blue" min="0" max="255" step="1" value="'.$BLUE.'" oninput="this.nextElementSibling.value = this.value"><output style="padding-left: 1rem" for="blue">'.$BLUE.'</output></td></tr>
                                <tr><td><label for="white" style="padding-right: 1rem">White:</label></td><td><input type="range" sytle="width: 21%" id="white" name="white" min="0" max="255" step="1" value="'.$WHITE.'" oninput="this.nextElementSibling.value = this.value"><output style="padding-left: 1rem" for="white">'.$WHITE.'</output></td></tr>
                            </table><br><br>
                            <input class="btn btn-xl btn-outline-light" type="Submit" name="send" value="send">
                        </form>';
                    break;                    
            }


            echo'</div>   
                </div>
            </section>';
        }
        ?>
        <!-- Copyright Section-->
        <div class="copyright py-4 text-center text-white">
            <div class="container"><small>Copyright &copy; Licht Manufaktur Berlin GmbH 2022</small></div>
        </div>

        <?php
        if ($SITE == 'scan'){
            echo'<script type="text/javascript" src="js/html5-qrcode.min.js"></script>';
        }
        ?>

        <?php
        if ($SITE == 'scan'){
            echo'<script type="text/javascript">
                function onScanSuccess(decodedText, decodedResult) {
                    // handle the scanned code as you like, for example:
                    console.log(`Code matched = ${decodedText}`, decodedResult);
                    document.getElementById("scan_code").value = decodedText;
                    document.getElementById("scanform").submit();
                }

                // Square QR box with edge size = 70% of the smaller edge of the viewfinder.
                let qrboxFunction = function(viewfinderWidth, viewfinderHeight) {
                    let minEdgePercentage = 0.7; // 70%
                    let minEdgeSize = Math.min(viewfinderWidth, viewfinderHeight);
                    let qrboxSize = Math.floor(minEdgeSize * minEdgePercentage);
                    return {
                        width: qrboxSize,
                        height: qrboxSize
                    };
                }

                let html5QrcodeScanner = new Html5QrcodeScanner(
                    "reader",
                    { fps: 10, qrbox: qrboxFunction },
                    /* verbose= */ false);
                html5QrcodeScanner.render(onScanSuccess);
            </script>';
        }
        ?>
    </body>
</html>