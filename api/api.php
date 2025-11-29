<?php
require_once __DIR__ . "/config.php";
require_once __DIR__ . "/db.php";

$memcache = new Memcache;
$memcache->connect(TEST_MC_HOST, TEST_MC_PORT) or die ("Failed to connect MemCached");

class TestAPI extends stdClass{
    
    public static function get_news(){ 
        
        global $memcache;
        
        $rss_url = TEST_RSS_URL;
        $news_added = 0;
        
        if (defined("TEST_DEBUG")) test_log(">get_news \n$rss_url");
        
        // get RSS content by CURL
        $ch = curl_init($rss_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        $content = curl_exec($ch);
        curl_close($ch);        
        
        $xml = simplexml_load_string($content);
        
        if (defined("TEST_DEBUG")) file_put_contents(__DIR__."/news.htm", $content);
        
        foreach ($xml->channel->item as $item){
            if (defined("TEST_DEBUG")) test_log("1.get_news \n".print_r($item, true));
            
            $url = $item->link;
            $crc = crc32($url);
            
            $item_key = "ITEM|" . $crc;
            
            $news_id = (int)$memcache->get($item_key);
            if (defined("TEST_DEBUG")) test_log("2.get_news news_id = $news_id");
            
            if (!$news_id){
                // new item found
                if (defined("TEST_DEBUG")) test_log("3.get_news ADD NEW ITEM...");
                $term_key = "TERM|" . $item->category;
                $term_id = (int)$memcache->get($term_key);
                if (!$term_id) {
                    // add new category
                    if (defined("TEST_DEBUG")) test_log("4.get_news ADD NEW CATEGORY...");
                    DB::query("INSERT INTO `terms` VALUES (NULL,?)", [$item->category]);
                    $term_id = DB::get_last_insert_id();
                    if (defined("TEST_DEBUG")) test_log("5.get_news ADD NEW CATEGORY... term_id = $term_id $term_key");
                    $memcache->set($term_key, $term_id);
                }
                // parse date
                $s = trim(str_replace('+0300', '', $item->pubDate));
                $t = strtotime($s);
                // extract slug from url
                $ar = explode("/", $item->link);
                $slug = trim(end($ar));
                unset($ar);
                // get image url
                $image_url = PDO::NULL_EMPTY_STRING;
                $p = stripos($content, $item->link);
if (defined("TEST_DEBUG")) test_log("1.p = $p");
                if ($p !== false){
                    $tag0 = '<enclosure url="';
                    $tag1 = '"';
                    $p += strlen($item->link);
                    $p = stripos($content, $tag0, $p);
if (defined("TEST_DEBUG")) test_log("2.p = $p");
                    if ($p !== false){
                        $p += strlen($tag0);
if (defined("TEST_DEBUG")) test_log("3.p = $p");
                        $p1 = stripos($content, $tag1, $p);
if (defined("TEST_DEBUG")) test_log("4.p1 = $p1");
                        if ($p1 !== false){
                            $image_url = trim(substr($content, $p, $p1-$p));
if (defined("TEST_DEBUG")) test_log("img=$image_url");
                        }
                    }                    
                }
                if (defined("TEST_DEBUG")) test_log("6.get_news ADD NEW CATEGORY...  term_id = $term_id $term_key\n$slug\n$image_url\n".$item->pubDate. " ".@date("Y-m-d H:i:s", $t));
                die ("2");
                
                DB::query("INSERT INTO `terms` VALUES (NULL,?,?,?,?,?,?,?,?)", [
                    $term_id,
                    $crc,
                    @date("Y-m-d", @date("Y-m-d", $t)),
                    @date("Y-m-d", @date("H:i:s", $t)),
                    $item->title,
                    $slug,
                    $item->link,  
                    $image_url
                ]);        
                $news_id = DB::get_last_insert_id();
                $memcache->set($item_key, $news_id);
                $news_added++;
            }            
        }
        if (defined("TEST_DEBUG")) test_log("<get_news news_added = $news_added");
        die ("get_news! $news_added");
    }
    
}