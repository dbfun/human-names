#!/usr/bin/php
<?
require(__DIR__ . '/lib/simplehtmldom/simple_html_dom.php');

class itemObj {
  public function __set($name, $value) {
    if ($value) $this->$name = $value;
    }
  }

class documentParser {
  private $database;
  public $count = 0;
  public function __construct(Mongo $mongo) {
    $this->database = $mongo->names;
    }
    
  public function parseNsave($uri, $sex) {
    $html = file_get_html($uri);
    foreach ($html->find('a.js-names-compare-link') as $link) {
      $this->count += $this->parseNsaveName($link->href, $sex) ? 1 : 0;
      }
    }
  private function parseNsaveName($uri, $sex) {
    // $uri = '/names/vladimir/'; // TEST
    $html = file_get_html('http://deti.mail.ru'.$uri);
    
    $name = $html->find('.b-bread__curr', 0);
    if (!$name) return false;
    
    $item = new itemObj();
    
    $item->name = trim(strip_tags($name->innertext));
    
    $item->sex = $sex;
    
    preg_match('#\/.*\/(.*)\/#Ui', $uri, $matches);
    if (isset($matches[1])) $item->alias = $matches[1];
    
    $origin = $html->find('.b-names-issue__info__table__td', 1);
    if ($origin) $item->origin= trim(strip_tags($origin->innertext));
    
    $meaning = $html->find('.b-names-issue__info__table__td', 3);
    if ($meaning) $item->meaning = trim(strip_tags($meaning->innertext));

    
    $description = $html->find('.b-names-issue__text', 0);
    if($description) $item->description = trim($description->innertext);
    
    $nameDay = $html->find('.b-names-issue__text', 1);
    if($nameDay) $item->nameDay = $this->getNameDays($nameDay->innertext);
    
    $this->database->names->insert($item);
    return true;
    }
  
  private static $dates = array();
  private static function getDates($matches) {
    $date = new stdClass();
    $date->month = $matches[1];
    $date->day = $matches[2];
    self::$dates[] = $date;
    }
  
  private function getNameDays($text) {
    self::$dates = array();
    preg_replace_callback('/href=".*\/([a-z]*)\/#day-([0-9]*)"/Ui', 'self::getDates', $text);
    return self::$dates;
    }
  
  
  }
  
$mongo = new Mongo('localhost');
$documentParser = new documentParser($mongo);

for($i = 1; $i <= 5; $i++) { // 374
  $uri = 'http://deti.mail.ru/names/male/?sort=0&ot=asc&page='.$i;
  $documentParser->parseNsave($uri, 'male');
  }
  
for($i = 1; $i <= 5; $i++) { // 464
  $uri = 'http://deti.mail.ru/names/female/?sort=0&ot=asc&page='.$i;
  $documentParser->parseNsave($uri, 'female');
  }

echo (int)$documentParser->count . PHP_EOL;