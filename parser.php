<?php

define('MAX_BYTES', 1024 * 1024 * 100);
define('IS_POST_FIELD', 'post');
define('IS_FILE_FIELD', 'file');
$boundary = "------WebKitFormBoundaryjGBXQ4DjtaUCh5Ea";
$fp = fopen('fcgi.txt', 'r');
$posts = "";
$files = [];
$content_type = false;
$mimetype = "";
$key_name = "";
$filename = "";
$in_header = false;
$got_content = false;

while (!feof($fp)) {
    if ($key_name && !$in_header) {
        $buffer = stream_get_line($fp, MAX_BYTES, "\r\n" . $boundary);
        $got_content = true;
    } else {
        $buffer = stream_get_line($fp, MAX_BYTES, "\r\n");
    }

    if ($in_header && strlen($buffer) == 0) {
        $in_header = false;
    } else {
        if ($got_content) {
            if ($content_type === IS_POST_FIELD) {
                $posts .= $key_name . "=" . $buffer;
            } elseif ($content_type === IS_FILE_FIELD) {
                $ext = substr($filename, strrpos($filename, '.'));
                $tmp_path = sys_get_temp_dir().'/php'.substr(sha1(rand()), 0, 12).$ext;
                $err = file_put_contents($tmp_path, $buffer);
                $files[$key_name] = [
                    'type' => $mime_type ?: 'application/octet-stream',
                    'name' => $filename,
                    'tmp_name' => $tmp_path,
                    'error' => ($err === false) ? true : 0,
                    'size' => filesize($tmp_path),
                ];
                $filename = "";
                $mime_type = "";
            }
            $key_name = "";
            $content_type = false;
            $got_content = false;
        } elseif (strpos($buffer, 'Content-Disposition') === 0) {
            $in_header = true;
            if (preg_match('/name=\"([^\"]*)\"/', $buffer, $matches)) {
                $key_name = $matches[1];
            }
            if ($is_file = preg_match('/filename=\"([^\"]*)\"/', $buffer, $matches)) {
                $filename = $matches[1];
            }
            $content_type = $is_file ? IS_FILE_FIELD : IS_POST_FIELD;
        } elseif (strpos($buffer, 'Content-Type') === 0) {
            $in_header = true;
            if (preg_match('/Content-Type: (.*)?/', $buffer, $matches)) {
                $mime_type = trim($matches[1]);
            }
        }
    }
}

var_dump($posts);
var_dump($files);
