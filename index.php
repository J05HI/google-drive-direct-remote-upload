<?php
/**
 * Made with love by J05HI [https://github.com/J05HI]
 * Released under the MIT.
 *
 * Feel free to contribute!
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/GoogleApi.php';

if ('cli' !== PHP_SAPI) {
    throw new Exception('This application must be run on the command line. You need to modify it a bit to run over the browser :)');
}

/**
 * Get a chunk of a file.
 *
 * @param resource $handle
 * @param int      $chunkSize
 *
 * @return string
 */
function readFileChunk($handle, int $chunkSize) {
    $byteCount = 0;
    $giantChunk = '';
    
    while (!feof($handle)) {
        $chunk = fread($handle, 8192);
        $byteCount += strlen($chunk);
        $giantChunk .= $chunk;
        
        if ($byteCount >= $chunkSize) {
            return $giantChunk;
        }
    }
    
    return $giantChunk;
}

/**
 * Get remote file size.
 *
 * @param string $url
 *
 * @return int
 */
function remoteFileSize($url) {
    # Get all header information
    $data = get_headers($url, true);
    
    # Look up validity
    if (isset($data['Content-Length'])) {
        return (int)$data['Content-Length'];
    }
    
    return 0;
}

/**
 * Get remote mime type.
 *
 * @param string $url
 *
 * @return string
 */
function remoteMimeType($url) {
    $mimeTypes = require 'mimeTypes.php';
    $extension = pathinfo($url, PATHINFO_EXTENSION);
    
    if (isset($mimeTypes[$extension])) {
        return $mimeTypes[$extension];
    }
    
    return 'application/octet-stream';
}

/**
 * Upload a file to the google drive.
 */
try {
    $googleApi = new GoogleApi();
    $client = $googleApi->getClient();
    $service = new Google_Service_Drive($client);
    $getFile = new Google_Service_Drive_DriveFile();
    $url = 'http://download.thinkbroadband.com/1MB.zip';
    $getFile->name = 'Test File.zip';
    $chunkSizeBytes = 20 * 4 * 256 * 1024;
    
    # Call the API with the media upload, defer so it doesn't immediately return.
    $client->setDefer(true);
    $request = $service->files->create($getFile);
    
    # Get file mime type
    $mimeType = remoteMimeType($url);
    
    # Get file size
    $size = remoteFileSize($url);
    
    # Create a media file upload to represent our upload process.
    $media = new Google_Http_MediaFileUpload(
        $client,
        $request,
        $mimeType,
        null,
        true,
        $chunkSizeBytes
    );
    $media->setFileSize($size);
    
    # Upload the chunks. Status will be false until the process is complete.
    $status = false;
    $sizeUploaded = 0;
    
    $handle = fopen($url, 'rb');
    
    while (!$status && !feof($handle)) {
        # Read until you get $chunkSizeBytes from the file
        $chunk = readFileChunk($handle, $chunkSizeBytes);
        $chunkSizee = strlen($chunk);
        $sizeUploaded += $chunkSizee;
        $sizeMissing = $size - $sizeUploaded;
        $status = $media->nextChunk($chunk);
    }
    
    fclose($handle);
    
    # The final value of $status will be the data from the API for the object that has been uploaded.
    $uploadedFileId = '';
    
    if ($status !== false) {
        $uploadedFileId = $status['id'];
        print_r($uploadedFileId);
    } else {
        throw new Exception('Upload failed');
    }
} catch (Exception $e) {
    print 'An error occurred: ' . $e->getMessage();
}

///**
// * Get info of a drive file.
// */
//try {
//    $googleApi = new GoogleApi();
//    $client = $googleApi->getClient();
//    $service = new Google_Service_Drive($client);
//    $getFile = $service->files->get(
//        $uploadedFileId,
//        ['fields' => 'name, fileExtension, md5Checksum, size, webContentLink']
//    );
//    #print_r($getFile);
//} catch (Exception $e) {
//    print 'An error occurred: ' . $e->getMessage();
//}
//
///**
// * Add user by email to a drive file.
// */
//try {
//    $googleApi = new GoogleApi();
//    $client = $googleApi->getClient();
//    $service = new Google_Service_Drive($client);
//    $permission = new Google_Service_Drive_Permission();
//    $permission->setRole('reader');
//    $permission->setType('user');
//    $permission->setEmailAddress('joshuaott98@gmail.com');
//    $permission->setExpirationTime(new DateTime());
//
//    $createPermission = $service->permissions->create(
//        $uploadedFileId,
//        $permission
//    );
//    #print_r($createPermission);
//} catch (Exception $e) {
//    print 'An error occurred: ' . $e->getMessage();
//}
//
///**
// * List all files.
// */
//try {
//    $googleApi = new GoogleApi();
//    $client = $googleApi->getClient();
//    $service = new Google_Service_Drive($client);
//
//    $files = $service->files->listFiles();
//
//    print_r($files);
//} catch (Exception $e) {
//    print 'An error occurred: ' . $e->getMessage();
//}