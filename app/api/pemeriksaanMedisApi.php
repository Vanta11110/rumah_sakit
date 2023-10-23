<?php

declare(strict_types=1);

use App\Application\Actions\User\ListUsersAction;
use App\Application\Actions\User\ViewUserAction;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;

return function (App $app) {

    
    $app->get('/pemeriksaan_medis', function(Request $request, Response $response) {
        $db = $this->get(PDO::class);

        $query = $db->query('SELECT * FROM pemeriksaan_medis_view');
        $results = $query->fetchAll(PDO::FETCH_ASSOC);

        if (count($results) > 0) {
            $response->getBody()->write(json_encode($results));
        } else {
            $response->getBody()->write(json_encode(['message' => 'Tidak dapat mengambil data Rawat Inap']));
        }
        return $response->withHeader('Content-Type','application/json');
    });


    $app->get('/pemeriksaan_medis/{id}', function(Request $request, Response $response, $args) {
        $db = $this->get(PDO::class);

        $query = $db->prepare('SELECT * FROM pemeriksaan_medis_view WHERE id=?');
        $query->execute([$args['id']]);
        $results = $query->fetchAll(PDO::FETCH_ASSOC);

        if (count($results) > 0) {
            $response->getBody()->write(json_encode($results[0]));
        } else {
            $response->getBody()->write(json_encode(['message' => 'Data tidak ditemukan']));
        }

        return $response->withHeader('Content-Type', 'application/json');
});

    $app->post('/pemeriksaan_medis', function(Request $request, Response $response) {
    try {
        $parseBody = $request->getParsedBody();
        if (
            empty($parseBody['id_pasien']) ||
            empty($parseBody['id_dokter']) ||
            empty($parseBody['jenis_pemeriksaan']) ||
            empty($parseBody['hasil_pemeriksaan']) ||
            empty($parseBody['tanggal_pemeriksaan'])
        ) {
            throw new Exception("Harap isi semua field.");
        }

        $pasienId = $parseBody['id_pasien'];
        $dokterId = $parseBody['id_dokter'];
        $jenis = $parseBody['jenis_pemeriksaan'];
        $hasil = $parseBody['hasil_pemeriksaan'];
        $tanggal = $parseBody['tanggal_pemeriksaan'];
        $db = $this->get(PDO::class);
        $query = $db->prepare('CALL TambahPemeriksaanMedis(?,?,?,?,?, @lastId)');

        $query->execute([$pasienId,$dokterId,$jenis,$hasil,$tanggal]);
        $lastIdQuery = $db->query("SELECT @lastId as last_id");
        $lastId = $lastIdQuery->fetch(PDO::FETCH_ASSOC)['last_id'];

        $response->getBody()->write(json_encode(['message' => 'Data Pemeriksaan Medis Tersimpan Dengan ID ' . $lastId]));

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

    $app->put('/pemeriksaan_medis/{id}', function(Request $request, Response $response, $args) {
    try {
        $parseBody = $request->getParsedBody();

        $currentId = $args['id'];
        $pasienId = $parseBody['id_pasien'];
        $dokterId = $parseBody['id_dokter'];
        $jenis = $parseBody['jenis_pemeriksaan'];
        $hasil = $parseBody['hasil_pemeriksaan'];
        $tanggal = $parseBody['tanggal_pemeriksaan'];

        $db = $this->get(PDO::class);
        $query = $db->prepare('CALL UpdatePemeriksaanMedis(?,?,?,?,?,?)');
        $query->execute([$currentId,$pasienId,$dokterId,$jenis,$hasil,$tanggal]);

        if ($query->rowCount() > 0) {
            $response->getBody()->write(json_encode(['message' => 'Data Pemeriksaan Medis dengan ID ' . $currentId . ' telah diupdate']));
            return $response->withHeader('Content-Type', 'application/json');
        } else {
            return $response->withStatus(404)->getBody()->write(json_encode(['message' => 'Data Pemeriksaan Medis dengan ID ' . $currentId . ' tidak ditemukan']));
        }
    } catch (Exception $e) {
        return $response->withStatus(500)->getBody()->write(json_encode(['error' => 'Terjadi kesalahan saat memperbarui data Pemeriksaan Medis']));
    }
});
  
   $app->delete('/pemeriksaan_medis/{id}', function(Request $request, Response $response, $args) {
    try {
        $currentId = $args['id'];

        $db = $this->get(PDO::class);
        $query = $db->prepare('CALL HapusPemeriksaanMedis(?)');
        $query->execute([$currentId]);

        if ($query->rowCount() > 0) {
            $response->getBody()->write(json_encode(['message' => 'Data Pemeriksaan Medis dengan ID ' . $currentId . ' telah dihapus']));
            return $response->withHeader('Content-Type', 'application/json');
        } else {
            return $response->withStatus(404)->getBody()->write(json_encode(['message' => 'Data Pemeriksaan Medis dengan ID ' . $currentId . ' tidak ditemukan']));
        }
    } catch (Exception $e) {
        return $response->withStatus(500)->getBody()->write(json_encode(['error' => 'Terjadi kesalahan saat menghapus data Pemeriksaan Medis']));
    }
});   
   
     
};
