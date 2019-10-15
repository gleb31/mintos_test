<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Feed\EnglishFeed as EnglishFeed;

class DefaultController extends AbstractController
{
    /**
     * @Route("/", name="index")
     */
    public function index()
    {
        // return new Response('This is the first Symfony4 page!');

        $projectDir = $this->getParameter('kernel.project_dir');

        $topWords = false;
        $feedItems = false;

        if($this->getUser()) {

            $feed = new EnglishFeed($projectDir);

            if($feedData = $feed->getFeedData()) {

                $topWords = isset($feedData['top_words']) ? $feedData['top_words'] : false;
                $feedItems = [];
                $xml = json_decode($feedData['xml']);

                foreach ($xml->entry as $feedItem) {

                    $row = [
                        'title' => $feedItem->title,
                        'updated' => $feedItem->updated,
                        'author_name' => $feedItem->author->name,
                        'summary' => $feedItem->summary
                    ];

                    foreach ($feedItem->link as $attribute) {
                        $row['href'] = $attribute->href;
                    }

                    $feedItems[] = $row;
                }
            }
        }

        return $this->render('default/homepage.html.twig', [
            'topWords' => $topWords,
            'feedItems' => $feedItems
        ]);
    }
}