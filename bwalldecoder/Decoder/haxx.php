<?php

/* **************** *\
|* MODIFIED BY MAXE *|
\* **************** */

include("config.inc.php");
include("Decoder.inc.php");
$decoder = new Decoder($outputFolder);
$payload = false;

if( isset($argv[1]) && !empty($argv[1]) )
{
	$filepointer = file_get_contents($argv[1]);
	$payload = $decoder->DecodeFromText($filepointer);
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
	$decoded = $payload->decodedPayload;
	$original = htmlentities($payload->rawPayload);
}

?>
<?php print $decoded;?>