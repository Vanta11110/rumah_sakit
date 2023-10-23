<?php

declare(strict_types=1);

use App\Application\Actions\User\ListUsersAction;
use App\Application\Actions\User\ViewUserAction;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;

return function (App $app) {

    
    $app->get('/dokter', function(Request $request, Response $response) {
        $db = $this->get(PDO::class);

        $query = $db->query('SELECT * FROM dokter_view');
        $results = $query->fetchAll(PDO::FETCH_ASSOC);

        if (count($results) > 0) {
            $response->getBody()->write(json_encode($results));
        } else {
            $response->getBody()->write(json_encode(['message' => 'Tidak dapat mengambil data dokter']));
        }
        return $response->withHeader('Content-Type','application/json');
    });


    $app->get('/dokter/{id}', function(Request $request, Response $response, $args) {
        $db = $this->get(PDO::class);

        $query = $db->prepare('SELECT * FROM dokter_view WHERE id=?');
        $query->execute([$args['id']]);
        $results = $query->fetchAll(PDO::FETCH_ASSOC);

        if (count($results) > 0) {
            $response->getBody()->write(json_encode($results[0]));
        } else {
            $response->getBody()->write(json_encode(['message' => 'Data tidak ditemukan']));
        }

        return $response->withHeader('Content-Type', 'application/json');
});

    $app->post('/dokter', function(Request $request, Response $response) {
    try {
        $parseBody = $request->getParsedBody();
        if (
            empty($parseBody['nama']) ||
            empty($parseBody['spesialis']) ||
            empty($parseBody['nomor_lisensi'])
        ) {
            throw new Exception("Harap isi semua field.");
        }

        $dokterName = $parseBody['nama'];
        $dokterSpesialis = $parseBody['spesialis'];
        $dokterLisensi = $parseBody['nomor_lisensi'];
        $db = $this->get(PDO::class);
        $query = $db->prepare('CALL TambahDokter(?,?,?)');

        $query->execute([$dokterName,$dokterSpesialis,$dokterLisensi]);
        $lastIdQuery = $db->query("SELECT @lastId as last_id");
        $lastId = $lastIdQuery->fetch(PDO::FETCH_ASSOC)['last_id'];

        $response->getBody()->write(json_encode(['message' => 'Data Dokter Tersimpan Dengan ID ' . $lastId]));

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

    $app->put('/dokter/{id}', function(Request $request, Response $response, $args) {
    try {
        $parseBody = $request->getParsedBody();

        $currentId = $args['id'];
        $dokterName = $parseBody['nama'];
        $dokterSpesialis = $parseBody['spesialis'];
        $dokterLisensi = $parseBody['nomor_lisensi'];

        $db = $this->get(PDO::class);
        $query = $db->prepare('CALL UpdateDokter(?,?,?,?)');
        $query->execute([$currentId,$dokterName,$dokterSpesialis,$dokterLisensi]);

        if ($query->rowCount() > 0) {
            $response->getBody()->write(json_encode(['message' => 'Data dokter dengan ID ' . $currentId . ' telah diupdate']));
            return $response->withHeader('Content-Type', 'application/json');
        } else {
            return $response->withStatus(404)->getBody()->write(json_encode(['message' => 'Dokter dengan ID ' . $currentId . ' tidak ditemukan']));
        }
    } catch (Exception $e) {
        return $response->withStatus(500)->getBody()->write(json_encode(['error' => 'Terjadi kesalahan saat memperbarui data dokter']));
    }
});
  
   $app->delete('/dokter/{id}', function(Request $request, Response $response, $args) {
    try {
        $currentId = $args['id'];

        $db = $this->get(PDO::class);
        $query = $db->prepare('CALL HapusDokter(?)');
        $query->execute([$currentId]);

        if ($query->rowCount() > 0) {
            $response->getBody()->write(json_encode(['message' => 'Data dokter dengan ID ' . $currentId . ' telah dihapus']));
            return $response->withHeader('Content-Type', 'application/json');
        } else {
            return $response->withStatus(404)->getBody()->write(json_encode(['message' => 'Data dokter dengan ID ' . $currentId . ' tidak ditemukan']));
        }
    } catch (Exception $e) {
        return $response->withStatus(500)->getBody()->write(json_encode(['error' => 'Terjadi kesalahan saat menghapus data dokter']));
    }
});   
   
     
};
