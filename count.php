<?php
$tokens = token_get_all(file_get_contents('storage/framework/views/013d22581ea5e1cda6b8e1aeee528ee3.php'));
$if = 0; $endif = 0;
foreach($tokens as $t) {
    if(is_array($t)) {
        if($t[0] == T_IF) $if++;
        if($t[0] == T_ENDIF) $endif++;
    }
}
echo "if: $if, endif: $endif\n";
