<?php

declare(strict_types=1);

use App\Application\Actions\User\ListUsersAction;
use App\Application\Actions\User\ViewUserAction;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;

return function (App $app) {

    
    $app->get('/jadwal_dokter', function(Request $request, Response $response) {
        $db = $this->get(PDO::class);

        $query = $db->query('CALL ViewJadwalDokter');
        $results = $query->fetchAll(PDO::FETCH_ASSOC);

        if (count($results) > 0) {
            $response->getBody()->write(json_encode($results));
        } else {
            $response->getBody()->write(json_encode(['message' => 'Tidak dapat mengambil data Jadwal Dokter']));
        }
        return $response->withHeader('Content-Type','application/json');
    });


    $app->get('/jadwal_dokter/{id}', function(Request $request, Response $response, $args) {
        $db = $this->get(PDO::class);
        $id = $args['id'];

        $query = $db->prepare('CALL ViewJadwalDokterId(:id)');
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

    $app->post('/jadwal_dokter', function(Request $request, Response $response) {
    try {
        $parseBody = $request->getParsedBody();
        if (
            empty($parseBody['id_dokter']) ||
            empty($parseBody['hari_kerja']) ||
            empty($parseBody['waktu_mulai']) ||
            empty($parseBody['waktu_selesai']) 
        ) {
            throw new Exception("Harap isi semua field.");
        }

        $dokterId = $parseBody['id_dokter'];
        $hari = $parseBody['hari_kerja'];
        $start = $parseBody['waktu_mulai'];
        $end = $parseBody['waktu_selesai'];
        $db = $this->get(PDO::class);
        $query = $db->prepare('CALL TambahJadwalDokter(?,?,?,?, @lastId)');

        $query->execute([$dokterId,$hari,$start,$end]);
        $lastIdQuery = $db->query("SELECT @lastId as last_id");
        $lastId = $lastIdQuery->fetch(PDO::FETCH_ASSOC)['last_id'];

        $response->getBody()->write(json_encode(['message' => 'Data Jadwal Dokter Tersimpan Dengan ID ' . $lastId]));

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

    $app->put('/jadwal_dokter/{id}', function(Request $request, Response $response, $args) {
    try {
        $parseBody = $request->getParsedBody();

        $currentId = $args['id'];
        $dokterId = $parseBody['id_dokter'];
        $hari = $parseBody['hari_kerja'];
        $start = $parseBody['waktu_mulai'];
        $end = $parseBody['waktu_selesai'];

        $db = $this->get(PDO::class);
        $query = $db->prepare('CALL UpdateJadwalDokter(?,?,?,?,?)');
        $query->execute([$currentId,$dokterId,$hari,$start,$end]);

        if ($query->rowCount() > 0) {
            $response->getBody()->write(json_encode(['message' => 'Data Jadwal Dokter dengan ID ' . $currentId . ' telah diupdate']));
            return $response->withHeader('Content-Type', 'application/json');
        } else {
            return $response->withStatus(404)->getBody()->write(json_encode(['message' => 'Data Jadwal Dokter dengan ID ' . $currentId . ' tidak ditemukan']));
        }
    } catch (Exception $e) {
        return $response->withStatus(500)->getBody()->write(json_encode(['error' => 'Terjadi kesalahan saat memperbarui data Jadwal Dokter']));
    }
});
  
   $app->delete('/jadwal_dokter/{id}', function(Request $request, Response $response, $args) {
    try {
        $currentId = $args['id'];

        $db = $this->get(PDO::class);
        $query = $db->prepare('CALL HapusJadwalDokter(?)');
        $query->execute([$currentId]);

        if ($query->rowCount() > 0) {
            $response->getBody()->write(json_encode(['message' => 'Data Jadwal Dokter dengan ID ' . $currentId . ' telah dihapus']));
            return $response->withHeader('Content-Type', 'application/json');
        } else {
            return $response->withStatus(404)->getBody()->write(json_encode(['message' => 'Data Jadwal Dokter dengan ID ' . $currentId . ' tidak ditemukan']));
        }
    } catch (Exception $e) {
        return $response->withStatus(500)->getBody()->write(json_encode(['error' => 'Terjadi kesalahan saat menghapus data Jadwal Dokter']));
    }
});   
   
     
};
