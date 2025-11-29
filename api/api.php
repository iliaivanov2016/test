<?php
require_once __DIR__ . "/config.php";
require_once __DIR__ . "/db.php";

$memcache = new Memcache;
$memcache->connect(TEST_MC_HOST, TEST_MC_PORT) or die ("Failed to connect MemCached");

class TestAPI extends stdClass{
    
    public static function get_news(){ 
        
        global $memcache;
        
        $rss_url = TEST_RSS_URL;
        
        if (defined("TEST_DEBUG")) test_log(">get_news \n$rss_url");
        
        // get RSS content by CURL
        $ch = curl_init($rss_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        $content = curl_exec($ch);
        curl_close($ch);        
        
        $xml = simplexml_load_string($content);
        
        if (defined("TEST_DEBUG")) test_log("1.get_news \n".print_r($xml, true));
        
        foreach ($xml->channel->item as $item){
            if (defined("TEST_DEBUG")) test_log("2.get_news \n".print_r($item, true));
            
            $url = $item->link;
            $crc = crc32($url);
            
            $key = "ITEM|" . $crc;
            
            $res = $memcache->get($key);
            if (defined("TEST_DEBUG")) test_log("2.get_news \n".print_r($res, true));
            
            if (!$res){
                // new item found
                if (defined("TEST_DEBUG")) test_log("3.get_news ADD NEW ITEM...");
                $key = "TERM|" . $item->category;
                $category_id = (int)$memcache->get($key);
                if (!$category_id) {
                    // add new category
                    if (defined("TEST_DEBUG")) test_log("4.get_news ADD NEW CATEGORY...");
                    DB::query("INSERT INTO `terms` VALUES (NULL,?)", [$item->category]);
                    $category_id = DB::get_last_insert_id();
                    if (defined("TEST_DEBUG")) test_log("5.get_news ADD NEW CATEGORY... id = $category_id");
                    $memcache->set($key, $category_id);
                }                
            }
            
            
            die ("!");
        }

        
        die ("get_news!");
    }
    
}