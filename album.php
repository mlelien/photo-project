<?php session_start(); ?>

<html>
    <head>
        <link rel="stylesheet" type="text/css" href="css/style.css">
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
                    </a></li>
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
            <?php
                require_once 'php/config.php';
                $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            
                $id = $_GET['album_id'];
                $id = filter_var($id, FILTER_VALIDATE_INT);
            
                

                if (is_int($id)) {
                    $albums_result = $mysqli->query("SELECT * FROM Albums WHERE AlbumID=$id");

                    //ALBUMS
                    if ($albums_result->num_rows) {
                        while ($album_row = $albums_result->fetch_assoc()) { 
                            $album_title = $album_row['Title'];
                            echo "<h2>$album_title</h2>"; //PRINTS ALBUM TITLE

                            //GETTING ALL TOGETHER ELEMENTS WITH SAME ALBUM ID
                            $albumID = $album_row['AlbumID'];
                            $dateCreated = $album_row['Date_created'];
                            $dateModified = $album_row['Date_modified'];
                            $together_result = $mysqli->query("SELECT * FROM Together WHERE AlbumID=$albumID");

                            $together_dictionary = array();
                            while ($together_row = $together_result->fetch_assoc()) {
                                $together_dictionary[] = $together_row;
                            }
                            
                            if (isset($_SESSION['logged_user'])) {
                                echo "<h2><a href='index.php?action=delete&albumID=$id' style='color:red;'>CLICK HERE TO DELETE ALBUM. WARNING PERMANENT ACTION.</a></h2>";
                                
                                // REMOVE IMAGE
                                $imgs = goodImages($mysqli, $albumID);
                                echo 'Remove image from this album: ';
                                echo "<form method='post'>";
                                echo "<select name='remove_image'>";
                                    foreach ($imgs as $imageRow) {
                                        $imgID = $imageRow['ImageID'];
                                        $name = $imageRow['Title'];
                                        echo "<option value=$imgID>$name</option>";
                                    }
                                echo "</select>";
                                echo "<input type='submit' name='remove_button' value='Remove Image'>";
                                echo "</form>";
                                
                                if (isset($_POST['remove_button'])) {
                                    $id = htmlentities($_POST['remove_image']);
                                    $result = $mysqli->query("DELETE FROM `Together` WHERE ImageID=$id AND AlbumID=$albumID");
                                    updateDateModified($mysqli, $albumID);
                                    header("Refresh:0");
                               }
                                
                                //ADD IMAGE
                                echo 'Add image to this album: ';
                                echo "<form method='post'>";
                                echo "<select name='add_image'>";
                                
                                $result = $mysqli->query("SELECT DISTINCT ImageID FROM Together WHERE ImageID NOT IN ( SELECT ImageID from Together WHERE AlbumID = '$albumID')");
                                $goodImgs = array();
                                if ($result) {
                                    while ($imageIDs = $result->fetch_assoc()) {
                                        $id = $imageIDs['ImageID'];
                                        echo $id;
                                        $goodImgs[] = getImageFromID($id, $mysqli);
                                    }
                                } 

                                foreach ($goodImgs as $imageRow) {
                                    $id = $imageRow["ImageID"];
                                    $name = $imageRow['Title'];
                                    echo "<option value=$id>$name</option>";
                                }
                                
                                echo "</select>";
                                echo "<input type='submit' name='add_button' value='Add Image'>";
                                echo "</form>";
                                
                                if (isset($_POST['add_button'])) {
                                    $id = htmlentities($_POST['add_image']);
                                    $result = $mysqli->query("INSERT INTO `Together`(`AlbumID`, `ImageID`) VALUES ('$albumID','$id')");
                                    updateDateModified($mysqli, $albumID);
                                    header("Refresh:0");
                               }
                                
                                //EDIT ALBUM DETAILS
                                 echo "<form method='post'>";
                                    echo "<input type='text' name='title' placeholder='New Album Title'>";
                                    echo "<input type='submit' name='edit_title_button' value='Save Title'>";
                                echo "</form>";
                                
                                if (isset($_POST['edit_title_button'])) {
                                    if (isset($_POST['title']))
                                        $album_title = htmlentities($_POST['title']);
                                    
                                    $result = $mysqli->query("UPDATE `Albums` SET `Title`='$album_title' WHERE AlbumID='$albumID'");
                                    updateDateModified($mysqli, $albumID);
                                    header("Refresh:0");

                                }
                            }
                            
                            echo "Album created on: $dateCreated<br>";
                            echo "Album modified on: $dateModified";
                            //IMAGES IN ALBUM          
                            $images_result = $mysqli->query("SELECT * FROM Images");
                            echo "<div class='album-photos'>";
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
                            echo "</div>";
                            echo "</div>";
                        }
                    } else {
                        echo 'This is not a valid Album ID number';
                    }
                } else {
                    echo 'This is not a number at all';
                }
            
                function goodImages($mysqli, $albumID) {
                     $together_result = $mysqli->query("SELECT * FROM Together WHERE AlbumID=$albumID");
                    $together_dictionary = array();
                            while ($together_row = $together_result->fetch_assoc()) {
                                $together_dictionary[] = $together_row;
                            }

                    $imgs = array();
                    $images_result = $mysqli->query("SELECT * FROM Images");
                    while ($image_row = $images_result->fetch_assoc()) {
                        $putIn = false;
                        foreach ($together_dictionary as $index => $array) {
                            if ($array['ImageID'] == $image_row['ImageID']) {
                                $putIn = true;
                            }
                        }

                        if ($putIn) 
                            $imgs[] = $image_row;
                    }
                    return $imgs;
                }
            
                function getImageFromID($id, $mysqli) {
                    $result = $mysqli->query("SELECT * FROM Images WHERE ImageID=$id");
                    if ($result) {
                        while ($row = $result->fetch_assoc()) 
                            return $row;
                    } 
                    else return null;
                }

                function displayImage($row) {
                    $file_path = "img/" . $row['File_name'];
                    $image_title = $row['Title'];
                    $id = $row['ImageID'];
                    $link = "image.php?image_id=$id";
                    echo "<a href=$link>";
                    echo "<div class='image-container'>";
                        echo "<img src=$file_path alt=$image_title>";
                        echo "<span class='caption'><span>$image_title</span></span>";
                    echo "</div>";
                    echo "</a>";
                }
            
                function updateDateModified($mysqli, $albumID) {
                    $date = getdate();
                    $format = $date['year'] . "-" . $date['mon'] . "-" . $date['mday'];
                    $mysqli->query("UPDATE `Albums` SET `Date_modified`='$format' WHERE AlbumID='$albumID'");

                }
            ?>


        
        </div>
    </body>
</html>
