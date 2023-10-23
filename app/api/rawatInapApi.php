<?php

declare(strict_types=1);

use App\Application\Actions\User\ListUsersAction;
use App\Application\Actions\User\ViewUserAction;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;

return function (App $app) {

    
    $app->get('/rawat_inap', function(Request $request, Response $response) {
        $db = $this->get(PDO::class);

        $query = $db->query('SELECT * FROM rawat_inap_view');
        $results = $query->fetchAll(PDO::FETCH_ASSOC);

        if (count($results) > 0) {
            $response->getBody()->write(json_encode($results));
        } else {
            $response->getBody()->write(json_encode(['message' => 'Tidak dapat mengambil data Rawat Inap']));
        }
        return $response->withHeader('Content-Type','application/json');
    });


    $app->get('/rawat_inap/{id}', function(Request $request, Response $response, $args) {
        $db = $this->get(PDO::class);

        $query = $db->prepare('SELECT * FROM rawat_inap_view WHERE id=?');
        $query->execute([$args['id']]);
        $results = $query->fetchAll(PDO::FETCH_ASSOC);

        if (count($results) > 0) {
            $response->getBody()->write(json_encode($results[0]));
        } else {
            $response->getBody()->write(json_encode(['message' => 'Data tidak ditemukan']));
        }

        return $response->withHeader('Content-Type', 'application/json');
});

    $app->post('/rawat_inap', function(Request $request, Response $response) {
    try {
        $parseBody = $request->getParsedBody();
        if (
            empty($parseBody['id_pasien']) ||
            empty($parseBody['id_ruangan']) ||
            empty($parseBody['tanggal_masuk'])
        ) {
            throw new Exception("Harap isi semua field.");
        }

        $pasienId = $parseBody['id_pasien'];
        $ruanganId = $parseBody['id_ruangan'];
        $masukDate = $parseBody['tanggal_masuk'];
        $db = $this->get(PDO::class);
        $query = $db->prepare('CALL TambahRawatInap(?,?,?, @lastId)');

        $query->execute([$pasienId,$ruanganId,$masukDate]);
        $lastIdQuery = $db->query("SELECT @lastId as last_id");
        $lastId = $lastIdQuery->fetch(PDO::FETCH_ASSOC)['last_id'];

        $response->getBody()->write(json_encode(['message' => 'Data Rawat Inap Tersimpan Dengan ID ' . $lastId]));

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

    $app->put('/rawat_inap/{id}', function(Request $request, Response $response, $args) {
    try {
        $parseBody = $request->getParsedBody();

        $currentId = $args['id'];
        $pasienId = $parseBody['id_pasien'];
        $ruanganId = $parseBody['id_ruangan'];
        $masukDate = $parseBody['tanggal_masuk'];

        $db = $this->get(PDO::class);
        $query = $db->prepare('CALL UpdateRawatInap(?,?,?,?)');
        $query->execute([$currentId,$pasienId,$ruanganId,$masukDate]);

        if ($query->rowCount() > 0) {
            $response->getBody()->write(json_encode(['message' => 'Data Rawat Inap dengan ID ' . $currentId . ' telah diupdate']));
            return $response->withHeader('Content-Type', 'application/json');
        } else {
            return $response->withStatus(404)->getBody()->write(json_encode(['message' => 'Data Rawat Inap dengan ID ' . $currentId . ' tidak ditemukan']));
        }
    } catch (Exception $e) {
        return $response->withStatus(500)->getBody()->write(json_encode(['error' => 'Terjadi kesalahan saat memperbarui data Rawat Inap']));
    }
});
  
   $app->delete('/rawat_inap/{id}', function(Request $request, Response $response, $args) {
    try {
        $currentId = $args['id'];

        $db = $this->get(PDO::class);
        $query = $db->prepare('CALL HapusRawatInap(?)');
        $query->execute([$currentId]);

        if ($query->rowCount() > 0) {
            $response->getBody()->write(json_encode(['message' => 'Data Rawat Inap dengan ID ' . $currentId . ' telah dihapus']));
            return $response->withHeader('Content-Type', 'application/json');
        } else {
            return $response->withStatus(404)->getBody()->write(json_encode(['message' => 'Data Rawat Inap dengan ID ' . $currentId . ' tidak ditemukan']));
        }
    } catch (Exception $e) {
        return $response->withStatus(500)->getBody()->write(json_encode(['error' => 'Terjadi kesalahan saat menghapus data Rawat Inap']));
    }
});   
   
     
};
