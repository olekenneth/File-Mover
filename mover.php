<?
/**
 * File Mover
 *
 * Helps you organize your movies and tv show library.
 * Feel free to reuse the script for something else. After all its purpose is to use regexp-rules to move files.
 *
 * Use: php mover.php ../Incoming ../Videos
 *
 * PHP version 5
 *
 * @package    File Mover
 * @author     Ole-Kenneth Bratholt <ok@rait.no>
 * @copyright  2010-2016 Ole-Kenneth Bratholt
 * @license    https://opensource.org/licenses/MIT  MIT License
 * @link       http://github.com/olekenneth/File-Mover
 */

function parse($filename) {
    $rules = array(
        'TV Shows' => array(
            'Show.Name.S01E02' => array(
                array('(.*) S(\d{1,2})E(\d{1,2}).*', '\1/Season \2/\1 - S\2E\3'),
            ),
            'Show.Name.1x02' => array(
                array('(.*) (\d{1,2})x(\d{2}).*', '\1/Season \2/\1 - S\2E\3'),
            ),
        ),
        'Movies' => array(
            'Movie.2010' => array(
                array('(.*)(\d{4}).*', '\1(\2)/\1(\2)'),
            ),
        ),
    );

    $used_rule = null;
    $filename = trim(preg_replace('/((US)?[-._ ])+/i', ' ', ucfirst($filename)));
    foreach($rules as $type => $array) {
        if (is_array($array)) {
            foreach($array as $rulename => $rulearray) {
                if (is_array($rulearray)) {
                    if (preg_match('/' . $rulearray[0][0] . '/i', $filename)) {
                        $used_rule = $type;
                        foreach($rulearray as $rule) {
                            $filename = trim(preg_replace('/' . (string)$rule[0] . '/i', (string)$rule[1], $filename));
                        }
                    }
                }
            }
        }
    }
    return array('rule' => $used_rule, 'filename' => $filename);
}

function read_movie_dir($dir, $store_dir) {
    $dir = realpath($dir);
    $store_dir = realpath($store_dir);

    $ignore = array('.', '..', '.DS_Store');
    $banned_ext = array('txt', 'part', 'nfo', 'jpg', 'gif', 'png', 'rar', 'zip', 'r00', 'r01');
    $banned_filename = array('sample');

    if ($handle = opendir($dir)) {
        while (false !== ($file = readdir($handle))) {
            if (in_array($file, $ignore)) continue;
            if (is_dir($dir . '/' . $file)) {
                read_movie_dir($dir . '/' . $file, $store_dir);
            } else {
                $path = pathinfo($file);
                if ((!in_array($path['extension'], $banned_ext)) && (!in_array($file, $banned_filename))) {
                    $parsed = parse($file);

                    if (isset($parsed['rule'])) {
                        $filename = $store_dir . '/' . $parsed['rule'] . '/' . $parsed['filename'] . '.' . $path['extension'];
                        $path2 = explode('/', $parsed['filename']);

                        if (!file_exists($filename)) {
                            $filePath = $store_dir . '/' . $parsed['rule'] . '/' . $path2[0] . '/' . $path2[1];
                            if (!file_exists($filePath)) {
                                if (!mkdir($filePath, 0777, true)) {
                                    echo 'Unable to create directory' + $filePath + "\n\n";
                                }
                            }

                            if (rename($dir . '/' . $file, $filename)) {
                                echo 'Moved file ' . $file . "\n\n";
                            }
                        }
                    } else {
                        // echo "\n\n" . $file . ' did not match any known regexp';
                        // If we somehow didn't move the file. Maybe because the movie didn't have a year like this: summer.vacation.2010.avi
                    }
                }
            }
        }
        closedir($handle);
    }
}
if (!isset($argv[1])) {
    die('Need source directory');
}

$dest = getcwd();
if (!isset($argv[2])) {
    echo 'No destination directory set. Using ' . $dest;
} else {
    $dest = $argv[2];
}

read_movie_dir($argv[1], $dest);
