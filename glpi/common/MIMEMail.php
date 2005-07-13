<?php
/*
 ----------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2005 by the INDEPNET Development Team.
 
 http://indepnet.net/   http://glpi.indepnet.org
 ----------------------------------------------------------------------

 LICENSE

	This file is part of GLPI.

    GLPI is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    GLPI is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with GLPI; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 ------------------------------------------------------------------------
*/


/* A really good mail class -- written by Jesse Lang <jesselang.com>
* Features:
* Fully MIME-compliant (I hope!)
* Multiple message formats
* Multiple attachments
* all the bells and whistles you want! (I hope!)
* SMTP socket or local sendmail transport (maybe someday)
*
* If you make modifications that may be useful to others, please send
* them to <j |at| jgdataworks.com>.  I am especially looking for changes
* that fix any possible compatability problems with MTAs or MUAs, and any
* other features or options that are considered part of the e-mail standards.
*
* The (open source) community needs your help.  'Tis more blessed to give than
* to receive!  Get involved!  Go to http://www.sf.net/projects/twobit/ or
* http://sf.net to find projects that you can help with.  We all can benefit.
*/

/*  This program is free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  This program is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  You should have received a copy of the GNU General Public License
*  along with this program; if not, write to the Free Software
*  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/


define('DEBUG', FALSE);

define('MM_VERSION', '0.2');

define('MM_FILE', 1);
define('MM_STREAM', 2);


/******************************************************************************
* Compatibility Code
******************************************************************************
*/
if (!function_exists ("mime_content_type")) {
    function mime_content_type ($file) {
        return exec ("file -bi " . escapeshellcmd($file));
    }
}

if(!function_exists("stripos")) {
    function stripos($haystack, $needle, $offset=0) {
        return strpos(strtoupper($haystack), strtoupper($needle), $offset);
    }
}

if (!function_exists('file_get_contents')) {
    function file_get_contents($filename, $use_include_path = 0) {
        $file = @fopen($filename, 'rb', $use_include_path);
        if ($file) {
            if ($fsize = @filesize($filename)) {
                $data = fread($file, $fsize);
            } else {
                while (!feof($file)) {
                    $data .= fread($file, 1024);
                }
            }
            fclose($file);
            return $data;
        } else {
            return FALSE;
        }
        
    }
}

/******************************************************************************
* End - Compatibility Code
******************************************************************************
*/

class MIMEMail extends MIMEMailCommon {

    /* define unique parts of mail */
    var $Messages;
    var $Attachments;
    

    function MIMEMail($to = FALSE, $from = FALSE, $subject = FALSE, $message = FALSE, $attachments = FALSE) {
        $this->setHeader('X-Mailer', 'PHP/MIMEMail '.MM_VERSION);
	$this->setHeader('X-Powered', 'by GLPI');
        $this->Priority('Normal');
        //if($to) { $this->To(
        return $this;
    }
    
    function From($email, $name = FALSE) {
        $from = new MIMEMailAddress($email,$name);
        /* set the 'Reply-To' header by default */
        return $this->setHeader('From', $from) && $this->setHeader('X-Sender', $from) && $this->setHeader('Reply-To', $from);
    }
    
    function ReplyTo($email, $name = FALSE) {
        $replyto = new MIMEMailAddress($email,$name);
        return $this->setHeader('Reply-To', $replyto) && $this->setHeader('Return-Path', $replyto);
    }
    
    function To($email, $name = FALSE) {
        if(is_array($email)) {
            $addresses = $this->parseEmailAddresses($email);
            foreach($addresses as $e => $n) {
                $rv &= $this->To($e, $n);
            }
            return $rv;
        } else {
            return $this->appendHeader('To', new MIMEMailAddress($email,$name));
        }
    }
    
    function Cc($email, $name = FALSE) {
        if(is_array($email)) {
            $addresses = $this->parseEmailAddresses($email);
            foreach($addresses as $e => $n) {
                $rv &= $this->Cc($e, $n);
            }
            return $rv;
        } else {
            return $this->appendHeader('Cc', new MIMEMailAddress($email,$name));
        }
    }
    
    function Bcc($email, $name = FALSE) {
        if(is_array($email)) {
            $addresses = $this->parseEmailAddresses($email);
            foreach($addresses as $e => $n) {
                $rv &= $this->Bcc($e, $n);
            }
            return $rv;
        } else {
            return $this->appendHeader('Bcc', new MIMEMailAddress($email,$name));
        }
    }
    
    function Subject($str) {
        return $this->setHeader('Subject', $str);
    }
    
    function Organization($str) {
        return $this->setHeader('Organization', $str);
    }
    
    function Priority($priority) {
        if(is_numeric($priority)) {
            switch($priority) {
                case 1:
                case 2:
                    $MSPriority = 'High';
                    break;
                case 3:
                    $MSPriority = 'Normal';
                    break;
                case 4:
                case 5:
                    $MSPriority = 'Low';
                    break;
            }
        } else {
            $MSPriority = $priority;
            switch($MSPriority) {
                case 'High':
                    $priority = 1;
                    break;
                case 'Normal':
                    $priority = 3;
                    break;
                case 'Low':
                    $priority = 5;
                    break;
            }
        }
        return $this->setHeader('X-Priority', $priority) && $this->setHeader('X-MSMail-Priority', $MSPriority);
    }
    
    
    
    function Message($fnameordata, $mode) { /* MM_FILE */
        $message = new MIMEMailMessage($fnameordata, $mode);
        if(!empty($message->data)) {
            $this->Messages[] = $message;
            return TRUE;
        } else {
            return FALSE;
        }
    }

    function MessageFile($filename) {
        return $this->Message($filename, MM_FILE);
    }
    
    function MessageStream($stream) {
        return $this->Message($stream, MM_STREAM);
    }

    function Attach($fnameordata, $mode, $filename = FALSE, $inline = FALSE) { /* MM_FILE */
        $attachment = new MIMEMailAttachment($fnameordata, $mode, $filename, $inline);
        if(!empty($attachment->data)) {
            $this->Attachments[] = $attachment;
            return TRUE;
        } else {
            return FALSE;
        }
    }
    
    function AttachFile($file, $filename = FALSE, $inline = FALSE) {
        return $this->Attach($file, MM_FILE, $filename, $inline);
    }
    
    function AttachStream($stream, $filename, $inline = FALSE) {
        return $this->Attach($stream, MM_STREAM, $filename, $inline);
    }
        
    function parseEmailAddresses($data) {
        if(is_array($data)) {
            foreach($data as $d) {
                $addys[] = $this->parseEmailAddresses($d);
            }
            foreach($addys as $addy) {
                $addresses = array_merge($addresses, $addy);
            }
            return $addresses;
        } else {
            if($addys = preg_match_all("/(\"(.*?)\"\s+)?<?([\w\._-]+\@([\w_-]+\.)+[\w_-]+)>?/", $data, $matches)) {
                for($i=0;$i<$addys;$i++) {
                    $addresses[$matches[3][$i]] = (!empty($matches[2][$i])?$matches[2][$i]:FALSE);
                }
                return $addresses;
            } else {
                return FALSE;
            }
        }
    }
        
    
    function Send($SMTP = FALSE) {
    	$Mail['Headers']="";
	$Mail['Subject']="";
	$Mail['Body']="";
        $isMime = sizeof($this->Messages) > 1 || sizeof($this->Attachments);
        $multiMessage = sizeof($this->Messages) > 1;

        $Mail['Headers'] .= $this->printHeader('From');

        for($i=0;$i<sizeof($this->Headers['To']);$i++) {
            $Mail['To'][] = $this->getHeader('To',$i);
        }
        $Mail['To'] = implode(',',$Mail['To']);
        
        $Mail['Headers'] .= $this->printHeader('Cc');
        $Mail['Headers'] .= $this->printHeader('Bcc');
        $Mail['Headers'] .= $this->printHeader('Reply-To');
        $Mail['Headers'] .= $this->printHeader('Return-Path');
        $Mail['Headers'] .= $this->printHeader('Disposition-Notification-To');
        
        $Mail['Subject'] .= $this->getHeader('Subject');
        
        foreach($this->Headers as $name => $value) {
            if(is_scalar($value) && substr($name,0,2) == 'X-') {
                $Mail['Headers'] .= $this->printHeader($name);
            }
        }

        // Modif GLPI
	//if($isMime) {
            $Mail['Headers'] .= "MIME-Version: 1.0".$this->LB;
        //}
	
	
        if($isMime) {
            $MailBoundary = "<<<:" . md5(uniqid(mt_rand(), 1));
            $Mail['Headers'] .= "Content-Type: multipart/mixed;".$this->LB."\tboundary=\"".$MailBoundary."\"".$this->LB;
            $Mail['Body'] .= 'This is a multi-part message in MIME format.'.$this->LB;
            $Mail['Body'] .= $this->LB.'--'.$MailBoundary.$this->LB;
            if($multiMessage) {
                $MessageBoundary = "<<<:" . md5(uniqid(mt_rand(), 1));
                $Mail['Body'] .= "Content-Type: multipart/alternative;".$this->LB."\tboundary=\"".$MessageBoundary."\"".$this->LB;
            }
        }
        if(sizeof($this->Messages)) {
            foreach($this->Messages as $message) {
                if($multiMessage) { $Mail['Body'] .= $this->LB.'--'.$MessageBoundary.$this->LB; }
                /* content type, encoding */
                
                $Mail[($isMime?'Body':'Headers')] .= $message->printHeader('Content-Type',';','charset="'.$message->getCharset().'"').
                    $message->printHeader('Content-Transfer-Encoding');
                if($isMime) { $Mail['Body'] .= $this->LB; }
                /* content */
                $Mail['Body'] .= $message->getData().$this->LB;
            }
            if($multiMessage) {
                $Mail['Body'] .= $this->LB.'--'.$MessageBoundary.'--'.$this->LB;
            }
        }
        if($isMime) {
            if(sizeof($this->Attachments)) {
                foreach($this->Attachments as $attachment) {
                    $Mail['Body'] .= $this->LB.'--'.$MailBoundary.$this->LB;
                    $Mail['Body'] .= $attachment->printHeader('Content-Type',';','name="'.$attachment->getFilename().'"').
                        $attachment->printHeader('Content-Transfer-Encoding').
                        $attachment->printHeader('Content-Disposition',';','filename="'.$attachment->getFilename().'"').$this->LB;
                    $Mail['Body'] .= $attachment->getData().$this->LB;
                }
            }
            $Mail['Body'] .= $this->LB.'--'.$MailBoundary.'--'.$this->LB;
        }
        
        if(DEBUG) {
            print $Mail['Headers'].$Mail['Subject'].$Mail['Body'];
            return TRUE;
//        } else if($SMTP) {
//            return $this->SMTP($Mail['To'], $Mail['Subject'], $Mail['Body'], trim($Mail['Headers']));
        } else {
            return mail($Mail['To'], $Mail['Subject'], $Mail['Body'], trim($Mail['Headers']));
        }
    }
    
/*    function SMTP($to, $subject, $body, $headers = '') {
        
        if(preg_match("/^From:\s+(.*?)$this->LB/m", $headers, $matches)) {
            $from = $matches[1];
        }
        //ini_set(sendmail_from, $from);
        $socket = fsockopen (ini_get("SMTP"), ini_get("smtp_port"), $errno, $errstr, 30) or die("Could not talk to the sendmail server!");
        $rcv = fgets($socket, 1024);
        
        print $rcv;
        fclose($socket);        
        //ini_restore(sendmail_from);
        return TRUE;
    }
*/
}


class MIMEMailContent extends MIMEMailCommon
{
    var $data;
    var $charset;
    var $filename;
        
    /* encodings: 7bit, quoted-printable, base64 (for html) */
    function MIMEMailContent($fnameordata, $mode) {
        if($mode == MM_FILE) {
            if($data = file_get_contents($fnameordata)) {
                $this->setFilename(basename($fnameordata));
                $fnameordata = $data;
                unset($data);
            } else {
                return FALSE;
            }
        }
        /* we should have data by now */
        $this->setContentType($this->getContentType($fnameordata));
        $this->data = $fnameordata;
        $this->setCharsetFromContentType();
        switch($this->getHeader('Content-Type')) {
            case 'text/plain':
                $encoding = '8bit';
                break;
            case 'text/html':
            default:
	    // Force 7bit encoding for GLPI
	    $encoding = '8bit';
                //$encoding = 'base64';
                break;
        }
        $this->setContentTransferEncoding($encoding);
        return $this;
    }
    
    function setContentType($type) {
        return $this->setHeader('Content-Type',$type);
    }

    function setContentTransferEncoding($encoding) {
        return $this->setHeader('Content-Transfer-Encoding', $encoding);
    }
    
    function getContentType($content = FALSE) {
	   // Force Content Type to text/plain for GLPI
	    return "text/plain";
        $tmpfile = $this->toTempfile($content);
        if(file_exists($tmpfile)) {
            $ct = mime_content_type($tmpfile);
            unlink($tmpfile);
            return $ct;
        } else {
            return FALSE;
        }
    }
    
    function getData() {
        switch($this->getHeader('Content-Transfer-Encoding')) {
            case '7bit':
            case 'quoted-printable':
                return $this->data;
                break;
            case 'base64':
                return chunk_split(base64_encode($this->data));
            case FALSE:
                return $this->data;
                break;
            default:
                return $this->data;
                break;
        };
    }

    function toTempfile($content = FALSE) {
        $filename = tempnam("/var/www/html/tmp", 'MMTMP');
        $fp = fopen($filename, 'wb');
        if($content !== FALSE) {
            fwrite($fp, $content);
        } else {
            fwrite($fp, $this->data);
        }
        fclose($fp);
        return $filename;
    }

    function setCharset($charset) {
        $this->charset = $charset;
        return TRUE;
    }
    
    function getCharset() {
        return $this->charset;
    }

    function setCharsetFromContentType() {
        $ct = $this->getHeader('Content-Type');
        if(($csI = stripos($ct, 'charset=')) === FALSE) {
            return FALSE;
        }
        $cs = substr($ct, $csI);
        $ct = substr($ct, 0, $csI);
        $ct = str_replace(';','',$ct);
        $this->setContentType(trim($ct));
        $cs = explode('=', str_replace('"','',$cs));
        return $this->setCharset($cs[1]);
    }
    
    function setFilename($fname) {
        $this->filename = $fname;
        return TRUE;
    }
    
    function getFilename() {
        return $this->filename;
    }
    
}

class MIMEMailMessage extends MIMEMailContent
{
    
    
    function MIMEMailMessage($fnameordata, $mode) {
        $this->charset = 'utf8';
        if($this->MIMEMailContent($fnameordata, $mode) === FALSE) { return FALSE; }
        return $this;
    }
        
}

class MIMEMailAttachment extends MIMEMailContent
{

    
    function MIMEMailAttachment($fnameordata, $mode, $filename = FALSE, $inline = FALSE) {
        if($this->MIMEMailContent($fnameordata, $mode) === FALSE) { return FALSE; }
        if($filename !== FALSE) { $this->setFilename($filename); }
        $this->setContentDisposition($inline);
        return $this;
    }    
    
    function setContentDisposition($inline) {
        return $this->setHeader('Content-Disposition',($inline?'inline':'attachment'));
    }
    

    
}

class MIMEMailCommon
{

    /* define common members and functions */
    var $Headers;
    
    var $LB = "\n";
    
    function MIMEMailCommonObject() {
        return $this;
    }

    function printHeader($name, $delim = ',', $params = FALSE) {
        if(!isset($this->Headers[$name])) {
            return '';
        }
        $str = '';
        if(is_array($this->Headers[$name])) {
            foreach($this->Headers[$name] as $element) {
                $str .= $delim;
                if(is_object($element)) {
                    $str .= $element->toString();
                } else if(is_scalar($element)) {
                    $str .= $element;
                }
            }
            $str = substr($str,1); // to remove extra delimiter
        } else if(is_object($this->Headers[$name])) {
            $str .= $this->Headers[$name]->toString();
        } else if(is_scalar($this->Headers[$name])) {
            $str .= $this->Headers[$name];
        }
        $str = $name.': '.$str;
        if($params !== FALSE) {
            $str .= $delim.' '.$params;
        }
        $str .= $this->LB;
        
        return $str;
    }
    
    function getHeader($name, $index = 0) {
        if(isset($this->Headers[$name])) {
            if(is_array($this->Headers[$name])) {
                if(is_object($this->Headers[$name][$index])) {
                    return $this->Headers[$name][$index]->toString();
                } else {
                    return $this->Headers[$name][$index];
                }
            } else if(is_object($this->Headers[$name])) {
                return $this->Headers[$name]->toString();
            } else {
                return $this->Headers[$name];
            }
        } else {
            return FALSE;
        }
    }
    
    function setHeader($name, $value) {
        $this->Headers[$name] = $value;
        return TRUE;
    }
    
    function appendHeader($name, $value) {
        if(isset($this->Headers[$name])) {
            if(is_array($this->Headers[$name])) {
                $this->Headers[$name][] = $value;
            } else {
                $this->Headers[$name] = array($this->Headers[$name], $value);
            }
            return TRUE;
        } else {
            return $this->setHeader($name, $value);
        }
    }
    
    function removeHeader($name) {
        unset($this->Headers[$name]);
    }

}

class MIMEMailAddress
{
    var $email;
    var $name = FALSE;
    
    function MIMEMailAddress($email, $name = FALSE) {
        // should do proper regex checks on email
        $this->email = $email;
        if($name !== FALSE) { $this->name = $name; }    
        return $this;
    }
    
    function toString($useName = TRUE) {
        $str = '';
        if($useName && $this->name !== FALSE) { $str .= '"'.$this->name.'" <'; }
        $str .= $this->email;
        if($useName && $this->name !== FALSE) { $str .= '>'; }
        return $str;
    }
}

/* Usage example:
$mailobj = new MIMEMail();
$mailobj->From('sentfrom@myhost.com', 'I am the sender');
$mailobj->To('receive@yourhost.com');
$mailobj->ReplyTo('replyto@myhost.com');
$mailobj->Subject('This is a neat PHP mail class!');
$mailobj->setHeader('X-Mailer', 'PHP/MIMEMail');

$mailobj->MessageStream('<html><head><title>title</title></head><body>This is an html page.</body></html>');
$mailobj->MessageFile('test.txt');
$mailobj->AttachFile('test.txt');
$mailobj->AttachFile('../imgs/img.jpg');

$mailobj->Send();
*/

?>
