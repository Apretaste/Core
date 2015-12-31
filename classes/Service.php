<?php 

class Service {
	public $serviceName;
	public $serviceDescription;
	public $creatorEmail;
	public $serviceCategory;
	public $serviceUsage;
	public $insertionDate;
	public $pathToService;
	public $utils; // Instance of the Utils class

	public function __construct() {
		$this->utils = new Utils($this->serviceName);
	}
    
    /**
     * Get service data
     *
     * @param String $container
     * @param String/Function $filter
     * @return mixed
     */
    public function getData($container, $filter = null, $limit = false){
        
        $sql = "SELECT id,container,put_date,data FROM service_data 
                WHERE service = '{$this->serviceName}' 
                  AND container = '{$container}'";
        
        if (!is_callable($filter) && is_string($filter)){
            $sql .= " AND id = '$filter'";
            
            $db = new Connection();
            
            $rows = $db->deepQuery($sql);    
            
            if (isset($rows[0])){
                $rows[0]->data = unserialize($rows[0]->data);
                return $rows[0];
            }
            return null;
            
        } else {
        
            $di = \Phalcon\DI\FactoryDefault::getDefault();
            $result = $di->get('db')->query($sql);
            $result->setFetchMode(Phalcon\Db::FETCH_OBJ);

            // convert to array of objects
            $rows = array();
            $i = 0;
            while ($data = $result->fetch()){
                $data->data = unserialize($data->data);
                
                if (is_null($filter)){
                    $rows[] = $data;
                    $i++;                    
                }
                else {
                    
                    // filter can return false, true, null. If null then break;
                    
                    $record = clone $data;
                    unset($record->data);
                    
                    $r = $filter($data->data, $record);
                    
                    if ($r === true){
                        $rows[] = $data;
                        $i++;
                    }
                    
                    if (is_null($r)){
                        break;
                    }
                }
                
                if ($limit !== false)
                    if (isset($rows[$i-1]) && $limit === $i)
                        break;
            }

            // return the array of objects
            return $rows;
        }
    }
    
    /**
     * Put service data
     *
     * @param String $container
     * @param String $data
     * @param String $id
     * @return String
     */
    public function putData($container, $data, $id = null){
        
        $db = new Connection();
        $data = serialize($data);
        
        if (is_null($id)){
            
            // INSERT MODE
            
            $id = uniqid();
            $sql = "INSERT INTO service_data
                    (id,service,container,data)
                    VALUES ('$id','{$this->serviceName}', '{$container}', '$data');";
            
            $db->deepQuery($sql);
            
            return $id;
            
        } 
            
        // UPDATE MODE

        $sql = "UPDATE service_data 
                SET data = '$data', update_date = CURRENT_TIMESTAMP
                WHERE service = '{$this->serviceName}' 
                  AND container = '{$container}'
                  AND id = '$id'";

        $db->deepQuery($sql);

        return $id;

    }
    
    /**
     * Drop/delete data from containers
     *
     * @param String $container
     * @param String $filter
     */
    public function dropData($container, $filter = null, $limit = false){
             
        $db = new Connection(); 
        
         if (is_null($filter)){ // delete all container
             $sql = "DELETE FROM service_data 
                    WHERE service = '{$this->serviceName}' 
                    AND container = '{$container}'";
             $db->deepQuery($sql);
             return true;
         }
        
        $find = $this->getData($container, $filter, $limit);
        
        $i = 0;
        foreach($find as $record){
            
            $sql = "DELETE FROM service_data 
                    WHERE service = '{$this->serviceName}' 
                    AND container = '{$container}'
                    AND id = '{$record->id}'";
            
            $db->deepQuery($sql);
            $i++;    
        }
        
        if ($i > 0)
            return true;
        
        return false;
    }
}
