<?php

namespace OfxParser;

/**
 * An OFX parser library
 *
 * Heavily refactored from Guillaume Bailleul's grimfor/ofxparser
 *
 * @author Guillaume BAILLEUL <contact@guillaume-bailleul.fr>
 * @author James Titcumb <hello@jamestitcumb.com>
 * @author Oliver Lowe <mrtriangle@gmail.com>
 */
class Parser
{
    /**
     * Load an OFX file into this parser by way of a filename
     *
     * @param string $ofxFile A path that can be loaded with file_get_contents
     * @return Ofx
     * @throws \Exception
     */
    public function loadFromFile($ofxFile)
    {
        if (!file_exists($ofxFile)) {
            throw new \InvalidArgumentException("File '{$ofxFile}' could not be found");
        }

        return $this->loadFromString(file_get_contents($ofxFile));
    }

    /**
     * Load an OFX by directly using the text content
     *
     * @param string $ofxContent
     * @return  Ofx
     * @throws \Exception
     */
    public function loadFromString($ofxContent)
    {
        $ofxContent = utf8_encode($ofxContent);
        $ofxContent = $this->conditionallyAddNewlines($ofxContent);

        $sgmlStart = stripos($ofxContent, '<OFX>');
        $ofxSgml = trim(substr($ofxContent, $sgmlStart));

        $ofxXml = $this->convertSgmlToXml($ofxSgml);

	//print_r( $ofxXml, true );
//	var_dump( $ofxXml );
        $xml = $this->xmlLoadString($ofxXml);
//	var_dump( $xml );
	//print_r( $xml );
        return new Ofx($xml);
    }

    /**
     * Detect if the OFX file is on one line. If it is, add newlines automatically.
     *
     * @param string $ofxContent
     * @return string
     */
    private function conditionallyAddNewlines($ofxContent)
    {
        if (preg_match('/<OFX>.*<\/OFX>/', $ofxContent) === 1) {
            $ofxContent =  str_replace('<', "\n<", $ofxContent); // add line breaks to allow XML to parse
		var_dump( __FILE__ . "::" .( __FILE__ . "::" . __LINE__ ). "::" . $ofxContent );
            //return str_replace('<', "\n<", $ofxContent); // add line breaks to allow XML to parse
        }

        return $ofxContent;
    }

    /**
     * Load an XML string without PHP errors - throws exception instead
     *
     * @param string $xmlString
     * @throws \Exception
     * @return \SimpleXMLElement
     */
    private function xmlLoadString($xmlString)
    {
        libxml_clear_errors();
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xmlString);

        if ($errors = libxml_get_errors()) {
            throw new \RuntimeException('Failed to parse OFX: ' . var_export($errors, true));
        }

        return $xml;
    }

    /**
     * Detect any unclosed XML tags - if they exist, close them
     *
     * @param string $line
     * @return string
     */
    private function closeUnclosedXmlTags_orig($line)
    {
	var_dump( __FILE__ . "::" .( __FILE__ . "::" . __LINE__ )); 
	var_dump( $line );
 	$line = trim($line);
        $tag = ltrim(
			substr(	$line, 
				1, 
				strpos($line, '>') - 1
			), 
			'/' );

        // Line is "<SOMETHING>" or "</SOMETHING>"
        if ($line === '<' . $tag . '>' || $line === '</' . $tag . '>') {
		var_dump( __FILE__ . "::" .( __FILE__ . "::" . __LINE__ )); 
		var_dump( $line );
            return $line;
        }

 	// Tag is properly closed i.e. there is a close tag on the line.  Doesn't mean the TAG matches though!
        if (strpos($line, '</' . $tag . '>') !== false) {
		var_dump( __FILE__ . "::" .( __FILE__ . "::" . __LINE__ )); 
		var_dump( $line );
            return $line;
        }

        $lines = explode("\n", str_replace('</', "\n" . '</', $line));
        $lines[0] = trim($lines[0]) . '</' . $tag .'>';
        return implode('', $lines);
    }

    /**
     * Detect any unclosed XML tags - if they exist, close them
     *
  string(68) "<OFX><SIGNONMSGSRSV1><SONRS><STATUS><CODE>0<SEVERITY>INFO<MESSAGE>OK"
  [1]=>
  string(109) "</STATUS><DTSERVER>20240223062422<LANGUAGE>ENG<DTPROFUP>20240223062422<DTACCTUP>20240223062422<INTU.BID>00005"
  [2]=>
  string(8) "</SONRS>"
  [3]=>
  string(17) "</SIGNONMSGSRSV1>"
}
*/
    private function closeUnclosedXmlTags($line)
	{
 	$line = trim($line);
        $tag = ltrim(
			substr(	$line, 
				1, 
				strpos($line, '>') - 1
			), 
			'/' );

        // Line is "<SOMETHING>" or "</SOMETHING>"
        if ($line === '<' . $tag . '>' || $line === '</' . $tag . '>') {
		var_dump( __FILE__ . "::" .( __FILE__ . "::" . __LINE__ )); 
		var_dump( $line );
            return $line;
        }

 	// Tag is properly closed i.e. there is a close tag on the line.  Doesn't mean the TAG matches though!
        if (strpos($line, '</' . $tag . '>') !== false) {
		var_dump( __FILE__ . "::" .( __FILE__ . "::" . __LINE__ )); 
		var_dump( $line );
            return $line;
        }

	var_dump(( __FILE__ . "::" . __LINE__ ));
//This separates on closing tags.  However, some files are <tag1><tag2><tag3><tag4></tag1> so this isn't working.
        $lines = explode("\n", str_replace('</', "\n" . '</', $line));
		var_dump( __FILE__ . "::" .( __FILE__ . "::" . __LINE__ )); 
		var_dump( $lines );
/*
        $newline = explode("\n", str_replace('<', "\n" . '<', $lines));
		var_dump(( __FILE__ . "::" . __LINE__ ));
		var_dump( $newline );
	foreach( $newline as $line2 )
	{
		var_dump(( __FILE__ . "::" . __LINE__ ));
		var_dump( $line2 );
		$newline[] = $this->closeUnclosedXmlTags( $line2 );
	}
*/
	foreach( $lines as $line2 )
	{
		var_dump(( __FILE__ . "::" . __LINE__ ));
		var_dump( $line2 );
		$newline[] = $this->closeUnclosedXmlTags( $line2 );
	}
	var_dump(( __FILE__ . "::" . __LINE__ ));
	var_dump( $newline );
	implode( '', $newline );
	var_dump(( __FILE__ . "::" . __LINE__ ));
	var_dump( $newline );
        $lines[0] = trim($lines[0]) . '</' . $tag .'>';
	var_dump(( __FILE__ . "::" . __LINE__ ));
	var_dump( $lines );
        return implode('', $lines);
    }
    /**
     * Detect any unclosed XML tags - if they exist, close them
     *
     * @param string $line
     * @return string
     */
    private function closeUnclosedXmlTags_orig($line)
    {
        // Matches: <SOMETHING>blah
        // Does not match: <SOMETHING>
        // Does not match: <SOMETHING>blah</SOMETHING>
        if (preg_match(
            "/<([A-Za-z0-9.]+)>([\wà-úÀ-Ú0-9\.\-\_\+\, ;:\[\]\'\&\/\\\*\(\)\+\{\|\}\!\£\$\?=@€£#%±§~`\"]+)$/",
            trim($line),
            $matches
        )) {
            return "<{$matches[1]}>{$matches[2]}</{$matches[1]}>";
        }
        return $line;
    }

    /**
     * Convert an SGML to an XML string
     *
	*
	*	20240707 this is the function that was here originally
     * @param string $sgml
     * @return string
     */
    private function convertSgmlToXml_orig($sgml)
    {
