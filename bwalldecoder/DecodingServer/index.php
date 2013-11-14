<?php
/*
 * Generates a name for a bot dump
 * Set $dumpFolder to a folder that is not web viewable, its suggested to use the full path
 *
 * returns false if it already exists, else returns the filename
 */
function GetFileName($uri, $tail = "", $canFail = true)
{
    $dumpFolder = "temp/";
    if(file_exists($dumpFolder.md5($uri).$tail))
    {
        if($canFail)
            return false;
    }
    return $dumpFolder.md5($uri).$tail;
}

function GetUrl($uri)
{
	return "read.php?u=".md5($uri);
}

function RemoveComments($str)
{
	$done = false;
	while($done === false)
	{
		if(preg_match('/\/\*.*\*\//m', $str, $matches))
		{
                    $str = str_replace($matches[0], "", $str);
		}
		//else if(preg_match('/\/\/.*\n/m', $str, $matches))
		//{
                        //Causing issues in some base64
			//$str = str_replace($matches[0], "", $str);
		//}
		else
		{
			$done = true;
		}
	}
	return $str;
}

function ExpandLines($str)
{
    return $str;
    //return str_replace(";", ";\n", $str);
}

function ClearEmptyEvals(&$str)
{
    $done = false;
    while($done === false)
    {
        if(preg_match('/eval\(["\'][[:space:]]*["\']\);/m', $str, $matches))
        {
                $str = str_replace($matches[0], "", $str);
        }
        else
                $done = true;
    }
}

function Decode($funcArray, &$str, &$aliases, &$steps)
{
	$count = count($funcArray);
	$funcs = "";
	$tail = "";
	$toEval = "";
	$endEval = "";
	for($i = 0; $i < $count; $i++)
	{
		$funcs .= $funcArray[$i]."[[:space:]]*\([[:space:]]*";
		$tail .= '[[:space:]]*\)';
		$toEval .= $funcArray[$i]."(";
		$endEval .= ")";
	}
	$endEval .= ";";
	if(preg_match('/'.$funcs.'(?<data>"[^"]+")'.$tail.'/m', $str, $matches))
	{
		$str = str_replace($matches[0], "'".ExpandLines(eval("return ".$toEval.$matches["data"].$endEval))."'", $str);
		$steps .= $toEval.$matches["data"].$endEval."\n";
		return true;
	}
	else if(preg_match('/'.$funcs.'(?<data>\'[^\']+\')'.$tail.'/m', $str, $matches))
	{
		$str = str_replace($matches[0], "'".ExpandLines(eval("return ".$toEval.$matches["data"].$endEval))."'", $str);
		$steps .= $toEval.$matches["data"].$endEval."\n";
		return true;
	}
	else if(preg_match('/'.$funcs.'(?<data>\'[^\']+\')/m', $str, $matches))
	{
		$str = str_replace($matches[0], "'".ExpandLines(eval("return ".$toEval.$matches["data"].$endEval))."'", $str);
		$steps .= $toEval.$matches["data"].$endEval."\n";
		return true;
	}
	else if(preg_match('/'.$funcs.'(?<data>"[^"]+")/m', $str, $matches))
	{
		$str = str_replace($matches[0], "'".ExpandLines(eval("return ".$toEval.$matches["data"].$endEval))."'", $str);
		$steps .= $toEval.$matches["data"].$endEval."\n";
		return true;
	}
	else if(preg_match('/'.$funcs.'(?<data>"[^"]+)/m', $str, $matches))
	{
		$str = str_replace($matches[0], "'".ExpandLines(eval("return ".$toEval.$matches["data"].'"'.$endEval))."'", $str);
		$steps .= $toEval.$matches["data"].$endEval."\n";
		return true;
	}
	else if(preg_match('/'.$funcs.'(?<data>\'[^\']+)/m', $str, $matches))
	{
		$str = str_replace($matches[0], "'".ExpandLines(eval("return ".$toEval.$matches["data"]."'".$endEval))."'", $str);
		$steps .= $toEval.$matches["data"].$endEval."\n";
		return true;
	}
	else
	{
		return false;
	}
}

function RemoteFileSize($url)
{
        $sch = parse_url($url, PHP_URL_SCHEME);
        if (($sch != "http") && ($sch != "https") && ($sch != "ftp") && ($sch != "ftps")) {
            return false;
        }
        if (($sch == "http") || ($sch == "https")) {
            $headers = get_headers($url, 1);
            if ($headers === false || (!array_key_exists("Content-Length", $headers))) { return false; }
            return $headers["Content-Length"];
        }
        if (($sch == "ftp") || ($sch == "ftps")) {
            $server = parse_url($url, PHP_URL_HOST);
            $port = parse_url($url, PHP_URL_PORT);
            $path = parse_url($url, PHP_URL_PATH);
            $user = parse_url($url, PHP_URL_USER);
            $pass = parse_url($url, PHP_URL_PASS);
            if ((!$server) || (!$path)) { return false; }
            if (!$port) { $port = 21; }
            if (!$user) { $user = "anonymous"; }
            if (!$pass) { $pass = "phpos@"; }
            switch ($sch) {
                case "ftp":
                    $ftpid = ftp_connect($server, $port);
                    break;
                case "ftps":
                    $ftpid = ftp_ssl_connect($server, $port);
                    break;
            }
            if (!$ftpid) { return false; }
            $login = ftp_login($ftpid, $user, $pass);
            if (!$login) { return false; }
            $ftpsize = ftp_size($ftpid, $path);
            ftp_close($ftpid);
            if ($ftpsize == -1) { return false; }
            return $ftpsize;
        }
}

function AutoDecode(&$str, &$steps)
{
    $str = RemoveComments($str);
    $str = ExpandLines($str);
    $done = FALSE;
    $aliases = array();
    $variables = array();
    while($done === FALSE)
    {
        if(Decode(array("gzinflate", "str_rot13", "base64_decode"), $str, $aliases, $steps) ||
                    Decode(array("gzuncompress", "str_rot13", "base64_decode"), $str, $aliases, $steps) ||
                    Decode(array("gzinflate", "str_rot13"), $str, $aliases, $steps) ||
                    Decode(array("gzuncompress", "str_rot13"), $str, $aliases, $steps) ||
                    Decode(array("gzinflate", "base64_decode"), $str, $aliases, $steps) ||
                    Decode(array("gzuncompress", "base64_decode"), $str, $aliases, $steps) ||
                    Decode(array("base64_decode"), $str, $aliases, $steps) ||
                    Decode(array("gzinflate", "str_rot13"), $str, $aliases, $steps) ||
		    Decode(array("gzinflate", "base64_decode", "str_rot13"), $str, $aliases, $steps) ||
		    Decode(array("base64_decode", "str_rot13"), $str, $aliases, $steps))
        {
        }
        else
        {
            $done = true;
            if(preg_match_all('/(\$[[:alnum:]_]+)[[:space:]]*=[[:space:]]*("[^"]+");/s', $str, $matches) != 0)
            {
                $count = count($matches[0]);
                for($i = 0; $i < $count; $i++)
                {
                    $name = $matches[1][$i];
		    if(in_array($name, $variables) === true)
		    {
			continue;
		    }
                    $value = $matches[2][$i];
                    if($str !== preg_replace('/('.preg_quote($name).')([^<>[:alnum:]_ \=])/m', "$value$2", $str) && strstr($value, $name) === false)
                    {
                        $done = false;
			array_push($variables, $name);
                        $str = preg_replace('/('.preg_quote($name).')([^<>[:alnum:]_ \=])/m', "$value$2", $str);
                        $steps .= "Replacing $name with $value\n";                        
                    }                    
                }
            }
            if(preg_match_all('/(\$[[:alnum:]_]+)[[:space:]]*=[[:space:]]*(\'[^\']+\');/s', $str, $matches) != 0)
            {
                $count = count($matches[0]);
                for($i = 0; $i < $count; $i++)
                {
                    $name = $matches[1][$i];
                    if(in_array($name, $variables) === true)
                    {
                        continue;
                    }
                    $value = $matches[2][$i];
                    if($str !== preg_replace('/('.preg_quote($name).')([^<>[:alnum:]_ \=])/m', "$value$2", $str) && strstr($value, $name) === false)
                    {
                        $done = false;
			array_push($variables, $name);
                        $str = preg_replace('/('.preg_quote($name).')([^<>[:alnum:]_ \=])/m', "$value$2", $str);
                        $steps .= "Replacing $name with $value\n";                        
                    }                    
                }
            }
        }
        ClearEmptyEvals($str);
    }
}
$str = "";
$steps = "";
$meta = "";
if(isset($_POST['input']) && !empty($_POST['input']) && !(isset($_POST['url']) && !empty($_POST['url'])))
{
    $str = $_POST['input'];
    $raw = $str;
    AutoDecode($str, $steps);
    $file = GetFileName($str, ".DecodedOnWeb");
    if($file !== false)
    {
        $toFile = "Timestamp: ".strftime('%c')."\n";
	$toFile .= "Submitter: ".exec("htdeny ".$_SERVER['REMOTE_ADDR'])."\n";
        $toFile .= "Was decoded from text on server.\n\n";
        $toFile .= "Shell -> ".base64_encode($str)."\n\n";
	$toFile .= "Raw -> ".base64_encode($raw)."\n\n";
        file_put_contents($file, $toFile);
    }
}
else if(isset($_POST['url']) && !empty($_POST['url']))
{
    $file = GetFileName($_POST['url'], ".DecodedByUrl");
    if($file !== false)
    {
            $str = file_get_contents($_POST['url'], false, null, 0, 1024 * 1024 * 16);
	    $raw = $str;
	    if($str !== false)
	    {
            	AutoDecode($str, $steps);
            	$toFile = "Timestamp: ".strftime('%c')."\n";
				$toFile .= "Submitter: ".$_SERVER['REMOTE_ADDR']."\n";
            	$toFile .= "URL: ".$_POST['url']."\n";
            	$toFile .= "Was decoded from url on server.\n\n";
            	$toFile .= "Shell -> ".base64_encode($str)."\n\n";
				$toFile .= "Raw -> ".base64_encode($raw)."\n\n";
            	file_put_contents($file, $toFile);
				$meta = "<META HTTP-EQUIV=REFRESH CONTENT=\"1; URL=".GetUrl($_POST['url'])."\">";
	    }
    }
    else
    {
		$meta = "<META HTTP-EQUIV=REFRESH CONTENT=\"1; URL=".GetUrl($_POST['url'])."\">";
    }
}
print "<!DOCTYPE html>
<html>
$meta
<body>
<form action=\"\" method=\"post\">
<table width=\"100%\" height=\"100%\" border=\"1\"><tr><td colspan=\"2\">
<h1>PHP Decoder</h1><a href=\"https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=KCHYQRCBZEWML\">Donate</a><br/> <a href='https://github.com/bwall/PHP-RFI-Payload-Decoder'>Source</a></td></tr><tr valign=\"top\">
<td style=\"width:100px;text-align:top;\"><b>Tools</b><br />
<br />URL: <input type=\"text\" name=\"url\" />
<br /><input type=\"submit\" value=\"Decode\" />
<br />
<p><a href=\"https://www.firebwall.com/decoding/read.php\">Decoded Bots</a></p><br />
</td>
<td style=\"height:100%;text-align:top;\">
<table width=\"99%\" border=\"0\">
<tr style=\"width:90%;text-align:top;\">
<td style=\"width:100%;text-align:top;\">
<p>PHP to Decode</p>
<textarea name=\"input\" style=\"width:100%;height:400px;\">
".htmlentities($str)."</textarea></td></tr>
</table></tr></td><tr>
<td colspan=\"2\" style=\"text-align:center;\">
Copyright &copy; fireBwall 2012. All rights reserved</td></tr></table></form></body>
</html>
";
?>
