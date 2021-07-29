<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use Mailgun\Mailgun;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../private/config.php';

$app = AppFactory::create();

$twig = Twig::create(__DIR__ . '/../templates', [
    //'cache' => __DIR__ . '/../cache'
]);

$app->add(TwigMiddleware::create($app, $twig));

$algoritmeregister = new \Tiltshift\Algoritmeregister\ApiClient($config["api-url"]);

$app->get('/', function (Request $request, Response $response, $args) use ($algoritmeregister) {
    $view = Twig::fromRequest($request);
    $toepassingen = $algoritmeregister->readToepassingen();
    return $view->render($response, 'overzicht.twig', [
        'items' => $toepassingen
    ]);
});

$app->get('/over', function (Request $request, Response $response, $args) {
    $view = Twig::fromRequest($request);
    return $view->render($response, 'over.twig', [
        'title' => 'Over het Algoritmeregister'
    ]);
});

$app->get('/aanmelden', function (Request $request, Response $response, $args) {
    $view = Twig::fromRequest($request);
    return $view->render($response, 'aanmelden.twig', [
        'title' => 'Algoritmische Toepassing Aanmelden',
        'description' => 'Meld je Algoritmische Toepassing aan.'
    ]);
});

$app->post('/aanmelden', function (Request $request, Response $response, $args) use ($config, $algoritmeregister) {
    $data = $request->getParsedBody();

    $data["type"] = "onbekend";
    $data["status"] = "aangemeld";
    $data["herziening"] = date("d-m-Y");

    $contact = $data["contact"];
    
    $maildomain = array_pop(explode('@', $contact));
    if (!in_array($maildomain, $config['known-maildomains'])) {
        return $response->withHeader("Location", "/aanmelden")->withStatus(303);
    }

    $toepassing = $algoritmeregister->createToepassing($data);
    
    $naam = $toepassing["naam"]["waarde"];
    $id = $toepassing["id"]["waarde"];
    $token = $toepassing["token"]["waarde"];

    $baseUrl = $request->getUri()->getScheme() . "://" . $request->getUri()->getHost();
    $port = $request->getUri()->getPort();
    if ($port && $port !== "80") {
        $baseUrl .= ":" . $port;
    }

    $mgClient = Mailgun::create($config["mailgun-key"], $config["mailgun-url"]);
    $result = $mgClient->messages()->send("algoritmeregister.nl", array(
        'from'	=> 'Algoritmeregister <no-reply@algoritmeregister.nl>',
        'to'	=> $contact,
        'subject' => "Beheerpagina toepassing \"{$naam}\" beschikbaar",
        'text'	=> "Je bent aangemeld als beheerder voor de detailpagina van de toepassing \"{$naam}\" in het Algoritmeregister. Op {$baseUrl}/details/{$id}?token={$token} kun je de gegevens beheren."
    ));
    
    return $response->withHeader("Location", "/details/{$id}")->withStatus(303);
});

$app->get('/details/{id}', function (Request $request, Response $response, $args) use ($algoritmeregister) {
    $view = Twig::fromRequest($request);
    $id = $args['id'];
    $token = $request->getQueryParams()["token"];
    $toepassing = $algoritmeregister->readToepassing($id);
    $events = $algoritmeregister->readEvents($id);
    $grouped = [];
    foreach ($toepassing as $field) {
        if ($field["categorie"]) {
            $grouped[$field["categorie"]][] = $field;
        }
    }
    return $view->render($response, 'details.twig', [
        'id' => $id,
        'token' => $token,
        'title' => $toepassing["naam"]["waarde"],
        'description' => $toepassing["beschrijving"]["waarde"],
        'grouped' => $grouped,
        'events' => $events
    ]);
});

$app->get('/aanpassen/{id}', function (Request $request, Response $response, $args) use ($algoritmeregister) {
    $view = Twig::fromRequest($request);
    $id = $args['id'];
    $token = $request->getQueryParams()["token"];
    $toepassing = $algoritmeregister->readToepassing($id);
    $grouped = [];
    foreach ($toepassing as $field) {
        $grouped[$field["categorie"]][] = $field;
    }
    return $view->render($response, 'aanpassen.twig', [
        'id' => $id,
        'token' => $token,
        'title' => $toepassing["naam"]["waarde"],
        'description' => $toepassing["beschrijving"]["waarde"],
        'grouped' => $grouped
    ]);
});

$app->post('/aanpassen/{id}', function (Request $request, Response $response, $args) use ($algoritmeregister) {
    $id = $args['id'];
    $token = $request->getQueryParams()["token"];
    $algoritmeregister->updateToepassing($id, $request->getParsedBody(), $token);
    return $response->withHeader("Location", "/details/{$id}")->withStatus(303);
});

$app->run();
