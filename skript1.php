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

function uploadFiles($remote_path, $xmlName, $txtName)
{
    $ftp_server = "134.119.225.245";
    $ftp_user_name = "310721-297-zahlsystem";
    $ftp_user_pass = "Berufsschule8005!";

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

    // upload xml file
    if (ftp_put($conn_id, $remote_path . $xmlName, "./$xmlName", FTP_ASCII)) {
        echo "successfully uploaded\n";
    } else {
        echo "There was a problem while uploading\n";
    }

    // upload txt file
    if (ftp_put($conn_id, $remote_path . $txtName, "./$txtName", FTP_ASCII)) {
        echo "successfully uploaded\n";
    } else {
        echo "There was a problem while uploading\n";
    }
    ftp_close($conn_id);
}

list($result, $localFile) = downloadFiles();

$txt = createTXT($result);
file_put_contents(str_ireplace(".data", ".txt", $localFile), $txt);

$xml = createXML($result);
file_put_contents(str_ireplace(".data", ".xml", $localFile), $xml);

uploadFiles("/in/AP17dSavas/",
    str_ireplace(".data", ".xml", $localFile),
    str_ireplace(".data", ".txt", $localFile));

function createXML($data)
{
    $date = date('Ymd', strtotime($data[0][3]));
    $xml = new SimpleXMLElement("<XML-FSCM-INVOICE-2003A></XML-FSCM-INVOICE-2003A>");
    $interchange = $xml->addChild('INTERCHANGE');
    $interchange->addChild("IC-SENDER")->Pid = $data[1][1];
    $interchange->addChild("IC-RECEIVER")->Pid = $data[2][1];
    $interchange->addChild("IR-Ref");

    $invoice = $xml->addChild('INVOICE');
    $header = $invoice->addChild('HEADER');
    $functionFlags = $header->addChild('FUNCTION-FLAGS');
    $functionFlags->addChild('Confirmation-Flag');
    $functionFlags->addChild('Canellation-Flag');
    $messageReference = $header->addChild('MESSAGE-REFERENCE');
    $referenceDate = $messageReference->addChild('REFERENCE-DATE');
    $referenceDate->addChild('Reference-No', str_ireplace("Rechnung_", "", $data[0][0]));
    $referenceDate->addChild('Date', $date);
    $header->addChild('PRINT-DATE')->Date = $date;
    $reference = $header->addChild('REFERENCE');

    $invoiceReference = $reference->addChild('INVOICE-REFERENCE');
    $referenceDate2 = $invoiceReference->addChild('REFERENCE-DATE');
    $referenceDate2->addChild('Reference-No', str_ireplace("Rechnung_", "", $data[0][0]));
    $referenceDate2->addChild('Date', $date);

    $order = $reference->addChild('ORDER');
    $referenceDate3 = $order->addChild('REFERENCE-DATE');
    $referenceDate3->addChild('Reference-No', str_ireplace("Auftrag_", "", $data[0][1]));
    $referenceDate3->addChild('Date', $date);

    $reminder = $reference->addChild('REMINDER');
    $reminder->addAttribute('Which', 'MAH');
    $referenceDate4 = $reminder->addChild('REFERENCE-DATE');
    $referenceDate4->addChild('Reference-No');
    $referenceDate4->addChild('Date', $date);

    $otherReference = $reference->addChild('OTHER-REFERENCE');
    $otherReference->addAttribute('Type', 'ADE|CR|CT');
    $referenceDate5 = $otherReference->addChild('REFERENCE-DATE');
    $referenceDate5->addChild('Reference-No', '12345678');
    $referenceDate5->addChild('Date', $date);

    $biller = $header->addChild('BILLER');
    $biller->addChild('Tax-No', $data[1][6]);
    $biller->addChild('Doc-Reference')->addAttribute('Type', 'ESR-NEU|ESR-ALT|IPI');
    $biller->addChild('PARTY-ID')->Pid = $data[1][1];

    $nameAddress = $biller->addChild('NAME-ADDRESS');
    $nameAddress->addAttribute('Format', "COM");
    $name = $nameAddress->addChild('NAME');
    $name->addChild('Line-35', $data[1][3]);
    $name->addChild('Line-35', $data[1][4]);
    $name->addChild('Line-35', $data[1][5]);
    $name->addChild('Line-35');
    $name->addChild('Line-35');

    $street = $nameAddress->addChild('STREET');
    $street->addChild('Line-35');
    $street->addChild('Line-35');
    $street->addChild('Line-35');

    $nameAddress->addChild('City');
    $nameAddress->addChild('State');
    $nameAddress->addChild('Zip');
    $nameAddress->addChild('Country');

    $bankInfo = $biller->addChild('BANK-INFO');
    $bankInfo->addChild('Acct-No');
    $bankInfo->addChild('Acct-Name');
    $bankId = $bankInfo->addChild('BankId', '001996');
    $bankId->addAttribute('Type', 'BCNr-nat|BCNr-int|Bic|Esr|Iban');
    $bankId->addAttribute('Country', 'CH');

    $payer = $header->addChild('PAYER');
    $payer->addChild('PARTY-ID')->Pid = $data[1][1];

    $nameAddress2 = $payer->addChild('NAME-ADDRESS');
    $nameAddress2->addAttribute('Format', "COM");
    $name2 = $nameAddress2->addChild('NAME');
    $name2->addChild('Line-35', $data[2][2]);
    $name2->addChild('Line-35', $data[2][3]);
    $name2->addChild('Line-35', $data[2][4]);
    $name2->addChild('Line-35');
    $name2->addChild('Line-35');

    $street2 = $nameAddress2->addChild('STREET');
    $street2->addChild('Line-35');
    $street2->addChild('Line-35');
    $street2->addChild('Line-35');

    $nameAddress2->addChild('City');
    $nameAddress2->addChild('State');
    $nameAddress2->addChild('Zip');
    $nameAddress2->addChild('Country');

    $invoice->addChild('LINE-ITEM');
    $summary = $invoice->addChild('SUMMARY');
    $invoiceAmount = $summary->addChild('INVOICE-AMOUNT');
    $invoiceAmount->addChild('Amount', $data[3][5] + $data[4][5]);
    $vatAmount = $summary->addChild('VAT-AMOUNT');
    $vatAmount->addChild('Amount');

    $depositAmount = $summary->addChild('DEPOSIT-AMOUNT');
    $depositAmount->addChild('Amount');
    $referenceDate6 = $depositAmount->addChild('REFERENCE-DATE');
    $referenceDate6->addChild('Reference-No');
    $referenceDate6->addChild('Date', $date);

    $summary->addChild('EXTENDED-AMOUNT')->addAttribute('Type', 79);

    $tax = $summary->addChild('TAX');
    $taxBasis = $tax->addChild('TAX-BASIS');
    $taxBasis->addChild('Amount');
    $tax->addChild('Rate', 0)->addAttribute('Categorie', 'S|E');
    $tax->addChild('Amount');

    $paymentTerms = $summary->addChild('PAYMENT-TERMS');
    $basic = $paymentTerms->addChild('BASIC');
    $basic->addAttribute('Payment-Type', 'ESR|ESP|NPY');
    $basic->addAttribute('Terms-Type', '1|5');

    $terms = $basic->addChild('TERMS');
    $paymentPeriod = $terms->addChild('Payment-Period', str_ireplace("ZahlungszielInTagen_", "", $data[0][5]));
    $paymentPeriod->addAttribute('Type', 'CD|M');
    $paymentPeriod->addAttribute('On-Or-After', '1|3');
    $paymentPeriod->addAttribute('Reference-Day', '5|29');
    $terms->addChild('Date', $date);

    $discount = $paymentTerms->addChild('DISCOUNT');
    $discount->addAttribute('Terms-Type', 22);
    $discount->addChild('Discount-Percentage', 2.0);

    $terms2 = $discount->addChild('TERMS');
    $paymentPeriod2 = $terms2->addChild('Payment-Period', str_ireplace("ZahlungszielInTagen_", "", $data[0][5]));
    $paymentPeriod2->addAttribute('Type', 'CD|M');
    $paymentPeriod2->addAttribute('On-Or-After', '1|3');
    $paymentPeriod2->addAttribute('Reference-Day', '5|29');
    $terms2->addChild('Date', $date);

    $discount->addChild('Back-Pack-Container', 'xxyy')->addAttribute('Encode', 'Base64|Hex');

    return $xml->asXML();
}

function createTXT($data)
{
    $txt = "\n\n\n\n";
    $txt .= $data[1][3] . PHP_EOL;
    $txt .= $data[1][4] . PHP_EOL;
    $txt .= $data[1][5] . PHP_EOL . PHP_EOL;
    $txt .= $data[1][6] . PHP_EOL;
    $txt .= "\n\n\n\n";
    $txt .=
        $data[0][2] . ", den " . $data[0][3] .
        spaceHelper($data[0][2], "Uster", "                            ") . $data[2][2] . PHP_EOL;

    $txt .= "                                                 " . $data[2][3] . PHP_EOL;
    $txt .= "                                                 " . $data[2][4] . PHP_EOL . PHP_EOL;
    $txt .= "Kundennummer:      " . $data[1][2] . PHP_EOL;
    $txt .= "Auftragsnummer:    " . str_ireplace("Auftrag_", "", $data[0][2]) . PHP_EOL . PHP_EOL;
    $txt .= "Rechnung Nr        " . str_ireplace("Rechnung_", "", $data[0][0]) . PHP_EOL;
    $txt .= "-----------------------" . PHP_EOL;
    $txt .=
        " " . $data[3][1] . "   " . $data[3][2] .
        spaceHelper($data[3][2], "Einrichten E-Mailclients", "              ") . $data[3][3] .
        spaceHelper($data[3][4], "25.00", "      ") . $data[3][4] . "  CHF" .
        spaceHelper($data[3][5], "150.00", "      ") . $data[3][5] . "  " .
        str_ireplace("MWST_", "", $data[3][6]) . PHP_EOL;

    $txt .=
        " " . $data[4][1] . "   " . $data[4][2] .
        spaceHelper($data[4][2], "Einrichten E-Mailclients", "              ") . $data[4][3] .
        spaceHelper($data[4][4], "25.00", "      ") . $data[4][4] . "  CHF" .
        spaceHelper($data[4][5], "150.00", "      ") . $data[4][5] . "  " .
        str_ireplace("MWST_", "", $data[4][6]) . PHP_EOL;

    $txt .= "                                                             -----------" . PHP_EOL;
    $chfTotal = $data[3][5] + $data[4][5];
    $txt .=
        "                                                Total CHF" .
        spaceHelper(number_format((float)$chfTotal, 2, '.', ''), "1350.00", "        ") .
        number_format((float)$chfTotal, 2, '.', '') . PHP_EOL;

    $mwstTotal =
        ($data[3][5] * (str_ireplace("%", "", str_ireplace("MWST_", "", $data[3][6])) / 100)) +
        ($data[4][5] * (str_ireplace("%", "", str_ireplace("MWST_", "", $data[4][6])) / 100));

    $txt .=
        "                                                MWST  CHF           " .
        number_format((float)$mwstTotal, 2, '.', '');

    $txt .= "\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n";

    $dateIn30Days = date("d.m.Y", strtotime($data[0][3] . ' + 30 days'));

    $txt .=
        "Zahlungsziel ohne Abzug " . str_ireplace("ZahlungszielInTagen_", "", $data[0][5]) .
        " Tage (" . $dateIn30Days . ")" . PHP_EOL . PHP_EOL;
    $txt .= "Einzahlungsschein";
    $txt .= "\n\n\n\n\n\n\n\n\n\n\n";
    $txt .=
        "    " . number_format((float)$chfTotal, 2, ' . ', '') . "                    " .
        number_format((float)$chfTotal, 2, ' . ', '') . "     " . $data[2][2] . PHP_EOL;

    $txt .= "                                               " . $data[2][3] . PHP_EOL;
    $txt .= "0 00000 00000 00000                            " . $data[2][4] . PHP_EOL;
    return $txt;
}

function spaceHelper($textLength, $initialLength, $initialSpace)
{
    $textLength = mb_strlen($textLength);
    $initialLength = mb_strlen($initialLength);
    $initialSpace = mb_strlen($initialSpace);

    $diff = abs($textLength - $initialLength);
    $returnSpace = "";
    if (($textLength - $initialLength) < 0) {
        $resultSpace = $initialSpace + $diff;
    } else {
        $resultSpace = $initialSpace - $diff;
    }
    for ($i = 0; $i < $resultSpace; $i++) {
        $returnSpace .= " ";
    }
    return $returnSpace;
}