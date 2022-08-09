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

        if (isset( $_POST['scene'])){
            $SCENE = cleanNumber($_POST['scene']);
        }else{
            $SCENE = 0;
        }

        if (isset( $_POST['type'])){
            $TYPE = cleanSite($_POST['type']);
        }else{
            $TYPE = 0;
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

                if ($TYPE == "level"){
                    $message = buildcasambiString(1, $lithernet_id, $target_type, $target_id, $LEVEL, $RED, $GREEN, $BLUE, $WHITE, $TC, $SCENE);
                    socket_sendto($sock, $message, 100 , 0 , $lithernet_base_ip , $lithernet_base_port);
                }
                if ($TYPE == "tc"){
                    $message = buildcasambiString(2, $lithernet_id, $target_type, $target_id, $LEVEL, $RED, $GREEN, $BLUE, $WHITE, $TC, $SCENE);
                    socket_sendto($sock, $message, 100 , 0 , $lithernet_base_ip , $lithernet_base_port);
                }
                if($TYPE == "rgbw"){
                    $message = buildcasambiString(3, $lithernet_id, $target_type, $target_id, $LEVEL, $RED, $GREEN, $BLUE, $WHITE, $TC, $SCENE);
                    socket_sendto($sock, $message, 100 , 0 , $lithernet_base_ip , $lithernet_base_port);
                }
                if($TYPE == "scene"){
                    $message = buildcasambiString(4, $lithernet_id, $target_type, $target_id, $LEVEL, $RED, $GREEN, $BLUE, $WHITE, $TC, $SCENE);
                    socket_sendto($sock, $message, 100 , 0 , $lithernet_base_ip , $lithernet_base_port);
                }
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
        <link href="css/slider.css" rel="stylesheet" type="text/css" />
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
                    echo'   <br><br>
                            <table style="margin: 0 auto; min-width: 3vw;">
                                <tr><td>
                                    <form name="doit_level" action="index.php?site=control" method="post" target="hidden-form">
                                        <input type="hidden" name="scan_code" id="scan_code" value="'.$CODE.'">
                                        <input type="hidden" name="run" id="run" value="1">   
                                        <input type="hidden" name="type" id="type" value="level">     
                                        <div class="slider" id="slider1">
                                            <label for="level" style="padding-right: 1rem">Level:</label><br>
                                            <input type="range" name="level" id="level" min="0" max="254" value="'.$LEVEL.'"oninput="rangeValue1.innerText = this.value" onchange="document.forms[\'doit_level\'].submit()">
                                            <p id="rangeValue1">'.$LEVEL.'</p>
                                        </div>                        
                                    </form>
                                </td></tr>
                            </table><br><br>';
                    break;
                case 2://TC
                    echo'   <br><br>
                            <table style="margin: 0 auto; min-width: 3vw;">
                                <tr><td>
                                    <form name="doit_level" action="index.php?site=control" method="post" target="hidden-form">
                                        <input type="hidden" name="scan_code" id="scan_code" value="'.$CODE.'">
                                        <input type="hidden" name="run" id="run" value="1">
                                        <input type="hidden" name="type" id="type" value="level">
                                        <div class="slider" id="slider1">
                                            <label for="level" style="padding-right: 1rem">Level:</label><br>
                                            <input type="range" name="level" id="level" min="0" max="254" value="'.$LEVEL.'"oninput="rangeValue1.innerText = this.value" onchange="document.forms[\'doit_level\'].submit()">
                                            <p id="rangeValue1">'.$LEVEL.'</p>
                                        </div>
                                    </form>
                                </td></tr>
                                <tr><td>&nbsp;</td><td>&nbsp;</td></tr>
                                <tr><td>
                                    <form name="doit_tc" action="index.php?site=control" method="post" target="hidden-form">
                                        <input type="hidden" name="scan_code" id="scan_code" value="'.$CODE.'">
                                        <input type="hidden" name="run" id="run" value="1">
                                        <input type="hidden" name="type" id="type" value="tc">
                                        <div class="slider" id="slider2">
                                            <label for="tc" style="padding-right: 1rem">Tc:</label><br>
                                            <input type="range" name="tc" id="tc" min="0" max="254" value="'.$TC.'"oninput="rangeValue2.innerText = this.value" onchange="document.forms[\'doit_tc\'].submit()">
                                            <p id="rangeValue2">'.$TC.'</p>
                                        </div>
                                    </form>
                                </td></tr>
                            </table><br><br>';
                    break;
                case 3://RGBW
                    echo'   <br><br>
                            <table style="margin: 0 auto; min-width: 3vw;">
                                <tr><td>
                                    <form name="doit_level" action="index.php?site=control" method="post" target="hidden-form">
                                        <input type="hidden" name="scan_code" id="scan_code" value="'.$CODE.'">
                                        <input type="hidden" name="run" id="run" value="1">   
                                        <input type="hidden" name="type" id="type" value="level">     
                                        <div class="slider" id="slider1">
                                            <label for="level" style="padding-right: 1rem">Level:</label><br>
                                            <input type="range" name="level" id="level" min="0" max="254" value="'.$LEVEL.'"oninput="rangeValue1.innerText = this.value" onchange="document.forms[\'doit_level\'].submit()">
                                            <p id="rangeValue1">'.$LEVEL.'</p>
                                        </div>                        
                                    </form>
                                </td></tr>
                                <tr><td>&nbsp;</td><td>&nbsp;</td></tr>
                                <tr><td>
                                    <form name="doit_rgbw" action="index.php?site=control" method="post" target="hidden-form">
                                        <input type="hidden" name="scan_code" id="scan_code" value="'.$CODE.'">
                                        <input type="hidden" name="run" id="run" value="1">
                                        <input type="hidden" name="type" id="type" value="rgbw">
                                        <div class="slider" id="slider2">
                                            <label for="red" style="padding-right: 1rem">Red:</label><br>
                                            <input type="range" name="red" id="red" min="0" max="254" value="'.$RED.'"oninput="rangeValue2.innerText = this.value" onchange="document.forms[\'doit_rgbw\'].submit()">
                                            <p id="rangeValue2">'.$RED.'</p>
                                        </div>   
                                </td></tr>
                                <tr><td>&nbsp;</td><td>&nbsp;</td></tr>
                                <tr><td>
                                        <div class="slider" id="slider3">
                                            <label for="green" style="padding-right: 1rem">Green:</label><br>
                                            <input type="range" name="green" id="green" min="0" max="254" value="'.$GREEN.'"oninput="rangeValue3.innerText = this.value" onchange="document.forms[\'doit_rgbw\'].submit()">
                                            <p id="rangeValue3">'.$GREEN.'</p>
                                        </div>   
                                        </td></tr>
                                        <tr><td>&nbsp;</td><td>&nbsp;</td></tr>
                                        <tr><td>                                        
                                        <div class="slider" id="slider4">
                                            <label for="blue" style="padding-right: 1rem">Blue:</label><br>
                                            <input type="range" name="blue" id="blue" min="0" max="254" value="'.$BLUE.'"oninput="rangeValue4.innerText = this.value" onchange="document.forms[\'doit_rgbw\'].submit()">
                                            <p id="rangeValue4">'.$BLUE.'</p>
                                        </div>  
                                        </td></tr>
                                        <tr><td>&nbsp;</td><td>&nbsp;</td></tr>
                                        <tr><td>                                         
                                        <div class="slider" id="slider5">
                                            <label for="white" style="padding-right: 1rem">White:</label><br>
                                            <input type="range" name="white" id="white" min="0" max="254" value="'.$WHITE.'"oninput="rangeValue5.innerText = this.value" onchange="document.forms[\'doit_rgbw\'].submit()">
                                            <p id="rangeValue5">'.$WHITE.'</p>
                                        </div>                                                                                                                       
                                    </form>
                                </td></tr>
                            </table><br><br>';
                    break;                    
            }
            echo'</div>   
                </div>
            </section>';
        }
        ?>
        <iframe style="display:none" name="hidden-form"></iframe>   
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