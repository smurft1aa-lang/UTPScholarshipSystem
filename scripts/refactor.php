<?php
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__));
$count = 0;
foreach ($it as $file) {
    if ($file->isFile() && $file->getExtension() === 'php' && strpos($file->getPathname(), 'vendor') === false) {
        if ($file->getFilename() === 'init.php' || $file->getFilename() === 'refactor.php') continue;
        $content = file_get_contents($file->getPathname());
        $orig = $content;
        
        // Match both variations (with and without space)
        $content = str_replace("require_once __DIR__ . '/../includes/init.php';", "require_once __DIR__ . '/../includes/init.php';", $content);
        
        
        if ($content !== $orig) {
            file_put_contents($file->getPathname(), $content);
            $count++;
        }
    }
}
echo "Refactored $count files.";
