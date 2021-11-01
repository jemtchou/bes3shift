<!DOCTYPE html>
<html lang="en">
<head>
  <title>Shibboleth Test</title>
</head>
<body>
    <h1>Shibboleth Test</h1>
<?php
// Include the Shibboleth attributes you intend to test here
$attributes = array('displayName', 'mail', 'eppn',
                    'givenName', 'sn', 'affiliation', 'unscoped-affiliation');
foreach($attributes as $a){
    print "<p>";
    print "<strong>$a</strong> = ";
    print isset($_SERVER[$a]) ? $_SERVER[$a] : "<em>Undefined</em>";
    print "</p>";
}

phpinfo();
?>
</body>
</html>
