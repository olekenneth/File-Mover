<?
/**
 * File Mover
 *
 * Helps you organize your movies and tv show library.
 * Feel free to reuse the script for something else. After all its purpose is to use regexp-rules to move files.
 *
 * PHP version 5
 *
 * @package    File Mover
 * @author     Rait <ok@rait>
 * @copyright  2010-2011 Rait
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @link       http://github.com/rait-no/File-Mover
 */

$dir = "/Volumes/External/Imported"; 	// This is the directory you want to read from...
$store_dir = "/Volumes/External/"; 		// Here you need directories named exactly the same as the rules. Ex: TV Shows and Movies

function parse($filename) {
$rules = array(
				"TV Shows" => array(
									"Show.Name.S01E02" => array(
										array('(.*) S0?(\d{1,2})E(\d{1,2}).*', '\1/Season \2/\1 - S\2E\3'),
									),
									"Show.Name.1x02" => array(
										array('(.*) (\d{1,2})x(\d{2}).*', '\1/Season \2/\1 - S\2E\3'),
									),
				),
				"Movies" => array(
									"Movie.2010" => array(
										array('(.*)(\d{4}).*', '\1(\2)/\1(\2)'),
									),
				),
);

$filename = preg_replace('/[-._ ]+/i', " ", ucfirst($filename));
foreach($rules as $type => $array) {
	if (is_array($array)) {
		foreach($array as $rulename => $rulearray) {
			if (is_array($rulearray)) {
				if (preg_match("/{$rulearray[0][0]}/i", $filename)) {
					$used_rule = $type;
					foreach($rulearray as $rule) {
						$filename = preg_replace("/".(string)$rule[0]."/i", (string)$rule[1], $filename);
					}
				}
			}
		}
	}
}
return array("rule" => $used_rule, "filename" => $filename);
}

function read_movie_dir($dir, $store_dir) {
$ignore = array(".", "..", ".DS_Store");
$banned_ext = array("txt", "part", "nfo", "jpg", "gif", "png", "rar", "zip", "r00", "r01");
$banned_filename = array("sample");
if ($handle = opendir($dir)) {
    while (false !== ($file = readdir($handle))) {
		if (in_array($file, $ignore)) continue;
		if (is_dir("{$dir}/{$file}")) {
			read_movie_dir($dir."/".$file);
		} else {
			$path = pathinfo($file);
			if ((!in_array($path['extension'], $banned_ext)) && (!in_array($file, $banned_filename))) {
				$parsed = parse("$file");
				if (isset($parsed['rule'])) {
					$filename = $store_dir."/".$parsed['rule']."/".$parsed['filename'].".".$path['extension'];
					$path2 = explode("/", $parsed['filename']);
        			if (!file_exists($filename)) {
        				if (!file_exists($store_dir."/".$parsed['rule']."/".$path2[0])) {
        					mkdir($store_dir."/".$parsed['rule']."/".$path2[0]);
        				}
        				if (rename("$dir/$file",$filename)) {
        					exec('/usr/local/bin/growlnotify -t FileMover --image "'.$store_dir.'/'.$parsed['rule'].'.png" -m "Moved file '.$file.'"');
        				}
        			}
        		} else {
        			// If we somehow didn't move the file. Maybe because the movie didn't have a year like this: summer.vacation.2010.avi
        		}
        	}
        }
    }
    closedir($handle);
}
}
read_movie_dir($dir, $store_dir);
?>