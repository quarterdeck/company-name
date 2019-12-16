<?php
require_once __DIR__ . '/vendor/autoload.php';

// use Goutte\Client;

// header('Content-type:application/json;charset=utf-8');

// © Copyright 2019 Stray FM, a UKRD group company

class WebsiteCompanyName
{
    protected $prefixes = [ 'https://www.', 'https://', 'http://www.', 'http://', ];
    protected $client;
    protected $crawler;
    protected $excluded_methods = [
        '__construct',
        'guess',
    ];
    public $guesses = [];
    public $domain;
    public $name;

    function __construct($domain)
    {
        $parsed_domain = (object) parse_url($domain);

        if(isset($parsed_domain->path)){
            $this->domain = str_replace('www.', '', $parsed_domain->path);
        } elseif(isset($url->host)){
            $this->domain = str_replace('www.', '', $parsed_domain->host);
        }

        $this->name = explode('.', $this->domain)[0];
        $this->client = new Goutte\Client();

        foreach ($this->prefixes as $prefix) {
            $this->crawler = $this->client->request('GET', $prefix . $this->domain);
            if ($this->client->getResponse()->getStatusCode() == 200) {
                break;
            }
        }

        foreach (array_diff(get_class_methods($this), $this->excluded_methods) as $method) {
            array_push($this->guesses, $this->{$method}());
        }
    }

    function og_site_name()
    {
        $element = $this->crawler->filter('meta[property="og:site_name"]')->eq(0);
        if (count($element)) {
            return $element->attr('content');
        }
    }

    function local_business()
    {
        $element = $this->crawler->filter('[itemtype="http://schema.org/LocalBusiness"] [itemprop="name"]')->eq(0);
        if (count($element)) {
            return $element->text();
        }
    }

    function organization()
    {
        $element = $this->crawler->filter('[itemtype="http://schema.org/Organization"] [itemprop="name"]')->eq(0);
        if (count($element)) {
            return $element->text();
        }
    }

    function title()
    {
        $sections = array_map(function($section){
            return trim($section);
        }, preg_split('/–|-|,|\\|•|:|\|/', $this->crawler->filter('title')->text()));

        $scores = [];
        foreach ($sections as $section) {
            similar_text($this->name, $section, $similarity);
            $scores[$section] = $similarity;
        }
        asort($scores);
        $scores = array_keys($scores);

        return array_pop($scores);
    }

    function guess()
    {
        $this->guesses = array_filter($this->guesses, function($guess){
            return !is_null($guess);
        });
        var_dump( $this->guesses );
    }
}

// var_dump( (new WebsiteCompanyName($_GET['url']))->name );
(new WebsiteCompanyName($_GET['url']))->guess();

// $sld = ucwords(str_replace('-', ' ', Str::before($this->url, '.')));
