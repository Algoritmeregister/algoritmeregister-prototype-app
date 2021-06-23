<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;

require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();

$twig = Twig::create(__DIR__ . '/../templates', [
    //'cache' => __DIR__ . '/../cache'
]);

$app->add(TwigMiddleware::create($app, $twig));

$app->get('/', function (Request $request, Response $response, $args) {
    $view = Twig::fromRequest($request);
    return $view->render($response, 'default.twig', [
        'title' => 'Algoritmeregister',
        'description' => 'We werken aan een overzicht van algoritmes, een Algoritmeregister. Door in deze registratie aspecten van algoritmes op een begrijpelijke manier vast te leggen kunnen wij transparantie bieden en verantwoording af leggen. In het register kun je technische elementen terugvinden, zoals de dataverwerking of broncode, maar bijvoorbeeld ook afspraken tussen overheden en leveranciers of bijvoorbeeld een beschrijving van de werking van het algoritme.'
    ]);
});

$app->get('/over', function (Request $request, Response $response, $args) {
    $view = Twig::fromRequest($request);
    return $view->render($response, 'over.twig', [
        'title' => 'Over Algoritmeregister'
    ]);
});

$app->get('/aanmelden', function (Request $request, Response $response, $args) {
    $view = Twig::fromRequest($request);
    return $view->render($response, 'aanmelden.twig', [
        'title' => 'Algoritme aanmelden',
        'description' => ''
    ]);
});

$app->get('/details/{id}', function (Request $request, Response $response, $args) {
    $view = Twig::fromRequest($request);
    return $view->render($response, 'details.twig', [
        'title' => 'Parkeercontrole',
        'description' => 'Om Amsterdam leefbaar en toegankelijk te houden, mag er maar een beperkt aantal auto’s in de stad parkeren. De gemeente controleert of een geparkeerde auto het recht heeft om geparkeerd te staan, dus of iemand parkeergeld heeft betaald of een parkeervergunning heeft. Om efficiënter te werken doen we die controle met scanauto’s. Daarmee controleren we momenteel meer dan 150.000 officiële parkeerplaatsen in Amsterdam.'
    ]);
});

$app->get('/aanpassen/{id}', function (Request $request, Response $response, $args) {
    $view = Twig::fromRequest($request);
    return $view->render($response, 'aanpassen.twig', [
        'title' => 'Parkeercontrole',
        'description' => 'Om Amsterdam leefbaar en toegankelijk te houden, mag er maar een beperkt aantal auto’s in de stad parkeren. De gemeente controleert of een geparkeerde auto het recht heeft om geparkeerd te staan, dus of iemand parkeergeld heeft betaald of een parkeervergunning heeft. Om efficiënter te werken doen we die controle met scanauto’s. Daarmee controleren we momenteel meer dan 150.000 officiële parkeerplaatsen in Amsterdam.'
    ]);
});

$app->run();
