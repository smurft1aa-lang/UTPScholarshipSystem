<?php
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__ . '/..'));
foreach ($it as $file) {
    if ($file->isFile() && $file->getExtension() === 'php' && strpos($file->getPathname(), 'vendor') === false) {
        $content = file_get_contents($file->getPathname());
        $orig = $content;
        
        $content = preg_replace_callback('/require_once\s+__DIR__\s*\.\s*[\'"](.*?)\/includes\/(ai_engine|audit|auth|CSRF|GradeMapper|InputSanitizer|mailer|RateLimiter|RoleGuard|security|SessionManager|UserAuth|telemetry)\.php[\'"]\s*;/i', function($m) {
            if (strpos($m[0], 'init.php') !== false) return $m[0];
            return 'require_once __DIR__ . \'' . $m[1] . '/includes/init.php\';';
        }, $content);
        
        if ($content !== $orig) {
            // Deduplicate multiple init.php requires
            $lines = explode("\n", $content);
            $newLines = [];
            $hasInit = false;
            foreach ($lines as $line) {
                if (strpos($line, 'includes/init.php') !== false) {
                    if ($hasInit) continue;
                    $hasInit = true;
                }
                $newLines[] = $line;
            }
            file_put_contents($file->getPathname(), implode("\n", $newLines));
            echo "Updated {$file->getPathname()}\n";
        }
    }
}
// Remove deprecated files
$deprecated = ['ai_engine.php', 'audit.php', 'auth.php', 'CSRF.php', 'GradeMapper.php', 'InputSanitizer.php', 'mailer.php', 'RateLimiter.php', 'RoleGuard.php', 'security.php', 'SessionManager.php', 'UserAuth.php', 'telemetry.php'];
foreach ($deprecated as $f) {
    $p = __DIR__ . '/../includes/' . $f;
    if (file_exists($p)) {
        unlink($p);
        echo "Deleted $p\n";
    }
}
