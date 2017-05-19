<?php 
    session_start(); 
?>

<!DOCTYPE html>
<html>
    <head>
        <link rel="stylesheet" type="text/css" href="css/style.css">
        <script src="//ajax.googleapis.com/ajax/libs/jquery/1.6.4/jquery.min.js"></script>
        <script src="js/script.js"></script>
        
        <?php
            $style_path = 'css/style.css';
            $version = filemtime($style_path);
            echo "<link rel='stylesheet' href='$style_path?ver=$version'>";
        ?>
    </head>
    
    <body>
        <nav>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li id="click_new_album"><a href="#">
                    <?php
                        if (isset($_SESSION['logged_user'])) 
                            echo "<a href='#'>New Album";
                        else
                            echo "<a href='#'>New Album (must be logged in)";
                    ?>
                    </a></li>
                <li id="click_new_photo"><a href="#">
                    <?php
                        if (isset($_SESSION['logged_user'])) 
                            echo "<a href='#'>Add Photo";
                        else
                            echo "<a href='#'>Add Photo (must be logged in)";
                    ?>
                    </a>
                </li>
                <li id="login">
                    <?php
                        if (isset($_SESSION['logged_user'])) 
                            echo "<a href='index.php?link=logout'>Logout";
                        else
                            echo "<a href='#'>Login";
                    ?>
                    </a>
                </li>
                <li id="search">
                    <form method="post">
                        <input type="text" name="search" placeholder="Search">
                        <input type="submit" name="search_submit" value="Search">
                    </form>
                </li>
            </ul>
        </nav>
        
        <div class="content">
             <div class="new-album">
                 <form method="post">
                     <input type="text" name="album_name" placeholder="Album Name">
                     <input type="submit" name="submit_album" value="Add">
                 </form>
                 <button id="cancel_add_new_album">Cancel</button>
             </div>
            

            <div class="new-photo">
                <form method="post" enctype="multipart/form-data">
                    <input type="file" name="newphoto">
                    <input type="text" name="image_title" placeholder="Name"> <br>
                    <input type="text" name="credit" placeholder="Credit (optional)"> <br>
                    Add to pre-existing album:
                    
                        Pictures without albums (default)<br>
                        <?php
                            require_once 'php/config.php';
                            $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
                            $albums_result = $mysqli->query("SELECT * FROM Albums");
                            while ($album_row = $albums_result->fetch_assoc()) { 
                                $title = $album_row['Title'];
                                $id = $album_row['AlbumID'];
                                echo "<input type='checkbox' name='albums[]' value=$id>$title<br>";
                            }
                        ?>
                  
                    <br><br>
                    <input type="submit" name="submit_photo" value="Add Photo">
                </form>
                <button id="cancel_add_new_photo">Cancel</button>
            </div>

            <div class="login-box">
                <form method="post">
                    <input type="text" name="username" placeholder="Username">
                    <input type="text" name="password" placeholder="Password">
                    <input type="submit" name="login_button" value="Login">
                </form>
                <button id="cancel_login">Cancel</button>
            </div>

            <?php
                require_once 'php/config.php';
                $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
                $albums_result = $mysqli->query("SELECT * FROM Albums");
                $albums_result2 = $mysqli->query("SELECT * FROM Albums");
            
                //LOGIN
                if (isset($_POST['login_button']) && isset($_POST['username']) && isset($_POST['password'])) {
                    $username = htmlentities($_POST['username']);
                    $password = htmlentities($_POST['password']);
                    
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $valid_password = password_verify('blurryface', $hash);
                    
                    $result = $mysqli->query("SELECT * FROM users WHERE username = '$username'");
                    if ($result && $result->num_rows == 1) {
                        $row = $result->fetch_assoc();
                        $db_hashpassword = $row['hashpassword'];
                        
                        if (password_verify($password, $db_hashpassword)) {
                            $_SESSION['logged_user'] = $username;
                            header("Refresh:0");
                        }
                    }
                }
            
                //LOGOUT
                if (isset($_GET['link']) && $_GET['link']=='logout') {
                    echo 'logout';
                    
                        unset($_SESSION["logged_user"] );
                        unset( $_SESSION );
                        $_SESSION = array();
                        session_destroy();
                        header("Location:index.php");
                    
                }
            
                //DELETE ALBUM
                    if (isset($_GET['action'])) {
                        $action = $_GET['action'];
                        if ($action == 'delete') {
                            $albumID = $_GET['albumID'];
                            $albumID = filter_var($id, FILTER_VALIDATE_INT);
                            $mysqli->query("DELETE FROM `Albums` WHERE AlbumID=$albumID");
                            header("Location:index.php");
                        }
                    }

            
                //ADD NEW ALBUM
                if(isset($_POST['submit_album'])) {
                    if($_POST['album_name']) {
                        $name = htmlentities($_POST['album_name']);
                        $today = getdate();
                        $good_format = "$today[year]-$today[mon]-$today[mday]";
                        $query = "INSERT INTO `Albums` (`Title`, `Date_created`) VALUES ('$name', '$good_format')";
                        if(!$mysqli->query($query))     
                            echo "<script>alert('Unable to create new album');</script>";
                        else
                            header("Refresh:0");
                    }
                }
            
                //ADD NEW PHOTO
                if (isset($_POST['submit_photo'])) {  
                    if (!empty($_FILES['newphoto'])) { 
                        $photo = $_FILES['newphoto']; 
                        $originalName = $photo['name'];
                        if (!$photo['error']) {
                            move_uploaded_file($photo['tmp_name'], "img/$originalName");
                            $_SESSION['photos'][] = $photo['name'];
                            
                            $title = isset($_POST['image_title']) ? $_POST['image_title'] : "Untitled";
                            $credit = isset($_POST['credit']) ? $_POST['credit'] : "N/A";
                            $albumIDs = isset($_POST['albums']) ? $_POST['albums'] : array();
                            $addImageQuery = "INSERT INTO `Images`(`Title`, `File_name`, `Credit`) VALUES ('$title', '$originalName', '$credit')";
                            if ($mysqli->query($addImageQuery)) { //if image successfully added
                                $imageIDresult = $mysqli->query("SELECT * FROM Images WHERE File_name='$originalName'");
                                
                                if (count($albumIDs) > 0) {
                                    while ($imageRow = $imageIDresult->fetch_assoc())
                                        $imageID = $imageRow['ImageID'];
                                    
                                    foreach ($albumIDs as $albumID) {
                                        $togetherResult = $mysqli->query("INSERT INTO Together (ImageID, AlbumID) VALUES ('$imageID', '$albumID')");
                                        if (!$togetherResult)
                                            echo "<script>alert('Unable to add new photo in the album');<script>";
                                    }
                                }
                            } else 
                                echo "<script>alert('Unable to add new photo');<script>";
                        }
                    } else 
                        echo "<script>alert('Unable to add new photo');<script>";
                }
            
                //SEARCH
                if (isset($_POST['search_submit']) && isset($_POST['search'])) {
                    $searched = $_POST['search'];
                    echo "Results for '$searched' (exact AlbumID/ImageID and partial search for all other fields)";

                    // SEARCH ALBUMS
                    $albumIDs = array();
                    $albumsResult = $mysqli->query("SELECT * FROM `Albums` WHERE AlbumID='%$searched%' OR Title LIKE '%$searched%' OR Date_created LIKE '%$searched%' OR Date_modified LIKE '%$searched%'");
                    if ($albumsResult) {
                        while ($album = $albumsResult->fetch_assoc()) {
                            $albumIDs[] = $album['AlbumID'];
                            displayAlbum($album, $mysqli);
                        }
                    }

                    $imagesResult = $mysqli->query("SELECT * FROM `Images` WHERE `ImageID`='%$searched%' OR `Title` LIKE '%$searched%' OR `File_name` LIKE '%$searched%' OR `Credit` LIKE '%$searched%'");
                    if ($imagesResult) {
                        // SEARCHED IMAGES WITHOUT ALBUMS
                        $togetherResult = $mysqli->query("SELECT Images.ImageID FROM `Images` LEFT OUTER JOIN `Together` ON Images.ImageID=Together.ImageID WHERE Together.ImageID IS NULL");
                        $picsWithoutAlbums = array();
                        if ($togetherResult) {
                            $imageIDsNoAlbum = array();
                            while ($row = $togetherResult->fetch_assoc()) {
                                $imageIDsNoAlbum[] = $row['ImageID']; //GOT IDS OF IMAGES WITH NO ALBUMS
                            }
                        }
                        
                        // GET THE ALBUMS OF THE SEARCHED IMAGES
                        $togetherArray = array();
                        $imagesResult2 = $mysqli->query("SELECT * FROM `Images` WHERE `ImageID`='%$searched%' OR `Title` LIKE '%$searched%' OR `File_name` LIKE '%$searched%' OR `Credit` LIKE '%$searched%'");
                        if ($imagesResult2) {
                            while ($image = $imagesResult2->fetch_assoc()) { // goes through all desired images
                                $imageID = $image['ImageID'];
                                $togetherResult = $mysqli->query("SELECT * FROM Together WHERE ImageID=$imageID"); 
                                if ($togetherResult) { 
                                    while ($row = $togetherResult->fetch_assoc()) { // goes through that image with all albums linked to it
                                        $albumID = $row['AlbumID'];
                                        $title = $image["Title"];
                                        if (array_key_exists($albumID, $togetherArray)) { 
                                            $imgs = $togetherArray[$albumID]; 
                                            $imgs[] = $image;
                                            $togetherArray[$albumID] = $imgs; //append the image to that album 
                                        } else 
                                            $togetherArray[$albumID] = array(); //or make a new album
                                    }
                                }
                            }
                        }
                        
                        $displayedNoAlbum = false;
                        $newAlbums = array();
                        $albumToImageMap = array();
                        echo "<ul class='photos'>";
                        while ($image = $imagesResult->fetch_assoc()) {
                            if (in_array($image['ImageID'], $imageIDsNoAlbum)) { // DISPLAYS IMAGES WITHOUT ALBUMS
                                if (!$displayedNoAlbum)
                                    printAlbumTitle("Pictures without albums");
                                displayImage($image);
                                $displayedNoAlbum = true;
                            }
                        }
                        
                        foreach ($togetherArray as $albumID=>$imgArray) { // PRINTS ALBUM WITH CORRESPONDING SEARCHED IMAGE
                            $album = getAlbumFromID($albumID, $mysqli);
                            $title = $album["Title"];
                            if (!in_array($albumID, $albumIDs)) {
                                $album = getAlbumFromID($albumID, $mysqli);
                                printAlbumTitle($album['Title']);
                                foreach ($imgArray as $image) {
                                    displayImage($image);
                                }
                            }
                        }
                        echo '</ul>';
                    } else {
                        echo $mysqli->error;
                    }
                }
                
                if (!isset($_POST['search'])) {
                    //CLICK TO GO TO ALBUM PAGE
                    echo 'Click on these to go to a dedicated page for the album:<br>';
                    while ($album_row = $albums_result2->fetch_assoc()) { 
                        $title = $album_row['Title'];
                        $id = $album_row["AlbumID"];
                        $link = "album.php?album_id=$id";
                        echo "<h3><a href=$link>$title</a></h3>";
                    }
                    echo "<br><br>";
                    
                    
                    //DELETE IMAGE
                    if (isset($_SESSION['logged_user'])) {
                        echo "Delete Image (manual refresh for changes to show up on drop-down): "
                        echo "<form method='post'>";
                           echo "<select name='delete_image'>";
                                $result = $mysqli->query("SELECT * FROM `Images`");

                                if ($result) {
                                    while ($row = $result->fetch_assoc()) {
                                        $id = $row["ImageID"];
                                        $name = $row['Title'];
                                        echo "<option value=$id>$name</option>";
                                    }
                                }

                            echo "</select>";
                            echo "<input type='submit' name='delete_button' value='Delete Image'>";
                        echo "</form>";

                        if (isset($_POST['delete_button'])) {
                            $id = $_POST['delete_image'];
                            $mysqli->query("DELETE FROM `Images` WHERE ImageID=$id");
                       }
                    }
                    
                    //PICTURES WITHOUT ALBUMS
                    $result = $mysqli->query("SELECT Images.ImageID, Title, File_name FROM `Images` LEFT OUTER JOIN Together ON Together.ImageID = Images.ImageID WHERE Together.ImageID IS NULL");
                    $dictionary = array();
                    while ($row = $result->fetch_assoc()) {
                        $dictionary[] = $row;
                    }
                    if (count($dictionary) > 0) {
                        echo '<div class="album">';
                        echo "<h2><a href='#'>Pictures without albums</a></h2>";
                        echo "<ul class='photos'>";
                        foreach ($dictionary as $index => $row) {  
                            displayImage($row);
                        }
                        echo "</ul>";
                        echo '</div>';
                    }

                    //ALBUMS
                    while ($album_row = $albums_result->fetch_assoc()) { 
                        displayAlbum($album_row, $mysqli);
                    }
                    
                }
            
                function getAlbumFromID($id, $mysqli) {
                    $result = $mysqli->query("SELECT * FROM Albums WHERE AlbumID=$id");
                    if ($result) {
                        while ($row = $result->fetch_assoc()) 
                            return $row;
                    } 
                    else return null;
                }
            
                function printAlbumTitle($title) {
                    echo "<div class='album'>";
                    echo "<h2><a href='#'>$title</a></h2>"; //PRINTS ALBUM TITLE
                }
            
                function displayAlbum($album_row, $mysqli) {
                        printAlbumTitle($album_row['Title']);

                        //GETTING ALL TOGETHER ELEMENTS WITH SAME ALBUM ID
                        $albumID = $album_row['AlbumID'];
                        $together_result = $mysqli->query("SELECT * FROM Together WHERE AlbumID=$albumID");

                        $together_dictionary = array();
                        while ($together_row = $together_result->fetch_assoc()) {
                            $together_dictionary[] = $together_row;
                        }

                        //IMAGES IN ALBUM          
                        $images_result = $mysqli->query("SELECT * FROM Images");
                        echo "<ul class='photos'>";
                        while ($image_row = $images_result->fetch_assoc()) {
                            $putIn = false;
                            foreach ($together_dictionary as $index => $array) {
                                if ($array['ImageID'] == $image_row['ImageID']) {
                                    $putIn = true;
                                }
                            }

                            if ($putIn) 
                                displayImage($image_row);
                        }
                        echo "</ul>";
                        echo '</div>';
                    
                }
                   
            function displayImage($row) {
                    $file_path = "img/" . $row['File_name'];
                    $image_title = $row['Title'];
                    $id = $row['ImageID'];
                    $link = "image.php?image_id=$id";
                    echo "<a href=$link>";
                    echo "<li><img src=$file_path alt=$image_title>";
                    echo "<span class='info'><span>$image_title</span></span></li>";
                    echo "</a>";
                }

//                function displayImage($row) {
//                    $file_path = "img/" . rawurlencode($row['File_name']);
//                    $image_title = $row['Title'];
//                    echo "<li><img src=$file_path alt=$image_title>";
//                    echo "<span class='info'><span>$image_title</span></span></li>";
//                }
            
                function displayImageWithoutli($image) {
                    $file_path = "img/" . rawurlencode($image['File_name']);
                    $image_title = $image['Title'];
                    echo "<div class='full-image'>";
                    echo "<img src=$file_path alt=$image_title>";
                    echo "</div>";
                }
            ?>
        </div>
    </body>
    
<!--
    PHOTO CREDITS:
    Funny Sign 1: https://s-media-cache-ak0.pinimg.com/236x/42/5d/21/425d21e4ae7f65c6ed8e44b1ee354d32.jpg
    Funny Sign 2: https://img.buzzfeed.com/buzzfeed-static/static/campaign_images/webdr06/2013/8/14/18/22-chinese-signs-that-got-seriously-lost-in-trans-1-29860-1376517755-3_big.jpg
    Funny Sign 3: http://www.michaeltyler.co.uk/wp-content/uploads/2015/11/Chinglish-signs3.jpg
    Funny Sign 4: https://s-media-cache-ak0.pinimg.com/originals/82/2c/8f/822c8f8b70cb6132374c3d6be06a6a5e.jpg
    Funny Sign 5: https://s-media-cache-ak0.pinimg.com/736x/31/e8/de/31e8dea8bf97a6ce64f050e7bfffe78b.jpg
    Funny Sign 6: http://21onuv2o3diqcdqccz3o9c12iv.wpengine.netdna-cdn.com/wp-content/uploads/2007/02/_89_229872911_aaee98013a-tm.jpg
    Funny Sign 7: http://www.rd.com/wp-content/uploads/sites/2/2011/11/16-silly-signs-from-around-the-world-02-sl.jpg
    Unwritten: https://upload.wikimedia.org/wikipedia/en/b/b9/Natasha_Bedingfield_Unwritten.jpg
    Gameboy: http://www.11points.com/images/debuted2000s/gameboyadvance.jpg
    Blockbuster: https://s-media-cache-ak0.pinimg.com/originals/20/d1/e2/20d1e22117fa4672351754a91659a696.jpg
    DDR: https://img.buzzfeed.com/buzzfeed-static/static/enhanced/webdr03/2013/4/1/16/enhanced-buzz-13197-1364849725-20.jpg
    SillyBandz: https://a.dilcdn.com/bl/wp-content/uploads/sites/8/2013/11/Screen-Shot-2013-11-07-at-9.22.36-PM.png
    Slideshow: https://img.buzzfeed.com/buzzfeed-static/static/2016-02/11/23/campaign_images/webdr12/21-things-that-will-give-early-2000s-tweens-sever-2-17792-1455249826-0_dblbig.jpg
    iPod Nano: https://img.buzzfeed.com/buzzfeed-static/static/enhanced/webdr02/2013/4/1/17/enhanced-buzz-2692-1364850188-9.jpg
    Dublin 1: https://goo.gl/images/Y4cVQb
    Dublin 2: https://goo.gl/images/W5TsXh
    Dublin 3: https://goo.gl/images/W5TsXh
    Dublin 4: https://goo.gl/images/IqD8Zb
    Dublin 5: https://goo.gl/images/w8iKJK
    Dublin 6: https://goo.gl/images/FQQYJA
    Dublin 7: https://goo.gl/images/KEM6A5
    Dublin 8: https://goo.gl/images/GeDcsi
-->
    
</html>