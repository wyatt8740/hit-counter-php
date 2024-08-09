<?php


/* 
   1. open sqlite database
   2. get page visit count (get page name from parameter to php script)
     'SELECT visits FROM `pagestats` WHERE pagestats.pagename = "' . realpath($path) . '";'
   2. check if user agent corresponds to a known bot/crawler.
      a. if it is not a known bot/crawler:
             1. bump up visit count for realpath($page) by one
             2. write new number to database
      b. if it is a known bot/crawler:
             1. do nothing
   3. close database
   4. generate a hit count image by stitching together digit images
     
*/
header("Content-type:image/png");

function chr_filename($chr) {
    return ( $_SERVER['DOCUMENT_ROOT'] . '/counter/digits/' . $chr . '.png' );
}

/* function to make our image representing a visitor count number */
function generate_hits_image($count) {
    /*
      1. convert number to a string
      2. split string into an array
      3. iterate over each, fetching appropriate digit image
    */

    $char_width = 30; /* pixel width of a single character image representation */
    $char_height = 27;
    
    $count_str = strval($count);
    $string_len = strlen($count_str);

    $dest_img_width = $char_width * $string_len;
    $dest_img_height = $char_height;


    $dest_img = imagecreatetruecolor($dest_img_width,$dest_img_height);
    imagesavealpha($dest_img, true);
    imagealphablending($dest_img, false);
    $transparent=imagecolorallocatealpha($dest_img, 255,255,255,127);
    imagefill($dest_img, 0,0,$transparent);
    /* #808080 rgb with full transparency */
    
    $insert_x = 0;

    foreach(str_split($count_str) as $chr) {
        $ins_char = imagecreatefrompng( chr_filename($chr) );
        imagecopy($dest_img, $ins_char, $insert_x, 0, 0, 0, $char_width, $char_height);
        $insert_x = $insert_x + $char_width - 3;
    }
    return imagepng($dest_img);
}

function update_visits($db_conn, $page_name, $visit_count) {
    /* 'UPDATE `pages` SET `hit_count` = $visit_count WHERE `page_name` = ?' */

    $prepped_req_stmt = mysqli_prepare($db_conn, 'UPDATE `pages` SET `hit_count` = ? WHERE `page_name` = ?');
    mysqli_stmt_bind_param($prepped_req_stmt, "is", $visit_count, $page_name);
    mysqli_stmt_execute($prepped_req_stmt);
}

function get_visits($db_conn, $page_name) {
    $prepped_req_stmt = mysqli_prepare($db_conn, 'SELECT `hit_count` FROM `pages` WHERE `page_name` = ?;');
    mysqli_stmt_bind_param($prepped_req_stmt, "s", $page_name);
    mysqli_stmt_execute($prepped_req_stmt);
    $result = mysqli_stmt_get_result($prepped_req_stmt);
    $visit_count = mysqli_fetch_row($result)[0];
    return $visit_count;
}

function db_connect() {
    /* yeah, yeah. i know. bad wyatt. */
    $db_conn = mysqli_connect('localhost', 'hitcount', 'PASSWORD_HERE', 'page_visits');
    if (!$db_conn) {
        die("Connection failed: " . mysqli_connect_error());
    }
    return $db_conn;
}


$db_conn = db_connect();

/* page id to look up in database */
$page_name = $_GET['page'];

/* get visit count */
$visit_count = get_visits($db_conn, $page_name);

/* increment number and write back */
$visit_count = $visit_count + 1;
update_visits($db_conn, $page_name, $visit_count);

/* close connection */
mysqli_close($db_conn);

generate_hits_image($visit_count);
/* generate_hits_image(1234567);*/

?>