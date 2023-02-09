<?php

declare(strict_types=1);

namespace App\Controllers;

class Home extends BaseController
{
    public function index()
    {
        $this->document->setTitle('Home | WebSite')
            ->setMeta('description', 'Home page description')
            ->setMeta('robots', 'index, follow, max-snippet:-1, max-video-preview:-1, max-image-preview:large')
            ->addLink('canonical', 'https://example.com')
            ->addLink('alternate', 'https://example.com/', ['hreflang' => 'en'])
            ->addLink('alternate', 'https://example.com/ru/', ['hreflang' => 'ru'])
            ->addBodyClasses('page-home');

        return view('home');
    }
}
