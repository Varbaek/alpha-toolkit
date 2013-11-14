<?php

class DecodedPayload
{
	public $rawPayload = "";
	public $decodedPayload = "";	
	public $origin = "";
	public $type = "";
	public $hash = "";
	#public $submitter = "";
	public $timestamp = "";
}

class Decoder
{
	private $dumpFolder = "temp/";
	private $aliases = array();
	
	public $list = array();
	public $htmllist = "";
	public $details = "";
	
	public function __construct($folder)
	{
	/*
		$this->dumpFolder = $folder;
		if ($handle = opendir($folder))
		{
			while (false !== ($entry = readdir($handle)))
			{
				if ($entry != "." && $entry != "..")
				{
					$f = unserialize(gzinflate(base64_decode(file_get_contents($folder.$entry))));
					$ctime = strtotime($f->timestamp);
					$this->list[$ctime] = $f;
				}
			}
			closedir($handle);
			krsort($this->list);
			foreach($this->list as $time => $payload)
			{
				$name = $payload->timestamp;
				if(preg_match("/\/([^\/]+)$/", $payload->origin, $matches) != 0)
				{
					$name = htmlentities($matches[1]);
				}
				$this->htmllist .= '<a href="?hash='.$payload->hash.'" title="'.htmlentities($payload->origin).'">'.$name.'</a><br/>';
			}
		}
	*/
	}
	
	private function GetFileName($uri, $tail = "", $canFail = true)
	{
		if(file_exists($this->dumpFolder.md5($uri).$tail))
		{
			if($canFail)
				return false;
		}
		return $this->dumpFolder.md5($uri).$tail;
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
			else
			{
				$done = true;
			}
		}
		return $str;
	}
	
	function ExpandLines($str)
	{
		$str = preg_replace("/;([^\n])/", ";\n$1", $str);
		return $str;
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
	
	function Concatenate($str)
	{
		if(preg_match_all("/'([^']*)'[\s]*\.[\s]*'([^']*)'/", $str, $matches) != 0)
		{
			$count = count($matches[0]);
			for($i = 0; $i < $count; $i++)
			{
				$value = $matches[2][$i];
				if($str !== preg_replace("/'([^']*)'[\s]*\.[\s]*'([^']*)'/", "'$1$2'", $str))
				{
					$str = preg_replace("/'([^']*)'[\s]*\.[\s]*'([^']*)'/", "'$1$2'", $str);
				}
			}
		}
		if(preg_match_all("/\"([^\"]*)\"[\s]*\.[\s]*\"([^\"]*)\"/", $str, $matches) != 0)
		{
			$count = count($matches[0]);
			for($i = 0; $i < $count; $i++)
			{
				$value = $matches[2][$i];
				if($str !== preg_replace("/\"([^\"]*)\"[\s]*\.[\s]*\"([^\"]*)\"/", "\"$1$2\"", $str))
				{
					$str = preg_replace("/\"([^\"]*)\"[\s]*\.[\s]*\"([^\"]*)\"/", "\"$1$2\"", $str);
				}
			}
		}
		return $str;
	}
	
	function Unescape($data)
	{
		if(preg_match_all('/'.preg_quote('\x', '/').'([a-fA-F0-9][a-fA-F0-9])/', $data, $matches) != 0)
		{
			$count = count($matches[0]);
			for($i = 0; $i < $count; $i++)
			{
				$value = hexdec($matches[1][$i]);
				if($data !== preg_replace('/'.preg_quote('\x', '/').$matches[1][$i]."/", chr($value), $data))
				{
					$data = preg_replace('/'.preg_quote('\x', '/').$matches[1][$i]."/", chr($value), $data);
				}
			}
		}
		
		if(preg_match_all('/'.preg_quote('\\', '/').'([0-7][0-7]?[0-7]?)/', $data, $matches) != 0)
		{
			$count = count($matches[0]);
			for($i = 0; $i < $count; $i++)
			{
				$value = octdec($matches[1][$i]);
				if($data !== preg_replace('/'.preg_quote('\\', '/').$matches[1][$i]."/", chr($value), $data))
				{
					$data = preg_replace('/'.preg_quote('\\', '/').$matches[1][$i]."/", chr($value), $data);
				}
			}
		}
		return $data;
	}
	
	function Decode($funcArray, &$str)
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
		if(preg_match('/'.$funcs.'(?<data>"[^"]+")'.$tail.'/mi', $str, $matches))
		{
			$str = str_replace($matches[0], "'".$this->ExpandLines(eval("return ".str_replace("'", "", str_replace('"', "", $toEval)).$matches["data"].$endEval))."'", $str);
			return true;
		}
		else if(preg_match('/'.$funcs.'(?<data>\'[^\']+\')'.$tail.'/m', $str, $matches))
		{
			$str = str_replace($matches[0], "'".$this->ExpandLines(eval("return ".str_replace("'", "", str_replace('"', "", $toEval)).$matches["data"].$endEval))."'", $str);
			return true;
		}
		else if(preg_match('/'.$funcs.'(?<data>\'[^\']+\')/mi', $str, $matches))
		{
			$str = str_replace($matches[0], "'".$this->ExpandLines(eval("return ".str_replace("'", "", str_replace('"', "", $toEval)).$matches["data"].$endEval))."'", $str);
			return true;
		}
		else if(preg_match('/'.$funcs.'(?<data>"[^"]+")/mi', $str, $matches))
		{
			$str = str_replace($matches[0], "'".$this->ExpandLines(eval("return ".str_replace("'", "", str_replace('"', "", $toEval)).$matches["data"].$endEval))."'", $str);
			return true;
		}
		else if(preg_match('/'.$funcs.'(?<data>"[^"]+)/mi', $str, $matches))
		{
			$str = str_replace($matches[0], "'".$this->ExpandLines(eval("return ".str_replace("'", "", str_replace('"', "", $toEval)).$matches["data"].'"'.$endEval))."'", $str);
			return true;
		}
		else if(preg_match('/'.$funcs.'(?<data>\'[^\']+)/mi', $str, $matches))
		{
			$str = str_replace($matches[0], "'".$this->ExpandLines(eval("return ".str_replace("'", "", str_replace('"', "", $toEval)).$matches["data"]."'".$endEval))."'", $str);
			return true;
		}
		else if(preg_match('/'.$funcs.'(?<data>[a-zA-Z0-9\+\/\=]+)'.$tail.'/mi', $str, $matches))
		{
			$str = str_replace($matches[0], "'".$this->ExpandLines(eval("return ".str_replace("'", "", str_replace('"', "", $toEval))."'".$matches["data"]."'".$endEval))."'", $str);
			return true;
		}
		else
		{
			return false;
		}
	
	}
	
	function AutoDecode(&$str)
	{
		$str = $this->RemoveComments($str);
		$str = $this->ExpandLines($str);
		$done = FALSE;		
		$variables = array();
		while($done === FALSE)
		{
			//concatenation
			$str = $this->Concatenate($str);
			if($this->Decode(array("gzinflate", "str_rot13", "base64_decode"), $str)
				|| $this->Decode(array("gzuncompress", "str_rot13", "base64_decode"), $str)
				|| $this->Decode(array("gzinflate", "base64_decode", "str_rot13"), $str)
				|| $this->Decode(array('"gzinflate"', '"base64_decode"', '"str_rot13"'), $str)
				|| $this->Decode(array("gzinflate", "str_rot13"), $str)
				|| $this->Decode(array("gzuncompress", "str_rot13"), $str)
				|| $this->Decode(array("gzinflate", "base64_decode"), $str)
				|| $this->Decode(array('"gzinflate"', '"base64_decode"'), $str)
				|| $this->Decode(array("gzuncompress", "base64_decode"), $str)
				|| $this->Decode(array("base64_decode"), $str)
				|| $this->Decode(array("gzinflate", "str_rot13"), $str)								
				|| $this->Decode(array("base64_decode", "str_rot13"), $str)
				|| $this->Decode(array('"base64_decode"'), $str) 
				|| $this->Decode(array("'base64_decode'"), $str)
				|| $this->Decode(array("urldecode"), $str)
					)
			{
			}
			else
			{
				$done = true;
				if(preg_match_all('/(\$[[:alnum:]_]+)[[:space:]]*\.=[[:space:]]*([^;]+);/s', $str, $matches) != 0)
				{
					$count = count($matches[0]);
					for($i = 0; $i < $count; $i++)
					{
						$name = $matches[1][$i];
						if(strlen($matches[0][$i]) < 50000 && preg_match_all('/('.preg_quote($name, '/').')[[:space:]]*=[[:space:]]*([^;]+);/s', $str, $m) != 0)
						{
							$value = $this->Unescape($m[2][count($m[2]) - 1]).".".$this->Unescape($matches[2][$i]);
							$value = $this->Concatenate($value);
							$value = $this->Unescape($value);
							$str = preg_replace('/'.preg_quote($matches[0][$i], '/').'/', $matches[1][$i]." = ".$value.";", $str, 1);
							$done = false;							
						}
					}
				}				
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
						if(preg_match_all('/('.preg_quote($name, '/').')[[:space:]]*=[[:space:]]*([^;]+);/s', $str, $m) != 0)
						{
							$value = $this->Unescape($m[2][count($m[2]) - 1]);							
							if($str !== preg_replace('/('.preg_quote($name, '/').')([^<>[:alnum:]_ \=])/m', $value."$2", $str) && strstr($value, $name) === false)
							{
								$done = false;
								array_push($variables, $name);
								$str = preg_replace('/('.preg_quote($name, '/').')([^<>[:alnum:]_ \=])/m', $value."$2", $str);
							}
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
						if(preg_match_all('/('.preg_quote($name, '/').')[[:space:]]*=[[:space:]]*([^;]+);/s', $str, $m) != 0)
						{
							$value = $this->Unescape($m[2][count($m[2]) - 1]);
							if($str !== preg_replace('/('.preg_quote($name).')([^<>[:alnum:]_ \=])/m', $value."$2", $str) && strstr($value, $name) === false)
							{
								$done = false;
								array_push($variables, $name);
								$str = preg_replace('/('.preg_quote($name).')([^<>[:alnum:]_ \=])/m', $value."$2", $str);
							}
						}
					}
				}
				if(preg_match_all('/\$([^\=^\s^\)^{]+)[\s]*\=[\s]*array\(([^\)]*)\)[\s]*\;/im', $str, $matches) != 0)
				{
					$count = count($matches[0]);
					for($i = 0; $i < $count; $i++)
					{
						$name = $matches[1][$i];
						if(in_array($name, $variables) === true)
						{
							continue;
						}
						array_push($variables, $name);
						$value = $matches[2][$i];
						if(preg_match_all("/'([^']*)'/im", $value, $nmatches) != 0)
						{
							foreach($nmatches[1] as $index => $data)
							{
								$nname = preg_quote($name."[".$index."]");
								if($str !== preg_replace('/\$'.$nname.'/m', $data, $str) && strstr($data, $nname) === false)
								{
									$done = false;
									$str = preg_replace('/\$'.$nname.'/m', $data, $str);
								}
							}
						}
					}
				}				
				if(preg_match_all("/\'(?<string>[^\']+)\'{(?<index>[0-9]+)}/", $str, $matches) != 0)
				{
					$count = count($matches[0]);
					for($i = 0; $i < $count; $i++)
					{
						$value = $matches['string'][$i]{$matches['index'][$i]};
						if($str !== preg_replace('/'.preg_quote($matches[0][$i]).'/mi', "'$value'", $str))
						{
							$done = false;
							$str = preg_replace('/'.preg_quote($matches[0][$i]).'/mi', "'$value'", $str);
						}
					}
				}
				
				if(preg_match_all('/(\$[^\=^\s^\)^{]+){(?<index>[0-9]+)}/', $str, $matches) != 0)
				{
					$count = count($matches[0]);
					for($i = 0; $i < $count; $i++)
					{
						$name = $matches[1][$i];
						if(preg_match_all('/('.preg_quote($name, '/').')[[:space:]]*=[[:space:]]*\'([^\']+)\'[^\s]*;/s', $str, $m) != 0)
						{
							if($matches['index'][$i] > strlen($m[2][count($m[2]) - 1]))
							{
								continue;
							}
							$value = $m[2][count($m[2]) - 1]{$matches['index'][$i]};
							if($str !== preg_replace('/'.preg_quote($matches[0][$i]).'/mi', "'$value'", $str))
							{
								$done = false;
								$str = preg_replace('/'.preg_quote($matches[0][$i]).'/mi', "'$value'", $str);
							}
						}
					}
				}
				
				//Autocomputes a function that just does a base64 decode of an internal array and returns the results
				if(preg_match_all('/function[\s]+([^\(^\s]+)\(\$[^\)]+\)[\s]*{[\s]*\$[^\s^=]+[\s]*=[\s]*array\(([^\)]+)\)[\s]*;[\s]*return[\s]+base64_decode\(\$[^\)]+\)[\s]*;[\s]*}/im', $str, $matches) != 0)
				{
					$count = count($matches[0]);
					for($i = 0; $i < $count; $i++)
					{
						$name = $matches[1][$i];
						if(in_array("function ".$name, $variables) === true)
						{
							continue;
						}
						array_push($variables, $name);
						$value = $matches[2][$i];
						if(preg_match_all("/'([^']*)'/im", $value, $nmatches) != 0)
						{
							foreach($nmatches[1] as $index => $data)
							{
								$nname = preg_quote($name."(".$index.")");
								if($str !== preg_replace('/'.$nname.'/m', "'".base64_decode($data)."'", $str) && strstr(base64_decode($data), $nname) === false)
								{
									$done = false;
									$str = preg_replace('/'.$nname.'/m', "'".base64_decode($data)."'", $str);
								}
							}
						}
					}
				}

				//Process specific functions
				//round
				if(preg_match_all('/round\(([0-9\+\.\-\s]+)\)/im', $str, $matches) != 0)
				{
					$count = count($matches[0]);
					for($i = 0; $i < $count; $i++)
					{
						$value = eval("return round(".$matches[1][$i].");");
						if($str !== preg_replace('/'.preg_quote($matches[0][$i]).'/mi', "$value", $str))
						{
							$done = false;
							$str = preg_replace('/'.preg_quote($matches[0][$i]).'/mi', "$value", $str);
						}
					}
				}

				if($str != $this->Unescape($str))
				{
					$done = false;
					$str = $this->Unescape($str);
				}				
				
				//surrogate base64
				if(preg_match_all('/function[\s]+([^\(^\s]+)\(\$[^\)]+\)[\s]*{[\s]*return[\s]*base64_decode\(\$[^\)]+\);}/im', $str, $matches) != 0)
				{
					$count = count($matches[0]);
					for($i = 0; $i < $count; $i++)
					{
						if($str !== preg_replace('/'.preg_quote($matches[1][$i]).'\(/mi', "base64_decode(", $str))
						{
							$done = false;
							$str = preg_replace('/'.preg_quote($matches[1][$i]).'\(/mi', "base64_decode(", $str);
						}
					}
				}
			}
			$this->ClearEmptyEvals($str);			
		}
		
		$str = $this->ExpandLines($str);
		
		$varCount = 0;
		$variables = array();
		if(preg_match_all('/(\$[[:alnum:]_]+)/', $str, $matches) != 0)
		{
			$count = count($matches[0]);
			for($i = 0; $i < $count; $i++)
			{
				$name = $matches[1][$i];
				if(in_array($name, $variables) === true)
				{
					continue;
				}
				$value = "\$var_".$varCount;				
				if($str !== preg_replace('/('.preg_quote($name).')([^[:alnum:]^_])/m', "$value$2", $str) && strstr($value, $name) === false)
				{
					$done = false;
					array_push($variables, $name);
					array_push($variables, $value);
					$str = preg_replace('/('.preg_quote($name).')([^[:alnum:]^_])/m', "$value$2", $str);
					$varCount++;
				}
			}
		}		
	}
	
	public function DecodeFromHash($hash)
	{
		foreach($this->list as $time => $payload)
		{
			if($payload->hash == $hash)
			{
				$payload->decodedPayload = $payload->rawPayload;
				$this->AutoDecode($payload->decodedPayload);
				return $payload;
			}
		}
		return false;
	}
	
	public function DecodeFromText($raw)
	{
		$str = $raw;
		$hash = md5($raw);
		foreach($this->list as $time => $payload)
		{
			if($payload->hash == $hash)
			{
				return $payload;
			}
		}
		$file = $this->GetFileName($str, ".DecodedOnWeb");
		if($file !== false)
		{
			$this->AutoDecode($str);
			$decoded = new DecodedPayload();
			$decoded->timestamp = strftime('%c');
			#$decoded->submitter = $_SERVER['REMOTE_ADDR'];
			$decoded->decodedPayload = $str;
			$decoded->rawPayload = $raw;
			$decoded->hash = $hash;
			$decoded->type = ".DecodedOnWeb";
			$decoded->origin = false;
			$toFile = base64_encode(gzdeflate(serialize($decoded), 9));
			#file_put_contents($file, $toFile);
			return $decoded;
		}
		return false;
	}
	
	function startsWith($haystack, $needle)
	{
		$length = strlen($needle);
		return (substr($haystack, 0, $length) === $needle);
	}
	
	public function DecodeFromUrl($url)
	{
		if(!($this->startsWith($url, "http://") || $this->startsWith($url, "ftp://") || $this->startsWith($url, "https://")))
		{
			$url = "http://".$url;
		}
		$str = "";
		$hash = md5($url);
		foreach($this->list as $time => $payload)
		{
			if($payload->hash == $hash)
			{
				return $payload;
			}
		}
		$file = $this->GetFileName($url, ".DecodedByUrl");
		if($file !== false)
		{
			$str = file_get_contents($url, false, null, 0, 1024 * 1024 * 16);
			$raw = $str;
			if($str !== false)
			{
				$this->AutoDecode($str);
				$decoded = new DecodedPayload();
				$decoded->timestamp = strftime('%c');
				#$decoded->submitter = $_SERVER['REMOTE_ADDR'];
				$decoded->origin = $url;
				$decoded->hash = $hash;
				$decoded->decodedPayload = $str;
				$decoded->rawPayload = $raw;
				$decoded->type = ".DecodedByUrl";
				$toFile = base64_encode(gzdeflate(serialize($decoded), 9));
				file_put_contents($file, $toFile);
				return $decoded;
			}
		}
		return false;
	}
}

?>