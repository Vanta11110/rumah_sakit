<?php

declare(strict_types=1);

use App\Application\Actions\User\ListUsersAction;
use App\Application\Actions\User\ViewUserAction;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;

return function (App $app) {

    
    $app->get('/resep_obat', function(Request $request, Response $response) {
        $db = $this->get(PDO::class);

        $query = $db->query('CALL ViewResepObat');
        $results = $query->fetchAll(PDO::FETCH_ASSOC);

        if (count($results) > 0) {
            $response->getBody()->write(json_encode($results));
        } else {
            $response->getBody()->write(json_encode(['message' => 'Tidak dapat mengambil data Rawat Inap']));
        }
        return $response->withHeader('Content-Type','application/json');
    });


    $app->get('/resep_obat/{id}', function(Request $request, Response $response, $args) {
        $db = $this->get(PDO::class);
        $id = $args['id'];

        $query = $db->prepare('CALL ViewResepObatId(:id)');
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

    $app->post('/resep_obat', function(Request $request, Response $response) {
    try {
        $parseBody = $request->getParsedBody();
        if (
            empty($parseBody['id_pasien']) ||
            empty($parseBody['id_dokter']) ||
            empty($parseBody['tanggal_resep']) ||
            empty($parseBody['daftar_obat'])
        ) {
            throw new Exception("Harap isi semua field.");
        }

        $pasienId = $parseBody['id_pasien'];
        $dokterId = $parseBody['id_dokter'];
        $tanggal = $parseBody['tanggal_resep'];
        $daftar = $parseBody['daftar_obat'];
        $db = $this->get(PDO::class);
        $query = $db->prepare('CALL TambahResepObat(?,?,?,?, @lastId)');

        $query->execute([$pasienId,$dokterId,$tanggal,$daftar]);
        $lastIdQuery = $db->query("SELECT @lastId as last_id");
        $lastId = $lastIdQuery->fetch(PDO::FETCH_ASSOC)['last_id'];

        $response->getBody()->write(json_encode(['message' => 'Data Resep Obat Tersimpan Dengan ID ' . $lastId]));

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

    $app->put('/resep_obat/{id}', function(Request $request, Response $response, $args) {
    try {
        $parseBody = $request->getParsedBody();

        $currentId = $args['id'];
        $pasienId = $parseBody['id_pasien'];
        $dokterId = $parseBody['id_dokter'];
        $tanggal = $parseBody['tanggal_resep'];
        $daftar = $parseBody['daftar_obat'];

        $db = $this->get(PDO::class);
        $query = $db->prepare('CALL UpdateResepObat(?,?,?,?,?)');
        $query->execute([$currentId,$pasienId,$dokterId,$tanggal,$daftar]);

        if ($query->rowCount() > 0) {
            $response->getBody()->write(json_encode(['message' => 'Data Resep Obat dengan ID ' . $currentId . ' telah diupdate']));
            return $response->withHeader('Content-Type', 'application/json');
        } else {
            return $response->withStatus(404)->getBody()->write(json_encode(['message' => 'Data Resep Obat dengan ID ' . $currentId . ' tidak ditemukan']));
        }
    } catch (Exception $e) {
        return $response->withStatus(500)->getBody()->write(json_encode(['error' => 'Terjadi kesalahan saat memperbarui data Resep Obat']));
    }
});
  
   $app->delete('/resep_obat/{id}', function(Request $request, Response $response, $args) {
    try {
        $currentId = $args['id'];

        $db = $this->get(PDO::class);
        $query = $db->prepare('CALL HapusResepObat(?)');
        $query->execute([$currentId]);

        if ($query->rowCount() > 0) {
            $response->getBody()->write(json_encode(['message' => 'Data Resep Obat dengan ID ' . $currentId . ' telah dihapus']));
            return $response->withHeader('Content-Type', 'application/json');
        } else {
            return $response->withStatus(404)->getBody()->write(json_encode(['message' => 'Data Resep Obat dengan ID ' . $currentId . ' tidak ditemukan']));
        }
    } catch (Exception $e) {
        return $response->withStatus(500)->getBody()->write(json_encode(['error' => 'Terjadi kesalahan saat menghapus data Resep Obat']));
    }
});   
   
     
};
