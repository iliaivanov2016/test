<?php
require_once __DIR__ . "/config.php";
require_once __DIR__ . "/db.php";

$memcache = new Memcache;
$memcache->connect(TEST_MC_HOST, TEST_MC_PORT) or die ("Failed to connect MemCached");


class TestAPI extends stdClass{
    
    
    public static function news_cron(){ 
        
        global $memcache;
        
        $rss_url = TEST_RSS_URL;
        $news_added = 0;
        
        if (defined("TEST_DEBUG_CRON")) test_log(">news_cron\n$rss_url");
        
        // get RSS content by CURL
        $ch = curl_init($rss_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        $content = curl_exec($ch);
        curl_close($ch);        
        
        $xml = simplexml_load_string($content);
        $tag_end = '</item>';
        $tag0 = '<enclosure url="';
        $tag1 = '"';
        
        foreach ($xml->channel->item as $item){
            if (defined("TEST_DEBUG_CRON")) test_log("1.news_cron \n".print_r($item, true));
            
            $url = $item->link;
            $crc = crc32($url);
            
            $item_key = "ITEM|" . $crc;
            
            $news_id = (int)$memcache->get($item_key);
            if (defined("TEST_DEBUG_CRON")) test_log("2.news_cron news_id = $news_id");
            
            //if (!$news_id){
            if (1){
                // new item found
                if (defined("TEST_DEBUG_CRON")) test_log("3.news_cron ADD NEW ITEM...");
                $term_key = "TERM|" . $item->category;
                $term_id = (int)$memcache->get($term_key);
                if (!$term_id) {
                    // add new category
                    if (defined("TEST_DEBUG_CRON")) test_log("4.news_cron ADD NEW CATEGORY...");
                    DB::query("INSERT INTO `terms` VALUES (NULL,?)", [$item->category]);
                    $term_id = DB::get_last_insert_id();
                    if (defined("TEST_DEBUG_CRON")) test_log("5.news_cron ADD NEW CATEGORY... term_id = $term_id $term_key");
                    $memcache->set($term_key, $term_id);
                }
                // parse date
                $s = trim( str_replace('+0300', '', $item->pubDate) );
                $t = strtotime($s);
                // extract slug from url
                $ar = explode("/", $item->link);
                $slug = trim( end($ar) );
                unset($ar);
                // get image url
                $image_url = null;
                if (is_object($item->enclosure)){
                    $image_url = $item->enclosure->attributes()->url;
                }
                if (defined("TEST_DEBUG_CRON")) test_log("6.news_cron ADD NEW CATEGORY...  term_id = $term_id $term_key\n$slug\n$image_url\n".$item->pubDate. " ".@date("Y-m-d H:i:s", $t));
                DB::query("INSERT INTO `news` VALUES (NULL,?,?,?,?,?,?,?,?)", [
                    $term_id,
                    $crc,
                    @date("Y-m-d", $t),
                    @date("H:i:s", $t),
                    $item->title,
                    $slug,
                    $item->link,  
                    $image_url
                ]);        
                $news_id = DB::get_last_insert_id();
                $memcache->set($item_key, $news_id);
                $news_added++;
                if (defined("TEST_DEBUG_CRON")) test_log("7.news_cron news_added = $news_added");
            }            
        }
        
        if (defined("TEST_DEBUG_CRON")) test_log("<news_cron news_added = $news_added");
        
    }    
    
    
    public static function get_default_term(){
        
        $terms = self::get_terms();
        return $terms["terms"][0]["title"];
        
    }


    public static function get_current_date_term(){
        
        $s = (isset($_REQUEST["path"])) ? $_REQUEST["path"] : "";
        $ar = (!empty($s)) ? explode("/", $s) : [];
        $term = "";
        if ((is_array($ar)) && (count($ar) == 2)){
            if (defined("TEST_DEBUG")) test_log(">>get_current_date_term\ns=$s\n".print_r($ar, true));
            $term = array_pop($ar);
            // limit 250 max
            if (strlen($term) > 250){
                $term = substr($term, 0, 250); 
            }
            if (defined("TEST_DEBUG")) test_log(">>>get_current_date_term\nterm = $term");
            $dt = array_pop($ar); // YYYYMMDD
            // limit 8
            if (strlen($dt) > 8){
                $dt = substr($dt, 0, 8); 
            }
            if (defined("TEST_DEBUG")) test_log(">>>get_current_date_term\ndt = $dt");
            $year = (int)substr($dt,0,4);
            $month = (int)substr($dt,4,2);
            $day = (int)substr($dt,6,2);
            $date = "{$year}-{$month}-{$day}";
        }
        
        if (defined("TEST_DEBUG")) test_log("1.get_current_date_term term = $term date = $date");
        
        $res = [
            "date"  => (empty($term)) ? @date("Y-m-d") : $date,
            "term"  => (empty($term)) ? self::get_default_term() : $term
        ]; 
        
        if (defined("TEST_DEBUG")) test_log("<get_current_date_term\ns=$s\n".print_r($res, true));
        
        return $res;
        
    }
    
    
    public static function get_terms(){
        
        global $memcache;
       
        $term_key = "TEST_TERMS";
        
        $res = $memcache->get($term_key);        
        $t = time();
        
        if (defined("TEST_DEBUG")) test_log(">get_terms t = $t\n".print_r($res, true));
        
        if ($t - $res["t"] > TEST_MAX_CACHE_TIME){
            $res = false;
        }
        
        if (!$res) {

            if (defined("TEST_DEBUG")) test_log("1.get_terms load data from db");
            
            $res = [
                "t"      => time(),
                "terms"  => DB::select("SELECT * FROM `terms` ORDER BY `id`")
            ];
            $memcache->set($term_key, $res);
           
        } 
        
        if (defined("TEST_DEBUG")) test_log("<get_terms t = $t\n".print_r($res, true));
        
        return $res;
    }

    
    public static function get_dates(){
        
        global $memcache;
       
        $date_key = "TEST_DATES";
        
        $res = $memcache->get($date_key);        
        $t = time();
        
        if (defined("TEST_DEBUG")) test_log(">get_dates t = $t\n".print_r($res, true));
        
        if ($t - $res["t"] > TEST_MAX_CACHE_TIME){
            $res = false;
        }
        
        if (!$res) {

            if (defined("TEST_DEBUG")) test_log("1.get_dates load data from db");
            
            $res = [
                "t"      => time(),
                "dates"  => DB::select("SELECT DISTINCT `d` FROM `news` ORDER BY `d` DESC LIMIT 0," . TEST_MAX_DAYS)
            ];
            $memcache->set($date_key, $res);
           
        } 
        
        if (defined("TEST_DEBUG")) test_log("<get_dates t = $t\n".print_r($res, true));
        
        return $res;
    }

    
    public static function get_news($dt){
    
        global $memcache;
       
        $news_key = "TEST_NEWS|".$dt["date"]."|".$dt["term"];
        
        $res = $memcache->get($news_key);        
        $t = time();
        $cur_date = date("Y-m-d");
        
        if (defined("TEST_DEBUG")) test_log(">get_news t = $t\n".print_r($res, true));
        
        if (($dt["date"] == $cur_date) && ($t - $res["t"] > TEST_MAX_CACHE_TIME)){
            $res = false;
        }
        
        if (!$res) {

            if (defined("TEST_DEBUG")) test_log("1.get_news load data from db");
            
            $res = [
                "t"     => @time(),
                "news"  => []
            ];
            
            $term_key = "TERM|" . $dt["term"];
            $term_id = (int)$memcache->get($term_key);
            
            if (!$term_id) {
                $qr = DB::select("SELECT `id` FROM `terms` WHERE `title` = ?", [ $dt["term"] ]);
                $term_id = (count($qr) == 1) ? (int)$qr[0]["id"] : 0;
            }
           
            if ($term_id > 0){
                $memcache->set($term_key, $term_id);
                $res["news"] = DB::select("SELECT * FROM `news` WHERE (`term_id` = $term_id) AND (`d` = ?) ORDER BY `d` DESC, `t` DESC", [$dt["date"]]);
                $res["t"] = time();
                $memcache->set($news_key, $res);
            }
        } 
        
        if (defined("TEST_DEBUG")) test_log("<get_news t = $t\n".print_r($res, true));
        
        return $res;
        
    }
    
    public static function get_data(){
        
        if (defined("TEST_DEBUG")) test_log(">get_data");
        
        $data = [
            "terms" => self::get_terms(),
            "dates" => self::get_dates(),
            "qty" => 0, 
            "news" => []
        ];
        
        $data["dt"] = self::get_current_date_term();
        $data["news"] = self::get_news($data["dt"]);
        
        if (defined("TEST_DEBUG")) test_log("<get_data\n".print_r($data, true));
        
        return $data;
        
    }
}