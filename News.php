<?php

class News {

    /**
     * News XML file
     * 
     * @var string
     */
    public $newsFile = 'news.xml';

    /**
     * Returns the specified number of articles ($count) beginning with the specified article number ($start)
     * 
     * @param int $start
     * @param int $count
     * @return string 
     */
    public function getNews($start = 0, $count = 1) {
        $out = '';
        if (file_exists($this->newsFile)) {
            $news = simplexml_load_file($this->newsFile);
            for ($i = $start; $i < $start + $count; $i++) {
                if (isset($news->article[$i])) {
                    $article = $news->article[$i];
                    $out.= '<div><h4>' . $article->date . ' - ' . $article->title . "</h4>\n";
                    $out.= $article->body . "</div>\n";
                }
            }
            return $out;
        } else {
            return "<p>No recent updates</p>\n";
        }
    }

    /**
     * Returns the total number of articles
     * 
     * @return int 
     */
    public function getNewsCount() {
        if (file_exists($this->newsFile)) {
            $news = simplexml_load_file($this->newsFile);
            return count($news->article);
        } else {
            return 0;
        }
    }
}
