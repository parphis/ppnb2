<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use App\PPNBDB;
use App\ParameterCheck;
use App\Config;

require __DIR__ . '/vendor/autoload.php';

function generateJSONErrorString($err_msg, $details) {
    return '{' .
        '"error": "'.$err_msg.'",'.
        '"details":' . json_encode($details).
    '}';
}

$app = AppFactory::create();

$app->setBasePath('/ppnb2');

function addNewRecord($request, $response, $table) {
    $data = $request->getParsedBody();
    if ((new ParameterCheck($data))->hasRequiredParams($table)) {
        $ppnbdb = new PPNBDB();
        $ppnbdb->connect();
        $res = $ppnbdb->insertNewRecord($table, $data);
        if ($res !== null) {
            $response->getBody()->write('{"status":"'.$table.' added: '.$res.'"}');
        }
        else {
            $response->getBody()->write(generateJSONErrorString("Could'nt add ".$table.": ", $data));
        }
    }
    else {
        $response->getBody()->write(generateJSONErrorString("Missing or invalid parameter(s).", $data));
    }
    return $response;
}

function updateRecord($request, $response, $table, $id) {
    $data = $request->getParsedBody();
    $ppnbdb = new PPNBDB();
    $ppnbdb->connect();
    $res = $ppnbdb->updateExistingRecord($table, $data, $id);
    if ($res !== null) {
        $response->getBody()->write('{"status":"'.$table.' updated: '.$res.'"}');
    }
    else {
        $response->getBody()->write(generateJSONErrorString("Could'nt update ".$table.": ", $data));
    }
    return $response;
}

function markAsDeleted($request, $response, $table, $id) {
    $ppnbdb = new PPNBDB();
    $ppnbdb->connect();
    $res = $ppnbdb->markRecordDeleted($table, $id);
    if ($res !== null) {
        $response->getBody()->write('{"status":"'.$table.' deleted: '.$res.'"}');
    }
    else {
        $response->getBody()->write(generateJSONErrorString("Could'nt delete ".$table.": ", $id));
    }
    return $response;
}

$app->get('/work_hours', function(Request $request, Response $response, $args) {
    $ppnbdb = new PPNBDB();
    $ppnbdb->connect();
            
    $response->getBody()->write($ppnbdb->getWorkhours());
    return $response;
});

$app->post('/work_hour/{id}', function(Request $request, Response $response, $args) {
    return updateRecord($request, $response, 'work_hours', $args['id']);
});

$app->post('/work_hour', function(Request $request, Response $response, $args) {
    return addNewRecord($request, $response, 'work_hours');
});

$app->delete('/work_hour/{id}', function(Request $request, Response $response, $args) {
    return markAsDeleted($request, $response, 'work_hours', $args['id']);
});

$app->get('/currencies', function(Request $request, Response $response, $args) { 
    $ppnbdb = new PPNBDB();
    $ppnbdb->connect();
            
    $response->getBody()->write($ppnbdb->getCurrencies());
    return $response;
});

$app->post('/currency/{id}', function(Request $request, Response $response, $args) {
    return updateRecord($request, $response, 'currency', $args['id']);
});

$app->post('/currency', function(Request $request, Response $response, $args) {
    return addNewRecord($request, $response, 'currency');
});

$app->delete('/currency/{id}', function(Request $request, Response $response, $args) {
    return markAsDeleted($request, $response, 'currency', $args['id']);
});

$app->get('/db_log', function(Request $request, Response $response, $args) { 
    $ppnbdb = new PPNBDB();
    $ppnbdb->connect();
            
    $response->getBody()->write($ppnbdb->getDBLog());
    return $response;
});

$app->get('/work_categories', function(Request $request, Response $response, $args) { 
    $ppnbdb = new PPNBDB();
    $ppnbdb->connect();
            
    $response->getBody()->write($ppnbdb->getWorkcategory());
    return $response;
});

$app->post('/work_category/{id}', function(Request $request, Response $response, $args) {
    return updateRecord($request, $response, 'work_category', $args['id']);
});

$app->post('/work_category', function(Request $request, Response $response, $args) {
    return addNewRecord($request, $response, 'work_category');
});

$app->delete('/work_category/{id}', function(Request $request, Response $response, $args) {
    return markAsDeleted($request, $response, 'work_category', $args['id']);
});

$app->get('/work_companies', function(Request $request, Response $response, $args) { 
    $ppnbdb = new PPNBDB();
    $ppnbdb->connect();
            
    $response->getBody()->write($ppnbdb->getWorkcompany());
    return $response;
});

$app->post('/work_company/{id}', function(Request $request, Response $response, $args) {
    return updateRecord($request, $response, 'work_company', $args['id']);
});

$app->post('/work_company', function(Request $request, Response $response, $args) {
    return addNewRecord($request, $response, 'work_company');
});

$app->delete('/work_company/{id}', function(Request $request, Response $response, $args) {
    return markAsDeleted($request, $response, 'work_company', $args['id']);
});

$app->get('/work_projects', function(Request $request, Response $response, $args) { 
    $ppnbdb = new PPNBDB();
    $ppnbdb->connect();
            
    $response->getBody()->write($ppnbdb->getWorkProject());
    return $response;
});

$app->post('/work_project/{id}', function(Request $request, Response $response, $args) {
    return updateRecord($request, $response, 'work_project', $args['id']);
});

$app->post('/work_project', function(Request $request, Response $response, $args) {
    return addNewRecord($request, $response, 'work_project');
});

$app->delete('/work_project/{id}', function(Request $request, Response $response, $args) {
    return markAsDeleted($request, $response, 'work_project', $args['id']);
});

$app->get('/private_diary', function(Request $request, Response $response, $args) { 
    $ppnbdb = new PPNBDB();
    $ppnbdb->connect();
            
    $response->getBody()->write($ppnbdb->getPrivateDiary());
    return $response;
});

try {
    $app->run();
}
catch (Exception $e) {
    if (Config::DEBUG) {
        die($e);
    }
    else {
        die('Ooops, something went wrong... :-o');
    }
}
