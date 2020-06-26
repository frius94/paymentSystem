<?php

function downloadFiles()
{
    $ftp_server = "ftp.haraldmueller.ch";
    $ftp_user_name = "schoolerinvoices";
    $ftp_user_pass = "Berufsschule8005!";
    $localFile = "rechnung";

// set up basic connection
    $conn_id = ftp_connect($ftp_server);

// login with username and password
    $login_result = ftp_login($conn_id, $ftp_user_name, $ftp_user_pass);

// check connection
    if ((!$conn_id) || (!$login_result)) {
        echo "FTP connection has failed!";
        echo "Attempted to connect to $ftp_server for user $ftp_user_name" . PHP_EOL;
        exit;
    } else {
        echo "Connected to $ftp_server, for user $ftp_user_name" . PHP_EOL;
    }

// download the file

    $fileList = ftp_nlist($conn_id, "/out/AP17dSavas/*.data");

    for ($i = 0; $i < count($fileList); $i++) {
        $localFile .= $i . ".data";
        if (ftp_get($conn_id, $localFile, $fileList[$i], FTP_BINARY)) {
            echo "FTP download was successful!" . PHP_EOL;
//        if (ftp_delete($conn_id, $fileList[$i])) {
//            echo "FTP delete was successful!";
//        } else {
//            echo "FTP delete has failed!";
//        }
        } else {
            echo "FTP download has failed!" . PHP_EOL;
        }

        $rows = str_getcsv(file_get_contents($localFile), "\n", ";");
        $result = [];
        foreach ($rows as $row) {
            $result[] = str_getcsv($row, ";");
        }
    }
// close the FTP stream
    ftp_close($conn_id);
    return array($result, $localFile);
}

function zip($source, $destination)
{
    if (!extension_loaded('zip') || !file_exists($source)) {
        return false;
    }

    $zip = new ZipArchive();
    if (!$zip->open($destination, ZIPARCHIVE::CREATE)) {
        return false;
    }

    $source = str_replace('\\', '/', realpath($source));

    if (is_dir($source) === true) {
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST);

        foreach ($files as $file) {
            $file = str_replace('\\', '/', $file);

            // Ignore "." and ".." folders
            if (in_array(substr($file, strrpos($file, '/') + 1), array('.', '..')))
                continue;

            $file = realpath($file);

            if (is_dir($file) === true) {
                $zip->addEmptyDir(str_replace($source . '/', '', $file . '/'));
            } else if (is_file($file) === true) {
                $zip->addFromString(str_replace($source . '/', '', $file), file_get_contents($file));
            }
        }
    } else if (is_file($source) === true) {
        $zip->addFromString(basename($source), file_get_contents($source));
    }
    return $zip->close();
}