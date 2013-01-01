<?php
class simpleScrape
{
	private $values = Array();
	private $sourceStartPos = 0;
	private $script = "";
	private $scriptLines;

	public $source = "";
	public $userAgent = "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.95 Safari/537.11";

// SETTERS

	public function __set($name, $value)
	{
		switch(strtoupper($name))
		{
			case "SOURCEURL":
				$this->source = $this->urlGetContents($value);
				break;

			case "SOURCEPATH":
				$this->source = file_get_contents($value);
				break;

			case "SCRIPTPATH":
				$this->script = file_get_contents($value);
				$this->scriptLines = explode("\n", $this->script);
				break;

			case "SCRIPT":
				$this->script = $value;
				$this->scriptLines = explode("\n", $this->script);
				break;
		}
	}

// SCRAPE
// Kicks off a scrape using the provided script and source

	public function scrape()
	{
		if (trim($this->source) == "")
		{
			trigger_error("No source was provided");
		}
		elseif (trim($this->script) == "")
		{
			trigger_error("No script was provided");
		}
		else
		{
			$this->values = Array();
			$this->sourceStartPos = 0;

			$this->runScript($this->scriptLines);
		}

		return $this->values;
	}

// RUNSCRIPT
// Runs through an array of script lines
// This function will call itself if a REPEAT tag is found

	private function runScript($scriptLines, $offsetLineNo = 0, $nestLevel = 0)
	{
		$scriptLineNo = 0;

		while($scriptLineNo < count($scriptLines))
		{
			$scriptLine = str_replace(array("\r", "\t"), "", $scriptLines[$scriptLineNo]);

			if (trim($scriptLine) == "")
			{
				// Empty line- ignore
			}
			elseif (preg_match_all("/\[(?i:REPEAT)[\:]?([0-9])?\]/", $scriptLine, $repeatTag))
			{
				// This is a REPEAT tag
				$repeatLimit = $repeatTag[1][0];

				$endTagLineNo = $this->findClosingTagLine($scriptLines, "REPEAT", ($scriptLineNo + 1));

				if ($endTagLineNo)
				{
					$subScriptLines = array_slice($scriptLines, ($scriptLineNo + 1), ($endTagLineNo - ($scriptLineNo + 1)));

					$repetitions = 0;

					do
					{
						$result = $this->runScript($subScriptLines, ($offsetLineNo + $scriptLineNo), ($nestLevel + 1));

						$repetitions++;

						if (($repeatLimit > 0) && ($repetitions == $repeatLimit)) break;
					}
					While ($result);

					$scriptLineNo = $endTagLineNo;
				}
				else
				{
					trigger_error("Found no closing tag for REPEAT (line ".($scriptLineNo + $offsetLineNo).")");
				}
			}
			else
			{
				// No REPEAT tag so this must be a straightforward HTML search with or without GRABs
				if (!($this->matchLineAndExtractGrabs($scriptLine))) return false;
			}

			$scriptLineNo++;
		}

		return true;
	}

// MATCHLINEANDEXTRACTGRABS
// Checks for the next occurrence of the given script line in the source
// If the given script line contains GRAB tags then will also store the html found in that relative position in the source

	function matchLineAndExtractGrabs($scriptLine)
	{
		if (!(stripos($scriptLine, "[GRAB")))
		{
			// We're just finding a piece of HTML
			$startPos = stripos($this->source, $scriptLine, $this->sourceStartPos);

			if ($startPos) $this->sourceStartPos = $startPos + strlen($scriptLine);

			return $startPos;
		}
		else
		{
			// We're grabbing data from a piece of HTML
			$tags = array();

			$scriptLine = "[GRAB:__STARTTAG__]".$scriptLine."[GRAB:__ENDTAG__]";

			preg_match_all("/\[(?i:GRAB):.+?\]/", $scriptLine, $tagMatches, PREG_OFFSET_CAPTURE);

			// Store all grab tags in this line into $tags array
			for($tagIndex = 0; ($tagIndex < count($tagMatches[0]) - 1); $tagIndex++)
			{
				$tag = $tagMatches[0][$tagIndex][0];
				$tagStart = $tagMatches[0][$tagIndex][1];
				$tagEnd = ($tagStart + strlen($tag));

				$nextTagStart = $tagMatches[0][$tagIndex + 1][1];

				$variableName = substr($tag, strlen("[GRAB:"));
				$variableName = substr($variableName, 0, strlen($variableName) - strlen("]"));

				// Store grab key name along with what HTML we're grabbing up to
				array_push($tags, array($variableName, substr($scriptLine, $tagEnd, ($nextTagStart - $tagEnd))));
			}

			// Loop through grab tags array $tags, attempting to match them all
			for($tagIndex = 0; $tagIndex < count($tags); $tagIndex++)
			{
				$key = $tags[$tagIndex][0];
				$value = $tags[$tagIndex][1];

				if ($key == "__STARTTAG__")
				{
					if ($value != "")
					{
						$startPos = stripos($this->source, $value, $this->sourceStartPos);

						if (!$startPos) return false;

						$this->sourceStartPos = $startPos + strlen($value);
					}
				}
				else
				{
					$sourceEndPos = stripos($this->source, $value, $this->sourceStartPos);

					if (!$sourceEndPos) return false;

					$this->storeValue($key, substr($this->source, $this->sourceStartPos, $sourceEndPos - $this->sourceStartPos));

					$this->sourceStartPos = $sourceEndPos + strlen($value);
				}
			}

			return true;
		}
	}

// FINDCLOSINGTAGLINE
// Returns line number of the closing tag for the given tag, allowing for nested versions of this tag

	private function findClosingTagLine($scriptLines, $tag, $startLine)
	{
		$unmatchedClosingTags = 1;

		for ($scriptLineNo = $startLine; $scriptLineNo < count($scriptLines); $scriptLineNo++)
		{
			$scriptLine = str_replace(array("\r", "\t"), "", $scriptLines[$scriptLineNo]);

			if (stripos($scriptLine, "[$tag") !== false)
			{
				$unmatchedClosingTags++;
			}
			elseif (strtoupper($scriptLine) == strtoupper("[/$tag]"))
			{
				$unmatchedClosingTags--;
			}

			if ($unmatchedClosingTags == 0) break;
		}

		return (($unmatchedClosingTags > 0) ? false : $scriptLineNo);
	}

// STOREVALUE
// Inserts a value into the $values array for a given key

	private function storeValue($key, $value = null)
	{
		if (array_key_exists($key, $this->values))
		{
			// This key exists and points to an array
			// Push the provided value onto it
			$arrayItems = $this->values[$key];

			array_push($arrayItems, $value);
		}
		else
		{
			// Key doesn't exist
			// Create key and associate it with an array initially containing just this value
			$arrayItems = Array($value);
		}

		$this->values[$key] = $arrayItems;
	}

	private function urlGetContents($url)
	{
		$options = array(
					'http' => array(
	        			'method' => 'GET',
	        			'user_agent' => $this->userAgent,
	        			'header' => array('Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*\/*;q=0.8')
	    			)
				);

		$context = stream_context_create($options);

		if (@get_headers($url, 1) === false)
		{
			trigger_error("Could not open URL: $url");

			$response = "";
		}
		else
		{
			$response = file_get_contents($url, false, $context);
		}

		return $response;
	}
}
?>