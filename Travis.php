<?php
$server = proc_open(PHP_BINARY.' src/pocketmine/PocketMine.php --no-wizard --disable-readline', [
    0 => ['pipe', 'r'],
    1 => ['pipe', 'w'],
    2 => ['pipe', 'w'],
], $pipes);
fwrite($pipes[0], "version\nmakeplugin PureEntitiesX\nstop\n\n");
while(!feof($pipes[1])){
    echo fgets($pipes[1]);
}
fclose($pipes[0]);
fclose($pipes[1]);
fclose($pipes[2]);
echo "\n\nReturn value: ".proc_close($server)."\n";
if(count(glob('plugins/DevTools/PureEntitiesX*.phar')) === 0){
    echo "Failed to create a PureEntitiesX phar!\n";
    exit(1);
}else{
    $fn = glob('plugins/DevTools/PureEntitiesX*');
    rename($fn[0], 'plugins/DevTools/PureEntitiesX.phar');
    echo "A PureEntitiesX phar was created!\n";
    exit(0);
}
