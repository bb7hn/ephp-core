<?php
namespace ephp;

use Firebase\JWT\Key;
use Firebase\JWT\JWT;

class Core
{
    private $DB_HOST='db';
    private $DB_USER='root';
    private $DB_PASS='';
    private $DB_TABLE='test';
    private $DB_PORT=3306;
    private $DB_CHAR_SET='utf8mb4';
    private $DB_DEFAULT_FETCH_MODE=\PDO::FETCH_OBJ;
    private $DB_ERRMODE=\PDO::ERRMODE_EXCEPTION;

    public $DB = null;

    protected function connect_to_mysql(array $options=null)
    {
        $default_options = [
            \PDO::ATTR_ERRMODE            => $this->DB_ERRMODE,
            \PDO::ATTR_DEFAULT_FETCH_MODE => $this->DB_DEFAULT_FETCH_MODE,
        ];
        
        $dsn = "mysql:host=".(getenv('DB_HOST')?:$this->DB_HOST).";dbname=".(getenv('DB_TABLE')?:$this->DB_TABLE).";port=".(getenv('DB_PORT')?:$this->DB_PORT);
        
        try {
            $GLOBALS['DB'] = new \PDO($dsn, (getenv('DB_USER')?:$this->DB_USER), (getenv('DB_PASS')?:$this->DB_PASS), ($options?:$default_options));
            $GLOBALS['DB']->exec('set names '.(getenv('DB_CHAR_SET')?:$this->DB_CHAR_SET));
        } catch (\PDOException $e) {
            $GLOBALS['DB'] = null;
            throw new \PDOException($e->getMessage(), (int)$e->getCode());
        }
    }

    public function __construct()
    {
    }
    public function get_jwt_secret(): string
    {
        $JWT_KEY = getenv('TOKEN_SECRET');
        if (!$JWT_KEY) {
            $secret_key_file = __DIR__ . '/secret';
            if (file_exists($secret_key_file)) {
                $JWT_KEY = file_get_contents($secret_key_file);
            } else {
                $JWT_KEY = uniqid(md5(time()), true);
                file_put_contents($secret_key_file, $JWT_KEY);
            }
        }
        ;
        return $JWT_KEY;
    }
    public function create_token(array $options = []): string
    {
        $JWT_KEY = $this->get_jwt_secret();
        $payload = [
            "created_at" => time(),
        ];

        return JWT::encode($payload, $JWT_KEY, 'HS256');

    }
    public function validate_token(string $token = "", $JWT_EXPIRE_DAYS=10): false|\stdClass
    {
        $JWT_KEY = $this->get_jwt_secret();
        try {
            $decoded = JWT::decode($token, new Key($JWT_KEY, 'HS256'));
            if (!isset($decoded->created_at)) {
                return false;
            }

            $now            = time() - $decoded->created_at;
            $dateDiffInDays = round($now / (60 * 60 * 24));

            if ($dateDiffInDays >= $JWT_EXPIRE_DAYS ?? 10) {
                return false;
            }

            return $decoded;
        } catch (\throwable $exception) {
            return false;
        }


        /*JWT::$leeway = 60; // $leeway in seconds
        $decoded = JWT::decode($jwt, new Key($key, 'HS256'));*/
    }
    public function set_response(array $data = null, int $code=200, string $message="success")
    {
        header('Content-Type: application/json');
        $response = [
            "code"    => $code,
            "message" => $message,
            "data"    => $data
        ];
        echo json_encode($response, JSON_NUMERIC_CHECK);
    }

    public function get_db():false|\PDO
    {
        $db = $GLOBALS['DB'];
        if($db!==null) {
            return $db;
        }
        return false;
    }
    public function use_auth(array $data=[], int $status=403, string $message="Unauthorized")
    {
        $token = $GLOBALS['AUTH_TOKEN'];
        $token = $this->validate_token($token);
        if(!$token) {
            $this->set_response($data, $status, $message);
            exit;
        }
    }
}
