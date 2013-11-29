<?php
namespace MeroPhp\Database;

class Adapter extends \PDO
{	
    public function __construct($dsn, $user, $password)
    {
    	parent::__construct($dsn, $user, $password,
    	    array(
    	        \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\''
    	));
    	$this->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }
    
    public function select($sQuery, $aValue, $bFetchOne = true, $nFetchMode = \PDO::FETCH_ASSOC)
    {    	
    	$rPreStatement = $this->prepare($sQuery);
	    $rPreStatement->execute($aValue);
	            
	    $rPreStatement->setFetchMode($nFetchMode);
	    if($bFetchOne === true){
		    return $rPreStatement->fetch();
        }
	    return $rPreStatement->fetchAll();
    }
	
    public function update($sQuery, array $aValue, $bLastInsertId = false)
    {
    	$rPreStatement = $this->prepare($sQuery);
	    $rPreStatement->execute($aValue);
	    $nRow = $rPreStatement->rowCount();
	    if($bLastInsertId !== false){
            $nRow = $this->lastInsertId();
        }
	    return $nRow;
    }
    
    public function create($sQuery)
    {
    	$this->exec($sQuery);
    }
	
    public function query($sQuery, $bFetchOne = true, $nFetchMode = \PDO::FETCH_ASSOC)
    {
        $rPreStatement = $this->query($sQuery, $nFetchMode);
	    if($bFetchOne === true){
	        return $rPreStatement->fetch();
        }
    }
}
