<?php

declare(strict_types=1);

use App\Application\Actions\User\ListUsersAction;
use App\Application\Actions\User\ViewUserAction;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;

return function (App $app) {

    
    $app->get('/pasien', function(Request $request, Response $response) {
        $db = $this->get(PDO::class);

        $query = $db->query('SELECT * FROM pasien_view');
        $results = $query->fetchAll(PDO::FETCH_ASSOC);

        if (count($results) > 0) {
            $response->getBody()->write(json_encode($results));
        } else {
            $response->getBody()->write(json_encode(['message' => 'Tidak dapat mengambil data pasien']));
        }
        return $response->withHeader('Content-Type','application/json');
    });


    $app->get('/pasien/{id}', function(Request $request, Response $response, $args) {
        $db = $this->get(PDO::class);

        $query = $db->prepare('SELECT * FROM pasien_view WHERE id=?');
        $query->execute([$args['id']]);
        $results = $query->fetchAll(PDO::FETCH_ASSOC);

        if (count($results) > 0) {
            $response->getBody()->write(json_encode($results[0]));
        } else {
            $response->getBody()->write(json_encode(['message' => 'Data tidak ditemukan']));
        }

        return $response->withHeader('Content-Type', 'application/json');
});

    $app->post('/pasien', function(Request $request, Response $response) {
    try {
        $parseBody = $request->getParsedBody();
        if (
            empty($parseBody['nama']) ||
            empty($parseBody['tanggal_lahir']) ||
            empty($parseBody['jenis_kelamin']) ||
            empty($parseBody['alamat']) ||
            empty($parseBody['nomor_telepon'])
        ) {
            throw new Exception("Harap isi semua field.");
        }

        $pasienName = $parseBody['nama'];
        $pasienLahir = $parseBody['tanggal_lahir'];
        $pasienGender = $parseBody['jenis_kelamin'];
        $pasienAlamat = $parseBody['alamat'];
        $pasienTel = $parseBody['nomor_telepon'];
        $db = $this->get(PDO::class);
        $query = $db->prepare('CALL pendaftaran_pasien(?,?,?,?,?)');

        $query->execute([$pasienName, $pasienLahir, $pasienGender, $pasienAlamat, $pasienTel]);
        $lastIdQuery = $db->query("SELECT @lastId as last_id");
        $lastId = $lastIdQuery->fetch(PDO::FETCH_ASSOC)['last_id'];

        $response->getBody()->write(json_encode(['message' => 'Data Pasien Tersimpan Dengan ID ' . $lastId]));

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

    $app->put('/pasien/{id}', function(Request $request, Response $response, $args) {
    try {
        $parseBody = $request->getParsedBody();

        $currentId = $args['id'];
        $pasienName = $parseBody['nama'];
        $pasienLahir = $parseBody['tanggal_lahir'];
        $pasienGender = $parseBody['jenis_kelamin'];
        $pasienAlamat = $parseBody['alamat'];
        $pasienTel = $parseBody['nomor_telepon'];

        $db = $this->get(PDO::class);
        $query = $db->prepare('CALL UpdatePasien(?,?,?,?,?,?)');
        $query->execute([$currentId, $pasienName, $pasienLahir, $pasienGender, $pasienAlamat, $pasienTel]);

        if ($query->rowCount() > 0) {
            $response->getBody()->write(json_encode(['message' => 'Data pasien dengan ID ' . $currentId . ' telah diupdate']));
            return $response->withHeader('Content-Type', 'application/json');
        } else {
            return $response->withStatus(404)->getBody()->write(json_encode(['message' => 'Pasien dengan ID ' . $currentId . ' tidak ditemukan']));
        }
    } catch (Exception $e) {
        // Tangani kesalahan jika terjadi
        return $response->withStatus(500)->getBody()->write(json_encode(['error' => 'Terjadi kesalahan saat memperbarui data pasien']));
    }
});
  
   $app->delete('/pasien/{id}', function(Request $request, Response $response, $args) {
    try {
        $currentId = $args['id'];

        $db = $this->get(PDO::class);
        $query = $db->prepare('CALL HapusPasien(?)');
        $query->execute([$currentId]);

        if ($query->rowCount() > 0) {
            $response->getBody()->write(json_encode(['message' => 'Pasien dengan ID ' . $currentId . ' telah dihapus']));
            return $response->withHeader('Content-Type', 'application/json');
        } else {
            return $response->withStatus(404)->getBody()->write(json_encode(['message' => 'Pasien dengan ID ' . $currentId . ' tidak ditemukan']));
        }
    } catch (Exception $e) {
        return $response->withStatus(500)->getBody()->write(json_encode(['error' => 'Terjadi kesalahan saat menghapus data pasien']));
    }
});   
   
     
};
