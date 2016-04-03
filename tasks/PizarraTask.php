<?php

// include the Twitter library
use Abraham\TwitterOAuth\TwitterOAuth;
include_once "../vendor/abraham/twitteroauth/autoload.php";

class ReminderTask extends \Phalcon\Cli\Task
{

    private $KEY = "nXbz7LXFcKSemSb9v2pUh5XWV";

    private $KEY_SECRET = "kjSF6NOppBgR3UsP4u9KjwavrLUFGOcWEeFKmcWCZQyLLpOWCm";

    private $TOKEN = "4247250736-LgRlKf0MgOLQZY6VnaZTJUKTuDU7q0GefcEPYyB";

    private $TOKEN_SECRET = "WXpiTky2v9RVlnJnrwSYlX2BOmJqv8W3Sfb1Ve61RrWa3";

    private $twitterQueries = array(
            'email@example.com' => '#twitterUser'
    );

    private $rssChanels = array(
            'rss@example.com' => 'http://feeds.bbci.co.uk/news/england/rss.xml'
    );

    private $connection = null;

    public function mainAction ()
    {
        // post from tweets
        $twitter = new TwitterOAuth($this->KEY, $this->KEY_SECRET, $this->TOKEN, $this->TOKEN_SECRET);
        
        foreach ($this->twitterQueries as $email => $q) {
            $listOfTweets = $twitter->get("search/tweets", array(
                    "q" => $q,
                    "count" => "1"
            ));
            
            foreach ($listOfTweets->statuses as $tweet) {
                $this->insertNote($email, $tweet->text);
            }
        }
        
        // post from rss
        
        foreach ($this->rssChanels as $email => $rss) {
            $items = $this->getRSS($rss);
            if ($items !== false) {
                $text = $items[0]->title . ': ' . $items[0]->description;
                $this->insertNote($email, $text);
            }
        }
    }

    /**
     * Insert NOTE into pizarra's db
     *
     * @param string $email            
     * @param string $text            
     */
    private function insertNote ($email, $text)
    {
        $text = substr($tweet->text, 0, 130);
        $text = $this->getConnection()->escape($text);
        $this->getConnection()->deepQuery("INSERT INTO _pizarra_notes (email, text) VALUES ('$email', '$text')");
    }

    /**
     * Return connection property
     */
    private function getConnection ()
    {
        if (is_null($this->connection)) $this->connection = new Connection();
        
        return $this->connection;
    }

    /**
     * Retrieve RSS/Atom feed
     *
     * @param unknown $url            
     * @return NULL[]
     */
    private function getRSS ($url)
    {
        $rss = simplexml_load_file($url);
        $root_element_name = $rss->getName();
        
        $items = array();
        
        if ($root_element_name == 'feed') {
            $result = array();
            
            if (isset($rss->entry)) {
                $items = array(
                        array(
                                'title' => '',
                                'description' => ''
                        )
                );
                
                if (isset($rss->entry->title)) $items[0]['title'] = $rss->entry->title . '';
                if (isset($rss->entry->summary)) $items[0]['description'] = $rss->entry->summary . '';
            }
            
            return $result;
        } else 
            if ($root_element_name == 'rss') {
                
                if (isset($rss->channel->item)) foreach ($rss->channel->item as $item) {
                    
                    $data = array(
                            'link' => '',
                            'title' => '',
                            'pubDate' => date('Y-m-d') . '',
                            'description' => ''
                    );
                    
                    if (isset($item->title)) $data['title'] = $item->title;
                    if (isset($item->description)) $data['description'] = $item->description;
                    
                    $items[] = $data;
                }
                
                return $result;
            }
        return false;
    }
}