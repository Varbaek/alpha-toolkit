<?php
if(isset($_REQUEST['u']) && !empty($_REQUEST['u']))
{
	include("config.inc.php");
	include("Decoder.inc.php");
	$decoder = new Decoder($outputFolder);
	$decoder->DecodeFromUrl(base64_decode(urldecode($_REQUEST['u'])));
}
?>