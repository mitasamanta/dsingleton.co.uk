<?php require '../../../init.php'; ?>
<?php

/*
 * Caching.
 * Setup marking urls (scrape, install http headers)
 * Marking done (toread -> read) in one click, post version, hide, ajax-up 
 * add skipping (anoher icon?)
 * Nice option... mark all as read.
 * Use dates somewhere. (auto highlight old ones to read or dump, at top and in display, x days old)
 * show recently read, or recently skipped, too.
 * For me links too http://del.icio.us/for/dsingleton
 */

class ReadingList
{   
    var $link = array();
    var $tags = array();
    
    function ReadingList($config)
    {
        $this->config = $config;
    }
    
    function getItems()
    {
        $this->feed = sprintf('http://del.icio.us/rss/%s/%s', $this->config['user'], $this->config['tag']);

        $rss = new DOMDocument();
        $rss->load($this->feed);

        foreach ($rss->getElementsByTagName("item") as $item) {

            $link = array();
            foreach (array('title', 'link', 'description') as $field) {
                $link[$field] = @$item->getElementsByTagName($field)->item(0)->nodeValue;
            }
            $tags = array_flip(explode(' ', $item->getElementsByTagName('subject')->item(0)->nodeValue));
            $tags = array_flip($tags);

            foreach($tags as $k => $t) {
                if ($t == $this->config['tag']) unset($tags[$k]);
                else @$this->tags[$t]++;
            }

            $link['tags'] = $tags;
            $link['date'] = strtotime(substr($item->getElementsByTagName('date')->item(0)->nodeValue, 0, 25));
            $this->links[] = $link;
        }

        ksort($this->tags);
        return $this->links;
    }
    
    function getTags()
    {    
        return $this->tags;
    }
    
    function getUser()
    {
        return $this->config['user'];
    }
    
    function markItemRead($url)
    {
        return $this->updateItemTag($url, $this->config['tag'], 'readinglist:read');
    }
    
    function updateItemTag($url, $removeTag, $addTag)
    {
        $postXML = $this->_callAPI('get', array('url' => $url));
        
        $dom = new DOMDocument();
        $dom->loadXML($postXML);

        if ($post = $dom->getElementsByTagName('post')->item(0)) {
            $description = $post->getAttribute('description');
            $tags = str_replace($removeTag, $addTag, $post->getAttribute('tag'));
            $r = $this->_callAPI('add', compact('url', 'tags', 'description'));
            return strpos($r, 'done') !== false;
        }
        return false;
    }
    
    function _callAPI($method, $params)
    {
        $url = sprintf("https://api.del.icio.us/v1/posts/%s?", $method);
        $url .= http_build_query($params);

        $options = array(
            CURLOPT_HEADER => false,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_USERPWD => $this->config['user'] . ':' . $this->config['pass'],
            CURLOPT_URL => $url,
        );

        $ch = curl_init();
        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);
        curl_close($ch);
        
        return $response;
    }
    
}

$config  = array(
    'user' => 'dsingleton',
    // 'pass' => '',
    'tag'  => 'readinglist:toread',
);

$readingList = new ReadingList($config);

switch (@$_GET['action']) {
    
    case 'remove':
    
        trigger_error('Need to handle auth better', E_USER_ERROR);
    
        $removed = $readingList->markItemRead($_GET['url']);
        if (@$_GET['ajax']) echo $removed ? 1 : 0;
        else header('Location: /code/reading/');
        die;
    break;
    
    case 'view':
    default: 
        $links = $readingList->getItems();
        $tags = $readingList->getTags();
    break;
}

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
    <head>
        <title>Reading List</title>
        
        <link rel="stylesheet" href="/code/_static/css/text.css" type="text/css" />
        <link rel="stylesheet" href="/code/_static/css/structure.css" type="text/css" />
        
        <link rel="stylesheet" href="default.css" type="text/css" />
        
        <link rel="alternate" type="application/rss+xml" href="<?php h($readingList->feed) ?>" />
        
        <script type="text/javascript" src="/_assets/js/jquery.js"></script>
        <script type="text/javascript" src="default.js"></script>
        
    </head>
    <body class="l-2col">
        
        <h1>
            <a href="http://del.icio.us/<?php h($readingList->getUser()); ?>">
                <img height="32" src="http://dsingleton.co.uk/code/grabicon/?url=del.icio.us" />
                Reading List
            </a>
        </h1>
      
        <div class="primary content toread">
            <h2>To Read</h2>
            <ol>
            <?php foreach($links as $link) { ?>
                
                <li>
                    <h3><a href="<?php echo $link['link'] ?>"><?php echo $link['title']; ?></a></h3>
                    
                    <a class="deliciousEdit manage" href="http://del.icio.us/dsingleton/?url=<?php h(urlencode($link['link'])) ?>&amp;">
                        <img src="http://dsingleton.co.uk/code/grabicon/?url=del.icio.us" alt="Edit on del.icio.us" />
                    </a>
                    
                    <a class="markRead manage" href="?action=remove&amp;url=<?php h(urlencode($link['link'])) ?>">
                        <img src="http://dsingleton.co.uk/_assets/img/icons/famfamfam/tick.png" alt="Mark read" />
                    </a>
                    
                    
                    <?php if ($link['tags']) { ?>
                        
                    <ul class="tags">
                    <?php foreach($link['tags'] as $tag) { ?>
                        
                        <li><a href="http://del.icio.us/<?php h($user) ?>/<?php h($tag) ?>"><?php h($tag)?></a></li>
                    <?php } ?>
                    
                    </ul>
                    <?php } ?>
                    
                </li>
                
            <?php } ?>
            </ol>
        </div>
        
        <div class="secondary content tagUsage">
            <h2>Tags</h2>
            <?php if ($tags) { ?>
                
            <ul class="tags">
            <?php foreach($tags as $tag => $count) { ?>
                
                <li><a href="http://del.icio.us/<?php h($readingList->getUser()) ?>/<?php h($tag) ?>"><?php h($tag)?></a> (<?php h($count); ?>)</li>
            <?php } ?>
            
            </ul>
            <?php } ?>
            
        </div>
        
    </body>
</html>