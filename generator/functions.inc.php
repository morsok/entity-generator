<?php

/**
 * @param string $source
 * @param string $destination
 * @param bool $include_dir
 * @return bool
 */
function createZipFile($source, $destination, $include_dir = false)
{
    if (!extension_loaded('zip') || !file_exists($source)) {
        return false;
    }

    if (file_exists($destination)) {
        unlink($destination);
    }

    $zip = new ZipArchive();

    if (!$zip->open($destination, ZIPARCHIVE::CREATE)) {
        return false;
    }
    $source = str_replace('\\', '/', realpath($source));

    if (is_dir($source) === true) {
        $files = new RecursiveIteratorIterator(
          new RecursiveDirectoryIterator($source),
          RecursiveIteratorIterator::SELF_FIRST
        );

        if ($include_dir) {
            $arr = explode('/', $source);
            $maindir = $arr[count($arr) - 1];
            $source = '';

            for ($i = 0; $i < count($arr) - 1; $i++) {
                $source .= '/' . $arr[$i];
            }

            $source = substr($source, 1);

            $zip->addEmptyDir($maindir);
        }

        foreach ($files as $file) {
            $file = str_replace('\\', '/', $file);

            // Ignore '.' and '..' folders
            if (in_array(
              substr($file, strrpos($file, '/') + 1),
              array('.', '..')
            )) {
                continue;
            }

            $file = realpath($file);

            if (is_dir($file) === true) {
                $zip->addEmptyDir(str_replace($source . '/', '', $file . '/'));
            } else {
                if (is_file($file) === true) {
                    $zip->addFromString(
                      str_replace($source . '/', '', $file),
                      file_get_contents($file)
                    );
                }
            }
        }
    } else {
        if (is_file($source) === true) {
            $zip->addFromString(basename($source), file_get_contents($source));
        }
    }

    return $zip->close();
}

/**
 * @param array $supported
 * @param string $default
 * @return string
 */
function detectLanguage(array $supported, $default = 'en')
{
    $language = '';

    // Detection.
    if (isset($_GET['language'])) {
        $language = $_GET['language'];
    } elseif (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
        if (preg_match('/^fr/i', trim($_SERVER['HTTP_ACCEPT_LANGUAGE']))) {
            $language = 'fr';
        }
    }

    // Fallback if not supported.
    if (!in_array($language, $supported)) {
        $language = $default;
    }

    return $language;
}

/**
 * @param string $source
 * @param string $dir
 * @param array $replace_tokens
 * @return string
 */
function parseTemplate($source, $dir, $replace_tokens)
{
    $data = file_get_contents(BASE_SRC . '/' . $source);
    $data = strtr($data, $replace_tokens);
    $destination = strtr($source, $replace_tokens);

    file_put_contents($dir . '/' . $destination, $data);

    return $dir . '/' . $destination;
}
