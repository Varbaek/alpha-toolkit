<html>
<head></head>
<body>
<p><a href="https://www.firebwall.com/decoding/index.php"><h1>Submit bot to be decoded</h1></a></p>
<p><a href="https://github.com/bwall/PHP-RFI-Payload-Decoder">Source Code</a></p>
<p><a href="http://ballastsec.blogspot.com/2012/07/anti-bot-vulnerability-search.html">Bot Net Analysis</a>  The analysis is light at this point, but does include the configurations to various bots seen.</p>
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

function EntryURLDescription($entry)
{
	print "<h1>$entry</h1><br/>";
	$handle = @fopen("temp/".$entry, "r");
	if($handle)
	{
		while(($buffer = fgets($handle)) !== false)
		{
			if(strpos($buffer, "Timestamp") === 0 || strpos($buffer, "URL") === 0)
			{
				print htmlentities($buffer)."<br />";
			}
			else if(strpos($buffer, "Shell") === 0)
			{
				print "<h3>Decoded</h3>";
				print "<br /><textarea style=\"width:100%;height:100%;\">";
				print htmlentities(base64_decode(substr($buffer, 9)));
				print "</textarea><br /><br />";
			}
			else if(strpos($buffer, "Raw") === 0)
			{
				print "<h3>Raw</h3>";
				print "<br /><textarea style=\"width:100%;height:100%;\">";
                                print htmlentities(base64_decode(substr($buffer, 7)));
                                print "</textarea><br /><br />";
			}

		}
		fclose($handle);
	}
	print "<br/>";
}

function GetTimestamp($entry)
{
	$handle = @fopen("temp/".$entry, "r");
        if($handle)
        {
                while(($buffer = fgets($handle)) !== false)
                {
                        if(strpos($buffer, "Timestamp: ") === 0)
                        {
                                return substr($buffer, 11);
                        }
                }
                fclose($handle);
        }
        return false;
}

function GetUrl($entry)
{
        $handle = @fopen("temp/".$entry, "r");
        if($handle)
        {
                while(($buffer = fgets($handle)) !== false)
                {
                        if(strpos($buffer, "URL: ") === 0)
                        {
                                return htmlentities(urldecode(substr($buffer, 5)));
                        }
                }
                fclose($handle);
        }
	return false;
}

function PrintList()
{
	print "<h1>Bots Decoded from Urls</h1><br />";
	$list = array();
	if ($handle = opendir('temp/'))
	{
    		while (false !== ($entry = readdir($handle)))
    		{
        		if ($entry != "." && $entry != "..")
				{
					$ctime = strtotime(GetTimestamp($entry));
					$list[$ctime] = $entry;
        		}
    		}
    		closedir($handle);
		krsort($list);
		foreach($list as $i => $entry)
		{
			$entry = $list[$i];
			if(strpos($entry, "Url") !== false)
				print "<p>".GetTimestamp($entry)."<a href=\"read.php?u=".strstr($entry, ".", true)."\">".GetUrl($entry)."</a></p>";
			else if(strstr($entry, ".", true) !== false)
				print "<p>".GetTimestamp($entry)."<a href=\"read.php?u=".strstr($entry, ".", true)."\">".$entry."</a></p>";
			else
				print "<p>".GetTimestamp($entry)."<a href=\"read.php?u=$entry\">".$entry."</a></p>";
		}
	}
}

if(isset($_GET['u']))
{
	if(strlen($_GET['u']) == 32 && strpos($_GET['u'], ".") === false && strpos($_GET['u'], "/") === false)
	{
		if(file_exists("temp/".$_GET['u'].".DecodedByUrl"))
			EntryURLDescription($_GET['u'].".DecodedByUrl");
		else if(file_exists("temp/".$_GET['u'].".DecodedOnWeb"))
			EntryURLDescription($_GET['u'].".DecodedOnWeb");
		else
			EntryURLDescription($_GET['u']);
	}
	else
	{
		PrintList();
	}
}
else
{
	PrintList();
}

?>
</body>
</html>
