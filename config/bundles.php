<?php

return [
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],
    Nelmio\ApiDocBundle\NelmioApiDocBundle::class => ['dev' => true, 'test' => true],
    Nelmio\CorsBundle\NelmioCorsBundle::class => ['all' => true],
    Symfony\Bundle\TwigBundle\TwigBundle::class => ['all' => true],
    Twig\Extra\TwigExtraBundle\TwigExtraBundle::class => ['all' => true],
    Symfony\Bundle\MonologBundle\MonologBundle::class => ['all' => true],
    Ec\MicroserviceBundle\MicroserviceBundle::class => ['all' => true],
    Http\HttplugBundle\HttplugBundle::class => ['all' => true],
    Jb\Bundle\PhumborBundle\JbPhumborBundle::class => ['all' => true],
];
