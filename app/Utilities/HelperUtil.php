<?php
namespace App\Utilities;

use App\Utilities\CONST_DEF;
use Illuminate\Support\Facades\Session;

class HelperUtil{

   
    /*public static function setDatabaseConnection($connectionName)
    {
        // Set the connection name in the session
        Session::put('database_connection', $connectionName);
    }*/
    
    
   

    
    public static function decodeUserPassword($encrptedPassword){
        return strrev(base64_decode($encrptedPassword));
    }

    public static function encodeUserPassword($pass){
        return base64_encode(strrev($pass));
    }


    public static function aes_decrypt($encryptedText, $password="gmtech.co.tz") {
        try{
            // Split the encrypted text into the IV and the encrypted bytes
            list($ivHex, $encryptedHex) = explode(':', $encryptedText);
            $iv = hex2bin($ivHex);
            $encrypted = hex2bin($encryptedHex);

            // Derive the key from the password and salt
            $key = openssl_pbkdf2($password, 'grand-master', 32, 1000, 'sha1');

            // Decrypt the data using AES CBC mode
            $plainText = openssl_decrypt($encrypted, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);

            return $plainText;
        }catch(\Exception $e){
            return "";
        }
    }
    public static function aes_encrypt($plainText,  $password="gmtech.co.tz") {
        // Generate a random initialization vector (IV)
        $iv = openssl_random_pseudo_bytes(16);

        // Derive the key from the password and salt
        $key = openssl_pbkdf2($password, 'grand-master', 32, 1000, 'sha1');

        // Encrypt the data using AES CBC mode
        $encrypted = openssl_encrypt($plainText, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);

        // Combine the IV and encrypted data into a single string
        $ivHex = bin2hex($iv);
        $encryptedHex = bin2hex($encrypted);
        $result = $ivHex . ':' . $encryptedHex;

        return $result;
    }

    public static function isDateFormat($dateString, $format = 'Y-m-d') {
        $date = \DateTime::createFromFormat($format, $dateString);
        return $date && $date->format($format) === $dateString;
    }


    public static function generateRandomAccNo($length = 6) {
        $characters = '0123456789';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
    public static function generateRandomNo($length = 6) {
        $characters = '0123456789';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    public static function generateRandomString($length = 10) {
        $characters = '0123456789ABCDEFGHIJKLMN0PQRSTUVWXYZ';
        //$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
    public static function generateRandomAlphabeticalString($length = 10) {
        $characters = 'ABCDEFGHIJKLMNPQRSTUVWXYZ';
        //$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
    public static function generateRandomRefString($length = 4) {
        $date = date("Y-m-d");
        $shortYear = substr(date('y', strtotime($date)), -2);//short year
        $dtStr = $shortYear."". date("mdis")."".HelperUtil::generateRandomString($length);
        return $dtStr;
    }
    public static function generateRandomIDString($length = 8) {
        //$characters = '0123456789abc';
        $characters = '0123456789abcdefghijklmnpqrstuvwxyz';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    public static function generateRandomAccountNo($length = 10) {
        $characters = '0123456789';
        //$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
   
    public static function convert2FADateFormat($dateStr) {
        $date = \DateTime::createFromFormat('Y-m-d', $dateStr);
        return $date->format('m/d/Y');
    }

    public static function extract_error_message_4rm_fa_slim_api($html) {
        $start = strpos($html, '<strong>Message:</strong>');
        if ($start === false) {
            return '';
        }
        $start += 27;
        $end = strpos($html, '</div>', $start);
        if ($end === false) {
            return '';
        }
        return trim(substr($html, $start, $end - $start));
    }


    public static function get_suggested_first_loan_repayment_date($group_repayment_day_no = 1){
        $group_repayment_day_no = (int)$group_repayment_day_no;
        $today_day_number = date('N');
        // Calculate the difference between today's day and the repayment day
        $day_difference = ($group_repayment_day_no - $today_day_number + 7) % 7;
        $day_difference = $day_difference == 0 ? 7 : $day_difference;
        // Calculate the nearest future date for the repayment day
        $nearest_repayment_date = date('Y-m-d', strtotime("+$day_difference days"));
        //echo "Nearest repayment date: $nearest_repayment_date\n";
        return $nearest_repayment_date;
    }

    public static function getInsertStatementFromArray($table_name,$data, $columns_list, $group_count = 500)
    {
        $array_sql = [];

        // Chunk the data into groups of $group_count
        $dataGroups = array_chunk($data, $group_count);

        foreach ($dataGroups as $group) {
            $values = [];

            foreach ($group as $entry) {
                $values[] = '(' . implode(', ', array_map(function ($column) use ($entry) {
                    return "'" . addslashes($entry[$column]) . "'";
                }, $columns_list)) . ')';
            }

            $insertStatement = "INSERT INTO $table_name (" . implode(', ', $columns_list) . ") VALUES " . implode(', ', $values);
            $array_sql[] = $insertStatement;
        }

        return $array_sql;
    }

    public static function getDBTablePrefix(){
        $db_conn_name = session('database_connection');
        $dfltTblPrefix = config('database.connections.'.$db_conn_name.'.prefix');
        return $dfltTblPrefix;
    }

    public static function getLoanTransTypeGLCodeValueFromTransNo($trans_no, $field_name, $list = null)
    {
        //$list = json_decode($list,true);
        $list = $list->toArray();
        $affected_gl_code = null;

        for ($i=0; $i < sizeof($list); $i++) { 
            if($list[$i]['trans_no'] == (int)$trans_no){//echo "found";
                $affected_gl_code= $list[$i]['affected_gl_code'];
                break;
            }
        }
        return $affected_gl_code;
    }
    public static function getMercuryTransTypeGLCodeValueFromTransNo($trans_no, $field_name, $list = null)
    {
        //$list = json_decode($list,true);
        $list = $list->toArray();
        $affected_gl_code = null;

        for ($i=0; $i < sizeof($list); $i++) { 
            if($list[$i]['trans_no'] == (int)$trans_no){//echo "found";
                $affected_gl_code= $list[$i]['affected_gl_code'];
                break;
            }
        }
        return $affected_gl_code;
    }

    public static function getSessionStoredSysRef($nameField){
        //$sysPrefsInfo = Session::get('SYS_PREF_INFO');
        $sysPrefsInfo = session(CONST_DEF::$SESSION_ENTITY_PREF_DATA);
        $v = null;
        for($i = 0; $i < sizeof($sysPrefsInfo);$i++){
            if($nameField == $sysPrefsInfo[$i]['name']){
                $v = $sysPrefsInfo[$i]['value'];
                break;
            }
        }
        return $v;
    }


}




?>