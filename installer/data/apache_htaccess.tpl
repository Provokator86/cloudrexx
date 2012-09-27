#RewriteLog "logs/rewrite.log"
#RewriteLogLevel 9

# This is ASCMS_PATH_OFFSET, i.e.
RewriteBase   %PATH_ROOT_OFFSET%

# Folders that may be accessed by page requests are NO ALIASES by design
RewriteRule ^(\w\w\/)?(_meta|admin|cache|cadmin|changelog|config|core|core_modules|customizing|feed|images|installer|lang|lib|media|model|modules|testing|themes|tmp|update|webcam|favicon.ico)(\/|$)(.*) $2$3$4 [L,QSA]

# Anything that is neither a directory nor a file *might* be an alias.
# Append the entire request to the query string.
# See core/Dispatcher.class.php, look for "CONTREXX_ALIAS_PARAMETER"
# The Dispatcher will decide.
# Warning: Overhead!
RewriteCond   %{REQUEST_FILENAME}  index.php
RewriteRule   .  index.php?__cap=%{REQUEST_URI} [L,QSA]

RewriteCond   %{REQUEST_FILENAME}  !-d
RewriteCond   %{REQUEST_FILENAME}  !-f
RewriteRule   .  index.php?__cap=%{REQUEST_URI} [L,QSA]

RewriteCond %{REQUEST_FILENAME} ^\w+/sitemap.xml$
RewriteRule ^(\w+)/.*$ sitemap_$1.xml [L,NC]