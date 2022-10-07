<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use Mailgun\Mailgun;
use ScssPhp\ScssPhp\Compiler;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../private/config.php';

$app = AppFactory::create();

$twig = Twig::create(__DIR__ . '/../templates', [
    //'cache' => __DIR__ . '/../cache'
]);

$app->add(TwigMiddleware::create($app, $twig));

$algoritmeregister = new \Tiltshift\Algoritmeregister\ApiClient($config["api-url"]);

$app->get('/login', function (Request $request, Response $response, $args) use ($algoritmeregister) {
    $view = Twig::fromRequest($request);
    return $view->render($response, 'login.twig', []);
});

$app->get('/', function (Request $request, Response $response, $args) use ($algoritmeregister) {
    $token = $request->getQueryParams()["token"];
    $view = Twig::fromRequest($request);
    $toepassingen = $algoritmeregister->readToepassingen();
    foreach ($toepassingen as &$toepassing) {
        $toepassing["schema"] = end(explode("/", $toepassing["schema"]));
    }
    $csvExportUrl = $algoritmeregister->getCsvExportUrl();
    return $view->render($response, 'overzicht.twig', [
        'items' => $toepassingen,
        'token' => $token,
        'csvExportUrl' => $csvExportUrl,
    ]);
});

$app->get('/toevoegen', function (Request $request, Response $response, $args) {
    $token = $request->getQueryParams()["token"];
    if (!$token) die;

    $view = Twig::fromRequest($request);
    return $view->render($response, 'toevoegen.twig', [
        'token' => $token
    ]);
});

$app->post('/toevoegen', function (Request $request, Response $response, $args) use ($config, $algoritmeregister) {
    $data = $request->getParsedBody();
    $token = $request->getQueryParams()["token"];

    $data["type"] = "onbekend";
    $data["status"] = "aangemeld";
    $data["revision_date"] = date("d-m-Y");

    $toepassing = $algoritmeregister->createToepassing($data);

    if (!$toepassing) {
        return $response->withHeader("Location", "/toevoegen");
    }
    
    $naam = $toepassing["name"];
    $id = $toepassing["id"];
    $token = $toepassing["token"];
    $contact = $data["contact_email"];

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

$app->get('/project/{id}', function (Request $request, Response $response, $args) use ($algoritmeregister) {
    $view = Twig::fromRequest($request);
    $id = $args['id'];
    $token = $request->getQueryParams()["token"];
    $toepassing = $algoritmeregister->readToepassing($id);
    $schema = json_decode(file_get_contents($toepassing["schema"]), true);
    $grouped = [];
    foreach ($schema["properties"] as $property => $details) {
        if ($toepassing[$property]) $details["value"] = $toepassing[$property];
        $grouped[$details["category"]][] = $details;
    }
    return $view->render($response, 'project.twig', [
        'id' => $id,
        'token' => $token,
        'title' => $toepassing["name"],
        'department' => $toepassing["department"],
        'status' => $toepassing["status"],
        'contact_name' => $toepassing["contact_name"],
        'contact_email' => $toepassing["contact_email"],
        'uri' => $toepassing["uri"],
        'description' => $toepassing["description_short"],
        'grouped' => $grouped
    ]);
});

$app->get('/details/{id}', function (Request $request, Response $response, $args) use ($algoritmeregister) {
    $view = Twig::fromRequest($request);
    $id = $args['id'];
    $token = $request->getQueryParams()["token"];
    $toepassing = $algoritmeregister->readToepassing($id);
    $schema = json_decode(file_get_contents($toepassing["schema"]), true);
    $grouped = [];
    foreach ($schema["properties"] as $property => $details) {
        if ($toepassing[$property]) $details["value"] = $toepassing[$property];
        $grouped[$details["category"]][] = $details;
    }
    return $view->render($response, 'details.twig', [
        'id' => $id,
        'token' => $token,
        'title' => $toepassing["name"],
        'description' => $toepassing["description_short"],
        'grouped' => $grouped
    ]);
});

$app->get('/details/{id}/log', function (Request $request, Response $response, $args) use ($algoritmeregister) {
    $view = Twig::fromRequest($request);
    $id = $args['id'];
    $token = $request->getQueryParams()["token"];
    $toepassing = $algoritmeregister->readToepassing($id);
    $events = $algoritmeregister->readEvents($id);
    return $view->render($response, 'details-log.twig', [
        'id' => $id,
        'token' => $token,
        'title' => $toepassing["name"],
        'description' => $toepassing["description_short"],
        'events' => $events
    ]);
});

$app->get('/aanpassen/{id}', function (Request $request, Response $response, $args) use ($algoritmeregister) {
    $view = Twig::fromRequest($request);
    $id = $args['id'];
    $token = $request->getQueryParams()["token"];
    $toepassing = $algoritmeregister->readToepassing($id);
    $schema = json_decode(file_get_contents($toepassing["schema"]), true);
    $grouped = [];
    foreach ($schema["properties"] as $property => $details) {
        $details["property"] = $property;
        if ($toepassing[$property]) $details["value"] = $toepassing[$property];
        $grouped[$details["category"]][] = $details;
    }
    return $view->render($response, 'aanpassen.twig', [
        'id' => $id,
        'token' => $token,
        'title' => $toepassing["name"],
        'description' => $toepassing["description_short"],
        'grouped' => $grouped
    ]);
});

$app->post('/aanpassen/{id}', function (Request $request, Response $response, $args) use ($algoritmeregister) {
    $id = $args['id'];
    $token = $request->getQueryParams()["token"];
    $algoritmeregister->updateToepassing($id, $request->getParsedBody(), $token);
    return $response->withHeader("Location", "/details/{$id}?token={$token}")->withStatus(303);
});

$app->get('/acties/{id}', function (Request $request, Response $response, $args) use ($algoritmeregister) {
    $view = Twig::fromRequest($request);
    $id = $args['id'];
    $token = $request->getQueryParams()["token"];
    $toepassing = $algoritmeregister->readToepassing($id);
    return $view->render($response, 'acties.twig', [
        'id' => $id,
        'token' => $token,
        'title' => $toepassing["name"],
        'description' => $toepassing["description_short"]
    ]);
});

$app->post('/acties/verwijderen/{id}', function (Request $request, Response $response, $args) use ($algoritmeregister) {
    $id = $args['id'];
    $token = $request->getQueryParams()["token"];
    $algoritmeregister->deleteToepassing($id, $token);
    return $response->withHeader("Location", "/")->withStatus(204);
});

$app->get('/scss/compile', function (Request $request, Response $response, $args) use ($algoritmeregister) {

    $compiler = new Compiler();
    $compiler->setImportPaths(__DIR__ . '/../templates/elements');

    $importString = '';
    $directory = scandir(__DIR__ . '/../templates/elements');
    $scssImports = [];
    foreach ($directory as $item) {
        if (file_exists(__DIR__ . '/../templates/elements/' . $item . '/' . $item . '.scss')) {
            $scssImports[] = $item;
            $importString .= '@import "' . $item . '/' . $item . '";';
        }
    }

    $str = $compiler->compileString($importString)->getCss();

    $response->getBody()->write($str);
    return $response->withHeader('Content-type', 'text/css');

});


$app->run();
