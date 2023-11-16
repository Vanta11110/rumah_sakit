<?php

declare(strict_types=1);

use App\Application\Actions\User\ListUsersAction;
use App\Application\Actions\User\ViewUserAction;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;

return function (App $app) {

    
    $app->get('/ruangan', function(Request $request, Response $response) {
        $db = $this->get(PDO::class);

        $query = $db->query('CALL ViewRuangan');
        $results = $query->fetchAll(PDO::FETCH_ASSOC);

        if (count($results) > 0) {
            $response->getBody()->write(json_encode($results));
        } else {
            $response->getBody()->write(json_encode(['message' => 'Tidak dapat mengambil data Ruangan']));
        }
        return $response->withHeader('Content-Type','application/json');
    });


    $app->get('/ruangan/{id}', function(Request $request, Response $response, $args) {
        $db = $this->get(PDO::class);
        $id = $args['id'];

        $query = $db->prepare('CALL ViewRuanganId(:id)');
        $query->bindParam(':id', $id, PDO::PARAM_INT);
        $query->execute();
        $results = $query->fetch(PDO::FETCH_ASSOC);

        if ($results) {
            $response->getBody()->write(json_encode($results));
        } else {
            $response->getBody()->write(json_encode(['message' => 'Data tidak ditemukan']));
        }

        return $response->withHeader('Content-Type', 'application/json');
});

    $app->post('/ruangan', function(Request $request, Response $response) {
    try {
        $parseBody = $request->getParsedBody();
        if (
            empty($parseBody['nama_ruangan']) ||
            empty($parseBody['kelas']) ||
            empty($parseBody['jumlah_tempat_tidur']) ||
            empty($parseBody['biaya_harian']) 
        ) {
            throw new Exception("Harap isi semua field.");
        }

        $roomName = $parseBody['nama_ruangan'];
        $roomClass = $parseBody['kelas'];
        $roomBed = $parseBody['jumlah_tempat_tidur'];
        $roomCost = $parseBody['biaya_harian'];
        $db = $this->get(PDO::class);
        $query = $db->prepare('CALL TambahRuangan(?,?,?,?, @lastId)');

        $query->execute([$roomName, $roomClass, $roomBed, $roomCost]);
        $lastIdQuery = $db->query("SELECT @lastId as last_id");
        $lastId = $lastIdQuery->fetch(PDO::FETCH_ASSOC)['last_id'];

        $response->getBody()->write(json_encode(['message' => 'Data Ruangan Tersimpan Dengan ID ' . $lastId]));

        return $response->withHeader('Content-Type', 'application/json');
    } catch (Exception $e) {
        $errorResponse = ['error' => $e->getMessage()];
        $response = $response
            ->withStatus(400)
            ->withHeader('Content-Type', 'application/json');
        $response->getBody()->write(json_encode($errorResponse));
        return $response;
    }
});

    $app->put('/ruangan/{id}', function(Request $request, Response $response, $args) {
    try {
        $parseBody = $request->getParsedBody();

        $currentId = $args['id'];
        $roomName = $parseBody['nama_ruangan'];
        $roomClass = $parseBody['kelas'];
        $roomBed = $parseBody['jumlah_tempat_tidur'];
        $roomCost = $parseBody['biaya_harian'];

        $db = $this->get(PDO::class);
        $query = $db->prepare('CALL UpdateRuangan(?,?,?,?,?)');
        $query->execute([$currentId,$roomName, $roomClass, $roomBed, $roomCost]);

        if ($query->rowCount() > 0) {
            $response->getBody()->write(json_encode(['message' => 'Data Ruangan dengan ID ' . $currentId . ' telah diupdate']));
            return $response->withHeader('Content-Type', 'application/json');
        } else {
            return $response->withStatus(404)->getBody()->write(json_encode(['message' => 'Data Ruangan dengan ID ' . $currentId . ' tidak ditemukan']));
        }
    } catch (Exception $e) {
        return $response->withStatus(500)->getBody()->write(json_encode(['error' => 'Terjadi kesalahan saat memperbarui data Ruangan']));
    }
});
  
   $app->delete('/ruangan/{id}', function(Request $request, Response $response, $args) {
    try {
        $currentId = $args['id'];

        $db = $this->get(PDO::class);
        $query = $db->prepare('CALL HapusRuangan(?)');
        $query->execute([$currentId]);

        if ($query->rowCount() > 0) {
            $response->getBody()->write(json_encode(['message' => 'Data Ruangan dengan ID ' . $currentId . ' telah dihapus']));
            return $response->withHeader('Content-Type', 'application/json');
        } else {
            return $response->withStatus(404)->getBody()->write(json_encode(['message' => 'Data Ruangan dengan ID ' . $currentId . ' tidak ditemukan']));
        }
    } catch (Exception $e) {
        return $response->withStatus(500)->getBody()->write(json_encode(['error' => 'Terjadi kesalahan saat menghapus data Ruangan']));
    }
});   
   
     
};
