<?php
ini_set('memory_limit', '-1');
// make sure browsers see this page as utf-8 encoded HTML
header('Content-Type: text/html; charset=utf-8');
$limit = 10;
$query = isset($_REQUEST['q']) ? $_REQUEST['q'] : false;
$results = false;
if ($query)
{
 // The Apache Solr Client library should be on the include path
 // which is usually most easily accomplished by placing in the
 // same directory as this script ( . or current directory is a default
 // php include path entry in the php.ini)
 require_once('Apache\Solr\Service.php');
 require_once('C:\apache\Apache24\htdocs\hw5IR\solr-php-client\SpellCorrector.php');
 // create a new solr service instance - host, port, and corename
 // path (all defaults in this example)
 $solr = new Apache_Solr_Service('localhost', 8983, 'solr/myexample/');


 //spell corrector check, making the variable  $corrector get the value of corrected spelling
 $corrector="";
 if(isset($_REQUEST['mean']))
 {
   $mean=false;
 }
 else {
   $mean = true;
 }
if($mean){
 $arr =  explode(" ", $query);
//getting the correct spelling from SpellCorrector.php file
foreach($arr as $v){
$corrector=$corrector.SpellCorrector::correct($v)." ";
}
}
 // if magic quotes is enabled then stripslashes will be needed
 if (get_magic_quotes_gpc() == 1)
 {
 $query = stripslashes($query);
 }
  $additionalparams=[];
    if(array_key_exists("algo", $_REQUEST) && $_REQUEST["algo"]=="pagerank") {
        $additionalparams['sort']="pageRankFile desc";
    }
 // in production code you'll always want to use a try /catch for any
 // possible exceptions emitted by searching (i.e. connection
 // problems or a query parsing error)
//$results = $solr->search($query, $start, $rows, $additionalParameters);

// read mapping from NYD mapping.csv and store in array
    $file = fopen("NYD Map.csv", "r");
    $mappings = [];
    while(!feof($file)){
        $line = fgets($file);
        $names = explode(",", trim($line));
        if (isset($names[1])) {
            $mappings[$names[0]] = $names[1];
        }
    }
    fclose($file);

    // calling the result based on corrector variable is empty or not
 try
 {
   if ($corrector!== '' && strtolower(trim($corrector)) !== strtolower(trim($query))) {
   $results = $solr->search($corrector, 0, $limit,$additionalparams);
 }else
 {
 $results = $solr->search($query, 0, $limit,$additionalparams);
}
 }
 catch (Exception $e)
 {
 // in production you'd probably log or email this error to an admin
 // and then show a special message to the user but for this example
 // we're going to show the full exception
 die("<html><head><title>SEARCH EXCEPTION</title><body><pre>{$e->__toString()}</pre></body></html>");
 }
}
?>
<html>
 <head>
 <title>PHP Solr Client Example</title>
 </head>
 <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">

 <script src="http://ajax.googleapis.com/ajax/libs/jquery/2.0.0/jquery.min.js"></script>
<script src="http://code.jquery.com/ui/1.10.2/jquery-ui.js" ></script>


 <body>
 <form accept-charset="utf-8" method="get">
 <label for="q">Search:</label>
 <input class="autocomplete" id="q" name="q" type="text" value="<?php echo htmlspecialchars($query, ENT_QUOTES, 'utf-8'); ?>"/>

<input type=radio name="algo" value="solr" <?php if(isset($_POST['algo']) && $_REQUEST['algo'] == 'solr')  echo ' checked="checked"'?>>Solr lucene</input>
<input type=radio name="algo" value = "pagerank"<?php if(isset($_POST['algo']) && $_REQUEST['algo'] == 'pagerank')  echo ' checked="checked"'?>>Page Rank</input>

<input type="submit"/>

 </form>
<?php
// display results
if ($results)
{
 $total = (int) $results->response->numFound;
 $start = min(1, $total);
 $end = min($limit, $total);
?>
<?php
// $mean is the variable used to store "did you mean corrector spelling"
// calling main.php again based on corrector variable to display result
$mean = true;
 if ($corrector != "" && strtolower(trim($corrector)) !== strtolower(trim($query))) {
          echo '<h3> Showing results for <a href="http://localhost:1234/hw5IR/solr-php-client/main.php?q='.$corrector.'"><i>'.$corrector.'</i></a></h3>';
            if(isset($_REQUEST['mean'])){
              $mean= false;
            }
            if($mean){
          echo '<h4> Search instead for <a href="http://localhost:1234/hw5IR/solr-php-client/main.php?mean=false&&q='.$query.'">'.$query.'</a></h4>';
        }
      }
      ?>
   <div><h3 style="color: blue">Results <?php echo $start; ?> - <?php echo $end;?> of <?php echo $total; ?>:</h3></div>
<ol>
<?php
// split the query words if there is multiple words
$query_split = array();
$query_split = explode(" ", trim($_REQUEST['q']));
 // iterate result documents
   foreach ($results->response->docs as $doc)
   {
// if corrector word is present, assign "q" as corrector variable
     if($corrector!==''){
      $_REQUEST['q'] = $corrector;
     }
// assign id for display
	$id = $doc->id;
	$exploded = explode('/', $id);
	$id1 = end($exploded);

// assign title
  if(isset($doc->title))
  {$title = $doc->title;
  }
  elseif(isset($doc->og_title)){
    $title = $doc->og_title;
  }else {
    $title = "None";
  }
// get and assign url from mapping file
	$url = $mappings[$id1];

// if title is empty displaying url as title
  if($title=="None"){
    $title = $url;
  }

// assign description
  if(isset($doc->description)){
	$desc = $doc->description;
	}
  elseif(isset($doc->og_description)){
    $desc = $doc->og_description;
  }
  elseif(isset($doc->og_description_str)){
    $desc = $doc->og_description_str;
  }
	else{
	$desc = 'None';
	}

// variable to store snippet value is set or not
  $present = false;

// get and set snippet from title or description
    $count=0;
    foreach($query_split as $i)
    {
      // if(isset($title) && (strpos(strtolower($title),strtolower(trim($i)))==0||strpos(strtolower($title),strtolower(trim($i))))){
      if(isset($title) && (preg_match("/".strtolower(trim($i))."/",strtolower($title)))){
        if($count==0){
      $snippet = strtolower($title);
      }
      $present = true;
      $_REQUEST['q']=$i;
      $pattern = "/".trim($i)."/";
      $replacement = "<b>".trim($i)."</b>";
      $snippet = preg_replace($pattern, $replacement, $snippet);
      $count=$count+1;
      }
      // elseif((isset($desc))&& (strpos(strtolower($desc),strtolower(trim($i)))==0||strpos(strtolower($desc),strtolower(trim($i))))){
      elseif(isset($desc) && (preg_match("/".strtolower(trim($i))."/",strtolower($desc)))){
        if($count==0){
        $snippet = strtolower($desc);
       }
        $present = true;
        $_REQUEST['q']=$i;
        $pattern = "/".trim($i)."/";
        $replacement = "<b> ".trim($i)." </b>";
        $snippet = preg_replace($pattern, $replacement, $snippet);
        $count=$count+1;
      }
    }
$dir = "C:/Users/Monika/Desktop/CS572 - Info. Retrieval/My Assignments/hw4/crawl_data/NYD/NYD/";
// if snippet is not set from title or description use html downloaded file to get the data for p, h1, h3, a tags in the html file
  if($present == false)
  {
    // extract snippet from paragraph
    $snippet = "";
    $doc = new DOMDocument;
    $doc->preserveWhiteSpace = FALSE;
    // echo $dir.$id1;
    // $file = file_get_contents($dir.$id1);
    // echo htmlentities($file);
        @$doc->loadHTMLFile($dir.$id1);
        $p_tags = $doc->getElementsByTagName('p');
        foreach ($p_tags as $tag) {
          //strpos(strtolower($tag->nodeValue),strtolower(trim($_REQUEST['q'])))
            if(preg_match("/".strtolower(trim($_REQUEST['q']))."/",strtolower($tag->nodeValue)))
            {
              $snippet = strtolower($tag->nodeValue);
              $pattern = "/".trim($_REQUEST['q'])."/";
              $replacement = "<b> ".trim($_REQUEST['q'])." </b>";
              $snippet = preg_replace($pattern, $replacement, $snippet);
              $present = true;
            break;
            }
            else {
              foreach($query_split as $i){
                //||strpos(strtolower($tag->nodeValue),strtolower(trim($_REQUEST['q'])))
                if(preg_match("/".strtolower(trim($i))."/",strtolower($tag->nodeValue)))
                {
                $snippet = strtolower($tag->nodeValue);
                $_REQUEST['q']=$i;
                $pattern = "/".trim($i)."/";
                $replacement = "<b> ".trim($i)." </b>";
                $snippet = preg_replace($pattern, $replacement, $snippet);
                $present = true;
                break;
                }
              }
            }
        }
      }
        if($present==false){
          // is snippet is not present in paragraph use h1 headers to get the snippet
          $snippet = "";
          $doc = new DOMDocument;
          $doc->preserveWhiteSpace = FALSE;
              @$doc->loadHTMLFile($dir.$id1);
              $h1_tags = $doc->getElementsByTagName('h1');
          foreach($h1_tags as $h1tag){
            // if(strpos(strtolower($tag->nodeValue),strtolower(trim($_REQUEST['q']))))
            if(preg_match("/".strtolower(trim($_REQUEST['q']))."/",strtolower($h1tag->nodeValue)))
              {
              $snippet = strtolower($h1tag->nodeValue);
              $pattern = "/".trim($_REQUEST['q'])."/";
              $replacement = "<b> ".trim($_REQUEST['q'])." </b>";
              $snippet = preg_replace($pattern, $replacement, $snippet);
              $present = true;
            break;
            }
            else {
              foreach($query_split as $i){
                // if(strpos(strtolower($tag->nodeValue),strtolower(trim($i))))
                if(preg_match("/".strtolower(trim($i))."/",strtolower($h1tag->nodeValue)))
                {
                $snippet = strtolower($h1tag->nodeValue);
                $_REQUEST['q']=$i;
                $pattern = "/".trim($i)."/";
                $replacement = "<b> ".trim($i)." </b>";
                $snippet = preg_replace($pattern, $replacement, $snippet);
                $present = true;
                break;
                }
              }
            }
          }
        }
        // if snippet is not present in paragraph or in h1 header use h3 header to get the snippet
        if($present == false){
          $snippet = "";
          $doc = new DOMDocument;
          $doc->preserveWhiteSpace = FALSE;
            @$doc->loadHTMLFile($dir.$id1);
              $h_tags = $doc->getElementsByTagName('h3');
          foreach($h_tags as $htag){
            // if(strpos(strtolower($tag->nodeValue),strtolower(trim($_REQUEST['q']))))
            // echo $htag->nodeValue;
            // echo "</br>";
            if(preg_match("/".strtolower(trim($_REQUEST['q']))."/",strtolower($htag->nodeValue)))
              {
              $snippet = strtolower($htag->nodeValue);
              $pattern = "/".trim($_REQUEST['q'])."/";
              $replacement = "<b> ".trim($_REQUEST['q'])." </b>";
              $snippet = preg_replace($pattern, $replacement, $snippet);
              $present = true;
            break;
            }
            else {
              foreach($query_split as $i){
                // if(strpos(strtolower($tag->nodeValue),strtolower(trim($i))))
                if(preg_match("/".strtolower(trim($i))."/",strtolower($htag->nodeValue)))
                {
                $snippet = strtolower($htag->nodeValue);
                $_REQUEST['q']=$i;
                $pattern = "/".trim($i)."/";
                $replacement = "<b> ".trim($i)." </b>";
                $snippet = preg_replace($pattern, $replacement, $snippet);
                $present = true;
                break;
                }
              }
            }
          }
        }
        //if snippet is not present in h1, h3, paragraph then use a tags to get the snippet
        if($present==false){
          // echo "here";
          $snippet = "";
          $doc = new DOMDocument;
          $doc->preserveWhiteSpace = FALSE;
            @$doc->loadHTMLFile($dir.$id1);
              $a_tags = $doc->getElementsByTagName('a');
          foreach($a_tags as $atag){
            // if(strpos(strtolower($tag->nodeValue),strtolower(trim($_REQUEST['q']))))
            if(preg_match("/".strtolower(trim($_REQUEST['q']))."/",strtolower($atag->nodeValue)))
              {
              $snippet = strtolower($atag->nodeValue);
              $pattern = "/".trim($_REQUEST['q'])."/";
              $replacement = "<b> ".trim($_REQUEST['q'])." </b>";
              $snippet = preg_replace($pattern, $replacement, $snippet);
              $present = true;
            break;
            }
            else {
              foreach($query_split as $i){
                // if(strpos(strtolower($tag->nodeValue),strtolower(trim($i))))
                if(preg_match("/".strtolower(trim($i))."/",strtolower($atag->nodeValue)))
                {
                $snippet = strtolower($atag->nodeValue);
                $_REQUEST['q']=$i;
                $pattern = "/".trim($i)."/";
                $replacement = "<b> ".trim($i)." </b>";
                $snippet = preg_replace($pattern, $replacement, $snippet);
                $present = true;
                break;
                }
              }
            }
          }
        }

        // span detection
        if($present==false){
          // echo "here";
          $snippet = "";
          $doc = new DOMDocument;
          $doc->preserveWhiteSpace = FALSE;
            @$doc->loadHTMLFile($dir.$id1);
              $s_tags = $doc->getElementsByTagName('span');
          foreach($s_tags as $stag){
            // if(strpos(strtolower($tag->nodeValue),strtolower(trim($_REQUEST['q']))))
            if(preg_match("/".strtolower(trim($_REQUEST['q']))."/",strtolower($stag->nodeValue)))
              {
              $snippet = strtolower($stag->nodeValue);
              $pattern = "/".trim($_REQUEST['q'])."/";
              $replacement = "<b> ".trim($_REQUEST['q'])." </b>";
              $snippet = preg_replace($pattern, $replacement, $snippet);
              $present = true;
            break;
            }
            else {
              foreach($query_split as $i){
                // if(strpos(strtolower($tag->nodeValue),strtolower(trim($i))))
                if(preg_match("/".strtolower(trim($i))."/",strtolower($stag->nodeValue)))
                {
                $snippet = strtolower($stag->nodeValue);
                $_REQUEST['q']=$i;
                $pattern = "/".trim($i)."/";
                $replacement = "<b> ".trim($i)." </b>";
                $snippet = preg_replace($pattern, $replacement, $snippet);
                $present = true;
                break;
                }
              }
            }
          }
        }
?>
<!-- display result -->
 <table style="width:100%;border: 1px solid black; text-align: left">

 <tr>
 <th>TITLE: </th>
 <td><a target="_blank" href="<?php echo $url?>"/><?php echo $title?></td></tr>
 <tr><th>URL: </th>
 <td><a target="_blank" href="<?php echo $url?>"/><?php echo $url?></td></tr>
 </tr><th>ID: </th>
 <td><?php echo $id?></td></tr>
 <tr><th>DESCRIPTION: </th>
 <td><?php echo $desc?></td>
 <tr><th>SNIPPET: </th>
   <td>
     <?php if(strlen($snippet)>160)
     {
       $pos = strpos($snippet,strtolower($_REQUEST['q']));
       if($pos > 160){
         $snippet = "...".substr($snippet,$pos+strlen($_REQUEST['q'])-160,163)."...";
       }
       else{
         $snippet=substr($snippet, 0, 163)."...";
       }
    }
     echo $snippet ?></td>
  </tr>

  </table>
<?php
 }
?>
 </ol>
<?php
}
?>
<!-- script for autocomplete -->
<script>
      $(document).ready(function() {
          $("#q").autocomplete({
            minLength:1,
              source : function(request, response) {
                  var lastword = $("#q").val().toLowerCase().split(" ").pop(-1);
                  $.ajax({
                      url : "http://localhost:8983/solr/myexample/suggest?q="+lastword+"&wt=json",
                      success : function(data) {
                          var lastword = $("#q").val().toLowerCase().split(" ").pop(-1);
                          var suggestions = data.suggest.suggest[lastword].suggestions;
                          suggestions = $.map(suggestions, function (value, index) {
                              var prefix = "";
                              var query = $("#q").val();
                              var queries = query.split(" ");
                              if (queries.length > 1) {
                                  var lastIndex = query.lastIndexOf(" ");
                                  prefix = query.substring(0, lastIndex + 1).toLowerCase();
                              }

                              return  prefix+value.term;
                          });
                           response(suggestions.slice(0, 7));
                      },
                      dataType : 'jsonp',
                      jsonp : 'json.wrf'

                  });
              },
              messages: {
          noResults: '',
          results: function() {}
      },
      });

        });

    </script>
  </noscript>
 </body>
</html>
