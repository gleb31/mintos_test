<?php

namespace App\Feed;

/**
 * Custom Class EnglishFeed
 */
class EnglishFeed
{
    const FEED_URL = "https://www.theregister.co.uk/software/headlines.atom";
    const LIMIT_TOP_WORDS = 10;

    protected $_folder = '/var/cache/englishFeed/';
    protected $_allWords = [];
    protected $_topWords = [];
    protected $_mostPopularWords = [
        'the', 'be', 'to', 'of', 'end', 'a', 'in', 'that', 'have', 'I', 'it', 'for', 'not', 'on',
        'with', 'he', 'as','you', 'do', 'at', 'this', 'but', 'his', 'by', 'from', 'they', 'we', 'say',
        'her', 'she', 'or', 'an', 'will','my', 'one', 'all', 'would', 'there', 'their', 'what', 'so', 'up',
        'out', 'if', 'about', 'who', 'get', 'which', 'go', 'me', 'when'
    ];

    protected $_rootDir;

    public function __construct(String $rootDir)
    {
        $this->_rootDir = $rootDir;
    }

    /**
     * @return bool|string
     */
    public function getFeedData()
    {
        // check today cache
        if ($cacheResult = $this->_readCache()) {
            return $cacheResult;
        }

        try {

            $feedData = simplexml_load_file(self::FEED_URL);
            if ($feedData !== false) {
                $this->_prepareTopWords($feedData);
            }
            $result = ['top_words' => $this->_topWords, 'xml' => json_encode($feedData)];
            $this->_writeCache($result);

        } catch (Exception $e) {
            // echo $e->getMessage();
            // no data or error
            return false;
        }

        return $result;
    }

    /**
     * @param $feedData
     */
    protected function _prepareTopWords($feedData)
    {
        // parse main fields
        $this->_parseString($feedData->id);
        $this->_parseString($feedData->title);
        $this->_parseString($feedData->title);

        foreach ($feedData->link as $attribute) {
            $this->_parseString($attribute['href']);
        }

        $this->_parseString($feedData->rights);
        $this->_parseString($feedData->author->name);
        $this->_parseString($feedData->author->email);
        $this->_parseString($feedData->author->uri);
        $this->_parseString($feedData->icon);
        $this->_parseString($feedData->subtitle);
        $this->_parseString($feedData->logo);

        // parse feed items
        if (isset($feedData->entry) && count($feedData->entry)) {
            foreach ($feedData->entry as $rowRow) {
				// skip id field for rows
				// $this->_parseString($rowRow->id);
                // skip link field for rows
                // $this->_parseString($rowRow->link);
                $this->_parseString($rowRow->title);
                $this->_parseString($rowRow->author->name);
                $this->_parseString($rowRow->summary);
            }
        }

        // sort word
        sort($this->_allWords);

        // remove 1 not alpha symbol
        $tempWords = [];
        foreach ($this->_allWords as $word) {
            if ((strlen(trim($word)) >= 1 && preg_match("/[[:alpha:]]/", $word))) {
                $tempWords[] = $word;
            }
        }

        $this->_allWords = $tempWords;

        $preTopWords = [];
        foreach ($this->_allWords as $word) {
            if(isset($preTopWords[$word])) {
                $preTopWords[$word]++;
            } else {
                $preTopWords[$word] = 1;
            }
        }

        // Sort an array in reverse order and maintain index association
        arsort($preTopWords);

        // excluding top 50 English common words
        $i = 0;
        foreach ($preTopWords as $topWord => $topWordIndex) {
            if (!in_array($topWord, $this->_mostPopularWords)) {
                $this->_topWords[] = $topWord . ' (' . $topWordIndex . ' times)';
                $i++;
                if ($i == self::LIMIT_TOP_WORDS) {
                    break;
                }
            }
        }
    }

    /**
     * @param $string
     */
    protected function _parseString($string)
    {
        $string = htmlspecialchars_decode($string);
        $string = strip_tags($string);

        $string = preg_replace("/\d+/", '', $string);
        $string = str_replace('..', '.', $string);
        $string = str_replace(['?', '\'', '!', '©', '…', '"', '='], '', $string);
        $string = str_replace(['http://','https://'],[''],$string);

        // convert to ;
        $string = str_replace([' ', ',', '–', '/', ':', '.', '@', '_', '—'], ';', $string);

        while (strpos($string, ';;') !== false) {
            $string = str_replace(';;', ';', $string);
        }

        $string = trim($string);
        $string = strtolower($string);

        $this->_allWords = array_merge($this->_allWords, explode(';', $string));
    }

    /**
     * @return string
     */
    protected function _getFullFileName()
    {
        return $this->_rootDir . $this->_folder . date("Y-d-m") . '.txt';
    }

    /**
     * @param $content
     */
    protected function _writeCache($content)
    {
        $fullName = $this->_getFullFileName();
        try {
            if(!is_dir($this->_rootDir . $this->_folder)) {
                mkdir($this->_rootDir . $this->_folder);
            }
            $fp = fopen($fullName, 'w');
            fwrite($fp, json_encode($content));
            fclose($fp);
        } catch (Exception $e) {
            // echo "_writeCache: Exception " . $e->getMessage();
        }
    }

    /**
     * @return bool|string
     */
    protected function _readCache()
    {
        $fullName = $this->_getFullFileName();
        if (file_exists($fullName)) {
            $cache = file_get_contents($fullName);
            if ($cache) {
                return json_decode($cache, true);
            }
        }
        return false;
    }
}