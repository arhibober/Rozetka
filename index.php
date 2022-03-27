<?php
  class photo_data
  {
    public $table;
    public $priseNum;
    public function __construct ($table, $priceNum)
    {
      $this->table = $table;
      $this->priceNum = $priceNum;
    }
    public static function mySort (&$f1, &$f2)
    {
      if ($f1->priceNum < $f2->priceNum)
        return -1;
  	  elseif ($f1->priceNum > $f2->priceNum)
  	    return 1;
  	  else
  	    return 0;
    }
  }
  function get_web_page ($url)
  {
    $uagent = "Mozilla/5.0 (Windows; U; Windows NT 5.1; ru; rv:1.9.0.8) Gecko/2009032609 Firefox/3.0.8";
    $ch = curl_init ($url);
    curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1); // РІРѕР·РІСЂР°С‰Р°РµС‚ РІРµР±-СЃС‚СЂР°РЅРёС†Сѓ
    curl_setopt ($ch, CURLOPT_HEADER, 0); // РЅРµ РІРѕР·РІСЂР°С‰Р°РµС‚ Р·Р°РіРѕР»РѕРІРєРё
    curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, 1); // РїРµСЂРµС…РѕРґРёС‚ РїРѕ СЂРµРґРёСЂРµРєС‚Р°Рј
    curl_setopt ($ch, CURLOPT_ENCODING, ""); // РѕР±СЂР°Р±Р°С‚С‹РІР°РµС‚ РІСЃРµ РєРѕРґРёСЂРѕРІРєРё
    curl_setopt ($ch, CURLOPT_USERAGENT, $uagent); // useragent
    curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 120); // С‚Р°Р№РјР°СѓС‚ СЃРѕРµРґРёРЅРµРЅРёСЏ
    curl_setopt ($ch, CURLOPT_TIMEOUT, 120); // С‚Р°Р№РјР°СѓС‚ РѕС‚РІРµС‚Р°
    curl_setopt ($ch, CURLOPT_MAXREDIRS, 10); // РѕСЃС‚Р°РЅР°РІР»РёРІР°С‚СЊСЃСЏ РїРѕСЃР»Рµ 10-РѕРіРѕ СЂРµРґРёСЂРµРєС‚Р°
    $content = curl_exec ($ch);
    $err = curl_errno ($ch);
    $errmsg = curl_error ($ch);
    $header = curl_getinfo ($ch);
    curl_close ($ch);
    $header['errno'] = $err;
    $header['errmsg'] = $errmsg;
    $header['content'] = $content;
    return $header;
  }  
  set_time_limit (10000);
  $start = microtime (true);
  include "simple_html_dom.php";
  $pd = array();
  $j = 0;
  $k = 0;
  $result = get_web_page
    ("http://rozetka.com.ua/search/?p=0&section=%D0%A4%D0%BE%D1%82%D0%BE%D0%B0%D0%BF%D0%BF%D0%B0%D1%80%D0%B0%D1%82%D1%8B&text=%D0%A4%D0%BE%D1%82%D0%BE%D0%B0%D0%BF%D0%BF%D0%B0%D1%80%D0%B0%D1%82%D1%8B");
  if ($result ["errno"] != 0)
    echo "... ошибка: неправильный url, таймаут, зацикливание ...";
  if ($result ["http_code"] != 200)
    echo "... ошибка: нет страницы, нет прав ...";
  while (($result ["errno"] == 0) && ($result ["http_code"] == 200))
  {
  	if (microtime(true) - $start > 28)
  	  break;  	
    $page = $result ["content"];
    $html = str_get_html ($page);
    $titles = $html->find ('td.detail .title a');
    $prices = $html->find ('td.detail .price .uah');
    for ($i = 0; $i < count ($titles); $i++)
    {
      $pd [$j] = new photo_data (substr (strstr ($titles [$i], ">"), 6, strlen (strstr ($titles [$i],
  	    ">")) - 14).";".substr ($prices [$i], 17, strlen ($prices [$i]) - 44)." грн".";"."<a href=\"h".
  	    substr (strstr ($titles [$i], ">", true), 10, strlen (strstr ($titles [$i], ">", true)) - 10).
  	    ">Подробнее</a>", substr ($prices [$i], 17, strlen ($prices [$i]) - 44));
  	  $j++;
    }
    $k++;
    $result = get_web_page
      ("http://rozetka.com.ua/search/?p=".$k."&section=%D0%A4%D0%BE%D1%82%D0%BE%D0%B0%D0%BF%D0%BF%D0%B0%D1%80%D0%B0%D1%82%D1%8B&text=%D0%A4%D0%BE%D1%82%D0%BE%D0%B0%D0%BF%D0%BF%D0%B0%D1%80%D0%B0%D1%82%D1%8B");
  }
  usort ($pd, array("photo_data", "mySort"));
  $handle = fopen ("rozetka.csv", "w");
  foreach ($pd as $value)
  	fwrite ($handle, $value->table."\n");
  $res = file_get_contents ("rozetka.csv");
  toBD ("Фотоаппараты", $res, date("Y n j"));
  function toBD ($query, $data, $dop)
  {
  	$result = onBD ("photo");
  	$id = 0;
    while ($row = mysql_fetch_array ($result))
      if ($row [0] > $id)
        $id = $row [0];
  	$conn = mysql_connect ("localhost:3306", "root", "")
    or die("Невозможно установить соединение: ".mysql_error());
    $database = "rozetka";
    mysql_select_db ($database); // выбираем базу данных
    $result = mysql_query ("INSERT INTO photo VALUES('".($id + 1)."', '".$query."', '".$data.
      "', '".$dop."')");
    if (!$result)
    {
	  echo "Can't insert into photo";
	  return;
    }
  }
    
  function onBD($table_name)
  {
    $conn = mysql_connect ("localhost:3306", "root", "")
      or die("Невозможно установить соединение: ". mysql_error());
    $database = "rozetka";
    mysql_select_db ($database); // выбираем базу данных
    $result = mysql_query ("SELECT * FROM ".$table_name, $conn);
    if (!$result)
    {
	  echo " Can't select from ($table_name) ";
	  return;
    }
    return $result;
  }
?>