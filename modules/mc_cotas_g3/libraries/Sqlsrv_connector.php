<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Biblioteca de conexão SQL Server para Multiclubes
 * Suporta conexões via SQLSRV ou PDO_SQLSRV
 */
class Sqlsrv_connector
{
    private $connection = null;
    private $use_pdo = false;
    private $host;
    private $user;
    private $password;
    private $database;
    private $port;
    private $last_error = '';

    /**
     * Construtor
     */
    public function __construct($config = [])
    {
        if (!empty($config)) {
            $this->host = $config['host'] ?? '';
            $this->user = $config['user'] ?? '';
            $this->password = $config['password'] ?? '';
            $this->database = $config['database'] ?? '';
            $this->port = $config['port'] ?? '1433';
        }
    }

    /**
     * Conectar ao SQL Server
     *
     * @return boolean
     */
    public function connect()
    {
        try {
            // Verificar qual driver está disponível
            if (extension_loaded('sqlsrv')) {
                return $this->connect_sqlsrv();
            } elseif (extension_loaded('pdo_sqlsrv')) {
                return $this->connect_pdo();
            } else {
                $this->last_error = 'Nenhum driver SQL Server disponível (sqlsrv ou pdo_sqlsrv). Por favor, instale um dos drivers.';
                return false;
            }
        } catch (Exception $e) {
            $this->last_error = $e->getMessage();
            return false;
        }
    }

    /**
     * Conectar usando driver SQLSRV nativo
     *
     * @return boolean
     */
    private function connect_sqlsrv()
    {
        $this->use_pdo = false;

        $serverName = $this->host . ',' . $this->port;
        $connectionInfo = [
            'Database' => $this->database,
            'UID' => $this->user,
            'PWD' => $this->password,
            'CharacterSet' => 'UTF-8',
            'ReturnDatesAsStrings' => true,
        ];

        $this->connection = sqlsrv_connect($serverName, $connectionInfo);

        if ($this->connection === false) {
            $errors = sqlsrv_errors();
            $this->last_error = 'Erro ao conectar ao SQL Server: ' . json_encode($errors);
            return false;
        }

        return true;
    }

    /**
     * Conectar usando PDO
     *
     * @return boolean
     */
    private function connect_pdo()
    {
        $this->use_pdo = true;

        try {
            $dsn = "sqlsrv:Server={$this->host},{$this->port};Database={$this->database}";
            $this->connection = new PDO($dsn, $this->user, $this->password);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return true;
        } catch (PDOException $e) {
            $this->last_error = 'Erro ao conectar ao SQL Server via PDO: ' . $e->getMessage();
            return false;
        }
    }

    /**
     * Executar query e retornar resultados
     *
     * @param string $query
     * @return array|false
     */
    public function query($query)
    {
        if ($this->connection === null) {
            if (!$this->connect()) {
                return false;
            }
        }

        if ($this->use_pdo) {
            return $this->query_pdo($query);
        } else {
            return $this->query_sqlsrv($query);
        }
    }

    /**
     * Query usando SQLSRV
     *
     * @param string $query
     * @return array|false
     */
    private function query_sqlsrv($query)
    {
        $stmt = sqlsrv_query($this->connection, $query);

        if ($stmt === false) {
            $errors = sqlsrv_errors();
            $this->last_error = 'Erro ao executar query: ' . json_encode($errors);
            return false;
        }

        $results = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $results[] = $row;
        }

        sqlsrv_free_stmt($stmt);

        return $results;
    }

    /**
     * Query usando PDO
     *
     * @param string $query
     * @return array|false
     */
    private function query_pdo($query)
    {
        try {
            $stmt = $this->connection->query($query);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $results;
        } catch (PDOException $e) {
            $this->last_error = 'Erro ao executar query: ' . $e->getMessage();
            return false;
        }
    }

    /**
     * Buscar membros do Multiclubes
     *
     * @param array $filters
     * @return array|false
     */
    public function get_members($filters = [])
    {
        $query = "SELECT
            [MemberId],
            [MemberName],
            [MemberMobilePhone],
            [MemberEmail],
            [MemberDocumentType],
            [MemberDocumentNumber],
            [MemberBirthDate],
            [MemberAge],
            [MemberSex],
            [Titular],
            [MemberStatus],
            [MemberOccupation],
            [TitleCode],
            [TitleTypeName],
            [AdressCity],
            [AdressBurgh],
            [AdressStreet],
            [AdressNumber],
            [AdressComplement],
            [AdressState],
            [ParentageName],
            [DependenciesLastUpdateDate],
            [LastUpdateDate]
        FROM [MultiClubes].[Analytics].[MembersView]";

        $conditions = [];

        // Filtro: apenas titulares
        if (!empty($filters['only_titular'])) {
            $conditions[] = "[Titular] = 'Titular'";
        }

        // Filtro: apenas ativos
        if (!empty($filters['only_active'])) {
            $conditions[] = "[MemberStatus] = 'Ativo'";
        }

        // Filtro: data específica
        if (!empty($filters['from_date'])) {
            $conditions[] = "[LastUpdateDate] >= '" . $filters['from_date'] . "'";
        }

        // Adicionar condições à query
        if (!empty($conditions)) {
            $query .= " WHERE " . implode(' AND ', $conditions);
        }

        // Ordenar por MemberId para paginação consistente
        $query .= " ORDER BY [MemberId] ASC";

        // Paginação (OFFSET/FETCH - SQL Server 2012+)
        if (isset($filters['limit']) && $filters['limit'] > 0) {
            $offset = isset($filters['offset']) ? (int)$filters['offset'] : 0;
            $limit = (int)$filters['limit'];

            $query .= " OFFSET $offset ROWS FETCH NEXT $limit ROWS ONLY";
        }

        return $this->query($query);
    }

    /**
     * Contar total de membros (para paginação)
     *
     * @param array $filters
     * @return int|false
     */
    public function count_members($filters = [])
    {
        $query = "SELECT COUNT(*) as total
        FROM [MultiClubes].[Analytics].[MembersView]";

        $conditions = [];

        // Filtro: apenas titulares
        if (!empty($filters['only_titular'])) {
            $conditions[] = "[Titular] = 'Titular'";
        }

        // Filtro: apenas ativos
        if (!empty($filters['only_active'])) {
            $conditions[] = "[MemberStatus] = 'Ativo'";
        }

        // Filtro: data específica
        if (!empty($filters['from_date'])) {
            $conditions[] = "[LastUpdateDate] >= '" . $filters['from_date'] . "'";
        }

        // Adicionar condições à query
        if (!empty($conditions)) {
            $query .= " WHERE " . implode(' AND ', $conditions);
        }

        $result = $this->query($query);

        if ($result && isset($result[0]['total'])) {
            return (int)$result[0]['total'];
        }

        return false;
    }

    /**
     * Testar conexão
     *
     * @return boolean
     */
    public function test_connection()
    {
        if (!$this->connect()) {
            return false;
        }

        $result = $this->query("SELECT 1 AS test");

        return $result !== false && !empty($result);
    }

    /**
     * Obter último erro
     *
     * @return string
     */
    public function get_last_error()
    {
        return $this->last_error;
    }

    /**
     * Fechar conexão
     */
    public function close()
    {
        if ($this->connection !== null) {
            if ($this->use_pdo) {
                $this->connection = null;
            } else {
                sqlsrv_close($this->connection);
            }
        }
    }

    /**
     * Destrutor
     */
    public function __destruct()
    {
        $this->close();
    }
}
