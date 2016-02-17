<?php
 ob_start();
 require_once("Rest.inc.php");   
 use google\appengine\api\users\User;
 use google\appengine\api\users\UserService;
 use \google\appengine\api\mail\Message;
$DEBUG = TRUE;

class AWOS_API extends REST {

    public $data = "";


    const DB_SERVER = "/cloudsql/awos-beta:awos";
    const DB_USER = "root";
    const DB_PASSWORD = "admin";
    const DB = "awos";
    

    private $db = NULL;

    public function __construct() {
      parent::__construct();// Init parent contructor
      $this->dbConnect(); // Initiate Database connection
    }

//Database connection
    private function dbConnect() {
       // $this->db = mysql_connect(self::DB_SERVER, self::DB_USER, self::DB_PASSWORD);
        $this->db = new mysqli(null, self::DB_USER, self::DB_PASSWORD, self::DB,null, '/cloudsql/awos-beta:awos');
       // if ($this->db)
        //    mysql_select_db(self::DB, $this->db);
        
       
    }
    
    function processApi()
    {
        $this->log("Entered processApi()");
        # Looks for current Google account session
        $user = UserService::getCurrentUser();
        
      if( $user)
      { 
          
          
          
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $func = strtolower(trim(str_replace("/", "", $path)));
        
        $this->log("Function is: ".$func);
        
        if ((int) method_exists($this, $func) > 0)
            $this->$func($user);
        else
            $this->response('', 404);
// If the method not exist with in this class, response would be "Page not found".
      }
     else
     {
          header('Location: ' . UserService::createLoginURL($_SERVER['REQUEST_URI']));
     }
       $this->log("Exited processApi()");
        
    }

    //************************************
    // function: users() - returns a listing
    // of users
    //***************************************
    function users($user)
    {
        $this->log("Entered users()");
        if ($this->get_request_method() == "GET") {
           //***************************
            // Check if the logged in user
            // is an Admin. In which case
            // show all the tickets
            //****************************
            $isAdmin = $_SERVER['USER_IS_ADMIN'];

            if( isAdmin)
            {
                $sql = mysql_query("SELECT id, userid FROM users", $this->db);
                    if (mysql_num_rows($sql) > 0) {
                        $result = array();
                    while ($rlt = mysql_fetch_array($sql, MYSQL_ASSOC)) {
                        $result[] = $rlt;
                        }   
              
                        }
                $this->deliver_response("200", "User List", $result);
             }
            
        else {
              $this->deliver_response("403", "Forbidden", null);
        }
        $this->log("Exited users()");
    }
    }
    
    function complaints($user)
    {
        $this->log("Entered complaints()");
        if ($this->get_request_method() == "GET") {
           //***************************
            // Check if the logged in user
            // is an Admin. In which case
            // show all the tickets
            //****************************
            $isAdmin = $_SERVER['USER_IS_ADMIN'];
            $email = $_SERVER['USER_EMAIL'];
        
        if($user) 
        {
            if( $isAdmin)
            {
                $historyQuery = 'Select a.*, b.block, b.apt, b.Mobile from grievance a, users b 
                          where a.Submitter=b.userid';
            }
            else {
                  $historyQuery = "Select * from grievance where Submitter='$email'";

                }
              // $sql = mysql_query($historyQuery, $this->db);
                
                $queryResult = $this->db->query($historyQuery);
                
                $result = array();
                
                while($complaint = $queryResult->fetch_assoc())
                {
                   $result[] = $complaint; 
                }
                    
                
                   
        }
                $this->deliver_response("200", "Complaints", $result);
             
        }   
        else {
              $this->deliver_response("403", "Forbidden", null);
        }
        $this->log("Exited complaints()");
    }
    
    
    
    public function deliver_response($status, $statusMsg, $data)
{
    header("HTTP/1/1 $status $statusMsg");
    header("Content-Type:application/json");
    $_RESPONSE["status"] = $status;
    $_RESPONSE["statusMsg"] = $statusMsg;
    $_RESPONSE["data"] = $data;
    $json_response = json_encode($_RESPONSE);
    echo $json_response;
}

  public function log($logString)
                {
                 global $DEBUG;
                 if($DEBUG)              
                 echo $logString .'<br>';               
                    
                }
    
}; // end class

 $apiClass = new AWOS_API;
 $apiClass->processApi();

 
 
 
 ob_end_flush();