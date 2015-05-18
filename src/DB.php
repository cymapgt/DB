<?php
namespace cymapgt\core\utility\db;

use cymapgt\Exception\DBException;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Configuration;

/**
 * class DB
 * 
 * Light Wrapper around Doctrines DBAL to provide a consistent interface for database connections
 * and issue of queries
 * 
 * @author    - Cyril Ogana <cogana@gmail.com>
 * @package   - cymapgt.core.utility.db
 * @copyright - CYMAP BUSINESS SOLUTIONS
 */
class DB
{
    /**
    * Instance of the DBAL connection
    * 
    * @var Doctrine\DBAL
    */
    private static $dbLink;

    /*
    *  Database type for the connection
    * 
    *  @var string
    */
    private static $dbType = 'mysql';
    
    /**
     * List of supported database platforms
     * 
     * @var array
     */
    private static $dbList = array (
        'mysql',
        'oracle',
        'postgres',
        'mssql',
        'sqlite',
        'drizzle',
        'sybase'
    );
    
    /**
    * Static function to set the db type
    * 
    * @param string $dbType
    * 
    * @return void
    * 
    * @throws DBException
    */
    public static function setDbType($dbType) {
        $dbTypeCast = (string) $dbType;
        
        if (array_search($dbTypeCast, self::$dbList) === false) {
            throw new DBException('Illegal database type provided to DB Service');
        }
        
        self::$dbType = $dbTypeCast;
    }
    
    /**
    *  Return the  db type
    * 
    * @return string
    */    
    public static function getDbType() {
        return self::$dbType;
    }
    
    /**
    * Returns the db parameters set in the OS / Apache
    *  environment as an array (if any) 
    * 
    *  @return array
    */
    public static function getDatabaseParameters()
    {
        $dbUser     = getenv('CYMAPKDB_USER');
        $dbPassword = getenv('CYMAPKDB_PASSWORD');
        $dbHost     = getenv('CYMAPKDB_HOST');
        $dbPort     = getenv('CYMAPKDB_PORT');
        $dbName     = getenv('CYMAPKDB_DB');
        
        $dbParams = array(
            'user'     => (string) $dbUser,
            'password' => (string) $dbPassword,
            'host'     => (string) $dbHost,
            'port'     => (int)    $dbPort,
            'dbname'   => (string) $dbName
        );
                
        //database specific configuration 
        switch (self::getDbType()) {
            case 'mysql':
                $dbSocket       = getenv('CYMAPKDB_SOCKET');
                $dbCharset      = getenv('CYMAPKDB_CHARSET');
                
                $dbParams['driver']       = 'pdo_mysql';
                $dbParams['unix_socket']  = (string) $dbSocket;
                $dbParams['charset']      = (string) $dbCharset;
                
                break;
            case 'oracle':
                $dbServicename  = getenv('CYMAPKDB_SERVICENAME');
                $dbService      = getenv('CYMAPKDB_SERVICE');
                $dbPooled       = getenv('CYMAPKDB_POOLED');
                $dbCharset      = getenv('CYMAPKDB_CHARSET');
                $dbInstancename = getenv('CYMAPKDB_INSTANCENAME');
                
                $dbParams['driver']       = 'oci8';
                $dbParams['servicename']  = (string) $dbServicename;
                $dbParams['service']      = (bool)   $dbService;
                $dbParams['pooled']       = (bool)   $dbPooled;
                $dbParams['charset']      = (string) $dbCharset;
                $dbParams['instancename'] = (string) $dbInstancename;
                
                break;
            case 'postgres':
                $dbCharset      = getenv('CYMAPKDB_CHARSET');
                $dbSslmode      = getenv('CYMAPKDB_SSLMODE');
                
                $dbParams['driver']  = 'pdo_pgsql';
                $dbParams['charset'] = (string) $dbCharset;
                $dbParams['sslmode'] = (string) $dbSslmode;
                
                break;
            case 'mssql':
                $dbParams['driver'] = 'sqlsrv';
                
                break;
            case 'sqlite':
                $dbPath         = getenv('CYMAPK_DBPATH');
                $dbMemory       = getenv('CYMAPK_DBMEMORY');
                
                $dbParams['driver'] = 'pdo_sqlite';
                $dbParams['path']   = (string) $dbPath;
                $dbParams['memory'] = (bool) $dbMemory;
                
                break;
            case 'drizzle':
                $dbSocket       = getenv('CYMAPKDB_SOCKET');
                
                $dbParams['driver']      = 'drizzle_pdo_mysql';
                $dbParams['unix_socket'] = (string) $dbSocket;
                
                break;
            case 'sybase':
                $dbPersistent   = getenv('CYMAPKDB_PERSISTENT');
                $dbServer       = getenv('CYMAPKDB_SERVER');
                
                $dbParams['driver']     = 'sqlanywhere';
                $dbParams['persistent'] = (bool) $dbPersistent;
                $dbParams['server']     = $dbServer;
                
                break;
        }

        return $dbParams;
    }
    
    /**
    * Validate the database parameters provided
    * 
    *   @param array $dbParams  - Database parameters for the connection
    * 
    *  @return void
    * 
    * @throws DBException
    * 
    */
    public static function validateDbParameters($dbParams) {
        //validate default option
        if (
            empty($dbParams['driver'])
        ) {
            throw new DBException('One or more default parameters for DB connection not set');
        }
        
        //validate that the extension for the DB driver to be used is loaded
        if (!(extension_loaded($dbParams['driver']))) {
            throw new DBException("DB Connection validation failed. {$dbParams['driver']} extension not loaded");
        }
    }

    /**
    * Sanitize the database parameters provided
    * 
    *   @param array $dbParams  - Database parameters for the connection
    * 
    *  @return array
    */   
    public static function sanitizeDbParameters($dbParams) {
        switch (self::getDbType()) {
            case 'mysql':
                if (empty($dbParams['unix_socket'])) {
                    unset($dbParams['unix_socket']);
                }
                
                if (empty($dbParams['charset'])) {
                    unset($dbParams['charset']);
                }
                break;
            case 'sqlite':
                if (empty($dbParams['user'])) {
                    unset($dbParams['user']);
                }
                
                if (empty($dbParams['password'])) {
                    unset($dbParams['password']);
                }
                
                if (empty($dbParams['host'])) {
                    unset($dbParams['host']);
                }
                if (empty($dbParams['port'])) {
                    unset($dbParams['port']);
                }                
                break;
        }
        
        return $dbParams;
    }
    
    /**
    * Create a database connection or return singleton connection using environment settings
    * 
    * @param   connectParams - Associative array of connection parameters
    *  
    * @return  Doctrine\DBAL
    */
    public static function connectDb() {
        if 
            (!isset(self::$dbLink)) {
            $dbParams = self::getDatabaseParameters();
            self::validateDbParameters($dbParams);
            $dbParamsSan = self::sanitizeDbParameters($dbParams);
            $config = new Configuration();
            self::$dbLink = DriverManager::getConnection($dbParamsSan, $config);
        }
        
        return self::$dbLink;
    }

    /**
    *  For new connections that are building transactions
    * 
    * @param  array $dbParams  - Database connection parameters
    *  
    * @return  Doctrine\DBAL
    */
    public static function connectDbNew($dbParams) {
        self::validateDbParameters($dbParams);
        $dbParamsSan = self::sanitizeDbParameters($dbParams);
        $config = new Configuration();
        return DriverManager::getConnection($dbParamsSan, $config);        
    }
           
    /**
    * Closes the static db connection
    * 
    * @return void
    */
    public static function closeDbConnection()
    {
        if (isset(self::$dbLink)) {
            $dbConn = self::$dbLink;
            $dbConn->close();
            self::$dbLink = null;
        }
        
        return null;
    }
}
