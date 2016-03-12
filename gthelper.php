#!/usr/bin/php -f
<?php
/*
 * The MIT License (MIT)
 * 
 * Copyright (c) 2016 Saso Kiselkov
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to
 * deal in the Software without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or
 * sell copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 */

$commands = array("at", "pause", "backup", "reverse", "set", "when", "and");

$xmlstr = "";
$fp = fopen("php://stdin", "r");
while (!feof($fp))
	$xmlstr .= fread($fp, 1 << 20);
fclose($fp);

$xml = new SimpleXMLElement($xmlstr);

# Construct an in-memory ID-to-object mapping for faster access
$objects = $xml->xpath("//object");
$id_to_objs = array();
foreach ($objects as $object) {
	$id = $object->xpath("@id")[0];
	$id_to_objs[(int)$id] = $object;
}

# Read through all string objects which contain a ':' in the name
$strings = $xml->xpath("//object[@class='WED_StringPlacement']" .
    "[hierarchy[contains(@name, ':')]]");
foreach ($strings as $string)
{
	$name_comps = explode(":", $string->xpath("hierarchy/@name")[0]);
	$route_name = $name_comps[0];
	$vehicle = $name_comps[1];
	$spacing = $string->xpath("string_placement/@spacing")[0];

	echo "# $route_name\n";
	echo "route $spacing 0 0 $vehicle\n";
	$node_ids = $string->xpath("children/child/@id");
	foreach ($node_ids as $node_id) {
		$node = $id_to_objs[(int)$node_id];
		$node_name = $node->xpath("hierarchy/@name")[0];
		$point = $node->xpath("point")[0];
		$lat = $point->xpath("@latitude")[0];
		$lon = $point->xpath("@longitude")[0];
		echo "$lat $lon\n";

		$comps = explode("|", $node_name);
		foreach ($comps as $comp) {
			$cmd_comps = explode(" ", $comp);
			$cmd = $cmd_comps[0];
			if (in_array($cmd, $commands))
				echo "$comp\n";
		}
	}
	echo "\n";
}

?>
