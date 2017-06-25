<?php
class large_apod extends Plugin {

    private $host;

    function about() {
        return array(1.0,
                "Display large images in NASA APOD feed",
                "t0t4");
    }

    function init($host) {
        $this->host = $host;
        $host->add_hook($host::HOOK_ARTICLE_FILTER, $this);
    }

    function hook_article_filter($article) {
        global $fetch_last_content_type;

        if (strpos($article['link'], 'apod.nasa.gov') === false) return $article; // skip other URLs
        if (isset($article['stored']['content'])) {
                $article['content'] = $article['stored']['content'];
                return $article; // skip already stored content
        }

        $doc = new DOMDocument();
        $link = trim($article['link']);

        $html = fetch_file_contents($link);
        $content_type = $fetch_last_content_type;

        $charset = false;
        if ($content_type) {
                preg_match('/charset=(\S+)/', $content_type, $matches);
                if (isset($matches[1]) && !empty($matches[1])) $charset = $matches[1];
        }

        if ($charset) {
                $html = iconv($charset, 'utf-8', $html);
                $charset = 'utf-8';
                $html = '<?xml encoding="' . $charset . '">' . $html;
        }

        @$doc->loadHTML($html);

        if ($doc) {
                $basenode = false;
                $xpath = new DOMXPath($doc);
                $entries = $xpath->query('(//img)');

                if ($entries->length > 0) $basenode = $entries->item(0);

                if ($basenode) {
                        $article['content'] = $doc->saveXML($basenode);
                }
        }

        return $article;
    }

    function api_version() {
      return 2;
    }
}
?>
