<?php

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\HttpClient\HttpClient;

class WebsiteCompanyName
{
    protected $prefixes = [ 'http://www.', 'http://', 'https://www.', 'https://', ];
    protected $browser;
    protected $crawler;
    protected $guessing_methods = [
        'title',
        'og_site_name',
        'og_title',
        // 'local_business',
        'organization',
        'copyright',
    ];
    public $guesses = [];
    public $domain;
    public $sld;
    public $name;

    function __construct($domain)
    {
        $parsed_domain = (object) parse_url($domain);

        if(isset($parsed_domain->path)){
            $this->domain = str_replace('www.', '', $parsed_domain->path);
        } elseif(isset($url->host)){
            $this->domain = str_replace('www.', '', $parsed_domain->host);
        }

        $this->sld = explode('.', $this->domain)[0];
        $this->name = ucwords(str_replace('-', ' ', $this->sld));
        $this->browser = new HttpBrowser(HttpClient::create());

        try {
            foreach ($this->prefixes as $prefix) {
                $this->crawler = $this->browser->request('GET', $prefix . $this->domain, [
                    'max_redirects' => 3,
                ]);
                if ($this->browser->getResponse()->getStatusCode() == 200) {
                    break;
                }
            }
            foreach ($this->guessing_methods as $method) {
                foreach ($this->split($this->{$method}()) as $guess) {
                    array_push($this->guesses, trim($guess));
                }
            }
        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            array_push($this->guesses, trim($this->name));
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            array_push($this->guesses, trim($this->name));
        } catch (\InvalidArgumentException $e) {
            array_push($this->guesses, trim($this->name));
        }
    }

    function title()
    {
        return $this->crawler->filter('title')->text();
    }

    function og_site_name()
    {
        $element = $this->crawler->filter('meta[property="og:site_name"]')->eq(0);
        if (count($element)) {
            return $element->attr('content');
        }
    }

    function og_title()
    {
        $element = $this->crawler->filter('meta[property="og:title"]')->eq(0);
        if (count($element)) {
            return $element->attr('content');
        }
    }

    // function local_business()
    // {
    //     $element = $this->crawler->filter('[itemtype="http://schema.org/LocalBusiness"] [itemprop="name"]')->eq(0);
    //     if (count($element)) {
    //         return $element->text();
    //     }
    // }

    function organization()
    {
        $element = $this->crawler->filter('[type="application/ld+json"]')->eq(0);
        if (count($element)) {
            $json = json_decode($element->text(), true);
            if (isset($json['@type'], $json['name']) && $json['@type'] == 'Organization') {
                return $json['name'];
            }
        }
    }

    // function organization()
    // {
    //     $element = $this->crawler->filter('[itemtype="http://schema.org/Organization"] [itemprop="name"]')->eq(0);
    //     if (count($element)) {
    //         return $element->text();
    //     }
    // }

    function copyright()
    {
		$copyright = $this->crawler->filter('p, span, div, li, small')->each(function (Crawler $node, $i) {
			if (str_contains($node->text(), '©')) {
				return $node->text();
			}
		});

		$copyright = array_filter($copyright);
		$copyright = array_pop($copyright);
		$copyright = str_ireplace([
			'copyright',
			'©',
			'2025',
			'2024',
			'2023',
			'2022',
			'2021',
			'2020',
			'2019',
			'2018',
			'2017',
			'2016',
			'2015',
			'All Rights Reserved',
			'All Right Reserved',
			'.',
		], '', $copyright);

		return $copyright;
    }

    function guess()
    {
        $this->guesses = array_filter($this->guesses);
        $this->guesses = array_unique($this->guesses);
        $this->guesses = array_values($this->guesses);

        if (empty($this->guesses)) {
            array_push($this->guesses, $this->name);
        }

        $scores = [];
        foreach ($this->guesses as $guess) {
            similar_text($this->sld, $guess, $score);
            $scores[$guess] = $score;
        }
        asort($scores);
        $scores = array_keys($scores);

        return array_pop($scores);
    }

    function split($text)
    {
        return preg_split('/–|-|,|\\|•|•|:|\.|\|/', $text ?? '');
    }

    function __toString()
    {
        return json_encode([
            'best_guess' => $this->guess(),
            'guesses' => $this->guesses,
        ]);
    }
}
