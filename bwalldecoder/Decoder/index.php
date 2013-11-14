<?php
include("config.inc.php");
include("Decoder.inc.php");
$decoder = new Decoder($outputFolder);
$payload = false;
if(isset($_GET['hash']))
{
	$payload = $decoder->DecodeFromHash($_GET['hash']);
}
else if(isset($_POST['input']) && !empty($_POST['input']) && !(isset($_POST['url']) && !empty($_POST['url'])))
{
	$payload = $decoder->DecodeFromText($_POST['input']);
}
else if(isset($_POST['url']) && !empty($_POST['url']))
{
	$payload = $decoder->DecodeFromUrl($_POST['url']);
}
$decoded = "";
$original = "";
$details = "";
if($payload === false)
{
	$payload = new DecodedPayload();
}
else
{
	$details = "URL: ".htmlentities($payload->origin)."<br/>Timestamp: ".htmlentities($payload->timestamp);
	$decoded = htmlentities($payload->decodedPayload);
	$original = htmlentities($payload->rawPayload);
}

?>

<!DOCTYPE html>
<html>
<head>
<title>PHP Decoder</title>
<link href="shThemeEclipse.css" rel="stylesheet" type="text/css" />
<script src="shCore.js" type="text/javascript"></script>
<script src="shAutoloader.js" type="text/javascript"></script>
<script src="shBrushPhp.js" type="text/javascript"></script>
</head>
<body>
<table width="100%" height="100%" border="1"><tr><td colspan="2">
<h1>PHP Decoder</h1><form action="index.php" method="post"><a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=KCHYQRCBZEWML">Donate</a> - 
<a href='https://github.com/bwall/PHP-RFI-Payload-Decoder'>Source</a> - URL: <input type="text" name="url" /><input type="submit" value="Decode by Url" /> - Use a raw link to pastebin for non-RFI payloads</form></td></tr>
<tr valign="top">
<td style="width:200px;text-align:top;">
<p>Decoded Bots</p>
<?php print $decoder->htmllist; ?>
</td>
<td style="height:100%;text-align:top;">
<table width="99%" border="0">
<tr style="width:90%;text-align:top;">
<td style="width:100%;text-align:top;">
<?php print $details; ?>
<p>Decoded</p>
<pre class="brush: php"><?php print $decoded;?></pre>
</td>
</tr>
</table></tr></td><tr>
<script type="text/javascript">
     SyntaxHighlighter.all()
</script>
<td colspan="2" style="text-align:center;">
Copyright &copy; fireBwall 2012. All rights reserved</td></tr></table></body>
</html>