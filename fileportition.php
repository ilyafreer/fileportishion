<?php
$file = 'access.log.txt'; // путь до файла, можно вынести в параметр к скрипту
// в начале ищем сам файл. Может быть, путь к нему был некорректно указан 
if(!file_exists($file)) exit("Файл не найден"); 
 
// рассмотрим файл как массив
$file_arr = file($file); 
 
// подсчитываем количество строк в массиве 
$lines = count($file_arr); 

$countLinesInNewFile = 100000; // так же можно вынести в параметр или задать как константу
$countNewFiles = $lines/$countLinesInNewFile;
$lineStart = 0;
$h = fopen($file, 'r');
$numRows = 0;
$i=1;
$fp = fopen('file/data_'.$i.'.txt', 'w');
while ($line = fgets($h)){
	if($numRows< $countLinesInNewFile*$i){
		fwrite($fp, $line);
	}else{
		fclose($fp);
		$i+=1;
		$fp = fopen('file/data_'.$i.'.txt', 'w');
		fwrite($fp, $line);
	}
	$numRows +=1;
}
fclose($fp);
