<?php 
    session_start(); 
?>

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

            <div class="full-image">
                <?php
                    require_once 'php/config.php';
                    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

                    $id = $_GET['image_id'];
                    $id = filter_var($id, FILTER_VALIDATE_INT);

                    if (is_int($id)) {
                        $image_result = $mysqli->query("SELECT * FROM Images WHERE ImageID=$id");

                        if ($image_result) {
                            while ($image = $image_result->fetch_assoc()) {
                                displayImage($image);
                                $title =  $image['Title'];
                                $credit = $image['Credit'];
                                echo "<p class='deets'>";
                                echo "<b>" . $title;
                                echo " (" . $image['File_name'] . ")</b><br>";
                                echo "<b>Credit: </b>" . $credit . "<br>";
                                echo "<b>Located:</b> ";
                                
                                $imageID = $image['ImageID'];
                                $togetherResult = $mysqli->query("SELECT * FROM Together WHERE ImageID=$imageID"); 
                                if ($togetherResult) { 
                                    $count = $togetherResult->num_rows;
                                    while ($row = $togetherResult->fetch_assoc()) { // goes through that image with all albums linked to it
                                        $albumID = $row['AlbumID'];
                                        $album = getAlbumFromID($albumID, $mysqli);
                                        echo $album["Title"];
                                        if ($count > 1) echo ", ";
                                        $count--;
                                    }
                                } else
                                    echo '(no albums)';
                                
                                
                                
                                echo "</p>";
                                
                                if (isset($_SESSION['logged_user']))
                                    echo "<p class='deets'><a href='image.php?image_id=$id&mode=edit'>EDIT</a></p>";
                                    //DELETE ALBUM
                                    if (isset($_GET['mode'])) {
                                        $action = $_GET['mode'];
                                        if ($action == 'edit') {
                                            echo "<form method='post'>";
                                                echo "<input id='center' type='text' name='title' placeholder=$title>";
                                                $placeCreds = $credit == "" ? "Credit" : $credit;
                                                echo "<input id='center' type='text' name='credit' placeholder=$placeCreds>";
                                                echo "<input id='center' type='submit' name='edit_button' value='Save Changes'>";
                                            echo "</form>";
                                            
                                            if (isset($_POST['edit_button'])) {
                                                if (isset($_POST['title']))
                                                    $title = htmlentities($_POST['title']);
                                                if (isset($_POST['credit']))
                                                    $credit = htmlentities($_POST['credit']);
                                                echo $title . "<br>";
                                                echo $credit . "<br>";
                                                echo $id;
                                                $result = $mysqli->query("UPDATE `Images` SET `Title`='$title', `Credit`='$credit' WHERE ImageID='$id'");
                                                header("Location:image.php?image_id=$id");
    
                                            }
                                        }
                                    }
                    
                            }
                        }
                        
                    
                    }
                
                    function displayImage($row) {
                        $file_path = "img/" . rawurlencode($row['File_name']);
                        $image_title = $row['Title'];
                        echo "<img src=$file_path alt=$image_title>";
                    }
                
                    function getAlbumFromID($id, $mysqli) {
                        $result = $mysqli->query("SELECT * FROM Albums WHERE AlbumID=$id");
                        if ($result) {
                            while ($row = $result->fetch_assoc()) 
                                return $row;
                        } 
                        else return null;
                    }
                ?>
            </div>
        </div>
    </body>
</html>
