$(document).ready(function() {
    // album click toggle
    $('.album a').click(function() {
        $(this).toggleClass('clicked');
        $(this).parent().parent().find(".photos").slideToggle(200);
    });
    
    
    //correct width and height for the text when hovered over of picture
    var $photos_li = $(".photos li img");
    var width = $photos_li.width();
    var height = $photos_li.height();

    $(".info").width(width);
    $(".info").height(height);
    

    // add new album toggle
    $('#click_new_album a').click(function() {
        var text = $("#click_new_album a").text();
        text = $.trim(text);
        
        if (!$(".content").find('.show-new-photo').length > 0 && text=="New Album")
            $(".new-album").toggleClass('show-new-album');
           
    });
    
    $("#cancel_add_new_album").click(function() {
        $(".new-album").toggleClass("show-new-album");
    });
    
    // add new photo toggle
    $('#click_new_photo a').click(function() {
        var text = $("#click_new_photo a").text();
        text = $.trim(text);
        
         if (!$(".content").find('.show-new-album').length > 0 && text=="Add Photo")
            $(".new-photo").toggleClass('show-new-photo');
    });
    
    $("#cancel_add_new_photo").click(function() {
        $(".new-photo").toggleClass("show-new-photo");
    });
    
    // open login box
    $("#login").click(function() {
        var text = $("#login a").text();
        text = $.trim(text);

        if (text == "Login")
            $(".login-box").toggleClass('show-login-box');
        
    });
    
    $("#cancel_login").click(function() {
        $(".login-box").toggleClass("show-login-box");
    });
});