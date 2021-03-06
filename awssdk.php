<?php
/*
 * jQuery File Upload Plugin Server PHP for S3 Amazon
 *
 * Copyright 2012, Roberto Colonello
 * http://www.parsec.it
 *
 * Licensed under the MIT license:
 * http://www.opensource.org/licenses/MIT
 */
 
require_once 'aws-sdk-php/sdk.class.php';
$s3 = new AmazonS3();


function getFileInfo($bucket, $fileName) {
    global $s3;
    $fileArray = "";
    $size = $s3->get_object_filesize($bucket, $fileName);
    $furl = "http://" . $bucket . ".s3.amazonaws.com/" . $fileName;
    $fileArray['name'] = $fileName;
    $fileArray['size'] = $size;
    $fileArray['url'] = $furl;
    $fileArray['thumbnail'] = $furl;
    $fileArray['delete_url'] = "server/php/index.php?file=" . $fileName;
    $fileArray['delete_type'] = "DELETE";
    return $fileArray;
}

function getListOfContents($bucket, $prefix="") {
    global $s3;
    if ($prefix=="") {
      $contents = $s3->get_object_list($bucket);
    } else {
    	$contents = $s3->get_object_list($bucket, array("prefix" => $prefix));
    }
    $resultArray = "";
    for ($i = 0;$i < count($contents);$i++) {
        $resultArray[] = getFileInfo($bucket, $contents[$i]);
    }
    return $resultArray;
}

function uploadFiles($bucket, $prefix="") {
    global $s3;
    if (isset($_REQUEST['_method']) && $_REQUEST['_method'] === 'DELETE') {
        return "";
    }
    $upload = isset($_FILES['files']) ? $_FILES['files'] : null;
    $info = array();
    if ($upload && is_array($upload['tmp_name'])) {
        foreach($upload['tmp_name'] as $index => $value) {
            $fileTempName = $upload['tmp_name'][$index];
            $fileName = (isset($_SERVER['HTTP_X_FILE_NAME']) ? $_SERVER['HTTP_X_FILE_NAME'] : $upload['name'][$index]);
            $fileName = $prefix.str_replace(" ", "_", $fileName);
            $response = $s3->create_object($bucket, $fileName, array('fileUpload' => $fileTempName, 'acl' => AmazonS3::ACL_PUBLIC, 'meta' => array('keywords' => 'example, test'),));
            if ($response->isOK()) {
                $info[] = getFileInfo($bucket, $fileName);
            } else {
                //     echo "<strong>Something went wrong while uploading your file... sorry.</strong>";
                
            }
        }
    } else {
        if ($upload || isset($_SERVER['HTTP_X_FILE_NAME'])) {
            $fileTempName = $upload['tmp_name'];
            $fileName = (isset($_SERVER['HTTP_X_FILE_NAME']) ? $_SERVER['HTTP_X_FILE_NAME'] : $upload['name']);
            $fileName =  $prefix.str_replace(" ", "_", $fileName);
            $response = $s3->create_object($bucket, $fileName, array('fileUpload' => $fileTempName, 'acl' => AmazonS3::ACL_PUBLIC, 'meta' => array('keywords' => 'example, test'),));
            if ($response->isOK()) {
                $info[] = getFileInfo($bucket, $fileName);
            } else {
                //     echo "<strong>Something went wrong while uploading your file... sorry.</strong>";
                
            }
        }
    }
    header('Vary: Accept');
    $json = json_encode($info);
    $redirect = isset($_REQUEST['redirect']) ? stripslashes($_REQUEST['redirect']) : null;
    if ($redirect) {
        header('Location: ' . sprintf($redirect, rawurlencode($json)));
        return;
    }
    if (isset($_SERVER['HTTP_ACCEPT']) && (strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)) {
        header('Content-type: application/json');
    } else {
        header('Content-type: text/plain');
    }
    return $info;
}

function deleteFiles($bucket, $prefix="") {
    global $s3;
    $file_name = $prefix.(isset($_REQUEST['file']) ? basename(stripslashes($_REQUEST['file'])) : null);
    $s3->delete_object($bucket, $file_name);
    $success = "";
    
    header('Content-type: application/json');
    return $success;
}
?>
