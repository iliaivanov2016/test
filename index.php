<?php
require_once __DIR__ . "/api/api.php";

$data = TestAPI::get_data();

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Новостная лента</title>
</head>
<body>
    <style>
        body {
            max-width: 1024px;
            margin: 0 auto;
        }
        ul li{
            list-style: none;
            padding: 0 0.5em;            
        }
        ul li.active {
            background: #FFFF00;
            color:      #000;
            text-decoration: underline !important;
        }
        ul li a {
            text-decoration: none !important;
        }
        ul li a:hover{
            text-decoration: underline !important;
            cursor:     pointer;
        }
        .row{
            margin-bottom: 1em;
        }
        ul.terms{
            width:      10em;
            display:    inline-block;
        }
        ul.dates{
            width:      5.5em;
            display:    inline-block;
        }
        .grid td.img img{
            width: 300px;
        }
    </style>

    <div class="row">
        <h3>Категории</h3>
        <ul class="terms">
            <?php foreach ($data["terms"]["terms"] as $term) {
                    $active = ($term["title"] == $data["dt"]["term"]) ? 'class="active"' : '';
            ?>
                <li <?php echo $active?>>
                    <a onclick="select_term(this)"><?php echo $term["title"]?></a>
                </li>    
            <?php } ?>
        </ul>
    </div>    
    
    <div class="row">
        <h3>Даты</h3>
        <ul class="dates">
            <?php 
                    foreach ($data["dates"]["dates"] as $date) {
                        $active = ($date["d"] == $data["dt"]["date"]) ? 'class="active"' : '';
            ?>
                <li <?php echo $active?>>
                    <a onclick="select_date(this)" data-date="<?php echo str_replace("-","",$date["d"])?>"><?php echo date("d.m.Y", strtotime($date["d"]))?></a>
                </li>    
            <?php } ?>
        </ul>
    </div>    
    
    <h2>Новости ( <?php echo count($data["news"]["news"])?> найдено )</h2>
    <table class="grid">
        <tbody>

            <?php foreach ($data["news"]["news"] as $item) { ?> 
                    
                <tr>
                    <td class="img">
                        <?php if (!empty($item["img"])) { ?>
                            <img src="<?php echo $item["img"]?>" />
                        <?php } else { ?>
                            &nbsp;
                        <?php } ?>
                    </td>
                    <td>
                        <a href="<?php echo $item["url"]?>"><?php echo $item["title"]?></a>
                    </td>
                </tr>    
                    
            <?php } ?>
            
        </tbody>            
    </table>
    
    <script src="https://cdn-script.com/ajax/libs/jquery/3.7.1/jquery.min.js" type="text/javascript"></script>
    <script>

        var PATH = "<?php echo TEST_PATH?>";
        var DATE = "<?php echo str_replace("-","",$data["dt"]["date"])?>";
        var TERM = "<?php echo $data["dt"]["term"]?>";
    
        function encode_term(s){
            var res = (""+s).replace(/ /g,"+");
            console.log("s = "+s+" res = "+res);
            return res;
        }
        
        function select_date(el){
            var url = PATH + "/" + $(el).attr("data-date") + "/" + encode_term(TERM);
            console.log(url);
            window.location.href = url;
        }
    
        function select_term(el){
            var url = PATH +  "/" + DATE + "/" + encode_term($(el).text()) ;
            console.log(url);
            window.location.href = url;
        }
    
    </script>
</body>
</html>
