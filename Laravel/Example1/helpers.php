<?php

/**
 * @param $files
 * Checks and uploading files
 * @param $isSeniorAdmin
 * @return array
 */
function complianceFileUploadHandler($files, $isSeniorAdmin) {
    $fileWhichMoveToS3 = [];
    $uploadedFiles = [];
    $fileUploadMessage = '';
    foreach ($files as $key => $file) {
        // Get binary data for file
        $fileTmpName = file_get_contents($_FILES['file']['tmp_name'][$key]);
        $getFirstBytes = substr($fileTmpName, 0, 8);

        // Check vulnerable in binary data
        preg_match('/<\?php |<\? /', $fileTmpName, $vulnerable);

        if (empty($vulnerable)) {
            // Check file type from binary data
            preg_match('/PDF/', $getFirstBytes, $matches, PREG_OFFSET_CAPTURE);

            if (count($matches)) {
                // Check that we have a file
                if($file->getError() === 0) {
                    $filename = $file->getClientOriginalName();
                    $ext = substr($filename, strrpos($filename, '.') + 1);
                    // Check if the file is PDF and it's size is less than 100M
                    if (($ext === "pdf") && $file->getClientmimeType() === 'application/pdf' && ($file->getSize() < (100 * 1024 * 1024))) {
                        // Determine the path to which we want to save this file
                        $date = new \DateTime();
                        $nowTime = $date->getTimestamp();
                        // Attempt to move the uploaded file to it's new place
                        $filename = $nowTime . '_' . $filename;
                        $uploadedPath = $isSeniorAdmin ? env('AWS_URL') . 'template-files/' : env('AWS_URL') . 'object-files/';
                        $filePath = $uploadedPath . $filename;
                        $fileWhichMoveToS3[] = [
                            'file' => base64_encode(file_get_contents($file)),
                            'file_path' => $filePath
                        ];
                        $fileObject = new \stdClass();
                        $fileObject->index = $key;
                        $fileObject->name = $filename;
                        $fileObject->size = $file->getSize();
                        $fileObject->file_path = $filePath;
                        array_push($uploadedFiles, $fileObject);
                    } else {
                        $fileUploadMessage = 'Sorry! Only PDF file under 100M are accepted for upload.';
                    }
                } else {
                    $fileUploadMessage = 'Sorry! No file uploaded.';
                }
            } else {
                $fileUploadMessage = 'Sorry! The file must be a PDF.';
            }
        } else {
            $fileUploadMessage = 'Sorry! This file contains vulnerable code.';
        }
    }

    return [
        'moved_files' => $fileWhichMoveToS3,
        'uploaded_files' => $uploadedFiles,
        'file_upload_message' => $fileUploadMessage
    ];
}