<?php

declare(strict_types=1);

use App\Application\Actions\User\ListUsersAction;
use App\Application\Actions\User\ViewUserAction;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;

return function (App $app) {

    
    $app->get('/perawat', function(Request $request, Response $response) {
        $db = $this->get(PDO::class);

        $query = $db->query('CALL ViewPerawat');
        $results = $query->fetchAll(PDO::FETCH_ASSOC);

        if (count($results) > 0) {
            $response->getBody()->write(json_encode($results));
        } else {
            $response->getBody()->write(json_encode(['message' => 'Tidak dapat mengambil data perawat']));
        }
        return $response->withHeader('Content-Type','application/json');
    });


    $app->get('/perawat/{id}', function(Request $request, Response $response, $args) {
        $db = $this->get(PDO::class);
        $id = $args['id'];

        $query = $db->prepare('CALL ViewPerawatId(:id)');
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

    $app->post('/perawat', function(Request $request, Response $response) {
    try {
        $parseBody = $request->getParsedBody();
        if (
            empty($parseBody['nama']) ||
            empty($parseBody['nomor_sip']) ||
            empty($parseBody['jenis_kelamin']) ||
            empty($parseBody['nomor_telepon']) 
        ) {
            throw new Exception("Harap isi semua field.");
        }

        $perawatName = $parseBody['nama'];
        $perawatSip = $parseBody['nomor_sip'];
        $perawatGender = $parseBody['jenis_kelamin'];
        $perawatTel = $parseBody['nomor_telepon'];
        $db = $this->get(PDO::class);
        $query = $db->prepare('CALL TambahPerawat(?,?,?,?)');

        $query->execute([$perawatName,$perawatSip,$perawatGender,$perawatTel]);
        $lastIdQuery = $db->query("SELECT @lastId as last_id");
        $lastId = $lastIdQuery->fetch(PDO::FETCH_ASSOC)['last_id'];

        $response->getBody()->write(json_encode(['message' => 'Data Perawat Tersimpan Dengan ID ' . $lastId]));

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

    $app->put('/perawat/{id}', function(Request $request, Response $response, $args) {
    try {
        $parseBody = $request->getParsedBody();

        $currentId = $args['id'];
        $perawatName = $parseBody['nama'];
        $perawatSip = $parseBody['nomor_sip'];
        $perawatGender = $parseBody['jenis_kelamin'];
        $perawatTel = $parseBody['nomor_telepon'];

        $db = $this->get(PDO::class);
        $query = $db->prepare('CALL UpdatePerawat(?,?,?,?,?)');
        $query->execute([$currentId,$perawatName,$perawatSip, $perawatGender,$perawatTel]);

        if ($query->rowCount() > 0) {
            $response->getBody()->write(json_encode(['message' => 'Data Perawat dengan ID ' . $currentId . ' telah diupdate']));
            return $response->withHeader('Content-Type', 'application/json');
        } else {
            return $response->withStatus(404)->getBody()->write(json_encode(['message' => 'Data Perawat dengan ID ' . $currentId . ' tidak ditemukan']));
        }
    } catch (Exception $e) {
        return $response->withStatus(500)->getBody()->write(json_encode(['error' => 'Terjadi kesalahan saat memperbarui data Perawat']));
    }
});
  
   $app->delete('/perawat/{id}', function(Request $request, Response $response, $args) {
    try {
        $currentId = $args['id'];

        $db = $this->get(PDO::class);
        $query = $db->prepare('CALL HapusPerawat(?)');
        $query->execute([$currentId]);

        if ($query->rowCount() > 0) {
            $response->getBody()->write(json_encode(['message' => 'Data Perawat dengan ID ' . $currentId . ' telah dihapus']));
            return $response->withHeader('Content-Type', 'application/json');
        } else {
            return $response->withStatus(404)->getBody()->write(json_encode(['message' => 'Data Perawat dengan ID ' . $currentId . ' tidak ditemukan']));
        }
    } catch (Exception $e) {
        return $response->withStatus(500)->getBody()->write(json_encode(['error' => 'Terjadi kesalahan saat menghapus data Perawat']));
    }
});   
   
     
};
